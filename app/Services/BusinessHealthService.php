<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * "Tu negocio hoy" — capa de cálculo (Fase 1, on-demand).
 *
 * Todas las consultas corren contra la conexión 'client_db' (ya configurada
 * por el componente que invoca este servicio) usando el prefijo del comercio.
 * Solo lectura, siempre acotadas por FECHA (indexada). No toca el esquema.
 */
class BusinessHealthService
{
    // Umbrales (en %). Fáciles de tunear.
    private const CREC_ROJO = -10.0;   // caída mensual por debajo de esto = rojo
    private const CONC_AMARILLO = 20.0; // participación del 1er ítem
    private const CONC_ROJO = 35.0;
    private const RITMO_ROJO = -10.0;   // proyección vs mes anterior
    // Piso de ventas identificadas (% del total facturado) para que la
    // concentración por cliente signifique algo. Por debajo de esto casi todo
    // es mostrador y el "top cliente" es ruido estadístico: la concentración
    // se mide solo por producto.
    private const IDENT_MINIMO = 15.0;

    /**
     * Devuelve el resumen completo de indicadores para un comercio.
     *
     * @param string $prefix Prefijo de tabla del comercio (ej. ge_000123)
     * @param string $pack   'GE3' (pymes) o 'RE3' (resto)
     */
    public function resumen(string $prefix, string $pack): array
    {
        $conn = DB::connection('client_db');
        $hoy = Carbon::now();

        // ---- Rangos de fecha (a la fecha / MTD para comparaciones justas) ----
        $diaDelMes    = (int) $hoy->day;
        $iniActual    = $hoy->copy()->startOfMonth()->format('Y-m-d');
        $finActual    = $hoy->copy()->format('Y-m-d');

        $iniMesAnt    = $hoy->copy()->subMonthNoOverflow()->startOfMonth();
        $finMesAntFull = $iniMesAnt->copy()->endOfMonth();
        // mismo día del mes anterior (sin desbordar fin de mes)
        $finMesAntMtd = $iniMesAnt->copy()->addDays($diaDelMes - 1);
        if ($finMesAntMtd->gt($finMesAntFull)) {
            $finMesAntMtd = $finMesAntFull->copy();
        }

        $iniAnioAnt   = $hoy->copy()->subYearNoOverflow()->startOfMonth();
        $finAnioAntFull = $iniAnioAnt->copy()->endOfMonth();
        $finAnioAntMtd = $iniAnioAnt->copy()->addDays($diaDelMes - 1);
        if ($finAnioAntMtd->gt($finAnioAntFull)) {
            $finAnioAntMtd = $finAnioAntFull->copy();
        }

        $b = [
            'ini_act'      => $iniActual,
            'fin_act'      => $finActual,
            'ini_ant'      => $iniMesAnt->format('Y-m-d'),
            'fin_ant_mtd'  => $finMesAntMtd->format('Y-m-d'),
            'fin_ant_full' => $finMesAntFull->format('Y-m-d'),
            'ini_yoy'      => $iniAnioAnt->format('Y-m-d'),
            'fin_yoy_mtd'  => $finAnioAntMtd->format('Y-m-d'),
            'min'          => $iniAnioAnt->format('Y-m-d'),
            'max'          => $finActual,
        ];

        // Columna de monto de descuento según pack (el decimal (12,2) es monto).
        $descMontoCol = $pack === 'GE3' ? 'DESCU' : 'descup';

        // Los ~2000 bases de clientes conviven en distintas versiones de esquema:
        // detectamos qué columnas existen y degradamos con neutrales si faltan.
        $detCols = $this->columnas($conn, $prefix . 'ventas_det');
        $encCols = $this->columnas($conn, $prefix . 'ventas_encab');

        // Si el comercio no tiene las tablas de ventas (base demo/vacía o en otra
        // ubicación), no es un error: devolvemos vacío y la vista muestra "sin datos".
        if (empty($encCols) || empty($detCols)) {
            return [];
        }

        $descExpr   = in_array(strtolower($descMontoCol), $detCols, true) ? $descMontoCol : '0';
        $netoExpr   = in_array('neto', $detCols, true) ? 'NETO' : 'IMPORTE';
        $pcostoExpr = in_array('pcosto', $detCols, true) ? 'PCOSTO' : '0';
        $hasCodcli  = in_array('codcli', $encCols, true);

        $encab = $this->consultarEncabezado($conn, $prefix, $b, $hasCodcli);
        $det   = $this->consultarDetalle($conn, $prefix, $b, $descExpr);
        $topProductos = $this->topProductos($conn, $prefix, $b, $netoExpr, $pcostoExpr);
        $topClientes  = $hasCodcli ? $this->topClientes($conn, $prefix, $b) : [];
        $medios       = $this->mediosPago($conn, $prefix, $b);

        // El peso de cada cliente se lee contra el TOTAL facturado (mostrador
        // incluido), no contra el subconjunto identificado: si no, un cliente
        // que pesa 4% del negocio aparece como 40% y asusta de más.
        $totalMes = (float) ($encab->fact_act ?? 0);
        foreach ($topClientes as &$tc) {
            $tc['pct_total'] = $totalMes > 0 ? ($tc['fact'] / $totalMes) * 100 : 0;
        }
        unset($tc);

        // ---- Armado de indicadores ----
        $origen       = $this->indicadorOrigenVentas($encab, $hasCodcli, $pack);
        $crecimiento  = $this->indicadorCrecimiento($encab);
        $ticket       = $this->indicadorTicket($encab, $det);
        $concentracion = $this->indicadorConcentracion($topProductos, $topClientes, $det, $encab, $origen);
        $ritmo        = $this->indicadorRitmo($encab, $hoy, $diaDelMes);
        $descuentos   = $this->indicadorDescuentos($det);

        $prioridades = $this->tresCosasParaHoy($crecimiento, $ticket, $concentracion, $ritmo, $descuentos);

        return [
            'meta' => [
                'periodo'       => $hoy->locale('es')->isoFormat('MMMM YYYY'),
                'dia_del_mes'   => $diaDelMes,
                'dias_del_mes'  => (int) $hoy->copy()->daysInMonth,
                'generado'      => $hoy->format('d/m/Y H:i'),
                'pack'          => $pack,
            ],
            'prioridades'   => $prioridades,
            'origen'        => $origen,
            'crecimiento'   => $crecimiento,
            'ticket'        => $ticket,
            'concentracion' => $concentracion,
            'ritmo'         => $ritmo,
            'descuentos'    => $descuentos,
            'top_productos' => $topProductos,
            'top_clientes'  => $topClientes,
            'medios_pago'   => $medios,
        ];
    }

