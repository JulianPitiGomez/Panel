<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Cloud;

/**
 * Fotos de productos de la tienda.
 *
 * Un solo lugar que sabe donde viven las fotos y como se arman sus URLs.
 * Ninguna pantalla habla con el proveedor de almacenamiento directo: si manana
 * R2 se cambia por otra cosa, se cambia el disco en config y este archivo.
 *
 * Por cada foto se guardan tres variantes con clave fija:
 *   productos/{prefijo}/{codigo}/thumb.jpg     400px  -> grilla del catalogo (lo que hoy se muestra)
 *   productos/{prefijo}/{codigo}/medium.jpg    900px  -> ficha del producto
 *   productos/{prefijo}/{codigo}/original.jpg  1600px -> zoom / galeria
 *
 * En articu_fotos.url se guarda la URL del thumb, igual que hasta ahora (400px),
 * asi el catalogo sigue mostrando exactamente lo mismo. Las otras variantes se
 * derivan de esa URL con variante(). La URL lleva ?v=timestamp para que la cache
 * de Cloudflare no sirva la foto vieja cuando el comercio la reemplaza.
 */
class ImagenProductoService
{
    public const VARIANTES = [
        'thumb'    => ['ancho' => 400,  'calidad' => 82],
        'medium'   => ['ancho' => 900,  'calidad' => 82],
        'original' => ['ancho' => 1600, 'calidad' => 85],
    ];

    private Cloud $disco;

    public function __construct(?string $disco = null)
    {
        $this->disco = Storage::disk($disco ?? config('filesystems.imagenes_productos', 'r2'));
    }

    /**
     * Sube una foto desde una ruta local, generando las tres variantes.
     * Devuelve la URL del thumb (la que se guarda en articu_fotos.url).
     */
    public function subir(string $prefijo, string $codigo, string $rutaLocal): string
    {
        $imagen = $this->abrir($rutaLocal);
        if (!$imagen) {
            throw new \RuntimeException('El archivo no es una imagen valida.');
        }

        $version = time();

        foreach (self::VARIANTES as $nombre => $spec) {
            $jpeg = $this->renderizar($imagen, $spec['ancho'], $spec['calidad']);
            $this->disco->put($this->clave($prefijo, $codigo, $nombre), $jpeg, [
                'ContentType' => 'image/jpeg',
                'CacheControl' => 'public, max-age=31536000',
            ]);
        }

        imagedestroy($imagen);

        return $this->url($prefijo, $codigo, 'thumb', $version);
    }

    /**
     * Galeria (F4): sube las tres variantes bajo una clave propia por foto, asi un
     * articulo puede tener varias. Devuelve la clave y las tres URLs (con ?v=).
     * @return array{clave: string, thumb: string, medium: string, original: string}
     */
    public function subirEnGaleria(string $prefijo, string $codigo, string $rutaLocal): array
    {
        $imagen = $this->abrir($rutaLocal);
        if (!$imagen) {
            throw new \RuntimeException('El archivo no es una imagen valida.');
        }

        $slot = 'g' . base_convert((string) (microtime(true) * 1000), 10, 36) . substr(bin2hex(random_bytes(2)), 0, 3);
        $clave = "productos/{$prefijo}/{$codigo}/{$slot}";
        $version = time();
        $urls = [];

        foreach (self::VARIANTES as $nombre => $spec) {
            $jpeg = $this->renderizar($imagen, $spec['ancho'], $spec['calidad']);
            $this->disco->put("{$clave}/{$nombre}.jpg", $jpeg, [
                'ContentType' => 'image/jpeg',
                'CacheControl' => 'public, max-age=31536000',
            ]);
            $urls[$nombre] = $this->disco->url("{$clave}/{$nombre}.jpg") . '?v=' . $version;
        }

        imagedestroy($imagen);

        return ['clave' => $clave] + $urls;
    }

    /** Borra las tres variantes de una foto de galeria por su clave. */
    public function eliminarClave(string $clave): void
    {
        $this->disco->delete(array_map(fn ($v) => "{$clave}/{$v}.jpg", array_keys(self::VARIANTES)));
    }

    /** Borra las tres variantes de una foto. */
    public function eliminar(string $prefijo, string $codigo): void
    {
        $claves = array_map(
            fn ($variante) => $this->clave($prefijo, $codigo, $variante),
            array_keys(self::VARIANTES)
        );
        $this->disco->delete($claves);
    }

    /** URL publica de una variante. */
    public function url(string $prefijo, string $codigo, string $variante = 'thumb', ?int $version = null): string
    {
        $url = $this->disco->url($this->clave($prefijo, $codigo, $variante));

        return $version ? $url . '?v=' . $version : $url;
    }

    /**
     * Dada la URL guardada (thumb), devuelve la URL de otra variante.
     * Sirve para que la ficha pida medium/original sin consultar nada.
     */
    public static function variante(?string $urlThumb, string $variante): ?string
    {
        if (!$urlThumb || !isset(self::VARIANTES[$variante])) {
            return $urlThumb;
        }

        return preg_replace('~/thumb\.jpg(\?|$)~', '/' . $variante . '.jpg$1', $urlThumb, 1);
    }

    private function clave(string $prefijo, string $codigo, string $variante): string
    {
        return "productos/{$prefijo}/{$codigo}/{$variante}.jpg";
    }

    /** @return \GdImage|null */
    private function abrir(string $ruta)
    {
        $info = @getimagesize($ruta);
        if (!$info) {
            return null;
        }

        $imagen = match ($info['mime']) {
            'image/jpeg' => imagecreatefromjpeg($ruta),
            'image/png'  => imagecreatefrompng($ruta),
            'image/webp' => imagecreatefromwebp($ruta),
            'image/gif'  => imagecreatefromgif($ruta),
            default      => null,
        };

        return $imagen ?: null;
    }

    /**
     * Redimensiona a un ancho maximo (sin agrandar) y devuelve el JPEG en memoria.
     * Fondo blanco para PNG/WebP con transparencia, que en JPEG saldria negro.
     */
    private function renderizar(\GdImage $origen, int $maxLado, int $calidad): string
    {
        $anchoOrig = imagesx($origen);
        $altoOrig  = imagesy($origen);

        if ($anchoOrig >= $altoOrig) {
            $ancho = min($maxLado, $anchoOrig);
            $alto  = intval($altoOrig * $ancho / $anchoOrig);
        } else {
            $alto  = min($maxLado, $altoOrig);
            $ancho = intval($anchoOrig * $alto / $altoOrig);
        }

        $lienzo = imagecreatetruecolor($ancho, $alto);
        $blanco = imagecolorallocate($lienzo, 255, 255, 255);
        imagefill($lienzo, 0, 0, $blanco);
        imagecopyresampled($lienzo, $origen, 0, 0, 0, 0, $ancho, $alto, $anchoOrig, $altoOrig);

        ob_start();
        imagejpeg($lienzo, null, $calidad);
        $jpeg = ob_get_clean();

        imagedestroy($lienzo);

        return $jpeg;
    }
}
