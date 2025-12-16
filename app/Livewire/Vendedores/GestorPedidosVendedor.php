<?php

namespace App\Livewire\Vendedores;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;

class GestorPedidosVendedor extends Component
{
    public $pedidoId;
    public $clienteId;
    public $cliente;
    public $vendedor;
    public $busqueda = '';
    public $productos = [];
    public $detallePedido = [];
    public $cantidades = [];
    public $descuentos = [];
    public $pedido;

    public $editandoObservacionId = null;
    public $nuevaObservacion = '';
    public $editandoFechaId = null;
    public $nuevaFecha = '';

    public function mount($id)
    {
        $this->pedidoId = $id;
        $this->setupClientDatabase();

        $pedido = DB::connection('client_db')
            ->table("{$this->tablePrefix}pedidos_encab")
            ->where('id', $this->pedidoId)
            ->first();
        $this->pedido = $pedido;
        $this->clienteId = $pedido->codcli ?? null;
        $this->cliente = DB::connection('client_db')
            ->table("{$this->tablePrefix}clientes")
            ->where('CODIGO', $this->clienteId)
            ->first();

        $this->vendedor = DB::connection('client_db')
            ->table("{$this->tablePrefix}vendedores")
            ->where('CODIGO', $pedido->codven)
            ->first();

        $this->cargarDetallePedido();
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

    public function updatedBusqueda()
    {
        $this->setupClientDatabase();

        $this->productos = DB::connection('client_db')
            ->table("{$this->tablePrefix}articu")
            ->where('NOSALE', 0)
            ->where(function ($q) {
                $q->where('NOMBRE', 'like', "%{$this->busqueda}%")
                  ->orWhere('CODIGO', 'like', "%{$this->busqueda}%");
            })
            ->limit(20)
            ->get();
         $productosActuales = collect($this->productos)->pluck('CODIGO')->toArray();
         $this->cantidades = array_intersect_key($this->cantidades, array_flip($productosActuales));   
    }

    public function incrementarCantidad($productoId)
    {
        // Verificar que el producto existe en los resultados actuales
        $producto = collect($this->productos)->firstWhere('CODIGO', $productoId);
        if (!$producto) {
            return; // El producto no está en los resultados actuales
        }
        
        if (!isset($this->cantidades[$productoId])) {
            $this->cantidades[$productoId] = 1;
        }
        
        // Incrementar de 0.25 en 0.25 para decimales, o de 1 en 1
        $increment = 1;
        $nuevaCantidad = floatval($this->cantidades[$productoId]) + $increment;
        $this->cantidades[$productoId] = number_format($nuevaCantidad, 2);
    }

    public function decrementarCantidad($productoId)
    {
        // Verificar que el producto existe en los resultados actuales
        $producto = collect($this->productos)->firstWhere('CODIGO', $productoId);
        if (!$producto) {
            return; // El producto no está en los resultados actuales
        }
        
        if (!isset($this->cantidades[$productoId])) {
            $this->cantidades[$productoId] = 1;
        }
        
        // Decrementar de 0.25 en 0.25 para decimales, o de 1 en 1
        $decrement = 1;
        $nuevaCantidad = floatval($this->cantidades[$productoId]) - $decrement;
        
        // No permitir cantidades menores a 0.01
        if ($nuevaCantidad >= 0.01) {
            $this->cantidades[$productoId] = number_format($nuevaCantidad, 2);
        }
    }

    public function agregarProducto($codigo)
    {
        $this->setupClientDatabase();

        $producto = DB::connection('client_db')
            ->table("{$this->tablePrefix}articu")
            ->where('CODIGO', $codigo)
            ->first();

        if (!$producto) return;

        $precio = $this->obtenerPrecioCliente($codigo);
        $descuento = $this->cliente->DESCUENTO ?? 0;
        $descup = $descuento;
        $descu = round($precio * $descup / 100, 2);
        $cantidad = $this->cantidades[$producto->CODIGO] ?? 1;

        $descuentoPorcentaje = floatval($this->descuentos[$codigo] ?? 0);        

        // Verificar que el descuento no sea mayor a 100%
        if ($descuentoPorcentaje > 100) {
            session()->flash('error', 'El descuento no puede ser mayor a 100%');
            return;
        }

        // Calcular descuento en pesos
        if ($descuentoPorcentaje > 0) {
            $descup = $descuentoPorcentaje; 
        }

        // Al final, resetear:
        $this->descuentos[$codigo] = '0';        

        DB::connection('client_db')
            ->table("{$this->tablePrefix}pedidos_det")
            ->insert([
                'idpedido' => $this->pedidoId,
                'codart' => $producto->CODIGO,
                'detart' => $producto->NOMBRE,
                'cantidad' => $cantidad,
                'cantidadreal' => $cantidad,
                'punitario' => $precio,
                'descu' => $descu,
                'descup' => $descup,
            ]);
        $this->productos = [];
        $this->cargarDetallePedido();
    }

    public function obtenerPrecioCliente($codigo)
    {
        $this->setupClientDatabase();

        $lispreesp = $this->cliente->lispreesp;
        $lispre = $this->cliente->LISPRE;

        if ($lispreesp) {
            $especial = DB::connection('client_db')
                ->table("{$this->tablePrefix}lispredet")
                ->where('CODLIS', $lispreesp)
                ->where('CODART', $codigo)
                ->value('PRECIO');

            if ($especial !== null) return $especial;
        }

        $producto = DB::connection('client_db')
            ->table("{$this->tablePrefix}articu")
            ->where('CODIGO', $codigo)
            ->first();

        return $lispre == 2 ? $producto->REVENTA : $producto->PRECIOVEN;
    }

    public function cargarDetallePedido()
    {
        $this->setupClientDatabase();

        $this->detallePedido = DB::connection('client_db')
            ->table("{$this->tablePrefix}pedidos_det")
            ->where('idpedido', $this->pedidoId)
            ->get();
    }

    public function eliminarProducto($id)
    {
        $this->setupClientDatabase();

        DB::connection('client_db')
            ->table("{$this->tablePrefix}pedidos_det")
            ->where('id', $id)
            ->delete();

        $this->cargarDetallePedido();
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

        $this->editandoFechaId = null;
    }


    #[Layout('layouts.vendedores')]
    public function render()
    {
        return view('vendedores.gestor-pedido-vendedor');
    }
}
