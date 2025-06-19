<div class="min-h-screen bg-gray-50">

    <!-- Header fijo -->
    <div class="bg-orange-500 text-white sticky top-0 z-40">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between">
                <button wire:click="volverAlDashboard" 
                        class="p-2 rounded-lg hover:bg-orange-600 transition-colors">
                    <i class="fas fa-arrow-left text-white"></i>
                </button>
                
                <h1 class="text-lg font-bold text-center flex-1">
                    Carga de Pedidos
                </h1>
                
                <div class="w-10"></div> <!-- Spacer para centrar título -->
            </div>
        </div>
    </div>

    @if($cabeza )
        <!-- Info del cliente -->
        <div class="bg-orange-300 text-white">
            <div class="p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center">
                        <div class="bg-orange-500 bg-opacity-20 rounded-full p-2 mr-3">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-lg leading-tight">{{ $cabeza->cliente }}</h2>
                            <p class="text-white text-xs">Cliente: {{ $cabeza->codcli }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="bg-orange-500 bg-opacity-20 rounded-lg px-3 py-1">
                            <p class="text-xs text-white">Pedido #</p>
                            <p class="font-bold text-sm">{{ $cabeza->id }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Info adicional del cliente en grid compacto -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    @if($cabeza->direccion ?? false)
                        <div class="flex items-center text-blue-100">
                            <i class="fas fa-map-marker-alt mr-2 text-xs"></i>
                            <span class="truncate">{{ $cabeza->direccion }}</span>
                        </div>
                    @endif
                    @if($cabeza->localidad ?? false)
                        <div class="flex items-center text-blue-100">
                            <i class="fas fa-city mr-2 text-xs"></i>
                            <span class="truncate">{{ $cabeza->localidad }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="p-4 space-y-6">

            <!-- Datos del pedido -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="grid grid-cols-2 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha:</label>
                        <input type="date" 
                               value="{{ $cabeza->fecha }}" 
                               readonly
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-base">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Para Fecha:</label>
                        <input type="date" 
                               wire:model.blur="cabeza.parafecha"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-base">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones:</label>
                    <input type="text" 
                           wire:model.blur="cabeza.observa"
                           placeholder="Observaciones"
                           maxlength="200"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-base">
                </div>
            </div>

            <!-- Formulario de productos -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Agregar Producto</h3>
                
                <div class="space-y-4">
                    <!-- Código de producto -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Código Producto:</label>
                        <input type="number" 
                               wire:model.live.debounce.500ms="codigoProducto"
                               placeholder="Código"
                               class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-base text-right">
                        @error('codigoProducto')
                            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Búsqueda de productos -->
                    <div>
                        <button type="button"
                                wire:click="abrirModalBusqueda"
                                class="w-full px-3 py-3 border btn-primary transition-colors text-base">
                            <i class="fas fa-search mr-2"></i>
                            Buscar producto por nombre
                        </button>
                    </div>

                    <!-- Nombre del producto -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Producto:</label>
                        <input type="text" 
                               value="{{ $nombreProducto }}"
                               placeholder="Nombre del producto"
                               readonly
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-base {{ !$productoEncontrado && $nombreProducto ? 'text-red-600' : '' }}">
                    </div>

                    <!-- Unidades y Stock -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Unidades:</label>
                            <input type="number" 
                                   wire:model="unidades"
                                   placeholder="Unidades"
                                   step="0.01"
                                   min="0"
                                   class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-base text-right">
                            @error('unidades')
                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Stock Actual:</label>
                            <input type="number" 
                                   value="{{ $stockProducto }}"
                                   readonly
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-base text-right">
                        </div>
                    </div>

                    <!-- Descuento -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descuento %:</label>
                        <input type="number" 
                               wire:model="descuentoPorcentaje"
                               placeholder="Descuento %"
                               step="0.01"
                               min="0"
                               max="100"
                               {{ !$vendedorPermiteDesc ? 'readonly' : '' }}
                               class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-base text-right {{ !$vendedorPermiteDesc ? 'bg-gray-100' : '' }}">
                        @error('descuentoPorcentaje')
                            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Botón agregar -->
                    <div class="pt-4">
                        <button wire:click="agregarProducto"
                                type="button"
                                {{ !$productoEncontrado || !$unidades ? 'disabled' : '' }}
                                class="w-full px-6 py-3 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed text-base">
                            Agregar a pedido
                        </button>
                    </div>
                </div>
            </div>

            <!-- Lista de productos pedidos -->
            @if(count($itemsPedido) > 0)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <!-- Header -->
                    <div class="p-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                        <h3 class="text-lg font-semibold text-gray-800 text-center">Productos pedidos</h3>
                    </div>

                    <!-- Header de columnas (solo desktop) -->
                    <div class="hidden sm:grid sm:grid-cols-12 gap-2 p-4 bg-gray-200 text-sm font-medium text-gray-700">
                        <div class="col-span-6">Producto</div>
                        <div class="col-span-2 text-right">Código</div>
                        <div class="col-span-2 text-right">Cant.</div>
                        <div class="col-span-2 text-center">Acción</div>
                    </div>

                    <!-- Items -->
                    <div class="divide-y divide-gray-200">
                        @foreach($itemsPedido as $item)
                            <!-- Vista móvil -->
                            <div class="p-4 sm:hidden">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex-1 pr-3">
                                        <h4 class="font-medium text-gray-900 text-sm leading-tight">
                                            {{ $item->detart }}
                                        </h4>
                                        <p class="text-xs text-gray-600 mt-1">Código: {{ $item->codart }}</p>
                                        @if($item->descup > 0)
                                            <p class="text-xs text-gray-500">
                                                ${{ number_format($item->punitario, 2) }} - {{ $item->descup }}%
                                            </p>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-medium text-gray-600">Cant: {{ $item->cantidad }}</div>
                                        @if($item->descup == 0)
                                            <div class="text-xs text-gray-500">${{ number_format($item->punitario, 2) }}</div>
                                            <div class="text-sm font-bold text-blue-600">
                                                ${{ number_format($item->punitario * $item->cantidad, 2) }}
                                            </div>
                                        @else
                                            <div class="text-xs text-gray-500">
                                                ${{ number_format($item->punitario * (1 - $item->descup / 100), 2) }}
                                            </div>
                                            <div class="text-sm font-bold text-blue-600">
                                                ${{ number_format($item->punitario * $item->cantidad * (1 - $item->descup / 100), 2) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button wire:click="eliminarItem({{ $item->id }})"
                                            onclick="return confirm('¿Eliminar este producto?')"
                                            class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600 transition-colors">
                                        <i class="fas fa-times-circle"></i> Eliminar
                                    </button>
                                </div>
                            </div>

                            <!-- Vista desktop -->
                            <div class="hidden sm:grid sm:grid-cols-12 gap-2 p-4 items-center hover:bg-gray-50">
                                <div class="col-span-6">
                                    <div class="font-medium text-gray-900">{{ $item->detart }}</div>
                                    @if($item->descup > 0)
                                        <div class="text-xs text-gray-500">
                                            ${{ number_format($item->punitario, 2) }} - {{ $item->descup }}%
                                        </div>
                                    @endif
                                </div>
                                <div class="col-span-2 text-right">
                                    <div class="text-sm text-gray-600">{{ $item->codart }}</div>
                                    @if($item->descup == 0)
                                        <div class="text-xs text-gray-500">${{ number_format($item->punitario, 2) }}</div>
                                        <div class="font-bold text-blue-600">
                                            ${{ number_format($item->punitario * $item->cantidad, 2) }}
                                        </div>
                                    @else
                                        <div class="text-xs text-gray-500">
                                            ${{ number_format($item->punitario * (1 - $item->descup / 100), 2) }}
                                        </div>
                                        <div class="font-bold text-blue-600">
                                            ${{ number_format($item->punitario * $item->cantidad * (1 - $item->descup / 100), 2) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="col-span-2 text-right">
                                    <span class="font-medium">{{ $item->cantidad }}</span>
                                </div>
                                <div class="col-span-2 text-center">
                                    <button wire:click="eliminarItem({{ $item->id }})"
                                            onclick="return confirm('¿Eliminar este producto?')"
                                            class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600 transition-colors">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Totales -->
                    <div class="p-4 bg-gray-200 rounded-b-lg">
                        <!-- Móvil -->
                        <div class="sm:hidden">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-800">TOTALES</span>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-blue-600">
                                        ${{ number_format($totalPrecio, 2) }}
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        {{ $totalUnidades }} unidades
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop -->
                        <div class="hidden sm:grid sm:grid-cols-12 gap-2 items-center">
                            <div class="col-span-6">
                                <strong class="text-gray-800">T O T A L E S</strong>
                            </div>
                            <div class="col-span-2 text-right">
                                <strong class="text-lg text-blue-600">
                                    ${{ number_format($totalPrecio, 2) }}
                                </strong>
                            </div>
                            <div class="col-span-2 text-right">
                                <strong class="text-gray-800">{{ $totalUnidades }}</strong>
                            </div>
                            <div class="col-span-2"></div>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                    <div class="text-center">
                        <i class="fas fa-shopping-cart text-gray-400 text-4xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No hay productos en el pedido</h3>
                        <p class="text-gray-600">Agregue productos usando el formulario de arriba</p>
                    </div>
                </div>
            @endif

        </div>

        <!-- Modal de búsqueda de productos -->
        @if($mostrarModalBusqueda)
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-end sm:items-center justify-center z-50 p-4">
                <div class="bg-white rounded-t-xl sm:rounded-xl w-full sm:max-w-2xl max-h-[80vh] flex flex-col">
                    <!-- Header del modal -->
                    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">Buscar Productos</h3>
                        <button wire:click="cerrarModalBusqueda"
                                class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-times text-gray-600"></i>
                        </button>
                    </div>

                    <!-- Contenido del modal -->
                    <div class="flex-1 overflow-y-auto">
                        <div class="p-4">
                            <!-- Campo de búsqueda -->
                            <div class="mb-4">
                                <input type="text"
                                       wire:model.live.debounce.500ms="busquedaProducto"
                                       placeholder="Escriba parte del producto para buscar"
                                       class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-base"
                                       autofocus>
                            </div>

                            <!-- Resultados de búsqueda -->
                            @if($mostrandoBusqueda && count($productosBuscados) > 0)
                                <div class="space-y-2">
                                    <!-- Botón cancelar -->
                                    <button wire:click="cerrarModalBusqueda"
                                            class="w-full p-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-sm">
                                        Cancelar búsqueda
                                    </button>

                                    <!-- Productos encontrados -->
                                    @foreach($productosBuscados as $producto)
                                        <div class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50 transition-colors">
                                            <div class="flex justify-between items-start mb-2">
                                                <div class="flex-1 pr-3">
                                                    <h4 class="font-medium text-gray-900 text-sm">{{ $producto->NOMBRE }}</h4>
                                                    <p class="text-xs text-gray-600">Stock: {{ $producto->STOCKACT }}</p>
                                                </div>
                                                <button wire:click="seleccionarProducto('{{ $producto->CODIGO }}', '{{ addslashes($producto->NOMBRE) }}', {{ $producto->STOCKACT }})"
                                                        class="px-3 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600 transition-colors">
                                                    {{ $producto->CODIGO }}
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif(strlen($busquedaProducto) > 2 && !$mostrandoBusqueda)
                                <div class="text-center py-8">
                                    <i class="fas fa-search text-gray-400 text-3xl mb-3"></i>
                                    <p class="text-gray-600">No se encontraron productos</p>
                                </div>
                            @elseif(strlen($busquedaProducto) <= 2)
                                <div class="text-center py-8">
                                    <i class="fas fa-keyboard text-gray-400 text-3xl mb-3"></i>
                                    <p class="text-gray-600">Escriba al menos 3 caracteres para buscar</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
     @else
        <!-- Modal de selección de cliente para nuevo pedido -->
        @if($mostrandoSeleccionCliente)
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-end sm:items-center justify-center z-50 p-4">
                <div class="bg-white rounded-t-xl sm:rounded-xl w-full sm:max-w-2xl max-h-[80vh] flex flex-col">
                    <!-- Header del modal -->
                    <div class="p-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900">
                                <i class="fas fa-users text-blue-500 mr-2"></i>
                                Seleccionar Cliente
                            </h3>
                            <button wire:click="cerrarSeleccionCliente"
                                    class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                                <i class="fas fa-times text-gray-600"></i>
                            </button>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">Seleccione un cliente para crear el nuevo pedido</p>
                    </div>

                    <!-- Búsqueda de clientes -->
                    <div class="p-4 border-b border-gray-200 bg-gray-50">
                        <div class="relative">
                            <input type="text"
                                   wire:model.live="busquedaCliente"
                                   placeholder="Buscar cliente por nombre, código o localidad..."
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                        </div>
                        @if(count($clientesFiltrados) != count($clientesDelVendedor) && strlen($busquedaCliente) >= 2)
                            <p class="text-xs text-gray-600 mt-2">
                                Mostrando {{ count($clientesFiltrados) }} de {{ count($clientesDelVendedor) }} clientes
                            </p>
                        @endif
                    </div>

                    <!-- Lista de clientes -->
                    <div class="flex-1 overflow-y-auto">
                        @if(count($clientesFiltrados) > 0)
                            <div class="divide-y divide-gray-200">
                                @foreach($clientesFiltrados as $cliente)
                                    <button wire:click="seleccionarCliente('{{ $cliente->CODIGO }}')"
                                            class="w-full p-4 text-left hover:bg-blue-50 transition-colors focus:bg-blue-50 focus:outline-none">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <h4 class="font-medium text-gray-900 text-sm">
                                                    {{ $cliente->NOMBRE }}
                                                </h4>
                                                <div class="flex items-center text-xs text-gray-600 mt-1">
                                                    <span class="bg-gray-100 px-2 py-1 rounded mr-2">{{ $cliente->CODIGO }}</span>
                                                    @if($cliente->LOCALIDAD)
                                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                                        {{ $cliente->LOCALIDAD }}
                                                    @endif
                                                </div>
                                                @if($cliente->DIRECCION)
                                                    <p class="text-xs text-gray-500 mt-1 truncate">
                                                        {{ $cliente->DIRECCION }}
                                                    </p>
                                                @endif
                                                @if($cliente->TELEFONO)
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        <i class="fas fa-phone mr-1"></i>
                                                        {{ $cliente->TELEFONO }}
                                                    </p>
                                                @endif
                                            </div>
                                            <div class="ml-4">
                                                <i class="fas fa-chevron-right text-gray-400"></i>
                                            </div>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @elseif(strlen($busquedaCliente) >= 2)
                            <div class="p-8 text-center">
                                <i class="fas fa-search text-gray-400 text-4xl mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No se encontraron clientes</h3>
                                <p class="text-gray-600">Intente con otros términos de búsqueda</p>
                            </div>
                        @else
                            <div class="p-8 text-center">
                                <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">
                                    {{ count($clientesDelVendedor) > 0 ? 'Clientes Disponibles' : 'No hay clientes asignados' }}
                                </h3>
                                <p class="text-gray-600">
                                    {{ count($clientesDelVendedor) > 0 ? 'Use el buscador para encontrar un cliente' : 'No se encontraron clientes asignados a este vendedor' }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Footer del modal -->
                    <div class="p-4 border-t border-gray-200 bg-gray-50">
                        <button wire:click="cerrarSeleccionCliente"
                                class="w-full px-4 py-3 bg-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-400 transition-colors">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        @else


   
        <!-- Estado de carga o error -->
        <div class="p-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Pedido no encontrado</h3>
                    <p class="text-gray-600 mb-4">No se pudo cargar la información del pedido</p>
                    <button wire:click="volverAlDashboard"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Volver al Dashboard
                    </button>
                </div>
            </div>
        </div>
        @endif
    @endif

</div>

@push('scripts')
<script>
// Optimizaciones para móvil y funcionalidad mejorada
document.addEventListener('livewire:init', () => {
    
    // Auto-focus en cantidad cuando se selecciona un producto
    Livewire.on('producto-seleccionado', () => {
        setTimeout(() => {
            const unidadesInput = document.querySelector('input[wire\\:model="unidades"]');
            if (unidadesInput) {
                unidadesInput.focus();
                unidadesInput.select();
            }
        }, 100);
    });

    // Manejo de teclas para mejorar UX
    document.addEventListener('keydown', function(e) {
        // Enter en código de producto enfoca unidades
        if (e.key === 'Enter') {
            const activeElement = document.activeElement;
            
            if (activeElement && activeElement.matches('input[wire\\:model*="codigoProducto"]')) {
                e.preventDefault();
                const unidadesInput = document.querySelector('input[wire\\:model="unidades"]');
                if (unidadesInput) {
                    unidadesInput.focus();
                    unidadesInput.select();
                }
            }
            
            // Enter en unidades agrega el producto
            else if (activeElement && activeElement.matches('input[wire\\:model="unidades"]')) {
                e.preventDefault();
                const agregarBtn = document.querySelector('button[wire\\:click="agregarProducto"]');
                if (agregarBtn && !agregarBtn.disabled) {
                    agregarBtn.click();
                }
            }
        }
        
        // F2 para abrir búsqueda
        if (e.key === 'F2') {
            e.preventDefault();
            @this.abrirModalBusqueda();
        }
        
        // Escape para cerrar modal
        if (e.key === 'Escape') {
            @this.cerrarModalBusqueda();
        }
    });

    // Prevenir zoom en inputs en iOS
    if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
        const inputs = document.querySelectorAll('input[type="text"], input[type="number"], select, textarea');
        inputs.forEach(input => {
            input.style.fontSize = '16px';
        });
    }

    // Optimización del scroll en modales para móvil
    function optimizeModalScroll() {
        const modals = document.querySelectorAll('.fixed.inset-0');
        modals.forEach(modal => {
            modal.addEventListener('touchmove', function(e) {
                const modalContent = modal.querySelector('.bg-white');
                if (modalContent && !modalContent.contains(e.target)) {
                    e.preventDefault();
                }
            }, { passive: false });
        });
    }

    optimizeModalScroll();

    // Auto-limpiar formulario después de agregar producto
    Livewire.on('producto-agregado', () => {
        setTimeout(() => {
            const codigoInput = document.querySelector('input[wire\\:model*="codigoProducto"]');
            if (codigoInput) {
                codigoInput.focus();
            }
        }, 100);
    });
});

// Función para confirmar eliminación
function confirmarEliminacion(itemId) {
    if (confirm('¿Está seguro de eliminar este producto del pedido?')) {
        @this.eliminarItem(itemId);
    }
}

// Función para enfocar campo al cargar
window.addEventListener('load', function() {
    const codigoInput = document.querySelector('input[wire\\:model*="codigoProducto"]');
    if (codigoInput) {
        setTimeout(() => {
            codigoInput.focus();
        }, 500);
    }
});
</script>
@endpush