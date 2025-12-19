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

    <!-- Gráfico de Barras: Ventas y Compras -->
    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
        <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-chart-bar text-orange-500 mr-3"></i>
            Ventas y Compras por Día
        </h3>
        <div class="relative" style="height: 350px;"
             data-ventas="{{ json_encode($ventasPorDia) }}"
             data-compras="{{ json_encode($comprasPorDia) }}">
            <canvas id="chartVentasComprasResto"></canvas>
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

        <!-- Ventas por Departamento - Tabla -->
        @if(count($ventasPorDepartamento) > 0)
        <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
            <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-utensils text-purple-500 mr-3"></i>
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
    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
        <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-chart-bar text-purple-500 mr-3"></i>
            Distribución de Ventas por Departamento
        </h3>
        <div class="relative" style="height: 300px;"
             data-departamentos="{{ json_encode($ventasPorDepartamento) }}">
            <canvas id="chartDepartamentosResto"></canvas>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('livewire:init', () => {
        let chartVentasCompras = null;
        let chartDepartamentos = null;

        const renderCharts = () => {
            // Gráfico de Ventas y Compras
            const ctxVentas = document.getElementById('chartVentasComprasResto');
            if (ctxVentas) {
                if (chartVentasCompras) {
                    chartVentasCompras.destroy();
                }

                // Leer datos desde los atributos data
                const container = ctxVentas.parentElement;
                const ventasPorDia = JSON.parse(container.getAttribute('data-ventas') || '[]');
                const comprasPorDia = JSON.parse(container.getAttribute('data-compras') || '[]');

                console.log('Ventas por día:', ventasPorDia);
                console.log('Compras por día:', comprasPorDia);

                const todasFechas = [...new Set([
                    ...ventasPorDia.map(v => v.fecha),
                    ...comprasPorDia.map(c => c.fecha)
                ])].sort();

                console.log('Todas las fechas:', todasFechas);

                const ventasData = todasFechas.map(fecha => {
                    const item = ventasPorDia.find(v => v.fecha === fecha);
                    return item ? item.total : 0;
                });

                const comprasData = todasFechas.map(fecha => {
                    const item = comprasPorDia.find(c => c.fecha === fecha);
                    return item ? item.total : 0;
                });

                console.log('Datos de ventas para gráfico:', ventasData);
                console.log('Datos de compras para gráfico:', comprasData);

                chartVentasCompras = new Chart(ctxVentas, {
                    type: 'bar',
                    data: {
                        labels: todasFechas,
                        datasets: [
                            {
                                label: 'Ventas',
                                data: ventasData,
                                backgroundColor: 'rgba(34, 197, 94, 0.8)',
                                borderColor: 'rgb(34, 197, 94)',
                                borderWidth: 1
                            },
                            {
                                label: 'Compras',
                                data: comprasData,
                                backgroundColor: 'rgba(239, 68, 68, 0.8)',
                                borderColor: 'rgb(239, 68, 68)',
                                borderWidth: 1
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
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += '$' + context.parsed.y.toLocaleString();
                                        return label;
                                    }
                                }
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
            }

            // Gráfico de Departamentos
            const ctxDeptos = document.getElementById('chartDepartamentosResto');
            if (ctxDeptos) {
                if (chartDepartamentos) {
                    chartDepartamentos.destroy();
                }

                // Leer datos desde los atributos data
                const containerDeptos = ctxDeptos.parentElement;
                const departamentos = JSON.parse(containerDeptos.getAttribute('data-departamentos') || '[]');

                console.log('Departamentos:', departamentos);

                if (departamentos && departamentos.length > 0) {
                    const labels = departamentos.map(d => d.departamento);
                    const data = departamentos.map(d => d.total);

                    chartDepartamentos = new Chart(ctxDeptos, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Ventas por Departamento',
                                data: data,
                                backgroundColor: 'rgba(147, 51, 234, 0.8)',
                                borderColor: 'rgb(147, 51, 234)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return '$' + context.parsed.x.toLocaleString();
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
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
                }
            }
        };

        // Renderizar al cargar
        renderCharts();

        // Re-renderizar cuando se actualicen los datos
        Livewire.on('datos-actualizados', () => {
            setTimeout(renderCharts, 200);
        });
    });
</script>
@endpush
