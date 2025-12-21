<div class="space-y-6" x-data="{ filtrosAbiertos: window.innerWidth >= 640 }">
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

                <!-- Origen de Venta -->
                <div>
                    <label for="origenFiltro" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">
                        <i class="fas fa-store mr-1"></i>Origen de Venta
                    </label>
                    <select wire:model="origenFiltro"
                            id="origenFiltro"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="SALON">Salón</option>
                        <option value="MOSTRADOR">Mostrador</option>
                        <option value="DELIVERY">Delivery</option>
                        <option value="POS">P.O.S.</option>
                        <option value="OTRAS">Otras</option>
                    </select>
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

        <!-- Tarjetas de Totales por Origen -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mt-4 pt-4 border-t border-gray-200">
            <!-- Salón -->
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg mr-3">
                        <i class="fas fa-utensils text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Salón</p>
                        <p class="text-xl font-semibold text-blue-600">${{ number_format($totalesPorOrigen['SALON'], 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Mostrador -->
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg mr-3">
                        <i class="fas fa-store text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Mostrador</p>
                        <p class="text-xl font-semibold text-green-600">${{ number_format($totalesPorOrigen['MOSTRADOR'], 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Delivery -->
            <div class="bg-orange-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="p-2 bg-orange-100 rounded-lg mr-3">
                        <i class="fas fa-motorcycle text-orange-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Delivery</p>
                        <p class="text-xl font-semibold text-orange-600">${{ number_format($totalesPorOrigen['DELIVERY'], 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- P.O.S. -->
            <div class="bg-purple-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg mr-3">
                        <i class="fas fa-credit-card text-purple-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">P.O.S.</p>
                        <p class="text-xl font-semibold text-purple-600">${{ number_format($totalesPorOrigen['POS'], 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Otras -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="p-2 bg-gray-100 rounded-lg mr-3">
                        <i class="fas fa-ellipsis-h text-gray-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Otras</p>
                        <p class="text-xl font-semibold text-gray-600">${{ number_format($totalesPorOrigen['OTRAS'], 2) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Ventas -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800">
                <i class="fas fa-table mr-2"></i>Detalle de Ventas
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
                            Fecha/Hora
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Comprobante
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Origen
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Usuario
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Importe
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Detalle
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($ventas as $venta)
                        <!-- Fila principal -->
                        <tr class="hover:bg-gray-50 transition duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $venta->fecha_formateada }}</div>
                                <div class="text-xs text-gray-500">{{ $venta->hora_formateada }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                       {{ $venta->ticomp == 'NC' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $venta->comprobante }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $venta->nombre }}">
                                    {{ $venta->nombre }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $origenConfig = [
                                        'SALON' => ['icon' => 'fa-utensils', 'color' => 'blue', 'label' => 'Salón'],
                                        'MOSTRADOR' => ['icon' => 'fa-store', 'color' => 'green', 'label' => 'Mostrador'],
                                        'DELIVERY' => ['icon' => 'fa-motorcycle', 'color' => 'orange', 'label' => 'Delivery'],
                                        'POS' => ['icon' => 'fa-credit-card', 'color' => 'purple', 'label' => 'P.O.S.'],
                                        'OTRAS' => ['icon' => 'fa-ellipsis-h', 'color' => 'gray', 'label' => 'Otras'],
                                    ];
                                    $config = $origenConfig[$venta->origen] ?? $origenConfig['OTRAS'];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $config['color'] }}-100 text-{{ $config['color'] }}-800">
                                    <i class="fas {{ $config['icon'] }} mr-1"></i>
                                    {{ $config['label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900">{{ $venta->usuario }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-sm font-medium {{ $venta->ticomp == 'NC' ? 'text-red-600' : 'text-gray-900' }}">
                                    ${{ number_format($venta->importe_calculado, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button wire:click="toggleDetalle('{{ $venta->nrofac }}')"
                                        class="text-blue-600 hover:text-blue-900">
                                    <i class="fas {{ $ventaExpandida === $venta->nrofac ? 'fa-chevron-up' : 'fa-chevron-down' }}"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Fila de detalle expandible -->
                        @if($ventaExpandida === $venta->nrofac)
                            <tr>
                                <td colspan="7" class="px-6 py-4 bg-gray-50">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">P. Unitario</th>
                                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Importe</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach($this->getDetalleVenta($venta->nrofac) as $detalle)
                                                    <tr>
                                                        <td class="px-4 py-2 text-sm text-gray-900">{{ $detalle->codart }}</td>
                                                        <td class="px-4 py-2 text-sm text-gray-900">{{ $detalle->detart }}</td>
                                                        <td class="px-4 py-2 text-sm text-gray-900 text-right">{{ number_format($detalle->cantidad, 2) }}</td>
                                                        <td class="px-4 py-2 text-sm text-gray-900 text-right">${{ number_format($detalle->punit, 2) }}</td>
                                                        <td class="px-4 py-2 text-sm font-medium text-gray-900 text-right">${{ number_format($detalle->importe, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @if($venta->observa)
                                        <div class="mt-3 p-3 bg-blue-50 rounded-md">
                                            <p class="text-xs font-medium text-gray-700">Observaciones:</p>
                                            <p class="text-sm text-gray-900">{{ $venta->observa }}</p>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
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
        <div class="lg:hidden divide-y divide-gray-200">
            @forelse($ventas as $venta)
                <div class="p-4">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                       {{ $venta->ticomp == 'NC' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $venta->comprobante }}
                                </span>
                                @php
                                    $origenConfig = [
                                        'SALON' => ['icon' => 'fa-utensils', 'color' => 'blue', 'label' => 'Salón'],
                                        'MOSTRADOR' => ['icon' => 'fa-store', 'color' => 'green', 'label' => 'Mostrador'],
                                        'DELIVERY' => ['icon' => 'fa-motorcycle', 'color' => 'orange', 'label' => 'Delivery'],
                                        'POS' => ['icon' => 'fa-credit-card', 'color' => 'purple', 'label' => 'P.O.S.'],
                                        'OTRAS' => ['icon' => 'fa-ellipsis-h', 'color' => 'gray', 'label' => 'Otras'],
                                    ];
                                    $config = $origenConfig[$venta->origen] ?? $origenConfig['OTRAS'];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $config['color'] }}-100 text-{{ $config['color'] }}-800">
                                    <i class="fas {{ $config['icon'] }} mr-1"></i>
                                    {{ $config['label'] }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-900 font-medium truncate">{{ $venta->nombre }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ $venta->fecha_formateada }} - {{ $venta->hora_formateada }}</div>
                            <div class="text-xs text-gray-500">Usuario: {{ $venta->usuario }}</div>
                        </div>
                        <div class="text-right ml-2">
                            <div class="text-base font-semibold {{ $venta->ticomp == 'NC' ? 'text-red-600' : 'text-gray-900' }}">
                                ${{ number_format($venta->importe_calculado, 2) }}
                            </div>
                        </div>
                    </div>

                    <!-- Botón para expandir detalle -->
                    <button wire:click="toggleDetalle('{{ $venta->nrofac }}')"
                            class="w-full mt-2 px-3 py-2 text-sm bg-blue-50 text-blue-700 rounded-md hover:bg-blue-100 transition-colors">
                        <i class="fas {{ $ventaExpandida === $venta->nrofac ? 'fa-chevron-up' : 'fa-chevron-down' }} mr-1"></i>
                        {{ $ventaExpandida === $venta->nrofac ? 'Ocultar detalle' : 'Ver detalle' }}
                    </button>

                    <!-- Detalle expandible -->
                    @if($ventaExpandida === $venta->nrofac)
                        <div class="mt-3 p-3 bg-gray-50 rounded-md">
                            <div class="space-y-2">
                                @foreach($this->getDetalleVenta($venta->nrofac) as $detalle)
                                    <div class="border-b border-gray-200 pb-2 last:border-b-0">
                                        <div class="text-sm font-medium text-gray-900">{{ $detalle->detart }}</div>
                                        <div class="text-xs text-gray-600">Código: {{ $detalle->codart }}</div>
                                        <div class="flex justify-between mt-1">
                                            <span class="text-xs text-gray-600">Cant: {{ number_format($detalle->cantidad, 2) }} × ${{ number_format($detalle->punit, 2) }}</span>
                                            <span class="text-sm font-medium text-gray-900">${{ number_format($detalle->importe, 2) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if($venta->observa)
                                <div class="mt-3 p-2 bg-blue-50 rounded-md">
                                    <p class="text-xs font-medium text-gray-700">Observaciones:</p>
                                    <p class="text-xs text-gray-900">{{ $venta->observa }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
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
