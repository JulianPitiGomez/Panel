<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Favoritos del comprador (el corazon). Logueado: tienda_favoritos por comprador.
 * Anonimo: en la sesion, y al ingresar se pasan a su cuenta. Asi el que mira sin
 * cuenta no pierde lo que marco.
 */
class TiendaFavoritoService
{
    public const SESION = 'tienda_favoritos';

    private string $prefijo;

    public function __construct(string $prefijo)
    {
        $this->prefijo = $prefijo;
    }

    private function tabla()
    {
        return DB::connection('client_db')->table($this->prefijo . 'tienda_favoritos');
    }

    private function compradorId(): ?int
    {
        return session('tienda_comprador_web_id') ? (int) session('tienda_comprador_web_id') : null;
    }

    /** Codigos marcados (strings), del mas nuevo al mas viejo. */
    public function codigos(): array
    {
        $id = $this->compradorId();
        if ($id) {
            try {
                return $this->tabla()->where('comprador_id', $id)->orderByDesc('creado')->pluck('codigo')->map(fn ($c) => (string) $c)->all();
            } catch (\Throwable $e) {
                return [];   // sin la tabla todavia
            }
        }

        return array_values(array_map('strval', session(self::SESION, [])));
    }

    public function es(string $codigo): bool
    {
        return in_array((string) $codigo, $this->codigos(), true);
    }

    /** Marca o desmarca. Devuelve true si quedo marcado. */
    public function alternar(string $codigo): bool
    {
        $codigo = (string) $codigo;
        $id = $this->compradorId();
        if ($id) {
            try {
                if ($this->tabla()->where('comprador_id', $id)->where('codigo', $codigo)->exists()) {
                    $this->tabla()->where('comprador_id', $id)->where('codigo', $codigo)->delete();

                    return false;
                }
                $this->tabla()->insert(['comprador_id' => $id, 'codigo' => $codigo, 'creado' => now()]);

                return true;
            } catch (\Throwable $e) {
                // sin tabla: cae a la sesion
            }
        }
        $lista = array_values(array_map('strval', session(self::SESION, [])));
        if (in_array($codigo, $lista, true)) {
            $lista = array_values(array_diff($lista, [$codigo]));
            session([self::SESION => $lista]);

            return false;
        }
        array_unshift($lista, $codigo);
        session([self::SESION => array_slice($lista, 0, 200)]);

        return true;
    }

    /** Al ingresar: lo marcado sin cuenta pasa a la cuenta. */
    public function fusionarSesion(): void
    {
        $id = $this->compradorId();
        $lista = array_values(array_map('strval', session(self::SESION, [])));
        if (!$id || empty($lista)) {
            return;
        }
        try {
            $ya = $this->tabla()->where('comprador_id', $id)->pluck('codigo')->map(fn ($c) => (string) $c)->all();
            foreach (array_diff($lista, $ya) as $codigo) {
                $this->tabla()->insert(['comprador_id' => $id, 'codigo' => $codigo, 'creado' => now()]);
            }
            session()->forget(self::SESION);
        } catch (\Throwable $e) {
            // sin tabla: quedan en sesion
        }
    }

    public function cantidad(): int
    {
        return count($this->codigos());
    }
}
