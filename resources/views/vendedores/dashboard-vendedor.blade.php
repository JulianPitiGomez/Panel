<div class="pb-28 px-3" style="padding-bottom: calc(7rem + env(safe-area-inset-bottom));" x-data="{
    mostrarConfirmacionPedido: false,
    pedidoAEliminar: null,
    confirmarEliminacionPedido(id) {
        this.pedidoAEliminar = id;
        this.mostrarConfirmacionPedido = true;
    },
    cancelarEliminacionPedido() {
        this.mostrarConfirmacionPedido = false;
        this.pedidoAEliminar = null;
    },
    ejecutarEliminacionPedido() {
        if(this.pedidoAEliminar) {
            $wire.eliminarPedido(this.pedidoAEliminar);
        }
        this.mostrarConfirmacionPedido = false;
        this.pedidoAEliminar = null;
    }
}">

    {{-- Modal de confirmación para eliminar pedido --}}
    <div x-show="mostrarConfirmacionPedido"
         x-transition.opacity
         class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-50 flex items-center justify-center px-4"
         style="display: none;">
        <div @click.away="cancelarEliminacionPedido()"
             x-transition
             class="bg-white rounded-lg shadow-2xl max-w-md w-full p-6">
            <div class="flex items-center justify-center w-16 h-16 mx-auto bg-red-100 rounded-full mb-4">
                <i class="fas fa-trash-alt text-red-600 text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 text-center mb-2">¿Eliminar pedido completo?</h3>
            <p class="text-gray-600 text-center mb-6">Esta acción no se puede deshacer. El pedido y todos sus productos serán eliminados permanentemente.</p>
            <div class="flex gap-3">
                <button @click="cancelarEliminacionPedido()"
                        class="flex-1 px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold rounded-lg transition-colors">
                    Cancelar
                </button>
                <button @click="ejecutarEliminacionPedido()"
                        class="flex-1 px-4 py-3 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition-colors">
                    Eliminar Pedido
                </button>
            </div>
        </div>
    </div>

    <!-- Bienvenida -->
    <div class="bg-[#222036] text-white rounded-lg p-4 mb-4 mt-3 shadow-md">
        <h2 class="text-lg font-semibold">¡Bienvenido, {{session()->get('vendedor_nombre')}}!</h2>
        <p class="text-sm">Comienza a armar tus pedidos.</p>
    </div>

    {{-- Aviso de visita registrada --}}
    @if($mensajeVisita)
        <div class="flex items-start gap-3 bg-green-50 border border-green-300 text-green-800 rounded-lg px-4 py-3 mb-4">
            <i class="fa fa-check-circle text-green-600 mt-1"></i>
            <span class="flex-grow text-sm">{{ $mensajeVisita }}</span>
            <button wire:click="$set('mensajeVisita', null)" class="text-green-700 hover:text-green-900 text-xl leading-none">&times;</button>
        </div>
    @endif

    <!-- Botones de acción -->
    <div class="w-full flex flex-row justify-between gap-2 mt-4 pt-2 pb-2 flex-nowrap">
        <button wire:click="iniciarNuevoPedido"
            class="w-full sm:w-1/2 bg-[#FFAF22] hover:bg-yellow-500 text-white font-semibold py-2 px-4 rounded shadow">
            + Nuevo Pedido
        </button>
        <a href="{{route('historial')}}" wire:navigate
            class="hidden sm:block w-1/2 bg-[#222036] hover:bg-gray-900 text-white font-semibold py-2 px-4 rounded shadow text-center">
            Historial
        </a>
    </div>

    <div class="w-full pb-6">
        <button wire:click="iniciarVisitaSinCompra"
            class="w-full bg-[#222036] hover:bg-gray-900 text-white font-semibold py-2 px-4 rounded shadow transition-colors flex items-center justify-center gap-2">
            <i class="fa fa-map-marker-alt"></i>
            Visita sin compra
        </button>
    </div>

    <!-- Selector de cliente -->
    @if($buscandoCliente)
        <!-- Fondo semitransparente -->
        <div class="fixed inset-0 z-40 bg-black bg-opacity-50 backdrop-blur-sm flex items-start justify-center pt-20 pb-20 overflow-y-auto">
            <!-- Modal principal -->
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-2 relative border-1 border-gray-700">
                <div class="flex justify-center items-center bg-[#1E1B35] px-4 py-2 rounded-t">
                    <span class="font-semibold text-white">{{ $modoBusqueda === 'visita' ? 'Visita sin compra' : 'Buscar Cliente' }}</span>
                    <span>
                        <button wire:click="$set('buscandoCliente', false)"
                        class="absolute top-3 right-4 text-white hover:text-red-500 text-xl bg-[#1E1B35]">
                        &times;
                        </button>
                    </span>
                </div>
                
                <div class="p-4">
                    @if($modoBusqueda === 'visita')
                        <p class="text-sm text-gray-600 mb-2">Elegí el cliente que visitaste y no te compró.</p>
                    @endif
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

                {{-- Botón Nuevo Cliente (solo si tiene permisos) --}}
                @if(session('vendedor_permitealta') && $modoBusqueda !== 'visita')
                    <div class="m-4 pt-4 border-t border-gray-200">
                        <a href="{{ route('vendedor.nuevo-cliente') }}"
                           class="w-full bg-[#FFAF22] hover:bg-[#E89B0A] text-white font-semibold py-3 px-4 rounded-lg shadow-lg transition-colors flex items-center justify-center">
                            <i class="fa fa-user-plus mr-2"></i>
                            <span>Nuevo Cliente</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif


    {{-- Modal: registrar visita sin compra --}}
    @if($visitaCliente)
        <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-50 flex items-start justify-center pt-20 px-4">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md relative border-1 border-gray-700">
                <div class="flex justify-center items-center bg-[#1E1B35] px-4 py-2 rounded-t">
                    <span class="font-semibold text-white">Visita sin compra</span>
                    <button wire:click="cancelarVisita"
                        class="absolute top-3 right-4 text-white hover:text-red-500 text-xl bg-[#1E1B35]">&times;</button>
                </div>

                <div class="p-4">
                    <div class="bg-gray-50 border border-gray-200 rounded p-3 mb-4">
                        <div class="font-semibold text-gray-800">{{ $visitaCliente['nombre'] }}</div>
                        <div class="text-xs text-gray-500">Código {{ $visitaCliente['codigo'] }} &middot; {{ now()->format('d/m/Y') }}</div>
                    </div>

                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Motivo <span class="text-red-500">*</span>
                    </label>
                    <textarea wire:model.live.debounce.500ms="visitaMotivo" rows="3" maxlength="200"
                        placeholder="Ej: tenía stock, no estaba el encargado, precio alto..."
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm shadow-sm"></textarea>

                    @if($visitaError)
                        <p class="text-sm text-red-600 mt-2">{{ $visitaError }}</p>
                    @endif

                    <div class="flex gap-3 mt-4">
                        <button wire:click="cancelarVisita"
                            class="flex-1 px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold rounded-lg transition-colors">
                            Cancelar
                        </button>
                        <button wire:click="registrarVisita" wire:loading.attr="disabled"
                            @disabled(trim((string) $visitaMotivo) === '')
                            class="flex-1 px-4 py-3 bg-[#FFAF22] hover:bg-yellow-500 text-white font-semibold rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            Registrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Lista de pedidos -->
    @forelse ($pedidos as $pedido)
    <div class="bg-white shadow-md rounded-xl mb-3 pb-5 transition-all duration-200 hover:shadow-lg border-2 border-transparent hover:border-[#FFAF22]">
        <!-- Cabecera (colapsada) -->
        <div class="flex justify-between items-center px-5 py-4 bg-white rounded-t-xl cursor-pointer" wire:click="toggleExpand({{ $pedido->id }})">
            <div class="text-left flex-grow">
                <div class="text-md font-semibold text-gray-800">{{ $pedido->cliente }}</div>
                <div class="text-sm text-gray-500">{{ $pedido->direccion }}</div>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold" style="color: #FFAF22;">${{ number_format($pedido->total, 2) }}</div>
                <div class="text-sm text-gray-500">Para: {{ \Carbon\Carbon::parse($pedido->parafecha)->format('d/m/Y') }}</div>
            </div>
            <!-- Icono de despliegue -->
            <div class="ml-4 text-gray-600">
                <i class="fas {{ $pedidoExpandido === $pedido->id ? 'fa-chevron-up' : 'fa-chevron-down' }}"></i>
            </div>
        </div>

        <!-- Cuerpo expandido -->
        @if ($pedidoExpandido === $pedido->id)
        <div class="px-5 pb-6 pt-2 bg-gray-50">
            <!-- Lista de productos -->
            @if(count($detalle))
                <div class="space-y-2 mb-3">
                @foreach ($detalle as $item)
                <div class="flex justify-between items-center text-sm py-3 px-3 bg-gray-100 rounded-lg">
                    <span class="text-gray-700">{{ $item->detart }} x {{ $item->cantidad }}</span>
                    <span class="font-semibold text-base" style="color: #FFAF22;">${{ number_format(($item->cantidad * ($item->punitario - $item->descu)), 2) }}</span>
                </div>
                @endforeach
                </div>
                @else
                <div class="text-center text-gray-500 py-10">
                    No hay articulos en el pedido.
                </div>
            @endif

            <hr class="my-3 border-gray-300">

            <div class="flex justify-between font-bold text-base px-3">
                <span class="text-gray-800">Total</span>
                <span style="color: #FFAF22;">${{ number_format($pedido->total, 2) }}</span>
            </div>

            <!-- Botones de acción -->
            <div class="flex flex-wrap justify-center mt-4 text-sm text-center gap-2">
                <button wire:click="modificarPedido({{ $pedido->id }})" class="flex-1 min-w-[80px] py-3 px-2 text-blue-600 rounded-lg transition-all duration-200 hover:bg-blue-50 hover:border hover:border-blue-600">
                    <i class="fa fa-pen text-lg mb-1"></i><br><span class="text-xs">Modificar</span>
                </button>
                <button wire:click="enviarPedido({{ $pedido->id }})" class="flex-1 min-w-[80px] py-3 px-2 text-green-600 rounded-lg transition-all duration-200 hover:bg-green-50 hover:border hover:border-green-600">
                    <i class="fa fa-check text-lg mb-1"></i><br><span class="text-xs">Enviar</span>
                </button>
                <button wire:click="editarObservacion({{ $pedido->id }})" class="flex-1 min-w-[80px] py-3 px-2 text-yellow-600 rounded-lg transition-all duration-200 hover:bg-yellow-50 hover:border hover:border-yellow-600">
                    <i class="fa fa-comment text-lg mb-1"></i><br><span class="text-xs">Observ.</span>
                </button>
                <button wire:click="editarFecha({{ $pedido->id }})" class="flex-1 min-w-[80px] py-3 px-2 text-purple-600 rounded-lg transition-all duration-200 hover:bg-purple-50 hover:border hover:border-purple-600">
                    <i class="fa fa-calendar text-lg mb-1"></i><br><span class="text-xs">Fecha</span>
                </button>
                <button wire:click="imprimirPedido({{ $pedido->id }})" class="flex-1 min-w-[80px] py-3 px-2 text-gray-700 rounded-lg transition-all duration-200 hover:bg-gray-100 hover:border hover:border-gray-700">
                    <i class="fa fa-print text-lg mb-1"></i><br><span class="text-xs">Imprimir</span>
                </button>
                <button @click="confirmarEliminacionPedido({{ $pedido->id }})" class="flex-1 min-w-[80px] py-3 px-2 text-red-600 rounded-lg transition-all duration-200 hover:bg-red-50 hover:border hover:border-red-600">
                    <i class="fa fa-trash text-lg mb-1"></i><br><span class="text-xs">Eliminar</span>
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
        <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-40 flex items-start justify-center pt-20">
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
        <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-40 flex items-start justify-center pt-20">
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
