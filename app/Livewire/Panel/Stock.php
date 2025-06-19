<?php
namespace App\Livewire\Panel;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;

class Stock extends Component
{
    use WithPagination;
    
    // Filtros
    public $busqueda = '';
    public $marcaSeleccionada = '';
    public $proveedorSeleccionado = '';
    public $rubroSeleccionado = '';
    public $departamentoSeleccionado = '';
    public $tipoStock = ''; // '', 'positivo', 'negativo', 'alerta'
    
    // Datos para filtros
    public $marcas = [];
    public $proveedores = [];
    public $rubros = [];
    public $departamentos = [];
    
    // Configuración
    public $paginacionSize = 25;
    public $ordenarPor = 'nombre';
    public $direccionOrden = 'asc';
    
    // Totales
    public $totalStockCosto = 0;
    public $totalStockVenta = 0;
    public $cantidadArticulos = 0;
    
    // Modal de stock relacionado
    public $verModal = false;
    public $articuloSeleccionado = null;
    public $articulosRelacionados = [];
    
    protected $tablePrefix;
    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->setupClientDatabase();
        $this->cargarDatosIniciales();
        $this->calcularTotales();
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

    public function cargarDatosIniciales()
    {
        // Cargar marcas
        $this->marcas = DB::connection('client_db')
            ->table($this->tablePrefix . 'marcas')
            ->select('codigo', 'nombre')
            ->whereNotNull('nombre')
            ->where('nombre', '!=', '')
            ->orderBy('nombre')
            ->get();

        // Cargar proveedores
        $this->proveedores = DB::connection('client_db')
            ->table($this->tablePrefix . 'provee')
            ->select('codigo', 'nombre')
            ->whereNotNull('nombre')
            ->where('nombre', '!=', '')
            ->orderBy('nombre')
            ->get();

        // Cargar rubros
        $this->rubros = DB::connection('client_db')
            ->table($this->tablePrefix . 'rubros')
            ->select('codigo', 'nombre')
            ->whereNotNull('nombre')
            ->where('nombre', '!=', '')
            ->orderBy('nombre')
            ->get();

        // Cargar departamentos
        $this->departamentos = DB::connection('client_db')
            ->table($this->tablePrefix . 'deptos')
            ->select('codigo', 'nombre')
            ->whereNotNull('nombre')
            ->where('nombre', '!=', '')
            ->orderBy('nombre')
            ->get();
    }

    public function getArticulosProperty()
    {
        $this->setupClientDatabase();
        
        $query = DB::connection('client_db')
            ->table($this->tablePrefix . 'articu as a')
            ->leftJoin($this->tablePrefix . 'marcas as m', 'a.marca', '=', 'm.codigo')
            ->leftJoin($this->tablePrefix . 'provee as p', 'a.prov', '=', 'p.codigo')
            ->leftJoin($this->tablePrefix . 'rubros as r', 'a.rubro', '=', 'r.codigo')
            ->leftJoin($this->tablePrefix . 'deptos as d', 'a.depto', '=', 'd.codigo')
            ->select(
                'a.codigo',
                'a.nombre',
                'a.stockact',
                'a.stockmin',
                'a.preciocos',
                'a.precioven',
                'a.stockotro',
                'm.nombre as marca_nombre',
                'p.nombre as proveedor_nombre',
                'r.nombre as rubro_nombre',
                'd.nombre as departamento_nombre'
            );

        // Filtro por búsqueda
        if ($this->busqueda) {
            $query->where(function($q) {
                $q->where('a.codigo', 'like', '%' . $this->busqueda . '%')
                  ->orWhere('a.nombre', 'like', '%' . $this->busqueda . '%');
            });
        }

        // Filtros por categorías
        if ($this->marcaSeleccionada) {
            $query->where('a.marca', $this->marcaSeleccionada);
        }

        if ($this->proveedorSeleccionado) {
            $query->where('a.prov', $this->proveedorSeleccionado);
        }

        if ($this->rubroSeleccionado) {
            $query->where('a.rubro', $this->rubroSeleccionado);
        }

        if ($this->departamentoSeleccionado) {
            $query->where('a.depto', $this->departamentoSeleccionado);
        }

        // Filtro por tipo de stock
        switch ($this->tipoStock) {
            case 'positivo':
                $query->where('a.stockact', '>', 0);
                break;
            case 'negativo':
                $query->where('a.stockact', '<=', 0);
                break;
            case 'alerta':
                $query->whereRaw('a.stockact < a.stockmin');
                break;
        }

        // Ordenamiento
        $query->orderBy('a.' . $this->ordenarPor, $this->direccionOrden);

        return $query->paginate($this->paginacionSize);
    }

