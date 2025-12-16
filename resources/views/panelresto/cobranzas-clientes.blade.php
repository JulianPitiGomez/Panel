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

            <!-- Cliente -->
            <div>
                <label class="block text-xs md:text-sm font-secondary font-medium text-gray-700 mb-1">Cliente</label>
                <select wire:model.live="clienteSeleccionado"
                        class="w-full px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
                    <option value="">Todos</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->codigo }}">{{ $cliente->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Caja -->
            <div>
                <label class="block text-xs md:text-sm font-secondary font-medium text-gray-700 mb-1">Caja</label>
                <select wire:model.live="cajaSeleccionada"
                        class="w-full px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
                    <option value="">Todas</option>
                    @foreach($cajas as $caja)
                        <option value="{{ $caja->caja }}">Caja {{ $caja->caja }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Filtros adicionales -->
        <div class="flex items-center mt-3 md:mt-4 pt-3 md:pt-4 border-t border-gray-200">
            <label class="flex items-center">
                <input type="checkbox"
                       wire:model.live="soloSinCerrar"
                       class="w-4 h-4 text-orange-600 bg-gray-100 border-gray-300 rounded focus:ring-orange-500">
                <span class="ml-2 text-xs md:text-sm font-secondary text-gray-700">Solo pagos sin cerrar</span>
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
            <i class="fas fa-chart-pie mr-1"></i>
            <span class="hidden sm:inline">{{ $mostrarResumen ? 'Ocultar' : 'Mostrar' }}</span> Resumen
        </button>
    </div>

    <!-- Total general - Móvil Optimizado -->
    <div class="bg-gradient-to-r from-orange-50 to-orange-100 rounded-xl p-3 md:p-6 border border-orange-200 mb-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="text-base md:text-lg font-primary font-semibold text-gray-800">Total de Pagos</h3>
                <p class="text-xs md:text-sm font-secondary text-gray-600">{{ $pagos->total() ?? 0 }} pagos encontrados</p>
            </div>
            <div class="sm:text-right">
                <p class="text-2xl md:text-3xl font-primary font-bold text-orange-600">${{ number_format($totalPagos, 2) }}</p>
                <p class="text-xs md:text-sm font-secondary text-gray-600">{{ date('d/m/Y', strtotime($fechaDesde)) }} - {{ date('d/m/Y', strtotime($fechaHasta)) }}</p>
            </div>
        </div>
    </div>

    <!-- Sección: Resumen por Formas de Pago -->
    @if($mostrarResumen)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4">
        <h3 class="text-base md:text-lg font-primary font-semibold text-gray-800 mb-3 md:mb-4 flex items-center">
            <i class="fas fa-chart-pie text-orange-500 mr-2 text-sm md:text-base"></i>
            Resumen por Formas de Pago
        </h3>

        @if(count($resumenFormasPago) > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
            @foreach($resumenFormasPago as $forma)
            <div class="bg-gray-50 rounded-lg p-3 md:p-4 border border-gray-200">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-secondary font-semibold text-gray-800 text-sm md:text-base">{{ $forma->forma_pago }}</h4>
                    <span class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded-full font-secondary">
                        {{ $forma->cantidad_pagos }} pagos
                    </span>
                </div>
                <p class="text-xl md:text-2xl font-primary font-bold text-orange-600">${{ number_format($forma->total_importe, 2) }}</p>
                <p class="text-xs md:text-sm font-secondary text-gray-600">
                    {{ $totalPagos > 0 ? number_format(($forma->total_importe / $totalPagos) * 100, 1) : 0 }}% del total
                </p>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-6 md:py-8">
            <i class="fas fa-chart-pie text-gray-400 text-3xl md:text-4xl mb-3"></i>
            <p class="text-gray-600 font-secondary text-sm md:text-base">No hay datos de formas de pago para mostrar</p>
        </div>
        @endif
    </div>
    @endif

    <!-- Layout Principal con Panel Lateral -->
    <div class="flex flex-col lg:flex-row gap-4 md:gap-6">
        <!-- Contenido Principal -->
        <div class="{{ $mostrarDetallePago ? 'hidden lg:block lg:w-2/3' : 'w-full' }} transition-all duration-300 overflow-hidden">

            <!-- Sección: Pagos por Fecha -->
            @if($mostrarDetalle)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="p-3 md:p-6 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 md:gap-4">
                        <h3 class="text-base md:text-lg font-primary font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-list text-orange-500 mr-2 text-sm md:text-base"></i>
                            <span class="hidden sm:inline">Pagos por Fecha</span>
                            <span class="sm:hidden">Pagos</span>
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

                            @if($pagos->hasPages())
                            <div class="text-xs md:text-sm font-secondary text-gray-600 hidden md:block">
                                Mostrando {{ $pagos->firstItem() }} - {{ $pagos->lastItem() }} de {{ $pagos->total() }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if($pagos->count() > 0)
                <!-- Vista de tabla para desktop -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider">N° Pago</th>
                                <th class="px-6 py-3 text-left text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                <th class="px-6 py-3 text-right text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-center text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider">Caja</th>
                                <th class="px-6 py-3 text-center text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                <th class="px-6 py-3 text-center text-xs font-secondary font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($pagos as $pago)
                            <tr class="hover:bg-gray-50 transition-colors duration-200 {{ $mostrarDetallePago && $pagoSeleccionado && $pagoSeleccionado->NUMERO == $pago->NUMERO ? 'bg-orange-50 border-l-4 border-orange-500' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-secondary font-medium text-gray-900">
                                        {{ date('d/m/Y', strtotime($pago->FECHA)) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-secondary font-medium text-gray-900">
                                        #{{ $pago->NUMERO }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-secondary font-medium text-gray-900">
                                        {{ $pago->cliente_nombre ?? 'Cliente #' . $pago->CLIENTE }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-secondary font-bold text-gray-900">
                                        ${{ number_format($pago->TOTAL, 2) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-secondary font-medium bg-blue-100 text-blue-800">
                                        Caja {{ $pago->caja }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($pago->checkeado)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-secondary font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-times mr-1"></i>
                                            Abierto
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-secondary font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check mr-1"></i>
                                            Cerrado
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-secondary text-gray-900">{{ $pago->USUARIO }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button wire:click="verDetallePago({{ $pago->NUMERO }})"
                                            class="text-orange-600 hover:text-orange-900 font-secondary text-sm">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            @if($pago->OBSERVA)
                            <tr class="bg-gray-50">
                                <td colspan="8" class="px-6 py-2">
                                    <div class="text-xs font-secondary text-gray-600">
                                        <i class="fas fa-comment text-gray-400 mr-1"></i>
                                        <strong>Observaciones:</strong> {{ $pago->OBSERVA }}
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Vista de cards para móvil -->
                <div class="lg:hidden space-y-3 p-3">
                    @foreach($pagos as $pago)
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm {{ $mostrarDetallePago && $pagoSeleccionado && $pagoSeleccionado->NUMERO == $pago->NUMERO ? 'border-orange-500 border-2' : '' }}">
                        <!-- Header del card -->
                        <div class="p-3 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                            <div class="flex justify-between items-start gap-2">
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-secondary font-semibold text-gray-900 text-sm">Pago #{{ $pago->NUMERO }}</h4>
                                    <p class="text-xs text-gray-600">
                                        {{ date('d/m/Y', strtotime($pago->FECHA)) }}
                                    </p>
                                </div>
                                @if($pago->checkeado)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-secondary font-medium bg-red-100 text-red-800 flex-shrink-0">
                                        <i class="fas fa-times mr-1"></i>
                                        Abierto
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-secondary font-medium bg-green-100 text-green-800 flex-shrink-0">
                                        <i class="fas fa-check mr-1"></i>
                                        Cerrado
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Contenido del card -->
                        <div class="p-3 space-y-2">
                            <!-- Cliente -->
                            <div>
                                <span class="text-xs font-secondary text-gray-600">Cliente:</span>
                                <p class="text-xs font-secondary text-gray-900 font-medium">{{ $pago->cliente_nombre ?? 'Cliente #' . $pago->CLIENTE }}</p>
                            </div>

                            <!-- Importe -->
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-secondary text-gray-600">Total:</span>
                                <span class="text-base font-secondary font-bold text-gray-900">
                                    ${{ number_format($pago->TOTAL, 2) }}
                                </span>
                            </div>

                            <!-- Caja y Usuario -->
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-gray-600">Caja {{ $pago->caja }}</span>
                                <span class="text-gray-600">{{ $pago->USUARIO }}</span>
                            </div>

                            <!-- Observaciones -->
                            @if($pago->OBSERVA)
                            <div class="text-xs text-gray-600 bg-amber-50 p-2 rounded">
                                <i class="fas fa-comment mr-1"></i>
                                {{ $pago->OBSERVA }}
                            </div>
                            @endif
                        </div>

                        <!-- Acciones del card -->
                        <div class="p-3 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                            <button wire:click="verDetallePago({{ $pago->NUMERO }})"
                                    class="w-full px-3 py-2 bg-orange-500 text-white rounded-lg font-secondary text-sm hover:bg-orange-600 transition-colors active:scale-95">
                                <i class="fas fa-eye mr-1"></i>
                                Ver Detalle
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>

                @else
                <div class="text-center py-8 md:py-12">
                    <i class="fas fa-hand-holding-usd text-gray-400 text-4xl md:text-5xl mb-4"></i>
                    <h3 class="text-base md:text-lg font-primary font-medium text-gray-900 mb-2">No hay pagos</h3>
                    <p class="text-sm md:text-base text-gray-600 font-secondary">No se encontraron pagos con los filtros seleccionados</p>
                </div>
                @endif
            </div>
            @endif

        </div>

        <!-- Panel Lateral de Detalle - Móvil Optimizado -->
        @if($mostrarDetallePago && $pagoSeleccionado)
        <div class="w-full lg:w-1/3 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
            <div class="h-full flex flex-col max-h-screen lg:max-h-[800px]">
                <!-- Header del Panel -->
                <div class="flex items-center justify-between p-3 md:p-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-base md:text-lg font-primary font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-receipt text-orange-500 mr-2 text-sm md:text-base"></i>
                        Pago #{{ $pagoSeleccionado->NUMERO }}
                    </h3>
                    <button wire:click="cerrarDetallePago" class="text-gray-400 hover:text-gray-600 p-2 hover:bg-gray-200 rounded-lg transition-colors">
                        <i class="fas fa-times text-lg md:text-xl"></i>
                    </button>
                </div>

                <!-- Botón "Volver a la Lista" solo en móvil -->
                <div class="lg:hidden p-3 border-b border-gray-200 bg-orange-50">
                    <button wire:click="cerrarDetallePago"
                            class="w-full px-4 py-2 bg-orange-500 text-white rounded-lg font-secondary text-sm hover:bg-orange-600 transition-colors active:scale-95">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Volver a la Lista
                    </button>
                </div>

                <!-- Información General del Pago -->
                <div class="p-3 md:p-4 border-b border-gray-200 bg-gray-50">
                    <div class="grid grid-cols-1 gap-2 md:gap-3 text-xs md:text-sm">
                        <div class="flex justify-between">
                            <span class="font-secondary font-medium text-gray-600">Cliente:</span>
                            <span class="font-secondary text-gray-900">{{ $pagoSeleccionado->cliente_nombre ?? 'Cliente #' . $pagoSeleccionado->CLIENTE }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-secondary font-medium text-gray-600">Fecha:</span>
                            <span class="font-secondary text-gray-900">{{ date('d/m/Y', strtotime($pagoSeleccionado->FECHA)) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-secondary font-medium text-gray-600">Total:</span>
                            <span class="font-secondary font-bold text-orange-600">${{ number_format($pagoSeleccionado->TOTAL, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-secondary font-medium text-gray-600">Caja:</span>
                            <span class="font-secondary text-gray-900">{{ $pagoSeleccionado->caja }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-secondary font-medium text-gray-600">Usuario:</span>
                            <span class="font-secondary text-gray-900">{{ $pagoSeleccionado->USUARIO }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="font-secondary font-medium text-gray-600">Estado:</span>
                            @if($pagoSeleccionado->checkeado)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-secondary font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-times mr-1"></i>
                                    Abierto
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-secondary font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check mr-1"></i>
                                    Cerrado
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($pagoSeleccionado->OBSERVA)
                    <div class="mt-2 md:mt-3 pt-2 md:pt-3 border-t border-gray-200">
                        <div class="text-xs md:text-sm">
                            <span class="font-secondary font-medium text-gray-600">Observaciones:</span>
                            <p class="font-secondary text-gray-900 mt-1">{{ $pagoSeleccionado->OBSERVA }}</p>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Detalle de Formas de Pago -->
                <div class="flex-1 overflow-y-auto">
                    <div class="p-3 md:p-4">
                        <h4 class="font-secondary font-semibold text-gray-800 mb-2 md:mb-3 flex items-center text-sm md:text-base">
                            <i class="fas fa-credit-card text-orange-500 mr-2"></i>
                            Formas de Pago
                        </h4>

                        @if(count($detallePagoCon) > 0)
                        <div class="space-y-2 md:space-y-3">
                            @foreach($detallePagoCon as $detalle)
                            <div class="bg-gray-50 rounded-lg p-2 md:p-3 border border-gray-200">
                                <div class="flex justify-between items-start mb-1 md:mb-2">
                                    <h5 class="font-secondary font-medium text-gray-900 text-xs md:text-sm flex-1 pr-2">
                                        {{ $detalle->forma_pago ?? 'Forma de pago #' . $detalle->CODCON }}
                                    </h5>
                                    <span class="font-secondary font-bold text-gray-900 text-xs md:text-sm">
                                        ${{ number_format($detalle->IMPORTE, 2) }}
                                    </span>
                                </div>

                                @if(isset($detalle->tipocon) && $detalle->tipocon)
                                <div class="text-xs text-gray-600 mb-1">
                                    <span class="font-medium">Tipo:</span>
                                    <span class="ml-1">{{ $detalle->tipocon }}</span>
                                </div>
                                @endif

                                @if($detalle->OBSERVA)
                                <div class="text-xs text-amber-600 mt-2">
                                    <i class="fas fa-comment mr-1"></i>
                                    {{ $detalle->OBSERVA }}
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-6 md:py-8">
                            <i class="fas fa-credit-card text-gray-400 text-xl md:text-2xl mb-2"></i>
                            <p class="text-gray-600 font-secondary text-xs md:text-sm">Sin formas de pago registradas</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Footer del Panel - Resumen -->
                @if(count($detallePagoCon) > 0)
                <div class="border-t border-gray-200 p-3 md:p-4 bg-gray-50">
                    <div class="flex justify-between items-center">
                        <span class="font-secondary font-bold text-gray-900 text-sm md:text-base">TOTAL PAGO:</span>
                        <span class="font-primary font-black text-lg md:text-xl text-orange-600">
                            ${{ number_format($pagoSeleccionado->TOTAL, 2) }}
                        </span>
                    </div>
                    <div class="text-xs md:text-sm text-gray-600 font-secondary mt-1">
                        {{ count($detallePagoCon) }} {{ count($detallePagoCon) == 1 ? 'forma de pago' : 'formas de pago' }}
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

    </div>

</div>
