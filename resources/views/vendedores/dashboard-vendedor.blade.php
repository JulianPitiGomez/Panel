<div class="pb-8 px-1">
    <!-- Bienvenida -->
    <div class="bg-[#222036] text-white rounded-lg p-4 mb-4 mt-3 shadow-md">
        <h2 class="text-lg font-semibold">¡Bienvenido, {{session()->get('vendedor_nombre')}}!</h2>
        <p class="text-sm">Comienza a armar tus pedidos.</p>
    </div>

    <!-- Botones de acción -->
    <div class="w-full flex flex-row justify-between gap-2 mt-4 pt-2 pb-6 flex-nowrap">
        <button wire:click="iniciarNuevoPedido"
            class="w-1/2 md:w-1/2 bg-[#FFAF22] hover:bg-yellow-500 text-white font-semibold py-2 px-4 rounded shadow">
            + Nuevo Pedido
        </button>
        <a href="{{route('historial')}}" wire:navigate
            class="w-1/2 md:w-1/2 bg-[#222036] hover:bg-gray-900 text-white font-semibold py-2 px-4 rounded shadow text-center">
            Historial
        </a>
    </div>

    <!-- Selector de cliente -->
    @if($buscandoCliente)
        <!-- Fondo semitransparente -->
        <div class="fixed inset-0 z-40 bg-black bg-opacity-50 backdrop-blur-sm flex items-start justify-center pt-20">
            <!-- Modal principal -->
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-2 relative border-1 border-gray-700">
                <div class="flex justify-center items-center bg-[#1E1B35] px-4 py-2 rounded-t">
                    <span class="font-semibold text-white">Buscar Cliente</span>                    
                    <span>
                        <button wire:click="$set('buscandoCliente', false)"
                        class="absolute top-3 right-4 text-white hover:text-red-500 text-xl bg-[#1E1B35]">
                        &times;
                        </button>
                    </span>
                </div>
                
                <div class="p-4">                    
                <!-- Input de búsqueda -->
                    <input type="text" wire:model.live.debounce.300ms="busqueda"
                        placeholder="Buscar cliente por nombre, código o dirección..."
                        class="w-full border border-gray-300 rounded px-4 py-2 text-sm shadow-sm mb-2" />

                    <!-- Resultados -->
                    @if(count($clientesFiltrados) > 0)
                        <ul class="border border-gray-200 rounded shadow-sm divide-y max-h-60 overflow-y-auto text-sm">
                            @foreach($clientesFiltrados as $cliente)
                                <li wire:click="seleccionarCliente('{{ $cliente->CODIGO }}')"
                                    class="p-2 hover:bg-gray-100 cursor-pointer">
                                    <span class="font-semibold">{{ $cliente->NOMBRE }}</span><br>
                                    <span class="text-gray-500 text-xs">{{ $cliente->DIRECCION }} ({{ $cliente->CODIGO }})</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-500 mt-2">Sin resultados...</p>
                    @endif
                </div>
            </div>
        </div>
    @endif


    <!-- Lista de pedidos -->    
    @forelse ($pedidos as $pedido)
    <div class="bg-white shadow rounded-lg mb-3 pb-4">
        <!-- Cabecera (colapsada) -->
        <div class="flex justify-between items-center px-4 py-3 bg-white cursor-pointer" wire:click="toggleExpand({{ $pedido->id }})">
            <div class="text-left flex-grow">
                <div class="text-md font-semibold text-gray-800">{{ $pedido->cliente }}</div>
                <div class="text-sm text-gray-500">{{ $pedido->direccion }}</div>
            </div>
            <div class="text-right">
                <div class="text-lg font-semibold text-gray-800">${{ number_format($pedido->total, 2) }}</div>
                <div class="text-sm text-gray-500">Para: {{ \Carbon\Carbon::parse($pedido->parafecha)->format('d/m/Y') }}</div>
            </div>
            <!-- Icono de despliegue -->
            <div class="ml-4 text-gray-600">
                <i class="fas {{ $pedidoExpandido === $pedido->id ? 'fa-chevron-up' : 'fa-chevron-down' }}"></i>
            </div>
        </div>

        <!-- Cuerpo expandido -->
        @if ($pedidoExpandido === $pedido->id)
        <div class="px-0 pb-6 bg-gray-100">
            <!-- Lista de productos -->
            @if(count($detalle))
                @foreach ($detalle as $item)
                <div class="flex justify-between text-xs py-1">
                    <span>{{ $item->detart }} x {{ $item->cantidad }}</span>
                    <span>${{ number_format(($item->cantidad * ($item->punitario - $item->descu)), 2) }}</span>
                </div>
                @endforeach
                @else
                <div class="text-center text-gray-500 py-10">
                    No hay articulos en el pedido.
                </div>
            @endif

            <hr class="my-2">

            <div class="flex justify-between font-semibold">
                <span>Total</span>
                <span>${{ number_format($pedido->total, 2) }}</span>
            </div>

            <!-- Botones de acción -->
            <div class="flex justify-between mt-3 text-sm text-center bg-gray-100">
                <button wire:click="modificarPedido({{ $pedido->id }})" class="text-blue-600 hover:text-blue-800">
                    <i class="fa fa-pen"></i><br>Modificar
                </button>
                <button wire:click="enviarPedido({{ $pedido->id }})" class="text-green-600 hover:text-green-800">
                    <i class="fa fa-check"></i><br>Enviar
                </button>
                <button wire:click="editarObservacion({{ $pedido->id }})" class="text-yellow-600 hover:text-yellow-800">
                    <i class="fa fa-comment"></i><br>Observ.
                </button>
                <button wire:click="editarFecha({{ $pedido->id }})" class="text-purple-600 hover:text-purple-800">
                    <i class="fa fa-calendar"></i><br>Fecha
                </button>
                <button wire:click="imprimirPedido({{ $pedido->id }})" class="text-gray-700 hover:text-black">
                    <i class="fa fa-print"></i><br>Imprimir
                </button>
                <button wire:click="eliminarPedido({{ $pedido->id }})" class="text-red-600 hover:text-red-800">
                    <i class="fa fa-trash"></i><br>Eliminar
                </button>
            </div>
        </div>
        @endif
    </div>
    @empty
        <div class="text-center text-gray-500 py-10">
            No hay pedidos en proceso.
        </div>
    @endforelse

    @if($editandoObservacionId)
        <div class="fixed inset-0 backdrop-blur-xs z-40 flex items-start justify-center pt-20">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-2 relative border-1 border-gray-700 p-4">
                <button wire:click="$set('editandoObservacionId', null)" class="absolute top-2 right-2 text-gray-600 text-xl hover:text-red-600">&times;</button>

                <h2 class="text-lg font-semibold mb-2">Editar Observaciones {{$editandoObservacionId}}</h2>
                <textarea wire:model.defer="nuevaObservacion" rows="4" class="w-full border border-gray-300 rounded p-2 text-sm mb-4"></textarea>

                <div class="flex justify-end space-x-2">
                    <button wire:click="$set('editandoObservacionId', null)" class="px-4 py-2 bg-gray-300 text-gray-700 rounded">Cancelar</button>
                    <button wire:click="modificarObservacion({{ $editandoObservacionId }}, '{{ addslashes($nuevaObservacion) }}')" class="px-4 py-2 bg-yellow-500 text-white rounded">Guardar</button>
                </div>
            </div>
        </div>
    @endif

    @if($editandoFechaId)
        <div class="fixed inset-0 backdrop-blur-xs z-40 flex items-start justify-center pt-20">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-2 relative border-1 border-gray-700 p-4">
                <button wire:click="$set('editandoFechaId', null)" class="absolute top-2 right-2 text-gray-600 text-xl hover:text-red-600">&times;</button>

                <h2 class="text-lg font-semibold mb-2">Editar Fecha para</h2>
                <input type="date" wire:model.defer="nuevaFecha" class="w-full border border-gray-300 rounded px-4 py-2 text-sm mb-4">

                <div class="flex justify-end space-x-2">
                    <button wire:click="$set('editandoFechaId', null)" class="px-4 py-2 bg-gray-300 text-gray-700 rounded">Cancelar</button>
                    <button wire:click="modificarFechaPara({{ $editandoFechaId }}, '{{ $nuevaFecha }}')" class="px-4 py-2 bg-yellow-500 text-white rounded">Guardar</button>
                </div>
            </div>
        </div>
    @endif

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
