<div class="flex h-screen bg-gray-50">
    <!-- Panel Principal -->
    <div class="{{ $mostrarPanel ? 'w-2/3' : 'w-full' }} transition-all duration-300 overflow-hidden">
        <div class="container mx-auto px-4 py-6 h-full overflow-y-auto">
            <!-- Encabezado -->
            <div class="mb-6">
                <h1 class="text-2xl font-primary font-bold text-gray-900 mb-2">
                    <i class="fas fa-cash-register text-orange-500 mr-2"></i>
                    Gestión de Cajas
                </h1>
                <p class="text-gray-600 font-secondary">Consulta de turnos de caja cerrados y sus movimientos</p>
            </div>

            <!-- Tarjetas de Resumen -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100">
                            <i class="fas fa-calculator text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-secondary font-medium text-gray-600">Total Cajas</p>
                            <p class="text-2xl font-primary font-bold text-gray-900">{{ number_format($totalCajas) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-secondary font-medium text-gray-600">Faltantes</p>
                            <p class="text-2xl font-primary font-bold text-red-600">${{ number_format($totalFaltantes, 2) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100">
                            <i class="fas fa-plus-circle text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-secondary font-medium text-gray-600">Sobrantes</p>
                            <p class="text-2xl font-primary font-bold text-blue-600">${{ number_format($totalSobrantes, 2) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100">
                            <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-secondary font-medium text-gray-600">Total Efectivo</p>
                            <p class="text-2xl font-primary font-bold text-green-600">${{ number_format($totalEfectivo, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-primary font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-filter text-orange-500 mr-2"></i>
                    Filtros de Búsqueda
                </h2>
            </div>
            <!-- Filtros -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                    <!-- Búsqueda -->
                    <div class="xl:col-span-1">
                        <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Buscar Caja</label>
                        <div class="relative">
                            <input type="text" 
                                   wire:model.live.debounce.500ms="buscar" 
                                   placeholder="Número de caja..."
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 font-secondary text-sm">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>

                    <!-- Fecha Desde -->
                    <div>
                        <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Fecha Desde</label>
                        <input type="date" 
                               wire:model.live="fechaDesde"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 font-secondary text-sm">
                    </div>

                    <!-- Fecha Hasta -->
                    <div>
                        <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Fecha Hasta</label>
                        <input type="date" 
                               wire:model.live="fechaHasta"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 font-secondary text-sm">
                    </div>

                    <!-- Caja -->
                    <div>
                        <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Número de Caja</label>
                        <select wire:model.live="cajaSelecionada" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 font-secondary text-sm">
                            <option value="">Todas las cajas</option>
                            @foreach($numerosCaja as $numero)
                                <option value="{{ $numero }}">Caja {{ $numero }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Tipo de Saldo -->
                    <div>
                        <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Tipo de Saldo</label>
                        <select wire:model.live="tipoSaldo" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 font-secondary text-sm">
                            <option value="">Todos</option>
                            <option value="faltante">Con Faltante</option>
                            <option value="sobrante">Con Sobrante</option>
                            <option value="exacto">Exacto</option>
                        </select>
                    </div>

                    <!-- Botón Limpiar -->
                    <div class="flex items-end">
                        <button wire:click="limpiarFiltros" 
                                class="w-full px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 font-secondary text-sm">
                            <i class="fas fa-eraser mr-2"></i>
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla de Cajas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <!-- Controles de la tabla -->
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <label class="text-sm font-secondary font-medium text-gray-700">Mostrar:</label>
                                <select wire:model.live="porPagina" 
                                        class="border border-gray-300 rounded-lg px-3 py-1 text-sm font-secondary focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="200">200</option>
                                </select>
                                <span class="text-sm font-secondary text-gray-700">registros</span>
                            </div>
                        </div>

                        @if($cajas->hasPages())
                        <div class="text-sm font-secondary text-gray-700">
                            Mostrando {{ $cajas->firstItem() }} a {{ $cajas->lastItem() }} de {{ $cajas->total() }} resultados
                        </div>
                        @endif
                    </div>
                </div>

                @if($cajas->count() > 0)
                <!-- Tabla -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-center">
                                    <span class="text-xs font-secondary font-semibold text-gray-700 uppercase tracking-wider">Acciones</span>
                                </th>
                                <th class="px-6 py-3 text-left">
                                    <button wire:click="ordenar('fecha')" 
                                            class="flex items-center space-x-1 text-xs font-secondary font-semibold text-gray-700 uppercase tracking-wider hover:text-orange-600 transition-colors duration-200">
                                        <span>Fecha</span>
                                        @if($ordenarPor === 'fecha')
                                            <i class="fas fa-sort-{{ $ordenarDireccion === 'asc' ? 'up' : 'down' }} text-orange-500"></i>
                                        @else
                                            <i class="fas fa-sort text-gray-400"></i>
                                        @endif
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-left">
                                    <button wire:click="ordenar('hora')" 
                                            class="flex items-center space-x-1 text-xs font-secondary font-semibold text-gray-700 uppercase tracking-wider hover:text-orange-600 transition-colors duration-200">
                                        <span>Hora</span>
                                        @if($ordenarPor === 'hora')
                                            <i class="fas fa-sort-{{ $ordenarDireccion === 'asc' ? 'up' : 'down' }} text-orange-500"></i>
                                        @else
                                            <i class="fas fa-sort text-gray-400"></i>
                                        @endif
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-center">
                                    <button wire:click="ordenar('caja')" 
                                            class="flex items-center space-x-1 text-xs font-secondary font-semibold text-gray-700 uppercase tracking-wider hover:text-orange-600 transition-colors duration-200">
                                        <span>Caja</span>
                                        @if($ordenarPor === 'caja')
                                            <i class="fas fa-sort-{{ $ordenarDireccion === 'asc' ? 'up' : 'down' }} text-orange-500"></i>
                                        @else
                                            <i class="fas fa-sort text-gray-400"></i>
                                        @endif
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-center">
                                    <span class="text-xs font-secondary font-semibold text-gray-700 uppercase tracking-wider">Estado</span>
                                </th>
                                <th class="px-6 py-3 text-right">
                                    <button wire:click="ordenar('saldo')" 
                                            class="flex items-center space-x-1 text-xs font-secondary font-semibold text-gray-700 uppercase tracking-wider hover:text-orange-600 transition-colors duration-200">
                                        <span>Diferencia</span>
                                        @if($ordenarPor === 'saldo')
                                            <i class="fas fa-sort-{{ $ordenarDireccion === 'asc' ? 'up' : 'down' }} text-orange-500"></i>
                                        @else
                                            <i class="fas fa-sort text-gray-400"></i>
                                        @endif
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($cajas as $caja)
                            <tr class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button wire:click="verDetalleCaja({{ $caja->id }})" 
                                            class="inline-flex items-center px-3 py-1.5 border border-orange-300 rounded-lg text-sm font-secondary font-medium text-orange-700 bg-orange-50 hover:bg-orange-100 hover:border-orange-400 transition-colors duration-200">
                                        <i class="fas fa-eye mr-1"></i>
                                        Ver Detalle
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-secondary font-medium text-gray-900">
                                        {{ \Carbon\Carbon::parse($caja->fecha)->format('d/m/Y') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-secondary text-gray-600">
                                        {{ $caja->hora ? \Carbon\Carbon::parse($caja->hora)->format('H:i') : '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-secondary font-medium bg-blue-100 text-blue-800">
                                        Caja {{ $caja->caja }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($caja->saldo > 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-secondary font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            Faltante
                                        </span>
                                    @elseif($caja->saldo < 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-secondary font-medium bg-blue-100 text-blue-800">
                                            <i class="fas fa-plus-circle mr-1"></i>
                                            Sobrante
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-secondary font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Exacto
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-secondary {{ $this->getClaseSaldo($caja->saldo) }}">
                                        {{ $this->getTipoSaldo($caja->saldo) }}
                                    </div>
                                </td>                                
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                @if($cajas->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-secondary text-gray-700">
                            Mostrando {{ $cajas->firstItem() }} a {{ $cajas->lastItem() }} de {{ $cajas->total() }} resultados
                        </div>
                        
                        <nav class="flex items-center space-x-2">
                            {{-- Botón Primera Página --}}
                            @if($cajas->currentPage() > 3)
                                <button wire:click="gotoPage(1)" 
                                        class="px-3 py-2 text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                                    <i class="fas fa-angle-double-left"></i>
                                </button>
                            @endif

                            {{-- Botón Anterior --}}
                            @if($cajas->onFirstPage())
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
                                $start = max(1, $cajas->currentPage() - 2);
                                $end = min($cajas->lastPage(), $cajas->currentPage() + 2);
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
                                @if($page == $cajas->currentPage())
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

                            @if($end < $cajas->lastPage())
                                @if($end < $cajas->lastPage() - 1)
                                    <span class="px-2 py-2 text-sm font-secondary text-gray-400">...</span>
                                @endif
                                <button wire:click="gotoPage({{ $cajas->lastPage() }})" 
                                        class="px-3 py-2 text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                                    {{ $cajas->lastPage() }}
                                </button>
                            @endif

                            {{-- Botón Siguiente --}}
                            @if($cajas->hasMorePages())
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
                            @if($cajas->currentPage() < $cajas->lastPage() - 2)
                                <button wire:click="gotoPage({{ $cajas->lastPage() }})" 
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
                    <i class="fas fa-cash-register text-gray-400 text-5xl mb-4"></i>
                    <h3 class="text-lg font-primary font-medium text-gray-900 mb-2">No hay cajas</h3>
                    <p class="text-gray-600 font-secondary">No se encontraron turnos de caja con los filtros seleccionados</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Panel Lateral -->
    @if($mostrarPanel)
    <div class="w-1/3 bg-white border-l border-gray-200 shadow-lg overflow-hidden">
        <div class="h-full flex flex-col">
            <!-- Header del Panel -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-primary font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-cash-register text-orange-500 mr-2"></i>
                    Detalle Caja {{ $cajaSeleccionada->caja ?? '' }}
                </h3>
                <button wire:click="cerrarPanel" class="text-gray-400 hover:text-gray-600 p-1">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Contenido del Panel -->
            <div class="flex-1 overflow-y-auto p-4">
                @if($cajaSeleccionada)
                <!-- Información de la Caja -->
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <div class="grid grid-cols-1 gap-3">
                        <div>
                            <span class="text-sm font-secondary font-medium text-gray-600">Fecha:</span>
                            <span class="text-sm font-secondary text-gray-900 ml-2">
                                {{ \Carbon\Carbon::parse($cajaSeleccionada->fecha)->format('d/m/Y') }}
                            </span>
                        </div>
                        @if($cajaSeleccionada->hora)
                        <div>
                            <span class="text-sm font-secondary font-medium text-gray-600">Hora:</span>
                            <span class="text-sm font-secondary text-gray-900 ml-2">
                                {{ \Carbon\Carbon::parse($cajaSeleccionada->hora)->format('H:i') }}
                            </span>
                        </div>
                        @endif
                        <div>
                            <span class="text-sm font-secondary font-medium text-gray-600">Estado:</span>
                            <span class="text-sm font-secondary ml-2 {{ $this->getClaseSaldo($cajaSeleccionada->saldo) }}">
                                {{ $this->getTipoSaldo($cajaSeleccionada->saldo) }}
                            </span>
                        </div>
                        <div>
                            <span class="text-sm font-secondary font-medium text-gray-600">Total Efectivo:</span>
                            <span class="text-sm font-secondary font-bold text-green-600 ml-2">
                                ${{ number_format($this->calcularTotalDetalle($detallesCaja, 'efectivo'), 2) }}
                            </span>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Movimientos -->
                <div class="space-y-3">
                    <h4 class="text-md font-primary font-semibold text-gray-900 border-b border-gray-200 pb-2">
                        Movimientos
                    </h4>
                    
                    @if($detallesCaja && count($detallesCaja) > 0)
                        @foreach($detallesCaja as $detalle)
                        <div class="bg-white border border-gray-200 rounded-lg p-3 hover:shadow-sm transition-shadow duration-200">
                            <div class="flex justify-between items-start mb-2">
                                <h5 class="text-sm font-secondary font-medium text-gray-900">{{ $detalle->concepto }}</h5>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                @if($detalle->debe > 0)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Debe:</span>
                                    <span class="font-medium text-green-600">${{ number_format($detalle->debe, 2) }}</span>
                                </div>
                                @endif
                                
                                @if($detalle->haber > 0)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Haber:</span>
                                    <span class="font-medium text-red-600">${{ number_format($detalle->haber, 2) }}</span>
                                </div>
                                @endif
                                
                                @if($detalle->efectivo != 0)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Efectivo:</span>
                                    <span class="font-medium {{ $detalle->efectivo > 0 ? 'text-blue-600' : 'text-orange-600' }}">
                                        ${{ number_format($detalle->efectivo, 2) }}
                                    </span>
                                </div>
                                @endif
                                
                                @php
                                    if($detalle->concepto != '**VENTAS TOTALES TURNO**') {
                                        $total = ($detalle->debe ?? 0) - ($detalle->haber ?? 0);
                                    }
                                @endphp
                                @if($total != 0)
                                <div class="flex justify-between col-span-2 pt-1 border-t border-gray-100">
                                    <span class="text-gray-600 font-medium">Total:</span>
                                    <span class="font-bold {{ $total > 0 ? 'text-green-600' : 'text-red-600' }}">
                                        ${{ number_format($total, 2) }}
                                    </span>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                        
                        <!-- Totales -->
                        <div class="bg-gray-100 border border-gray-300 rounded-lg p-3 mt-4">
                            <h5 class="text-sm font-secondary font-bold text-gray-900 mb-2">TOTALES GENERALES</h5>
                            <div class="grid grid-cols-1 gap-1 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Debe:</span>
                                    <span class="font-bold text-green-600">
                                        ${{ number_format($this->calcularTotalDetalle($detallesCaja, 'debe'), 2) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Haber:</span>
                                    <span class="font-bold text-red-600">
                                        ${{ number_format($this->calcularTotalDetalle($detallesCaja, 'haber'), 2) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Efectivo:</span>
                                    <span class="font-bold text-blue-600">
                                        ${{ number_format($this->calcularTotalDetalle($detallesCaja, 'efectivo'), 2) }}
                                    </span>
                                </div>
                                @php
                                    $totalGeneral = $this->calcularTotalDetalle($detallesCaja, 'debe') - $this->calcularTotalDetalle($detallesCaja, 'haber');
                                @endphp
                                <div class="flex justify-between pt-1 border-t border-gray-300">
                                    <span class="text-gray-900 font-bold">Diferencia:</span>
                                    <span class="font-bold {{ $totalGeneral > 0 ? 'text-green-600' : ($totalGeneral < 0 ? 'text-red-600' : 'text-gray-600') }}">
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-triangle text-gray-400 text-2xl mb-2"></i>
                            <p class="text-gray-600 font-secondary text-sm">No hay movimientos registrados</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Footer del Panel -->
            <div class="border-t border-gray-200 p-4 bg-gray-50">
                <button wire:click="cerrarPanel" 
                        class="w-full px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors duration-200 font-secondary text-sm">
                    <i class="fas fa-times mr-1"></i>
                    Cerrar Panel
                </button>
            </div>
        </div>
    </div>
    @endif
</div>