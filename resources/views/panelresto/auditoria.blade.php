<div class="space-y-6" x-data="{ filtrosAbiertos: window.innerWidth >= 640, tipoSeleccionado: @entangle('tipoFiltro') }">
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
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-4">
                <!-- Fecha Desde -->
                <div>
                    <label for="fechaDesde" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">
                        <i class="fas fa-calendar-alt mr-1"></i>Desde
                    </label>
                    <input type="date"
                           wire:model="fechaDesde"
                           id="fechaDesde"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Fecha Hasta -->
                <div>
                    <label for="fechaHasta" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">
                        <i class="fas fa-calendar-alt mr-1"></i>Hasta
                    </label>
                    <input type="date"
                           wire:model="fechaHasta"
                           id="fechaHasta"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Tipo -->
                <div>
                    <label for="tipoFiltro" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">
                        <i class="fas fa-tag mr-1"></i>Tipo
                    </label>
                    <select wire:model="tipoFiltro"
                            id="tipoFiltro"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($tiposAuditoria as $key => $tipo)
                            <option value="{{ $key }}">{{ $tipo }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Usuario -->
                <div>
                    <label for="usuarioFiltro" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">
                        <i class="fas fa-user mr-1"></i>Usuario
                    </label>
                    <input type="text"
                           wire:model="usuarioFiltro"
                           id="usuarioFiltro"
                           placeholder="Usuario..."
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Mesa -->
                <div>
                    <label for="mesaFiltro" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">
                        <i class="fas fa-table mr-1"></i>Mesa
                    </label>
                    <input type="number"
                           wire:model="mesaFiltro"
                           id="mesaFiltro"
                           placeholder="Nº Mesa..."
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Pedido -->
                <div>
                    <label for="pedidoFiltro" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">
                        <i class="fas fa-receipt mr-1"></i>Pedido
                    </label>
                    <input type="number"
                           wire:model="pedidoFiltro"
                           id="pedidoFiltro"
                           placeholder="Nº Pedido..."
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Comandado (solo visible si tipo es 4 u 8) -->
                <div x-show="tipoSeleccionado == 4 || tipoSeleccionado == 8">
                    <label for="comandadoFiltro" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">
                        <i class="fas fa-clipboard-check mr-1"></i>Estado
                    </label>
                    <select wire:model="comandadoFiltro"
                            id="comandadoFiltro"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="CC">Comandado (CC)</option>
                        <option value="SC">Sin Comandar (SC)</option>
                    </select>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex gap-2">
                <button wire:click="aplicarFiltros"
                        class="btn-primary flex-1 sm:flex-none text-sm">
                    <i class="fas fa-filter mr-1"></i><span class="hidden sm:inline">Aplicar Filtros</span><span class="sm:hidden">Aplicar</span>
                </button>
                <button wire:click="limpiarFiltros"
                        class="px-3 sm:px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition duration-200 flex-1 sm:flex-none text-sm">
                    <i class="fas fa-eraser mr-1"></i><span class="hidden sm:inline">Limpiar</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Tabla de Auditoría -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800">
                <i class="fas fa-clipboard-list mr-2"></i>Registro de Auditoría
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
                            Tipo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Descripción
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Mesa
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Pedido
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Usuario
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($registros as $registro)
                        <tr class="hover:bg-gray-50 transition duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $registro->fecha_formateada }}</div>
                                <div class="text-xs text-gray-500">{{ $registro->hora_formateada }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @php
                                        $colors = [
                                            1 => 'bg-green-100 text-green-800',
                                            2 => 'bg-red-100 text-red-800',
                                            3 => 'bg-purple-100 text-purple-800',
                                            4 => 'bg-orange-100 text-orange-800',
                                            5 => 'bg-red-200 text-red-900',
                                            6 => 'bg-blue-100 text-blue-800',
                                            7 => 'bg-indigo-100 text-indigo-800',
                                            8 => 'bg-yellow-100 text-yellow-800',
                                            9 => 'bg-pink-100 text-pink-800',
                                            10 => 'bg-rose-100 text-rose-800',
                                            11 => 'bg-emerald-100 text-emerald-800',
                                            12 => 'bg-cyan-100 text-cyan-800',
                                            13 => 'bg-amber-100 text-amber-800',
                                            14 => 'bg-red-100 text-red-800',
                                            15 => 'bg-purple-200 text-purple-900',
                                            16 => 'bg-teal-100 text-teal-800',
                                            17 => 'bg-lime-100 text-lime-800',
                                            18 => 'bg-fuchsia-100 text-fuchsia-800',
                                        ];
                                        echo $colors[$registro->TIPO] ?? 'bg-gray-100 text-gray-800';
                                    @endphp">
                                    {{ $registro->tipo_descripcion }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $registro->DESCRIPCION }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($registro->MESA)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $registro->MESA }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($registro->PEDIDO)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        {{ $registro->PEDIDO }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $registro->USUARIO }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <i class="fas fa-search text-4xl mb-4"></i>
                                    <p class="text-lg">No se encontraron registros</p>
                                    <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Vista Móvil/Tablet (tarjetas) -->
        <div class="lg:hidden divide-y divide-gray-200">
            @forelse($registros as $registro)
                <div class="p-4 hover:bg-gray-50 transition duration-200">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mb-2
                                @php
                                    $colors = [
                                        1 => 'bg-green-100 text-green-800',
                                        2 => 'bg-red-100 text-red-800',
                                        3 => 'bg-purple-100 text-purple-800',
                                        4 => 'bg-orange-100 text-orange-800',
                                        5 => 'bg-red-200 text-red-900',
                                        6 => 'bg-blue-100 text-blue-800',
                                        7 => 'bg-indigo-100 text-indigo-800',
                                        8 => 'bg-yellow-100 text-yellow-800',
                                        9 => 'bg-pink-100 text-pink-800',
                                        10 => 'bg-rose-100 text-rose-800',
                                        11 => 'bg-emerald-100 text-emerald-800',
                                        12 => 'bg-cyan-100 text-cyan-800',
                                        13 => 'bg-amber-100 text-amber-800',
                                        14 => 'bg-red-100 text-red-800',
                                        15 => 'bg-purple-200 text-purple-900',
                                        16 => 'bg-teal-100 text-teal-800',
                                        17 => 'bg-lime-100 text-lime-800',
                                        18 => 'bg-fuchsia-100 text-fuchsia-800',
                                    ];
                                    echo $colors[$registro->TIPO] ?? 'bg-gray-100 text-gray-800';
                                @endphp">
                                {{ $registro->tipo_descripcion }}
                            </span>
                        </div>
                        <div class="text-right ml-2">
                            <div class="text-xs text-gray-500">{{ $registro->fecha_formateada }}</div>
                            <div class="text-xs text-gray-500">{{ $registro->hora_formateada }}</div>
                        </div>
                    </div>
                    <div class="text-sm text-gray-900 mb-2">{{ $registro->DESCRIPCION }}</div>
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="text-gray-600">
                            <i class="fas fa-user mr-1"></i>{{ $registro->USUARIO }}
                        </span>
                        @if($registro->MESA)
                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-blue-100 text-blue-800">
                                <i class="fas fa-table mr-1"></i>Mesa: {{ $registro->MESA }}
                            </span>
                        @endif
                        @if($registro->PEDIDO)
                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-green-100 text-green-800">
                                <i class="fas fa-receipt mr-1"></i>Pedido: {{ $registro->PEDIDO }}
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-4 py-12 text-center text-gray-500">
                    <i class="fas fa-search text-4xl mb-4"></i>
                    <p class="text-base">No se encontraron registros</p>
                    <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                </div>
            @endforelse
        </div>

        <!-- Paginación -->
        @if($paginationInfo->last_page > 1)
            <div class="px-4 sm:px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs sm:text-sm text-gray-700 text-center sm:text-left">
                        Mostrando {{ $paginationInfo->from }} a {{ $paginationInfo->to }} de {{ $paginationInfo->total }} registros
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
