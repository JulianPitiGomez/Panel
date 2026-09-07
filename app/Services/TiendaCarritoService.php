<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Foto del carrito del comprador logueado (tienda_carritos), para recuperar los
 * abandonados (F7). Se actualiza cada vez que cambia el carrito; al confirmar el
 * pedido se marca convertido. El comando tienda:carritos-abandonados busca los que
 * quedaron quietos mas de N horas y, si el comercio activo WhatsApp, les manda la
 * plantilla de recuperacion una sola vez.
 */
class TiendaCarritoService
{
    private string $prefijo;

    public function __construct(string $prefijo)
    {
        $this->prefijo = $prefijo;
    }

    private function tabla()
    {
        return DB::connection('client_db')->table($this->prefijo . 'tienda_carritos');
    }

    /** Guarda o actualiza la foto del carrito del comprador en sesion. Nunca rompe la tienda. */
    public function guardar(array $carrito): void
    {
        $compradorId = session('tienda_comprador_web_id');
        if (!$compradorId) {
            return;
        }
        try {
            $items = array_values(array_map(fn ($i) => ['codigo' => $i['codigo'], 'nombre' => $i['nombre'], 'cantidad' => (float) $i['cantidad'], 'precio' => (float) $i['precio']], $carrito));
            if (empty($items)) {
                $this->tabla()->where('comprador_id', (int) $compradorId)->whereNull('convertido')->delete();

                return;
            }
            $fila = [
                'items' => json_encode($items, JSON_UNESCAPED_UNICODE),
                'cantidad' => count($items),
                'total' => (float) array_sum(array_map(fn ($i) => $i['cantidad'] * $i['precio'], $items)),
                'actualizado' => now(),
            ];
            $abierto = $this->tabla()->where('comprador_id', (int) $compradorId)->whereNull('convertido')->first();
            if ($abierto) {
                $this->tabla()->where('id', $abierto->id)->update($fila);
            } else {
                $this->tabla()->insert(array_merge($fila, ['comprador_id' => (int) $compradorId, 'creado' => now()]));
            }
        } catch (\Throwable $e) {
            // sin la tabla todavia: se ignora
        }
    }

    /** El comprador confirmo: el carrito abierto queda convertido. */
    public function convertido(int $idpedido): void
    {
        $compradorId = session('tienda_comprador_web_id');
        if (!$compradorId) {
            return;
        }
        try {
            $this->tabla()->where('comprador_id', (int) $compradorId)->whereNull('convertido')->update(['convertido' => now(), 'idpedido' => $idpedido]);
        } catch (\Throwable $e) {
            // sin tabla
        }
    }

    /** Carritos quietos hace mas de $horas, sin convertir y sin aviso, con el comprador. */
    public function abandonados(int $horas, int $limite = 100)
    {
        return $this->tabla()
            ->join($this->prefijo . 'tienda_compradores as c', 'c.id', '=', $this->prefijo . 'tienda_carritos.comprador_id')
            ->whereNull('convertido')->whereNull('avisado')
            ->where('actualizado', '<', now()->subHours($horas))
            ->where('actualizado', '>', now()->subDays(7))
            ->orderBy('actualizado')->limit($limite)
            ->get([$this->prefijo . 'tienda_carritos.*', 'c.nombre', 'c.telefono', 'c.email', 'c.codcli']);
    }

    public function marcarAvisado(int $id, ?string $resultado = null): void
    {
        $this->tabla()->where('id', $id)->update(['avisado' => now(), 'aviso_resultado' => $resultado ? mb_substr($resultado, 0, 120) : null]);
    }
}
