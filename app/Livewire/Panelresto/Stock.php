<?php

namespace App\Livewire\PanelResto;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Livewire\Attributes\Layout;

class Stock extends Component
{
    use WithPagination;

    // Pestaña activa
    public $pestanaActiva = 'articulos'; // 'articulos' o 'materias'

    // Filtros Artículos
    public $buscarArticulo = '';
    public $departamentoSeleccionado = '';
    public $estadoArticulo = 'activos'; // 'activos', 'inactivos', 'todos'
    public $estadoStockArticulo = ''; // '', 'negativo', 'positivo', 'sin_stock'
    public $stockMinimoArticulo = ''; // '', 'debajo', 'minimo_superior'

    // Filtros Materias Primas
    public $buscarMateria = '';
    public $estadoStockMateria = ''; // '', 'negativo', 'positivo', 'sin_stock'
    public $stockMinimoMateria = ''; // '', 'debajo', 'minimo_superior'

    // Paginación
    public $porPaginaArticulos = 25;
    public $porPaginaMaterias = 25;

    // Totales
    public $totalArticulos = 0;
    public $totalValorizadoArticulos = 0;
    public $totalMaterias = 0;
    public $totalValorizadoMaterias = 0;

    protected $tablePrefix;
    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'pestanaActiva' => ['except' => 'articulos'],
        'buscarArticulo' => ['except' => ''],
        'departamentoSeleccionado' => ['except' => ''],
        'estadoArticulo' => ['except' => 'activos'],
        'estadoStockArticulo' => ['except' => ''],
        'stockMinimoArticulo' => ['except' => ''],
        'buscarMateria' => ['except' => ''],
        'estadoStockMateria' => ['except' => ''],
        'stockMinimoMateria' => ['except' => ''],
        'porPaginaArticulos' => ['except' => 25],
        'porPaginaMaterias' => ['except' => 25],
    ];

    public function mount()
    {
        $this->setupClientDatabase();
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

    public function cambiarPestana($pestana)
    {
        $this->pestanaActiva = $pestana;
        $this->resetPage();
    }

    public function updatedBuscarArticulo()
    {
        $this->resetPage();
    }

    public function updatedDepartamentoSeleccionado()
    {
        $this->resetPage();
    }

    public function updatedEstadoArticulo()
    {
        $this->resetPage();
    }

    public function updatedEstadoStockArticulo()
    {
        $this->resetPage();
    }

    public function updatedStockMinimoArticulo()
    {
        $this->resetPage();
    }

    public function updatedBuscarMateria()
    {
        $this->resetPage();
    }

    public function updatedEstadoStockMateria()
    {
        $this->resetPage();
    }

    public function updatedStockMinimoMateria()
    {
        $this->resetPage();
    }

    public function updatedPorPaginaArticulos()
    {
        $this->resetPage();
    }

    public function updatedPorPaginaMaterias()
    {
        $this->resetPage();
    }

    public function limpiarFiltrosArticulos()
    {
        $this->buscarArticulo = '';
        $this->departamentoSeleccionado = '';
        $this->estadoArticulo = 'activos';
        $this->estadoStockArticulo = '';
        $this->stockMinimoArticulo = '';
        $this->resetPage();
    }

    public function limpiarFiltrosMaterias()
    {
        $this->buscarMateria = '';
        $this->estadoStockMateria = '';
        $this->stockMinimoMateria = '';
        $this->resetPage();
    }

    public function getArticulos()
    {
        $this->setupClientDatabase();

        $query = DB::connection('client_db')
            ->table($this->tablePrefix . 'articu as a')
            ->leftJoin($this->tablePrefix . 'deptos as d', 'a.DEPTO', '=', 'd.CODIGO')
            ->where('a.LSTOCK', 1);

        // Aplicar filtros
        if ($this->buscarArticulo) {
            $query->where(function($q) {
                $q->where('a.CODIGO', 'like', '%' . $this->buscarArticulo . '%')
                  ->orWhere('a.NOMBRE', 'like', '%' . $this->buscarArticulo . '%');
            });
        }

        if ($this->departamentoSeleccionado) {
            $query->where('a.DEPTO', $this->departamentoSeleccionado);
        }

        // Activos / inactivos segun el campo INACTIVO (true = inactivo)
        if ($this->estadoArticulo === 'activos') {
            $query->where(function ($q) {
                $q->where('a.INACTIVO', false)
                  ->orWhereNull('a.INACTIVO');
            });
        } elseif ($this->estadoArticulo === 'inactivos') {
            $query->where('a.INACTIVO', true);
        }

        if ($this->estadoStockArticulo) {
            if ($this->estadoStockArticulo === 'negativo') {
                $query->where('a.STOCK', '<', 0);
            } elseif ($this->estadoStockArticulo === 'positivo') {
                $query->where('a.STOCK', '>', 0);
            } elseif ($this->estadoStockArticulo === 'sin_stock') {
                $query->where('a.STOCK', '=', 0);
            }
        }

        if ($this->stockMinimoArticulo) {
            if ($this->stockMinimoArticulo === 'debajo') {
                $query->whereRaw('a.STOCK < a.stockmin');
            } elseif ($this->stockMinimoArticulo === 'minimo_superior') {
                $query->whereRaw('a.STOCK >= a.stockmin');
            }
        }

        // Obtener totales antes de la paginación
        $totalesQuery = clone $query;
        $totales = $totalesQuery->selectRaw('
            COUNT(*) as total_articulos,
            SUM(a.STOCK * a.PRECIOCOS) as total_valorizado
        ')->first();

        $this->totalArticulos = $totales->total_articulos ?? 0;
        $this->totalValorizadoArticulos = $totales->total_valorizado ?? 0;

        return $query->select([
            'a.CODIGO',
            'a.NOMBRE',
            'a.STOCK',
            'a.PRECIOCOS',
            'a.INACTIVO',
            'd.NOMBRE as nombre_depto',
            DB::raw('(a.STOCK * a.PRECIOCOS) as valorizado')
        ])->orderBy('a.NOMBRE')
          ->paginate($this->porPaginaArticulos);
    }

    public function getMaterias()
    {
        $this->setupClientDatabase();

        $query = DB::connection('client_db')
            ->table($this->tablePrefix . 'materia as m');

        // Aplicar filtros
        if ($this->buscarMateria) {
            $query->where(function($q) {
                $q->where('m.CODIGO', 'like', '%' . $this->buscarMateria . '%')
                  ->orWhere('m.NOMBRE', 'like', '%' . $this->buscarMateria . '%');
            });
        }

        if ($this->estadoStockMateria) {
            if ($this->estadoStockMateria === 'negativo') {
                $query->where('m.STOCK', '<', 0);
            } elseif ($this->estadoStockMateria === 'positivo') {
                $query->where('m.STOCK', '>', 0);
            } elseif ($this->estadoStockMateria === 'sin_stock') {
                $query->where('m.STOCK', '=', 0);
            }
        }

        if ($this->stockMinimoMateria) {
            if ($this->stockMinimoMateria === 'debajo') {
                $query->whereRaw('m.STOCK < m.stockmin');
            } elseif ($this->stockMinimoMateria === 'minimo_superior') {
                $query->whereRaw('m.STOCK >= m.stockmin');
            }
        }

        // Obtener totales antes de la paginación
        $totalesQuery = clone $query;
        $totales = $totalesQuery->selectRaw('
            COUNT(*) as total_materias,
            SUM(m.STOCK * m.PCOSTO) as total_valorizado
        ')->first();

        $this->totalMaterias = $totales->total_materias ?? 0;
        $this->totalValorizadoMaterias = $totales->total_valorizado ?? 0;

        return $query->select([
            'm.CODIGO',
            'm.NOMBRE',
            'm.STOCK',
            'm.UNIDAD',
            'm.PCOSTO',
            DB::raw('(m.STOCK * m.PCOSTO) as valorizado')
        ])->orderBy('m.NOMBRE')
          ->paginate($this->porPaginaMaterias);
    }

    public function getDepartamentos()
    {
        $this->setupClientDatabase();

        return DB::connection('client_db')
            ->table($this->tablePrefix . 'deptos')
            ->select('CODIGO', 'NOMBRE')
            ->orderBy('NOMBRE')
            ->get();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        if ($this->pestanaActiva === 'articulos') {
            $articulos = $this->getArticulos();
            $departamentos = $this->getDepartamentos();

            return view('panelresto.stock', compact('articulos', 'departamentos'));
        } else {
            $materias = $this->getMaterias();

            return view('panelresto.stock', compact('materias'));
        }
    }
}
