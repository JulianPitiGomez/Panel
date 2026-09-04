<div>
    @if($pedidoConfirmado)
        <!-- Pantalla de éxito -->
        <div class="max-w-lg mx-auto text-center py-12">
            <div class="bg-white rounded-2xl shadow-sm p-8">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-check text-green-500 text-4xl"></i>
                </div>
                <h2 class="text-2xl font-bold mb-2" style="color: {{ $colores['texto'] }}">Pedido confirmado</h2>
                <p class="text-gray-500 mb-4">Tu pedido #{{ $numeroPedido }} fue registrado correctamente.</p>
                <p class="text-sm text-gray-400 mb-8">Recibiras novedades sobre tu pedido.</p>

                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <button wire:click="irAMisPedidos"
                            class="px-6 py-3 text-white font-semibold rounded-lg transition-opacity hover:opacity-90"
                            style="background-color: {{ $colores['precio'] }}">
                        <i class="fas fa-clipboard-list mr-2"></i> Ver mis pedidos
                    </button>
                    <button wire:click="volverAlCatalogo"
                            class="px-6 py-3 text-white font-semibold rounded-lg transition-opacity hover:opacity-90"
                            style="background-color: {{ $colores['boton'] }}">
                        <i class="fas fa-arrow-left mr-2"></i> Volver a la tienda
                    </button>
                </div>
            </div>
        </div>
    @elseif(count($carrito) === 0)
        <!-- Carrito vacío -->
        <div class="max-w-lg mx-auto text-center py-12">
            <div class="bg-white rounded-2xl shadow-sm p-8">
                <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
                <h2 class="text-xl font-bold mb-2" style="color: {{ $colores['texto'] }}">Tu carrito esta vacio</h2>
                <p class="text-gray-500 mb-6">Agrega productos desde el catalogo.</p>
                <button wire:click="volverAlCatalogo"
                        class="px-6 py-3 text-white font-semibold rounded-lg transition-opacity hover:opacity-90"
                        style="background-color: {{ $colores['boton'] }}">
                    <i class="fas fa-arrow-left mr-2"></i> Ir al catalogo
                </button>
            </div>
        </div>
    @else
        <div class="grid lg:grid-cols-3 gap-6" x-data="{ confirmVaciar: false }">
            <!-- Lista de items (2/3) -->
            <div class="lg:col-span-2 space-y-3">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-xl font-bold" style="color: {{ $colores['texto'] }}">
                        <i class="fas fa-shopping-cart mr-2" style="color: {{ $colores['boton'] }}"></i> Mi carrito
                    </h2>
                    <button @click="confirmVaciar = true"
                            class="text-sm text-red-500 hover:text-red-700">
                        <i class="fas fa-trash mr-1"></i> Vaciar
                    </button>
                </div>

                {{-- Modal confirmación vaciar carrito --}}
                <div x-show="confirmVaciar" x-transition.opacity
                     class="fixed inset-0 z-[60] flex items-center justify-center p-4"
                     style="display: none;">
                    <div class="absolute inset-0 bg-black/50" @click="confirmVaciar = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
                        <div class="flex items-start gap-3 mb-4">
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-base font-bold text-gray-800">¿Vaciar carrito?</h3>
                                <p class="text-sm text-gray-500 mt-1">Se eliminarán todos los productos del carrito. Esta acción no se puede deshacer.</p>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-5">
                            <button @click="confirmVaciar = false" type="button"
                                    class="flex-1 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-semibold text-sm">
                                Cancelar
                            </button>
                            <button @click="confirmVaciar = false; $wire.vaciarCarrito()" type="button"
                                    class="flex-1 py-2.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition font-semibold text-sm">
                                <i class="fas fa-trash mr-1"></i> Vaciar
                            </button>
                        </div>
                    </div>
                </div>

                @foreach($carrito as $index => $item)
                    <div class="bg-white rounded-xl shadow-sm p-3 sm:p-4">
                        <!-- Fila superior: imagen + info + eliminar -->
                        <div class="flex gap-3 items-start">
                            <div class="w-14 h-14 sm:w-16 sm:h-16 bg-gray-100 rounded-lg flex-shrink-0 flex items-center justify-center overflow-hidden">
                                @if($item['foto_url'])
                                    <img src="{{ $item['foto_url'] }}" alt="{{ $item['nombre'] }}" class="w-full h-full object-cover">
                                @else
                                    <i class="fas fa-image text-gray-300"></i>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-medium truncate" style="color: {{ $colores['texto'] }}">{{ $item['nombre'] }}</h3>
                                <p class="text-xs text-gray-400">Cod: {{ $item['codigo'] }}</p>
                                <p class="text-sm font-bold mt-1" style="color: {{ $colores['precio'] }}">
                                    ${{ number_format($item['precio'], 2, ',', '.') }}
                                </p>
                            </div>
                            <button wire:click="eliminarItem({{ $index }})"
                                    class="text-red-400 hover:text-red-600 transition flex-shrink-0 p-1">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <!-- Fila inferior: cantidad + subtotal -->
                        <div class="flex items-center justify-between mt-3 pt-2 border-t border-gray-100">
                            <div class="flex items-center gap-2">
                                <button wire:click="decrementarCantidad({{ $index }})"
                                        class="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center text-gray-600 transition">
                                    <i class="fas fa-minus text-xs"></i>
                                </button>
                                <span class="w-8 text-center font-semibold text-sm" style="color: {{ $colores['texto'] }}">{{ $item['cantidad'] }}</span>
                                <button wire:click="incrementarCantidad({{ $index }})"
                                        class="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center text-gray-600 transition">
                                    <i class="fas fa-plus text-xs"></i>
                                </button>
                            </div>
                            <p class="text-sm font-bold" style="color: {{ $colores['precio'] }}">${{ number_format($item['subtotal'], 2, ',', '.') }}</p>
                        </div>
                    </div>
                @endforeach

                <div class="mt-2">
                    <button wire:click="volverAlCatalogo"
                            class="text-sm hover:underline"
                            style="color: {{ $colores['boton'] }}">
                        <i class="fas fa-arrow-left mr-1"></i> Seguir comprando
                    </button>
                </div>
            </div>

            <!-- Panel de checkout (1/3) -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm p-6 sticky top-20">
                    <h3 class="text-lg font-bold mb-4" style="color: {{ $colores['texto'] }}">Resumen del pedido</h3>

                    <!-- Validación de email (solo modo público) -->
                    @if($modo === \App\Services\TiendaService::MODO_PUBLICA && !$compradorValidado)
                        <div class="mb-4 p-4 bg-blue-50 rounded-lg">
                            <p class="text-sm text-blue-700 mb-3">
                                <i class="fas fa-info-circle mr-1"></i>
                                Para confirmar tu pedido, ingresa tu email registrado.
                            </p>
                            <div class="space-y-2">
                                <input type="email" wire:model="emailComprador"
                                       placeholder="tu@email.com"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 outline-none"
                                       style="--tw-ring-color: {{ $colores['boton'] }}"
                                       wire:keydown.enter="validarEmail">
                                <button wire:click="validarEmail"
                                        wire:loading.attr="disabled"
                                        class="w-full px-4 py-2 text-white text-sm font-semibold rounded-lg transition-opacity hover:opacity-90"
                                        style="background-color: {{ $colores['precio'] }}">
                                    <span wire:loading.remove wire:target="validarEmail">Validar</span>
                                    <span wire:loading wire:target="validarEmail"><i class="fas fa-spinner fa-spin"></i></span>
                                </button>
                            </div>
                            @if($compradorError)
                                <p class="text-sm text-red-500 mt-2">{{ $compradorError }}</p>
                            @endif
                        </div>
                    @endif

                    <!-- Info del comprador validado -->
                    @if($compradorValidado)
                        <div class="mb-4 p-3 bg-green-50 rounded-lg flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <div>
                                <p class="text-sm font-medium text-green-800">{{ $compradorNombre }}</p>
                                <p class="text-xs text-green-600">Cliente verificado</p>
                            </div>
                        </div>
                    @endif

                    <!-- Fecha de entrega -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de entrega</label>
                        <input type="date" wire:model="parafecha"
                               min="{{ date('Y-m-d') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 outline-none"
                               style="--tw-ring-color: {{ $colores['boton'] }}">
                    </div>

                    <!-- Observaciones -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                        <textarea wire:model="observaciones"
                                  rows="2"
                                  placeholder="Notas para tu pedido..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 outline-none resize-none"
                                  style="--tw-ring-color: {{ $colores['boton'] }}"></textarea>
                    </div>

                    <hr class="my-4">

                    <!-- Resumen -->
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Productos ({{ count($carrito) }})</span>
                            <span class="font-medium">${{ number_format($total, 2, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="flex justify-between text-lg font-bold border-t pt-3">
                        <span style="color: {{ $colores['texto'] }}">Total</span>
                        <span style="color: {{ $colores['precio'] }}">${{ number_format($total, 2, ',', '.') }}</span>
                    </div>

                    <!-- Botón confirmar -->
                    <button wire:click="confirmarPedido"
                            wire:loading.attr="disabled"
                            @if(!$compradorValidado) disabled @endif
                            class="w-full mt-4 py-3 text-white font-semibold rounded-lg transition-opacity hover:opacity-90 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                            style="background-color: {{ $colores['boton'] }}">
                        <span wire:loading.remove wire:target="confirmarPedido">
                            <i class="fas fa-check mr-2"></i> Confirmar pedido
                        </span>
                        <span wire:loading wire:target="confirmarPedido">
                            <i class="fas fa-spinner fa-spin mr-2"></i> Procesando...
                        </span>
                    </button>

                    @if(session()->has('error'))
                        <p class="text-sm text-red-500 mt-2 text-center">{{ session('error') }}</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- WhatsApp floating button --}}
    @php $telefonoWa = session('tienda_datos')['telefono'] ?? null; @endphp
    @if($telefonoWa)
        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $telefonoWa) }}"
           target="_blank"
           class="fixed bottom-6 right-6 z-50 w-14 h-14 bg-[#25D366] text-white rounded-full shadow-lg flex items-center justify-center hover:bg-[#1ebe57] transition-colors hover:scale-110 transform duration-200">
            <i class="fab fa-whatsapp text-2xl"></i>
        </a>
    @endif
</div>
