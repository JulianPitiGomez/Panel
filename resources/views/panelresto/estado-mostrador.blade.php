<div class="min-h-screen bg-gray-50">

    <!-- Filtros principales - Móvil Optimizado -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-6 mb-4">
        <div class="flex items-center justify-between mb-3 md:mb-4">
            <h2 class="text-base md:text-lg font-primary font-semibold text-gray-800 flex items-center">
                <i class="fas fa-filter text-orange-500 mr-2 text-sm md:text-base"></i>
                <span class="hidden sm:inline">Filtros de Búsqueda</span>
                <span class="sm:hidden">Filtros</span>
            </h2>
            <button wire:click="limpiarFiltros"
                    class="px-2 md:px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors duration-200 text-xs md:text-sm font-secondary">
                <i class="fas fa-broom md:mr-1"></i>
                <span class="hidden md:inline">Limpiar Filtros</span>
            </button>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 md:gap-4">
            <!-- Fecha Desde -->
            <div>
                <label class="block text-xs md:text-sm font-secondary font-medium text-gray-700 mb-1">Desde</label>
                <input type="date"
                       wire:model.live="fechaDesde"
                       class="w-full px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
            </div>

            <!-- Fecha Hasta -->
            <div>
                <label class="block text-xs md:text-sm font-secondary font-medium text-gray-700 mb-1">Hasta</label>
                <input type="date"
                       wire:model.live="fechaHasta"
                       class="w-full px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
            </div>

            <!-- Estado -->
            <div class="col-span-2 lg:col-span-1">
                <label class="block text-xs md:text-sm font-secondary font-medium text-gray-700 mb-1">Estado</label>
                <select wire:model.live="estadoSeleccionado"
                        class="w-full px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
                    <option value="">Todos los estados</option>
                    @foreach($estados as $key => $estado)
                        <option value="{{ $key }}">{{ $estado }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Filtros adicionales -->
        <div class="flex items-center mt-3 md:mt-4 pt-3 md:pt-4 border-t border-gray-200">
            <label class="flex items-center">
                <input type="checkbox"
                       wire:model.live="soloNoEntregados"
                       class="w-4 h-4 text-orange-600 bg-gray-100 border-gray-300 rounded focus:ring-orange-500">
                <span class="ml-2 text-xs md:text-sm font-secondary text-gray-700">Solo pedidos no entregados</span>
            </label>
        </div>
    </div>

    <!-- Controles de vista -->
    <div class="flex flex-wrap gap-2 md:gap-3 mb-4 px-1">
        <button wire:click="toggleDetalle"
                class="px-3 md:px-4 py-2 rounded-lg transition-colors duration-200 font-secondary text-xs md:text-sm {{ $mostrarDetalle ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            <i class="fas fa-list mr-1"></i>
            <span class="hidden sm:inline">{{ $mostrarDetalle ? 'Ocultar' : 'Mostrar' }}</span> Detalle
        </button>

        <button wire:click="toggleResumen"
                class="px-3 md:px-4 py-2 rounded-lg transition-colors duration-200 font-secondary text-xs md:text-sm {{ $mostrarResumen ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            <i class="fas fa-chart-bar mr-1"></i>
            <span class="hidden sm:inline">{{ $mostrarResumen ? 'Ocultar' : 'Mostrar' }}</span> Resumen
        </button>
    </div>

    <!-- Total general - Móvil Optimizado -->
    <div class="bg-gradient-to-r from-orange-50 to-orange-100 rounded-xl p-3 md:p-6 border border-orange-200 mb-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="text-base md:text-lg font-primary font-semibold text-gray-800">Resumen de Pedidos</h3>
                <p class="text-xs md:text-sm font-secondary text-gray-600">{{ $totalPedidos }} pedidos encontrados</p>
            </div>
            <div class="sm:text-right">
                <p class="text-2xl md:text-3xl font-primary font-bold text-orange-600">${{ number_format($importeTotal, 2) }}</p>
                <p class="text-xs md:text-sm font-secondary text-gray-600">{{ date('d/m/Y', strtotime($fechaDesde)) }} - {{ date('d/m/Y', strtotime($fechaHasta)) }}</p>
            </div>
        </div>
    </div>

    <!-- Sección: Resumen por Estados -->
    @if($mostrarResumen)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-chart-bar text-orange-500 mr-2"></i>
            Resumen por Estados
        </h3>

        @if(count($estadosCount) > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($estadosCount as $estadoId => $data)
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-secondary font-semibold text-gray-800 flex items-center">
                        <i class="{{ $this->getEstadoIcon($estadoId) }} mr-2 text-gray-600"></i>
                        {{ $estados[$estadoId] ?? 'Estado ' . $estadoId }}
                    </h4>
                    <span class="text-xs {{ $this->getEstadoColor($estadoId) }} px-2 py-1 rounded-full font-secondary">
                        {{ $data->total }} pedidos
                    </span>
                </div>
                <p class="text-2xl font-primary font-bold text-orange-600">${{ number_format($data->importe_total, 2) }}</p>
                <p class="text-sm font-secondary text-gray-600">
                    {{ $importeTotal > 0 ? number_format(($data->importe_total / $importeTotal) * 100, 1) : 0 }}% del total
                </p>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-8">
            <i class="fas fa-chart-bar text-gray-400 text-4xl mb-3"></i>
            <p class="text-gray-600 font-secondary">No hay datos de estados para mostrar</p>
        </div>
        @endif
    </div>
    @endif

    <!-- Layout Principal con Panel Lateral -->
    <div class="flex flex-col lg:flex-row gap-4 md:gap-6">
        <!-- Contenido Principal -->
        <div class="{{ $mostrarDetallePedido ? 'hidden lg:block lg:w-2/3' : 'w-full' }} transition-all duration-300 overflow-hidden">

            <!-- Sección: Pedidos -->
            @if($mostrarDetalle)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="p-3 md:p-6 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 md:gap-4">
                        <h3 class="text-base md:text-lg font-primary font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-cash-register text-orange-500 mr-2 text-sm md:text-base"></i>
                            <span class="hidden sm:inline">Pedidos de Mostrador</span>
                            <span class="sm:hidden">Mostrador</span>
                        </h3>

                        <!-- Controles de paginación superior -->
                        <div class="flex items-center gap-2 md:gap-4">
                            <div class="flex items-center gap-2">
                                <label class="text-xs md:text-sm font-secondary text-gray-600 hidden sm:inline">Mostrar:</label>
                                <select wire:change="cambiarTamanoPagina($event.target.value)"
                                        class="px-2 py-1 border border-gray-300 rounded text-xs md:text-sm font-secondary focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                    <option value="10" {{ $paginacionSize == 10 ? 'selected' : '' }}>10</option>
                                    <option value="20" {{ $paginacionSize == 20 ? 'selected' : '' }}>20</option>
                                    <option value="50" {{ $paginacionSize == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ $paginacionSize == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </div>

                            @if($pedidos->hasPages())
                            <div class="text-xs md:text-sm font-secondary text-gray-600 hidden md:block">
                                Mostrando {{ $pedidos->firstItem() }} - {{ $pedidos->lastItem() }} de {{ $pedidos->total() }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if($pedidos->count() > 0)
                <!-- Vista de tabla para desktop -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider">Fecha/Hora</th>
                                <th class="px-6 py-3 text-left text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                <th class="px-6 py-3 text-center text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider">Importe</th>
                                <th class="px-6 py-3 text-center text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($pedidos as $pedido)
                            <tr class="hover:bg-gray-50 transition-colors duration-200 {{ $mostrarDetallePedido && $pedidoSeleccionado && $pedidoSeleccionado->id == $pedido->id ? 'bg-orange-50 border-l-4 border-orange-500' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-secondary font-medium text-gray-900">
                                        {{ date('d/m/Y', strtotime($pedido->fecha)) }}
                                    </div>
                                    <div class="text-xs font-secondary text-gray-500">
                                        {{ date('H:i', strtotime($pedido->hora)) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-secondary font-medium text-gray-900">
                                        {{ $pedido->nombre }}
                                    </div>
                                    @if($pedido->telefono)
                                    <div class="text-xs font-secondary text-gray-500">
                                        <i class="fas fa-phone mr-1"></i>{{ $pedido->telefono }}
                                    </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-secondary font-medium {{ $this->getEstadoColor($pedido->estado) }}">
                                        <i class="{{ $this->getEstadoIcon($pedido->estado) }} mr-1"></i>
                                        {{ $estados[$pedido->estado] ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-secondary font-bold text-gray-900">
                                        ${{ number_format($pedido->importe, 2) }}
                                    </div>
                                    @if($pedido->pagacon > 0)
                                    <div class="text-xs font-secondary text-gray-500">
                                        Paga: ${{ number_format($pedido->pagacon, 2) }}
                                    </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button wire:click="verDetallePedido({{ $pedido->id }})"
                                                class="text-orange-600 hover:text-orange-900 font-secondary text-sm">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Vista de cards para móvil -->
                <div class="lg:hidden space-y-3 p-3">
                    @foreach($pedidos as $pedido)
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm {{ $mostrarDetallePedido && $pedidoSeleccionado && $pedidoSeleccionado->id == $pedido->id ? 'border-orange-500 border-2' : '' }}">
                        <!-- Header del card -->
                        <div class="p-3 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h4 class="font-secondary font-semibold text-gray-900 text-sm">{{ $pedido->nombre }}</h4>
                                    <p class="text-xs text-gray-600">
                                        {{ date('d/m/Y H:i', strtotime($pedido->fecha . ' ' . $pedido->hora)) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Contenido del card -->
                        <div class="p-3 space-y-2">
                            <!-- Estado -->
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-secondary text-gray-600">Estado:</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-secondary font-medium {{ $this->getEstadoColor($pedido->estado) }}">
                                    <i class="{{ $this->getEstadoIcon($pedido->estado) }} mr-1"></i>
                                    {{ $estados[$pedido->estado] ?? 'N/A' }}
                                </span>
                            </div>

                            <!-- Teléfono -->
                            @if($pedido->telefono)
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-secondary text-gray-600">Teléfono:</span>
                                <a href="tel:{{ $pedido->telefono }}" class="text-xs font-secondary text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-phone mr-1"></i>{{ $pedido->telefono }}
                                </a>
                            </div>
                            @endif

                            <!-- Importe -->
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-secondary text-gray-600">Total:</span>
                                <span class="text-base font-secondary font-bold text-gray-900">
                                    ${{ number_format($pedido->importe, 2) }}
                                </span>
                            </div>

                            <!-- Pago -->
                            @if($pedido->pagacon > 0)
                            <div class="text-xs text-gray-600 bg-yellow-50 p-2 rounded">
                                <i class="fas fa-money-bill mr-1"></i>
                                Paga con: ${{ number_format($pedido->pagacon, 2) }}
                                @if(isset($pedido->vuelto) && $pedido->vuelto > 0)
                                    | Vuelto: ${{ number_format($pedido->vuelto, 2) }}
                                @endif
                            </div>
                            @endif
                        </div>

                        <!-- Acciones del card -->
                        <div class="p-3 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                            <button wire:click="verDetallePedido({{ $pedido->id }})"
                                    class="w-full px-3 py-2 bg-orange-500 text-white rounded-lg font-secondary text-sm hover:bg-orange-600 transition-colors active:scale-95">
                                <i class="fas fa-eye mr-1"></i>
                                Ver Detalle
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Paginación (reutilizar el código anterior) -->
                @if($pedidos->hasPages())
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <!-- Código de paginación similar al anterior -->
                </div>
                @endif

                @else
                <div class="text-center py-12">
                    <i class="fas fa-motorcycle text-gray-400 text-5xl mb-4"></i>
                    <h3 class="text-lg font-primary font-medium text-gray-900 mb-2">No hay pedidos</h3>
                    <p class="text-gray-600 font-secondary">No se encontraron pedidos con los filtros seleccionados</p>
                </div>
                @endif
            </div>
            @endif

        </div>

        <!-- Panel Lateral de Detalle - Móvil Optimizado -->
        @if($mostrarDetallePedido && $pedidoSeleccionado)
        <div class="w-full lg:w-1/3 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
            <div class="h-full flex flex-col max-h-screen lg:max-h-[800px]">
                <!-- Header del Panel -->
                <div class="flex items-center justify-between p-3 md:p-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-base md:text-lg font-primary font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-receipt text-orange-500 mr-2 text-sm md:text-base"></i>
                        Pedido #{{ $pedidoSeleccionado->id }}
                    </h3>
                    <button wire:click="cerrarDetallePedido" class="text-gray-400 hover:text-gray-600 p-2 hover:bg-gray-200 rounded-lg transition-colors">
                        <i class="fas fa-times text-lg md:text-xl"></i>
                    </button>
                </div>

                <!-- Botón "Volver a la Lista" solo en móvil -->
                <div class="lg:hidden p-3 border-b border-gray-200 bg-orange-50">
                    <button wire:click="cerrarDetallePedido"
                            class="w-full px-4 py-2 bg-orange-500 text-white rounded-lg font-secondary text-sm hover:bg-orange-600 transition-colors active:scale-95">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Volver a la Lista de Pedidos
                    </button>
                </div>

                <!-- Información General del Pedido -->
                <div class="p-3 md:p-4 border-b border-gray-200 bg-gray-50">
                    <div class="grid grid-cols-1 gap-2 md:gap-3 text-xs md:text-sm">
                        <div class="flex justify-between">
                            <span class="font-secondary font-medium text-gray-600">Cliente:</span>
                            <span class="font-secondary text-gray-900">{{ $pedidoSeleccionado->nombre }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-secondary font-medium text-gray-600">Fecha:</span>
                            <span class="font-secondary text-gray-900">{{ date('d/m/Y H:i', strtotime($pedidoSeleccionado->fecha . ' ' . $pedidoSeleccionado->hora)) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-secondary font-medium text-gray-600">Teléfono:</span>
                            <span class="font-secondary text-gray-900">{{ $pedidoSeleccionado->telefono ?: 'No especificado' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="font-secondary font-medium text-gray-600">Estado:</span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-secondary font-medium {{ $this->getEstadoColor($pedidoSeleccionado->estado) }}">
                                <i class="{{ $this->getEstadoIcon($pedidoSeleccionado->estado) }} mr-1"></i>
                                {{ $estados[$pedidoSeleccionado->estado] ?? 'N/A' }}
                            </span>
                        </div>
                    </div>

                    <!-- Info de Pago -->
                    @if($pedidoSeleccionado->pagacon > 0)
                    <div class="mt-2 md:mt-3 pt-2 md:pt-3 border-t border-gray-200">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs md:text-sm">
                            <div class="flex justify-between">
                                <span class="font-secondary font-medium text-gray-600">Paga con:</span>
                                <span class="font-secondary text-gray-900">${{ number_format($pedidoSeleccionado->pagacon, 2) }}</span>
                            </div>
                            @if(isset($pedidoSeleccionado->vuelto) && $pedidoSeleccionado->vuelto > 0)
                            <div class="flex justify-between">
                                <span class="font-secondary font-medium text-gray-600">Vuelto:</span>
                                <span class="font-secondary text-gray-900">${{ number_format($pedidoSeleccionado->vuelto, 2) }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Detalle de Items del Pedido -->
                <div class="flex-1 overflow-y-auto">
                    <div class="p-3 md:p-4">
                        <h4 class="font-secondary font-semibold text-gray-800 mb-2 md:mb-3 flex items-center text-sm md:text-base">
                            <i class="fas fa-utensils text-orange-500 mr-2"></i>
                            Items del Pedido
                        </h4>

                        @if(count($detallePedido) > 0)
                        <div class="space-y-2 md:space-y-3">
                            @foreach($detallePedido as $item)
                            <div class="bg-gray-50 rounded-lg p-2 md:p-3 border border-gray-200">
                                <div class="flex justify-between items-start mb-1 md:mb-2">
                                    <h5 class="font-secondary font-medium text-gray-900 text-xs md:text-sm flex-1 pr-2">
                                        {{ $item->nomart }}
                                    </h5>
                                    <span class="font-secondary font-bold text-gray-900 text-xs md:text-sm">
                                        ${{ number_format($item->ptotal, 2) }}
                                    </span>
                                </div>

                                <div class="flex justify-between items-center text-xs text-gray-600">
                                    <div class="flex items-center space-x-2 md:space-x-4">
                                        <span>
                                            <i class="fas fa-hashtag mr-1"></i>
                                            {{ $item->codart }}
                                        </span>
                                        <span>
                                            <i class="fas fa-dollar-sign mr-1"></i>
                                            ${{ number_format($item->punit, 2) }}
                                        </span>
                                    </div>
                                    <span class="bg-orange-100 text-orange-800 px-2 py-0.5 rounded-full text-xs font-medium">
                                        × {{ number_format($item->cantidad, 2) }}
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-6 md:py-8">
                            <i class="fas fa-utensils text-gray-400 text-xl md:text-2xl mb-2"></i>
                            <p class="text-gray-600 font-secondary text-xs md:text-sm">Sin items en el pedido</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Footer del Panel - Resumen -->
                @if(count($detallePedido) > 0)
                <div class="border-t border-gray-200 p-3 md:p-4 bg-gray-50">
                    <div class="flex justify-between items-center">
                        <span class="font-secondary font-bold text-gray-900 text-sm md:text-base">TOTAL PEDIDO:</span>
                        <span class="font-primary font-black text-lg md:text-xl text-orange-600">
                            ${{ number_format($pedidoSeleccionado->importe, 2) }}
                        </span>
                    </div>
                    <div class="text-xs md:text-sm text-gray-600 font-secondary mt-1">
                        {{ count($detallePedido) }} {{ count($detallePedido) == 1 ? 'item' : 'items' }}
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

    </div>

</div>

<script>
function toggleDropdown(pedidoId) {
    const dropdown = document.getElementById('dropdown-' + pedidoId);
    dropdown.classList.toggle('hidden');
    
    // Cerrar otros dropdowns
    document.querySelectorAll('[id^="dropdown-"]').forEach(el => {
        if (el.id !== 'dropdown-' + pedidoId) {
            el.classList.add('hidden');
        }
    });
}

// Cerrar dropdowns al hacer click fuera
document.addEventListener('click', function(event) {
    if (!event.target.closest('[onclick^="toggleDropdown"]') && !event.target.closest('[id^="dropdown-"]')) {
        document.querySelectorAll('[id^="dropdown-"]').forEach(el => {
            el.classList.add('hidden');
        });
    }
});
</script>