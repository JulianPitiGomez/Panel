<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Comision de BCN por pedido de la tienda web.
 *
 * Reglas (ver plan, seccion "La comision"):
 *  - Se devenga al CONFIRMAR el pedido, no al pagar ni al entregar.
 *  - Base = items - descuentos - promos, SIN envio.
 *  - El porcentaje sale de config_clientes.comision (si lComision), se copia al
 *    pedido y ahi queda congelado: cambiar la tarifa no reescribe el pasado.
 *  - Un pedido anulado o rechazado en gestion revierte la comision. Como eso lo
 *    hace Fivewin escribiendo directo en MySQL, hay un proceso de conciliacion
 *    que lo detecta (conciliar()).
 *  - Lo que el comercio debe por cada pedido vive en tienda_pedidos.comision_saldo
 *    (= importe al devengar, 0 al pagar o revertir), igual que pedidos.saldo en resto.
 *    El saldo del comercio es la suma de esos.
 */
class TiendaComisionService
{
    public const ESTADO_PENDIENTE = 'pendiente';   // todavia no calculada
    public const ESTADO_DEVENGADA = 'devengada';
    public const ESTADO_REVERTIDA = 'revertida';
    public const ESTADO_REVISAR   = 'revisar';     // el pedido desaparecio de pedidos_encab
    public const ESTADO_NO_APLICA = 'no_aplica';   // el cliente no paga comision

    private string $prefijo;

    public function __construct(string $prefijo)
    {
        $this->prefijo = $prefijo;
    }

    private function tabla(string $nombre)
    {
        return DB::connection('client_db')->table($this->prefijo . $nombre);
    }

    /** Porcentaje vigente del cliente (0 si no tiene comision activa). */
    public static function porcentajeCliente(int $clienteId): float
    {
        $c = DB::connection('config')->table('config_clientes')
            ->where('id', $clienteId)
            ->first(['comision', 'lComision']);

        return ($c && $c->lComision) ? (float) $c->comision : 0.0;
    }

    /**
     * Base de comision de un pedido: suma de renglones con su descuento, sin envio.
     * pedidos_det guarda descu como importe por unidad y punitario sin descontar.
     */
    public function baseDelPedido(int $idpedido): float
    {
        $base = $this->tabla('pedidos_det')
            ->where('idpedido', $idpedido)
            ->selectRaw('COALESCE(SUM(cantidad * (punitario - COALESCE(descu, 0))), 0) AS base')
            ->value('base');

        // Las promos (F3) no tocan pedidos_det: se restan del satelite. Se comisiona sobre lo que realmente se vendio.
        $promos = (float) ($this->tabla('tienda_pedidos')->where('idpedido', $idpedido)->value('descuento_promos') ?? 0);

        return round(max(0, (float) $base - $promos), 2);
    }

    public static function calcular(float $base, float $pct): float
    {
        return round($base * $pct / 100, 2);
    }

    /**
     * Devenga la comision de un pedido recien confirmado y la congela en el satelite.
     * Se llama dentro de la transaccion del alta del pedido.
     */
    public function devengar(int $idpedido, int $clienteId): array
    {
        $pct = self::porcentajeCliente($clienteId);
        $base = $this->baseDelPedido($idpedido);
        $importe = self::calcular($base, $pct);
        $aplica = $pct > 0;

        $datos = [
            'comision_base'    => $base,
            'comision_pct'     => $pct,
            'comision_importe' => $importe,
            'comision_estado'  => $aplica ? self::ESTADO_DEVENGADA : self::ESTADO_NO_APLICA,
            'comision_saldo'   => $aplica ? $importe : 0,
        ];

        $this->tabla('tienda_pedidos')->where('idpedido', $idpedido)->update($datos);

        if ($aplica) {
            (new TiendaPedidoService($this->prefijo))->registrarEvento(
                $idpedido,
                'comision_devengada',
                sprintf('%.2f%% sobre $%s = $%s', $pct, number_format($base, 2, ',', '.'), number_format($importe, 2, ',', '.'))
            );
        }

        return $datos;
    }

