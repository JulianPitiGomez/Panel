<div class="pb-28 px-3" style="padding-bottom: calc(7rem + env(safe-area-inset-bottom));" x-data="{
    mostrarConfirmacion: false,
    itemAEliminar: null,
    confirmarEliminacion(id) {
        this.itemAEliminar = id;
        this.mostrarConfirmacion = true;
    },
    cancelarEliminacion() {
        this.mostrarConfirmacion = false;
        this.itemAEliminar = null;
    },
    ejecutarEliminacion() {
        if(this.itemAEliminar) {
            $wire.eliminarProducto(this.itemAEliminar);
        }
        this.mostrarConfirmacion = false;
        this.itemAEliminar = null;
    }
}">

    @if (session('aviso_duplicado'))
        <div class="bg-amber-50 border-l-4 border-amber-400 p-3 mb-3 rounded shadow-sm"
             x-data="{ open: true }" x-show="open" x-transition>
            <div class="flex justify-between items-start gap-3">
                <div class="text-sm text-amber-800">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    {{ session('aviso_duplicado') }}
                </div>
                <button @click="open = false" class="text-amber-600 hover:text-amber-800 text-lg leading-none">&times;</button>
            </div>
        </div>
    @endif

    {{-- Modal de confirmación bonito --}}
    <div x-show="mostrarConfirmacion"
         x-transition.opacity
         class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-50 flex items-center justify-center px-4"
         style="display: none;">
        <div @click.away="cancelarEliminacion()"
             x-transition
             class="bg-white rounded-lg shadow-2xl max-w-md w-full p-6">
            <div class="flex items-center justify-center w-16 h-16 mx-auto bg-red-100 rounded-full mb-4">
                <i class="fas fa-trash-alt text-red-600 text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 text-center mb-2">¿Eliminar producto?</h3>
            <p class="text-gray-600 text-center mb-6">Esta acción no se puede deshacer. El producto será removido del pedido.</p>
            <div class="flex gap-3">
                <button @click="cancelarEliminacion()"
                        class="flex-1 px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold rounded-lg transition-colors">
                    Cancelar
                </button>
                <button @click="ejecutarEliminacion()"
                        class="flex-1 px-4 py-3 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition-colors">
                    Eliminar
                </button>
            </div>
        </div>
    </div>

    {{-- Información del cliente --}}
    <div class="bg-white rounded shadow p-4">
        <div class="flex justify-between items-center mb-2">
            <h2 class="font-semibold text-gray-700">{{ $cliente->NOMBRE.' ('.$cliente->CODIGO.')'}}</h2>
            <p class="text-sm"><span class="font-semibold">Pedido #: <b>{{ $pedido->id }}</b></span></p>
        </div>
        <div x-data="{ mostrarInfo: false }">
            <!-- Header clickeable para expandir/contraer -->
            <button @click="mostrarInfo = !mostrarInfo" 
                    class="w-full flex items-center justify-between p-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                <span class="text-sm font-medium text-gray-700">Información del Cliente</span>
                <i class="fa text-gray-500 text-xs transition-transform duration-200"
                :class="mostrarInfo ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
            
            <!-- Contenido expandible -->
            <div x-show="mostrarInfo" class="mt-2 text-sm text-gray-700 space-y-1 bg-white p-3 rounded-lg border">            
                <p><span class="font-semibold">CUIT:</span> {{ $cliente->CUIT ?? '-' }}</p>
                <p><span class="font-semibold">Dirección:</span> {{ $cliente->DIRECCION ?? '-' }}</p>
                <p><span class="font-semibold">Teléfono:</span> {{ $cliente->TELEFONO ?? '-' }}</p>
            </div>
        </div>
    </div>
        @if($editandoObservacionId)
        <div class="fixed inset-0 backdrop-blur-xs z-40 flex items-start justify-center pt-20">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-2 relative border-1 border-gray-700 p-4">
                <button wire:click="$set('editandoObservacionId', null)" class="absolute top-2 right-2 text-gray-600 text-xl hover:text-red-600">&times;</button>

                <h2 class="text-lg font-semibold mb-2">Editar Observaciones {{$editandoObservacionId}}</h2>
                <textarea wire:model.defer="nuevaObservacion" rows="4" class="w-full border border-gray-300 rounded p-2 text-sm mb-4"></textarea>

                <div class="flex justify-end space-x-2">
                    <button wire:click="$set('editandoObservacionId', null)" class="px-4 py-2 bg-gray-300 text-gray-700 rounded">Cancelar</button>
                    <button wire:click="modificarObservacion({{ $editandoObservacionId }}, '{{ addslashes($nuevaObservacion) }}')" class="px-4 py-2 bg-[#FFAF22] hover:bg-[#E89B0A] text-white rounded">Guardar</button>
                </div>
            </div>
        </div>
    @endif

    @if($editandoFechaId)
        <div class="fixed inset-0 backdrop-blur-xs z-40 flex items-start justify-center pt-20">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-2 relative border-1 border-gray-700 p-4">
                <button wire:click="$set('editandoFechaId', null)" class="absolute top-2 right-2 text-gray-600 text-xl hover:text-red-600">&times;</button>

                <h2 class="text-lg font-semibold mb-2">Editar para Fecha</h2>
                <input type="date" wire:model.defer="nuevaFecha" class="w-full border border-gray-300 rounded px-4 py-2 text-sm mb-4">

                <div class="flex justify-end space-x-2">
                    <button wire:click="$set('editandoFechaId', null)" class="px-4 py-2 bg-gray-300 text-gray-700 rounded">Cancelar</button>
                    <button wire:click="modificarFechaPara({{ $editandoFechaId }}, '{{ $nuevaFecha }}')" class="px-4 py-2 bg-[#FFAF22] hover:bg-[#E89B0A] text-white rounded">Guardar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Botones de acción: Agregar producto, Observación y Fecha --}}
    <div class="bg-white rounded shadow p-4">
        <div class="flex flex-wrap gap-2">
            <!-- Botón Agregar Producto: 100% en móvil muy pequeño, 50% en móvil, auto en desktop -->
            <button wire:click="$set('mostrarModalBusqueda', true)"
                    class="flex-1 sm:flex-none sm:min-w-[200px] bg-[#FFAF22] hover:bg-[#E89B0A] text-white font-semibold py-3 px-4 rounded-lg shadow-lg transition-colors flex items-center justify-center">
                <i class="fa fa-plus-circle mr-2"></i>
                <span class="hidden sm:inline">Agregar Producto</span>
                <span class="sm:hidden">Agregar</span>
            </button>

            <!-- Botones de Observación y Fecha: comparten el espacio restante en móvil -->
            <div class="flex gap-2 flex-1 sm:flex-none">
                <button wire:click="editarObservacion({{ $pedido->id }})"
                        class="flex-1 sm:flex-none sm:px-4 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-3 rounded-lg shadow-lg transition-colors flex items-center justify-center">
                    <i class="fa fa-comment mr-0 sm:mr-2"></i>
                    <span class="hidden sm:inline">Observación</span>
                </button>
                <button wire:click="editarFecha({{ $pedido->id }})"
                        class="flex-1 sm:flex-none sm:px-4 bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 px-3 rounded-lg shadow-lg transition-colors flex items-center justify-center">
                    <i class="fa fa-calendar mr-0 sm:mr-2"></i>
                    <span class="hidden sm:inline">Fecha</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Modal de búsqueda de productos --}}
    @if($mostrarModalBusqueda)
        <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-50 flex items-start justify-center pt-4 px-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
                {{-- Header del modal --}}
                <div class="bg-[#FFAF22] px-4 py-3 flex justify-between items-center">
                    <h3 class="text-white font-semibold text-lg">Buscar Producto</h3>
                    <button wire:click="cerrarModal" class="text-white hover:text-gray-200 text-2xl">&times;</button>
                </div>

                {{-- Contenido del modal --}}
                <div class="p-4">
                    {{-- Buscador --}}
                    @if(!$productoSeleccionado)
                        <input type="text"
                               wire:model.live.debounce.300ms="busqueda"
                               placeholder="Buscar por nombre o código..."
                               autofocus
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#FFAF22] shadow-sm mb-4" />

                        {{-- Lista de productos --}}
                        <div class="max-h-96 overflow-y-auto">
                            @if($productos && count($productos) > 0)
                                <ul class="divide-y border border-gray-200 rounded-lg">
                                    @foreach($productos as $producto)
                                        <li wire:click="seleccionarProducto({{ $producto->CODIGO }})"
                                            class="p-3 hover:bg-yellow-50 cursor-pointer transition-colors">
                                            <div class="flex justify-between items-center">
                                                <div class="flex-1">
                                                    <div class="font-medium text-gray-800">{{ $producto->NOMBRE }}</div>
                                                    <div class="text-xs text-gray-500">Código: {{ $producto->CODIGO }} | Stock: {{ $producto->STOCKACT }}</div>
                                                </div>
                                                <div class="text-lg text-green-600 font-semibold ml-4">
                                                    ${{ number_format($this->obtenerPrecioCliente($producto->CODIGO), 2) }}
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @elseif($busqueda)
                                <p class="text-sm text-gray-500 text-center py-8">No se encontraron productos.</p>
                            @else
                                <p class="text-sm text-gray-400 text-center py-8">Escribe para buscar productos...</p>
                            @endif
                        </div>
                    @else
                        {{-- Formulario del producto seleccionado --}}
                        <div class="space-y-4">
                            {{-- Información del producto --}}
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="font-semibold text-gray-800 text-lg">{{ $productoSeleccionado->NOMBRE }}</div>
                                <div class="text-sm text-gray-600 mt-1">
                                    <span>Código: {{ $productoSeleccionado->CODIGO }}</span>
                                    <span class="mx-2">|</span>
                                    <span>Stock: {{ $productoSeleccionado->STOCKACT }}</span>
                                </div>
                                <div class="text-2xl text-green-600 font-bold mt-2">
                                    ${{ number_format($this->obtenerPrecioCliente($productoSeleccionado->CODIGO), 2) }}
                                </div>
                            </div>

                            {{-- Cantidad --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad</label>
                                <div class="flex items-center border border-gray-300 rounded-lg bg-white w-full overflow-hidden">
                                    <button type="button"
                                            wire:click="decrementarCantidad({{ $productoSeleccionado->CODIGO }})"
                                            class="flex-shrink-0 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-800 transition-colors">
                                        <i class="fa fa-minus text-sm"></i>
                                    </button>
                                    <input type="number"
                                        wire:model.live.debounce.300ms="cantidades.{{ $productoSeleccionado->CODIGO }}"
                                        min="0.01"
                                        step="0.01"
                                        value="{{ $cantidades[$productoSeleccionado->CODIGO] ?? '1.00' }}"
                                        placeholder="1.00"
                                        class="flex-1 min-w-0 px-2 py-2 text-center font-medium text-base border-l border-r border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
                                    <button type="button"
                                            wire:click="incrementarCantidad({{ $productoSeleccionado->CODIGO }})"
                                            class="flex-shrink-0 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-800 transition-colors">
                                        <i class="fa fa-plus text-sm"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Descuento (solo si tiene permisos) --}}
                            @if($vendedor->PERMITEDESC)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Descuento (%)</label>
                                    <input type="number"
                                        wire:model.live.debounce.300ms="descuentos.{{ $productoSeleccionado->CODIGO }}"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        placeholder="0"
                                        class="w-full px-3 py-2 text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
                                </div>
                            @endif

                            {{-- Botones de acción --}}
                            <div class="flex gap-3 pt-4">
                                <button wire:click="cerrarModal"
                                        class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold py-3 px-4 rounded-lg transition-colors">
                                    Cancelar
                                </button>
                                <button wire:click="agregarProducto({{ $productoSeleccionado->CODIGO }})"
                                        class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-4 rounded-lg shadow-lg transition-colors">
                                    <i class="fa fa-plus mr-2"></i>
                                     Agregar
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Detalle del pedido --}}
    <div class="bg-white rounded-xl shadow-md p-5">
        <div class="flex justify-between items-center mb-3 pb-3 border-b">
            <h2 class="font-semibold text-gray-800">Detalle del pedido</h2>
            <button class="relative bg-[#FFAF22] hover:bg-[#E89B0A] text-white text-xs px-3 py-1 rounded-full shadow">
                {{ count($detallePedido) }}
            </button>
        </div>
        @php $total = 0; @endphp
        @if(count($detallePedido) > 0)
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @foreach($detallePedido as $detalle)
                    <div class="flex items-center justify-between text-sm py-3 px-3 bg-gray-100 rounded-lg">
                        <div class="flex-1">
                            <span class="text-gray-700 font-medium">{{ $detalle->detart }}</span> x <span class="font-bold text-gray-900">{{ $detalle->cantidad }}</span>
                            @if($detalle->cantidad > 1)
                                <br>
                                <span class="text-gray-500 text-xs">P.Un. ${{ number_format($detalle->punitario, 2) }}</span>
                            @endif
                            @if($detalle->descup > 0)
                                <span class="text-gray-500 text-xs"> (Desc. {{ number_format($detalle->descup, 2) }}%)</span>
                            @endif
                        </div>
                        <div class="text-right mr-4">
                            <span class="font-semibold text-base" style="color: #FFAF22;">${{ number_format($detalle->cantidad * $detalle->punitario , 2) }}</span>
                        </div>
                        <div>
                            <button @click="confirmarEliminacion({{ $detalle->id }})"
                                    class="text-red-500 hover:text-red-600 hover:bg-red-50 p-3 rounded-lg transition-colors min-w-[44px] min-h-[44px] flex items-center justify-center">
                                <i class="fa fa-trash text-lg"></i>
                            </button>
                        </div>
                        @php $total += $detalle->cantidad * $detalle->punitario; @endphp
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center text-gray-500 py-10">
                <i class="fa fa-shopping-cart text-3xl mb-2 text-gray-400"></i><br>
                No hay productos en el pedido
            </div>
        @endif
    </div>

    {{-- Totales y acciones --}}
    <div class="bg-white rounded shadow p-4">
        <div class="flex justify-between items-center mb-4">
            <span class="text-lg font-bold text-gray-800">Total</span>
            <span class="text-2xl font-bold" style="color: #FFAF22;">${{ number_format($total, 2) }}</span>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 justify-end">
            <button wire:click="imprimirPedido"
                    class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded shadow flex items-center justify-center">
                <i class="fa fa-print mr-2"></i>
                Imprimir Pedido
            </button>
            <a href="{{route('dashboard-vendedor')}}" wire:navigate
                    class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded shadow text-center">
                Guardar Pedido
            </a>
        </div>
    </div>

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