    // ======================= CONSULTAS =======================

    private function consultarEncabezado($conn, string $prefix, array $b, bool $hasCodcli): object
    {
        // Fechas generadas por Carbon (formato Y-m-d), se interpolan directo:
        // este MySQL no reutiliza bindings nombrados (emulate prepares off).
        $iniAct = $b['ini_act']; $finAct = $b['fin_act'];
        $iniAnt = $b['ini_ant']; $finAntMtd = $b['fin_ant_mtd']; $finAntFull = $b['fin_ant_full'];
        $iniYoy = $b['ini_yoy']; $finYoyMtd = $b['fin_yoy_mtd'];

        // Cliente identificable = todo lo que no sea consumidor final. CODCLI 1
        // y -1 son consumidor final; 0/NULL es venta sin cliente cargado. Los
        // tres van al mismo balde "mostrador": suman al total, no son clientes.
        $esIdent = 'COALESCE(CODCLI,0) NOT IN (1,-1,0)';
        $enMes   = "FECHA BETWEEN '{$iniAct}' AND '{$finAct}'";

        $identExpr = $hasCodcli
            ? "SUM(CASE WHEN {$enMes} AND {$esIdent} THEN IMPORTE * IF(TICOMP='NC',-1,1) ELSE 0 END)"
            : '0';
        // Comprobantes y clientes distintos del tramo identificado: el resto
        // (mostrador) sale por diferencia, sin escanear la tabla de nuevo.
        $identCompExpr = $hasCodcli
            ? "SUM(CASE WHEN {$enMes} AND {$esIdent} AND TICOMP<>'NC' THEN 1 ELSE 0 END)"
            : '0';
        $identCantExpr = $hasCodcli
            ? "COUNT(DISTINCT CASE WHEN {$enMes} AND {$esIdent} THEN CODCLI END)"
            : '0';

        $sql = "
            SELECT
                SUM(CASE WHEN FECHA BETWEEN '{$iniAct}' AND '{$finAct}' THEN IMPORTE * IF(TICOMP='NC',-1,1) ELSE 0 END) AS fact_act,
                SUM(CASE WHEN FECHA BETWEEN '{$iniAnt}' AND '{$finAntMtd}' THEN IMPORTE * IF(TICOMP='NC',-1,1) ELSE 0 END) AS fact_ant_mtd,
                SUM(CASE WHEN FECHA BETWEEN '{$iniAnt}' AND '{$finAntFull}' THEN IMPORTE * IF(TICOMP='NC',-1,1) ELSE 0 END) AS fact_ant_full,
                SUM(CASE WHEN FECHA BETWEEN '{$iniYoy}' AND '{$finYoyMtd}' THEN IMPORTE * IF(TICOMP='NC',-1,1) ELSE 0 END) AS fact_yoy_mtd,
                SUM(CASE WHEN FECHA BETWEEN '{$iniAct}' AND '{$finAct}' AND TICOMP<>'NC' THEN 1 ELSE 0 END) AS comp_act,
                SUM(CASE WHEN FECHA BETWEEN '{$iniAnt}' AND '{$finAntMtd}' AND TICOMP<>'NC' THEN 1 ELSE 0 END) AS comp_ant,
                {$identExpr} AS fact_ident_act,
                {$identCompExpr} AS comp_ident_act,
                {$identCantExpr} AS clientes_ident_act
            FROM {$prefix}ventas_encab
            WHERE (FECHA BETWEEN '{$iniAnt}' AND '{$finAct}')
               OR (FECHA BETWEEN '{$iniYoy}' AND '{$finYoyMtd}')
        ";
        $row = $conn->select($sql);
        return $row[0] ?? (object) [];
    }

