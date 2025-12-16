<?php

namespace App\Livewire\PanelResto;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;

class Monitor extends Component
{
    public $pedidosEnPreparacion = [];
    public $pedidosListos = [];
    protected $tablePrefix;

    public function mount()
    {
        $this->setupClientDatabase();
        $this->cargarPedidos();
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

    public function cargarPedidos()
    {
        $this->setupClientDatabase();
        
        if (!$this->tablePrefix) {
            return;
        }

        try {
            // Pedidos en preparación (estado = 2)
            $this->pedidosEnPreparacion = DB::connection('client_db')
                ->table($this->tablePrefix . 'mostrador')
                ->select('id', 'nombre')
                ->where('estado', 2)
                ->orderBy('id', 'asc')
                ->get()
                ->toArray();

            // Pedidos listos (estado = 3)
            $this->pedidosListos = DB::connection('client_db')
                ->table($this->tablePrefix . 'mostrador')
                ->select('id', 'nombre')
                ->where('estado', 3)
                ->orderBy('id', 'asc')
                ->get()
                ->toArray();

        } catch (\Exception $e) {
            // En caso de error, inicializar arrays vacíos
            $this->pedidosEnPreparacion = [];
            $this->pedidosListos = [];
        }
    }
    #[Layout('layouts.fullscreen')]
    public function render()
    {
        return view('panelresto.monitor');
    }

    public function logout()
    {
        return $this->redirect(route('logout.monitor'), navigate: true);
    }
}