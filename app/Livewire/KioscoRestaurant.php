<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;

class KioscoRestaurant extends Component
{
    public $clientId;
    public $tablePrefix;
    public $currentView = 'menu'; // menu, product, cart, checkout, success
    public $departamentos = [];
    public $articulos = [];
    public $productosDestacados = [];
    public $comercioData = [];
    public $selectedProduct = null;
    public $selectedOptionals = [];
    public $cart = [];
    public $customerData = ['nombre' => '', 'telefono' => ''];
    public $selectedPayment = null;
    public $orderNumber = null;
    public $formasPago = [];
    public $sessionId; // ID único para esta sesión del kiosco
    public $finalOrderItems = [];
    public $finalOrderTotal = 0;

    public function mount()
    {
        $this->setupClientDatabase();
        $this->initializeSession();
        $this->loadInitialData();
        $this->loadCartFromDatabase();
        $this->dispatch('view-changed');
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
                $this->clientId = str_pad($clientId, 6, '0', STR_PAD_LEFT);
            }
        }
    }

    private function initializeSession()
    {
        // Generar ID único para esta sesión del kiosco
        if (!session()->has('kiosco_session_id')) {
            $this->sessionId = 'KIOSCO_' . date('YmdHis') . '_' . uniqid();
            session(['kiosco_session_id' => $this->sessionId]);
        } else {
            $this->sessionId = session('kiosco_session_id');
        }
    }

    private function loadInitialData()
    {
        $this->setupClientDatabase();
        
        // Crear tablas temporales si no existen
        $this->createTempTablesIfNotExist();
        
        // Cargar datos del comercio
        $this->comercioData = DB::connection('client_db')
            ->table($this->tablePrefix . 'comercio_web')
            ->first();

        // Cargar departamentos visibles y de mostrador
        $this->departamentos = DB::connection('client_db')
            ->table($this->tablePrefix . 'deptos')
            ->where('lMostrador', 1)
            ->orderBy('orden')
            ->get()
            ->toArray();

        // Cargar artículos visibles, activos y de mostrador
        $this->articulos = DB::connection('client_db')
            ->table($this->tablePrefix . 'articu')
            ->where('lMostrador', 1)
            ->where('inactivo', 0)
            ->whereRaw('precio_m > 0 OR preciom_oferta > 0')
            ->orderBy('DEPTO')
            ->orderBy('orden')
            ->get()
            ->toArray();

        // Cargar productos destacados
        $this->productosDestacados = DB::connection('client_db')
            ->table($this->tablePrefix . 'articu')
            ->where('lMostrador', 1)
            ->where('inactivo', 0)
            ->where('destacado', 1)
            ->whereRaw('precio_m > 0 OR preciom_oferta > 0')
            ->orderBy('orden')
            ->get()
            ->toArray();

        // Cargar formas de pago
        $this->formasPago = DB::connection('client_db')
            ->table($this->tablePrefix . 'forpag')
            ->where('tactil', 1)
            ->where('tipo', '!=', 3) // Excluir "Cuenta corriente"
            ->get()
            ->toArray();
    }

    private function createTempTablesIfNotExist()
    {
        // Crear tabla pedidosm_temp si no existe
        DB::connection('client_db')->statement("
            CREATE TABLE IF NOT EXISTS pedidosm_temp (
                id INT AUTO_INCREMENT PRIMARY KEY,
                session_id VARCHAR(100) NOT NULL,
                cliente_nombre VARCHAR(255),
                cliente_telefono VARCHAR(50),
                forma_pago_id INT,
                subtotal DECIMAL(10,2) DEFAULT 0,
                total DECIMAL(10,2) DEFAULT 0,
                estado ENUM('carrito', 'finalizado', 'cancelado') DEFAULT 'carrito',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_session_estado (session_id, estado)
            )
        ");

        // Crear tabla pedidosm_det_temp si no existe
        DB::connection('client_db')->statement("
            CREATE TABLE IF NOT EXISTS pedidosm_det_temp (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pedido_temp_id INT NOT NULL,
                producto_id INT NOT NULL,
                producto_nombre VARCHAR(255) NOT NULL,
                precio_unitario DECIMAL(10,2) NOT NULL,
                cantidad INT NOT NULL DEFAULT 1,
                subtotal DECIMAL(10,2) NOT NULL,
                opcionales_json TEXT,
                solo_efectivo TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (pedido_temp_id) REFERENCES pedidosm_temp(id) ON DELETE CASCADE,
                INDEX idx_pedido_temp (pedido_temp_id)
            )
        ");
    }

    private function loadCartFromDatabase()
    {
        $this->setupClientDatabase();
        
        // Buscar pedido temporal activo para esta sesión
        $pedidoTemp = DB::connection('client_db')
            ->table('pedidosm_temp')
            ->where('session_id', $this->sessionId)
            ->where('estado', 'carrito')
            ->first();

        if ($pedidoTemp) {
            // Cargar items del carrito
            $items = DB::connection('client_db')
                ->table('pedidosm_det_temp')
                ->where('pedido_temp_id', $pedidoTemp->id)
                ->get();

            $this->cart = [];
            foreach ($items as $item) {
                $this->cart[] = [
                    'id' => $item->id,
                    'product_id' => $item->producto_id,
                    'nombre' => $item->producto_nombre,
                    'precio' => $item->precio_unitario,
                    'quantity' => $item->cantidad,
                    'opcionales' => json_decode($item->opcionales_json, true) ?: [],
                    'solo_efectivo' => $item->solo_efectivo,
                    'total' => $item->subtotal
                ];
            }

            // Cargar datos del cliente si existen
            if ($pedidoTemp->cliente_nombre) {
                $this->customerData['nombre'] = $pedidoTemp->cliente_nombre;
            }
            if ($pedidoTemp->cliente_telefono) {
                $this->customerData['telefono'] = $pedidoTemp->cliente_telefono;
            }
            if ($pedidoTemp->forma_pago_id) {
                $this->selectedPayment = $pedidoTemp->forma_pago_id;
            }
        }
    }

    private function getOrCreateTempOrder()
    {
        $this->setupClientDatabase();
        
        $pedidoTemp = DB::connection('client_db')
            ->table('pedidosm_temp')
            ->where('session_id', $this->sessionId)
            ->where('estado', 'carrito')
            ->first();

        if (!$pedidoTemp) {
            $pedidoTempId = DB::connection('client_db')
                ->table('pedidosm_temp')
                ->insertGetId([
                    'session_id' => $this->sessionId,
                    'estado' => 'carrito',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

            $pedidoTemp = DB::connection('client_db')
                ->table('pedidosm_temp')
                ->where('id', $pedidoTempId)
                ->first();
        }

        return $pedidoTemp;
    }

    private function updateTempOrderTotals($pedidoTempId)
    {
        $this->setupClientDatabase();
        
        $total = DB::connection('client_db')
            ->table('pedidosm_det_temp')
            ->where('pedido_temp_id', $pedidoTempId)
            ->sum('subtotal');

        DB::connection('client_db')
            ->table('pedidosm_temp')
            ->where('id', $pedidoTempId)
            ->update([
                'subtotal' => $total,
                'total' => $total,
                'updated_at' => now()
            ]);
    }

    private function updateTempOrderCustomerData()
    {
        if (empty($this->customerData['nombre']) && empty($this->customerData['telefono'])) {
            return;
        }

        $this->setupClientDatabase();
        
        $pedidoTemp = $this->getOrCreateTempOrder();
        
        $updateData = ['updated_at' => now()];
        
        if (!empty($this->customerData['nombre'])) {
            $updateData['cliente_nombre'] = $this->customerData['nombre'];
        }
        
        if (!empty($this->customerData['telefono'])) {
            $updateData['cliente_telefono'] = $this->customerData['telefono'];
        }
        
        if ($this->selectedPayment) {
            $updateData['forma_pago_id'] = $this->selectedPayment;
        }

        DB::connection('client_db')
            ->table('pedidosm_temp')
            ->where('id', $pedidoTemp->id)
            ->update($updateData);
    }

    public function selectProduct($productId)
    {
        $this->selectedProduct = collect($this->articulos)->firstWhere('CODIGO', $productId);
        $this->selectedOptionals = [];
        $this->setupClientDatabase();
        
        // Verificar si tiene opcionales
        $hasOptionals = DB::connection('client_db')
            ->table($this->tablePrefix . 'articulos_opcionales')
            ->where('idproducto', $productId)
            ->exists();

        if ($hasOptionals) {
            $this->currentView = 'product';
            $this->changeView('product');
        } else {
            $this->addToCart($productId);
        }
    }

    public function addToCart($productId, $quantity = 1)
    {
        $product = collect($this->articulos)->firstWhere('CODIGO', $productId);
        
        if (!$product || $product->agotado) {
            return;
        }

        // Validar opcionales obligatorios si los hay
        if ($this->currentView === 'product') {
            $opcionales = $this->getProductOptionals($productId);
            foreach ($opcionales as $grupo) {
                if ($grupo->obligatorio && $grupo->minimo > 0) {
                    if ($grupo->por_cantidad) {
                        // Para grupos por cantidad, verificar que la suma total sea >= mínimo
                        $totalQuantity = collect($this->selectedOptionals)
                            ->where('idgrupo', $grupo->id)
                            ->sum('quantity');
                        if ($totalQuantity < $grupo->minimo) {
                            session()->flash('error', 'Debe seleccionar al menos ' . $grupo->minimo . ' unidades en ' . $grupo->nombre);
                            return;
                        }
                    } else {
                        // Para grupos normales, verificar cantidad de items seleccionados
                        $selected = collect($this->selectedOptionals)->where('idgrupo', $grupo->id)->count();
                        if ($selected < $grupo->minimo) {
                            session()->flash('error', 'Debe seleccionar las opciones obligatorias en ' . $grupo->nombre);
                            return;
                        }
                    }
                }
            }
        }

        // Obtener o crear pedido temporal
        $pedidoTemp = $this->getOrCreateTempOrder();
        
        // Calcular precios
        $precioUnitario = $this->getProductPrice($product);
        $precioOpcionales = $this->calculateOptionalPrice();
        $precioTotal = $precioUnitario + $precioOpcionales;
        $subtotal = $precioTotal * $quantity;

        // Insertar item en la base de datos
        $itemId = DB::connection('client_db')
            ->table('pedidosm_det_temp')
            ->insertGetId([
                'pedido_temp_id' => $pedidoTemp->id,
                'producto_id' => $productId,
                'producto_nombre' => $product->NOMBRE,
                'precio_unitario' => $precioTotal,
                'cantidad' => $quantity,
                'subtotal' => $subtotal,
                'opcionales_json' => json_encode(array_values($this->selectedOptionals)),
                'solo_efectivo' => $product->solo_efectivo ?? 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);

        // Actualizar totales del pedido
        $this->updateTempOrderTotals($pedidoTemp->id);

        // Agregar al carrito local
        $cartItem = [
            'id' => $itemId,
            'product_id' => $productId,
            'nombre' => $product->NOMBRE,
            'precio' => $precioTotal,
            'quantity' => $quantity,
            'opcionales' => array_values($this->selectedOptionals),
            'solo_efectivo' => $product->solo_efectivo ?? 0,
            'total' => $subtotal
        ];

        $this->cart[] = $cartItem;
        $this->selectedOptionals = [];
        $this->currentView = 'menu';
        $this->changeView('menu');
        
        session()->flash('success', 'Producto agregado al carrito');
    }

    public function updateCartQuantity($cartItemId, $quantity)
    {
        if ($quantity <= 0) {
            $this->removeFromCart($cartItemId);
            return;
        }

        $this->setupClientDatabase();
        
        // Buscar el item en el carrito local
        $cartIndex = null;
        foreach ($this->cart as $index => $item) {
            if ($item['id'] == $cartItemId) {
                $cartIndex = $index;
                break;
            }
        }

        if ($cartIndex === null) return;

        // Actualizar en base de datos
        $item = DB::connection('client_db')
            ->table('pedidosm_det_temp')
            ->where('id', $cartItemId)
            ->first();

        if ($item) {
            $nuevoSubtotal = $item->precio_unitario * $quantity;
            
            DB::connection('client_db')
                ->table('pedidosm_det_temp')
                ->where('id', $cartItemId)
                ->update([
                    'cantidad' => $quantity,
                    'subtotal' => $nuevoSubtotal,
                    'updated_at' => now()
                ]);

            // Actualizar carrito local
            $this->cart[$cartIndex]['quantity'] = $quantity;
            $this->cart[$cartIndex]['total'] = $nuevoSubtotal;

            // Actualizar totales del pedido
            $this->updateTempOrderTotals($item->pedido_temp_id);
        }
    }

    public function removeFromCart($cartItemId)
    {
        $this->setupClientDatabase();
        
        // Obtener info del item antes de eliminarlo
        $item = DB::connection('client_db')
            ->table('pedidosm_det_temp')
            ->where('id', $cartItemId)
            ->first();

        if ($item) {
            // Eliminar de base de datos
            DB::connection('client_db')
                ->table('pedidosm_det_temp')
                ->where('id', $cartItemId)
                ->delete();

            // Eliminar del carrito local
            $this->cart = array_filter($this->cart, function($cartItem) use ($cartItemId) {
                return $cartItem['id'] != $cartItemId;
            });
            $this->cart = array_values($this->cart);

            // Actualizar totales del pedido
            $this->updateTempOrderTotals($item->pedido_temp_id);
        }
    }

    public function clearCart()
    {
        $this->setupClientDatabase();
        
        $pedidoTemp = DB::connection('client_db')
            ->table('pedidosm_temp')
            ->where('session_id', $this->sessionId)
            ->where('estado', 'carrito')
            ->first();

        if ($pedidoTemp) {
            // Eliminar todos los items
            DB::connection('client_db')
                ->table('pedidosm_det_temp')
                ->where('pedido_temp_id', $pedidoTemp->id)
                ->delete();

            // Eliminar el pedido temporal
            DB::connection('client_db')
                ->table('pedidosm_temp')
                ->where('id', $pedidoTemp->id)
                ->delete();
        }

        $this->cart = [];
        $this->customerData = ['nombre' => '', 'telefono' => ''];
        $this->selectedPayment = null;
        
        session()->flash('success', 'Carrito vaciado');
    }

    public function toggleOptional($optionalId, $grupoId, $maxSelections = null, $porCantidad = false)
    {
        $optionalKey = "{$grupoId}_{$optionalId}";
        
        if ($porCantidad) {
            // Modo cantidad: agregar/quitar con cantidad mínima
            if (isset($this->selectedOptionals[$optionalKey])) {
                unset($this->selectedOptionals[$optionalKey]);
            } else {
                // Obtener el grupo para saber el mínimo
                $grupo = $this->getOptionalGroup($grupoId);
                $minimo = $grupo ? $grupo->minimo : 1;
                
                $opcional = $this->getOptionalById($optionalId);
                $this->selectedOptionals[$optionalKey] = [
                    'id' => $optionalId,
                    'idgrupo' => $grupoId,
                    'nombre' => $opcional->nombre,
                    'precio' => $opcional->precio,
                    'quantity' => $minimo
                ];
            }
        } else {
            // Modo tradicional
            if ($maxSelections == 1) {
                // Radio: limpiar otros del mismo grupo
                foreach ($this->selectedOptionals as $key => $optional) {
                    if ($optional['idgrupo'] == $grupoId) {
                        unset($this->selectedOptionals[$key]);
                    }
                }
            }
            
            if (isset($this->selectedOptionals[$optionalKey])) {
                unset($this->selectedOptionals[$optionalKey]);
            } else {
                // Verificar límite máximo para checkboxes
                if ($maxSelections > 1) {
                    $currentGroupSelections = array_filter($this->selectedOptionals, function($optional) use ($grupoId) {
                        return $optional['idgrupo'] == $grupoId;
                    });
                    
                    if (count($currentGroupSelections) >= $maxSelections) {
                        session()->flash('error', 'Máximo ' . $maxSelections . ' selecciones permitidas');
                        return;
                    }
                }
                
                $opcional = $this->getOptionalById($optionalId);
                $this->selectedOptionals[$optionalKey] = [
                    'id' => $optionalId,
                    'idgrupo' => $grupoId,
                    'nombre' => $opcional->nombre,
                    'precio' => $opcional->precio,
                    'quantity' => 1
                ];
            }
        }
    }

    public function updateOptionalQuantity($optionalKey, $newQuantity)
    {
        // Extraer grupoId y optionalId del key
        $parts = explode('_', $optionalKey);
        $grupoId = $parts[0];
        $optionalId = $parts[1];

        if ($newQuantity <= 0) {
            // Remover el opcional si la cantidad es 0
            unset($this->selectedOptionals[$optionalKey]);
        } else {
            // Verificar límites del grupo
            $grupo = $this->getOptionalGroup($grupoId);
            if ($grupo && $grupo->por_cantidad) {
                // Para grupos por cantidad, verificar que la suma total no exceda el máximo
                $currentTotal = collect($this->selectedOptionals)
                    ->where('idgrupo', $grupoId)
                    ->sum('quantity');
                
                // Restar la cantidad actual de este opcional
                $currentQuantityForThis = $this->selectedOptionals[$optionalKey]['quantity'] ?? 0;
                $totalWithoutThis = $currentTotal - $currentQuantityForThis;
                
                if ($totalWithoutThis + $newQuantity > $grupo->maximo) {
                    session()->flash('error', 'No puede exceder el máximo de ' . $grupo->maximo . ' para ' . $grupo->nombre);
                    return;
                }
            }

            // Crear o actualizar el opcional
            if (!isset($this->selectedOptionals[$optionalKey])) {
                $opcional = $this->getOptionalById($optionalId);
                $this->selectedOptionals[$optionalKey] = [
                    'id' => $optionalId,
                    'idgrupo' => $grupoId,
                    'nombre' => $opcional->nombre,
                    'precio' => $opcional->precio,
                    'quantity' => $newQuantity
                ];
            } else {
                $this->selectedOptionals[$optionalKey]['quantity'] = $newQuantity;
            }
        }
    }

    public function updatedCustomerData()
    {
        $this->updateTempOrderCustomerData();
    }

    public function updatedSelectedPayment()
    {
        $this->updateTempOrderCustomerData();
    }

    public function finalizeOrder()
    {
        $this->validate([
            'customerData.nombre' => 'required|min:2',
            'customerData.telefono' => 'required|min:8',
            'selectedPayment' => 'required'
        ]);

        $this->setupClientDatabase();
        
        // Actualizar datos del cliente
        $this->updateTempOrderCustomerData();
        
        $pedidoTemp = DB::connection('client_db')
            ->table('pedidosm_temp')
            ->where('session_id', $this->sessionId)
            ->where('estado', 'carrito')
            ->first();

        if ($pedidoTemp && count($this->cart) > 0) {
            // Guardar datos finales del pedido para mostrar en success
            $this->finalOrderItems = $this->cart;
            $this->finalOrderTotal = $this->getCartTotal();
            
            // Marcar como finalizado
            DB::connection('client_db')
                ->table('pedidosm_temp')
                ->where('id', $pedidoTemp->id)
                ->update([
                    'estado' => 'finalizado',
                    'updated_at' => now()
                ]);

            // Generar número de pedido
            $this->orderNumber = str_pad($pedidoTemp->id, 7, '0', STR_PAD_LEFT);
            
            // Aquí podrías mover los datos a las tablas definitivas (mostrador, mostrador_det)
            // $this->moveToFinalTables($pedidoTemp->id);
            //$this->printTicket();
            
            $this->currentView = 'success';
            $this->changeView('success');
            
            // Limpiar datos de carrito pero mantener los datos finales
            $this->cart = [];
            
            // Generar nueva sesión para el próximo pedido
            session()->forget('kiosco_session_id');
        }
    }

    public function startNewOrder()
    {
        $this->currentView = 'menu';
        $this->changeView('menu');
        $this->orderNumber = null;
        
        // Limpiar datos del pedido finalizado
        $this->finalOrderItems = [];
        $this->finalOrderTotal = 0;
        $this->customerData = ['nombre' => '', 'telefono' => ''];
        $this->selectedPayment = null;
        
        // Generar nueva sesión
        session()->forget('kiosco_session_id');
        $this->initializeSession();
    }

    // Métodos auxiliares
    private function getProductPrice($product)
    {
        return $product->preciom_oferta > 0 ? $product->preciom_oferta : $product->precio_m;
    }

    private function getProductOptionals($productId)
    {
        $this->setupClientDatabase();
        return DB::connection('client_db')
            ->table($this->tablePrefix . 'articulos_opcionales')
            ->where('idproducto', $productId)
            ->get();
    }

    private function getOptionalsByGroup($grupoId)
    {
        $this->setupClientDatabase();
        return DB::connection('client_db')
            ->table($this->tablePrefix . 'articulos_opcionales_art')
            ->where('idgrupo', $grupoId)
            ->orderBy('orden')
            ->get();
    }

    private function getOptionalById($optionalId)
    {
        $this->setupClientDatabase();
        return DB::connection('client_db')
            ->table($this->tablePrefix . 'articulos_opcionales_art')
            ->where('id', $optionalId)
            ->first();
    }

    private function getOptionalGroup($groupId)
    {
        $this->setupClientDatabase();
        return DB::connection('client_db')
            ->table($this->tablePrefix . 'articulos_opcionales')
            ->where('id', $groupId)
            ->first();
    }

    private function calculateOptionalPrice()
    {
        $totalOptionalPrice = 0;
        
        foreach ($this->selectedOptionals as $optionalData) {
            $quantity = $optionalData['quantity'] ?? 1;
            $totalOptionalPrice += $optionalData['precio'] * $quantity;
        }
        
        return $totalOptionalPrice;
    }

    private function calculateItemTotal($product, $opcionales, $quantity)
    {
        $basePrice = $this->getProductPrice($product);
        $optionalsPrice = collect($opcionales)->sum(function($opcional) {
            return $opcional['precio'] * ($opcional['quantity'] ?? 1);
        });
        return ($basePrice + $optionalsPrice) * $quantity;
    }

    public function getCartTotal()
    {
        return collect($this->cart)->sum('total');
    }

    public function hasOnlyEffectiveProducts()
    {
        return collect($this->cart)->contains('solo_efectivo', 1);
    }

    public function getAvailablePaymentMethods()
    {
        if ($this->hasOnlyEffectiveProducts()) {
            return collect($this->formasPago)->where('codigo', 1); // Solo efectivo
        }
        return collect($this->formasPago);
    }

    public function getProductImageUrl($codigo, $small = true)
    {
        $paddedCode = str_pad($codigo, 8, '0', STR_PAD_LEFT);
        $suffix = $small ? 'p' : '';        
        $imagePath = "img/{$this->clientId}/{$paddedCode}{$suffix}.jpg";
        $fullPath = public_path($imagePath);
        
        // Verificar si el archivo existe en el servidor
        if (file_exists($fullPath)) {
            return asset("img/{$this->clientId}/{$paddedCode}{$suffix}.jpg");
        }
        
        // Retornar imagen por defecto
        return asset('img/nofoto1.jpg');
    }

    public function getIconUrl($iconName)
    {
        return asset("img/{$iconName}");
    }

    private function printTicket()
    {
        /*try {
            // Crear el contenido del ticket
            $ticketContent = $this->generateTicketContent();
            
            // Método 1: Impresora térmica vía ESC/POS
            $this->sendToThermalPrinter($ticketContent);
            
            // Método 2: Alternativa con JavaScript (si tienes impresora configurada)
            $this->dispatch('print-ticket', ['content' => $ticketContent]);
            
        } catch (\Exception $e) {
            // Log del error pero no interrumpir el flujo
            \Log::error('Error al imprimir ticket: ' . $e->getMessage());
        }*/
    }

    #[Layout('layouts.fullscreen')]
    public function render()
    {
        return view('kiosco.kiosco');
    }

    public function changeView($view)
    {
        $oldView = $this->currentView;
        $this->currentView = $view;
        if($view === 'menu' ) {
            $this->dispatch('view-changed');
        }
        if($view === 'menu' && $oldView !== 'menu') {
            $this->dispatch('scrollToTop');
        }        
    }

    public function scrollToSection($departmentId)
    {
        $this->dispatch('scroll-to-section', sectionId: "depto-{$departmentId}");
    }
}