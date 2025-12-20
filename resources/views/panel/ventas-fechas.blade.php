<div class="space-y-6">


    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <!-- Fecha Desde -->
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

            <!-- Búsqueda -->
            <div>
                <label for="buscar" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">
                    <i class="fas fa-search mr-1"></i>Buscar
                </label>
                <input type="text"
                       wire:model.live.debounce.300ms="buscar"
                       id="buscar"
                       placeholder="Nº Factura o Cliente..."
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

        <!-- Totales -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 pt-4 border-t border-gray-200">
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg mr-3">
                        <i class="fas fa-dollar-sign text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Total Ventas</p>
                        <p class="text-xl font-semibold text-green-600">${{ number_format($totalVentas, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg mr-3">
                        <i class="fas fa-file-invoice text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Cantidad Facturas</p>
                        <p class="text-xl font-semibold text-blue-600">{{ number_format($cantidadFacturas) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Ventas -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800">
                <i class="fas fa-table mr-2"></i>Facturas de Venta
                <span class="text-xs sm:text-sm text-gray-500 ml-2">
                    ({{ $paginationInfo->from }} - {{ $paginationInfo->to }} de {{ $paginationInfo->total }})
                </span>
            </h2>
        </div>

        <!-- Vista Desktop (tabla) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Fecha
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nº Factura
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tipo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Importe
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($ventas as $venta)
                        <tr class="hover:bg-gray-50 transition duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $venta->fecha_formateada }}</div>
                                <div class="text-xs text-gray-500">{{ $venta->hora }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                       {{ $venta->ticomp == 'NC' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $venta->letra.$venta->numcomp }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                       {{ $venta->ticomp == 'NC' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                    <i class="fas {{ $venta->ticomp == 'NC' ? 'fa-minus-circle' : 'fa-plus-circle' }} mr-1"></i>
                                    {{ $venta->ticomp == 'NC' ? 'Nota Crédito' : 'Factura' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $venta->cliente }}">
                                    {{ $venta->cliente }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-sm font-medium {{ $venta->ticomp == 'NC' ? 'text-red-600' : 'text-gray-900' }}">
                                    ${{ number_format($venta->importe, 2) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <i class="fas fa-search text-4xl mb-4"></i>
                                    <p class="text-lg">No se encontraron ventas</p>
                                    <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Vista Móvil (tarjetas) -->
        <div class="md:hidden divide-y divide-gray-200">
            @forelse($ventas as $venta)
                <div class="p-4 hover:bg-gray-50 transition duration-200">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                       {{ $venta->ticomp == 'NC' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $venta->letra.$venta->numcomp }}
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                       {{ $venta->ticomp == 'NC' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                    <i class="fas {{ $venta->ticomp == 'NC' ? 'fa-minus-circle' : 'fa-plus-circle' }} mr-1"></i>
                                    {{ $venta->ticomp == 'NC' ? 'NC' : 'FC' }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-900 font-medium truncate">{{ $venta->cliente }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ $venta->fecha_formateada }} - {{ $venta->hora }}</div>
                        </div>
                        <div class="text-right ml-2">
                            <div class="text-base font-semibold {{ $venta->ticomp == 'NC' ? 'text-red-600' : 'text-gray-900' }}">
                                ${{ number_format($venta->importe, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-4 py-12 text-center text-gray-500">
                    <i class="fas fa-search text-4xl mb-4"></i>
                    <p class="text-base">No se encontraron ventas</p>
                    <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                </div>
            @endforelse
        </div>

        <!-- Paginación -->
        @if($paginationInfo->last_page > 1)
            <div class="px-4 sm:px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs sm:text-sm text-gray-700 text-center sm:text-left">
                        Mostrando {{ $paginationInfo->from }} a {{ $paginationInfo->to }} de {{ $paginationInfo->total }} resultados
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