    private function consultarDetalle($conn, string $prefix, array $b, string $descExpr): object
    {
        $iniAct = $b['ini_act']; $finAct = $b['fin_act'];
        $iniAnt = $b['ini_ant']; $finAntMtd = $b['fin_ant_mtd'];

        $sql = "
            SELECT
                SUM(CASE WHEN FECHA BETWEEN '{$iniAct}' AND '{$finAct}' AND LEFT(NROFAC,2)<>'NC' THEN CANTIDAD ELSE 0 END) AS unid_act,
                COUNT(DISTINCT CASE WHEN FECHA BETWEEN '{$iniAct}' AND '{$finAct}' AND LEFT(NROFAC,2)<>'NC' THEN NROFAC END) AS tick_act,
                SUM(CASE WHEN FECHA BETWEEN '{$iniAnt}' AND '{$finAntMtd}' AND LEFT(NROFAC,2)<>'NC' THEN CANTIDAD ELSE 0 END) AS unid_ant,
                COUNT(DISTINCT CASE WHEN FECHA BETWEEN '{$iniAnt}' AND '{$finAntMtd}' AND LEFT(NROFAC,2)<>'NC' THEN NROFAC END) AS tick_ant,
                SUM(CASE WHEN FECHA BETWEEN '{$iniAct}' AND '{$finAct}' THEN IMPORTE * IF(LEFT(NROFAC,2)='NC',-1,1) ELSE 0 END) AS fact_det_act,
                SUM(CASE WHEN FECHA BETWEEN '{$iniAct}' AND '{$finAct}' THEN {$descExpr} ELSE 0 END) AS desc_monto_act
            FROM {$prefix}ventas_det
            WHERE FECHA BETWEEN '{$iniAnt}' AND '{$finAct}'
        ";
        $row = $conn->select($sql);
        return $row[0] ?? (object) [];
    }

    private function topProductos($conn, string $prefix, array $b, string $netoExpr, string $pcostoExpr): array
    {
        $iniAct = $b['ini_act']; $finAct = $b['fin_act'];
        $signo = "IF(LEFT(NROFAC,2)='NC',-1,1)";

        // Renglones sin costo confiable — se excluyen del margen (no del facturado):
        //  · PCOSTO = 0 → "sin costo cargado", NO costo cero. Contarlo daría 100% de margen.
        //  · CODART < 0 → depto en vez de artículo. Según la versión de la base, PCOSTO
        //    queda en 0 O se guarda con el PRECIO DE VENTA (verificado en ge_000084:
        //    97.340 de 97.395 renglones con PCOSTO = PUNIT), lo que fabrica un margen
        //    negativo espurio de exactamente -21% (el IVA). Un depto no tiene costo.
        //  · IMPORTE < 0 → promo. PCOSTO queda en 0 o en negativo, según la base.
        $conCosto   = "CODART > 0 AND IMPORTE >= 0 AND {$pcostoExpr} > 0";
        $margenExpr = "CASE WHEN {$conCosto} THEN ({$netoExpr} - {$pcostoExpr}*CANTIDAD) * {$signo} ELSE 0 END";
        $baseExpr   = "CASE WHEN {$conCosto} THEN {$netoExpr} * {$signo} ELSE 0 END";

        $sql = "
            SELECT
                CODART,
                MAX(DETART) AS detalle,
                SUM(IMPORTE * {$signo}) AS fact,
                SUM({$margenExpr}) AS margen,
                SUM({$baseExpr}) AS base_margen,
                SUM(CASE WHEN {$conCosto} THEN 1 ELSE 0 END) AS rengl_con_costo,
                SUM(CANTIDAD * {$signo}) AS unidades
            FROM {$prefix}ventas_det
            WHERE FECHA BETWEEN '{$iniAct}' AND '{$finAct}'
            GROUP BY CODART
            ORDER BY fact DESC
            LIMIT 10
        ";

        return array_map(function ($r) {
            $tieneCosto = (int) $r->rengl_con_costo > 0;
            $base = (float) $r->base_margen;
            return [
                'codart'      => $r->CODART,
                'detalle'     => $r->detalle,
                'fact'        => (float) $r->fact,
                // null = ningún renglón del producto tiene costo cargado ⇒ "s/costo"
                'margen'      => $tieneCosto ? (float) $r->margen : null,
                'margen_pct'  => ($tieneCosto && $base > 0) ? ((float) $r->margen / $base) * 100 : null,
                'unidades'    => (float) $r->unidades,
            ];
        }, $conn->select($sql));
    }

