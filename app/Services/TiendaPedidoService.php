<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * El pedido web y su satelite ({prefijo}tienda_pedidos).
 *
 * Regla del proyecto: el pedido entra a pedidos_encab / pedidos_det exactamente
 * como hoy (eso lo sigue haciendo el carrito). Todo lo que es del ecommerce
 * (comprador web, entrega, pago, comision, estado web) vive en el satelite,
 * atado por idpedido, y se escribe desde aca.
 */
class TiendaPedidoService
{
    // entrega_modo
    public const ENTREGA_RETIRO     = 'retiro';
    public const ENTREGA_FLOTA      = 'flota';
    public const ENTREGA_CORREO     = 'correo';
    public const ENTREGA_A_CONVENIR = 'a_convenir';

    // pago_medio / pago_estado
    public const PAGO_A_CONVENIR   = 'a_convenir';
    public const PAGO_PENDIENTE    = 'pendiente';
    public const PAGO_NO_APLICA    = 'no_aplica';

    // comision_estado
    public const COMISION_PENDIENTE = 'pendiente';

    // estado_web
    public const WEB_RECIBIDO = 'recibido';

    private string $prefijo;

    public function __construct(string $prefijo)
    {
        $this->prefijo = $prefijo;
    }

    private function tabla(string $nombre)
    {
        return DB::connection('client_db')->table($this->prefijo . $nombre);
    }

    /**
     * Deja la fila satelite de un pedido recien creado y devenga la comision.
     * Se llama dentro de la misma transaccion que el alta en pedidos_encab:
     * si esto falla, el pedido no queda a medias.
     *
     * $datos admite cualquier columna de tienda_pedidos; lo que no venga toma
     * el valor de F0 (sin entrega, sin pago). $clienteId es el id en config_clientes,
     * de donde sale el porcentaje de comision.
     */
    public function registrarSatelite(int $idpedido, int $clienteId, array $datos = []): void
    {
        $fila = array_merge([
            'idpedido'        => $idpedido,
            'comprador_id'    => session('tienda_comprador_web_id'),
            'entrega_modo'    => self::ENTREGA_A_CONVENIR,
            'envio_importe'   => 0,
            'pago_medio'      => self::PAGO_A_CONVENIR,
            'pago_estado'     => self::PAGO_NO_APLICA,
            'iva_incluido'    => 0,
            'comision_base'   => 0,
            'comision_pct'    => 0,
            'comision_importe'=> 0,
            'comision_estado' => self::COMISION_PENDIENTE,
            'estado_web'      => self::WEB_RECIBIDO,
            'creado'          => now(),
        ], $datos);

        $this->tabla('tienda_pedidos')->insert($fila);

        $this->registrarEvento($idpedido, self::WEB_RECIBIDO, 'Pedido recibido desde la tienda web', 'comprador');

        (new TiendaComisionService($this->prefijo))->devengar($idpedido, $clienteId);
    }

    /** Anota un evento en el timeline del pedido (tienda_eventos). */
    public function registrarEvento(int $idpedido, string $estado, ?string $detalle = null, string $origen = 'sistema', ?string $usuario = null): void
    {
        $this->tabla('tienda_eventos')->insert([
            'idpedido' => $idpedido,
            'estado'   => $estado,
            'detalle'  => $detalle ? mb_substr($detalle, 0, 255) : null,
            'origen'   => $origen,
            'usuario'  => $usuario,
            'fecha'    => now(),
        ]);
    }

    /** Timeline de un pedido, del mas viejo al mas nuevo. */
    public function eventos(int $idpedido)
    {
        return $this->tabla('tienda_eventos')->where('idpedido', $idpedido)->orderBy('fecha')->orderBy('id')->get();
    }

    /** Fila satelite de un pedido (null si el pedido no vino de la tienda). */
    public function satelite(int $idpedido): ?object
    {
        return $this->tabla('tienda_pedidos')->where('idpedido', $idpedido)->first();
    }
}
