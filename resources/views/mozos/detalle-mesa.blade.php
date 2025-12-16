<div class="flex flex-col h-full bg-gray-50" x-data="{
    showConfirm: false,
    confirmAction: null,
    confirmMessage: '',
    confirmar(action, message) {
        this.confirmAction = action;
        this.confirmMessage = message;
        this.showConfirm = true;
    },
    ejecutar() {
        if (this.confirmAction) this.confirmAction();
        this.showConfirm = false;
    }
}">
    @if($error)
        <!-- Vista de Error -->
        <div class="flex items-center justify-center h-full p-4">
            <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-center mb-4">
                    <i class="fa fa-exclamation-triangle text-red-500 text-5xl"></i>
                </div>
                <h2 class="text-xl font-bold text-center text-gray-800 mb-4">Error Apertura de Mesa</h2>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <p class="text-red-700 text-center">{{ $mensajeError }}</p>
                </div>
                <a href="{{ route('mozos.mesas') }}"
                   wire:navigate
                   class="block text-center py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition font-semibold">
                    <i class="fa fa-arrow-left mr-2"></i> Volver al panel de mesas
                </a>
            </div>
        </div>
    @else
        <!-- Header compacto -->
        <div class="bg-white shadow-sm p-3 flex-shrink-0">
            <div class="flex justify-between items-center mb-2">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Mesa N° {{ $numeroMesa }}</h2>
                    <p class="text-xs text-gray-600">{{ $mesa->fechaaper }} {{ $mesa->horaaper }}</p>
                </div>
                <button wire:click="verPromociones"
                        {{ ($requiereComensales && $comensales < 1) ? 'disabled' : '' }}
                        class="px-3 py-2 rounded-lg text-sm {{ ($requiereComensales && $comensales < 1) ? 'bg-gray-400 text-gray-200 cursor-not-allowed' : 'bg-yellow-500 text-white hover:bg-yellow-600' }}">
                    <i class="fa fa-tags"></i>
                </button>
            </div>

            <!-- Comensales destacados -->
            <div class="flex items-center justify-between {{ $requiereComensales && $comensales < 1 ? 'bg-red-50 border-red-400' : 'bg-yellow-50 border-yellow-300' }} border rounded-lg px-3 py-2">
                <label class="text-sm font-bold text-gray-700 flex items-center gap-2">
                    <i class="fa fa-users {{ $requiereComensales && $comensales < 1 ? 'text-red-600' : 'text-yellow-600' }}"></i>
                    Comensales:
                    @if($requiereComensales)
                        <span class="text-red-600 text-xs">(Obligatorio)</span>
                    @endif
                </label>
                <div class="flex items-center gap-2">
                    <button wire:click="decrementarComensales"
                            class="px-2 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600">
                        <i class="fa fa-minus text-xs"></i>
                    </button>
                    <input type="number"
                           wire:model.blur="comensales"
                           wire:change="actualizarComensales"
                           min="{{ $cobraCubierto && count($detalle) > 0 ? 1 : 0 }}"
                           max="25"
                           class="w-16 px-2 py-1 border-2 {{ $requiereComensales && $comensales < 1 ? 'border-red-500' : 'border-yellow-500' }} rounded-lg text-center text-base font-bold">
                    <button wire:click="incrementarComensales"
                            class="px-2 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600">
                        <i class="fa fa-plus text-xs"></i>
                    </button>
                </div>
            </div>

            @if($requiereComensales && $comensales < 1)
                <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded-lg text-xs flex items-center gap-2">
                    <i class="fa fa-exclamation-circle"></i>
                    <span>Debe ingresar la cantidad de comensales para poder agregar productos</span>
                </div>
            @endif

            <!-- Total visible -->
            <div class="flex justify-between items-center bg-gray-50 rounded-lg px-3 py-2">
                <span class="text-sm font-semibold text-gray-700">Total:</span>
                <span class="text-lg font-bold text-green-600">${{ number_format($total, 2) }}</span>
            </div>
        </div>

        <!-- Área de contenido scrolleable -->
        <div class="flex-1 overflow-y-auto p-2 pb-16">
            <!-- Mensajes Flash -->
            @if (session()->has('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-3 py-2 rounded-lg mb-2 text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if (session()->has('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg mb-2 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Detalle del Pedido -->
            @if(count($detalle) > 0)
                <div class="space-y-2">
                    @foreach($detalle as $item)
                        <div class="bg-white rounded-lg shadow-sm p-3">
                            <div class="flex justify-between items-start">
                                <div class="flex-1 pr-2">
                                    <div class="font-semibold text-gray-800 text-sm">
                                        {{ $item->NOMART }}
                                    </div>
                                    @if($item->seleccion)
                                        <div class="text-xs text-gray-600 mt-0.5">{{ $item->seleccion }}</div>
                                    @endif
                                    @if($item->observa)
                                        <div class="text-xs text-gray-600 mt-0.5">{{ $item->observa }}</div>
                                    @endif
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs text-gray-600">
                                            Cant: <span class="font-bold">{{ $item->CANTIDAD }}</span>
                                        </span>
                                        @if($item->IMPRESA)
                                            <span class="text-xs text-blue-600">
                                                <i class="fa fa-print"></i>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-green-600 text-sm">
                                        ${{ number_format($item->TOTAL, 2) }}
                                    </div>
                                    @if(!str_starts_with(strtoupper($item->NOMART), 'SERVICIO DE MESA') && ((session('mozo_borracc') && $item->IMPRESA) || (session('mozo_borrasc') && !$item->IMPRESA)))
                                        <button @click="confirmar(() => $wire.eliminarProducto({{ $item->RENGLON }}), '¿Eliminar este producto?')"
                                                class="mt-1 px-2 py-0.5 bg-red-500 text-white rounded text-xs">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 text-gray-400">
                    <i class="fa fa-shopping-cart text-4xl mb-2"></i>
                    <p class="text-sm">Sin productos</p>
                </div>
            @endif

        </div>

        <!-- Barra de navegación inferior fija -->
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg">
            <div class="grid gap-1 p-2" style="grid-template-columns: repeat(auto-fit, minmax(60px, 1fr));">
                <!-- Botón Agregar -->
                <button wire:click="mostrarBuscador"
                        {{ ($requiereComensales && $comensales < 1) ? 'disabled' : '' }}
                        class="flex flex-col items-center justify-center py-2 rounded-lg transition
                               {{ ($requiereComensales && $comensales < 1)
                                  ? 'bg-gray-400 text-gray-200 cursor-not-allowed'
                                  : ($mostrarBusqueda
                                      ? 'bg-green-600 text-white'
                                      : 'bg-green-500 text-white hover:bg-green-600') }}">
                    <i class="fa fa-plus-circle text-base mb-0.5"></i>
                    <span class="text-xs font-semibold">Agregar</span>
                </button>

                <!-- Botón Comanda -->
                @if(session('mozo_comanda'))
                    <button wire:click="enviarComanda"
                            {{ ($requiereComensales && $comensales < 1) ? 'disabled' : '' }}
                            class="flex flex-col items-center justify-center py-2 rounded-lg transition
                                   {{ ($requiereComensales && $comensales < 1)
                                      ? 'bg-gray-400 text-gray-200 cursor-not-allowed'
                                      : 'bg-blue-500 text-white hover:bg-blue-600' }}">
                        <i class="fa fa-utensils text-base mb-0.5"></i>
                        <span class="text-xs font-semibold">Comanda</span>
                    </button>
                @endif

                <!-- Botón Precuenta -->
                @if(session('mozo_precuenta'))
                    <button wire:click="enviarPrecuenta"
                            {{ ($requiereComensales && $comensales < 1) ? 'disabled' : '' }}
                            class="flex flex-col items-center justify-center py-2 rounded-lg transition
                                   {{ ($requiereComensales && $comensales < 1)
                                      ? 'bg-gray-400 text-gray-200 cursor-not-allowed'
                                      : 'bg-purple-500 text-white hover:bg-purple-600' }}">
                        <i class="fa fa-file-invoice text-base mb-0.5"></i>
                        <span class="text-xs font-semibold">Precuenta</span>
                    </button>
                @endif

                <!-- Botón Cancelar (si aplica) -->
                @if($comensales > 0 && (count($detalle) == 0 || $soloServicioMesa))
                    <button @click="confirmar(() => $wire.cancelarMesa(), '¿Cancelar esta mesa?')"
                            class="flex flex-col items-center justify-center py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                        <i class="fa fa-times text-base mb-0.5"></i>
                        <span class="text-xs font-semibold">Cancelar</span>
                    </button>
                @endif

                <!-- Botón Volver -->
                <a href="{{ route('mozos.mesas') }}"
                   wire:navigate
                   class="flex flex-col items-center justify-center py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                    <i class="fa fa-arrow-left text-base mb-0.5"></i>
                    <span class="text-xs font-semibold">Volver</span>
                </a>
            </div>
        </div>

        <!-- Modal de Confirmación -->
        <div x-show="showConfirm"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
             style="display: none;">
            <div @click.away="showConfirm = false"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-90"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-90"
                 class="bg-white rounded-lg shadow-xl max-w-sm w-full p-6">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                        <i class="fa fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4" x-text="confirmMessage"></h3>
                    <div class="flex gap-3">
                        <button @click="showConfirm = false"
                                class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-semibold">
                            Cancelar
                        </button>
                        <button @click="ejecutar()"
                                class="flex-1 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 font-semibold">
                            Confirmar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Buscador de Productos -->
        @if($mostrarBusqueda)
            <div x-data="{ init() { $nextTick(() => this.$refs.buscadorInput.focus()) } }"
                 x-init="init()"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50">
                <div @click.away="$wire.mostrarBuscador()"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-90"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-90"
                     class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[80vh] flex flex-col">

                    <!-- Header del modal -->
                    <div class="flex justify-between items-center p-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Buscar Producto</h3>
                        <button wire:click="mostrarBuscador"
                                class="text-gray-500 hover:text-gray-700">
                            <i class="fa fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Input de búsqueda -->
                    <div class="p-4 border-b">
                        <input type="text"
                               wire:model.live.debounce.300ms="busqueda"
                               placeholder="Nombre o código..."
                               class="w-full px-4 py-3 border-2 border-yellow-500 rounded-lg focus:ring-2 focus:ring-yellow-500 text-sm"
                               x-ref="buscadorInput">
                    </div>

                    <!-- Resultados -->
                    <div class="flex-1 overflow-y-auto p-4">
                        @if(count($productos) > 0)
                            <div class="space-y-2">
                                @foreach($productos as $producto)
                                    <button wire:click="seleccionarProducto({{ $producto->codigo }})"
                                            class="w-full text-left p-3 border rounded-lg hover:bg-gray-50 transition hover:border-yellow-500">
                                        <div class="flex justify-between items-center">
                                            <div class="flex-1 pr-2">
                                                <div class="font-medium text-gray-800 text-sm">{{ $producto->nombre }}</div>
                                                <div class="text-xs text-gray-600">
                                                    Cód: {{ $producto->codigo }}
                                                    @if($producto->agotado)
                                                        <span class="text-red-500 font-semibold">(Agotado)</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="text-green-600 font-bold text-sm">
                                                ${{ number_format($producto->precio, 2) }}
                                            </div>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @elseif(strlen($busqueda) >= 2)
                            <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                                <i class="fa fa-search text-4xl mb-3"></i>
                                <p class="text-sm">No se encontraron productos</p>
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                                <i class="fa fa-keyboard text-4xl mb-3"></i>
                                <p class="text-sm">Escribe para buscar...</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
