<div class="space-y-6">
    <!-- Filtros de Fecha -->
    <div class="bg-white rounded-xl shadow-md p-4 border border-gray-100">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h3 class="text-lg font-primary font-semibold text-gray-800 flex items-center">
                <i class="fas fa-calendar-alt text-orange-500 mr-3"></i>
                Filtros de Período
            </h3>
            <div class="flex flex-col sm:flex-row gap-3">
                <div>
                    <label class="block text-xs font-secondary text-gray-600 mb-1">Desde</label>
                    <input type="date" wire:model.live="fechaDesde"
                           class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                </div>
                <div>
                    <label class="block text-xs font-secondary text-gray-600 mb-1">Hasta</label>
                    <input type="date" wire:model.live="fechaHasta"
                           class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                </div>
                <div class="flex items-end">
                    <button wire:click="cargarDatos" wire:loading.attr="disabled"
                            class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 transition-colors">
                        <i class="fas fa-sync-alt mr-2" wire:loading.class="animate-spin"></i>
                        <span wire:loading.remove>Actualizar</span>
                        <span wire:loading>Cargando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

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
        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-5 border border-orange-200 shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-secondary text-orange-700 mb-1">Deudas a Pagar</p>
                    <p class="text-2xl font-primary font-bold text-orange-800">${{ number_format($deudasAPagar, 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-credit-card text-white text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de Tendencia -->
    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
        <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-chart-line text-orange-500 mr-3"></i>
            Tendencia de Ventas y Compras
        </h3>
        <div class="relative" style="height: 300px;">
            <canvas id="chartVentasComprasResto"></canvas>
        </div>
    </div>

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
                        <th class="px-4 py-3 text-center text-xs font-secondary font-semibold text-gray-600">Cantidad</th>
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

    <!-- Ventas por Departamento -->
    @if(count($ventasPorDepartamento) > 0)
    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
        <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-utensils text-orange-500 mr-3"></i>
            Ventas por Departamento
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($ventasPorDepartamento as $depto)
            <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-4 border border-orange-200">
                <p class="text-sm font-secondary text-orange-700 mb-2">{{ $depto['departamento'] }}</p>
                <p class="text-xl font-primary font-bold text-orange-800">${{ number_format($depto['total'], 2) }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('livewire:init', () => {
        let chartInstance = null;

        const renderChart = () => {
            const ctx = document.getElementById('chartVentasComprasResto');
            if (!ctx) return;

            // Destruir gráfico anterior si existe
            if (chartInstance) {
                chartInstance.destroy();
            }

            const ventasPorDia = @json($ventasPorDia);
            const comprasPorDia = @json($comprasPorDia);

            // Combinar todas las fechas únicas
            const todasFechas = [...new Set([
                ...ventasPorDia.map(v => v.fecha),
                ...comprasPorDia.map(c => c.fecha)
            ])];

            // Crear arrays de datos alineados
            const ventasData = todasFechas.map(fecha => {
                const item = ventasPorDia.find(v => v.fecha === fecha);
                return item ? item.total : 0;
            });

            const comprasData = todasFechas.map(fecha => {
                const item = comprasPorDia.find(c => c.fecha === fecha);
                return item ? item.total : 0;
            });

            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: todasFechas,
                    datasets: [
                        {
                            label: 'Ventas',
                            data: ventasData,
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Compras',
                            data: comprasData,
                            borderColor: 'rgb(239, 68, 68)',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        };

        // Renderizar al cargar
        renderChart();

        // Re-renderizar cuando se actualicen los datos
        Livewire.on('datos-actualizados', () => {
            setTimeout(renderChart, 100);
        });
    });
</script>
@endpush
