<div class="px-1 pb-8">
    <!-- Bienvenida -->
    <div class="bg-[#222036] text-white rounded-lg p-4 mb-4 mt-3 shadow-md">
        <h2 class="text-lg font-semibold">HISTORIAL DE {{session()->get('vendedor_nombre')}}</h2>
        <p class="text-sm">Estos son tus pedidos.</p>
    </div>    
    <div x-data="{ mostrarFiltros: true }">
        <!-- Botón compacto para filtros -->

        <div class="bg-white">
            <div class="mb-4 flex justify-end">
                <button @click="mostrarFiltros = !mostrarFiltros" 
                        class="inline-flex items-center m-2 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg  transition-all duration-200"
                        :class="{ 'bg-blue-100  text-blue-700': mostrarFiltros }">
                    <i class="fas fa-filter text-sm transition-transform duration-200" 
                    :class="{ 'text-blue-600': mostrarFiltros }"></i>
                    <span class="ml-2 text-sm"></span>
                    <i class="fas fa-chevron-down ml-2 text-xs transition-transform duration-200" 
                    :class="{ 'rotate-180': mostrarFiltros }"></i>
                </button>
            </div>

            <!-- Panel de filtros -->
            <div x-show="mostrarFiltros" 
                x-collapse
                class="mb-4">
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 bg-white p-4 rounded-lg shadow-md">
                    <!-- Fecha Desde -->
                    <div>
                        <label for="desdefecha" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar-alt mr-1"></i>Fecha Desde
                        </label>
                        <input type="date" 
                                wire:model.live.debounce.300ms="desdefecha" 
                                id="desdefecha"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Fecha Hasta -->
                    <div>
                        <label for="hastafecha" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar-alt mr-1"></i>Fecha Hasta
                        </label>
                        <input type="date" 
                                wire:model.live.debounce.300ms="hastafecha" 
                                id="hastafecha"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <!-- Búsqueda -->
                    <div class="col-span-2">
                        <label for="busqueda" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-search mr-1"></i>Cliente
                        </label>
                        <input type="text" 
                                wire:model.live.debounce.300ms="busqueda" 
                                id="busqueda"
                                placeholder="Cliente..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <!-- Filtros de estado estilo pills -->
                <div class="flex flex-wrap gap-1 bg-gray-100 p-2 rounded-lg">
                    <!-- Todos -->
                    <button wire:click="$set('estado', ' ')" 
                            class="px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-200 {{ $estado == ' ' ? 'bg-black text-white shadow-sm' : 'text-gray-600 hover:text-gray-800' }}">
                        Todos
                    </button>
                    
                    <!-- En Armado -->
                    <button wire:click="$set('estado', 'A')" 
                            class="px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-200 {{ $estado == 'A' ? 'bg-amber-500 text-white shadow-sm' : 'text-amber-600 hover:bg-amber-50' }}">
                        Tomando
                    </button>
                    
                    <!-- Pendientes -->
                    <button wire:click="$set('estado', 'X')" 
                            class="px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-200 {{ $estado == 'X' ? 'bg-purple-500 text-white shadow-sm' : 'text-purple-600 hover:bg-purple-50' }}">
                        Pendientes
                    </button>
                    
                    <!-- En proceso -->
                    <button wire:click="$set('estado', 'P')" 
                            class="px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-200 {{ $estado == 'P' ? 'bg-rose-500 text-white shadow-sm' : 'text-rose-600 hover:bg-rose-50' }}">
                        En Proceso
                    </button>
                    
                    <!-- Facturados -->
                    <button wire:click="$set('estado', 'F')" 
                            class="px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-200 {{ $estado == 'F' ? 'bg-green-500 text-white shadow-sm' : 'text-green-600 hover:bg-green-50' }}">
                        Facturados
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Lista de pedidos -->    
    <!-- Tabla responsive con prioridad móvil -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <!-- Header con color personalizado -->
                <thead style="background-color: #222036;" class="text-white">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            <i class="fas fa-user mr-1"></i>
                            Cliente
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider hidden sm:table-cell">
                            <i class="fas fa-comment-alt mr-1"></i>
                            Observaciones
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider hidden md:table-cell">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            Para
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider">
                            <i class="fas fa-tag mr-1"></i>
                            Estado
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider">
                            <i class="fas fa-cog mr-1"></i>
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($pedidos as $pedido)
                        <!-- Fila principal -->
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <!-- Cliente (siempre visible) -->
                            <td class="px-3 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-blue-600 text-xs"></i>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 truncate max-w-32 sm:max-w-none">
                                            {{ $pedido->cliente }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <span class="bg-gray-100 px-2 py-1 rounded-full">#{{ $pedido->id }}</span>
                                            <span class="ml-1">{{ \Carbon\Carbon::parse($pedido->fecha)->format('d/m/Y') }}</span>
                                        </div>
                                        <!-- Observaciones en móvil (bajo el cliente) -->
                                        @if($pedido->observa)
                                            <div class="text-xs text-gray-600 mt-1 sm:hidden truncate max-w-32">
                                                <i class="fas fa-comment-alt text-gray-400 mr-1"></i>
                                                {{ $pedido->observa }}
                                            </div>
                                        @endif
                                        <!-- Fecha para en móvil -->
                                        <div class="text-xs text-gray-500 mt-1 md:hidden">
                                            <i class="fas fa-calendar-alt text-gray-400 mr-1"></i>
                                            Para: {{ \Carbon\Carbon::parse($pedido->parafecha)->format('d/m/Y') }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Observaciones (oculto en móvil) -->
                            <td class="px-3 py-4 hidden sm:table-cell">
                                @if($pedido->observa)
                                    <div class="text-sm text-gray-800 truncate max-w-48">
                                        {{ $pedido->observa }}
                                    </div>
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>

                            <!-- Fecha para (oculto en móvil) -->
                            <td class="px-3 py-4 hidden md:table-cell">
                                <div class="text-sm text-gray-600">
                                    {{ \Carbon\Carbon::parse($pedido->parafecha)->format('d/m/Y') }}
                                </div>
                            </td>

                            <!-- Estado -->
                            <td class="px-3 py-4 text-center">
                                @if($pedido->estado == 'A')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200">
                                        <i class="fas fa-pencil mr-1"></i>
                                        <span class="inline">Tomando</span>
                                    </span>
                                @elseif ($pedido->estado == 'P' && $pedido->nuevo)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">
                                        <i class="fas fa-cloud-upload mr-1"></i>
                                        <span class="inline">Pendiente</span>
                                    </span>
                                @elseif ($pedido->estado == 'P' && !$pedido->nuevo)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-rose-100 text-rose-800 border border-rose-200">
                                        <i class="fas fa-cog mr-1"></i>
                                        <span class="inline">Proceso</span>
                                    </span>
                                @elseif ($pedido->estado == 'F')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                        <i class="fas fa-check mr-1"></i>
                                        <span class="inline">Facturado</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Acciones -->
                            <td class="px-3 py-4 text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <!-- Botón expandir/contraer -->
                                    <button wire:click="toggleExpand({{ $pedido->id }})" 
                                            class="text-gray-500 hover:text-gray-700 transition-colors">
                                        <i class="fas {{ $pedidoExpandido === $pedido->id ? 'fa-chevron-up' : 'fa-chevron-down' }} text-sm"></i>
                                    </button>
                                    
                                    <!-- Botón imprimir -->
                                    <button wire:click="imprimirPedido({{ $pedido->id }})" 
                                            class="text-blue-500 hover:text-blue-700 transition-colors">
                                        <i class="fas fa-print text-sm"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Fila expandida (detalle de productos) -->
                        @if ($pedidoExpandido === $pedido->id)
                            <tr>
                                <td colspan="5" class="px-0 py-0">
                                    <div class="bg-gray-50 border-t border-gray-200">
                                        <div class="pl-1 pr-3 py-4">
                                            @php $total = 0; @endphp
                                            @if(count($detalle))
                                                <!-- Header de productos -->
                                                <div class="mb-3">
                                                    <h4 class="text-sm font-semibold text-gray-700 flex items-center">
                                                        <i class="fas fa-shopping-cart text-gray-400 mr-2"></i>
                                                        Productos del pedido
                                                    </h4>
                                                </div>
                                                
                                                <!-- Lista de productos responsive -->
                                                <div class="space-y-2 mb-4">
                                                    @foreach ($detalle as $item)
                                                        <div class="flex justify-between items-center m-0 py-2 px-2 bg-white rounded border border-gray-200">
                                                            <div class="flex-2 min-w-0">
                                                                <div class="text-sm font-medium text-gray-800 truncate">
                                                                    {{ $item->detart }}
                                                                </div>
                                                                <div class="text-xs text-gray-500">
                                                                    Cantidad: {{ number_format($item->cantidad, 2) }}
                                                                    @if($item->descu > 0)
                                                                        • Desc: ${{ number_format($item->descu, 2) }}
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            <div class="text-right flex-shrink-0" >
                                                                <div class="text-sm font-semibold text-gray-900">
                                                                    ${{ number_format(($item->cantidad * ($item->punitario - $item->descu)), 2) }}
                                                                </div>
                                                                <div class="text-xs text-gray-500">
                                                                    ${{ number_format($item->punitario, 2) }} c/u
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @php $total += $item->cantidad * ($item->punitario - $item->descu); @endphp
                                                    @endforeach
                                                </div>

                                                <!-- Total -->
                                                <div class="border-t border-gray-300 pt-3">
                                                    <div class="flex justify-between items-center py-2 px-3 bg-white rounded border-2 border-gray-300">
                                                        <span class="text-lg font-bold text-gray-800">Total</span>
                                                        <span class="text-lg font-bold text-gray-900">${{ number_format($total, 2) }}</span>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="text-center py-6">
                                                    <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-2">
                                                        <i class="fas fa-shopping-cart text-gray-400"></i>
                                                    </div>
                                                    <p class="text-gray-500 text-sm">No hay artículos en el pedido</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="fas fa-inbox text-gray-400 text-xl"></i>
                                    </div>
                                    <p class="text-gray-500 text-lg font-medium">No hay pedidos</p>
                                    <p class="text-gray-400 text-sm">No se encontraron pedidos con los filtros aplicados</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('abrir-impresion', (data) => {
                console.log(data[0]);
                const ventana = window.open(data[0].url, '_blank', 'width=800,height=600,scrollbars=yes');
                ventana.onload = function() {
                    ventana.print();
                };
            });
        });
    </script>
@endpush