    private function topClientes($conn, string $prefix, array $b): array
    {
        $iniAct = $b['ini_act']; $finAct = $b['fin_act'];

        // Hay clientes homónimos con distinta dirección: el nombre solo no alcanza
        // para distinguirlos, se muestran también código y dirección.
        $cliCols = $this->columnas($conn, $prefix . 'clientes');
        $dirExpr = in_array('direccion', $cliCols, true) ? "NULLIF(TRIM(c.DIRECCION), '')" : 'NULL';

        $sql = "
            SELECT
                e.CODCLI,
                COALESCE(NULLIF(TRIM(c.nombre), ''), CONCAT('Cliente ', e.CODCLI)) AS nombre,
                {$dirExpr} AS direccion,
                SUM(e.IMPORTE * IF(e.TICOMP='NC',-1,1)) AS fact,
                SUM(CASE WHEN e.TICOMP<>'NC' THEN 1 ELSE 0 END) AS comprobantes
            FROM {$prefix}ventas_encab e
            LEFT JOIN {$prefix}clientes c ON c.codigo = e.CODCLI
            WHERE e.FECHA BETWEEN '{$iniAct}' AND '{$finAct}'
              AND COALESCE(e.CODCLI,0) NOT IN (1,-1,0)
            GROUP BY e.CODCLI, nombre, direccion
            ORDER BY fact DESC
            LIMIT 10
        ";
        return array_map(fn ($r) => [
            'codcli'       => $r->CODCLI,
            'nombre'       => $r->nombre,
            'direccion'    => $r->direccion !== null ? trim((string) $r->direccion) : null,
            'fact'         => (float) $r->fact,
            'comprobantes' => (int) $r->comprobantes,
        ], $conn->select($sql));
    }

    private function mediosPago($conn, string $prefix, array $b): array
    {
        $iniAct = $b['ini_act']; $finAct = $b['fin_act'];
        $sql = "
            SELECT
                COALESCE(NULLIF(TRIM(observa), ''), 'Sin especificar') AS forma,
                SUM(importe * IF(ticomp='NC',-1,1)) AS total
            FROM {$prefix}concfact
            WHERE fecha BETWEEN '{$iniAct}' AND '{$finAct}'
            GROUP BY forma
            ORDER BY total DESC
        ";
        $rows = $conn->select($sql);
        $total = array_sum(array_map(fn ($r) => (float) $r->total, $rows));
        return array_map(fn ($r) => [
            'forma' => $r->forma,
            'total' => (float) $r->total,
            'pct'   => $total > 0 ? ((float) $r->total / $total) * 100 : 0,
        ], $rows);
    }

    // ======================= INDICADORES =======================

