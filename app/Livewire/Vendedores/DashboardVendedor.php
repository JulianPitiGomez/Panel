<?php
namespace App\Livewire\Vendedores;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Attributes\Layout;

class DashboardVendedor extends Component
{
    use WithPagination;
    
    // Datos del vendedor logueado
    public $vendedorCodigo;
    public $vendedorNombre;
    public $vendedorPermiteDesc = false;
    
    // Filtros y configuración
    public $filtroCliente = '';
    public $ordenarPor = 'fecha_desc';
    public $paginacionSize = 10;
    
    // Modal states
    public $mostrarModalObservaciones = false;
    public $mostrarModalFechaEntrega = false;
    public $pedidoEditando = null;
    public $nuevaObservacion = '';
    public $nuevaFechaEntrega = '';
    
    // Datos
    public $pedidosArmado = [];
    public $totalPedidosArmado = 0;
    public $totalImportePedidos = 0;
    
    protected $tablePrefix;
    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->setupClientDatabase();
        $this->cargarDatosVendedor();
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

    private function cargarDatosVendedor()
    {
        // Obtener datos del vendedor desde la sesión
        $this->vendedorCodigo = session('vendedor_user_id'); // Asumiendo que se guarda en la sesión al login
        
        if ($this->vendedorCodigo) {
            $vendedor = DB::connection('client_db')
                ->table($this->tablePrefix . 'vendedores')
                ->select('codigo', 'nombre', 'permitedesc')
                ->where('codigo', $this->vendedorCodigo)
                ->first();
                
            if ($vendedor) {
                $this->vendedorNombre = $vendedor->nombre;
                $this->vendedorPermiteDesc = $vendedor->permitedesc ?? false;
            }
        }
    }

    public function getPedidosProperty()
    {
        $this->setupClientDatabase();
        
        $query = DB::connection('client_db')
            ->table($this->tablePrefix . 'pedidos_encab as pe')
            ->leftJoin($this->tablePrefix . 'clientes as c', 'pe.codcli', '=', 'c.codigo')
            ->select(
                'pe.id',
                'pe.codcli',
                'pe.cliente',
                'pe.fecha',
                'pe.parafecha',
                'pe.estado',
                'pe.observa',
                'pe.nuevo',
                'c.direccion',
                'c.telefono',
                'c.localidad',
                DB::raw('(SELECT SUM(pd.cantidad * pd.punitario - pd.descu) FROM ' . $this->tablePrefix . 'pedidos_det pd WHERE pd.idpedido = pe.id) as total_pedido'),
                DB::raw('(SELECT COUNT(*) FROM ' . $this->tablePrefix . 'pedidos_det pd WHERE pd.idpedido = pe.id) as cantidad_items')
            )
            ->where('pe.codven', $this->vendedorCodigo)
            ->where('pe.estado', 'A') // Solo pedidos en armado
            ->orderBy($this->getOrderByField(), $this->getOrderDirection())
            ->when($this->filtroCliente, function($q) {
                return $q->where(function($subQ) {
                    $subQ->where('pe.cliente', 'like', '%' . $this->filtroCliente . '%')
                         ->orWhere('c.nombre', 'like', '%' . $this->filtroCliente . '%');
                });
            });

        return $query->paginate($this->paginacionSize);
    }

    private function getOrderByField()
    {
        $orderMap = [
            'fecha_desc' => 'pe.fecha',
            'fecha_asc' => 'pe.fecha',
            'cliente_asc' => 'pe.cliente',
            'cliente_desc' => 'pe.cliente',
            'parafecha_asc' => 'pe.parafecha',
            'parafecha_desc' => 'pe.parafecha',
            'total_desc' => 'total_pedido',
            'total_asc' => 'total_pedido'
        ];
        
        return $orderMap[$this->ordenarPor] ?? 'pe.fecha';
    }

    private function getOrderDirection()
    {
        return str_contains($this->ordenarPor, '_desc') ? 'desc' : 'asc';
    }

