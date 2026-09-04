<div class="space-y-6">
    <!-- Filtros de Fecha -->
    <div class="bg-white rounded-xl shadow-md p-4 border border-gray-100">
        <div class="flex flex-col gap-4">
            <h3 class="text-lg font-primary font-semibold text-gray-800 flex items-center">
                <i class="fas fa-calendar-alt text-[#FFAF22] mr-3"></i>
                Filtros de Período
            </h3>
            <div class="flex flex-row gap-2 items-end">
                <div class="flex-1">
                    <label class="block text-xs font-secondary text-gray-600 mb-1">Desde</label>
                    <input type="date" wire:model.live.debounce.500ms="fechaDesde"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FFAF22] focus:border-[#FFAF22]">
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-secondary text-gray-600 mb-1">Hasta</label>
                    <input type="date" wire:model.live.debounce.500ms="fechaHasta"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FFAF22] focus:border-[#FFAF22]">
                </div>
                <div>
                    <button wire:click="cargarDatos" wire:loading.attr="disabled"
                            class="btn-primary px-4 py-2 rounded-lg text-sm disabled:opacity-50 whitespace-nowrap">
                        <i class="fas fa-sync-alt mr-2" wire:loading.class="animate-spin"></i>
                        <span class="hidden sm:inline" wire:loading.remove>Actualizar</span>
                        <span class="sm:hidden" wire:loading.remove><i class="fas fa-sync-alt"></i></span>
                        <span wire:loading>...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tienda web: estado de cuenta de comisiones (solo clientes con tienda y comisión activa) --}}
    @if($tieneComisiones)
        @include('panel.partials.comisiones-tienda')
    @endif

    <!-- KPIs Principales -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Ventas del Período -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-5 border border-green-200 shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-secondary text-green-700 mb-1">Ventas del Período</p>
                    <p class="text-2xl font-primary font-bold text-green-800">${{ number_format($ventasDelPeriodo, 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-dollar-sign text-white text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Compras del Período -->
        <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-5 border border-red-200 shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-secondary text-red-700 mb-1">Compras del Período</p>
                    <p class="text-2xl font-primary font-bold text-red-800">${{ number_format($comprasDelPeriodo, 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-shopping-cart text-white text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Deudas a Cobrar -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-5 border border-blue-200 shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-secondary text-blue-700 mb-1">Deudas a Cobrar</p>
                    <p class="text-2xl font-primary font-bold text-blue-800">${{ number_format($deudasACobrar, 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-hand-holding-usd text-white text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Deudas a Pagar -->
        <div class="bg-white rounded-xl p-5 border-l-4 border-[#FFAF22] shadow-md border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-secondary text-gray-600 mb-1">Deudas a Pagar</p>
                    <p class="text-2xl font-primary font-bold text-[#FFAF22]">${{ number_format($deudasAPagar, 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-[#FFAF22] rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-credit-card text-white text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen Financiero -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Sueldos del Período -->
        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-5 border border-orange-200 shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-secondary text-orange-700 mb-1">Sueldos del Período</p>
                    <p class="text-2xl font-primary font-bold text-orange-800">${{ number_format($sueldosDelPeriodo, 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-users text-white text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Ganancia Neta -->
        <div class="bg-gradient-to-br {{ $gananciaNeta >= 0 ? 'from-emerald-50 to-emerald-100 border-emerald-200' : 'from-rose-50 to-rose-100 border-rose-200' }} rounded-xl p-5 border shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-secondary {{ $gananciaNeta >= 0 ? 'text-emerald-700' : 'text-rose-700' }} mb-1">Ganancia Neta</p>
                    <p class="text-2xl font-primary font-bold {{ $gananciaNeta >= 0 ? 'text-emerald-800' : 'text-rose-800' }}">${{ number_format($gananciaNeta, 2) }}</p>
                </div>
                <div class="w-12 h-12 {{ $gananciaNeta >= 0 ? 'bg-emerald-500' : 'bg-rose-500' }} rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas {{ $gananciaNeta >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }} text-white text-xl"></i>
                </div>
            </div>
        </div>

        <!-- % Utilidad Global -->
        <div class="bg-gradient-to-br {{ $utilidadGlobal >= 0 ? 'from-indigo-50 to-indigo-100 border-indigo-200' : 'from-rose-50 to-rose-100 border-rose-200' }} rounded-xl p-5 border shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-secondary {{ $utilidadGlobal >= 0 ? 'text-indigo-700' : 'text-rose-700' }} mb-1">% Utilidad Global</p>
                    <p class="text-2xl font-primary font-bold {{ $utilidadGlobal >= 0 ? 'text-indigo-800' : 'text-rose-800' }}">{{ number_format($utilidadGlobal, 2) }}%</p>
                </div>
                <div class="w-12 h-12 {{ $utilidadGlobal >= 0 ? 'bg-indigo-500' : 'bg-rose-500' }} rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-percentage text-white text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Botón Estado de Resultado -->
    <div class="flex justify-center">
        <button wire:click="verEstadoResultado"
                class="inline-flex items-center gap-3 bg-gradient-to-r from-[#222036] to-[#2d2a4a] hover:from-[#2d2a4a] hover:to-[#222036] text-white font-semibold px-6 py-3 rounded-xl shadow-lg hover:shadow-xl transition-all">
            <i class="fas fa-chart-pie text-[#FFAF22] text-lg"></i>
            <span>Ver Estado de Resultado completo</span>
            <i class="fas fa-arrow-right text-sm"></i>
        </button>
    </div>

    <!-- Gráfico de Barras: Ventas y Compras -->
    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100"
         x-data="inicioChartVentas()"
         x-init="render()">
        <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-chart-bar text-[#FFAF22] mr-3"></i>
            Ventas y Compras por Día
        </h3>
        <div id="inicioVentasData"
             data-ventas="{{ json_encode($ventasPorDia) }}"
             data-compras="{{ json_encode($comprasPorDia) }}"
             style="display:none;"></div>
        <div class="relative" style="height: 350px;" wire:ignore>
            <canvas x-ref="chartVentasCompras"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Productos Más Vendidos -->
        @if(count($productosMasVendidos) > 0)
        <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
            <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-star text-yellow-500 mr-3"></i>
                Productos Más Vendidos
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-secondary font-semibold text-gray-600">Producto</th>
                            <th class="px-4 py-3 text-center text-xs font-secondary font-semibold text-gray-600">Cant.</th>
                            <th class="px-4 py-3 text-right text-xs font-secondary font-semibold text-gray-600">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($productosMasVendidos as $producto)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-secondary text-gray-800">{{ $producto['nombre'] }}</td>
                            <td class="px-4 py-3 text-sm font-secondary text-center text-gray-600">{{ number_format($producto['cantidad']) }}</td>
                            <td class="px-4 py-3 text-sm font-secondary text-right font-semibold text-gray-800">${{ number_format($producto['total'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Ventas por Departamento - Tabla -->
        @if(count($ventasPorDepartamento) > 0)
        <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
            <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-sitemap text-purple-500 mr-3"></i>
                Ventas por Departamento
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-secondary font-semibold text-gray-600">Departamento</th>
                            <th class="px-4 py-3 text-right text-xs font-secondary font-semibold text-gray-600">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($ventasPorDepartamento as $depto)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-secondary text-gray-800">{{ $depto['departamento'] }}</td>
                            <td class="px-4 py-3 text-sm font-secondary text-right font-semibold text-purple-800">${{ number_format($depto['total'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <!-- Gráfico de Departamentos -->
    @if(count($ventasPorDepartamento) > 0)
    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100"
         x-data="inicioChartDeptos()"
         x-init="render()">
        <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-chart-bar text-purple-500 mr-3"></i>
            Distribución de Ventas por Departamento
        </h3>
        <div id="inicioDeptosData"
             data-deptos="{{ json_encode($ventasPorDepartamento) }}"
             style="display:none;"></div>
        <div class="relative" style="height: 300px;" wire:ignore>
            <canvas x-ref="chartDeptos"></canvas>
        </div>
    </div>
    @endif
</div>
