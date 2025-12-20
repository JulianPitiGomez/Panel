<div class="space-y-6" x-data="{ filtrosAbiertos: false }">
    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-6">
        <!-- Header de filtros con botón collapse para móvil -->
        <div class="flex items-center justify-between mb-4 sm:hidden">
            <h3 class="text-sm font-semibold text-gray-800">
                <i class="fas fa-filter text-orange-500 mr-2"></i>Filtros
            </h3>
            <button @click="filtrosAbiertos = !filtrosAbiertos"
                    class="px-3 py-1 text-xs bg-orange-500 text-white rounded-md hover:bg-orange-600 transition-colors">
                <i class="fas" :class="filtrosAbiertos ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                <span x-text="filtrosAbiertos ? 'Ocultar' : 'Mostrar'"></span>
            </button>
        </div>

        <!-- Contenido de filtros -->
        <div x-show="filtrosAbiertos" x-collapse class="sm:!block">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div>
                    <label for="fechaDesde" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">
                        <i class="fas fa-calendar-alt mr-1"></i>Fecha Desde
                    </label>
                    <input type="date"
                           wire:model="fechaDesde"
                           id="fechaDesde"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Fecha Hasta -->
                <div>
                    <label for="fechaHasta" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">
                        <i class="fas fa-calendar-alt mr-1"></i>Fecha Hasta
                    </label>
                    <input type="date"
                           wire:model="fechaHasta"
                           id="fechaHasta"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Búsqueda Producto -->
                <div>
                    <label for="buscarProducto" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">
                        <i class="fas fa-search mr-1"></i>Buscar Producto
                    </label>
                    <input type="text"
                           wire:model.live.debounce.300ms="buscarProducto"
                           id="buscarProducto"
                           placeholder="Código o nombre..."
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Botones -->
                <div class="flex items-end gap-2">
                    <button wire:click="aplicarFiltros"
                            class="btn-primary flex-1 sm:flex-none text-sm">
                        <i class="fas fa-filter mr-1"></i><span class="hidden sm:inline">Aplicar</span>
                    </button>
                    <button wire:click="limpiarFiltros"
                            class="px-3 sm:px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition duration-200 flex-1 sm:flex-none text-sm">
                        <i class="fas fa-eraser mr-1"></i><span class="hidden sm:inline">Limpiar</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Totales -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 pt-4 border-t border-gray-200">
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg mr-3">
                        <i class="fas fa-cubes text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Total Cantidad</p>
                        <p class="text-xl font-semibold text-blue-600">{{ number_format($totalCantidad) }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg mr-3">
                        <i class="fas fa-dollar-sign text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Total Importe</p>
                        <p class="text-xl font-semibold text-green-600">${{ number_format($totalImporte, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg mr-3">
                        <i class="fas fa-list text-purple-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Articulos Únicos</p>
                        <p class="text-xl font-semibold text-purple-600">{{ number_format($cantidadProductos) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Artículos -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800">
                <i class="fas fa-table mr-2"></i>Ranking de Articulos Vendidos
                <span class="text-xs sm:text-sm text-gray-500 ml-2">
                    ({{ $paginationInfo->from }} - {{ $paginationInfo->to }} de {{ $paginationInfo->total }})
                </span>
            </h2>
        </div>

        <!-- Vista Desktop (tabla) -->
        <div class="hidden lg:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Posición
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Código
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Producto
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cantidad
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Precio Prom.
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total Importe
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Facturas
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($articulos as $index => $articulo)
                        <tr class="hover:bg-gray-50 transition duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @php
                                        $posicion = ($paginationInfo->current_page - 1) * $paginationInfo->per_page + $index + 1;
                                        $badgeColor = '';
                                        if($posicion <= 3) {
                                            $badgeColor = $posicion == 1 ? 'bg-yellow-100 text-yellow-800' :
                                                         ($posicion == 2 ? 'bg-gray-100 text-gray-800' : 'bg-orange-100 text-orange-800');
                                        } else {
                                            $badgeColor = 'bg-blue-100 text-blue-800';
                                        }
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }}">
                                        @if($posicion <= 3)
                                            <i class="fas fa-medal mr-1"></i>
                                        @endif
                                        #{{ $posicion }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900">{{ $articulo->codart }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs" title="{{ $articulo->nombre_producto }}">
                                    {{ strlen($articulo->nombre_producto) > 50 ? substr($articulo->nombre_producto, 0, 50) . '...' : $articulo->nombre_producto }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="text-sm font-semibold text-blue-600">
                                    {{ number_format($articulo->cantidad_vendida) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-sm text-gray-900">
                                    ${{ number_format($articulo->precio_promedio, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-sm font-semibold text-green-600">
                                    ${{ number_format($articulo->total_importe, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $articulo->cantidad_facturas }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <i class="fas fa-search text-4xl mb-4"></i>
                                    <p class="text-lg">No se encontraron productos</p>
                                    <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Vista Móvil/Tablet (tarjetas) -->
        <div class="lg:hidden divide-y divide-gray-200">
            @forelse($articulos as $index => $articulo)
                @php
                    $posicion = ($paginationInfo->current_page - 1) * $paginationInfo->per_page + $index + 1;
                    $badgeColor = '';
                    if($posicion <= 3) {
                        $badgeColor = $posicion == 1 ? 'bg-yellow-100 text-yellow-800' :
                                     ($posicion == 2 ? 'bg-gray-100 text-gray-800' : 'bg-orange-100 text-orange-800');
                    } else {
                        $badgeColor = 'bg-blue-100 text-blue-800';
                    }
                @endphp
                <div class="p-4 hover:bg-gray-50 transition duration-200">
                    <div class="flex items-start gap-3 mb-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }} flex-shrink-0">
                            @if($posicion <= 3)
                                <i class="fas fa-medal mr-1"></i>
                            @endif
                            #{{ $posicion }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold text-gray-900 mb-1 truncate" title="{{ $articulo->nombre_producto }}">
                                {{ $articulo->nombre_producto }}
                            </div>
                            <div class="text-xs text-gray-500">Código: {{ $articulo->codart }}</div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <div class="text-xs text-gray-500">Cantidad</div>
                            <div class="font-semibold text-blue-600">{{ number_format($articulo->cantidad_vendida) }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Precio Prom.</div>
                            <div class="font-medium text-gray-900">${{ number_format($articulo->precio_promedio, 2) }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Total</div>
                            <div class="font-semibold text-green-600">${{ number_format($articulo->total_importe, 2) }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Facturas</div>
                            <div class="font-medium text-gray-900">{{ $articulo->cantidad_facturas }}</div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-4 py-12 text-center text-gray-500">
                    <i class="fas fa-search text-4xl mb-4"></i>
                    <p class="text-base">No se encontraron productos</p>
                    <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                </div>
            @endforelse
        </div>

        <!-- Paginación -->
        @if($paginationInfo->last_page > 1)
            <div class="px-4 sm:px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs sm:text-sm text-gray-700 text-center sm:text-left">
                        Mostrando {{ $paginationInfo->from }} a {{ $paginationInfo->to }} de {{ $paginationInfo->total }} productos
                    </div>
                    <div class="flex items-center gap-1">
                        <!-- Página anterior -->
                        @if($paginationInfo->current_page > 1)
                            <button wire:click="previousPage"
                                    class="px-3 py-1 text-xs sm:text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Anterior
                            </button>
                        @endif

                        <!-- Números de página (solo en desktop) -->
                        <div class="hidden sm:flex gap-1">
                            @for($i = max(1, $paginationInfo->current_page - 2); $i <= min($paginationInfo->last_page, $paginationInfo->current_page + 2); $i++)
                                <button wire:click="gotoPage({{ $i }})"
                                        class="px-3 py-1 text-sm rounded-md
                                               {{ $i == $paginationInfo->current_page
                                                  ? 'bg-blue-600 text-white'
                                                  : 'bg-white border border-gray-300 hover:bg-gray-50' }}">
                                    {{ $i }}
                                </button>
                            @endfor
                        </div>

                        <!-- Indicador de página actual (solo en móvil) -->
                        <div class="sm:hidden px-3 py-1 text-xs bg-blue-600 text-white rounded-md">
                            {{ $paginationInfo->current_page }} / {{ $paginationInfo->last_page }}
                        </div>

                        <!-- Página siguiente -->
                        @if($paginationInfo->current_page < $paginationInfo->last_page)
                            <button wire:click="nextPage"
                                    class="px-3 py-1 text-xs sm:text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Siguiente
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>