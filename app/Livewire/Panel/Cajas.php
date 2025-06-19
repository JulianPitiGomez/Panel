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
    
    protected $tablePrefix;

    protected $queryString = [
        'buscar' => ['except' => ''],
        'fechaDesde' => ['except' => ''],
        'fechaHasta' => ['except' => ''],
        'cajaSelecionada' => ['except' => ''],
        'tipoSaldo' => ['except' => ''],
        'porPagina' => ['except' => 25],
        'page' => ['except' => 1],
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
            ->table($this->tablePrefix . 'cajadiaria')
            ->select('caja')
            ->distinct()
            ->orderBy('caja')
            ->pluck('caja');
    }

    public function calcularTotalDetalle($detalles, $campo)
    {
        return $detalles->sum($campo);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $cajas = $this->getCajas();
        $numerosCaja = $this->getNumerosCaja();

        return view('panel.cajas', compact('cajas', 'numerosCaja'));
    }
}