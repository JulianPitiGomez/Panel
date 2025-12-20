<?php
namespace App\Livewire\PanelResto;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Attributes\Layout;

class EstadoDelivery extends Component
{
    use WithPagination;
    
    public $fechaDesde;
    public $fechaHasta;
    public $estadoSeleccionado = '';
    public $tipoEnvioSeleccionado = '';
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
        2 => 'Aceptado', 
        3 => 'En Delivery',
        4 => 'Entregado',
        5 => 'En Preparación',
        6 => 'Rechazado'
    ];

    public $tiposEnvio = [
        1 => 'Delivery',
        2 => 'Takeaway'
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
            ->table($this->tablePrefix . 'pedidos')
            ->select(
                'id',
                'fecha',
                'hora', 
                'nombre',
                'direccion',
                'piso_depto',
                'telefono',
                'horaent',
                'envio',
                'estado',
                'pagacon',
                'vuelto',
                'importe'
            )
            ->whereBetween('fecha', [$this->fechaDesde, $this->fechaHasta])
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc');

        // Filtros adicionales
        if ($this->estadoSeleccionado) {
            $query->where('estado', $this->estadoSeleccionado);
        }

        if ($this->tipoEnvioSeleccionado) {
            $query->where('envio', $this->tipoEnvioSeleccionado);
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
            ->table($this->tablePrefix . 'pedidos')
            ->whereBetween('fecha', [$this->fechaDesde, $this->fechaHasta]);

        // Aplicar filtros
        if ($this->tipoEnvioSeleccionado) {
            $query->where('envio', $this->tipoEnvioSeleccionado);
        }

        if ($this->soloNoEntregados) {
            $query->where('estado', '!=', 4);
        }

        // Contar por estados
        $this->estadosCount = DB::connection('client_db')
            ->table($this->tablePrefix . 'pedidos')
            ->select('estado', DB::raw('COUNT(*) as total'), DB::raw('SUM(importe) as importe_total'))
            ->whereBetween('fecha', [$this->fechaDesde, $this->fechaHasta])
            ->when($this->tipoEnvioSeleccionado, function($q) {
                return $q->where('envio', $this->tipoEnvioSeleccionado);
            })
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
        $this->tipoEnvioSeleccionado = '';
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
            ->table($this->tablePrefix . 'pedidos')
            ->where('id', $idPedido)
            ->first();

        // Obtener detalle del pedido
        $this->detallePedido = DB::connection('client_db')
            ->table($this->tablePrefix . 'pedidos_det')
            ->select(
                'orden',
                'codart',
                'nomart',
                'punit',
                'cantidad',
                'ptotal',
                'codiva'
            )
            ->where('id_pedido', $idPedido)
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

    public function cambiarEstadoPedido($idPedido, $nuevoEstado)
    {
        $this->setupClientDatabase();
        
        DB::connection('client_db')
            ->table($this->tablePrefix . 'pedidos')
            ->where('id', $idPedido)
            ->update(['estado' => $nuevoEstado]);

        // Actualizar datos
        $this->calcularEstadisticas();
        
        // Si el pedido está abierto en el panel, actualizarlo
        if ($this->pedidoSeleccionado && $this->pedidoSeleccionado->id == $idPedido) {
            $this->verDetallePedido($idPedido);
        }

        session()->flash('message', 'Estado del pedido actualizado correctamente.');
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
            2 => 'bg-blue-100 text-blue-800',       // Aceptado
            3 => 'bg-purple-100 text-purple-800',   // En Delivery
            4 => 'bg-green-100 text-green-800',     // Entregado
            5 => 'bg-orange-100 text-orange-800',   // En Preparación
            6 => 'bg-red-100 text-red-800'          // Rechazado
        ];
        
        return $colores[$estado] ?? 'bg-gray-100 text-gray-800';
    }

    public function getEstadoIcon($estado)
    {
        $iconos = [
            1 => 'fas fa-clock',           // Pendiente
            2 => 'fas fa-check-circle',    // Aceptado
            3 => 'fas fa-motorcycle',      // En Delivery
            4 => 'fas fa-check-double',    // Entregado
            5 => 'fas fa-utensils',        // En Preparación
            6 => 'fas fa-times-circle'     // Rechazado
        ];
        
        return $iconos[$estado] ?? 'fas fa-question-circle';
    }

    #[Layout('layouts.app')]    
    public function render()
    {
        return view('panelresto.estado-delivery', [
            'pedidos' => $this->pedidos
        ]);
    }

    // Watchers para actualizar automáticamente
    public function updated($propertyName)
    {
        if (in_array($propertyName, ['fechaDesde', 'fechaHasta', 'estadoSeleccionado', 'tipoEnvioSeleccionado', 'soloNoEntregados'])) {
            $this->resetPage();
            $this->calcularEstadisticas();
        }
    }
}