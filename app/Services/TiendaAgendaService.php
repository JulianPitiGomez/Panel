<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Agenda de entregas: "para cuando lo quiere" el comprador y "cuando puede" el comercio.
 *
 * Resuelve el caso del que fabrica a pedido (tortas, canastas, muebles): vende una
 * capacidad de produccion, no un stock. Ver plan, seccion "Para cuando lo quiere".
 *
 *  - anticipacion minima general y POR ARTICULO (tienda_articu.anticipacion_horas):
 *    la del carrito es la mayor de las de sus articulos.
 *  - anticipacion maxima en dias, franjas por dia de la semana con cupo, cupo diario,
 *    hora de corte, excepciones por fecha, cierre manual de la tienda.
 *  - el calendario del checkout ofrece SOLO lo que el comercio puede cumplir, y se
 *    revalida al confirmar (validar()).
 *
 * Si la agenda no esta activa, la tienda se comporta como hoy: fecha libre desde hoy.
 */
class TiendaAgendaService
{
    public const DIAS = [0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];

    private string $prefijo;

    public function __construct(string $prefijo)
    {
        $this->prefijo = $prefijo;
    }

    private function tabla(string $nombre)
    {
        return DB::connection('client_db')->table($this->prefijo . $nombre);
    }

    // ------------------------------------------------------------------ config

    public static function configDefault(): array
    {
        return [
            'activa' => 0, 'anticipacion_min_horas' => 0, 'anticipacion_max_dias' => 14, 'margen_minutos' => 0,
            'hora_corte' => null, 'cupo_diario' => null, 'abierta' => 1, 'recibe_cerrada' => 1,
            'muestra_cerrada' => 1, 'permite_lo_antes_posible' => 1,
        ];
    }

    public function config(): array
    {
        $fila = $this->tabla('tienda_agenda')->first();
        $cfg = $fila ? array_merge(self::configDefault(), (array) $fila) : self::configDefault();
        if (!empty($cfg['hora_corte'])) {
            $cfg['hora_corte'] = substr($cfg['hora_corte'], 0, 5);
        }
        return $cfg;
    }

    public function guardarConfig(array $datos): void
    {
        $datos = array_intersect_key($datos, self::configDefault());
        foreach (['activa', 'abierta', 'recibe_cerrada', 'muestra_cerrada', 'permite_lo_antes_posible'] as $b) {
            if (array_key_exists($b, $datos)) {
                $datos[$b] = $datos[$b] ? 1 : 0;
            }
        }
        foreach (['anticipacion_min_horas', 'anticipacion_max_dias', 'margen_minutos'] as $n) {
            if (array_key_exists($n, $datos)) {
                $datos[$n] = max(0, (int) $datos[$n]);
            }
        }
        if (array_key_exists('anticipacion_max_dias', $datos) && $datos['anticipacion_max_dias'] < 1) {
            $datos['anticipacion_max_dias'] = 1;
        }
        if (array_key_exists('cupo_diario', $datos)) {
            $datos['cupo_diario'] = is_numeric($datos['cupo_diario']) && (int) $datos['cupo_diario'] > 0 ? (int) $datos['cupo_diario'] : null;
        }
        if (array_key_exists('hora_corte', $datos)) {
            $datos['hora_corte'] = preg_match('/^\d{1,2}:\d{2}/', (string) $datos['hora_corte']) ? substr($datos['hora_corte'], 0, 5) . ':00' : null;
        }

        $existente = $this->tabla('tienda_agenda')->first();
        if ($existente) {
            $this->tabla('tienda_agenda')->where('id', $existente->id)->update($datos);
        } else {
            $this->tabla('tienda_agenda')->insert(array_merge(self::configDefault(), $datos));
        }
    }

    // ----------------------------------------------------------------- franjas

    public function franjas(bool $soloActivas = false): array
    {
        $q = $this->tabla('tienda_agenda_franjas')->orderBy('dia_semana')->orderBy('hora_desde')->orderBy('orden');
        if ($soloActivas) {
            $q->where('activa', 1);
        }
        return $q->get()->map(function ($f) {
            $f->hora_desde = substr($f->hora_desde, 0, 5);
            $f->hora_hasta = substr($f->hora_hasta, 0, 5);
            $f->etiqueta = $f->hora_desde . ' a ' . $f->hora_hasta;
            return $f;
        })->all();
    }