    public function calcularEstadisticas()
    {
        $this->setupClientDatabase();
        
        $estadisticas = DB::connection('client_db')
            ->table($this->tablePrefix . 'pedidos_encab as pe')
            ->leftJoin($this->tablePrefix . 'pedidos_det as pd', 'pe.id', '=', 'pd.idpedido')
            ->where('pe.codven', $this->vendedorCodigo)
            ->where('pe.estado', 'A')
            ->selectRaw('
                COUNT(DISTINCT pe.id) as total_pedidos,
                SUM(pd.cantidad * pd.punitario - pd.descu) as total_importe
            ')
            ->first();

        $this->totalPedidosArmado = $estadisticas->total_pedidos ?? 0;
        $this->totalImportePedidos = $estadisticas->total_importe ?? 0;
    }



    public function verHistorial()
    {
        $this->redirect(route('vendedor.historial-pedidos'), navigate: true);
    }

    public function crearNuevoPedido()
    {        
        $this->redirect(route('gestor-pedido-vendedor-nuevo'), navigate: true);
    }

    public function modificarPedido($pedidoId)
    {
        $this->redirect(route('gestor-pedido-vendedor-editar', ['pedidoId' => $pedidoId]), navigate: true);
    }

    public function abrirModalObservaciones($pedidoId)
    {   
        $this->setupClientDatabase();
        $pedido = DB::connection('client_db')
            ->table($this->tablePrefix . 'pedidos_encab')
            ->where('id', $pedidoId)
            ->where('codven', $this->vendedorCodigo)
            ->where('estado', 'A')
            ->first();

        if ($pedido) {
            $this->pedidoEditando = $pedido;
            $this->nuevaObservacion = $pedido->observa ?? '';
            $this->mostrarModalObservaciones = true;
        }
    }

    public function guardarObservaciones()
    {
        $this->setupClientDatabase();
        if ($this->pedidoEditando) {
            DB::connection('client_db')
                ->table($this->tablePrefix . 'pedidos_encab')
                ->where('id', $this->pedidoEditando->id)
                ->where('codven', $this->vendedorCodigo)
                ->where('estado', 'A')
                ->update(['observa' => $this->nuevaObservacion]);

            $this->cerrarModalObservaciones();
            $this->calcularEstadisticas();
            session()->flash('message', 'Observaciones actualizadas correctamente.');
        }
    }

    public function cerrarModalObservaciones()
    {
        $this->mostrarModalObservaciones = false;
        $this->pedidoEditando = null;
        $this->nuevaObservacion = '';
    }

    public function abrirModalFechaEntrega($pedidoId)
    {
        $this->setupClientDatabase();
        $pedido = DB::connection('client_db')
            ->table($this->tablePrefix . 'pedidos_encab')
            ->where('id', $pedidoId)
            ->where('codven', $this->vendedorCodigo)
            ->where('estado', 'A')
            ->first();

        if ($pedido) {
            $this->pedidoEditando = $pedido;
            $this->nuevaFechaEntrega = $pedido->parafecha ?? '';
            $this->mostrarModalFechaEntrega = true;
        }
    }

    public function guardarFechaEntrega()
    {
        $this->setupClientDatabase();
        $this->validate([
            'nuevaFechaEntrega' => 'required|date|after_or_equal:today'
        ]);

        if ($this->pedidoEditando) {
            DB::connection('client_db')
                ->table($this->tablePrefix . 'pedidos_encab')
                ->where('id', $this->pedidoEditando->id)
                ->where('codven', $this->vendedorCodigo)
                ->where('estado', 'A')
                ->update(['parafecha' => $this->nuevaFechaEntrega]);

            $this->cerrarModalFechaEntrega();
            $this->calcularEstadisticas();
            session()->flash('message', 'Fecha de entrega actualizada correctamente.');
        }
    }

    public function cerrarModalFechaEntrega()
    {
        $this->mostrarModalFechaEntrega = false;
        $this->pedidoEditando = null;
        $this->nuevaFechaEntrega = '';
    }

    public function enviarPedido($pedidoId)
    {
        $this->setupClientDatabase();
        
        // Verificar que el pedido existe y pertenece al vendedor
        $pedido = DB::connection('client_db')
            ->table($this->tablePrefix . 'pedidos_encab')
            ->where('id', $pedidoId)
            ->where('codven', $this->vendedorCodigo)
            ->where('estado', 'A')
            ->first();

        if ($pedido) {
            // Verificar que tenga al menos un item
            $tieneItems = DB::connection('client_db')
                ->table($this->tablePrefix . 'pedidos_det')
                ->where('idpedido', $pedidoId)
                ->exists();

            if ($tieneItems) {
                DB::connection('client_db')
                    ->table($this->tablePrefix . 'pedidos_encab')
                    ->where('id', $pedidoId)
                    ->update([
                        'estado' => 'P',
                        'nuevo' => 1
                    ]);

                $this->calcularEstadisticas();
                session()->flash('message', 'Pedido enviado correctamente.');
            } else {
                session()->flash('error', 'No se puede enviar un pedido sin productos.');
            }
        }
    }

    public function borrarPedido($pedidoId)
    {
        $this->setupClientDatabase();
        
        // Verificar que el pedido existe y pertenece al vendedor
        $pedido = DB::connection('client_db')
            ->table($this->tablePrefix . 'pedidos_encab')
            ->where('id', $pedidoId)
            ->where('codven', $this->vendedorCodigo)
            ->where('estado', 'A')
            ->first();

        if ($pedido) {
            DB::transaction(function () use ($pedidoId) {

                // Borrar encabezado
                DB::connection('client_db')
                    ->table($this->tablePrefix . 'pedidos_encab')
                    ->where('id', $pedidoId)
                    ->delete();
            });

            $this->calcularEstadisticas();
            session()->flash('message', 'Pedido eliminado correctamente.');
        }
    }

    public function imprimirPedido($pedidoId)
    {
        // Abrir en nueva ventana para imprimir
        $this->dispatch('abrir-imprimir', ['pedidoId' => $pedidoId]);
    }

    public function irAListaPrecios()
    {
        //return navigate()->toRoute('vendedor.lista-precios');
        $this->redirect(route('vendedor.lista-precios'), navigate: true);
    }

    public function irACuentasCobrar()
    {
        return navigate()->toRoute('vendedor.cuentas-cobrar');
        $this->redirect(route('vendedor.cuentas-cobrar'), navigate: true);
    }

    public function cerrarSesion()
    {
        session()->forget(['vendedor_codigo', 'client_id', 'client_table_prefix']);
        $this->redirect(route('home'), navigate: true);
    }

    public function limpiarFiltros()
    {
        $this->filtroCliente = '';
        $this->ordenarPor = 'fecha_desc';
        $this->resetPage();
        $this->calcularEstadisticas();
    }

    public function cambiarTamanoPagina($size)
    {
        $this->paginacionSize = $size;
        $this->resetPage();
    }

    #[Layout('layouts.app')] // Layout específico para vendedores
    public function render()
    {
        return view('vendedores.dashboard-vendedor', [
            'pedidos' => $this->pedidos
        ]);
    }

    // Watchers para actualizar automáticamente
    public function updated($propertyName)
    {
        if (in_array($propertyName, [ 'filtroCliente', 'ordenarPor'])) {
            $this->resetPage();
            $this->calcularEstadisticas();
        }
    }
}