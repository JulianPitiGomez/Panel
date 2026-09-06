<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Galeria de fotos por articulo ({prefijo}tienda_fotos).
 *
 * Varias fotos por articulo, con orden y una principal. articu_fotos.url sigue
 * guardando el thumb de la principal: es lo que leen la grilla del catalogo y
 * gestion, asi nada de lo existente cambia. Las fotos viejas (una por articulo,
 * en la clave fija productos/{p}/{codigo}) se adoptan a la galeria al tocarlas.
 */
class TiendaFotoService
{
    private string $prefijo;
    private ImagenProductoService $imagenes;

    public function __construct(string $prefijo)
    {
        $this->prefijo = $prefijo;
        $this->imagenes = app(ImagenProductoService::class);
    }

    private function tabla(string $nombre)
    {
        return DB::connection('client_db')->table($this->prefijo . $nombre);
    }

    /** Fotos de un articulo en orden (principal primero). */
    public function de($codigo): array
    {
        $this->adoptarExistente($codigo);

        return $this->tabla('tienda_fotos')
            ->where('codigo', $codigo)
            ->orderByDesc('principal')->orderBy('orden')->orderBy('id')
            ->get()->all();
    }

    /** URLs medium de la galeria para la ficha, principal primero (vacio si no hay). */
    public function urlsFicha($codigo): array
    {
        return array_map(fn ($f) => ['thumb' => $f->url_thumb, 'medium' => $f->url_medium, 'original' => $f->url_original], $this->de($codigo));
    }

    /** Sube una foto nueva. La primera queda como principal. */
    public function agregar($codigo, string $rutaLocal): object
    {
        $this->adoptarExistente($codigo);

        $subida = $this->imagenes->subirEnGaleria($this->prefijo, (string) $codigo, $rutaLocal);
        $hay = $this->tabla('tienda_fotos')->where('codigo', $codigo)->count();
        $orden = (int) $this->tabla('tienda_fotos')->where('codigo', $codigo)->max('orden') + 1;

        $id = $this->tabla('tienda_fotos')->insertGetId([
            'codigo' => $codigo,
            'clave' => $subida['clave'],
            'url_thumb' => $subida['thumb'],
            'url_medium' => $subida['medium'],
            'url_original' => $subida['original'],
            'orden' => $orden,
            'principal' => $hay === 0 ? 1 : 0,
            'creado' => now(),
        ]);

        $this->sincronizarArticuFotos($codigo);

        return $this->tabla('tienda_fotos')->where('id', $id)->first();
    }

    public function eliminar(int $id): void
    {
        $foto = $this->tabla('tienda_fotos')->where('id', $id)->first();
        if (!$foto) {
            return;
        }

        // La clave fija de una foto vieja (sin slot) se borra con el metodo viejo
        if (preg_match('~^productos/[^/]+/[^/]+$~', $foto->clave)) {
            $this->imagenes->eliminar($this->prefijo, (string) $foto->codigo);
        } else {
            $this->imagenes->eliminarClave($foto->clave);
        }

        $this->tabla('tienda_fotos')->where('id', $id)->delete();

        // Si era la principal, pasa a serlo la primera que quede
        if ($foto->principal) {
            $siguiente = $this->tabla('tienda_fotos')->where('codigo', $foto->codigo)->orderBy('orden')->orderBy('id')->first();
            if ($siguiente) {
                $this->tabla('tienda_fotos')->where('id', $siguiente->id)->update(['principal' => 1]);
            }
        }

        $this->sincronizarArticuFotos($foto->codigo);
    }

    /** Borra todas las fotos de un articulo. */
    public function eliminarTodas($codigo): void
    {
        foreach ($this->de($codigo) as $f) {
            $this->eliminar((int) $f->id);
        }
    }

    public function principal(int $id): void
    {
        $foto = $this->tabla('tienda_fotos')->where('id', $id)->first();
        if (!$foto) {
            return;
        }
        $this->tabla('tienda_fotos')->where('codigo', $foto->codigo)->update(['principal' => 0]);
        $this->tabla('tienda_fotos')->where('id', $id)->update(['principal' => 1]);
        $this->sincronizarArticuFotos($foto->codigo);
    }

    /** Mueve una foto un lugar arriba o abajo dentro de su articulo. */
    public function mover(int $id, string $direccion): void
    {
        $foto = $this->tabla('tienda_fotos')->where('id', $id)->first();
        if (!$foto) {
            return;
        }
        $lista = $this->tabla('tienda_fotos')->where('codigo', $foto->codigo)->orderBy('orden')->orderBy('id')->pluck('id')->all();
        $i = array_search($id, $lista);
        $j = $direccion === 'arriba' ? $i - 1 : $i + 1;
        if ($i === false || $j < 0 || $j >= count($lista)) {
            return;
        }
        [$lista[$i], $lista[$j]] = [$lista[$j], $lista[$i]];
        foreach ($lista as $orden => $fid) {
            $this->tabla('tienda_fotos')->where('id', $fid)->update(['orden' => $orden + 1]);
        }
    }

    /**
     * articu_fotos.url = thumb de la principal (lo que ya leian la grilla y gestion).
     * Sin fotos en la galeria, se borra la fila de articu_fotos.
     */
    public function sincronizarArticuFotos($codigo): void
    {
        $principal = $this->tabla('tienda_fotos')->where('codigo', $codigo)->orderByDesc('principal')->orderBy('orden')->orderBy('id')->first();

        if (!$principal) {
            $this->tabla('articu_fotos')->where('codigo', $codigo)->delete();
            return;
        }

        if ($this->tabla('articu_fotos')->where('codigo', $codigo)->exists()) {
            $this->tabla('articu_fotos')->where('codigo', $codigo)->update(['url' => $principal->url_thumb]);
        } else {
            $this->tabla('articu_fotos')->insert(['codigo' => $codigo, 'url' => $principal->url_thumb]);
        }
    }

    /**
     * Un articulo con foto vieja (articu_fotos con la clave fija) pero sin galeria:
     * se crea su fila en tienda_fotos apuntando a las mismas variantes, sin mover nada.
     */
    private function adoptarExistente($codigo): void
    {
        if ($this->tabla('tienda_fotos')->where('codigo', $codigo)->exists()) {
            return;
        }
        $url = $this->tabla('articu_fotos')->where('codigo', $codigo)->value('url');
        if (!$url || !preg_match('~/thumb\.jpg(\?|$)~', $url)) {
            return;   // sin foto, o foto de otro proveedor (Cloudinary viejo): no se adopta
        }

        $this->tabla('tienda_fotos')->insert([
            'codigo' => $codigo,
            'clave' => "productos/{$this->prefijo}/{$codigo}",
            'url_thumb' => $url,
            'url_medium' => ImagenProductoService::variante($url, 'medium'),
            'url_original' => ImagenProductoService::variante($url, 'original'),
            'orden' => 1,
            'principal' => 1,
            'creado' => now(),
        ]);
    }
}