    /** Guarda la lista completa de franjas (alta, modificacion y baja). */
    public function guardarFranjas(array $franjas): void
    {
        $existentes = $this->tabla('tienda_agenda_franjas')->pluck('id')->all();
        $vistas = [];

        foreach (array_values($franjas) as $orden => $f) {
            $dia = (int) ($f['dia_semana'] ?? -1);
            $desde = self::normalizarHora($f['hora_desde'] ?? null);
            $hasta = self::normalizarHora($f['hora_hasta'] ?? null);
            if ($dia < 0 || $dia > 6 || !$desde || !$hasta || $hasta <= $desde) {
                continue;
            }
            $fila = [
                'dia_semana'  => $dia,
                'hora_desde'  => $desde,
                'hora_hasta'  => $hasta,
                'cupo_franja' => is_numeric($f['cupo_franja'] ?? null) && (int) $f['cupo_franja'] > 0 ? (int) $f['cupo_franja'] : null,
                'vale_envio'  => !empty($f['vale_envio']) ? 1 : 0,
                'vale_retiro' => !empty($f['vale_retiro']) ? 1 : 0,
                'activa'      => !empty($f['activa']) ? 1 : 0,
                'orden'       => $orden,
            ];
            $id = (int) ($f['id'] ?? 0);
            if ($id > 0 && in_array($id, $existentes, true)) {
                $this->tabla('tienda_agenda_franjas')->where('id', $id)->update($fila);
                $vistas[] = $id;
            } else {
                $vistas[] = $this->tabla('tienda_agenda_franjas')->insertGetId($fila);
            }
        }

        $borrar = array_diff($existentes, $vistas);
        if (!empty($borrar)) {
            $this->tabla('tienda_agenda_franjas')->whereIn('id', $borrar)->delete();
        }
    }

    // ------------------------------------------------------------- excepciones

    public function excepciones(bool $soloFuturas = false): array
    {
        $q = $this->tabla('tienda_agenda_excepciones')->orderBy('fecha');
        if ($soloFuturas) {
            $q->where('fecha', '>=', today()->toDateString());
        }
        return $q->get()->all();
    }

