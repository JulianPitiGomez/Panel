<div class="space-y-6" x-data="{ filtrosAbiertos: window.innerWidth >= 640 }">
    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-6">
        <!-- Header de filtros con botón collapse para móvil -->
        <div class="flex items-center justify-between mb-4 sm:hidden">
            <h3 class="text-sm font-semibold text-gray-800">
                <i class="fas fa-filter text-[#FFAF22] mr-2"></i>Filtros
            </h3>
            <button @click="filtrosAbiertos = !filtrosAbiertos"
                    class="px-3 py-1 text-xs bg-[#FFAF22] text-white rounded-md hover:bg-[#FFAF22]/90 transition-colors">
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
                           wire:model.live.debounce.500ms="fechaDesde"
                           id="fechaDesde"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
                </div>

                <!-- Fecha Hasta -->
                <div>
                    <label for="fechaHasta" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">
                        <i class="fas fa-calendar-alt mr-1"></i>Fecha Hasta
                    </label>
                    <input type="date"
                           wire:model.live.debounce.500ms="fechaHasta"
                           id="fechaHasta"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
                </div>

                <!-- Origen de Venta -->
                <div>
                    <label for="origenFiltro" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">
                        <i class="fas fa-store mr-1"></i>Origen de Venta
                    </label>
                    <select wire:model.live="origenFiltro"
                            id="origenFiltro"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
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
                            class="px-3 sm:px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 flex-1 sm:flex-none text-sm cursor-pointer font-secondary">
                        <i class="fas fa-eraser mr-1"></i><span class="hidden sm:inline">Limpiar</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Total General -->
        <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="bg-gradient-to-r from-[#FFAF22] to-[#FFAF22]/90 rounded-lg p-4 sm:p-6 shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="p-3 bg-white/20 rounded-lg mr-4">
                            <i class="fas fa-dollar-sign text-white text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-white/90 text-sm sm:text-base font-medium">Total Ventas</p>
                            <p class="text-white text-2xl sm:text-3xl font-bold">${{ number_format($totalGeneral, 2) }}</p>
                        </div>
                    </div>
                    <div class="hidden sm:block">
                        <div class="text-right">
                            <p class="text-white/90 text-xs">Período</p>
                            <p class="text-white text-sm font-medium">{{ date('d/m/Y', strtotime($fechaDesde)) }} - {{ date('d/m/Y', strtotime($fechaHasta)) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjetas de Totales por Origen -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mt-4">
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
            <div class="bg-[#FFAF22]/10 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="p-2 bg-[#FFAF22]/15 rounded-lg mr-3">
                        <i class="fas fa-motorcycle text-[#FFAF22]"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Delivery</p>
                        <p class="text-xl font-semibold text-[#FFAF22]">${{ number_format($totalesPorOrigen['DELIVERY'], 2) }}</p>
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

        <!-- Tarjetas de Formas de Facturación -->
        <div class="mt-4 pt-4 border-t border-gray-200">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">
                <i class="fas fa-money-bill-wave text-green-600 mr-2"></i>Formas de Facturación
            </h4>
            @php
                // Clases literales para que Tailwind las compile
                $fpStyles = [
                    ['card' => 'bg-emerald-50', 'icon' => 'bg-emerald-100', 'text' => 'text-emerald-600'],
                    ['card' => 'bg-sky-50',     'icon' => 'bg-sky-100',     'text' => 'text-sky-600'],
                    ['card' => 'bg-violet-50',  'icon' => 'bg-violet-100',  'text' => 'text-violet-600'],
                    ['card' => 'bg-amber-50',   'icon' => 'bg-amber-100',   'text' => 'text-amber-600'],
                    ['card' => 'bg-rose-50',    'icon' => 'bg-rose-100',    'text' => 'text-rose-600'],
                    ['card' => 'bg-cyan-50',    'icon' => 'bg-cyan-100',    'text' => 'text-cyan-600'],
                    ['card' => 'bg-indigo-50',  'icon' => 'bg-indigo-100',  'text' => 'text-indigo-600'],
                    ['card' => 'bg-teal-50',    'icon' => 'bg-teal-100',    'text' => 'text-teal-600'],
                    ['card' => 'bg-fuchsia-50', 'icon' => 'bg-fuchsia-100', 'text' => 'text-fuchsia-600'],
                    ['card' => 'bg-lime-50',    'icon' => 'bg-lime-100',    'text' => 'text-lime-600'],
                ];
            @endphp
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @forelse($formasPago as $i => $fp)
                    @php $s = $fpStyles[$i % count($fpStyles)]; @endphp
                    <div class="{{ $s['card'] }} p-4 rounded-lg">
                        <div class="flex items-center">
                            <div class="p-2 {{ $s['icon'] }} rounded-lg mr-3 shrink-0">
                                <i class="fas fa-receipt {{ $s['text'] }}"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm text-gray-600 truncate" title="{{ $fp['forma'] }}">{{ $fp['forma'] }}</p>
                                <p class="text-lg font-semibold {{ $s['text'] }}">${{ number_format($fp['total'], 2) }}</p>
                                <p class="text-xs text-gray-400">{{ $fp['cantidad'] }} mov.</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center text-gray-400 text-sm py-4">
                        <i class="fas fa-info-circle mr-1"></i>Sin datos en el período
                    </div>
                @endforelse
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
                                       {{ $venta->TICOMP == 'NC' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $venta->comprobante }}
                                </span>
                                @if($venta->factura_aplicada)
                                    <div class="text-xs text-red-500 mt-1">
                                        <i class="fas fa-reply mr-1"></i>Aplica a: {{ $venta->factura_aplicada }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $venta->NOMBRE ?? 'Sin nombre' }}">
                                    {{ $venta->NOMBRE ?? 'Sin nombre' }}
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
                                <span class="text-sm text-gray-900">{{ $venta->USUARIO }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-sm font-medium {{ $venta->TICOMP == 'NC' ? 'text-red-600' : 'text-gray-900' }}">
                                    ${{ number_format($venta->importe_calculado, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button wire:click="toggleDetalle('{{ $venta->nrofac }}')"
                                        class="text-[#FFAF22] hover:text-[#FFAF22]/80 transition-colors duration-200">
                                    <i class="fas fa-eye text-lg"></i>
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
                                                        <td class="px-4 py-2 text-sm text-gray-900">{{ $detalle->CODART }}</td>
                                                        <td class="px-4 py-2 text-sm text-gray-900">{{ $detalle->DETART }}</td>
                                                        <td class="px-4 py-2 text-sm text-gray-900 text-right">{{ number_format($detalle->CANTIDAD, 2) }}</td>
                                                        <td class="px-4 py-2 text-sm text-gray-900 text-right">${{ number_format($detalle->PUNIT, 2) }}</td>
                                                        <td class="px-4 py-2 text-sm font-medium text-gray-900 text-right">${{ number_format($detalle->IMPORTE, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    {{-- Formas de Pago --}}
                                    @php
                                        $formasPago = $this->getFormasPago($venta->TICOMP, $venta->LETRA, $venta->NUMCOMP);
                                    @endphp
                                    @if($formasPago->isNotEmpty())
                                        <div class="mt-3 p-3 bg-green-50 rounded-md">
                                            <p class="text-xs font-medium text-gray-700 mb-2"><i class="fas fa-money-bill-wave mr-1 text-green-600"></i>Formas de Pago:</p>
                                            <div class="space-y-1">
                                                @foreach($formasPago as $fp)
                                                    <div class="flex justify-between items-center text-sm">
                                                        <span class="text-gray-900">{{ $fp->observa }}</span>
                                                        <span class="font-medium text-green-700">${{ number_format($fp->importe, 2) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if($venta->OBSERVA)
                                        <div class="mt-3 p-3 bg-blue-50 rounded-md">
                                            <p class="text-xs font-medium text-gray-700">Observaciones:</p>
                                            <p class="text-sm text-gray-900">{{ $venta->OBSERVA }}</p>
                                        </div>
                                    @endif
                                    @if($venta->origen === 'SALON')
                                        <div class="mt-3 p-3 bg-purple-50 rounded-md">
                                            <p class="text-xs font-medium text-gray-700 mb-2"><i class="fas fa-user-tie mr-1 text-purple-600"></i>Info de Salón:</p>
                                            <div class="space-y-1">
                                                @if($venta->VENDEDOR)
                                                    <div class="flex justify-between items-center text-sm">
                                                        <span class="text-gray-600">Mozo:</span>
                                                        <span class="font-medium text-gray-900">
                                                            {{ $venta->nombre_mozo ?? $venta->VENDEDOR }}
                                                            <span class="text-xs text-gray-400 ml-1">({{ $venta->VENDEDOR }})</span>
                                                        </span>
                                                    </div>
                                                @endif
                                                @if(($venta->COMENSALES ?? 0) > 0)
                                                    <div class="flex justify-between items-center text-sm">
                                                        <span class="text-gray-600">Comensales:</span>
                                                        <span class="font-medium text-gray-900">{{ $venta->COMENSALES }}</span>
                                                    </div>
                                                    @if($venta->COMENSALES > 1)
                                                        <div class="flex justify-between items-center text-sm">
                                                            <span class="text-gray-600">Promedio por persona:</span>
                                                            <span class="font-medium text-purple-700">${{ number_format($venta->IMPORTE / $venta->COMENSALES, 2) }}</span>
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
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
                                       {{ $venta->TICOMP == 'NC' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
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
                            @if($venta->factura_aplicada)
                                <div class="text-xs text-red-500 mt-1">
                                    <i class="fas fa-reply mr-1"></i>Aplica a: {{ $venta->factura_aplicada }}
                                </div>
                            @endif
                            <div class="text-sm text-gray-900 font-medium truncate">{{ $venta->NOMBRE ?? 'Sin nombre' }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ $venta->fecha_formateada }} - {{ $venta->hora_formateada }}</div>
                            <div class="text-xs text-gray-500">Usuario: {{ $venta->USUARIO }}</div>
                        </div>
                        <div class="text-right ml-2">
                            <div class="text-base font-semibold {{ $venta->TICOMP == 'NC' ? 'text-red-600' : 'text-gray-900' }}">
                                ${{ number_format($venta->importe_calculado, 2) }}
                            </div>
                        </div>
                    </div>

                    <!-- Botón para expandir detalle -->
                    <button wire:click="toggleDetalle('{{ $venta->nrofac }}')"
                            class="w-full mt-2 px-3 py-2 text-sm bg-[#FFAF22]/10 text-[#FFAF22] rounded-md hover:bg-[#FFAF22]/20 transition-colors">
                        <i class="fas fa-eye mr-1"></i>
                        {{ $ventaExpandida === $venta->nrofac ? 'Ocultar detalle' : 'Ver detalle' }}
                    </button>

                    <!-- Detalle expandible -->
                    @if($ventaExpandida === $venta->nrofac)
                        <div class="mt-3 p-3 bg-gray-50 rounded-md">
                            <div class="space-y-2">
                                @foreach($this->getDetalleVenta($venta->nrofac) as $detalle)
                                    <div class="border-b border-gray-200 pb-2 last:border-b-0">
                                        <div class="text-sm font-medium text-gray-900">{{ $detalle->DETART }}</div>
                                        <div class="text-xs text-gray-600">Código: {{ $detalle->CODART }}</div>
                                        <div class="flex justify-between mt-1">
                                            <span class="text-xs text-gray-600">Cant: {{ number_format($detalle->CANTIDAD, 2) }} × ${{ number_format($detalle->PUNIT, 2) }}</span>
                                            <span class="text-sm font-medium text-gray-900">${{ number_format($detalle->IMPORTE, 2) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            {{-- Formas de Pago --}}
                            @php
                                $formasPagoMobile = $this->getFormasPago($venta->TICOMP, $venta->LETRA, $venta->NUMCOMP);
                            @endphp
                            @if($formasPagoMobile->isNotEmpty())
                                <div class="mt-3 p-2 bg-green-50 rounded-md">
                                    <p class="text-xs font-medium text-gray-700 mb-1"><i class="fas fa-money-bill-wave mr-1 text-green-600"></i>Formas de Pago:</p>
                                    <div class="space-y-1">
                                        @foreach($formasPagoMobile as $fp)
                                            <div class="flex justify-between items-center text-xs">
                                                <span class="text-gray-900">{{ $fp->observa }}</span>
                                                <span class="font-medium text-green-700">${{ number_format($fp->importe, 2) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            @if($venta->OBSERVA)
                                <div class="mt-3 p-2 bg-blue-50 rounded-md">
                                    <p class="text-xs font-medium text-gray-700">Observaciones:</p>
                                    <p class="text-xs text-gray-900">{{ $venta->OBSERVA }}</p>
                                </div>
                            @endif
                            @if($venta->origen === 'SALON')
                                <div class="mt-3 p-2 bg-purple-50 rounded-md">
                                    <p class="text-xs font-medium text-gray-700 mb-1"><i class="fas fa-user-tie mr-1 text-purple-600"></i>Info de Salón:</p>
                                    <div class="space-y-1">
                                        @if($venta->VENDEDOR)
                                            <div class="flex justify-between items-center text-xs">
                                                <span class="text-gray-600">Mozo:</span>
                                                <span class="font-medium text-gray-900">
                                                    {{ $venta->nombre_mozo ?? $venta->VENDEDOR }}
                                                    <span class="text-gray-400 ml-1">({{ $venta->VENDEDOR }})</span>
                                                </span>
                                            </div>
                                        @endif
                                        @if(($venta->COMENSALES ?? 0) > 0)
                                            <div class="flex justify-between items-center text-xs">
                                                <span class="text-gray-600">Comensales:</span>
                                                <span class="font-medium text-gray-900">{{ $venta->COMENSALES }}</span>
                                            </div>
                                            @if($venta->COMENSALES > 1)
                                                <div class="flex justify-between items-center text-xs">
                                                    <span class="text-gray-600">Promedio x persona:</span>
                                                    <span class="font-medium text-purple-700">${{ number_format($venta->IMPORTE / $venta->COMENSALES, 2) }}</span>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
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
