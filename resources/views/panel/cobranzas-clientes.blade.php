<div class="space-y-6">
    
    <!-- Filtros principales -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-primary font-semibold text-gray-800 flex items-center">
                <i class="fas fa-filter text-orange-500 mr-2"></i>
                Filtros de Búsqueda
            </h2>
            <button wire:click="limpiarFiltros" 
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors duration-200 text-sm font-secondary">
                <i class="fas fa-broom mr-1"></i>
                Limpiar Filtros
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Fecha Desde -->
            <div>
                <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Fecha Desde</label>
                <input type="date" 
                       wire:model.live="fechaDesde"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
            </div>

            <!-- Fecha Hasta -->
            <div>
                <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Fecha Hasta</label>
                <input type="date" 
                       wire:model.live="fechaHasta"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
            </div>

            <!-- Cliente -->
            <div>
                <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Cliente</label>
                <select wire:model.live="clienteSeleccionado" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
                    <option value="">Todos los clientes</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->codigo }}">{{ $cliente->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Caja -->
            <div>
                <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Caja</label>
                <select wire:model.live="cajaSeleccionada" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
                    <option value="">Todas las cajas</option>
                    @foreach($cajas as $caja)
                        <option value="{{ $caja->caja }}">Caja {{ $caja->caja }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Filtros adicionales -->
        <div class="flex items-center mt-4 pt-4 border-t border-gray-200">
            <label class="flex items-center">
                <input type="checkbox" 
                       wire:model.live="soloSinCerrar"
                       class="w-4 h-4 text-orange-600 bg-gray-100 border-gray-300 rounded focus:ring-orange-500">
                <span class="ml-2 text-sm font-secondary text-gray-700">Solo pagos sin cerrar</span>
            </label>
        </div>
    </div>

    <!-- Controles de vista -->
    <div class="flex flex-wrap gap-3">
        <button wire:click="toggleDetalle" 
                class="px-4 py-2 rounded-lg transition-colors duration-200 font-secondary text-sm {{ $mostrarDetalle ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            <i class="fas fa-list mr-1"></i>
            {{ $mostrarDetalle ? 'Ocultar' : 'Mostrar' }} Detalle
        </button>
        
        <button wire:click="toggleResumen" 
                class="px-4 py-2 rounded-lg transition-colors duration-200 font-secondary text-sm {{ $mostrarResumen ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            <i class="fas fa-chart-pie mr-1"></i>
            {{ $mostrarResumen ? 'Ocultar' : 'Mostrar' }} Resumen
        </button>
    </div>

    <!-- Total general -->
    <div class="bg-gradient-to-r from-orange-50 to-orange-100 rounded-xl p-6 border border-orange-200">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-primary font-semibold text-gray-800">Total de Pagos</h3>
                <p class="text-sm font-secondary text-gray-600">{{ $pagos->total() ?? 0 }} pagos encontrados</p>
            </div>
            <div class="text-right">
                <p class="text-3xl font-primary font-bold text-orange-600">${{ number_format($totalPagos, 2) }}</p>
                <p class="text-sm font-secondary text-gray-600">Período: {{ date('d/m/Y', strtotime($fechaDesde)) }} - {{ date('d/m/Y', strtotime($fechaHasta)) }}</p>
            </div>
        </div>
    </div>

    <!-- Sección: Resumen por Formas de Pago -->
    @if($mostrarResumen)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-chart-pie text-orange-500 mr-2"></i>
            Resumen por Formas de Pago
        </h3>

        @if(count($resumenFormasPago) > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($resumenFormasPago as $forma)
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-secondary font-semibold text-gray-800">{{ $forma->forma_pago }}</h4>
                    <span class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded-full font-secondary">
                        {{ $forma->cantidad_pagos }} pagos
                    </span>
                </div>
                <p class="text-2xl font-primary font-bold text-orange-600">${{ number_format($forma->total_importe, 2) }}</p>
                <p class="text-sm font-secondary text-gray-600">
                    {{ number_format(($forma->total_importe / $totalPagos) * 100, 1) }}% del total
                </p>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-8">
            <i class="fas fa-chart-pie text-gray-400 text-4xl mb-3"></i>
            <p class="text-gray-600 font-secondary">No hay datos de formas de pago para mostrar</p>
        </div>
        @endif
    </div>
    @endif

    <!-- Layout Principal con Panel Lateral -->
    <div class="flex gap-6">
        <!-- Contenido Principal -->
        <div class="{{ $mostrarDetallePago ? 'w-2/3' : 'w-full' }} transition-all duration-300 overflow-hidden">
            
            <!-- Sección: Pagos por Fecha -->
            @if($mostrarDetalle)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <h3 class="text-lg font-primary font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-list text-orange-500 mr-2"></i>
                            Pagos por Fecha
                        </h3>
                        
                        <!-- Controles de paginación superior -->
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2">
                                <label class="text-sm font-secondary text-gray-600">Mostrar:</label>
                                <select wire:change="cambiarTamanoPagina($event.target.value)" 
                                        class="px-2 py-1 border border-gray-300 rounded text-sm font-secondary focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                    <option value="10" {{ $paginacionSize == 10 ? 'selected' : '' }}>10</option>
                                    <option value="20" {{ $paginacionSize == 20 ? 'selected' : '' }}>20</option>
                                    <option value="50" {{ $paginacionSize == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ $paginacionSize == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </div>
                            
                            @if($pagos->hasPages())
                            <div class="text-sm font-secondary text-gray-600">
                                Mostrando {{ $pagos->firstItem() }} - {{ $pagos->lastItem() }} de {{ $pagos->total() }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if($pagos->count() > 0)
                <div class="overflow-x-auto">
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
                                        <i class="fas fa-eye mr-1"></i>
                                        Ver Detalle
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

                <!-- Paginación -->
                @if($pagos->hasPages())
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="text-sm font-secondary text-gray-600">
                            Mostrando {{ $pagos->firstItem() }} - {{ $pagos->lastItem() }} de {{ $pagos->total() }} registros
                        </div>
                        
                        <nav class="flex items-center space-x-1" aria-label="Paginación">
                            {{-- Botón Primera Página --}}
                            @if($pagos->currentPage() > 3)
                                <button wire:click="gotoPage(1)" 
                                        class="px-3 py-2 text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                                    <i class="fas fa-angle-double-left"></i>
                                </button>
                            @endif

                            {{-- Botón Anterior --}}
                            @if($pagos->onFirstPage())
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
                                $start = max(1, $pagos->currentPage() - 2);
                                $end = min($pagos->lastPage(), $pagos->currentPage() + 2);
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
                                @if($page == $pagos->currentPage())
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

                            @if($end < $pagos->lastPage())
                                @if($end < $pagos->lastPage() - 1)
                                    <span class="px-2 py-2 text-sm font-secondary text-gray-400">...</span>
                                @endif
                                <button wire:click="gotoPage({{ $pagos->lastPage() }})" 
                                        class="px-3 py-2 text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                                    {{ $pagos->lastPage() }}
                                </button>
                            @endif

                            {{-- Botón Siguiente --}}
                            @if($pagos->hasMorePages())
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
                            @if($pagos->currentPage() < $pagos->lastPage() - 2)
                                <button wire:click="gotoPage({{ $pagos->lastPage() }})" 
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
                    <i class="fas fa-hand-holding-usd text-gray-400 text-5xl mb-4"></i>
                    <h3 class="text-lg font-primary font-medium text-gray-900 mb-2">No hay pagos</h3>
                    <p class="text-gray-600 font-secondary">No se encontraron pagos con los filtros seleccionados</p>
                </div>
                @endif
            </div>
            @endif

        </div>

        <!-- Panel Lateral de Detalle -->
        @if($mostrarDetallePago && $pagoSeleccionado)
        <div class="w-1/3 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
            <div class="h-full flex flex-col max-h-[800px]">
                <!-- Header del Panel -->
                <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-primary font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-receipt text-orange-500 mr-2"></i>
                        Pago #{{ $pagoSeleccionado->NUMERO }}
                    </h3>
                    <button wire:click="cerrarDetallePago" class="text-gray-400 hover:text-gray-600 p-1">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Información General del Pago -->
                <div class="p-4 border-b border-gray-200 bg-gray-50">
                    <div class="grid grid-cols-1 gap-3 text-sm">
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
                        <div class="flex justify-between">
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
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <div class="text-sm">
                            <span class="font-secondary font-medium text-gray-600">Observaciones:</span>
                            <p class="font-secondary text-gray-900 mt-1">{{ $pagoSeleccionado->OBSERVA }}</p>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Detalle de Formas de Pago -->
                <div class="flex-1 overflow-y-auto">
                    <div class="p-4">
                        <h4 class="font-secondary font-semibold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-credit-card text-orange-500 mr-2"></i>
                            Formas de Pago
                        </h4>

                        @if(count($detallePagoCon) > 0)
                        <div class="space-y-3">
                            @foreach($detallePagoCon as $detalle)
                            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                <div class="flex justify-between items-start mb-2">
                                    <h5 class="font-secondary font-medium text-gray-900">
                                        {{ $detalle->forma_pago ?? 'Forma de pago #' . $detalle->CODCON }}
                                    </h5>
                                    <span class="font-secondary font-bold text-gray-900">
                                        ${{ number_format($detalle->IMPORTE, 2) }}
                                    </span>
                                </div>
                                
                                @if($detalle->tipocon)
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
                        <div class="text-center py-6">
                            <i class="fas fa-credit-card text-gray-400 text-2xl mb-2"></i>
                            <p class="text-gray-600 font-secondary text-sm">Sin formas de pago registradas</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Footer del Panel - Resumen -->
                @if(count($detallePagoCon) > 0)
                <div class="border-t border-gray-200 p-4 bg-gray-50">
                    <div class="flex justify-between items-center">
                        <span class="font-secondary font-bold text-gray-900">TOTAL PAGO:</span>
                        <span class="font-primary font-black text-xl text-orange-600">
                            ${{ number_format($pagoSeleccionado->TOTAL, 2) }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-600 font-secondary mt-1">
                        {{ count($detallePagoCon) }} {{ count($detallePagoCon) == 1 ? 'forma de pago' : 'formas de pago' }}
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

    </div>

</div>