    public function guardarExcepciones(array $excepciones): void
    {
        $this->tabla('tienda_agenda_excepciones')->delete();
        $vistas = [];
        foreach ($excepciones as $e) {
            $fecha = $e['fecha'] ?? null;
            if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || isset($vistas[$fecha])) {
                continue;
            }
            $vistas[$fecha] = true;
            $this->tabla('tienda_agenda_excepciones')->insert([
                'fecha' => $fecha,
                'cerrado' => array_key_exists('cerrado', $e) ? (!empty($e['cerrado']) ? 1 : 0) : 1,
                'motivo' => mb_substr(trim((string) ($e['motivo'] ?? '')), 0, 80) ?: null,
            ]);
        }
    }

    // ---------------------------------------------------------- disponibilidad

    /** Anticipacion minima del carrito en horas: la general o la mayor de sus articulos. */
    public function anticipacionCarrito(array $carrito, ?array $config = null): int
    {
        $config ??= $this->config();
        $horas = (int) $config['anticipacion_min_horas'];
        $codigos = array_map(fn ($i) => $i['codigo'], $carrito);
        if (!empty($codigos)) {
            $max = (int) $this->tabla('tienda_articu')->whereIn('codigo', $codigos)->max('anticipacion_horas');
            $horas = max($horas, $max);
        }
        return $horas;
    }

    /** El carrito consume cupo si alguno de sus articulos lo consume (default si). */
    public function carritoConsumeCupo(array $carrito): bool
    {
        $codigos = array_map(fn ($i) => $i['codigo'], $carrito);
        if (empty($codigos)) {
            return true;
        }
        $sinCupo = $this->tabla('tienda_articu')->whereIn('codigo', $codigos)->where('consume_cupo', 0)->pluck('codigo')->all();
        foreach ($codigos as $c) {
            if (!in_array($c, $sinCupo)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Dias y franjas que el comercio puede cumplir para este carrito y modalidad.
     * @param string $modo 'retiro' o cualquier envio (flota/correo/a_convenir)
     * @return array{activa: bool, abierta: bool, mensaje_cerrada: ?string, anticipacion_horas: int, lo_antes_posible: ?array, dias: array}
     *   dias: [['fecha' => 'Y-m-d', 'etiqueta' => 'Lun 08/09', 'franjas' => [['id','etiqueta','disponible','motivo']]]]
     */
    public function disponibilidad(array $carrito, string $modo, ?Carbon $ahora = null): array
    {
        $cfg = $this->config();
        $ahora ??= now();
        $out = [
            'activa' => (bool) $cfg['activa'],
            'abierta' => (bool) $cfg['abierta'],
            'mensaje_cerrada' => null,
            'anticipacion_horas' => 0,
            'lo_antes_posible' => null,
            'dias' => [],
        ];

        if (!$cfg['abierta'] && $cfg['muestra_cerrada']) {
            $out['mensaje_cerrada'] = $cfg['recibe_cerrada']
                ? 'La tienda está cerrada por el momento. Podés dejar tu pedido y lo preparamos cuando volvamos a abrir.'
                : 'La tienda está cerrada por el momento. No estamos tomando pedidos.';
        }

        if (!$cfg['activa']) {
            return $out;
        }

        $anticipacion = $this->anticipacionCarrito($carrito, $cfg);
        $out['anticipacion_horas'] = $anticipacion;
        $esRetiro = $modo === TiendaPedidoService::ENTREGA_RETIRO;

        // Momento minimo de entrega: ahora + anticipacion + margen
        $minimo = $ahora->copy()->addHours($anticipacion)->addMinutes((int) $cfg['margen_minutos']);

        // Hora de corte: pasado el corte, hoy ya no se ofrece
        $primerDia = $ahora->copy()->startOfDay();
        if ($cfg['hora_corte'] && $ahora->format('H:i') >= $cfg['hora_corte']) {
            $primerDia->addDay();
        }

        $franjas = collect($this->franjas(true))->filter(fn ($f) => $esRetiro ? $f->vale_retiro : $f->vale_envio)->groupBy('dia_semana');
        $excepciones = collect($this->excepciones(true))->keyBy('fecha');
        $ocupacion = $this->ocupacion($primerDia->toDateString(), $primerDia->copy()->addDays((int) $cfg['anticipacion_max_dias'])->toDateString());
        $cupoDiario = $cfg['cupo_diario'] ? (int) $cfg['cupo_diario'] : null;

        for ($d = 0; $d < (int) $cfg['anticipacion_max_dias']; $d++) {
            $dia = $primerDia->copy()->addDays($d);
            $fecha = $dia->toDateString();
            $exc = $excepciones[$fecha] ?? null;
            if ($exc && $exc->cerrado) {
                continue;
            }

            $delDia = $franjas[$dia->dayOfWeek] ?? collect();
            if ($delDia->isEmpty() && !($exc && !$exc->cerrado)) {
                continue;
            }

            $usadosDia = (int) ($ocupacion['dia'][$fecha] ?? 0);
            $diaLleno = $cupoDiario !== null && $usadosDia >= $cupoDiario;
            $listaFranjas = [];
            $algunaDisponible = false;

            foreach ($delDia as $f) {
                $inicio = Carbon::parse($fecha . ' ' . $f->hora_desde);
                $fin = Carbon::parse($fecha . ' ' . $f->hora_hasta);
                $usadosFranja = (int) ($ocupacion['franja'][$fecha][$f->id] ?? 0);
                $motivo = null;
                if ($fin->lte($minimo)) {
                    $motivo = 'Ya no llegamos con la anticipación necesaria';
                } elseif ($diaLleno) {
                    $motivo = 'Día completo';
                } elseif ($f->cupo_franja !== null && $usadosFranja >= (int) $f->cupo_franja) {
                    $motivo = 'Franja completa';
                }
                $disponible = $motivo === null;
                $algunaDisponible = $algunaDisponible || $disponible;
                $listaFranjas[] = [
                    'id' => (int) $f->id,
                    'etiqueta' => $f->etiqueta,
                    'hora_desde' => $f->hora_desde,
                    'hora_hasta' => $f->hora_hasta,
                    'disponible' => $disponible,
                    'motivo' => $motivo,
                ];
            }

            // Dia abierto por excepcion sin franjas cargadas: se ofrece el dia entero
            if ($exc && !$exc->cerrado && empty($listaFranjas)) {
                $disponible = !$diaLleno && Carbon::parse($fecha . ' 23:59')->gt($minimo);
                $listaFranjas[] = ['id' => 0, 'etiqueta' => 'Todo el día', 'hora_desde' => '00:00', 'hora_hasta' => '23:59', 'disponible' => $disponible, 'motivo' => $disponible ? null : 'Sin disponibilidad'];
                $algunaDisponible = $algunaDisponible || $disponible;
            }

            if (!$algunaDisponible) {
                continue;   // no se muestran dias en los que no hay nada que elegir
            }

            $out['dias'][] = [
                'fecha' => $fecha,
                'etiqueta' => self::etiquetaDia($dia),
                'franjas' => $listaFranjas,
            ];
        }

        if ($cfg['permite_lo_antes_posible'] && !empty($out['dias'])) {
            $primero = $out['dias'][0];
            $franja = collect($primero['franjas'])->firstWhere('disponible', true);
            $out['lo_antes_posible'] = [
                'fecha' => $primero['fecha'],
                'franja_id' => $franja['id'] ?? null,
                'etiqueta' => 'Lo antes posible (' . $primero['etiqueta'] . ($franja ? ', ' . $franja['etiqueta'] : '') . ')',
            ];
        }

        return $out;
    }

    /**
     * Revalidacion al confirmar: la fecha/franja elegida sigue disponible?
     * Devuelve null si esta bien, o el motivo si no.
     */
    public function validar(array $carrito, string $modo, ?string $fecha, ?int $franjaId, ?Carbon $ahora = null): ?string
    {
        $disp = $this->disponibilidad($carrito, $modo, $ahora);
        if (!$disp['activa']) {
            return null;
        }
        if (!$disp['abierta'] && !$this->config()['recibe_cerrada']) {
            return 'La tienda está cerrada y no está tomando pedidos.';
        }
        if (!$fecha) {
            return 'Elegí cuándo querés recibir el pedido.';
        }
        foreach ($disp['dias'] as $d) {
            if ($d['fecha'] !== $fecha) {
                continue;
            }
            foreach ($d['franjas'] as $f) {
                if ((int) $f['id'] === (int) $franjaId) {
                    return $f['disponible'] ? null : ('Esa franja ya no está disponible: ' . $f['motivo'] . '.');
                }
            }
            return 'Elegí una franja horaria para ese día.';
        }
        return 'Ese día ya no está disponible. Elegí otro.';
    }

    /**
     * Cuantos pedidos web ya ocupan cada dia y cada franja en un rango de fechas.
     * Cuenta solo los que consumieron cupo y no estan anulados en gestion.
     */
    private function ocupacion(string $desde, string $hasta): array
    {
        $anulados = TiendaEstadoPedido::anulados();
        $filas = $this->tabla('tienda_pedidos as t')
            ->join($this->prefijo . 'pedidos_encab as e', 'e.id', '=', 't.idpedido')
            ->whereBetween('t.entrega_fecha', [$desde, $hasta])
            ->where('t.agenda_cupo', 1)
            ->when(!empty($anulados), fn ($q) => $q->whereNotIn('e.estado', $anulados))
            ->groupBy('t.entrega_fecha', 't.entrega_franja_id')
            ->selectRaw('t.entrega_fecha AS fecha, t.entrega_franja_id AS franja, COUNT(*) AS n')
            ->get();

        $out = ['dia' => [], 'franja' => []];
        foreach ($filas as $f) {
            $out['dia'][$f->fecha] = ($out['dia'][$f->fecha] ?? 0) + (int) $f->n;
            $out['franja'][$f->fecha][(int) $f->franja] = (int) $f->n;
        }
        return $out;
    }

    public static function etiquetaDia(Carbon $dia): string
    {
        $nombre = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'][$dia->dayOfWeek];
        if ($dia->isToday()) {
            return 'Hoy ' . $dia->format('d/m');
        }
        if ($dia->isTomorrow()) {
            return 'Mañana ' . $dia->format('d/m');
        }
        return $nombre . ' ' . $dia->format('d/m');
    }

    public static function normalizarHora($valor): ?string
    {
        if (!is_string($valor) || !preg_match('/^(\d{1,2}):(\d{2})/', trim($valor), $m)) {
            return null;
        }
        $h = (int) $m[1];
        $mi = (int) $m[2];
        if ($h > 23 || $mi > 59) {
            return null;
        }
        return sprintf('%02d:%02d:00', $h, $mi);
    }
}
