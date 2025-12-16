<!-- resources/views/livewire/kiosco/cart.blade.php -->

<div class="h-full flex flex-col bg-white/90 backdrop-blur-sm overflow-y-auto" id="main">
    
        <!-- Header del carrito -->
        <div class="bg-gradient-to-r from-[#FFAF22] to-[#FF9500] px-4 sm:px-8 py-4 sm:py-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl sm:text-3xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 mr-2 sm:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 2.5M7 13l2.5 2.5m6-2.5a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
                    </svg>
                    Tu Pedido
                </h1>
                
                @if(count($cart) > 0)
                    <button wire:click="clearCart" 
                            wire:confirm="¿Estás seguro de que quieres vaciar el pedido?"
                            class="bg-red-500 hover:bg-red-600 text-white px-2 sm:px-4 py-2 rounded-lg transition-all duration-300 hover:shadow-lg flex items-center text-sm sm:text-base">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span class="hidden sm:inline">Vaciar</span>
                    </button>
                @endif
            </div>
        </div>

        <div class="p-4 sm:p-8">
            @if(count($cart) == 0)
                <!-- Carrito vacío -->
                <div class="text-center py-8 sm:py-16">
                    <svg class="w-16 h-16 sm:w-24 sm:h-24 mx-auto text-[#FFAF22] mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 2.5M7 13l2.5 2.5m6-2.5a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
                    </svg>
                    <h2 class="text-xl sm:text-2xl font-semibold text-gray-600 mb-2">Tu pedido está vacío</h2>
                    <p class="text-gray-500 mb-6 text-sm sm:text-base px-4">Agrega algunos productos deliciosos para comenzar tu pedido</p>
                    <button wire:click="changeView('menu')" 
                            class="bg-gradient-to-r from-[#FFAF22] to-[#FF9500] hover:from-[#FF9500] hover:to-[#FF8400] text-white font-bold py-3 px-6 sm:px-8 rounded-lg transition-all duration-300 hover:shadow-lg transform hover:scale-105">
                        Ver Menú
                    </button>
                </div>
            @else
                <!-- Items del carrito -->
                <div class="space-y-4 sm:space-y-6 mb-6 sm:mb-8">
                    @foreach($cart as $item)
                        <div class="bg-gradient-to-r from-[#FFAF22]/10 to-[#FFAF22]/5 rounded-xl p-3 sm:p-6 border border-[#FFAF22]/20 hover:shadow-lg transition-all duration-300">
                            <!-- Layout móvil -->
                            <div class="block sm:hidden">
                                <!-- Fila superior: imagen, nombre y eliminar -->
                                <div class="flex items-start space-x-3 mb-3">
                                    <div class="flex-shrink-0">
                                        <img src="{{ $this->getProductImageUrl($item['product_id'], true) }}" 
                                                alt="{{ $item['nombre'] }}"
                                                class="w-16 h-16 object-cover rounded-lg shadow-md"
                                                onerror="this.src='{{ asset('img/nofoto1.jpg') }}'">
                                    </div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-semibold text-gray-800 mb-1 truncate">
                                            {{ $item['nombre'] }}
                                        </h3>
                                        
                                        @if($item['solo_efectivo'])
                                            <span class="inline-block bg-amber-100 text-amber-800 px-2 py-1 rounded-full text-xs font-medium">
                                                Solo Efectivo
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <button wire:click="removeFromCart('{{ $item['id'] }}')"
                                            wire:confirm="¿Quieres eliminar este producto del pedido?"
                                            class="text-red-500 hover:text-red-700 transition-all duration-300 p-1 rounded-lg hover:bg-red-50 flex-shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                                
                                <!-- Opcionales -->
                                @if(count($item['opcionales']) > 0)
                                    <div class="text-sm text-gray-600 space-y-1 bg-white rounded-lg p-2 mb-3">
                                        @foreach($item['opcionales'] as $opcional)
                                            <div class="flex justify-between text-xs">
                                                <span class="flex items-center">
                                                    <span class="w-1 h-1 bg-[#FFAF22] rounded-full mr-2"></span>
                                                    {{ $opcional['nombre'] }}
                                                </span>                                                
                                                @if($opcional['precio'] > 0)
                                                    <span class="text-green-600 font-semibold">+${{ number_format($opcional['precio'], 2) }}</span>
                                                @endif

                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                
                                <!-- Fila inferior: controles y precio -->
                                <div class="flex items-center justify-between">
                                    <!-- Controles de cantidad -->
                                    <div class="flex items-center space-x-2">
                                        <button wire:click="updateCartQuantity('{{ $item['id'] }}', {{ $item['quantity'] - 1 }})"
                                                class="bg-[#FFAF22] hover:bg-[#FF9500] text-white rounded-full w-8 h-8 flex items-center justify-center transition-all duration-300 hover:shadow-lg">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                            </svg>
                                        </button>
                                        
                                        <span class="text-lg font-semibold text-gray-800 w-8 text-center bg-white rounded-lg py-1 px-1 shadow-sm">{{ $item['quantity'] }}</span>
                                        
                                        <button wire:click="updateCartQuantity('{{ $item['id'] }}', {{ $item['quantity'] + 1 }})"
                                                class="bg-[#FFAF22] hover:bg-[#FF9500] text-white rounded-full w-8 h-8 flex items-center justify-center transition-all duration-300 hover:shadow-lg">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    <!-- Precio -->
                                    <div class="text-right bg-white rounded-lg p-2 shadow-sm">
                                        <div class="text-lg font-bold text-gray-800">${{ number_format($item['total'], 2) }}</div>
                                        @if($item['quantity'] > 1)
                                            <div class="text-xs text-gray-500">${{ number_format($item['precio'], 2) }} c/u</div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Layout escritorio (mantiene el diseño original) -->
                            <div class="hidden sm:flex items-center space-x-6">
                                <!-- Imagen del producto -->
                                <div class="flex-shrink-0">
                                    <img src="{{ $this->getProductImageUrl($item['product_id'], true) }}" 
                                            alt="{{ $item['nombre'] }}"
                                            class="w-20 h-20 object-cover rounded-lg shadow-md"
                                            onerror="this.src='{{ asset('img/nofoto1.jpg') }}'">
                                </div>

                                <!-- Información del producto -->
                                <div class="flex-1">
                                    <h3 class="text-xl font-semibold text-gray-800 mb-2 flex items-center">
                                        <span class="w-2 h-2 bg-[#FFAF22] rounded-full mr-3"></span>
                                        {{ $item['nombre'] }}
                                    </h3>
                                    
                                    <!-- Opcionales seleccionados -->
                                    @if(count($item['opcionales']) > 0)
                                        <div class="text-sm text-gray-600 space-y-1 bg-white rounded-lg p-3 ml-5">
                                            @foreach($item['opcionales'] as $opcional)
                                                <div class="flex justify-between">
                                                    <span class="flex items-center">
                                                        <span class="w-1 h-1 bg-[#FFAF22] rounded-full mr-2"></span>
                                                         {{ $opcional['nombre'] }}
                                                         @if($opcional['quantity']>0) 
                                                            ({{$opcional['quantity']}})
                                                         @endif
                                                    </span>
                                                    @if($opcional['precio'] > 0)
                                                        <span class="text-green-600 font-semibold">+${{ number_format($opcional['precio'], 2) }}</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if($item['solo_efectivo'])
                                        <span class="inline-block bg-amber-100 text-amber-800 px-3 py-1 rounded-full text-xs font-medium mt-2">
                                            Solo Efectivo
                                        </span>
                                    @endif
                                </div>

                                <!-- Controles de cantidad -->
                                <div class="flex items-center space-x-3">
                                    <button wire:click="updateCartQuantity('{{ $item['id'] }}', {{ $item['quantity'] - 1 }})"
                                            class="bg-[#FFAF22] hover:bg-[#FF9500] text-white rounded-full w-10 h-10 flex items-center justify-center transition-all duration-300 hover:shadow-lg transform hover:scale-110">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                        </svg>
                                    </button>
                                    
                                    <span class="text-xl font-semibold text-gray-800 w-8 text-center bg-white rounded-lg py-2 px-2 shadow-sm">{{ $item['quantity'] }}</span>
                                    
                                    <button wire:click="updateCartQuantity('{{ $item['id'] }}', {{ $item['quantity'] + 1 }})"
                                            class="bg-[#FFAF22] hover:bg-[#FF9500] text-white rounded-full w-10 h-10 flex items-center justify-center transition-all duration-300 hover:shadow-lg transform hover:scale-110">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Precio total del item -->
                                <div class="text-right bg-white rounded-lg p-3 shadow-sm">
                                    <div class="text-2xl font-bold text-gray-800">${{ number_format($item['total'], 2) }}</div>
                                    @if($item['quantity'] > 1)
                                        <div class="text-sm text-gray-500">${{ number_format($item['precio'], 2) }} c/u</div>
                                    @endif
                                </div>

                                <!-- Botón eliminar -->
                                <button wire:click="removeFromCart('{{ $item['id'] }}')"
                                        wire:confirm="¿Quieres eliminar este producto del pedido?"
                                        class="text-red-500 hover:text-red-700 transition-all duration-300 hover:scale-110 p-2 rounded-lg hover:bg-red-50">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Resumen del pedido -->
                <div class="bg-gradient-to-br from-[#FFAF22]/10 to-[#FFAF22]/5 rounded-xl p-4 sm:p-6 border border-[#FFAF22]/20">
                    <div class="flex justify-between items-center mb-3 sm:mb-4 pb-3 sm:pb-4 border-b border-[#FFAF22]/20">
                        <span class="text-lg sm:text-xl font-semibold text-gray-700 flex items-center">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-[#FFAF22]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <span class="hidden sm:inline">Total de productos:</span>
                            <span class="sm:hidden">Productos:</span>
                        </span>
                        <span class="text-lg sm:text-xl font-semibold text-gray-700 bg-white rounded-lg px-2 sm:px-3 py-1">{{ count($cart) }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center mb-4 sm:mb-6 pb-3 sm:pb-4 border-b border-[#FFAF22]/20">
                        <span class="text-xl sm:text-2xl font-bold text-gray-800 flex items-center">
                            <svg class="w-6 h-6 sm:w-7 sm:h-7 mr-2 text-[#FFAF22]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                            <span class="hidden sm:inline">Total a pagar:</span>
                            <span class="sm:hidden">Total:</span>
                        </span>
                        <span class="text-2xl sm:text-3xl font-bold text-green-600 bg-white rounded-lg px-3 sm:px-4 py-2 shadow-sm">${{ number_format($this->getCartTotal(), 2) }}</span>
                    </div>

                    @if($this->hasOnlyEffectiveProducts())
                        <div class="bg-amber-100 border border-amber-300 rounded-lg p-3 sm:p-4 mb-4 sm:mb-6">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-amber-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <span class="text-amber-800 font-medium text-sm">Tu pedido contiene productos que solo se pueden pagar en efectivo</span>
                            </div>
                        </div>
                    @endif

                    <!-- Botones de acción -->
                    <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4">
                        <button wire:click="changeView('menu')" 
                                class="w-full sm:flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 sm:py-4 px-4 sm:px-6 rounded-lg transition-all duration-300 hover:shadow-lg flex items-center justify-center">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Seguir Comprando
                        </button>
                        
                        <button wire:click="changeView('checkout')" 
                                class="w-full sm:flex-2 bg-gradient-to-r from-[#FFAF22] to-[#FF9500] hover:from-[#FF9500] hover:to-[#FF8400] text-white font-bold py-3 sm:py-4 px-4 sm:px-8 rounded-lg transition-all duration-300 hover:shadow-lg transform hover:scale-105 flex items-center justify-center">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Proceder al Pago
                        </button>
                    </div>
                </div>
            @endif
        </div>    
</div>
