<div class="space-y-6">
    <!-- Botón de actualización -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-primary font-thin text-gray-800">Dashboard Principal</h2>
        <button 
            wire:click="actualizarDatos"
            wire:loading.attr="disabled"
            class="flex items-center btn-primary transition-colors duration-200 disabled:opacity-50">
            <i class="fas fa-sync-alt mr-2" wire:loading.class="animate-spin"></i>
            <span wire:loading.remove>Actualizar</span>
            <span wire:loading>Actualizando...</span>
        </button>
    </div>

    <!-- Sección de Ventas -->
    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
        <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-chart-line text-green-500 mr-3"></i>
            Resumen de Ventas
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Ventas del Día -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 border border-green-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-secondary text-green-700">Ventas del Día</p>
                        <p class="text-xl font-primary font-bold text-green-800">${{ number_format($ventasDelDia, 2) }}</p>
                        <p class="text-xs font-secondary text-green-600">{{ date('d/m/Y') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-calendar-day text-white text-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Ventas del Mes -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-secondary text-blue-700">Ventas del Mes</p>
                        <p class="text-xl font-primary font-bold text-blue-800">${{ number_format($ventasDelMes, 2) }}</p>
                        <p class="text-xs font-secondary text-blue-600">{{ date('M Y') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-calendar-alt text-white text-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Ventas del Año -->
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 border border-purple-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-secondary text-purple-700">Ventas del Año</p>
                        <p class="text-xl font-primary font-bold text-purple-800">${{ number_format($ventasDelAno, 2) }}</p>
                        <p class="text-xs font-secondary text-purple-600">{{ date('Y') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-calendar text-white text-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de Deudas -->
    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
        <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-balance-scale text-yellow-500 mr-3"></i>
            Gestión de Deudas
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Deudas a Cobrar -->
            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-4 border border-yellow-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-secondary text-yellow-700">Deudas a Cobrar</p>
                        <p class="text-xl font-primary font-bold text-yellow-800">${{ number_format($deudasACobrar, 2) }}</p>
                        <p class="text-xs font-secondary text-yellow-600">Cuentas por cobrar</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-hand-holding-usd text-white text-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Deudas a Pagar -->
            <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-4 border border-red-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-secondary text-red-700">Deudas a Pagar</p>
                        <p class="text-xl font-primary font-bold text-red-800">${{ number_format($deudasAPagar, 2) }}</p>
                        <p class="text-xs font-secondary text-red-600">Cuentas por pagar</p>
                    </div>
                    <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-credit-card text-white text-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de Productos y Departamentos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Productos Más Vendidos -->
        <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
            <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-trophy text-orange-500 mr-3"></i>
                Top 10 Productos
            </h3>
            <div class="space-y-3">
                @forelse($productosMasVendidos as $index => $producto)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-gradient-to-br from-orange-400 to-orange-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold text-sm">{{ $index + 1 }}</span>
                            </div>
                            <div>
                                <p class="font-secondary font-semibold text-gray-800 text-sm">{{ $producto['nombre'] }}</p>
                                <p class="font-secondary text-gray-600 text-xs">ID: {{ $producto['producto_id'] }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-primary font-bold text-gray-800">{{ $producto['cantidad'] }}</p>
                            <p class="font-secondary text-gray-600 text-xs">${{ number_format($producto['total'], 2) }}</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <i class="fas fa-box-open text-gray-400 text-3xl mb-3"></i>
                        <p class="text-gray-500 font-secondary">No hay productos vendidos este mes</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Ventas por Departamento -->
        <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
            <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-layer-group text-indigo-500 mr-3"></i>
                Ventas por Departamento
            </h3>
            <div class="space-y-3">
                @forelse($ventasPorDepartamento as $departamento)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-indigo-400 to-indigo-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-tags text-white"></i>
                            </div>
                            <div>
                                <p class="font-secondary font-semibold text-gray-800 text-sm">{{ $departamento['departamento'] }}</p>
                                <p class="font-secondary text-gray-600 text-xs">{{ $departamento['cantidad_ventas'] }} ventas</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-primary font-bold text-gray-800">${{ number_format($departamento['total'], 2) }}</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <i class="fas fa-layer-group text-gray-400 text-3xl mb-3"></i>
                        <p class="text-gray-500 font-secondary">No hay datos de departamentos</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Indicador de última actualización -->
    <div class="text-center">
        <p class="text-xs font-secondary text-gray-500">
            <i class="fas fa-clock mr-1"></i>
            Última actualización: {{ date('d/m/Y H:i:s') }}
        </p>
    </div>
</div>