    /**
     * "De dónde vienen tus ventas": separa mostrador (consumidor final) de
     * clientes identificados.
     *
     * Las ventas de mostrador SUMAN en los totales pero NO son un cliente:
     * nunca entran al ranking ni al conteo de clientes. Es un bloque
     * descriptivo, sin semáforo: informa, no evalúa.
     *
     * Devuelve null cuando la base no tiene CODCLI (esquema viejo) y el corte
     * no se puede hacer: en ese caso el bloque no se muestra.
     */
    private function indicadorOrigenVentas(object $e, bool $hasCodcli, string $pack): ?array
    {
        if (!$hasCodcli) {
            return null;
        }

        $total = (float) ($e->fact_act ?? 0);
        if ($total <= 0) {
            // Sin ventas en el mes no hay mix que mostrar (puede haber
            // comprobantes en cero): el bloque no se dibuja.
            return null;
        }

        $ident = (float) ($e->fact_ident_act ?? 0);
        $mostrador = $total - $ident;

        $compIdent = (int) ($e->comp_ident_act ?? 0);
        $compMostrador = max(0, (int) ($e->comp_act ?? 0) - $compIdent);

        $pctIdent     = ($ident / $total) * 100;
        $pctMostrador = ($mostrador / $total) * 100;
        $clientes     = (int) ($e->clientes_ident_act ?? 0);

        $ticketMostrador = $compMostrador > 0 ? $mostrador / $compMostrador : 0;
        $ticketIdent     = $compIdent > 0 ? $ident / $compIdent : 0;

        $label = $pack === 'RE3' ? 'Consumidor final' : 'Mostrador';
        $plural = $clientes === 1 ? 'cliente' : 'clientes';
        $txtIdent = $this->pctEntero($pctIdent);

        if ($ident <= 0 || $clientes === 0) {
            $lectura = sprintf('Este mes todas tus ventas fueron de %s, sin cliente identificado.', mb_strtolower($label));
        } elseif ($mostrador <= 0) {
            $lectura = sprintf('Todas tus ventas de este mes son a clientes con nombre (%d %s).', $clientes, $plural);
        } elseif ($pctIdent < self::IDENT_MINIMO) {
            $lectura = sprintf(
                'Casi todo pasa por %s. Los clientes con nombre son el %s%% de tus ventas (%d %s este mes).',
                mb_strtolower($label), $txtIdent, $clientes, $plural
            );
        } else {
            $lectura = sprintf(
                'El %s%% de tus ventas es a clientes con nombre (%d %s este mes); el resto pasa por %s.',
                $txtIdent, $clientes, $plural, mb_strtolower($label)
            );
        }

        // Acción: si el cliente con nombre gasta bastante más por compra, vale
        // la pena registrar a los habituales del mostrador.
        $accion = 'Registrar a los clientes que vuelven te deja seguirlos de cerca.';
        if ($ticketIdent > 0 && $ticketMostrador > 0) {
            $ratio = $ticketIdent / $ticketMostrador;
            if ($ratio >= 1.5) {
                $accion = sprintf(
                    'Tus clientes con nombre gastan %s veces más por compra: cargar los datos de los habituales del %s te deja verlos acá.',
                    number_format($ratio, 1), mb_strtolower($label)
                );
            } elseif ($ratio <= 0.67) {
                $accion = sprintf('El %s deja un ticket más alto: mirá qué se lleva ese público para reforzarlo.', mb_strtolower($label));
            }
        }

        return [
            'titulo'           => 'De dónde vienen tus ventas',
            'icono'            => 'fa-shop',
            'label_mostrador'  => $label,
            'total'            => $total,
            'fact_mostrador'   => $mostrador,
            'fact_ident'       => $ident,
            'pct_mostrador'    => $pctMostrador,
            'pct_ident'        => $pctIdent,
            // Ya redondeados para mostrar, sin 0%/100% engañosos:
            'pct_mostrador_txt' => $this->pctEntero($pctMostrador),
            'pct_ident_txt'     => $txtIdent,
            'comp_mostrador'   => $compMostrador,
            'comp_ident'       => $compIdent,
            'clientes_ident'   => $clientes,
            'ticket_mostrador' => $ticketMostrador,
            'ticket_ident'     => $ticketIdent,
            // Por debajo del piso, la concentración por cliente no se evalúa.
            'cli_relevante'    => $pctIdent >= self::IDENT_MINIMO,
            'ident_minimo'     => self::IDENT_MINIMO,
            'lectura'          => $lectura,
            'accion'           => $accion,
        ];
    }

    private function indicadorCrecimiento(object $e): array
    {
        $act = (float) ($e->fact_act ?? 0);
        $ant = (float) ($e->fact_ant_mtd ?? 0);
        $yoy = (float) ($e->fact_yoy_mtd ?? 0);

        $momPct = $this->pct($act, $ant);
        $yoyPct = $this->pct($act, $yoy);

        $semaforo = 'verde';
        if ($momPct !== null && $momPct < self::CREC_ROJO) {
            $semaforo = 'rojo';
        } elseif ($momPct !== null && $momPct < 0) {
            $semaforo = 'amarillo';
        }

        $lectura = $momPct === null
            ? 'Todavía no hay base del mes anterior para comparar.'
            : ($momPct >= 0
                ? sprintf('Facturación a la fecha %s%% por encima del mismo tramo del mes pasado.', number_format($momPct, 1))
                : sprintf('Facturación a la fecha %s%% por debajo del mismo tramo del mes pasado.', number_format(abs($momPct), 1)));
        $lectura .= ' (Valores nominales, sin ajustar por inflación.)';

        return [
            'titulo'   => 'Crecimiento (nominal)',
            'icono'    => 'fa-chart-line',
            'valor_actual' => $act,
            'valor_ant'    => $ant,
            'valor_yoy'    => $yoy,
            'mom_pct'  => $momPct,
            'yoy_pct'  => $yoyPct,
            'semaforo' => $semaforo,
            'lectura'  => $lectura,
            'accion'   => $semaforo === 'verde'
                ? 'Vas bien en pesos; cargá el IPC para ver el crecimiento real.'
                : 'Mirá qué días o rubros aflojaron y probá alguna promo puntual.',
        ];
    }

