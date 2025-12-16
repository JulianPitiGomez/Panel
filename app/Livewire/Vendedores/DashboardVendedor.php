<?php

namespace App\Livewire\Vendedores;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Livewire\Attributes\Layout;

class DashboardVendedor extends Component
{
    public $pedidos = [];
    public $tablePrefix = '';
    public $vendedorCodigo;
    public $pedidoExpandido = null;
    public $detalle = [];

    public $buscandoCliente = false;
    public $busqueda = '';
    public $clientesFiltrados = [];

    public $editandoObservacionId = null;
    public $nuevaObservacion = '';

    public $editandoFechaId = null;
    public $nuevaFecha = '';



    public function mount()
    {
        $this->setupClientDatabase();
        $this->vendedorCodigo = session('vendedor_user_id');
        $this->cargarPedidos();
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

    public function cargarPedidos()
    {
        $this->pedidos = DB::connection('client_db')
            ->table("{$this->tablePrefix}pedidos_encab as p")
            ->select('p.id as id','p.cliente as cliente','p.codcli as codcli', 'p.codven as codven', 'p.vendedor as vendedor', 'p.fecha as fecha', 
                     'p.estado as estado', 'p.observa as observa', 'p.parafecha as parafecha',
                     'c.direccion as direccion', 'c.telefono as telefono',
                     db::raw("sum(pe.cantidad * (pe.punitario - pe.descu)) as total"),db::raw("count(pe.id) as items"))
            ->leftjoin("{$this->tablePrefix}pedidos_det as pe", 'p.id', '=', 'pe.idpedido')
            ->leftjoin("{$this->tablePrefix}clientes as c", 'c.codigo', '=', 'p.codcli')
            ->where('p.codven', $this->vendedorCodigo)
            ->where('p.estado', 'A')
            ->orderByDesc('p.fecha')
            ->groupBy('p.id', 'p.codven', 'p.cliente','p.codcli','p.vendedor', 'p.fecha', 'p.estado', 'p.observa', 'p.parafecha','c.direccion', 'c.telefono')
            ->get();
    }

    public function iniciarNuevoPedido()
    {
        $this->buscandoCliente = true;
        $this->busqueda = '';
        $this->setupClientDatabase();
        $this->clientesFiltrados = DB::connection('client_db')
            ->table("{$this->tablePrefix}clientes")
            ->where('VENDEDOR', $this->vendedorCodigo)            
            ->where('ZONA', '!=','2')
            ->orderBy('NOMBRE')
            ->limit(10)
            ->get();
    }

    public function updatedBusqueda()
    {
        $this->setupClientDatabase();
        $this->clientesFiltrados = DB::connection('client_db')
            ->table("{$this->tablePrefix}clientes")
            ->where('VENDEDOR', $this->vendedorCodigo)
            ->where(function ($q) {
                $q->where('NOMBRE', 'like', '%' . $this->busqueda . '%')
                ->orWhere('DIRECCION', 'like', '%' . $this->busqueda . '%')
                ->orWhere('CODIGO', 'like', '%' . $this->busqueda . '%');
            })
            ->where('ZONA', '!=','2')
            ->orderBy('NOMBRE')
            ->limit(10)
            ->get();
    }

    public function seleccionarCliente($codigo)
    {
        $this->setupClientDatabase();

        $cliente = DB::connection('client_db')
            ->table("{$this->tablePrefix}clientes")
            ->where('CODIGO', $codigo)
            ->first();

        if (!$cliente) return;

        $id = DB::connection('client_db')
            ->table("{$this->tablePrefix}pedidos_encab")
            ->insertGetId([
                'codcli' => $cliente->CODIGO,
                'cliente' => $cliente->NOMBRE,
                'codven' => $this->vendedorCodigo,
                'vendedor' => session('vendedor_nombre'),
                'fecha' => db::raw('CURDATE()'),
                'parafecha' => db::raw('CURDATE()'),
                'estado' => 'A',
                'nuevo' => true
            ]);

        return redirect('vendedores/pedido/editar/'.$id);
    }

    public function modificarPedido($id)
    {
        $this->setupClientDatabase();
        $pedido = DB::connection('client_db')
            ->table("{$this->tablePrefix}pedidos_encab")
            ->where('id', $id)
            ->first();

        if (!$pedido) return;

        return redirect('vendedores/pedido/editar/'.$id);
    }

    public function eliminarPedido($id)
    {
        $this->setupClientDatabase();
        DB::connection('client_db')
            ->table("{$this->tablePrefix}pedidos_encab")
            ->where('id', $id)
            ->delete();

        $this->cargarPedidos();
    }

    public function enviarPedido($id)
    {
        $this->setupClientDatabase();
        DB::connection('client_db')
            ->table("{$this->tablePrefix}pedidos_encab")
            ->where('id', $id)
            ->update(['estado' => 'P']);

        $this->cargarPedidos();
    }

    public function editarObservacion($id)
    {
        $this->setupClientDatabase();
        $pedido = DB::connection('client_db')->table("{$this->tablePrefix}pedidos_encab")->find($id);
        $this->editandoObservacionId = $id;
        $this->nuevaObservacion = $pedido->observa ?? '';
    }

    public function modificarObservacion()
    {
        $this->setupClientDatabase();
        DB::connection('client_db')
            ->table("{$this->tablePrefix}pedidos_encab")
            ->where('id', $this->editandoObservacionId)
            ->update(['observa' => $this->nuevaObservacion]);

        $this->cargarPedidos();
        $this->editandoObservacionId = null;
    }

    public function editarFecha($id)
    {
        $this->setupClientDatabase();
        $pedido = DB::connection('client_db')->table("{$this->tablePrefix}pedidos_encab")->find($id);
        $this->editandoFechaId = $id;
        $this->nuevaFecha = $pedido->parafecha ?? '';
    }


    public function modificarFechaPara($id, $fecha)
    {
        $this->setupClientDatabase();
        DB::connection('client_db')
            ->table("{$this->tablePrefix}pedidos_encab")
            ->where('id', $this->editandoFechaId)
            ->update(['parafecha' => $this->nuevaFecha]);

        $this->cargarPedidos();
        $this->editandoFechaId = null;
    }

    public function toggleExpand($id)
    {
        $this->pedidoExpandido = $this->pedidoExpandido === $id ? null : $id;
        if ($this->pedidoExpandido) {
            $this->setupClientDatabase();
            $this->detalle = DB::connection('client_db')
                ->table("{$this->tablePrefix}pedidos_det")
                ->where('idpedido', $id)
                ->get();
        } else {
            $this->detalle = [];
        }
        
    }

    public function logout()
    {
        return $this->redirect(route('logout.vendedores'), navigate: true);
    }

    public function imprimirPedido($id)
    {
        // Solo emitir evento para abrir la ventana
        $this->dispatch('abrir-impresion', [
            'url' => route('imprimir-pedido', $id)
        ]);
    }

    // Método para la ruta
    public function imprimirPedidoRuta($id)
    {
        $this->setupClientDatabase();
        $pedido = DB::connection('client_db')->table("{$this->tablePrefix}pedidos_encab")->find($id);
        $detalles = DB::connection('client_db')->table("{$this->tablePrefix}pedidos_det")->where('idpedido', $id)->get();
        
        return view('vendedores.imprimir-pedido', compact('pedido', 'detalles'));
    }

    #[Layout('layouts.vendedores')]
    public function render()
    {
        return view('vendedores.dashboard-vendedor');
    }
}
