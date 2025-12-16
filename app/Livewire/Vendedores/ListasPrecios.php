<?php

namespace App\Livewire\Vendedores;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

class ListasPrecios extends Component
{
    use WithPagination;

    public $tablePrefix = '';
    
    // Filtros
    public $busqueda = '';
    public $rubro = '';
    public $marca = '';
    public $depto = '';
    public $proveedor = '';
    public $listaEspecial = '';
    public $fechaDesde;
    public $fechaHasta;
    
    // Opciones para selects
    public $rubros = [];
    public $marcas = [];
    public $deptos = [];
    public $proveedores = [];
    public $listasEspeciales = [];
    public $productoss = [];

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        $this->fechaDesde = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->fechaHasta = Carbon::now()->format('Y-m-d');
        $this->setupClientDatabase();
        $this->cargarOpcionesFiltros();
    }

    private function setupClientDatabase()
    {
        $clientId = session('client_id');
        if ($clientId) {
            $cliente = \App\Models\Cliente::find($clientId);
            if ($cliente) {
                Config::set('database.connections.client_db.database', $cliente->base);
                DB::purge('client_db');
                session(['client_table_prefix' => $cliente->getTablePrefix()]);
                $this->tablePrefix = $cliente->getTablePrefix();
            }
        }
    }

    public function cargarOpcionesFiltros()
    {
        // Cargar rubros
        $this->rubros = DB::connection('client_db')
            ->table("{$this->tablePrefix}rubros")
            ->select('CODIGO', 'NOMBRE')
            ->orderBy('NOMBRE')
            ->get();

        // Cargar marcas
        $this->marcas = DB::connection('client_db')
            ->table("{$this->tablePrefix}marcas")
            ->select('CODIGO', 'NOMBRE')
            ->orderBy('NOMBRE')
            ->get();

        // Cargar departamentos
        $this->deptos = DB::connection('client_db')
            ->table("{$this->tablePrefix}deptos")
            ->select('CODIGO', 'NOMBRE')
            ->orderBy('NOMBRE')
            ->get();

        // Cargar proveedores
        $this->proveedores = DB::connection('client_db')
            ->table("{$this->tablePrefix}provee")
            ->select('CODIGO', 'NOMBRE')
            ->orderBy('NOMBRE')
            ->get();

        // Cargar listas especiales
        $this->listasEspeciales = DB::connection('client_db')
            ->table("{$this->tablePrefix}lispre")
            ->select('CODIGO', 'NOMBRE')
            ->orderBy('NOMBRE')
            ->get();
    }

    public function updatedBusqueda()
    {
        $this->resetPage();
    }

    public function updatedRubro()
    {
        $this->resetPage();
    }

    public function updatedMarca()
    {
        $this->resetPage();
    }

    public function updatedDepto()
    {
        $this->resetPage();
    }

    public function updatedProveedor()
    {
        $this->resetPage();
    }

    public function updatedListaEspecial()
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

    public function limpiarFiltros()
    {
        $this->busqueda = '';
        $this->rubro = '';
        $this->marca = '';
        $this->depto = '';
        $this->proveedor = '';
        $this->listaEspecial = '';
        $this->fechaDesde = '';
        $this->fechaHasta = '';
    }

    public function imprimirLista()
    {
        
         // Asegurarse de que los productos estén en un array
        $this->dispatch('abrir-impresion-lista', [
            'url' => route('imprimir-lista')
        ]);
    }

    public function imprimirListaRuta()
    {
        $this->setupClientDatabase();
        $query = DB::connection('client_db')
            ->table("{$this->tablePrefix}articu as a")
            ->leftJoin("{$this->tablePrefix}rubros as r", 'a.RUBRO', '=', 'r.CODIGO')
            ->leftJoin("{$this->tablePrefix}marcas as m", 'a.MARCA', '=', 'm.CODIGO')
            ->leftJoin("{$this->tablePrefix}deptos as d", 'a.DEPTO', '=', 'd.CODIGO')
            ->leftJoin("{$this->tablePrefix}provee as p", 'a.PROV', '=', 'p.CODIGO')
            ->select([
                'a.CODIGO',
                'a.NOMBRE',
                'a.PRECIOVEN',
                'a.REVENTA',
                'a.STOCKACT',
                'a.FECMOD',
                'r.NOMBRE as rubro_nombre',
                'm.NOMBRE as marca_nombre',
                'd.NOMBRE as depto_nombre',
                'p.NOMBRE as proveedor_nombre'
            ])
            ->where(function($q) {
                $q->where('a.NOSALE', '!=', 1)
                  ->orWhereNull('a.NOSALE');
            });

        // Aplicar filtros
        if (!empty($this->busqueda)) {
            $query->where(function($q) {
                $q->where('a.NOMBRE', 'like', '%' . $this->busqueda . '%')
                  ->orWhere('a.CODIGO', 'like', '%' . $this->busqueda . '%');
            });
        }

        if (!empty($this->rubro)) {
            $query->where('a.RUBRO', $this->rubro);
        }

        if (!empty($this->marca)) {
            $query->where('a.MARCA', $this->marca);
        }

        if (!empty($this->depto)) {
            $query->where('a.DEPTO', $this->depto);
        }

        if (!empty($this->proveedor)) {
            $query->where('a.PROV', $this->proveedor);
        }

        if (!empty($this->fechaDesde)) {
            $query->whereDate('a.FECMOD', '>=', $this->fechaDesde);
        }

        if (!empty($this->fechaHasta)) {
            $query->whereDate('a.FECMOD', '<=', $this->fechaHasta);
        }

        // Ordenar por fecha descendente
        $query->orderBy('a.FECMOD', 'desc');
        $productos = $query->get();

        // Si hay lista especial seleccionada, obtener precios especiales
        $preciosEspeciales = [];
        if (!empty($this->listaEspecial)) {
            $preciosEspeciales = DB::connection('client_db')
                ->table("{$this->tablePrefix}lispredet")
                ->where('CODLIS', $this->listaEspecial)
                ->pluck('PRECIO', 'CODART')
                ->toArray();
        }     
        return view('vendedores.precios-imprimir', compact('productos', 'preciosEspeciales'));
    }


    #[Layout('layouts.vendedores')]
    public function render()
    {
        $this->setupClientDatabase();
        $query = DB::connection('client_db')
            ->table("{$this->tablePrefix}articu as a")
            ->leftJoin("{$this->tablePrefix}rubros as r", 'a.RUBRO', '=', 'r.CODIGO')
            ->leftJoin("{$this->tablePrefix}marcas as m", 'a.MARCA', '=', 'm.CODIGO')
            ->leftJoin("{$this->tablePrefix}deptos as d", 'a.DEPTO', '=', 'd.CODIGO')
            ->leftJoin("{$this->tablePrefix}provee as p", 'a.PROV', '=', 'p.CODIGO')
            ->select([
                'a.CODIGO',
                'a.NOMBRE',
                'a.PRECIOVEN',
                'a.REVENTA',
                'a.STOCKACT',
                'a.FECMOD',
                'r.NOMBRE as rubro_nombre',
                'm.NOMBRE as marca_nombre',
                'd.NOMBRE as depto_nombre',
                'p.NOMBRE as proveedor_nombre'
            ])
            ->where(function($q) {
                $q->where('a.NOSALE', '!=', 1)
                  ->orWhereNull('a.NOSALE');
            });

        // Aplicar filtros
        if (!empty($this->busqueda)) {
            $query->where(function($q) {
                $q->where('a.NOMBRE', 'like', '%' . $this->busqueda . '%')
                  ->orWhere('a.CODIGO', 'like', '%' . $this->busqueda . '%');
            });
        }

        if (!empty($this->rubro)) {
            $query->where('a.RUBRO', $this->rubro);
        }

        if (!empty($this->marca)) {
            $query->where('a.MARCA', $this->marca);
        }

        if (!empty($this->depto)) {
            $query->where('a.DEPTO', $this->depto);
        }

        if (!empty($this->proveedor)) {
            $query->where('a.PROV', $this->proveedor);
        }

        if (!empty($this->fechaDesde)) {
            $query->whereDate('a.FECMOD', '>=', $this->fechaDesde);
        }

        if (!empty($this->fechaHasta)) {
            $query->whereDate('a.FECMOD', '<=', $this->fechaHasta);
        }

        // Ordenar por fecha descendente
        $query->orderBy('a.FECMOD', 'desc');
        $this->productoss = $query->get();
        $productos = $query->paginate(25);

        // Si hay lista especial seleccionada, obtener precios especiales
        $preciosEspeciales = [];
        if (!empty($this->listaEspecial)) {
            $preciosEspeciales = DB::connection('client_db')
                ->table("{$this->tablePrefix}lispredet")
                ->where('CODLIS', $this->listaEspecial)
                ->pluck('PRECIO', 'CODART')
                ->toArray();
        }

        return view('vendedores.listas-precios', [
            'productos' => $productos,
            'preciosEspeciales' => $preciosEspeciales
        ]);
    }
}