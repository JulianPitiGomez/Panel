<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class Cajas extends Component
{
    use WithPagination;

    // Filtros
    public $buscar = '';
    public $fechaDesde = '';
    public $fechaHasta = '';
    public $cajaSelecionada = '';
    public $tipoSaldo = '';
    public $tipoCaja = 'cerradas'; // 'cerradas' o 'abiertas'
    public $cajaAbiertaSelecionada = ''; // Para filtro de cajas abiertas

    // Paginación
    public $porPagina = 25;
    public $ordenarPor = 'fecha';
    public $ordenarDireccion = 'desc';

    // Totales
    public $totalFaltantes = 0;
    public $totalSobrantes = 0;
    public $totalCajas = 0;
    public $totalEfectivo = 0;

    // Panel lateral de detalle
    public $mostrarPanel = false;
    public $cajaSeleccionada = null;
    public $detallesCaja = [];

    // Para cajas abiertas
    public $cajasAbiertas = [];
    public $ventasTotalesTurno = 0;
    
    protected $tablePrefix;

    protected $queryString = [
        'buscar' => ['except' => ''],
        'fechaDesde' => ['except' => ''],
        'fechaHasta' => ['except' => ''],
        'cajaSelecionada' => ['except' => ''],
        'tipoSaldo' => ['except' => ''],
        'porPagina' => ['except' => 25],
    ];

    public function mount()
    {
        $this->setupClientDatabase();
        $this->fechaDesde = now()->subDays(30)->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
    }

    private function setupClientDatabase()
    {
        $clientId = session('client_id');
        if ($clientId) {
            $cliente = \App\Models\Cliente::find($clientId);
            
            if ($cliente) {                
                \Illuminate\Support\Facades\Config::set('database.connections.client_db.database', $cliente->base);
                DB::purge('client_db');
                session(['client_table_prefix' => $cliente->getTablePrefix()]);
                $this->tablePrefix = $cliente->getTablePrefix();
            }
        }
    }

    public function updatedBuscar()
    {
        $this->resetPage();
    }

    public function updatedFechaDesde()
    {
        $this->resetPage();
    }

    public function updatedFechaHasta()
    {
        $this->resetPage();
    }

    public function updatedCajaSelecionada()
    {
        $this->resetPage();
    }

    public function updatedTipoSaldo()
    {
        $this->resetPage();
    }

    public function updatedPorPagina()
    {
        $this->resetPage();
    }

    public function ordenar($campo)
    {
        if ($this->ordenarPor === $campo) {
            $this->ordenarDireccion = $this->ordenarDireccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenarPor = $campo;
            $this->ordenarDireccion = 'asc';
        }
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->buscar = '';
        $this->fechaDesde = now()->subDays(30)->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
        $this->cajaSelecionada = '';
        $this->tipoSaldo = '';
        $this->resetPage();
    }

    public function verDetalleCaja($idCaja)
    {
        $this->setupClientDatabase();
        
        // Obtener datos de la caja principal
        $this->cajaSeleccionada = DB::connection('client_db')
            ->table($this->tablePrefix . 'cajadiaria')
            ->where('id', $idCaja)
            ->first();

        if (!$this->cajaSeleccionada) {
            return;
        }

        // Obtener detalles de la caja
        $this->detallesCaja = DB::connection('client_db')
            ->table($this->tablePrefix . 'cajadiaria_det')
            ->where('id_caja', $idCaja)
            ->orderBy('id')
            ->get();

        $this->mostrarPanel = true;
    }

    public function cerrarPanel()
    {
        $this->mostrarPanel = false;
        $this->cajaSeleccionada = null;
        $this->detallesCaja = [];
    }

    public function getCajas()
    {
        $this->setupClientDatabase();
        
        $query = DB::connection('client_db')
            ->table($this->tablePrefix . 'cajadiaria as cd');

        // Aplicar filtros
        if ($this->buscar) {
            $query->where(function($q) {
                $q->where('cd.caja', 'like', '%' . $this->buscar . '%');
            });
        }

        if ($this->fechaDesde) {
            $query->where('cd.fecha', '>=', $this->fechaDesde);
        }

        if ($this->fechaHasta) {
            $query->where('cd.fecha', '<=', $this->fechaHasta);
        }

        if ($this->cajaSelecionada) {
            $query->where('cd.caja', $this->cajaSelecionada);
        }

        if ($this->tipoSaldo) {
            if ($this->tipoSaldo === 'faltante') {
                $query->where('cd.saldo', '>', 0);
            } elseif ($this->tipoSaldo === 'sobrante') {
                $query->where('cd.saldo', '<', 0);
            } elseif ($this->tipoSaldo === 'exacto') {
                $query->where('cd.saldo', '=', 0);
            }
        }

        // Obtener totales antes de la paginación
        $totalesQuery = clone $query;
        $totales = $totalesQuery->selectRaw('
            COUNT(*) as total_cajas,
            SUM(CASE WHEN saldo > 0 THEN saldo ELSE 0 END) as total_faltantes,
            SUM(CASE WHEN saldo < 0 THEN ABS(saldo) ELSE 0 END) as total_sobrantes
        ')->first();

        $this->totalCajas = $totales->total_cajas ?? 0;
        $this->totalFaltantes = $totales->total_faltantes ?? 0;
        $this->totalSobrantes = $totales->total_sobrantes ?? 0;

        // Calcular total de efectivo
        $efectivoQuery = DB::connection('client_db')
            ->table($this->tablePrefix . 'cajadiaria_det as cdd')
            ->join($this->tablePrefix . 'cajadiaria as cd', 'cdd.id_caja', '=', 'cd.id');

        if ($this->buscar) {
            $efectivoQuery->where('cd.caja', 'like', '%' . $this->buscar . '%');
        }
        if ($this->fechaDesde) {
            $efectivoQuery->where('cd.fecha', '>=', $this->fechaDesde);
        }
        if ($this->fechaHasta) {
            $efectivoQuery->where('cd.fecha', '<=', $this->fechaHasta);
        }
        if ($this->cajaSelecionada) {
            $efectivoQuery->where('cd.caja', $this->cajaSelecionada);
        }
        if ($this->tipoSaldo) {
            if ($this->tipoSaldo === 'faltante') {
                $efectivoQuery->where('cd.saldo', '>', 0);
            } elseif ($this->tipoSaldo === 'sobrante') {
                $efectivoQuery->where('cd.saldo', '<', 0);
            } elseif ($this->tipoSaldo === 'exacto') {
                $efectivoQuery->where('cd.saldo', '=', 0);
            }
        }

        $totalEfectivo = $efectivoQuery->sum('cdd.efectivo');
        $this->totalEfectivo = $totalEfectivo ?? 0;

        // Aplicar ordenamiento
        $query->orderBy('cd.' . $this->ordenarPor, $this->ordenarDireccion);

        return $query->select([
            'cd.id',
            'cd.fecha',
            'cd.saldo',
            'cd.caja',
            'cd.hora'
        ])->paginate($this->porPagina);
    }

    public function getMovimientosCajasAbiertas()
    {
        $this->setupClientDatabase();

        // Si no hay caja seleccionada, retornar vacío
        if (empty($this->cajaAbiertaSelecionada)) {
            $this->cajasAbiertas = collect();
            $this->ventasTotalesTurno = 0;
            $this->totalEfectivo = 0;
            return;
        }

        $nCaja = (int)$this->cajaAbiertaSelecionada;
        $condicionCaja = " AND caja = " . $nCaja;

        $query = "
            SELECT 'FONDO DE CAJA' AS concepto, SUM(importe) AS debe, 0 AS haber, SUM(importe) AS efec
            FROM {$this->tablePrefix}fondo
            WHERE checkeado = FALSE {$condicionCaja}

            UNION

            SELECT 'INGRESOS/RETIROS MANUAL' AS concepto, SUM(IF(tipo='I',importe,0)) as debe, SUM(IF(tipo='R',importe,0)) AS haber, SUM(-importe*IF(tipo='I',-1,1)) AS efec
            FROM {$this->tablePrefix}retiros
            WHERE checkeado = FALSE {$condicionCaja}

            UNION

            SELECT 'INGRESOS EN EFECTIVO' AS concepto, SUM(pc.importe) AS debe, 0 AS haber, SUM(pc.importe) AS efec
            FROM {$this->tablePrefix}pagos p LEFT JOIN {$this->tablePrefix}pagcon pc ON p.numero = pc.numero
            WHERE p.checkeado = FALSE {$condicionCaja}
            AND pc.tipocon = 1

            UNION

            SELECT 'EGRESOS EN EFECTIVO' AS concepto, 0 AS debe, SUM(oc.importe) AS haber, SUM(-oc.importe) AS efec
            FROM {$this->tablePrefix}ordpag o LEFT JOIN {$this->tablePrefix}ordcon oc ON o.numero = oc.numero
            WHERE o.checkeado = FALSE {$condicionCaja}
            AND oc.codcon = 1

            UNION

            SELECT 'INGRESOS POR TARJETAS DEB/CRED' AS concepto, SUM(pc.importe) AS debe, 0 AS haber, 0 AS efec
            FROM {$this->tablePrefix}pagos p LEFT JOIN {$this->tablePrefix}pagcon pc ON p.numero = pc.numero
            WHERE p.checkeado = FALSE {$condicionCaja}
            AND pc.tipocon = 4

            UNION

            SELECT 'INGRESOS POR MERCADO PAGO' AS concepto, SUM(pc.importe) AS debe, 0 AS haber, 0 AS efec
            FROM {$this->tablePrefix}pagos p LEFT JOIN {$this->tablePrefix}pagcon pc ON p.numero = pc.numero
            WHERE p.checkeado = FALSE
            AND pc.tipocon = 8 {$condicionCaja}

            UNION

            SELECT 'EGRESOS POR MERCADO PAGO' AS concepto, 0 AS debe, SUM(pc.importe) AS haber, 0 AS efec
            FROM {$this->tablePrefix}ordpag p LEFT JOIN {$this->tablePrefix}ordcon pc ON p.numero = pc.numero
            WHERE p.checkeado = FALSE {$condicionCaja}
            AND pc.codcon = 8

            UNION

            SELECT 'INGRESOS POR TRANSFERENCIAS' AS concepto, SUM(pc.importe) AS debe, 0 AS haber, 0 AS efec
            FROM {$this->tablePrefix}pagos p LEFT JOIN {$this->tablePrefix}pagcon pc ON p.numero = pc.numero
            WHERE p.checkeado = FALSE {$condicionCaja}
            AND pc.tipocon = 2

            UNION

            SELECT 'EGRESOS POR TRANSFERENCIAS' AS concepto, 0 AS debe, SUM(oc.importe) AS haber, 0 AS efec
            FROM {$this->tablePrefix}ordpag o LEFT JOIN {$this->tablePrefix}ordcon oc ON o.numero = oc.numero
            WHERE o.checkeado = FALSE {$condicionCaja}
            AND oc.codcon = 2

            UNION

            SELECT 'INGRESOS DE CHEQUES ' AS concepto, SUM(importe) AS debe, 0 AS haber, 0 AS efec
            FROM {$this->tablePrefix}pagos p LEFT JOIN {$this->tablePrefix}pagcon pc ON p.numero = pc.numero
            WHERE p.checkeado = FALSE {$condicionCaja}
            AND pc.tipocon = 3

            UNION

            SELECT 'EGRESOS DE CHEQUES' AS concepto, 0 AS debe, SUM(oc.importe) AS haber, 0 AS efec
            FROM {$this->tablePrefix}ordpag o LEFT JOIN {$this->tablePrefix}ordcon oc ON o.numero = oc.numero
            WHERE o.checkeado = FALSE {$condicionCaja}
            AND (oc.codcon = 3 OR oc.codcon = 4)
        ";

        $resultados = DB::connection('client_db')->select($query);

        // Filtrar resultados vacíos
        $this->cajasAbiertas = collect($resultados)->filter(function($item) {
            return $item->debe > 0 || $item->haber > 0 || $item->efec != 0;
        });

        // Calcular totales para cajas abiertas
        $this->totalEfectivo = $this->cajasAbiertas->sum('efec');

        // La información de ventas totales no existe en ge_, solo en re_
        $this->ventasTotalesTurno = 0;
    }

    public function getClaseSaldo($saldo)
    {
        if ($saldo > 0) {
            return 'text-red-600 font-bold'; // Faltante
        } elseif ($saldo < 0) {
            return 'text-blue-600 font-bold'; // Sobrante
        } else {
            return 'text-green-600 font-bold'; // Exacto
        }
    }

    public function getTipoSaldo($saldo)
    {
        if ($saldo > 0) {
            return 'Faltante: $' . number_format($saldo, 2);
        } elseif ($saldo < 0) {
            return 'Sobrante: $' . number_format(abs($saldo), 2);
        } else {
            return 'Exacto';
        }
    }

    public function getNumerosCaja()
    {
        $this->setupClientDatabase();

        return DB::connection('client_db')
            ->table($this->tablePrefix . 'punto')
            ->select('caja')
            ->orderBy('caja')
            ->pluck('caja');
    }

    public function getCajasAbiertas()
    {
        $this->setupClientDatabase();

        // Obtener cajas que tienen fondo con checkeado = FALSE
        return DB::connection('client_db')
            ->table($this->tablePrefix . 'fondo')
            ->where('checkeado', false)
            ->select('caja')
            ->distinct()
            ->orderBy('caja')
            ->pluck('caja');
    }

    public function calcularTotalDetalle($detalles, $campo)
    {
        return $detalles->sum($campo);
    }

    public function updatedTipoCaja()
    {
        $this->resetPage();
        $this->cerrarPanel();
        $this->cajaAbiertaSelecionada = '';
    }

    public function updatedCajaAbiertaSelecionada()
    {
        // Cuando se selecciona una caja abierta, cargar sus movimientos
        if (!empty($this->cajaAbiertaSelecionada)) {
            $this->getMovimientosCajasAbiertas();
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $numerosCaja = $this->getNumerosCaja();

        if ($this->tipoCaja === 'abiertas') {
            $listaCajasAbiertas = $this->getCajasAbiertas();
            $this->getMovimientosCajasAbiertas();
            $cajas = null;
        } else {
            $listaCajasAbiertas = collect();
            $cajas = $this->getCajas();
        }

        return view('panel.cajas', [
            'cajas' => $cajas,
            'numerosCaja' => $numerosCaja,
            'listaCajasAbiertas' => $listaCajasAbiertas,
            'cajasAbiertas' => $this->cajasAbiertas,
            'ventasTotalesTurno' => $this->ventasTotalesTurno
        ]);
    }
}