    private function indicadorTicket(object $e, object $d): array
    {
        $comp = (float) ($e->comp_act ?? 0);
        $compAnt = (float) ($e->comp_ant ?? 0);
        $ticketProm = $comp > 0 ? (float) ($e->fact_act ?? 0) / $comp : 0;
        $ticketPromAnt = $compAnt > 0 ? (float) ($e->fact_ant_mtd ?? 0) / $compAnt : 0;

        $tick = (float) ($d->tick_act ?? 0);
        $tickAnt = (float) ($d->tick_ant ?? 0);
        $uxt = $tick > 0 ? (float) ($d->unid_act ?? 0) / $tick : 0;
        $uxtAnt = $tickAnt > 0 ? (float) ($d->unid_ant ?? 0) / $tickAnt : 0;

        $ticketPct = $this->pct($ticketProm, $ticketPromAnt);
        $uxtPct = $this->pct($uxt, $uxtAnt);

        // Lectura cruzada: ticket sube por precio pero bajan unidades = achique.
        $semaforo = 'verde';
        $lectura = 'Ticket y unidades por compra estables o en alza. Buen equilibrio.';
        if ($uxtPct !== null && $uxtPct < 0) {
            if ($ticketPct !== null && $ticketPct > 0) {
                $semaforo = 'amarillo';
                $lectura = 'El ticket sube, pero más por precio que por cantidad: se están llevando algo menos de unidades por compra. Hay margen para impulsar el volumen.';
            } else {
                $semaforo = 'rojo';
                $lectura = 'El ticket y las unidades por compra vienen un poco por debajo del mes pasado.';
            }
        }

        return [
            'titulo'   => 'Ticket promedio y unidades',
            'icono'    => 'fa-receipt',
            'ticket_prom' => $ticketProm,
            'ticket_prom_ant' => $ticketPromAnt,
            'ticket_pct' => $ticketPct,
            'unidades_ticket' => $uxt,
            'unidades_ticket_ant' => $uxtAnt,
            'unidades_pct' => $uxtPct,
            'semaforo' => $semaforo,
            'lectura'  => $lectura,
            'accion'   => $semaforo === 'verde'
                ? 'Sostené la propuesta; probá combos para subir unidades por ticket.'
                : 'Impulsá venta cruzada y combos para recuperar unidades por compra.',
        ];
    }

