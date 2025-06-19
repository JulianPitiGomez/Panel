<?php
namespace App\Livewire\Vendedores;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Attributes\Layout;

class GestorPedidosVendedor extends Component
{
    // Datos del vendedor
    public $vendedorCodigo;
    public $vendedorNombre;
    public $vendedorPermiteDesc = false;
    
    // Datos del pedido
    public $pedidoId = null;
    public $esNuevoPedido = true;
    public $cabeza = null;
    public $itemsPedido = [];
    
    // Formulario de producto
    public $codigoProducto = '';
    public $nombreProducto = '';
    public $stockProducto = 0;
    public $unidades = 0;
    public $descuentoPorcentaje = 0;
    public $busquedaProducto = '';
    
    // Estados
    public $mostrandoBusqueda = false;
    public $productosBuscados = [];
    public $productoEncontrado = false;
    public $mostrarModalBusqueda = false;
    public $mostrandoSeleccionCliente = false;

    public $clientesDelVendedor = [];
    public $busquedaCliente = '';
    public $clientesFiltrados = [];

    // Totales
    public $totalUnidades = 0;
    public $totalPrecio = 0;
    
    protected $tablePrefix;

    protected $rules = [
        'codigoProducto' => 'required|numeric',
        'unidades' => 'required|numeric|min:0.01',
        'descuentoPorcentaje' => 'nullable|numeric|min:0|max:100',
    ];

    public function mount($pedidoId = null)
    {
        $this->setupClientDatabase();
        $this->cargarDatosVendedor();
        
        if ($pedidoId) {
            $this->pedidoId = $pedidoId;
            $this->esNuevoPedido = false;
            $this->cargarPedido();
        } else {
            $this->crearNuevoPedido();
        }
        
        $this->cargarItemsPedido();
        $this->calcularTotales();
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
        $this->vendedorCodigo = session('vendedor_user_id');
        $this->vendedorNombre = session('vendedor_nombre');
        $this->setupClientDatabase();
        if ($this->vendedorCodigo) {
            $vendedor = DB::connection('client_db')
                ->table($this->tablePrefix . 'vendedores')
                ->select('permitedesc')
                ->where('codigo', $this->vendedorCodigo)
                ->first();
                
            if ($vendedor) {
                $this->vendedorPermiteDesc = $vendedor->permitedesc ?? false;
            }
        }
    }

    private function crearNuevoPedido()
    {
        // Cargar clientes y mostrar modal de selección
        $this->cargarClientesDelVendedor();
        $this->mostrandoSeleccionCliente = true;
    }

    private function cargarClientesDelVendedor()
    {
        $this->setupClientDatabase();
        
        $this->clientesDelVendedor = DB::connection('client_db')
            ->table($this->tablePrefix . 'clientes')
            ->select('CODIGO', 'NOMBRE', 'DIRECCION', 'LOCALIDAD', 'TELEFONO', 'CUIT')
            ->where('VENDEDOR', $this->vendedorCodigo)
            ->orderBy('NOMBRE')
            ->get()
            ->toArray();
            
        $this->clientesFiltrados = $this->clientesDelVendedor;
    }

    public function buscarClientes()
    {
        if (strlen($this->busquedaCliente) < 2) {
            $this->clientesFiltrados = $this->clientesDelVendedor;
            return;
        }

        $busqueda = strtolower(trim($this->busquedaCliente));
        $this->setupClientDatabase();
        $this->clientesFiltrados = DB::connection('client_db')
            ->table($this->tablePrefix . 'clientes')
            ->select('CODIGO', 'NOMBRE', 'DIRECCION', 'LOCALIDAD', 'TELEFONO', 'CUIT')
            ->where('VENDEDOR', $this->vendedorCodigo)
            ->whereRaw('NOMBRE LIKE ? OR CODIGO LIKE ? OR LOWER(LOCALIDAD) LIKE ? OR LOWER(DIRECCION) LIKE ?', 
                ["%{$busqueda}%", "%{$busqueda}%", "%{$busqueda}%", "%{$busqueda}%"])
            ->get()
            ->toArray();        
    }

    public function seleccionarCliente($codigoCliente)
    {
        $this->crearPedidoParaCliente($codigoCliente);
        $this->cerrarSeleccionCliente();
    }

    public function cerrarSeleccionCliente()
    {
        $this->mostrandoSeleccionCliente = false;
        $this->busquedaCliente = '';
        $this->clientesFiltrados = [];
        $this->clientesDelVendedor = [];
    }

