<div class="space-y-6">

    <!-- Header con menú hamburguesa -->
    <div class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-40">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between">

                <div class="flex items-center">
                    <h1 class="text-lg font-primary font-bold text-gray-900">
                        <i class="fas fa-chart-line text-blue-600 mr-2"></i>
                        Vendedor: 
                    </h1>
                    <div class="ml-4 flex flex-col space-y-1">
                        <div class="flex items-center">
                            <i class="fas fa-user text-green-600 mr-2"></i>
                            <span class="text-sm font-medium text-gray-700">
                                {{ session('vendedor_nombre') }}
                            </span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-at text-purple-600 mr-2"></i>
                            <span class="text-sm text-gray-600">
                                {{ session('vendedor_user') }}
                            </span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-store text-orange-600 mr-2"></i>
                            <span class="text-sm text-gray-600">
                                {{ session('cliente_nombre') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Menú hamburguesa -->
                <div class="relative">
                    <button onclick="toggleMenu()" 
                            class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-bars text-gray-600 text-xl"></i>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div id="dropdown-menu" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                        <div class="py-2">
                            <!-- Info del vendedor -->
                            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                                <div class="text-sm font-secondary font-medium text-gray-900">{{ $vendedorNombre }}</div>
                                <div class="text-xs text-gray-600">Código: {{ $vendedorCodigo }}</div>
                                @if($vendedorPermiteDesc)
                                <div class="text-xs text-green-600 mt-1">
                                    <i class="fas fa-check-circle mr-1"></i>Puede aplicar descuentos
                                </div>
                                @endif
                            </div>
                            
                            <!-- Opciones del menú -->
                            <div class="py-1">
                                <button onclick="scrollToTop(); toggleMenu();" 
                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center">
                                    <i class="fas fa-home mr-3 text-blue-600"></i>
                                    Ver Pedidos
                                </button>
                                <button wire:click="irAListaPrecios" onclick="toggleMenu()"
                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center">
                                    <i class="fas fa-tags mr-3 text-green-600"></i>
                                    Lista de Precios
                                </button>
                                <button wire:click="irACuentasCobrar" onclick="toggleMenu()"
                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center">
                                    <i class="fas fa-file-invoice-dollar mr-3 text-orange-600"></i>
                                    Cuentas a Cobrar
                                </button>
                                <div class="border-t border-gray-200 my-1"></div>
                                <button wire:click="cerrarSesion" onclick="toggleMenu()"
                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center">
                                    <i class="fas fa-sign-out-alt mr-3"></i>
                                    Cerrar Sesión
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="p-4 space-y-6">

        <!-- Botones principales -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <button wire:click="crearNuevoPedido" 
                    class="btn-primary transition-all duration-200 transform hover:scale-105">
                <div class="flex items-center justify-center space-x-3">
                    <i class="fas fa-plus-circle text-2xl"></i>
                    <div class="text-left">
                        <div class="font-primary font-bold text-lg">Nuevo Pedido</div>
                        <div class="text-sm opacity-90">Crear un pedido</div>
                    </div>
                </div>
            </button>

            <button wire:click="verHistorial" 
                    class="bg-green-600 hover:bg-green-700 text-white p-4 rounded-xl shadow-sm transition-all duration-200 transform hover:scale-105">
                <div class="flex items-center justify-center space-x-3">
                    <i class="fas fa-history text-2xl"></i>
                    <div class="text-left">
                        <div class="font-primary font-bold text-lg">Historial</div>
                        <div class="text-sm opacity-90">Ver pedidos anteriores</div>
                    </div>
                </div>
            </button>
        </div>

        <!-- Estadísticas -->
        <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-primary font-semibold text-gray-800">Pedidos en Armado</h3>
                    <p class="text-sm font-secondary text-gray-600">{{ $totalPedidosArmado }} pedidos activos</p>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-primary font-bold text-blue-600">${{ number_format($totalImportePedidos, 2) }}</p>
                    <p class="text-sm font-secondary text-gray-600">Total en proceso</p>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-filter text-blue-500 mr-2"></i>
                Filtros
            </h3>

            <div class="space-y-4">
                <!-- Fila 1: Fecha y Cliente -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Cliente</label>
                        <input type="text" 
                               wire:model.live.debounce.500ms="filtroCliente"
                               placeholder="Buscar cliente..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-secondary">
                    </div>
                </div>

                <!-- Fila 2: Ordenar y acciones -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Ordenar por</label>
                        <select wire:model.live="ordenarPor" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-secondary">
                            <option value="fecha_desc">Fecha (más reciente)</option>
                            <option value="fecha_asc">Fecha (más antigua)</option>
                            <option value="cliente_asc">Cliente (A-Z)</option>
                            <option value="cliente_desc">Cliente (Z-A)</option>
                            <option value="parafecha_asc">Fecha entrega (próxima)</option>
                            <option value="parafecha_desc">Fecha entrega (lejana)</option>
                            <option value="total_desc">Total (mayor)</option>
                            <option value="total_asc">Total (menor)</option>
                        </select>
                    </div>

                    <div class="flex space-x-2">
                        <button wire:click="limpiarFiltros" 
                                class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors duration-200 text-sm font-secondary">
                            <i class="fas fa-broom mr-1"></i>
                            Limpiar
                        </button>
                        
                        <div class="flex items-center">
                            <select wire:change="cambiarTamanoPagina($event.target.value)" 
                                    class="px-2 py-2 border border-gray-300 rounded-lg text-sm font-secondary focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="10" {{ $paginacionSize == 10 ? 'selected' : '' }}>10</option>
                                <option value="20" {{ $paginacionSize == 20 ? 'selected' : '' }}>20</option>
                                <option value="50" {{ $paginacionSize == 50 ? 'selected' : '' }}>50</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Pedidos -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="p-4 border-b border-gray-200">
                <h3 class="text-lg font-primary font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-clipboard-list text-blue-500 mr-2"></i>
                    Pedidos en Armado
                    @if($pedidos->hasPages())
                        <span class="ml-2 text-sm font-secondary text-gray-600">
                            ({{ $pedidos->firstItem() }} - {{ $pedidos->lastItem() }} de {{ $pedidos->total() }})
                        </span>
                    @endif
                </h3>
            </div>

            @if($pedidos->count() > 0)
                <!-- Vista Desktop: Tabla -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-secondary font-medium text-gray-500 uppercase">#</th>
                                <th class="px-6 py-3 text-left text-xs font-secondary font-medium text-gray-500 uppercase">Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-secondary font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-secondary font-medium text-gray-500 uppercase">Para Fecha</th>
                                <th class="px-6 py-3 text-right text-xs font-secondary font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-6 py-3 text-center text-xs font-secondary font-medium text-gray-500 uppercase">Items</th>
                                <th class="px-6 py-3 text-center text-xs font-secondary font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($pedidos as $pedido)
                            <tr class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-secondary text-gray-900">{{ $pedido->id }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-secondary font-medium text-gray-900">{{ $pedido->cliente }}</div>
                                    <div class="text-xs text-gray-500">{{ $pedido->direccion }}</div>
                                    @if($pedido->localidad)
                                    <div class="text-xs text-gray-500">{{ $pedido->localidad }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-secondary text-gray-900">{{ date('d/m/Y', strtotime($pedido->fecha)) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-secondary text-gray-900">
                                        {{ $pedido->parafecha ? date('d/m/Y', strtotime($pedido->parafecha)) : 'Sin fecha' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-secondary font-bold text-gray-900">
                                        ${{ number_format($pedido->total_pedido ?? 0, 2) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-secondary font-medium bg-blue-100 text-blue-800">
                                        {{ $pedido->cantidad_items ?? 0 }} items
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <!-- Botones de acciones para desktop -->
                                    <div class="flex items-center justify-center space-x-2">
                                        <button wire:click="modificarPedido({{ $pedido->id }})" 
                                                class="text-blue-600 hover:text-blue-900 text-sm" title="Modificar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button wire:click="abrirModalObservaciones({{ $pedido->id }})" 
                                                class="text-green-600 hover:text-green-900 text-sm" title="Observaciones">
                                            <i class="fas fa-comment"></i>
                                        </button>
                                        <button wire:click="abrirModalFechaEntrega({{ $pedido->id }})" 
                                                class="text-orange-600 hover:text-orange-900 text-sm" title="Fecha entrega">
                                            <i class="fas fa-calendar"></i>
                                        </button>
                                        <button wire:click="enviarPedido({{ $pedido->id }})" 
                                                class="text-purple-600 hover:text-purple-900 text-sm" title="Enviar">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                        <button wire:click="imprimirPedido({{ $pedido->id }})" 
                                                class="text-gray-600 hover:text-gray-900 text-sm" title="Imprimir">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        <button wire:click="borrarPedido({{ $pedido->id }})" 
                                                onclick="return confirm('¿Está seguro de eliminar este pedido?')"
                                                class="text-red-600 hover:text-red-900 text-sm" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @if($pedido->observa)
                            <tr class="bg-yellow-50">
                                <td colspan="6" class="px-6 py-2">
                                    <div class="text-xs font-secondary text-gray-600">
                                        <i class="fas fa-comment text-yellow-500 mr-1"></i>
                                        <strong>Observaciones:</strong> {{ $pedido->observa }}
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Vista Mobile: Cards -->
                <div class="lg:hidden space-y-4 p-4">
                    @foreach($pedidos as $pedido)
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                        <!-- Header del card -->
                        <div class="p-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h4 class="font-secondary font-semibold text-gray-900">{{ $pedido->cliente }}</h4>
                                    <p class="text-sm text-gray-600">
                                        Creado: {{ date('d/m/Y', strtotime($pedido->fecha)) }}
                                    </p>
                                    @if($pedido->localidad)
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-map-marker-alt mr-1"></i>{{ $pedido->localidad }}
                                    </p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-primary font-bold text-blue-600">
                                        ${{ number_format($pedido->total_pedido ?? 0, 2) }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $pedido->cantidad_items ?? 0 }} items
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contenido del card -->
                        <div class="p-4 space-y-3">
                            <!-- Fecha de entrega -->
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-secondary text-gray-600">Para fecha:</span>
                                <span class="text-sm font-secondary font-medium text-gray-900">
                                    {{ $pedido->parafecha ? date('d/m/Y', strtotime($pedido->parafecha)) : 'Sin fecha' }}
                                </span>
                            </div>

                            <!-- Contacto -->
                            @if($pedido->telefono)
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-secondary text-gray-600">Teléfono:</span>
                                <a href="tel:{{ $pedido->telefono }}" class="text-sm font-secondary text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-phone mr-1"></i>{{ $pedido->telefono }}
                                </a>
                            </div>
                            @endif

                            <!-- Dirección -->
                            @if($pedido->direccion)
                            <div>
                                <span class="text-sm font-secondary text-gray-600">Dirección:</span>
                                <p class="text-sm font-secondary text-gray-900 mt-1">{{ $pedido->direccion }}</p>
                            </div>
                            @endif

                            <!-- Observaciones -->
                            @if($pedido->observa)
                            <div class="bg-yellow-50 p-3 rounded-lg border-l-4 border-yellow-400">
                                <div class="text-sm font-secondary text-gray-600">
                                    <i class="fas fa-comment text-yellow-500 mr-1"></i>
                                    <strong>Observaciones:</strong>
                                </div>
                                <p class="text-sm font-secondary text-gray-900 mt-1">{{ $pedido->observa }}</p>
                            </div>
                            @endif
                        </div>

                        <!-- Acciones del card -->
                        <div class="p-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                            <!-- Primera fila de botones -->
                            <div class="grid grid-cols-2 gap-2 mb-3">
                                <button wire:click="modificarPedido({{ $pedido->id }})" 
                                        class="px-3 py-2 bg-blue-500 text-white rounded-lg font-secondary text-sm hover:bg-blue-600 transition-colors flex items-center justify-center">
                                    <i class="fas fa-edit mr-1"></i>
                                    Modificar
                                </button>
                                
                                <button wire:click="enviarPedido({{ $pedido->id }})" 
                                        class="px-3 py-2 bg-purple-500 text-white rounded-lg font-secondary text-sm hover:bg-purple-600 transition-colors flex items-center justify-center">
                                    <i class="fas fa-paper-plane mr-1"></i>
                                    Enviar
                                </button>
                            </div>

                            <!-- Segunda fila de botones -->
                            <div class="grid grid-cols-3 gap-2">
                                <button wire:click="abrirModalObservaciones({{ $pedido->id }})" 
                                        class="px-3 py-2 bg-green-500 text-white rounded-lg font-secondary text-xs hover:bg-green-600 transition-colors flex items-center justify-center">
                                    <i class="fas fa-comment mr-1"></i>
                                    Obs.
                                </button>
                                
                                <button wire:click="abrirModalFechaEntrega({{ $pedido->id }})" 
                                        class="px-3 py-2 bg-orange-500 text-white rounded-lg font-secondary text-xs hover:bg-orange-600 transition-colors flex items-center justify-center">
                                    <i class="fas fa-calendar mr-1"></i>
                                    Fecha
                                </button>
                                
                                <button wire:click="imprimirPedido({{ $pedido->id }})" 
                                        class="px-3 py-2 bg-gray-500 text-white rounded-lg font-secondary text-xs hover:bg-gray-600 transition-colors flex items-center justify-center">
                                    <i class="fas fa-print mr-1"></i>
                                    Print
                                </button>
                            </div>

                            <!-- Botón eliminar separado -->
                            <div class="mt-3">
                                <button wire:click="borrarPedido({{ $pedido->id }})" 
                                        onclick="return confirm('¿Está seguro de eliminar este pedido?')"
                                        class="w-full px-3 py-2 bg-red-500 text-white rounded-lg font-secondary text-sm hover:bg-red-600 transition-colors flex items-center justify-center">
                                    <i class="fas fa-trash mr-1"></i>
                                    Eliminar Pedido
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Paginación -->
                @if($pedidos->hasPages())
                <div class="p-4 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="text-sm font-secondary text-gray-600">
                            Mostrando {{ $pedidos->firstItem() }} - {{ $pedidos->lastItem() }} de {{ $pedidos->total() }} pedidos
                        </div>
                        
                        <nav class="flex items-center justify-center space-x-1" aria-label="Paginación">
                            {{-- Botón Anterior --}}
                            @if($pedidos->onFirstPage())
                                <span class="px-3 py-2 text-sm font-secondary text-gray-400 cursor-not-allowed">
                                    <i class="fas fa-chevron-left"></i>
                                </span>
                            @else
                                <button wire:click="previousPage" 
                                        class="px-3 py-2 text-sm font-secondary text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors duration-200">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                            @endif

                            {{-- Números de Página (simplificado para móvil) --}}
                            @if($pedidos->lastPage() <= 5)
                                @for($page = 1; $page <= $pedidos->lastPage(); $page++)
                                    @if($page == $pedidos->currentPage())
                                        <span class="px-3 py-2 text-sm font-secondary bg-blue-500 text-white rounded-lg">
                                            {{ $page }}
                                        </span>
                                    @else
                                        <button wire:click="gotoPage({{ $page }})" 
                                                class="px-3 py-2 text-sm font-secondary text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors duration-200">
                                            {{ $page }}
                                        </button>
                                    @endif
                                @endfor
                            @else
                                <!-- Paginación compacta para muchas páginas -->
                                <span class="px-3 py-2 text-sm font-secondary bg-blue-500 text-white rounded-lg">
                                    {{ $pedidos->currentPage() }} / {{ $pedidos->lastPage() }}
                                </span>
                            @endif

                            {{-- Botón Siguiente --}}
                            @if($pedidos->hasMorePages())
                                <button wire:click="nextPage" 
                                        class="px-3 py-2 text-sm font-secondary text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors duration-200">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            @else
                                <span class="px-3 py-2 text-sm font-secondary text-gray-400 cursor-not-allowed">
                                    <i class="fas fa-chevron-right"></i>
                                </span>
                            @endif
                        </nav>
                    </div>
                </div>
                @endif

            @else
                <div class="text-center py-12">
                    <i class="fas fa-clipboard-list text-gray-400 text-5xl mb-4"></i>
                    <h3 class="text-lg font-primary font-medium text-gray-900 mb-2">No hay pedidos</h3>
                    <p class="text-gray-600 font-secondary mb-4">No tienes pedidos en armado en este momento</p>
                    <button wire:click="crearNuevoPedido" 
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg font-secondary hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Crear Primer Pedido
                    </button>
                </div>
            @endif
        </div>

    </div>

    <!-- Modal Observaciones -->
    @if($mostrarModalObservaciones)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl max-w-md w-full">
            <div class="p-6">
                <h3 class="text-lg font-primary font-semibold text-gray-900 mb-4">
                    Observaciones del Pedido
                </h3>
                
                <div class="mb-4">
                    <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">
                        Observaciones
                    </label>
                    <textarea wire:model="nuevaObservacion"
                              rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-secondary"
                              placeholder="Ingrese observaciones del pedido..."></textarea>
                </div>

                <div class="flex space-x-3">
                    <button wire:click="guardarObservaciones" 
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-secondary hover:bg-blue-700 transition-colors">
                        Guardar
                    </button>
                    <button wire:click="cerrarModalObservaciones" 
                            class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg font-secondary hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Fecha de Entrega -->
    @if($mostrarModalFechaEntrega)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl max-w-md w-full">
            <div class="p-6">
                <h3 class="text-lg font-primary font-semibold text-gray-900 mb-4">
                    Fecha de Entrega
                </h3>
                
                <div class="mb-4">
                    <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">
                        Nueva fecha de entrega
                    </label>
                    <input type="date" 
                           wire:model="nuevaFechaEntrega"
                           min="{{ date('Y-m-d') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-secondary">
                    @error('nuevaFechaEntrega')
                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex space-x-3">
                    <button wire:click="guardarFechaEntrega" 
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-secondary hover:bg-blue-700 transition-colors">
                        Guardar
                    </button>
                    <button wire:click="cerrarModalFechaEntrega" 
                            class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg font-secondary hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@push('scripts')
<script>
function toggleMenu() {
    const menu = document.getElementById('dropdown-menu');
    menu.classList.toggle('hidden');
}

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Cerrar menú al hacer click fuera
document.addEventListener('click', function(event) {
    const menu = document.getElementById('dropdown-menu');
    const button = event.target.closest('[onclick*="toggleMenu"]');
    
    if (!button && !menu.contains(event.target)) {
        menu.classList.add('hidden');
    }
});

// Listener para imprimir
document.addEventListener('livewire:init', () => {
    Livewire.on('abrir-imprimir', (data) => {
        const url = `/vendedor/imprimir-pedido/${data[0].pedidoId}`;
        window.open(url, '_blank', 'width=800,height=600');
    });
});
</script>
@endpush