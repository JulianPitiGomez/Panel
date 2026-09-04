<div class="pb-28 px-3" style="padding-bottom: calc(7rem + env(safe-area-inset-bottom));">
    @if (session('error'))
        <div class="bg-red-50 border-l-4 border-red-400 p-3 mb-3 mt-3 rounded shadow-sm"
             x-data="{ open: true }" x-show="open" x-transition>
            <div class="flex justify-between items-start gap-3">
                <div class="text-sm text-red-800">
                    <i class="fas fa-times-circle mr-1"></i>
                    {{ session('error') }}
                </div>
                <button @click="open = false" class="text-red-600 hover:text-red-800 text-lg leading-none">&times;</button>
            </div>
        </div>
    @endif
    <!-- Bienvenida -->
    <div class="bg-[#222036] text-white rounded-lg p-4 mb-4 mt-3 shadow-md">
        <h2 class="text-lg font-semibold">HISTORIAL DE {{session()->get('vendedor_nombre')}}</h2>
        <p class="text-sm">Estos son tus pedidos.</p>
    </div>
    <div x-data="{ mostrarFiltros: true }">
        <!-- Botón compacto para filtros -->
        <div class="mb-4 flex justify-end">
            <button @click="mostrarFiltros = !mostrarFiltros"
                    class="inline-flex items-center px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-all duration-200"
                    :class="{ 'bg-blue-100 text-blue-700': mostrarFiltros }">
                <i class="fas fa-filter text-sm transition-transform duration-200"
                :class="{ 'text-blue-600': mostrarFiltros }"></i>
                <span class="ml-2 text-sm">Filtros</span>
                <i class="fas fa-chevron-down ml-2 text-xs transition-transform duration-200"
                :class="{ 'rotate-180': mostrarFiltros }"></i>
            </button>
        </div>

        <!-- Panel de filtros -->
        <div x-show="mostrarFiltros"
            x-collapse
            class="mb-4">

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 bg-white p-4 rounded-xl shadow-md">
                <!-- Fecha Desde -->
                <div>
                    <label for="desdefecha" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar-alt mr-1"></i>Fecha Desde
                    </label>
                    <input type="date"
                            wire:model.live.debounce.300ms="desdefecha"
                            id="desdefecha"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
                </div>

                <!-- Fecha Hasta -->
                <div>
                    <label for="hastafecha" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar-alt mr-1"></i>Fecha Hasta
                    </label>
                    <input type="date"
                            wire:model.live.debounce.300ms="hastafecha"
                            id="hastafecha"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
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
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
                </div>
            </div>

            <!-- Filtros de estado estilo pills -->
            <div class="flex flex-wrap gap-1 bg-gray-100 p-2 rounded-lg">
                <button wire:click="$set('estado', ' ')"
                        class="px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-200 {{ $estado == ' ' ? 'bg-black text-white shadow-sm' : 'text-gray-600 hover:text-gray-800' }}">
                    Todos
                </button>
                <button wire:click="$set('estado', 'A')"
                        class="px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-200 {{ $estado == 'A' ? 'bg-amber-500 text-white shadow-sm' : 'text-amber-600 hover:bg-amber-50' }}">
                    Tomando
                </button>
                <button wire:click="$set('estado', 'X')"
                        class="px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-200 {{ $estado == 'X' ? 'bg-purple-500 text-white shadow-sm' : 'text-purple-600 hover:bg-purple-50' }}">
                    Pendientes
                </button>
                <button wire:click="$set('estado', 'P')"
                        class="px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-200 {{ $estado == 'P' ? 'bg-rose-500 text-white shadow-sm' : 'text-rose-600 hover:bg-rose-50' }}">
                    En Proceso
                </button>
                <button wire:click="$set('estado', 'F')"
                        class="px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-200 {{ $estado == 'F' ? 'bg-green-500 text-white shadow-sm' : 'text-green-600 hover:bg-green-50' }}">
                    Facturados
                </button>
            </div>
        </div>
    </div>

    <!-- Lista de pedidos -->
    @forelse ($pedidos as $pedido)
    <div class="bg-white shadow-md rounded-xl mb-3 pb-5 transition-all duration-200 hover:shadow-lg border-2 border-transparent hover:border-[#FFAF22]">
        <!-- Cabecera -->
        <div class="flex justify-between items-center px-5 py-4 bg-white rounded-t-xl cursor-pointer" wire:click="toggleExpand({{ $pedido->id }})">
            <div class="text-left flex-grow">
                <div class="text-md font-semibold text-gray-800">{{ $pedido->cliente }}</div>
                <div class="text-sm text-gray-500">
                    <span class="mr-2">#{{ $pedido->id }}</span>
                    <span>{{ \Carbon\Carbon::parse($pedido->fecha)->format('d/m/Y') }}</span>
                </div>
                @if($pedido->codven == 0)
                    <div class="mt-1">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                            <i class="fas fa-building mr-1"></i>Cargado por administración a tu nombre
                        </span>
                    </div>
                @endif
                @if($pedido->observa)
                    <div class="text-sm text-gray-500 truncate max-w-[200px] sm:max-w-none">
                        <i class="fas fa-comment-alt mr-1 text-xs"></i>{{ $pedido->observa }}
                    </div>
                @endif
            </div>
            <div class="text-right">
                @if($pedido->estado == 'A')
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                        <i class="fas fa-pencil mr-1"></i>Tomando
                    </span>
                @elseif($pedido->estado == 'P' && $pedido->nuevo)
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        <i class="fas fa-cloud-upload mr-1"></i>Pendiente
                    </span>
                @elseif($pedido->estado == 'P' && !$pedido->nuevo)
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-rose-100 text-rose-800">
                        <i class="fas fa-cog mr-1"></i>Proceso
                    </span>
                @elseif($pedido->estado == 'F')
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check mr-1"></i>Facturado
                    </span>
                @endif
                <div class="text-sm text-gray-500 mt-1">Para: {{ \Carbon\Carbon::parse($pedido->parafecha)->format('d/m/Y') }}</div>
            </div>
            <!-- Icono de despliegue -->
            <div class="ml-4 text-gray-600">
                <i class="fas {{ $pedidoExpandido === $pedido->id ? 'fa-chevron-up' : 'fa-chevron-down' }}"></i>
            </div>
        </div>

        <!-- Cuerpo expandido -->
        @if ($pedidoExpandido === $pedido->id)
        <div class="px-5 pb-6 pt-2 bg-gray-50">
            @php $total = 0; @endphp
            <!-- Lista de productos -->
            @if(count($detalle))
                <div class="space-y-2 mb-3">
                @foreach ($detalle as $item)
                <div class="flex justify-between items-center text-sm py-3 px-3 bg-gray-100 rounded-lg">
                    <div class="flex-1 min-w-0">
                        <span class="text-gray-700">{{ $item->detart }} x {{ number_format($item->cantidad, 2) }}</span>
                        @if($item->cantidad > 1)
                            <div class="text-xs text-gray-500">P.Un. ${{ number_format($item->punitario, 2) }}</div>
                        @endif
                        @if($item->descu > 0)
                            <div class="text-xs text-gray-500">Desc. ${{ number_format($item->descu, 2) }}</div>
                        @endif
                    </div>
                    <span class="font-semibold text-base" style="color: #FFAF22;">${{ number_format(($item->cantidad * ($item->punitario - $item->descu)), 2) }}</span>
                </div>
                @php $total += $item->cantidad * ($item->punitario - $item->descu); @endphp
                @endforeach
                </div>
            @else
                <div class="text-center text-gray-500 py-10">
                    No hay artículos en el pedido.
                </div>
            @endif

            <hr class="my-3 border-gray-300">

            <div class="flex justify-between font-bold text-base px-3">
                <span class="text-gray-800">Total</span>
                <span style="color: #FFAF22;">${{ number_format($total, 2) }}</span>
            </div>

            <!-- Botones de acción -->
            <div class="flex justify-center gap-3 mt-4">
                <button wire:click.stop="imprimirPedido({{ $pedido->id }})"
                        class="py-3 px-4 text-blue-600 rounded-lg transition-all duration-200 hover:bg-blue-50 hover:border hover:border-blue-600">
                    <i class="fa fa-print text-lg mb-1"></i><br><span class="text-xs">Imprimir</span>
                </button>
                <button wire:click.stop="pedirDuplicar({{ $pedido->id }})"
                        class="py-3 px-4 text-green-600 rounded-lg transition-all duration-200 hover:bg-green-50 hover:border hover:border-green-600">
                    <i class="fa fa-copy text-lg mb-1"></i><br><span class="text-xs">Duplicar</span>
                </button>
            </div>
        </div>
        @endif
    </div>
    @empty
        <div class="text-center text-gray-500 py-10">
            No hay pedidos con los filtros aplicados.
        </div>
    @endforelse

    {{-- Modal confirmar duplicación --}}
    @if($mostrarModalDuplicar)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl p-6 mx-4 max-w-sm w-full">
                <div class="flex items-center justify-center w-16 h-16 mx-auto bg-green-100 rounded-full mb-4">
                    <i class="fa fa-copy text-green-600 text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2 text-center">Duplicar Pedido</h3>
                <p class="text-sm text-gray-600 mb-5 text-center">
                    Se creará un pedido nuevo en estado <b>Tomando</b> con los mismos artículos y los <b>precios actualizados</b> según las listas vigentes del cliente.
                </p>
                <div class="flex flex-col gap-3">
                    <button wire:click="confirmarDuplicacion"
                            class="bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-4 rounded shadow flex items-center justify-center">
                        <i class="fa fa-copy mr-2"></i>
                        Duplicar pedido
                    </button>
                    <button wire:click="$set('mostrarModalDuplicar', false)"
                            class="text-gray-500 hover:text-gray-700 text-sm mt-1 text-center">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal elegir tipo de impresión --}}
    @if($mostrarModalImprimir)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl p-6 mx-4 max-w-sm w-full">
                <h3 class="text-lg font-bold text-gray-800 mb-4 text-center">
                    <i class="fa fa-print mr-2" style="color: #FFAF22;"></i>Imprimir Pedido
                </h3>
                <p class="text-sm text-gray-600 mb-5 text-center">Seleccione el tipo de impresión:</p>
                <div class="flex flex-col gap-3">
                    <button wire:click="confirmarImpresion(true)"
                            class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-4 rounded shadow flex items-center justify-center">
                        <i class="fa fa-dollar-sign mr-2"></i>
                        Valorizado (con precios)
                    </button>
                    <button wire:click="confirmarImpresion(false)"
                            class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-4 rounded shadow flex items-center justify-center">
                        <i class="fa fa-list mr-2"></i>
                        No valorizado (solo cantidades)
                    </button>
                    <button wire:click="$set('mostrarModalImprimir', false)"
                            class="text-gray-500 hover:text-gray-700 text-sm mt-1 text-center">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
    <script>
        Livewire.on('abrir-impresion', (data) => {
            console.log(data[0]);
            const ventana = window.open(data[0].url, '_blank', 'width=800,height=600,scrollbars=yes');
            if (ventana) {
                ventana.onload = function() {
                    ventana.print();
                };
            }
        });
    </script>
@endpush
