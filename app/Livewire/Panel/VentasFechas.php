<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

class VentasFechas extends Component
{
    use WithPagination;

    public $fechaDesde;
    public $fechaHasta;
    public $buscar = '';
    public $perPage = 15;
    public $datosGrafico = [];
    public $totalVentas = 0;
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
    }

    public function updatedFechaHasta()
    {
        $this->resetPage();
        $this->cargarDatosGrafico();
    }

    public function updatedBuscar()
    {
        $this->resetPage();
    }

    public function aplicarFiltros()
    {
        $this->resetPage();
        $this->cargarDatosGrafico();
        $this->dispatch('filtros-aplicados');
    }

    public function limpiarFiltros()
    {
        $this->fechaDesde = Carbon::now()->subMonth()->format('Y-m-d');
        $this->fechaHasta = Carbon::now()->format('Y-m-d');
        $this->buscar = '';
        $this->resetPage();
        $this->cargarDatosGrafico();
        $this->dispatch('filtros-limpiados');
    }

    private function getVentas()
    {
        $this->setupClientDatabase();
        
        $query = "
            SELECT 
                fecha,
                numcomp,
                letra,
                ticomp,
                nombre as cliente,
                importe,
                DATE_FORMAT(fecha, '%d/%m/%Y') as fecha_formateada,
                TIME_FORMAT(fecha, '%H:%i') as hora
            FROM {$this->tablePrefix}ventas_encab 
            WHERE DATE(fecha) >= ? 
            AND DATE(fecha) <= ?
        ";
        
        $params = [$this->fechaDesde, $this->fechaHasta];
        
        // Agregar filtro de búsqueda si existe
        if (!empty($this->buscar)) {
            $query .= " AND (numcomp LIKE ? OR nombre LIKE ?)";
            $params[] = '%' . $this->buscar . '%';
            $params[] = '%' . $this->buscar . '%';
        }
        
        $query .= " ORDER BY fecha DESC, numcomp DESC";
        
        $ventas = DB::connection('client_db')->select($query, $params);
        
        // Calcular totales basados en los resultados filtrados
        $this->calcularTotalesFiltrados($ventas);
        
        return $ventas;                
    }

    private function calcularTotalesFiltrados($ventas)
    {
        $total = 0;
        $cantidad = count($ventas);
        
        foreach ($ventas as $venta) {
            $multiplicador = $venta->ticomp == 'NC' ? -1 : 1;
            $total += $venta->importe * $multiplicador;
        }
        
        $this->totalVentas = $total;
        $this->cantidadFacturas = $cantidad;
    }

    private function cargarDatosGrafico()
    {
        $this->setupClientDatabase();
        
        // Obtener datos agrupados por día
        $datosChart = DB::connection('client_db')->select("
            SELECT 
                DATE(fecha) as fecha,
                SUM(importe * IF(ticomp='NC',-1,1)) as total_dia,
                COUNT(*) as cantidad_facturas
            FROM {$this->tablePrefix}ventas_encab 
            WHERE DATE(fecha) >= ? 
            AND DATE(fecha) <= ?
            GROUP BY DATE(fecha)
            ORDER BY fecha ASC
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
                SUM(importe * IF(ticomp='NC',-1,1)) as total,
                COUNT(*) as cantidad
            FROM {$this->tablePrefix}ventas_encab 
            WHERE DATE(fecha) >= ? 
            AND DATE(fecha) <= ?
        ", [$this->fechaDesde, $this->fechaHasta]);

        $this->totalVentas = $totales[0]->total ?? 0;
        $this->cantidadFacturas = $totales[0]->cantidad ?? 0;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $ventas = collect($this->getVentas());
        
        // Implementar paginación manual
        $currentPage = $this->getPage();
        $offset = ($currentPage - 1) * $this->perPage;
        $ventasPaginadas = $ventas->slice($offset, $this->perPage)->values();
        
        // Crear objeto de paginación
        $totalItems = $ventas->count();
        $lastPage = ceil($totalItems / $this->perPage);
        
        $paginationInfo = (object)[
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'per_page' => $this->perPage,
            'total' => $totalItems,
            'from' => $offset + 1,
            'to' => min($offset + $this->perPage, $totalItems)
        ];

        return view('panel.ventas-fechas', [
            'ventas' => $ventasPaginadas,
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