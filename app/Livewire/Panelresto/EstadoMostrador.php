<?php
namespace App\Livewire\PanelResto;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Attributes\Layout;

class EstadoMostrador extends Component
{
    use WithPagination;
    
    public $fechaDesde;
    public $fechaHasta;
    public $estadoSeleccionado = '';
    public $soloNoEntregados = true;
    
    // Datos
    public $estadosCount = [];
    public $totalPedidos = 0;
    public $importeTotal = 0;
    
    // Panel lateral
    public $mostrarDetallePedido = false;
    public $pedidoSeleccionado = null;
    public $detallePedido = [];
    
    // Configuración
    public $mostrarDetalle = true;
    public $mostrarResumen = true;
    public $paginacionSize = 20;
    
    protected $tablePrefix;
    protected $paginationTheme = 'bootstrap';

    // Estados y tipos
    public $estados = [
        1 => 'Pendiente',
        2 => 'En Cocina', 
        3 => 'Pedido Listo',
        4 => 'Entregado',        
    ];


    public function mount()
    {
        $this->setupClientDatabase();
        
        // Fechas por defecto (hoy)
        $this->fechaDesde = Carbon::now()->format('Y-m-d');
        $this->fechaHasta = Carbon::now()->format('Y-m-d');
        
        $this->calcularEstadisticas();
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

    public function getPedidosProperty()
    {
        $this->setupClientDatabase();
        
        $query = DB::connection('client_db')
            ->table($this->tablePrefix . 'mostrador')
            ->select(
                'id',
                'fecha',
                'hora', 
                'nombre',
                'direccion', 
                'telefono',
                'pagacon',               
                'estado',                
                'importe'
            )
            ->whereBetween('fecha', [$this->fechaDesde, $this->fechaHasta])
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc');

        // Filtros adicionales
        if ($this->estadoSeleccionado) {
            $query->where('estado', $this->estadoSeleccionado);
        }       

        if ($this->soloNoEntregados) {
            $query->where('estado', '!=', 4); // No entregados
        }

        return $query->paginate($this->paginacionSize);
    }

    public function calcularEstadisticas()
    {
        $this->setupClientDatabase();
        
        $query = DB::connection('client_db')
            ->table($this->tablePrefix . 'mostrador')
            ->whereBetween('fecha', [$this->fechaDesde, $this->fechaHasta]);

        // Aplicar filtros

        if ($this->soloNoEntregados) {
            $query->where('estado', '!=', 4);
        }

        // Contar por estados
        $this->estadosCount = DB::connection('client_db')
            ->table($this->tablePrefix . 'mostrador')
            ->select('estado', DB::raw('COUNT(*) as total'), DB::raw('SUM(importe) as importe_total'))
            ->whereBetween('fecha', [$this->fechaDesde, $this->fechaHasta])            
            ->when($this->soloNoEntregados, function($q) {
                return $q->where('estado', '!=', 4);
            })
            ->groupBy('estado')
            ->get()
            ->keyBy('estado');

        // Totales generales
        $totales = $query->selectRaw('COUNT(*) as total, SUM(importe) as importe')->first();
        $this->totalPedidos = $totales->total ?? 0;
        $this->importeTotal = $totales->importe ?? 0;
    }

    public function limpiarFiltros()
    {
        $this->estadoSeleccionado = '';
        $this->soloNoEntregados = true;
        $this->fechaDesde = Carbon::now()->format('Y-m-d');
        $this->fechaHasta = Carbon::now()->format('Y-m-d');
        
        $this->resetPage();
        $this->calcularEstadisticas();
    }

    public function toggleDetalle()
    {
        $this->mostrarDetalle = !$this->mostrarDetalle;
    }

    public function toggleResumen()
    {
        $this->mostrarResumen = !$this->mostrarResumen;
    }

    public function verDetallePedido($idPedido)
    {
        $this->setupClientDatabase();
        
        // Obtener información del pedido
        $this->pedidoSeleccionado = DB::connection('client_db')
            ->table($this->tablePrefix . 'mostrador')
            ->where('id', $idPedido)
            ->first();

        // Obtener detalle del pedido
        $this->detallePedido = DB::connection('client_db')
            ->table($this->tablePrefix . 'mostrador_det')
            ->select(
                'orden',
                'codart',
                'nomart',
                'punit',
                'cantidad',
                'ptotal',
            )
            ->where('id_mostrador', $idPedido)
            ->orderBy('orden')
            ->get();

        $this->mostrarDetallePedido = true;
    }

    public function cerrarDetallePedido()
    {
        $this->mostrarDetallePedido = false;
        $this->pedidoSeleccionado = null;
        $this->detallePedido = [];
    }

    
    public function cambiarTamanoPagina($size)
    {
        $this->paginacionSize = $size;
        $this->resetPage();
    }

    public function getEstadoColor($estado)
    {
        $colores = [
            1 => 'bg-yellow-100 text-yellow-800',   // Pendiente
            2 => 'bg-blue-100 text-blue-800',       // En cocina
            3 => 'bg-purple-100 text-purple-800',   // Pedido Listo
            4 => 'bg-green-100 text-green-800'     // Entregado            
        ];
        
        return $colores[$estado] ?? 'bg-gray-100 text-gray-800';
    }

    public function getEstadoIcon($estado)
    {
        $iconos = [
            1 => 'fas fa-clock',           // Pendiente
            2 => 'fas fa-utensils',        // Aceptado
            3 => 'fas fa-check-double',    // Entregado
            4 => 'fas fa-handshake',       // En Preparación            
        ];
        
        return $iconos[$estado] ?? 'fas fa-question-circle';
    }

    #[Layout('layouts.app')]    
    public function render()
    {
        return view('panelresto.estado-mostrador', [
            'pedidos' => $this->pedidos
        ]);
    }

    // Watchers para actualizar automáticamente
    public function updated($propertyName)
    {
        if (in_array($propertyName, ['fechaDesde', 'fechaHasta', 'estadoSeleccionado', 'soloNoEntregados'])) {
            $this->resetPage();
            $this->calcularEstadisticas();
        }
    }
}