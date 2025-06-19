<div class="space-y-6">
    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-primary font-semibold text-gray-800 flex items-center">
                <i class="fas fa-filter text-orange-500 mr-2"></i>
                Filtros de Búsqueda
            </h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div>
                <label for="fechaDesde" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-calendar-alt mr-1"></i>Fecha Desde
                </label>
                <input type="date" 
                       wire:model="fechaDesde" 
                       id="fechaDesde"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Fecha Hasta -->
            <div>
                <label for="fechaHasta" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-calendar-alt mr-1"></i>Fecha Hasta
                </label>
                <input type="date" 
                       wire:model="fechaHasta" 
                       id="fechaHasta"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Búsqueda Producto -->
            <div>
                <label for="buscarProducto" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-search mr-1"></i>Buscar Producto
                </label>
                <input type="text" 
                       wire:model.live.debounce.300ms="buscarProducto" 
                       id="buscarProducto"
                       placeholder="Código o nombre del producto..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Botones -->
            <div class="flex items-end space-x-2">
                <button wire:click="aplicarFiltros" 
                        class="btn-primary">
                    <i class="fas fa-filter mr-1"></i>Aplicar
                </button>
                <button wire:click="limpiarFiltros" 
                        class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition duration-200">
                    <i class="fas fa-eraser mr-1"></i>Limpiar
                </button>
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
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-table mr-2"></i>Ranking de Articulos Vendidos
                <span class="text-sm text-gray-500 ml-2">
                    ({{ $paginationInfo->from }} - {{ $paginationInfo->to }} de {{ $paginationInfo->total }})
                </span>
            </h2>
        </div>

        <div class="overflow-x-auto">
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

        <!-- Paginación -->
        @if($paginationInfo->last_page > 1)
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Mostrando {{ $paginationInfo->from }} a {{ $paginationInfo->to }} de {{ $paginationInfo->total }} productos
                    </div>
                    <div class="flex space-x-1">
                        <!-- Página anterior -->
                        @if($paginationInfo->current_page > 1)
                            <button wire:click="previousPage" 
                                    class="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Anterior
                            </button>
                        @endif

                        <!-- Números de página -->
                        @for($i = max(1, $paginationInfo->current_page - 2); $i <= min($paginationInfo->last_page, $paginationInfo->current_page + 2); $i++)
                            <button wire:click="gotoPage({{ $i }})" 
                                    class="px-3 py-1 text-sm rounded-md 
                                           {{ $i == $paginationInfo->current_page 
                                              ? 'bg-blue-600 text-white' 
                                              : 'bg-white border border-gray-300 hover:bg-gray-50' }}">
                                {{ $i }}
                            </button>
                        @endfor

                        <!-- Página siguiente -->
                        @if($paginationInfo->current_page < $paginationInfo->last_page)
                            <button wire:click="nextPage" 
                                    class="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Siguiente
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>