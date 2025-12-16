<?php

namespace App\Livewire\VendedoresEmpresas;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

class Historial extends Component
{
    public $pedidos = [];
    public $tablePrefix = '';
    public $vendedorCodigo;
    public $pedidoExpandido = null;
    public $detalle = [];

    public $buscandoCliente = false;
    public $busqueda = '';
    public $desdefecha;
    public $hastafecha;
    public $estado = ' ';
    public $clientesFiltrados = [];


    public function mount()
    {
        $this->vendedorCodigo = session('vendedor_empresa_user_id');
        $this->desdefecha = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->hastafecha = Carbon::now()->format('Y-m-d');
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
        $this->setupClientDatabase();
        $this->pedidos = DB::connection('client_db')
            ->table("ma_pedidos_encab")
            ->where('codven', $this->vendedorCodigo)
            ->where('cliente', 'like', '%' . $this->busqueda . '%')
            ->whereBetween('fecha', [$this->desdefecha, $this->hastafecha])
            ->orderByDesc('fecha')
            ->get();

        switch ($this->estado) {
            case 'A':
                // En Armado
                $this->pedidos = $this->pedidos->where('estado', 'A');
                break; 
            case 'P':
                // En Proceso
                $this->pedidos = $this->pedidos->where('estado', 'P');
                break;                

            case 'F':
                // Facturados
                $this->pedidos = $this->pedidos->where('estado', 'F');
                break;

            default:
                break;
        }

    }



    public function toggleExpand($id)
    {
        $this->pedidoExpandido = $this->pedidoExpandido === $id ? null : $id;
        if ($this->pedidoExpandido) {
            $this->setupClientDatabase();
            $this->detalle = DB::connection('client_db')
                ->table("ma_pedidos_det")
                ->where('idpedido', $id)
                ->get();
        } else {
            $this->detalle = [];
        }

    }

    public function imprimirPedido($id)
    {
        // Solo emitir evento para abrir la ventana
        $this->dispatch('abrir-impresion', [
            'url' => route('imprimir-pedido-empresa', $id)
        ]);
    }

    // Método para la ruta
    public function imprimirPedidoRuta($id)
    {
        $this->setupClientDatabase();
        $pedido = DB::connection('client_db')->table("ma_pedidos_encab")->find($id);
        $detalles = DB::connection('client_db')->table("ma_pedidos_det")->where('idpedido', $id)->get();

        return view('vendedores-empresas.imprimir-pedido', compact('pedido', 'detalles'));
    }

    #[Layout('layouts.vendedores-empresas')]
    public function render()
    {
        $this->cargarPedidos();
        return view('vendedores-empresas.historial');
    }
}