    /**
     * Conciliacion contra pedidos_encab. Corre periodicamente (comando tienda:conciliar-comisiones).
     *  - Pedido anulado/rechazado en gestion  -> comision revertida, saldo 0, evento.
     *  - Pedido que ya no existe              -> estado 'revisar', saldo 0, evento. No se revierte
     *    en silencio: un borrado puede ser un error tanto como una limpieza legitima.
     * Solo mira las devengadas; las ya revertidas / en revision no se vuelven a tocar.
     */
    public function conciliar(): array
    {
        $resumen = ['revisadas' => 0, 'revertidas' => 0, 'a_revisar' => 0, 'detalle' => []];
        $eventos = new TiendaPedidoService($this->prefijo);

        $devengadas = $this->tabla('tienda_pedidos as t')
            ->leftJoin($this->prefijo . 'pedidos_encab as e', 'e.id', '=', 't.idpedido')
            ->where('t.comision_estado', self::ESTADO_DEVENGADA)
            ->get(['t.idpedido', 't.comision_importe', 't.comision_saldo', 'e.estado', 'e.id as existe']);

        foreach ($devengadas as $p) {
            $resumen['revisadas']++;

            if ($p->existe === null) {
                $this->tabla('tienda_pedidos')->where('idpedido', $p->idpedido)->update([
                    'comision_estado' => self::ESTADO_REVISAR,
                    'comision_saldo'  => 0,
                ]);
                $eventos->registrarEvento($p->idpedido, 'comision_revisar',
                    "El pedido ya no existe en gestion; comision de $" . number_format($p->comision_importe, 2, ',', '.') . " apartada para revision",
                    'conciliacion');
                $resumen['a_revisar']++;
                $resumen['detalle'][] = ['idpedido' => $p->idpedido, 'importe' => $p->comision_importe, 'resultado' => 'revisar'];
                continue;
            }

            if (TiendaEstadoPedido::esAnulado($p->estado)) {
                $this->tabla('tienda_pedidos')->where('idpedido', $p->idpedido)->update([
                    'comision_estado' => self::ESTADO_REVERTIDA,
                    'comision_saldo'  => 0,
                ]);
                $eventos->registrarEvento($p->idpedido, 'comision_revertida',
                    "Pedido " . strtolower(TiendaEstadoPedido::etiqueta($p->estado)) . " en gestion; se revierte comision de $" . number_format($p->comision_importe, 2, ',', '.'),
                    'conciliacion');
                $resumen['revertidas']++;
                $resumen['detalle'][] = ['idpedido' => $p->idpedido, 'importe' => $p->comision_importe, 'resultado' => 'revertida', 'estado' => $p->estado];
            }
        }

        return $resumen;
    }

    /**
     * Lo que ve el comercio en su panel: saldo a pagar, pedidos que lo componen,
     * facturado, ticket promedio y plazo (N dias desde el primer pedido con saldo).
     * Portado de Panelresto/Inicio::cargarDatosComisiones().
     */
    public function resumenComercio(): array
    {
        $plazo = (int) config('tienda.comision_plazo_dias', 31);

        $s = $this->tabla('tienda_pedidos')
            ->where('comision_saldo', '>', 0)
            ->selectRaw('COALESCE(SUM(comision_saldo), 0) AS saldo')
            ->selectRaw('COALESCE(SUM(comision_base), 0) AS facturado')
            ->selectRaw('COUNT(*) AS cantidad')
            ->selectRaw('MIN(creado) AS fecha_ini')
            ->first();

        $cantidad = (int) $s->cantidad;
        $fechaIni = $s->fecha_ini ? \Carbon\Carbon::parse($s->fecha_ini)->startOfDay() : null;
        $fechaLim = $fechaIni ? $fechaIni->copy()->addDays($plazo) : null;

        return [
            'saldo'     => (float) $s->saldo,
            'facturado' => (float) $s->facturado,
            'cantidad'  => $cantidad,
            'promedio'  => $cantidad ? round((float) $s->facturado / $cantidad, 2) : 0.0,
            'fecha_ini' => $fechaIni?->toDateString(),
            'fecha_lim' => $fechaLim?->toDateString(),
            'dias_lim'  => $fechaLim ? now()->startOfDay()->diffInDays($fechaLim, false) : null,
        ];
    }

    /**
     * Vista interna (BCN): comision de un mes para este cliente.
     * $mes en formato YYYY-MM.
     */
    public function informeMes(string $mes): array
    {
        $desde = \Carbon\Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
        $hasta = $desde->copy()->endOfMonth();

        $r = $this->tabla('tienda_pedidos')
            ->whereBetween('creado', [$desde, $hasta])
            ->selectRaw('COUNT(*) AS pedidos')
            ->selectRaw('COALESCE(SUM(comision_base), 0) AS base')
            ->selectRaw("COALESCE(SUM(CASE WHEN comision_estado = 'devengada' THEN comision_importe END), 0) AS devengada")
            ->selectRaw("COALESCE(SUM(CASE WHEN comision_estado = 'revertida' THEN comision_importe END), 0) AS revertida")
            ->selectRaw("COALESCE(SUM(CASE WHEN comision_estado = 'revisar' THEN comision_importe END), 0) AS a_revisar")
            ->selectRaw('COALESCE(SUM(comision_saldo), 0) AS saldo')
            ->first();

        return [
            'pedidos'   => (int) $r->pedidos,
            'base'      => (float) $r->base,
            'devengada' => (float) $r->devengada,
            'revertida' => (float) $r->revertida,
            'a_revisar' => (float) $r->a_revisar,
            'saldo'     => (float) $r->saldo,
        ];
    }
}
