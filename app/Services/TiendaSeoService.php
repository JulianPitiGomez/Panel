<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Metadatos y sitemap de la tienda (F6): que Google la encuentre.
 *
 *  - Titulo y descripcion de la tienda: tienda_meta_titulo / tienda_meta_descripcion
 *    del JSON de config web; sin cargar, se arman con el nombre y el slogan.
 *  - Por producto: tienda_articu.meta_titulo / meta_descripcion; sin cargar, el
 *    nombre del articulo y el principio de su descripcion larga.
 *  - Solo la tienda publica y la vitrina se indexan. La privada va con noindex y
 *    sitemap vacio: su catalogo no es para cualquiera.
 */
class TiendaSeoService
{
    public const SITEMAP_MAX = 5000;
    public const SITEMAP_TTL = 3600;

    private string $prefijo;

    public function __construct(string $prefijo)
    {
        $this->prefijo = $prefijo;
    }

    /** Meta de la portada / catalogo. $datos = tienda_datos de la sesion. */
    public static function metaTienda(array $datos, string $nombre, string $modo, string $url): array
    {
        $titulo = trim((string) ($datos['tienda_meta_titulo'] ?? '')) ?: ($nombre . ' | Tienda online');
        $descripcion = trim((string) ($datos['tienda_meta_descripcion'] ?? ''))
            ?: trim((string) ($datos['slogan'] ?? '')) ?: "Comprá online en {$nombre}. Catálogo, precios y envíos.";

        return [
            'title'       => mb_substr($titulo, 0, 70),
            'description' => mb_substr($descripcion, 0, 160),
            'url'         => $url,
            'image'       => session('tienda_logo') ?: '',
            'type'        => 'website',
            'robots'      => self::indexable($modo) ? 'index, follow' : 'noindex, nofollow',
            'jsonld'      => null,
        ];
    }

    /** Meta de la ficha de un producto; null si no existe o no se puede mostrar. */
    public function metaProducto($codigo, array $datos, string $nombreTienda, string $modo, string $url, ?float $precio, ?string $foto, bool $conStock): ?array
    {
        $a = DB::connection('client_db')
            ->table("{$this->prefijo}articu as a")
            ->leftJoin("{$this->prefijo}tienda_articu as ta", 'a.CODIGO', '=', 'ta.codigo')
            ->leftJoin("{$this->prefijo}marcas as m", 'm.CODIGO', '=', 'a.MARCA')
            ->where('a.CODIGO', $codigo)->where('a.NOSALE', 0)
            ->whereRaw('COALESCE(ta.visible_web, 1) = 1')
            ->first(['a.NOMBRE', 'ta.meta_titulo', 'ta.meta_descripcion', 'ta.descripcion_larga', 'm.NOMBRE as marca']);
        if (!$a) {
            return null;
        }

        $titulo = trim((string) $a->meta_titulo) ?: (trim($a->NOMBRE) . ' | ' . $nombreTienda);
        $descripcion = trim((string) $a->meta_descripcion)
            ?: (trim((string) $a->descripcion_larga) ? mb_substr(preg_replace('/\s+/', ' ', trim($a->descripcion_larga)), 0, 155) : "Comprá {$a->NOMBRE} en {$nombreTienda}.");
        $mostrarPrecio = $precio !== null && $precio > 0 && !TiendaService::esVitrina($modo) ? $precio : null;

        $jsonld = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => trim($a->NOMBRE),
            'sku'         => (string) $codigo,
            'description' => $descripcion,
            'url'         => $url,
        ];
        if ($foto) {
            $jsonld['image'] = [$foto];
        }
        if ($a->marca) {
            $jsonld['brand'] = ['@type' => 'Brand', 'name' => trim($a->marca)];
        }
        if ($mostrarPrecio !== null) {
            $jsonld['offers'] = [
                '@type'         => 'Offer',
                'priceCurrency' => 'ARS',
                'price'         => number_format($mostrarPrecio, 2, '.', ''),
                'availability'  => $conStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url'           => $url,
            ];
        }

        return [
            'title'       => mb_substr($titulo, 0, 70),
            'description' => mb_substr($descripcion, 0, 160),
            'url'         => $url,
            'image'       => $foto ?: (session('tienda_logo') ?: ''),
            'type'        => 'product',
            'robots'      => self::indexable($modo) ? 'index, follow' : 'noindex, nofollow',
            'jsonld'      => $jsonld,
        ];
    }

    public static function indexable(?string $modo): bool
    {
        return !TiendaService::esPrivada($modo);
    }

    /** XML del sitemap: catalogo + fichas visibles. Cacheado; la privada devuelve un sitemap vacio. */
    public function sitemap(string $slug, string $modo): string
    {
        if (!self::indexable($modo)) {
            return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
        }

        $clave = "tienda_sitemap_{$this->prefijo}_" . TiendaMetricaService::versionCatalogo($this->prefijo);

        return Cache::remember($clave, self::SITEMAP_TTL, function () use ($slug) {
            $hoy = now()->toDateString();
            $lineas = [];
            $lineas[] = '<url><loc>' . e(route('tienda.catalogo', $slug)) . '</loc><lastmod>' . $hoy . '</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>';

            $codigos = DB::connection('client_db')
                ->table("{$this->prefijo}articu as a")
                ->leftJoin("{$this->prefijo}tienda_articu as ta", 'a.CODIGO', '=', 'ta.codigo')
                ->where('a.NOSALE', 0)
                ->whereRaw('COALESCE(ta.visible_web, 1) = 1')
                ->orderBy('a.CODIGO')
                ->limit(self::SITEMAP_MAX)
                ->pluck('a.CODIGO');
            foreach ($codigos as $c) {
                $lineas[] = '<url><loc>' . e(route('tienda.producto', [$slug, $c])) . '</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>';
            }

            return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
                . implode("\n", $lineas) . "\n"
                . '</urlset>';
        });
    }

    /** Lo que edita el comercio por producto (modal de fotos). */
    public function fichaDe($codigo): array
    {
        $r = DB::connection('client_db')->table("{$this->prefijo}tienda_articu")->where('codigo', $codigo)->first(['descripcion_larga', 'meta_titulo', 'meta_descripcion']);

        return [
            'descripcion'      => (string) ($r->descripcion_larga ?? ''),
            'meta_titulo'      => (string) ($r->meta_titulo ?? ''),
            'meta_descripcion' => (string) ($r->meta_descripcion ?? ''),
        ];
    }
}
