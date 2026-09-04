<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use App\Services\MercadoPagoService;

class KioscoRestaurant extends Component
{
    public $clientId;
    public $tablePrefix;
    public $currentView = 'welcome'; // welcome, menu, product, cart, checkout, sugerencias, pago-mp, success
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

    // Sugerencias
    public $sugerencias = [];
    public $mostrarSugerencias = false;

    // Personalización
    public $kioscoColores = [];
    public $kioscoTituloDestacados = 'Productos Destacados';
    public $kioscoPideNombre = true;
    public $kioscoPideTelefono = true;
    public $kioscoImprimeTicket = true;

    // Búsqueda
    public $busqueda = '';

    // Mercado Pago
    public $mpAccessToken = null;
    public $mpInitPoint = null;
    public $mpExternalReference = null;
    public $mpPreferenceId = null;
    public $mpPagoEstado = null;
    public $mpClientId = null;
    public $mpTipoIntegracion = 'qr_presencial';
    public $mpUserId = null;
    public $mpStoreExternalId = null;
    public $mpPosExternalId = null;
    public $mpPosQrImage = null;
    public $mpPointDeviceId = null;
    public $mpPaymentIntentId = null;

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
        // Soportar acceso desde panel (client_id) o desde kiosco público (kiosco_client_id)
        $clientId = session('kiosco_client_id') ?: session('client_id');
        if ($clientId) {
            $cliente = \App\Models\Cliente::find($clientId);

            if ($cliente) {
                \Illuminate\Support\Facades\Config::set('database.connections.client_db.database', $cliente->base);
                DB::purge('client_db');
                session(['client_table_prefix' => $cliente->getTablePrefix()]);
                $this->tablePrefix = $cliente->getTablePrefix();
                $this->clientId = str_pad($clientId, 6, '0', STR_PAD_LEFT);
                // Asegurar que client_id exista en sesión para otros componentes
                if (!session('client_id')) {
                    session(['client_id' => $clientId]);
                }
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

        // Cargar artículos visibles, activos, de mostrador y no agotados
        $this->articulos = DB::connection('client_db')
            ->table($this->tablePrefix . 'articu')
            ->where('lMostrador', 1)
            ->where('inactivo', 0)
            ->where('agotado', 0)
            ->whereRaw('precio_m > 0 OR preciom_oferta > 0')
            ->orderBy('DEPTO')
            ->orderBy('orden')
            ->get()
            ->toArray();

        // Cargar productos destacados (no agotados)
        $this->productosDestacados = DB::connection('client_db')
            ->table($this->tablePrefix . 'articu')
            ->where('lMostrador', 1)
            ->where('inactivo', 0)
            ->where('agotado', 0)
            ->where('destacado', 1)
            ->whereRaw('precio_m > 0 OR preciom_oferta > 0')
            ->orderBy('orden')
            ->get()
            ->toArray();

        // Cargar config desde config_clientes_web
        $clientId = session('kiosco_client_id') ?: session('client_id');
        $this->mpClientId = $clientId;
        $configWeb = DB::connection('config')
            ->table('config_clientes_web')
            ->where('cliente_id', $clientId)
            ->first();

        $kioscoFormasPago = [];
        if ($configWeb && $configWeb->configuracion) {
            $config = json_decode($configWeb->configuracion, true);
            $this->mpAccessToken = $config['mp_access_token'] ?? null;
            $this->mpTipoIntegracion = $config['mp_tipo_integracion'] ?? 'checkout_pro';
            $this->mpUserId = $config['mp_user_id'] ?? null;
            $this->mpStoreExternalId = $config['mp_store_external_id'] ?? null;
            $this->mpPosExternalId = $config['mp_pos_external_id'] ?? null;
            $this->mpPosQrImage = $config['mp_pos_qr_image'] ?? null;
            $this->mpPointDeviceId = $config['mp_point_device_id'] ?? null;
            $kioscoFormasPago = $config['kiosco_formas_pago'] ?? [];

            // Personalización
            $this->kioscoTituloDestacados = $config['kiosco_titulo_destacados'] ?? 'Productos Destacados';
            $this->kioscoPideNombre = $config['kiosco_pide_nombre'] ?? true;
            $this->kioscoPideTelefono = $config['kiosco_pide_telefono'] ?? true;
            $this->kioscoImprimeTicket = $config['kiosco_imprime_ticket'] ?? true;
            $this->kioscoColores = [
                'fondo' => $config['kiosco_color_fondo'] ?? '#FFFFFF',
                'letra' => $config['kiosco_color_letra'] ?? '#1F2937',
                'boton' => $config['kiosco_color_boton'] ?? '#F97316',
                'texto_boton' => $config['kiosco_color_texto_boton'] ?? '#FFFFFF',
                'precio' => $config['kiosco_color_precio'] ?? '#16A34A',
            ];
        } else {
            $this->kioscoColores = [
                'fondo' => '#FFFFFF',
                'letra' => '#1F2937',
                'boton' => '#F97316',
                'texto_boton' => '#FFFFFF',
                'precio' => '#16A34A',
            ];
        }

        // Cargar formas de pago (solo las habilitadas para tactil/kiosco)
        $query = DB::connection('client_db')
            ->table($this->tablePrefix . 'forpag')
            ->where('TACTIL', 1)
            ->where('TIPO', '!=', 3); // Excluir "Cuenta corriente"

        // Filtrar por las seleccionadas en config (si hay alguna)
        if (!empty($kioscoFormasPago)) {
            $query->whereIn('CODIGO', $kioscoFormasPago);
        }

        $this->formasPago = $query->get()->toArray();
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
                mp_external_reference VARCHAR(100) NULL,
                mp_preference_id VARCHAR(100) NULL,
                mp_payment_id VARCHAR(100) NULL,
                mp_status VARCHAR(30) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_session_estado (session_id, estado)
            )
        ");

        // Agregar columnas MP si la tabla ya existe pero no las tiene
        try {
            DB::connection('client_db')->statement("
                ALTER TABLE pedidosm_temp
                ADD COLUMN IF NOT EXISTS mp_external_reference VARCHAR(100) NULL,
                ADD COLUMN IF NOT EXISTS mp_preference_id VARCHAR(100) NULL,
                ADD COLUMN IF NOT EXISTS mp_payment_id VARCHAR(100) NULL,
                ADD COLUMN IF NOT EXISTS mp_status VARCHAR(30) NULL
            ");
        } catch (\Exception $e) {
            // Las columnas ya existen, ignorar
        }

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

        // Notificar al frontend (animación + sonido)
        $this->dispatch('product-added');

        // Cargar sugerencias basadas en el departamento del producto agregado
        $this->cargarSugerencias($product->DEPTO);

        if ($this->mostrarSugerencias) {
            $this->currentView = 'sugerencias';
            $this->changeView('sugerencias');
        } else {
            $this->currentView = 'menu';
            $this->changeView('menu');
        }

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
        $rules = ['selectedPayment' => 'required'];
        $messages = ['selectedPayment.required' => 'Seleccioná una forma de pago.'];

        if ($this->kioscoPideNombre) {
            $rules['customerData.nombre'] = 'required|min:2';
            $messages['customerData.nombre.required'] = 'Ingresá tu nombre para continuar.';
            $messages['customerData.nombre.min'] = 'El nombre debe tener al menos 2 caracteres.';
        }
        if ($this->kioscoPideTelefono) {
            $rules['customerData.telefono'] = 'required|min:8';
            $messages['customerData.telefono.required'] = 'Ingresá tu teléfono para que podamos contactarte.';
            $messages['customerData.telefono.min'] = 'El teléfono debe tener al menos 8 dígitos.';
        }

        $this->validate($rules, $messages);

        $this->setupClientDatabase();

        // Actualizar datos del cliente
        $this->updateTempOrderCustomerData();

        $pedidoTemp = DB::connection('client_db')
            ->table('pedidosm_temp')
            ->where('session_id', $this->sessionId)
            ->where('estado', 'carrito')
            ->first();

        if (!$pedidoTemp || count($this->cart) === 0) {
            session()->flash('error', 'No se encontró el pedido. Por favor, volvé al menú e intentá nuevamente.');
            return;
        }

        // Verificar si es pago con Mercado Pago (TIPO = 5,7,8)
        $formaPago = collect($this->formasPago)
            ->map(fn($fp) => is_array($fp) ? (object) $fp : $fp)
            ->firstWhere('CODIGO', $this->selectedPayment);

        \Log::info('Kiosco finalizeOrder', [
            'selectedPayment' => $this->selectedPayment,
            'formaPago' => $formaPago,
            'mpAccessToken' => $this->mpAccessToken ? 'SET' : 'NULL',
            'tipo' => $formaPago ? $formaPago->TIPO : 'N/A',
        ]);

        $esMercadoPago = $formaPago && in_array($formaPago->TIPO, [5, 7, 8]) && $this->mpAccessToken;

        if ($esMercadoPago) {
            $this->iniciarPagoMercadoPago($pedidoTemp);
            return;
        }

        // Flujo normal (sin MP)
        $this->finalizarPedidoDirecto($pedidoTemp);
    }

    private function finalizarPedidoDirecto($pedidoTemp)
    {
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

        $this->currentView = 'success';
        $this->changeView('success');
        $this->dispatch('order-confirmed');

        // Limpiar datos de carrito pero mantener los datos finales
        $this->cart = [];

        // Generar nueva sesión para el próximo pedido
        session()->forget('kiosco_session_id');
    }

    private function iniciarPagoMercadoPago($pedidoTemp)
    {
        $mp = new MercadoPagoService($this->mpAccessToken);
        $externalReference = 'KIOSCO-' . $pedidoTemp->id . '-' . time();
        $this->mpExternalReference = $externalReference;

        // Preparar items para MP
        $items = [];
        foreach ($this->cart as $item) {
            $items[] = [
                'title' => $item['nombre'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => (float) $item['precio'],
                'currency_id' => 'ARS',
            ];
        }

        $total = (float) $this->getCartTotal();

        // Solo enviar notification_url si es una URL pública (no localhost)
        $notificationUrl = route('mp.webhook', ['clientId' => $this->mpClientId]);
        $parsedHost = parse_url($notificationUrl, PHP_URL_HOST);
        if (in_array($parsedHost, ['localhost', '127.0.0.1', '::1']) || str_ends_with($parsedHost, '.local')) {
            $notificationUrl = null;
        }

        switch ($this->mpTipoIntegracion) {
            case 'qr_presencial':
                $this->iniciarPagoQRPresencial($mp, $pedidoTemp, $items, $total, $externalReference, $notificationUrl);
                break;

            case 'point':
                $this->iniciarPagoPoint($mp, $pedidoTemp, $total, $externalReference);
                break;

            case 'checkout_pro':
            default:
                $this->iniciarPagoCheckoutPro($mp, $pedidoTemp, $items, $externalReference, $notificationUrl);
                break;
        }
    }

    private function iniciarPagoQRPresencial($mp, $pedidoTemp, array $items, float $total, string $externalReference, ?string $notificationUrl)
    {
        if (!$this->mpUserId || !$this->mpStoreExternalId || !$this->mpPosExternalId) {
            Log::error('Kiosco MP QR: Config incompleta', [
                'mpUserId' => $this->mpUserId,
                'mpStoreExternalId' => $this->mpStoreExternalId,
                'mpPosExternalId' => $this->mpPosExternalId,
            ]);
            session()->flash('error', 'Ha ocurrido un error con el pago. Avise al comercio: QR Presencial no disponible.');
            return;
        }

        // Formatear items para QR presencial (formato diferente a Checkout Pro)
        $qrItems = [];
        foreach ($items as $item) {
            $qrItems[] = [
                'sku_number' => 'KIOSCO',
                'category' => 'marketplace',
                'title' => $item['title'],
                'quantity' => $item['quantity'],
                'unit_measure' => 'unit',
                'unit_price' => $item['unit_price'],
                'total_amount' => $item['unit_price'] * $item['quantity'],
            ];
        }

        $resultado = $mp->crearOrdenQR($this->mpUserId, $this->mpStoreExternalId, $this->mpPosExternalId, [
            'external_reference' => $externalReference,
            'title' => 'Pedido Kiosco #' . $pedidoTemp->id,
            'description' => 'Pedido desde kiosco autoservicio',
            'total_amount' => $total,
            'items' => $qrItems,
            'notification_url' => $notificationUrl,
        ]);

        if (!$resultado) {
            Log::error('Kiosco MP QR: crearOrdenQR falló', ['externalReference' => $externalReference]);
            session()->flash('error', 'Ha ocurrido un error al procesar el pago. Por favor intente nuevamente o avise al comercio.');
            return;
        }

        DB::connection('client_db')
            ->table('pedidosm_temp')
            ->where('id', $pedidoTemp->id)
            ->update([
                'mp_external_reference' => $externalReference,
                'mp_status' => 'pending',
                'updated_at' => now(),
            ]);

        $this->mpPagoEstado = 'pending';
        $this->currentView = 'pago-mp';
        $this->changeView('pago-mp');
    }

    private function iniciarPagoPoint($mp, $pedidoTemp, float $total, string $externalReference)
    {
        if (!$this->mpPointDeviceId) {
            Log::error('Kiosco MP Point: Device no configurado');
            session()->flash('error', 'Ha ocurrido un error con el pago. Avise al comercio: Lector Point no disponible.');
            return;
        }

        $intent = $mp->crearIntencionPago($this->mpPointDeviceId, [
            'amount' => $total,
            'description' => 'Pedido Kiosco #' . $pedidoTemp->id,
            'external_reference' => $externalReference,
        ]);

        if (!$intent) {
            Log::error('Kiosco MP Point: crearIntencionPago falló', ['externalReference' => $externalReference]);
            session()->flash('error', 'Ha ocurrido un error al procesar el pago. Por favor intente nuevamente o avise al comercio.');
            return;
        }

        $this->mpPaymentIntentId = $intent['id'];

        DB::connection('client_db')
            ->table('pedidosm_temp')
            ->where('id', $pedidoTemp->id)
            ->update([
                'mp_external_reference' => $externalReference,
                'mp_preference_id' => $intent['id'], // Reusamos campo para el intent ID
                'mp_status' => 'pending',
                'updated_at' => now(),
            ]);

        $this->mpPagoEstado = 'pending';
        $this->currentView = 'pago-mp';
        $this->changeView('pago-mp');
    }

    private function iniciarPagoCheckoutPro($mp, $pedidoTemp, array $items, string $externalReference, ?string $notificationUrl)
    {
        $preferencia = $mp->crearPreferencia($items, $externalReference, $notificationUrl);

        if (!$preferencia) {
            Log::error('Kiosco MP: crearPreferencia devolvió null', [
                'items' => $items,
                'externalReference' => $externalReference,
            ]);
            session()->flash('error', 'Error al crear el pago en Mercado Pago. Intente nuevamente.');
            return;
        }

        DB::connection('client_db')
            ->table('pedidosm_temp')
            ->where('id', $pedidoTemp->id)
            ->update([
                'mp_external_reference' => $externalReference,
                'mp_preference_id' => $preferencia['id'],
                'mp_status' => 'pending',
                'updated_at' => now(),
            ]);

        $this->mpPreferenceId = $preferencia['id'];
        $this->mpInitPoint = $preferencia['init_point'];
        $this->mpPagoEstado = 'pending';

        $this->currentView = 'pago-mp';
        $this->changeView('pago-mp');
    }

    public function regenerarQR()
    {
        $this->setupClientDatabase();

        $pedidoTemp = DB::connection('client_db')
            ->table('pedidosm_temp')
            ->where('session_id', $this->sessionId)
            ->where('estado', 'carrito')
            ->first();

        if (!$pedidoTemp) {
            $this->cancelarPagoMP();
            return;
        }

        $mp = new MercadoPagoService($this->mpAccessToken);

        $items = [];
        foreach ($this->cart as $item) {
            $items[] = [
                'title' => $item['nombre'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => (float) $item['precio'],
                'currency_id' => 'ARS',
            ];
        }

        $externalReference = 'KIOSCO-' . $pedidoTemp->id . '-' . time();
        $this->mpExternalReference = $externalReference;
        $notificationUrl = route('mp.webhook', ['clientId' => $this->mpClientId]);

        if ($this->mpTipoIntegracion === 'qr_presencial') {
            // Re-push order al QR presencial
            $total = (float) $this->getCartTotal();
            $qrItems = [];
            foreach ($items as $item) {
                $qrItems[] = [
                    'sku_number' => 'KIOSCO',
                    'category' => 'marketplace',
                    'title' => $item['title'],
                    'quantity' => $item['quantity'],
                    'unit_measure' => 'unit',
                    'unit_price' => $item['unit_price'],
                    'total_amount' => $item['unit_price'] * $item['quantity'],
                ];
            }

            $resultado = $mp->crearOrdenQR($this->mpUserId, $this->mpStoreExternalId, $this->mpPosExternalId, [
                'external_reference' => $externalReference,
                'title' => 'Pedido Kiosco #' . $pedidoTemp->id,
                'total_amount' => $total,
                'items' => $qrItems,
                'notification_url' => $notificationUrl,
            ]);

            if (!$resultado) {
                $this->dispatch('qr-error');
                return;
            }

            DB::connection('client_db')
                ->table('pedidosm_temp')
                ->where('id', $pedidoTemp->id)
                ->update([
                    'mp_external_reference' => $externalReference,
                    'mp_status' => 'pending',
                    'updated_at' => now(),
                ]);

            $this->mpPagoEstado = 'pending';
            $this->dispatch('qr-regenerado');
        } else {
            // Checkout Pro - regenerar preferencia
            $preferencia = $mp->crearPreferencia($items, $externalReference, $notificationUrl);

            if (!$preferencia) {
                $this->dispatch('qr-error');
                return;
            }

            DB::connection('client_db')
                ->table('pedidosm_temp')
                ->where('id', $pedidoTemp->id)
                ->update([
                    'mp_external_reference' => $externalReference,
                    'mp_preference_id' => $preferencia['id'],
                    'mp_status' => 'pending',
                    'updated_at' => now(),
                ]);

            $this->mpPreferenceId = $preferencia['id'];
            $this->mpInitPoint = $preferencia['init_point'];
            $this->mpPagoEstado = 'pending';

            $this->dispatch('qr-regenerado');
        }
    }

    public function verificarPagoMP()
    {
        if (!$this->mpExternalReference || !$this->mpAccessToken) {
            return;
        }

        $this->setupClientDatabase();

        $mp = new MercadoPagoService($this->mpAccessToken);
        $resultado = $mp->consultarPago($this->mpExternalReference);

        if ($resultado && $resultado['status'] === 'approved') {
            $this->mpPagoEstado = 'approved';

            // Actualizar DB
            DB::connection('client_db')
                ->table('pedidosm_temp')
                ->where('mp_external_reference', $this->mpExternalReference)
                ->update([
                    'mp_status' => 'approved',
                    'mp_payment_id' => $resultado['payment_id'] ? (string) $resultado['payment_id'] : null,
                    'updated_at' => now(),
                ]);

            // Obtener pedido temp y finalizar
            $pedidoTemp = DB::connection('client_db')
                ->table('pedidosm_temp')
                ->where('mp_external_reference', $this->mpExternalReference)
                ->first();

            if ($pedidoTemp) {
                $this->finalizarPedidoDirecto($pedidoTemp);
            }
        } elseif ($resultado && in_array($resultado['status'], ['rejected', 'cancelled'])) {
            $this->mpPagoEstado = $resultado['status'];
        }
    }

    public function simularPagoAprobado()
    {
        if (!config('app.debug') || !$this->mpExternalReference) {
            return;
        }

        $this->setupClientDatabase();

        // Marcar como aprobado en la DB
        DB::connection('client_db')
            ->table('pedidosm_temp')
            ->where('mp_external_reference', $this->mpExternalReference)
            ->update([
                'mp_status' => 'approved',
                'mp_payment_id' => 'TEST-' . time(),
                'updated_at' => now(),
            ]);

        $this->mpPagoEstado = 'approved';

        // Finalizar el pedido
        $pedidoTemp = DB::connection('client_db')
            ->table('pedidosm_temp')
            ->where('mp_external_reference', $this->mpExternalReference)
            ->first();

        if ($pedidoTemp) {
            $this->finalizarPedidoDirecto($pedidoTemp);
        }
    }

    public function cancelarPagoMP()
    {
        // Cancelar según tipo de integración
        if ($this->mpAccessToken) {
            $mp = new MercadoPagoService($this->mpAccessToken);

            if ($this->mpTipoIntegracion === 'qr_presencial' && $this->mpUserId && $this->mpStoreExternalId && $this->mpPosExternalId) {
                $mp->eliminarOrdenQR($this->mpUserId, $this->mpStoreExternalId, $this->mpPosExternalId);
            } elseif ($this->mpTipoIntegracion === 'point' && $this->mpPointDeviceId && $this->mpPaymentIntentId) {
                $mp->cancelarIntencionPago($this->mpPointDeviceId, $this->mpPaymentIntentId);
            }
        }

        $this->mpInitPoint = null;
        $this->mpExternalReference = null;
        $this->mpPreferenceId = null;
        $this->mpPagoEstado = null;
        $this->mpPaymentIntentId = null;

        $this->currentView = 'checkout';
        $this->changeView('checkout');
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

    public function resetKiosco()
    {
        // Limpiar carrito de la DB
        $this->clearCart();

        // Limpiar todo
        $this->orderNumber = null;
        $this->finalOrderItems = [];
        $this->finalOrderTotal = 0;
        $this->customerData = ['nombre' => '', 'telefono' => ''];
        $this->selectedPayment = null;
        $this->selectedProduct = null;
        $this->selectedOptionals = [];
        $this->sugerencias = [];
        $this->mostrarSugerencias = false;
        $this->mpInitPoint = null;
        $this->mpExternalReference = null;
        $this->mpPreferenceId = null;
        $this->mpPagoEstado = null;
        $this->mpPaymentIntentId = null;

        // Nueva sesión
        session()->forget('kiosco_session_id');
        $this->initializeSession();

        $this->currentView = 'welcome';
    }

    // Sugerencias
    private function cargarSugerencias($deptoId)
    {
        $this->sugerencias = [];
        $this->mostrarSugerencias = false;

        // Leer config de sugerencias
        $clientId = session('kiosco_client_id') ?: session('client_id');
        $configWeb = DB::connection('config')
            ->table('config_clientes_web')
            ->where('cliente_id', $clientId)
            ->first();

        if (!$configWeb || !$configWeb->configuracion) {
            return;
        }

        $config = json_decode($configWeb->configuracion, true);
        $sugerenciasConfig = $config['kiosco_sugerencias'] ?? [];
        $cantidad = $config['kiosco_sugerencias_cantidad'] ?? 4;

        $deptosSugeridos = $sugerenciasConfig[(string) $deptoId] ?? [];
        if (empty($deptosSugeridos)) {
            return;
        }

        // IDs de productos ya en el carrito (para no sugerir lo que ya tiene)
        $idsEnCarrito = collect($this->cart)->pluck('product_id')->toArray();

        // Agrupar candidatos por departamento
        $porDepto = [];
        foreach ($this->articulos as $a) {
            if (in_array($a->DEPTO, $deptosSugeridos) && !$a->agotado && !in_array($a->CODIGO, $idsEnCarrito)) {
                $porDepto[$a->DEPTO][] = $a;
            }
        }

        if (empty($porDepto)) {
            return;
        }

        // Mezclar productos dentro de cada departamento
        foreach ($porDepto as $depto => $productos) {
            shuffle($porDepto[$depto]);
        }

        // Distribuir equitativamente: round-robin entre departamentos
        $seleccionados = [];
        $deptosKeys = array_keys($porDepto);
        shuffle($deptosKeys); // orden aleatorio de departamentos
        $idx = 0;

        while (count($seleccionados) < $cantidad) {
            $agregado = false;
            foreach ($deptosKeys as $depto) {
                if (count($seleccionados) >= $cantidad) {
                    break;
                }
                if (!empty($porDepto[$depto])) {
                    $seleccionados[] = array_shift($porDepto[$depto]);
                    $agregado = true;
                }
            }
            if (!$agregado) {
                break; // no quedan más productos
            }
        }

        if (empty($seleccionados)) {
            return;
        }

        // Mezclar el resultado final para que no queden agrupados por depto
        shuffle($seleccionados);
        $this->sugerencias = $seleccionados;
        $this->mostrarSugerencias = true;
    }

    public function descartarSugerencias()
    {
        $this->sugerencias = [];
        $this->mostrarSugerencias = false;
        $this->currentView = 'menu';
        $this->changeView('menu');
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
        // Asegurar objetos (Livewire hydration convierte stdClass a arrays)
        $all = collect($this->formasPago)->map(fn($fp) => is_array($fp) ? (object) $fp : $fp);
        if ($this->hasOnlyEffectiveProducts()) {
            return $all->where('TIPO', 1); // Solo efectivo
        }
        return $all;
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
            $this->busqueda = '';
            $this->dispatch('view-changed');
        }
        if($view === 'menu' && $oldView !== 'menu') {
            $this->dispatch('scrollToTop');
        }
        // Auto-seleccionar forma de pago si solo hay una disponible
        if ($view === 'checkout') {
            $available = $this->getAvailablePaymentMethods();
            if ($available->count() === 1) {
                $this->selectedPayment = $available->first()->CODIGO;
            }
        }
    }

    public function scrollToSection($departmentId)
    {
        $this->dispatch('scroll-to-section', sectionId: "depto-{$departmentId}");
    }
}