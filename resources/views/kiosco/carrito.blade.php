{{-- CARRITO VIEW --}}
<div x-data="{ confirmarVaciar: false, confirmarEliminar: null }"
     class="h-full flex flex-col overflow-y-auto" id="main">

    <!-- Header compacto -->
    <div class="px-4 sm:px-6 py-3" style="background-color: var(--kiosco-boton);">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold flex items-center" style="color: var(--kiosco-texto-boton);">
                <i class="fas fa-shopping-cart mr-2"></i>
                Tu Pedido
            </h1>

            @if(count($cart) > 0)
                <button @click="confirmarVaciar = true"
                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg transition text-sm flex items-center">
                    <i class="fas fa-trash-alt mr-1"></i>
                    Vaciar
                </button>
            @endif
        </div>
    </div>

    <div class="p-4">
        @if(count($cart) == 0)
            <!-- Carrito vacío -->
            <div class="text-center py-12">
                <i class="fas fa-shopping-cart text-5xl mb-3" style="color: var(--kiosco-boton);"></i>
                <h2 class="text-lg font-semibold text-gray-600 mb-1">Tu pedido está vacío</h2>
                <p class="text-gray-500 text-sm mb-4">Agregá productos del menú para comenzar</p>
                <button wire:click="changeView('menu')"
                        class="hover:opacity-90 font-bold py-2.5 px-6 rounded-lg transition hover:shadow-lg"
                        style="background-color: var(--kiosco-boton); color: var(--kiosco-texto-boton);">
                    Ver Menú
                </button>
            </div>
        @else
            <!-- Items del carrito -->
            <div class="space-y-2 mb-4">
                @foreach($cart as $item)
                    <div class="bg-white rounded-lg p-3 border border-gray-200 shadow-sm">
                        <div class="flex items-center gap-3">
                            <!-- Imagen -->
                            <img src="{{ $this->getProductImageUrl($item['product_id'], true) }}"
                                 alt="{{ $item['nombre'] }}"
                                 class="w-14 h-14 object-cover rounded-lg flex-shrink-0"
                                 onerror="this.src='{{ asset('img/nofoto1.jpg') }}'">

                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-semibold text-gray-800 truncate">{{ $item['nombre'] }}</h3>

                                @if(count($item['opcionales']) > 0)
                                    <div class="text-xs text-gray-500 truncate">
                                        @foreach($item['opcionales'] as $opcional)
                                            + {{ $opcional['nombre'] }}@if(!$loop->last), @endif
                                        @endforeach
                                    </div>
                                @endif

                                @if($item['solo_efectivo'])
                                    <span class="text-xs text-amber-700 font-medium">Solo Efectivo</span>
                                @endif
                            </div>

                            <!-- Controles cantidad -->
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button wire:click="updateCartQuantity('{{ $item['id'] }}', {{ $item['quantity'] - 1 }})"
                                        class="rounded-full w-7 h-7 flex items-center justify-center text-sm font-bold hover:opacity-90 transition"
                                        style="background-color: var(--kiosco-boton); color: var(--kiosco-texto-boton);">
                                    -
                                </button>
                                <span class="w-7 text-center font-semibold text-sm">{{ $item['quantity'] }}</span>
                                <button wire:click="updateCartQuantity('{{ $item['id'] }}', {{ $item['quantity'] + 1 }})"
                                        class="rounded-full w-7 h-7 flex items-center justify-center text-sm font-bold hover:opacity-90 transition"
                                        style="background-color: var(--kiosco-boton); color: var(--kiosco-texto-boton);">
                                    +
                                </button>
                            </div>

                            <!-- Precio -->
                            <div class="text-right flex-shrink-0 min-w-[60px]">
                                <div class="font-bold text-gray-800 text-sm">${{ number_format($item['total'], 2) }}</div>
                                @if($item['quantity'] > 1)
                                    <div class="text-xs text-gray-400">${{ number_format($item['precio'], 2) }} c/u</div>
                                @endif
                            </div>

                            <!-- Eliminar -->
                            <button @click="confirmarEliminar = '{{ $item['id'] }}'"
                                    class="text-red-400 hover:text-red-600 transition flex-shrink-0">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Resumen y botones -->
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <div class="flex justify-between items-center mb-3 pb-3 border-b border-gray-200">
                    <span class="text-sm text-gray-600">Productos: {{ count($cart) }}</span>
                    <span class="text-2xl font-bold" style="color: var(--kiosco-precio);">${{ number_format($this->getCartTotal(), 2) }}</span>
                </div>

                @if($this->hasOnlyEffectiveProducts())
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-2 mb-3">
                        <p class="text-amber-800 text-xs">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Pedido solo pagable en efectivo
                        </p>
                    </div>
                @endif

                <div class="flex gap-3">
                    <button wire:click="changeView('menu')"
                            class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2.5 px-4 rounded-lg transition flex items-center justify-center text-sm">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Seguir Comprando
                    </button>

                    <button wire:click="changeView('checkout')"
                            class="flex-[2] hover:opacity-90 font-bold py-2.5 px-6 rounded-lg transition hover:shadow-lg flex items-center justify-center text-sm"
                            style="background-color: var(--kiosco-boton); color: var(--kiosco-texto-boton);">
                        <i class="fas fa-cash-register mr-2"></i>
                        Proceder al Pago
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal: Vaciar pedido -->
    <div x-show="confirmarVaciar" x-transition
         class="fixed inset-0 bg-black/50 z-[999] flex items-center justify-center p-4"
         @click.self="confirmarVaciar = false">
        <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-xs text-center" @click.stop>
            <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-trash-alt text-red-500 text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-800 mb-1">Vaciar pedido</h3>
            <p class="text-sm text-gray-500 mb-4">Se eliminarán todos los productos del carrito.</p>
            <div class="flex gap-2">
                <button @click="confirmarVaciar = false"
                        class="flex-1 py-2.5 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 transition">
                    Cancelar
                </button>
                <button @click="confirmarVaciar = false; $wire.clearCart()"
                        class="flex-1 py-2.5 bg-red-500 text-white rounded-lg text-sm font-medium hover:bg-red-600 transition">
                    Vaciar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: Eliminar producto -->
    <div x-show="confirmarEliminar" x-transition
         class="fixed inset-0 bg-black/50 z-[999] flex items-center justify-center p-4"
         @click.self="confirmarEliminar = null">
        <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-xs text-center" @click.stop>
            <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-times-circle text-red-500 text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-800 mb-1">Eliminar producto</h3>
            <p class="text-sm text-gray-500 mb-4">Se quitará este producto del pedido.</p>
            <div class="flex gap-2">
                <button @click="confirmarEliminar = null"
                        class="flex-1 py-2.5 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 transition">
                    Cancelar
                </button>
                <button @click="$wire.removeFromCart(confirmarEliminar); confirmarEliminar = null"
                        class="flex-1 py-2.5 bg-red-500 text-white rounded-lg text-sm font-medium hover:bg-red-600 transition">
                    Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