    public function crearPedidoParaCliente($codigoCliente)
    {
        $this->setupClientDatabase();
        
        $cliente = DB::connection('client_db')
            ->table($this->tablePrefix . 'clientes')
            ->select('CODIGO', 'NOMBRE', 'DIRECCION', 'LOCALIDAD', 'CUIT')
            ->where('CODIGO', $codigoCliente)
            ->where('VENDEDOR', $this->vendedorCodigo)
            ->first();

        if (!$cliente) {
            session()->flash('error', 'Cliente no encontrado o no asignado a este vendedor.');
            //return redirect()->route('vendedor.dashboard-vendedor');
            $this->redirect(route('dashboard-vendedor'), navigate: true);
        }

        try {
            $this->pedidoId = DB::connection('client_db')
                ->table($this->tablePrefix . 'pedidos_encab')
                ->insertGetId([
                    'codcli' => $cliente->CODIGO,
                    'cliente' => $cliente->NOMBRE,
                    'fecha' => date('Y-m-d'),
                    'parafecha' => date('Y-m-d'),
                    'codven' => $this->vendedorCodigo,
                    'estado' => 'A',
                    'vendedor' => $this->vendedorNombre
                ]);
                
            $this->esNuevoPedido = false;
            $this->cargarPedido();
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear el pedido: ' . $e->getMessage());
            $this->redirect(route('dashboard-vendedor'), navigate: true);
        }
    }

    private function cargarPedido()
    {
        $this->setupClientDatabase();
        $this->cabeza = DB::connection('client_db')
            ->table($this->tablePrefix . 'pedidos_encab')
            ->where('id', $this->pedidoId)
            ->where('codven', $this->vendedorCodigo)
            ->where('estado', 'A')
            ->first();
            
        if (!$this->cabeza) {
            session()->flash('error', 'Pedido no encontrado o no tiene permisos para editarlo.');
            $this->redirect(route('dashboard-vendedor'), navigate: true);
        }
    }

    private function cargarItemsPedido()
    {
        $this->setupClientDatabase();
        if (!$this->pedidoId) return;
        
        $this->itemsPedido = DB::connection('client_db')
            ->table($this->tablePrefix . 'pedidos_det')
            ->where('idpedido', $this->pedidoId)
            ->get()
            ->toArray();
    }

    public function buscarProductoPorCodigo()
    {
        if (!$this->codigoProducto) {
            $this->limpiarProducto();
            return;
        }

        $this->setupClientDatabase();
        
        $articulo = DB::connection('client_db')
            ->table($this->tablePrefix . 'articu')
            ->select('NOMBRE', 'STOCKACT')
            ->where('CODIGO', $this->codigoProducto)
            ->where('NOSALE', 0)
            ->first();

        if ($articulo) {
            $this->nombreProducto = str_replace('"', '', $articulo->NOMBRE);
            $this->stockProducto = $articulo->STOCKACT;
            $this->productoEncontrado = true;
            
            // Auto-focus en unidades si no tiene valor
            if (!$this->unidades) {
                $this->unidades = 1;
            }
            
            // Dispatch event para JavaScript
            $this->dispatch('producto-encontrado');
        } else {
            $this->nombreProducto = 'Articulo NO encontrado';
            $this->productoEncontrado = false;
            $this->stockProducto = 0;
            $this->unidades = 0;
        }
    }

    private function buscarStockProducto()
    {
        if (!$this->codigoProducto || !$this->productoEncontrado) return;
        $this->setupClientDatabase();
        $articulo = DB::connection('client_db')
            ->table($this->tablePrefix . 'articu')
            ->select('STOCKACT')
            ->where('CODIGO', $this->codigoProducto)
            ->where('NOSALE', 0)
            ->first();

        $this->stockProducto = $articulo ? $articulo->STOCKACT : 0;
    }

    public function buscarProductoPorNombre()
    {
        if (strlen($this->busquedaProducto) <= 2) {
            $this->productosBuscados = [];
            $this->mostrandoBusqueda = false;
            return;
        }

        $this->setupClientDatabase();
        
        $nombre = str_replace(["'", "%"], "", $this->busquedaProducto);
        $nombre = str_replace(" ", "%' and NOMBRE like '%", $nombre);
        $filtro = "NOMBRE LIKE '%" . $nombre . "%'";
        $this->setupClientDatabase();
        $this->productosBuscados = DB::connection('client_db')
            ->table($this->tablePrefix . 'articu')
            ->select('NOMBRE', 'CODIGO', 'STOCKACT')
            ->whereRaw($filtro)
            ->where('NOSALE', 0)
            ->limit(20)
            ->get()
            ->toArray();

        $this->mostrandoBusqueda = count($this->productosBuscados) > 0;
    }

    public function seleccionarProducto($codigo, $nombre, $stock)
    {
        $this->codigoProducto = $codigo;
        $this->nombreProducto = $nombre;
        $this->stockProducto = $stock;
        $this->productoEncontrado = true;
        $this->mostrandoBusqueda = false;
        $this->busquedaProducto = '';
        $this->mostrarModalBusqueda = false;
        $this->unidades = 1;
    }

    private function limpiarProducto()
    {
        $this->nombreProducto = '';
        $this->stockProducto = 0;
        $this->productoEncontrado = false;
        $this->unidades = 0;
    }

