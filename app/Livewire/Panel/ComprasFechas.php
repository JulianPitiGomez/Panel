<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

class ComprasFechas extends Component
{
    use WithPagination;

    public $fechaDesde;
    public $fechaHasta;
    public $buscar = '';
    public $perPage = 15;
    public $datosGrafico = [];
    public $totalCompras = 0;
    public $cantidadFacturas = 0;
    
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
        $this->dispatch('filtros-aplicados', datosGrafico: $this->datosGrafico);
    }

    public function updatedFechaHasta()
    {
        $this->resetPage();
        $this->cargarDatosGrafico();
        $this->dispatch('filtros-aplicados', datosGrafico: $this->datosGrafico);
    }

    public function updatedBuscar()
    {
        $this->resetPage();
        $this->dispatch('filtros-aplicados', datosGrafico: $this->datosGrafico);
    }

    public function aplicarFiltros()
    {
        $this->resetPage();
        $this->cargarDatosGrafico();
        $this->dispatch('filtros-aplicados-compra');
    }

    public function limpiarFiltros()
    {
        $this->fechaDesde = Carbon::now()->subMonth()->format('Y-m-d');
        $this->fechaHasta = Carbon::now()->format('Y-m-d');
        $this->buscar = '';
        $this->resetPage();
        $this->cargarDatosGrafico();
        $this->dispatch('filtros-limpiados-compra');
    }

    private function getCompras()
    {
        $this->setupClientDatabase();
        
        $query = "
            SELECT 
                c.fecfac as fecha,
                c.numfac as numcomp,
                c.letra,
                c.tipocomp as ticomp,
                p.nombre as proveedor,
                c.importe,
                DATE_FORMAT(c.fecfac, '%d/%m/%Y') as fecha_formateada
            FROM {$this->tablePrefix}compras c
            INNER JOIN {$this->tablePrefix}provee p ON c.codpro = p.codigo
            WHERE DATE(c.fecfac) >= ? 
            AND DATE(c.fecfac) <= ?
        ";
        
        $params = [$this->fechaDesde, $this->fechaHasta];
        
        // Agregar filtro de búsqueda si existe
        if (!empty($this->buscar)) {
            $query .= " AND (c.numfac LIKE ? OR p.nombre LIKE ?)";
            $params[] = '%' . $this->buscar . '%';
            $params[] = '%' . $this->buscar . '%';
        }
        
        $query .= " ORDER BY c.fecfac DESC, c.numfac DESC";
        
        $compras = DB::connection('client_db')->select($query, $params);
        
        // Calcular totales basados en los resultados filtrados
        $this->calcularTotalesFiltrados($compras);
        $this->cargarDatosGrafico();
        $this->dispatch('filtros-aplicados-compra');
        
        return $compras;                
    }

    private function calcularTotalesFiltrados($compras)
    {
        $total = 0;
        $cantidad = count($compras);
        
        foreach ($compras as $compra) {
            $multiplicador = $compra->ticomp == 'NC' ? -1 : 1;
            $total += $compra->importe * $multiplicador;
        }
        
        $this->totalCompras = $total;
        $this->cantidadFacturas = $cantidad;
    }

    private function cargarDatosGrafico()
    {
        $this->setupClientDatabase();
        
        // Obtener datos agrupados por día
        $datosChart = DB::connection('client_db')->select("
            SELECT 
                DATE(fecfac) as fecha,
                SUM(importe * IF(tipocomp='NC',-1,1)) as total_dia,
                COUNT(*) as cantidad_facturas
            FROM {$this->tablePrefix}compras 
            WHERE DATE(fecfac) >= ? 
            AND DATE(fecfac) <= ?
            GROUP BY DATE(fecfac)
            ORDER BY fecfac ASC
        ", [$this->fechaDesde, $this->fechaHasta]);

        // Preparar datos para el gráfico
        $this->datosGrafico = collect($datosChart)->map(function ($item) {
            return [
                'fecha' => Carbon::parse($item->fecha)->format('d/m'),
                'fecha_completa' => Carbon::parse($item->fecha)->format('d/m/Y'),
                'total' => round($item->total_dia, 2),
                'cantidad' => $item->cantidad_facturas
            ];
        })->toArray();

        // Calcular totales
        $totales = DB::connection('client_db')->select("
            SELECT 
                SUM(importe * IF(tipocomp='NC',-1,1)) as total,
                COUNT(*) as cantidad
            FROM {$this->tablePrefix}compras
            WHERE DATE(fecfac) >= ? 
            AND DATE(fecfac) <= ?
        ", [$this->fechaDesde, $this->fechaHasta]);

        $this->totalCompras = $totales[0]->total ?? 0;
        $this->cantidadFacturas = $totales[0]->cantidad ?? 0;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $compras = collect($this->getCompras());
        
        // Implementar paginación manual
        $currentPage = $this->getPage();
        $offset = ($currentPage - 1) * $this->perPage;
        $comprasPaginadas = $compras->slice($offset, $this->perPage)->values();
        
        // Crear objeto de paginación
        $totalItems = $compras->count();
        $lastPage = ceil($totalItems / $this->perPage);
        
        $paginationInfo = (object)[
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'per_page' => $this->perPage,
            'total' => $totalItems,
            'from' => $offset + 1,
            'to' => min($offset + $this->perPage, $totalItems)
        ];

        return view('panel.compras-fechas', [
            'compras' => $comprasPaginadas,
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