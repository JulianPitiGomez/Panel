<?php

namespace App\Livewire\VendedoresEmpresas;

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
    public $depto = '';
    public $listaEspecial = '';


    // Opciones para selects
    public $deptos = [];
    public $listasEspeciales = [];
    public $productoss = [];

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
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

        // Cargar departamentos
        $this->deptos = DB::connection('client_db')
            ->table("{$this->tablePrefix}deptos")
            ->select('CODIGO', 'NOMBRE')
            ->orderBy('NOMBRE')
            ->get();


        // Cargar listas especiales - USAR ma_lispre
        $this->listasEspeciales = DB::connection('client_db')
            ->table("ma_lispre")
            ->select('CODIGO', 'NOMBRE')
            ->orderBy('NOMBRE')
            ->get();
    }

    public function updatedBusqueda()
    {
        $this->resetPage();
    }


    public function updatedDepto()
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
        $this->depto = '';
        $this->listaEspecial = '';
    }

    public function imprimirLista()
    {

         // Asegurarse de que los productos estén en un array
        $this->dispatch('abrir-impresion-lista', [
            'url' => route('imprimir-lista-empresa')
        ]);
    }

    public function imprimirListaRuta()
    {
        $this->setupClientDatabase();
        $query = DB::connection('client_db')
            ->table("{$this->tablePrefix}articu as a")
            ->leftJoin("{$this->tablePrefix}deptos as d", 'a.DEPTO', '=', 'd.CODIGO')
            ->select([
                'a.CODIGO',
                'a.NOMBRE',
                'a.PRECIO',
                'a.STOCK',               
                'd.NOMBRE as depto_nombre'
            ])
            ->where(function($q) {
                $q->where('a.INACTIVO', '=', false)
                  ->orWhereNull('a.INACTIVO');
            });

        // Aplicar filtros
        if (!empty($this->busqueda)) {
            $query->where(function($q) {
                $q->where('a.NOMBRE', 'like', '%' . $this->busqueda . '%')
                  ->orWhere('a.CODIGO', 'like', '%' . $this->busqueda . '%');
            });
        }        
        

        if (!empty($this->depto)) {
            $query->where('a.DEPTO', $this->depto);
        }        
        

        // Ordenar por fecha descendente
        $productos = $query->get();

        // Si hay lista especial seleccionada, obtener precios especiales - USAR ma_lispredet
        $preciosEspeciales = [];
        if (!empty($this->listaEspecial)) {
            $preciosEspeciales = DB::connection('client_db')
                ->table("ma_lispredet")
                ->where('CODLIS', $this->listaEspecial)
                ->pluck('PRECIO', 'CODART')
                ->toArray();
        }
        return view('vendedores-empresas.precios-imprimir', compact('productos', 'preciosEspeciales'));
    }


    #[Layout('layouts.vendedores-empresas')]
    public function render()
    {
        $this->setupClientDatabase();
        $query = DB::connection('client_db')
            ->table("{$this->tablePrefix}articu as a")
            ->leftJoin("{$this->tablePrefix}deptos as d", 'a.DEPTO', '=', 'd.CODIGO')
            ->select([
                'a.CODIGO',
                'a.NOMBRE',
                'a.PRECIO',
                'a.STOCK',                
                'd.NOMBRE as depto_nombre',
            ])
            ->where(function($q) {
                $q->where('a.INACTIVO', '=', false)
                  ->orWhereNull('a.INACTIVO');
            });

        // Aplicar filtros
        if (!empty($this->busqueda)) {
            $query->where(function($q) {
                $q->where('a.NOMBRE', 'like', '%' . $this->busqueda . '%')
                  ->orWhere('a.CODIGO', 'like', '%' . $this->busqueda . '%');
            });
        }
        

        if (!empty($this->depto)) {
            $query->where('a.DEPTO', $this->depto);
        }

        // Ordenar por fecha descendente
        $query->orderBy('a.NOMBRE', 'asc');
        $this->productoss = $query->get();
        $productos = $query->paginate(25);

        // Si hay lista especial seleccionada, obtener precios especiales - USAR ma_lispredet
        $preciosEspeciales = [];
        if (!empty($this->listaEspecial)) {
            $preciosEspeciales = DB::connection('client_db')
                ->table("ma_lispredet")
                ->where('CODLIS', $this->listaEspecial)
                ->pluck('PRECIO', 'CODART')
                ->toArray();
        }

        return view('vendedores-empresas.listas-precios', [
            'productos' => $productos,
            'preciosEspeciales' => $preciosEspeciales
        ]);
    }
}