    private function indicadorConcentracion(array $productos, array $clientes, object $d, object $e, ?array $origen): array
    {
        $totalProd     = (float) ($d->fact_det_act ?? 0);
        $totalGeneral  = (float) ($e->fact_act ?? 0);
        $totalCliIdent = (float) ($e->fact_ident_act ?? 0);

        $top1Prod = $productos[0] ?? null;
        $top1ProdPct = ($top1Prod && $totalProd > 0) ? ($top1Prod['fact'] / $totalProd) * 100 : 0;
        $top10ProdPct = $totalProd > 0
            ? (array_sum(array_column($productos, 'fact')) / $totalProd) * 100 : 0;

        // El % que importa es sobre el TOTAL facturado, no sobre lo identificado:
        // un cliente que explica el 40% de lo identificado, cuando lo identificado
        // es el 10% del negocio, pesa 4%. El % sobre identificados queda como
        // dato secundario, para leer el mix interno de la cartera.
        $top1Cli = $clientes[0] ?? null;
        $sumTop10Cli = array_sum(array_column($clientes, 'fact'));

        $top1CliPct  = ($top1Cli && $totalGeneral > 0) ? ($top1Cli['fact'] / $totalGeneral) * 100 : 0;
        $top10CliPct = $totalGeneral > 0 ? ($sumTop10Cli / $totalGeneral) * 100 : 0;
        $top1CliPctIdent  = ($top1Cli && $totalCliIdent > 0) ? ($top1Cli['fact'] / $totalCliIdent) * 100 : 0;
        $top10CliPctIdent = $totalCliIdent > 0 ? ($sumTop10Cli / $totalCliIdent) * 100 : 0;

        // Guardrail: con pocas ventas identificadas (kiosco, restaurante) el
        // "top cliente" es ruido y no debe encender el semáforo ni entrar a las
        // "3 cosas para mirar hoy". Ahí la concentración se mide por producto.
        $cliRelevante = $origen !== null && $origen['cli_relevante'] && $top1Cli !== null;

        $peor = $cliRelevante ? max($top1ProdPct, $top1CliPct) : $top1ProdPct;
        $semaforo = 'verde';
        if ($peor >= self::CONC_ROJO) {
            $semaforo = 'rojo';
        } elseif ($peor >= self::CONC_AMARILLO) {
            $semaforo = 'amarillo';
        }

        if ($top1ProdPct >= self::CONC_AMARILLO && $top1Prod) {
            $lectura = sprintf('Un solo producto ("%s") explica el %s%% de las ventas.',
                mb_substr($top1Prod['detalle'], 0, 30), number_format($top1ProdPct, 0));
        } elseif ($cliRelevante && $top1CliPct >= self::CONC_AMARILLO) {
            $lectura = sprintf('Un solo cliente ("%s") explica el %s%% de todo lo que facturás (%s%% de lo que vendés a clientes con nombre).',
                mb_substr($top1Cli['nombre'], 0, 30),
                number_format($top1CliPct, 0),
                number_format($top1CliPctIdent, 0));
        } elseif (!$cliRelevante && $origen !== null && $totalGeneral > 0) {
            $lectura = sprintf('Como casi todo pasa por %s, la concentración se mide por producto: el principal explica el %s%% del total.',
                mb_strtolower($origen['label_mostrador']), number_format($top1ProdPct, 0));
        } else {
            $lectura = 'Ventas bien repartidas entre productos y clientes.';
        }

        return [
            'titulo'   => 'Concentración',
            'icono'    => 'fa-layer-group',
            'top1_prod_pct'  => $top1ProdPct,
            'top10_prod_pct' => $top10ProdPct,
            // Sobre el total facturado (el número que se muestra grande):
            'top1_cli_pct'   => $top1CliPct,
            'top10_cli_pct'  => $top10CliPct,
            // Sobre las ventas identificadas (dato secundario):
            'top1_cli_pct_ident'  => $top1CliPctIdent,
            'top10_cli_pct_ident' => $top10CliPctIdent,
            'cli_relevante'  => $cliRelevante,
            'semaforo' => $semaforo,
            'lectura'  => $lectura,
            'accion'   => $semaforo === 'verde'
                ? 'Diversificación saludable; mantené la mezcla.'
                : ($cliRelevante
                    ? 'Sumar otros productos o clientes ayuda a no depender tanto de pocos.'
                    : 'Sumar otros productos ayuda a no depender tanto de pocos.'),
        ];
    }

    private function indicadorRitmo(object $e, Carbon $hoy, int $diaDelMes): array
    {
        $acumulado = (float) ($e->fact_act ?? 0);
        $diasMes = (int) $hoy->copy()->daysInMonth;
        $proyeccion = $diaDelMes > 0 ? ($acumulado / $diaDelMes) * $diasMes : 0;
        $mesAntFull = (float) ($e->fact_ant_full ?? 0);

        $pct = $this->pct($proyeccion, $mesAntFull);
        $semaforo = 'verde';
        if ($pct !== null && $pct < self::RITMO_ROJO) {
            $semaforo = 'rojo';
        } elseif ($pct !== null && $pct < 0) {
            $semaforo = 'amarillo';
        }

        $lectura = sprintf(
            'Al día %d de %d, proyección de cierre: $%s. Mes anterior: $%s.',
            $diaDelMes, $diasMes,
            number_format($proyeccion, 0, ',', '.'),
            number_format($mesAntFull, 0, ',', '.')
        );

        return [
            'titulo'   => 'Ritmo del mes',
            'icono'    => 'fa-gauge-high',
            'acumulado' => $acumulado,
            'proyeccion' => $proyeccion,
            'mes_ant_full' => $mesAntFull,
            'pct' => $pct,
            'semaforo' => $semaforo,
            'lectura'  => $lectura,
            'accion'   => $semaforo === 'verde'
                ? 'Vas en camino a igualar o superar el mes pasado.'
                : 'Con un empujón en los días que quedan podés alcanzar el mes pasado.',
        ];
    }

    private function indicadorDescuentos(object $d): array
    {
        $descuento = (float) ($d->desc_monto_act ?? 0);
        $fact = (float) ($d->fact_det_act ?? 0);
        $pct = $fact > 0 ? ($descuento / $fact) * 100 : 0;

        $semaforo = 'verde';
        if ($pct >= 10) {
            $semaforo = 'rojo';
        } elseif ($pct >= 5) {
            $semaforo = 'amarillo';
        }

        return [
            'titulo'   => 'Descuentos otorgados',
            'icono'    => 'fa-tags',
            'monto'    => $descuento,
            'pct'      => $pct,
            'semaforo' => $semaforo,
            'lectura'  => sprintf('Se otorgaron $%s en descuentos (%s%% de la facturación).',
                number_format($descuento, 0, ',', '.'), number_format($pct, 1)),
            'accion'   => $semaforo === 'verde'
                ? 'Nivel de descuentos bajo control.'
                : 'Conviene revisar quién y sobre qué se descuenta, para cuidar el margen.',
        ];
    }

