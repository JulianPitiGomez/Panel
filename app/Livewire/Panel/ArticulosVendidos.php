<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

class ArticulosVendidos extends Component
{
    use WithPagination;

    public $fechaDesde;
    public $fechaHasta;
    public $buscarProducto = '';
    public $perPage = 20;
    public $datosGrafico = [];
    public $totalCantidad = 0;
    public $totalImporte = 0;
    public $cantidadProductos = 0;
    
    private $tablePrefix;

    public function mount()
    {
        $this->setupClientDatabase();
        
        // Fechas por defecto: último mes
        $this->fechaDesde = Carbon::now()->subMonth()->format('Y-m-d');
        $this->fechaHasta = Carbon::now()->format('Y-m-d');
        
        $this->cargarDatosGrafico();
    }

    public function updatedFechaDesde()
    {
        $this->resetPage();
        $this->cargarDatosGrafico();
        $this->dispatch('filtros-aplicados');
    }

    public function updatedFechaHasta()
    {
        $this->resetPage();
        $this->cargarDatosGrafico();
        $this->dispatch('filtros-aplicados');
    }

    public function updatedBuscarProducto()
    {
        $this->resetPage();
        $this->cargarDatosGrafico();
        $this->dispatch('filtros-aplicados');
    }

    public function aplicarFiltros()
    {
        $this->resetPage();
        $this->cargarDatosGrafico();
        $this->dispatch('filtros-aplicados', datosGrafico: $this->datosGrafico);
    }

    public function limpiarFiltros()
    {
        $this->fechaDesde = Carbon::now()->subMonth()->format('Y-m-d');
        $this->fechaHasta = Carbon::now()->format('Y-m-d');
        $this->buscarProducto = '';
        $this->resetPage();
        $this->cargarDatosGrafico();
        $this->dispatch('filtros-limpiados');
    }

    private function getArticulos()
    {
        $this->setupClientDatabase();
        
        $query = "
            SELECT 
                d.codart,
                d.detart as nombre_producto,
                SUM(d.cantidad * IF(left(d.nrofac,2)='NC',-1,1)) as cantidad_vendida,
                SUM(d.importe * IF(left(d.nrofac,2)='NC',-1,1)) as total_importe,
                AVG(d.importe) as precio_promedio,
                COUNT(DISTINCT d.nrofac) as cantidad_facturas
            FROM {$this->tablePrefix}ventas_det d            
            WHERE DATE(d.fecha) >= ? 
            AND DATE(d.fecha) <= ?
        ";
        
        $params = [$this->fechaDesde, $this->fechaHasta];
        
        // Agregar filtro de búsqueda por producto si existe
        if (!empty($this->buscarProducto)) {
            $query .= " AND (d.codart LIKE ? OR d.detart LIKE ?)";
            $params[] = '%' . $this->buscarProducto . '%';
            $params[] = '%' . $this->buscarProducto . '%';
        }
        
        $query .= " 
            GROUP BY d.codart, d.detart
            HAVING SUM(d.cantidad * IF(LEFT(d.nrofac,2)='NC',-1,1)) > 0
            ORDER BY cantidad_vendida DESC, total_importe DESC
        ";
        
        $articulos = DB::connection('client_db')->select($query, $params);
        
        // Calcular totales basados en los resultados filtrados
        $this->calcularTotalesFiltrados($articulos);
        
        return $articulos;
    }

    private function calcularTotalesFiltrados($articulos)
    {
        $totalCantidad = 0;
        $totalImporte = 0;
        $cantidad = count($articulos);
        
        foreach ($articulos as $articulo) {
            $totalCantidad += $articulo->cantidad_vendida;
            $totalImporte += $articulo->total_importe;
        }
        
        $this->totalCantidad = $totalCantidad;
        $this->totalImporte = $totalImporte;
        $this->cantidadProductos = $cantidad;
    }

    private function cargarDatosGrafico()
    {
        $this->setupClientDatabase();
        
        // Obtener top 10 productos más vendidos para el gráfico
        $query = "
            SELECT 
                d.detart as nombre_producto,
                SUM(d.cantidad * IF(left(d.nrofac,2)='NC',-1,1)) as cantidad_vendida,
                SUM(d.importe * IF(left(d.nrofac,2)='NC',-1,1)) as total_importe
            FROM {$this->tablePrefix}ventas_det d            
            WHERE DATE(d.fecha) >= ? 
            AND DATE(d.fecha) <= ?
        ";
        
        $params = [$this->fechaDesde, $this->fechaHasta];
        
        // Incluir filtro de búsqueda también en el gráfico
        if (!empty($this->buscarProducto)) {
            $query .= " AND (d.codart LIKE ? OR d.detart LIKE ?)";
            $params[] = '%' . $this->buscarProducto . '%';
            $params[] = '%' . $this->buscarProducto . '%';
        }
        
        $query .= "
            GROUP BY d.codart, d.detart
            HAVING SUM(d.cantidad * IF(LEFT(d.nrofac,2)='NC',-1,1)) > 0
            ORDER BY cantidad_vendida DESC
            LIMIT 10
        ";
        
        $datosChart = DB::connection('client_db')->select($query, $params);

        // Preparar datos para el gráfico
        $this->datosGrafico = collect($datosChart)->map(function ($item) {
            return [
                'producto' => strlen($item->nombre_producto) > 25 
                    ? substr($item->nombre_producto, 0, 25) . '...' 
                    : $item->nombre_producto,
                'producto_completo' => $item->nombre_producto,
                'cantidad' => (int)$item->cantidad_vendida,
                'importe' => round($item->total_importe, 2)
            ];
        })->toArray();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $articulos = collect($this->getArticulos());
        
        // Implementar paginación manual
        $currentPage = $this->getPage();
        $offset = ($currentPage - 1) * $this->perPage;
        $articulosPaginados = $articulos->slice($offset, $this->perPage)->values();
        
        // Crear objeto de paginación
        $totalItems = $articulos->count();
        $lastPage = ceil($totalItems / $this->perPage);
        
        $paginationInfo = (object)[
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'per_page' => $this->perPage,
            'total' => $totalItems,
            'from' => $offset + 1,
            'to' => min($offset + $this->perPage, $totalItems)
        ];

        return view('panel.articulos-vendidos', [
            'articulos' => $articulosPaginados,
            'paginationInfo' => $paginationInfo
        ]);
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
}