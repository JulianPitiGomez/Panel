<?php

namespace App\Livewire\Vendedores;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

class Deudas extends Component
{
    public $clientes = [];
    public $tablePrefix = '';
    public $vendedorCodigo;
    public $deudaExpandido = null;
    public $detalle = [];

    public $buscandoCliente = false;
    public $busqueda = '';    

    
    public function mount()
    {        
        $this->vendedorCodigo = session('vendedor_user_id');        
        $this->cargarClientes();
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

    public function cargarClientes()
    {
        $this->setupClientDatabase();
        $query = DB::connection('client_db')
            ->table("{$this->tablePrefix}clientes")
            ->where('VENDEDOR', $this->vendedorCodigo);
        if (!empty($this->busqueda)) {
            $query->where(function($q) {
                $q->where('NOMBRE', 'like', '%' . $this->busqueda . '%')
                  ->orWhere('CODIGO', 'like', '%' . $this->busqueda . '%')
                  ->orWhere('DIRECCION', 'like', '%' . $this->busqueda . '%');
            });
        }        
        $query->orderBy('NOMBRE');
        $this->clientes = $query->get();               
               
    }


    
    public function toggleExpand($id)
    {
        $this->deudaExpandido = $this->deudaExpandido === $id ? null : $id;
        if ($this->deudaExpandido) {
            $this->setupClientDatabase();
            $this->detalle = DB::connection('client_db')
                ->table("{$this->tablePrefix}ventas_cuota")
                ->where('CLIENTE', $id)
                ->where('SALDO','>',0)
                ->get();
        } else {
            $this->detalle = [];
        }
        
    }

    

    #[Layout('layouts.vendedores')]
    public function render()
    {
        $this->cargarClientes();
        return view('vendedores.deudas');
    }
}