    public function calcularTotales()
    {
        $this->setupClientDatabase();
        
        $query = DB::connection('client_db')
            ->table($this->tablePrefix . 'articu as a')
            ->leftJoin($this->tablePrefix . 'marcas as m', 'a.marca', '=', 'm.codigo')
            ->leftJoin($this->tablePrefix . 'provee as p', 'a.prov', '=', 'p.codigo')
            ->leftJoin($this->tablePrefix . 'rubros as r', 'a.rubro', '=', 'r.codigo')
            ->leftJoin($this->tablePrefix . 'deptos as d', 'a.depto', '=', 'd.codigo')
            ->select(
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(a.stockact * a.preciocos) as total_costo'),
                DB::raw('SUM(a.stockact * a.precioven) as total_venta')
            );

        // Aplicar los mismos filtros
        if ($this->busqueda) {
            $query->where(function($q) {
                $q->where('a.codigo', 'like', '%' . $this->busqueda . '%')
                  ->orWhere('a.nombre', 'like', '%' . $this->busqueda . '%');
            });
        }

        if ($this->marcaSeleccionada) {
            $query->where('a.marca', $this->marcaSeleccionada);
        }

        if ($this->proveedorSeleccionado) {
            $query->where('a.prov', $this->proveedorSeleccionado);
        }

        if ($this->rubroSeleccionado) {
            $query->where('a.rubro', $this->rubroSeleccionado);
        }

        if ($this->departamentoSeleccionado) {
            $query->where('a.depto', $this->departamentoSeleccionado);
        }

        switch ($this->tipoStock) {
            case 'positivo':
                $query->where('a.stockact', '>', 0);
                break;
            case 'negativo':
                $query->where('a.stockact', '<=', 0);
                break;
            case 'alerta':
                $query->whereRaw('a.stockact < a.stockmin');
                break;
        }

        $resultado = $query->first();
        
        $this->cantidadArticulos = $resultado->cantidad ?? 0;
        $this->totalStockCosto = $resultado->total_costo ?? 0;
        $this->totalStockVenta = $resultado->total_venta ?? 0;
    }

    public function verStockRelacionado($codigoArticulo)
    {
        $this->setupClientDatabase();
        
        // Obtener datos del artículo principal
        $this->articuloSeleccionado = DB::connection('client_db')
            ->table($this->tablePrefix . 'articu')
            ->select('codigo', 'nombre')
            ->where('codigo', $codigoArticulo)
            ->first();

        // Obtener artículos relacionados
        $this->articulosRelacionados = DB::connection('client_db')
            ->table($this->tablePrefix . 'reseta as r')
            ->leftJoin($this->tablePrefix . 'articu as a', 'r.codusa', '=', 'a.codigo')
            ->select('a.codigo', 'a.nombre', 'a.stockact', 'a.stockmin', 'a.preciocos', 'a.precioven')
            ->where('r.codart', $codigoArticulo)
            ->get();

        $this->verModal = true;
    }

    public function cerrarModal()
    {
        $this->verModal = false;
        $this->articuloSeleccionado = null;
        $this->articulosRelacionados = [];
    }
    public function limpiarFiltros()
    {
        $this->busqueda = '';
        $this->marcaSeleccionada = '';
        $this->proveedorSeleccionado = '';
        $this->rubroSeleccionado = '';
        $this->departamentoSeleccionado = '';
        $this->tipoStock = '';
        
        $this->resetPage();
        $this->calcularTotales();
    }

    public function cambiarTamanoPagina($size)
    {
        $this->paginacionSize = $size;
        $this->resetPage();
    }

    public function ordenarPor($campo)
    {
        if ($this->ordenarPor === $campo) {
            $this->direccionOrden = $this->direccionOrden === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenarPor = $campo;
            $this->direccionOrden = 'asc';
        }
        
        $this->resetPage();
    }

    public function getClaseStock($stockActual, $stockMinimo)
    {
        if ($stockActual <= 0) {
            return 'text-red-600 font-bold'; // Rojo para stock cero o negativo
        } elseif ($stockActual < $stockMinimo) {
            return 'text-orange-600 font-bold'; // Naranja para stock menor al mínimo
        } else {
            return 'text-green-600 font-bold'; // Verde para stock normal
        }
    }

    #[Layout('layouts.app')]    
    public function render()
    {
        return view('panel.stock', [
            'articulos' => $this->articulos
        ]);
    }

    // Watchers para actualizar automáticamente
    public function updated($propertyName)
    {
        if (in_array($propertyName, [
            'busqueda', 'marcaSeleccionada', 'proveedorSeleccionado', 
            'rubroSeleccionado', 'departamentoSeleccionado', 'tipoStock'
        ])) {
            $this->resetPage();
            $this->calcularTotales();
        }
    }
}