    public function agregarProducto()
    {
        $this->validate();

        if (!$this->productoEncontrado) {
            session()->flash('error', 'Debe seleccionar un producto válido.');
            return;
        }

        if ($this->unidades == 0) {
            session()->flash('error', 'La cantidad debe ser mayor a 0.');
            return;
        }

        $this->setupClientDatabase();

        try {
            // Actualizar observaciones y fecha primero
            $this->actualizarCabecera();

            // Obtener datos del cliente y calcular precio
            $cliente = DB::connection('client_db')
                ->table($this->tablePrefix . 'clientes')
                ->select('lispre', 'lispreesp')
                ->where('CODIGO', $this->cabeza->codcli)
                ->first();

            $lispre1 = $cliente->lispre;
            $lispre = $cliente->lispreesp;

            // Obtener precio del artículo
            $articulo = DB::connection('client_db')
                ->table($this->tablePrefix . 'articu')
                ->select('PRECIOVEN', 'REVENTA', 'IVA')
                ->where('CODIGO', $this->codigoProducto)
                ->first();

            if (!$articulo) {
                session()->flash('error', 'Artículo no encontrado.');
                return;
            }

            // Determinar precio según lista
            $precio = ($lispre1 == 1) ? $articulo->PRECIOVEN : $articulo->REVENTA;

            // Verificar precio especial
            $precioEspecial = DB::connection('client_db')
                ->table($this->tablePrefix . 'lispredet')
                ->select('precio')
                ->where('codlis', $lispre)
                ->where('codart', $this->codigoProducto)
                ->first();

            if ($precioEspecial) {
                $precio = $precioEspecial->precio;
            }

            // Calcular descuento
            $descuentoPeso = $precio * ($this->descuentoPorcentaje / 100);

            // Insertar item
            DB::connection('client_db')
                ->table($this->tablePrefix . 'pedidos_det')
                ->insert([
                    'idpedido' => $this->pedidoId,
                    'codart' => $this->codigoProducto,
                    'detart' => $this->nombreProducto,
                    'cantidad' => $this->unidades,
                    'cantidadreal' => $this->unidades,
                    'punitario' => $precio,
                    'descup' => $this->descuentoPorcentaje,
                    'descu' => $descuentoPeso
                ]);

            // Recargar datos
            $this->cargarItemsPedido();
            $this->calcularTotales();
            $this->limpiarFormulario();
            
            session()->flash('message', 'Producto agregado correctamente.');

        } catch (\Exception $e) {
            session()->flash('error', 'Error al agregar producto: ' . $e->getMessage());
        }
    }

    private function actualizarCabecera()
    {
        if (!$this->cabeza) return;
        $this->setupClientDatabase();
        DB::connection('client_db')
            ->table($this->tablePrefix . 'pedidos_encab')
            ->where('id', $this->pedidoId)
            ->update([
                'parafecha' => $this->cabeza->parafecha,
                'observa' => $this->cabeza->observa
            ]);
    }

    public function eliminarItem($itemId)
    {
        $this->setupClientDatabase();

        try {
            DB::connection('client_db')
                ->table($this->tablePrefix . 'pedidos_det')
                ->where('id', $itemId)
                ->where('idpedido', $this->pedidoId)
                ->delete();

            $this->cargarItemsPedido();
            $this->calcularTotales();
            
            session()->flash('message', 'Producto eliminado correctamente.');

        } catch (\Exception $e) {
            session()->flash('error', 'Error al eliminar producto: ' . $e->getMessage());
        }
    }

    private function limpiarFormulario()
    {
        $this->codigoProducto = '';
        $this->nombreProducto = '';
        $this->stockProducto = 0;
        $this->unidades = 0;
        $this->descuentoPorcentaje = 0;
        $this->productoEncontrado = false;
        $this->busquedaProducto = '';
        $this->mostrandoBusqueda = false;
    }

    private function calcularTotales()
    {
        $this->totalUnidades = 0;
        $this->totalPrecio = 0;

        foreach ($this->itemsPedido as $item) {
            $this->totalUnidades += $item->cantidad;
            $this->totalPrecio += $item->cantidad * $item->punitario * (1 - $item->descup / 100);
        }
    }

    public function volverAlDashboard()
    {
        //return redirect()->route('vendedor.dashboard');
        $this->redirect(route('dashboard-vendedor'), navigate: true);
    }

    public function abrirModalBusqueda()
    {
        $this->mostrarModalBusqueda = true;
        $this->busquedaProducto = '';
        $this->productosBuscados = [];
    }

    public function cerrarModalBusqueda()
    {
        $this->mostrarModalBusqueda = false;
        $this->busquedaProducto = '';
        $this->productosBuscados = [];
        $this->mostrandoBusqueda = false;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('vendedores.gestor-pedido-vendedor');
    }

    public function updated($propertyName)
    {
        if ($propertyName === 'codigoProducto') {
            $this->buscarProductoPorCodigo();
        }
        
        if ($propertyName === 'busquedaProducto') {
            $this->buscarProductoPorNombre();
        }

        if (in_array($propertyName, ['cabeza.parafecha', 'cabeza.observa'])) {
            $this->actualizarCabecera();
        }
        if ($propertyName === 'busquedaCliente') {  // Agregar esta línea
            $this->buscarClientes();
        }
    }
}