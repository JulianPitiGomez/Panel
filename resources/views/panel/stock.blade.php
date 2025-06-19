<div class="space-y-6">
    
    <!-- Filtros principales -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-primary font-semibold text-gray-800 flex items-center">
                <i class="fas fa-boxes text-orange-500 mr-2"></i>
                Filtros de Stock
            </h2>
            <button wire:click="limpiarFiltros" 
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors duration-200 text-sm font-secondary">
                <i class="fas fa-broom mr-1"></i>
                Limpiar Filtros
            </button>
        </div>

        <!-- Búsqueda por texto -->
        <div class="mb-4">
            <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Buscar por Código o Nombre</label>
            <input type="text" 
                   wire:model.live.debounce.300ms="busqueda"
                   placeholder="Ingresa código o nombre del artículo..."
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
        </div>

        <!-- Filtros por categoría -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <!-- Marca -->
            <div>
                <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Marca</label>
                <select wire:model.live="marcaSeleccionada" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
                    <option value="">Todas las marcas</option>
                    @foreach($marcas as $marca)
                        <option value="{{ $marca->codigo }}">{{ $marca->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Proveedor -->
            <div>
                <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Proveedor</label>
                <select wire:model.live="proveedorSeleccionado" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
                    <option value="">Todos los proveedores</option>
                    @foreach($proveedores as $proveedor)
                        <option value="{{ $proveedor->codigo }}">{{ $proveedor->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Rubro -->
            <div>
                <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Rubro</label>
                <select wire:model.live="rubroSeleccionado" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
                    <option value="">Todos los rubros</option>
                    @foreach($rubros as $rubro)
                        <option value="{{ $rubro->codigo }}">{{ $rubro->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Departamento -->
            <div>
                <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Departamento</label>
                <select wire:model.live="departamentoSeleccionado" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
                    <option value="">Todos los departamentos</option>
                    @foreach($departamentos as $departamento)
                        <option value="{{ $departamento->codigo }}">{{ $departamento->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Filtro por tipo de stock -->
        <div>
            <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Tipo de Stock</label>
            <select wire:model.live="tipoStock" 
                    class="w-full md:w-64 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
                <option value="">Todos los stocks</option>
                <option value="positivo">Stock Positivo (mayor a 0)</option>
                <option value="negativo">Stock Negativo (0 o menor)</option>
                <option value="alerta">Stock en Alerta (menor al mínimo)</option>
            </select>
        </div>
    </div>

    <!-- Resumen totales -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-primary font-semibold text-gray-800">Artículos</h3>
                    <p class="text-sm font-secondary text-gray-600">Total filtrado</p>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-primary font-bold text-blue-600">{{ number_format($cantidadArticulos) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-xl p-6 border border-green-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-primary font-semibold text-gray-800">Valor Stock (Costo)</h3>
                    <p class="text-sm font-secondary text-gray-600">Total a precio de costo</p>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-primary font-bold text-green-600">${{ number_format($totalStockCosto, 2) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-orange-50 to-orange-100 rounded-xl p-6 border border-orange-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-primary font-semibold text-gray-800">Valor Stock (Venta)</h3>
                    <p class="text-sm font-secondary text-gray-600">Total a precio de venta</p>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-primary font-bold text-orange-600">${{ number_format($totalStockVenta, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de artículos -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h3 class="text-lg font-primary font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-warehouse text-orange-500 mr-2"></i>
                    Stock de Artículos
                </h3>
                
                <!-- Controles de paginación superior -->
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-secondary text-gray-600">Mostrar:</label>
                        <select wire:change="cambiarTamanoPagina($event.target.value)" 
                                class="px-2 py-1 border border-gray-300 rounded text-sm font-secondary focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            <option value="25" {{ $paginacionSize == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ $paginacionSize == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $paginacionSize == 100 ? 'selected' : '' }}>100</option>
                            <option value="200" {{ $paginacionSize == 200 ? 'selected' : '' }}>200</option>
                        </select>
                    </div>
                    
                    @if($articulos->hasPages())
                    <div class="text-sm font-secondary text-gray-600">
                        Mostrando {{ $articulos->firstItem() }} - {{ $articulos->lastItem() }} de {{ $articulos->total() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>

        @if($articulos->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <button wire:click="ordenarPor('codigo')" class="flex items-center text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider hover:text-orange-600">
                                Código
                                @if($ordenarPor === 'codigo')
                                    <i class="fas fa-sort-{{ $direccionOrden === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @else
                                    <i class="fas fa-sort ml-1"></i>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left">
                            <button wire:click="ordenarPor('nombre')" class="flex items-center text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider hover:text-orange-600">
                                Nombre
                                @if($ordenarPor === 'nombre')
                                    <i class="fas fa-sort-{{ $direccionOrden === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @else
                                    <i class="fas fa-sort ml-1"></i>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider">Stock Actual</th>
                        <th class="px-6 py-3 text-center text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider">Stock Mínimo</th>
                        <th class="px-6 py-3 text-right text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider">Precio Costo</th>
                        <th class="px-6 py-3 text-right text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider">Precio Venta</th>
                        <th class="px-6 py-3 text-left text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider">Marca</th>
                        <th class="px-6 py-3 text-left text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider">Proveedor</th>
                        <th class="px-6 py-3 text-center text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($articulos as $articulo)
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-secondary font-medium text-gray-900">
                                {{ $articulo->codigo }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-secondary font-medium text-gray-900">
                                {{ $articulo->nombre }}
                            </div>
                            @if($articulo->rubro_nombre || $articulo->departamento_nombre)
                            <div class="text-xs font-secondary text-gray-500">
                                @if($articulo->rubro_nombre)
                                    <span class="inline-block bg-gray-100 px-2 py-1 rounded mr-1">{{ $articulo->rubro_nombre }}</span>
                                @endif
                                @if($articulo->departamento_nombre)
                                    <span class="inline-block bg-blue-100 px-2 py-1 rounded">{{ $articulo->departamento_nombre }}</span>
                                @endif
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="text-sm font-secondary {{ $this->getClaseStock($articulo->stockact, $articulo->stockmin) }}">
                                {{ number_format($articulo->stockact, 2) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="text-sm font-secondary text-gray-600">
                                {{ number_format($articulo->stockmin, 2) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-secondary text-gray-900">
                                ${{ number_format($articulo->preciocos, 2) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-secondary text-gray-900">
                                ${{ number_format($articulo->precioven, 2) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-secondary text-gray-900">
                                {{ $articulo->marca_nombre ?? '-' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-secondary text-gray-900">
                                {{ $articulo->proveedor_nombre ?? '-' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($articulo->stockotro)
                                <button wire:click="verStockRelacionado('{{ $articulo->codigo }}')" 
                                        class="text-orange-600 hover:text-orange-900 font-secondary text-sm">
                                    <i class="fas fa-link mr-1"></i>
                                    Ver Relacionados
                                </button>
                            @else
                                <span class="text-gray-400 text-sm font-secondary">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        @if($articulos->hasPages())
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="text-sm font-secondary text-gray-600">
                    Mostrando {{ $articulos->firstItem() }} - {{ $articulos->lastItem() }} de {{ $articulos->total() }} registros
                </div>
                
                <nav class="flex items-center space-x-1" aria-label="Paginación">
                    {{-- Botón Primera Página --}}
                    @if($articulos->currentPage() > 3)
                        <button wire:click="gotoPage(1)" 
                                class="px-3 py-2 text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                            <i class="fas fa-angle-double-left"></i>
                        </button>
                    @endif

                    {{-- Botón Anterior --}}
                    @if($articulos->onFirstPage())
                        <span class="px-3 py-2 text-sm font-secondary text-gray-400 cursor-not-allowed">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    @else
                        <button wire:click="previousPage" 
                                class="px-3 py-2 text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                    @endif

                    {{-- Números de Página --}}
                    @php
                        $start = max(1, $articulos->currentPage() - 2);
                        $end = min($articulos->lastPage(), $articulos->currentPage() + 2);
                    @endphp

                    @if($start > 1)
                        <button wire:click="gotoPage(1)" 
                                class="px-3 py-2 text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                            1
                        </button>
                        @if($start > 2)
                            <span class="px-2 py-2 text-sm font-secondary text-gray-400">...</span>
                        @endif
                    @endif

                    @for($page = $start; $page <= $end; $page++)
                        @if($page == $articulos->currentPage())
                            <span class="px-3 py-2 text-sm font-secondary bg-orange-500 text-white rounded-lg">
                                {{ $page }}
                            </span>
                        @else
                            <button wire:click="gotoPage({{ $page }})" 
                                    class="px-3 py-2 text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                                {{ $page }}
                            </button>
                        @endif
                    @endfor

                    @if($end < $articulos->lastPage())
                        @if($end < $articulos->lastPage() - 1)
                            <span class="px-2 py-2 text-sm font-secondary text-gray-400">...</span>
                        @endif
                        <button wire:click="gotoPage({{ $articulos->lastPage() }})" 
                                class="px-3 py-2 text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                            {{ $articulos->lastPage() }}
                        </button>
                    @endif

                    {{-- Botón Siguiente --}}
                    @if($articulos->hasMorePages())
                        <button wire:click="nextPage" 
                                class="px-3 py-2 text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    @else
                        <span class="px-3 py-2 text-sm font-secondary text-gray-400 cursor-not-allowed">
                            <i class="fas fa-chevron-right"></i>
                        </span>
                    @endif

                    {{-- Botón Última Página --}}
                    @if($articulos->currentPage() < $articulos->lastPage() - 2)
                        <button wire:click="gotoPage({{ $articulos->lastPage() }})" 
                                class="px-3 py-2 text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                            <i class="fas fa-angle-double-right"></i>
                        </button>
                    @endif
                </nav>
            </div>
        </div>
        @endif

        @else
        <div class="text-center py-12">
            <i class="fas fa-boxes text-gray-400 text-5xl mb-4"></i>
            <h3 class="text-lg font-primary font-medium text-gray-900 mb-2">No hay artículos</h3>
            <p class="text-gray-600 font-secondary">No se encontraron artículos con los filtros seleccionados</p>
        </div>
        @endif
    </div>
    <!-- Modal de Stock Relacionado -->
    @if($verModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 border-t-4 border-orange-500">
        <!-- Overlay -->        
        <div class="fixed inset-0 bg-gray-500 bg-opacity-50 backdrop-blur-sm" wire:click="cerrarModal"></div>
        <!-- Modal Content -->
        <div class="relative bg-white rounded-xl shadow-2xl border border-gray-200 max-w-4xl w-full max-h-[90vh] overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b">
                <h3 class="text-lg font-primary font-semibold text-gray-900">
                    Stock Relacionado - {{ $articuloSeleccionado->nombre ?? '' }}
                </h3>
                <button wire:click="cerrarModal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Content -->
            <div class="p-6 overflow-y-auto max-h-[70vh]">
                @if($articulosRelacionados && count($articulosRelacionados) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-secondary font-medium text-gray-500 uppercase">Código</th>
                                <th class="px-4 py-3 text-left text-xs font-secondary font-medium text-gray-500 uppercase">Nombre</th>
                                <th class="px-4 py-3 text-center text-xs font-secondary font-medium text-gray-500 uppercase">Stock Actual</th>
                                <th class="px-4 py-3 text-right text-xs font-secondary font-medium text-gray-500 uppercase">Precio Costo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($articulosRelacionados as $relacionado)
                            <tr>
                                <td class="px-4 py-3 font-secondary">{{ $relacionado->codigo }}</td>
                                <td class="px-4 py-3 font-secondary">{{ $relacionado->nombre }}</td>
                                <td class="px-4 py-3 text-center font-secondary {{ $this->getClaseStock($relacionado->stockact, $relacionado->stockmin) }}">
                                    {{ number_format($relacionado->stockact, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right font-secondary">${{ number_format($relacionado->preciocos, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8">
                    <p class="text-gray-600 font-secondary">No hay artículos relacionados</p>
                </div>
                @endif
            </div>
            
            <!-- Footer -->
            <div class="flex justify-end p-6 border-t bg-gray-50">
                <button wire:click="cerrarModal" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 font-secondary">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
    @endif
</div>