    // ======================= 3 COSAS PARA MIRAR HOY =======================

    private function tresCosasParaHoy(array $crec, array $ticket, array $conc, array $ritmo, array $desc): array
    {
        $c = [];

        // 1) Caída de facturación mensual (a la fecha) — impacto en pesos.
        if ($crec['mom_pct'] !== null && $crec['mom_pct'] < 0) {
            $impacto = max(0, $crec['valor_ant'] - $crec['valor_actual']);
            $c[] = [
                'titulo'  => 'Facturación algo por debajo',
                'detalle' => $crec['lectura'],
                'accion'  => $crec['accion'],
                'impacto' => $impacto,
                'semaforo' => $crec['semaforo'],
            ];
        }

        // 2) Proyección por debajo del mes anterior — brecha a cerrar.
        if ($ritmo['pct'] !== null && $ritmo['pct'] < 0) {
            $impacto = max(0, $ritmo['mes_ant_full'] - $ritmo['proyeccion']);
            $c[] = [
                'titulo'  => 'Camino a un cierre algo por debajo',
                'detalle' => $ritmo['lectura'],
                'accion'  => 'Con los días que quedan podés achicar la brecha.',
                'impacto' => $impacto,
                'semaforo' => $ritmo['semaforo'],
            ];
        }

        // 3) Achique por unidades (ticket sube por precio, bajan unidades).
        if ($ticket['semaforo'] !== 'verde' && $ticket['unidades_pct'] !== null && $ticket['unidades_pct'] < 0) {
            // Estimación de pesos: fracción de unidades perdidas por ticket * facturación del mes.
            $unidadesPerdidas = max(0, $ticket['unidades_ticket_ant'] - $ticket['unidades_ticket']);
            $impacto = $ticket['unidades_ticket'] > 0
                ? $unidadesPerdidas * $crec['valor_actual'] / $ticket['unidades_ticket']
                : 0;
            // No podés perder más de lo que facturás: acotamos a la facturación del mes.
            $impacto = min($impacto, max(0, $crec['valor_actual']));
            $c[] = [
                'titulo'  => 'Se venden menos unidades por compra',
                'detalle' => $ticket['lectura'],
                'accion'  => $ticket['accion'],
                'impacto' => $impacto,
                'semaforo' => $ticket['semaforo'],
            ];
        }

        // 4) Concentración riesgosa — pesos "en riesgo" del ítem dominante.
        if ($conc['semaforo'] !== 'verde') {
            $c[] = [
                'titulo'  => 'Ventas concentradas en pocos',
                'detalle' => $conc['lectura'],
                'accion'  => $conc['accion'],
                'impacto' => 0, // ordena por severidad, no domina el ranking
                'semaforo' => $conc['semaforo'],
            ];
        }

        // 5) Descuentos altos — pesos otorgados.
        if ($desc['semaforo'] !== 'verde') {
            $c[] = [
                'titulo'  => 'Descuentos por encima de lo habitual',
                'detalle' => $desc['lectura'],
                'accion'  => $desc['accion'],
                'impacto' => $desc['monto'],
                'semaforo' => $desc['semaforo'],
            ];
        }

        // Ordenar por impacto en pesos desc y quedarnos con las 3 principales.
        usort($c, fn ($a, $z) => $z['impacto'] <=> $a['impacto']);

        return array_slice($c, 0, 3);
    }

    // ======================= HELPERS =======================

    /**
     * Porcentaje redondeado a entero para mostrar, sin mentir en los extremos:
     * un balde con plata nunca se muestra como 0%, y si al otro le queda algo
     * este no llega a 100%. Evita textos del tipo "el 100% ... el resto".
     */
    private function pctEntero(float $pct): string
    {
        if ($pct > 0 && $pct < 1) {
            return '1';
        }
        if ($pct > 99 && $pct < 100) {
            return '99';
        }
        return number_format(round($pct), 0);
    }

    private function pct(float $nuevo, float $viejo): ?float
    {
        if ($viejo <= 0) {
            return null;
        }
        return (($nuevo - $viejo) / $viejo) * 100;
    }

    /**
     * Nombres de columnas (en minúscula) de una tabla del comercio.
     * Permite degradar con neutrales cuando una base está en una versión
     * de esquema más vieja y le falta alguna columna.
     */
    private function columnas($conn, string $table): array
    {
        try {
            $rows = $conn->select("SHOW COLUMNS FROM {$table}");
            return array_map(fn ($r) => strtolower($r->Field), $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
