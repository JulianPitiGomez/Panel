{{-- CHECKOUT VIEW --}}
<div class="h-full flex flex-col bg-white/90 backdrop-blur-sm overflow-y-auto" id="main"> 
    <!-- Header del checkout -->
    <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-8 py-6">
        <h1 class="text-3xl font-bold text-white flex items-center">
            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            Finalizar Pedido
        </h1>
    </div>

    <div class="p-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Formulario de datos del cliente -->
            <div class="space-y-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                        <span class="w-3 h-3 bg-orange-500 rounded-full mr-3"></span>
                        Datos del Cliente
                    </h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="nombre" class="block text-lg font-medium text-gray-700 mb-2">
                                Nombre Completo *
                            </label>
                            <input type="text" 
                                    id="nombre"
                                    wire:model="customerData.nombre"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500 text-lg transition-all duration-300"
                                    placeholder="Ingresa tu nombre completo">
                            @error('customerData.nombre') 
                                <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                            @enderror
                        </div>

                        <div>
                            <label for="telefono" class="block text-lg font-medium text-gray-700 mb-2">
                                Teléfono *
                            </label>
                            <input type="tel" 
                                    id="telefono"
                                    wire:model="customerData.telefono"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500 text-lg transition-all duration-300"
                                    placeholder="Ej: 011-1234-5678">
                            @error('customerData.telefono') 
                                <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Forma de pago -->
                <div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                        <span class="w-3 h-3 bg-orange-500 rounded-full mr-3"></span>
                        Forma de Pago
                    </h3>
                    
                    @if($this->hasOnlyEffectiveProducts())
                        <div class="bg-amber-100 border border-amber-300 rounded-lg p-4 mb-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-amber-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <span class="text-amber-800 font-medium">Solo puedes pagar en efectivo por los productos seleccionados</span>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-3">
                        @foreach($this->getAvailablePaymentMethods() as $formaPago)
                            <label class="flex items-center p-4 bg-gradient-to-r from-orange-50 to-orange-100/50 rounded-lg border-2 cursor-pointer transition-all hover:from-orange-100 hover:to-orange-200/50 hover:shadow-md {{ $selectedPayment == $formaPago->CODIGO ? 'border-orange-500 bg-gradient-to-r from-orange-100 to-orange-200 shadow-lg' : 'border-orange-200' }}">
                                <input type="radio" 
                                        name="payment_method"
                                        value="{{ $formaPago->CODIGO }}"
                                        wire:model="selectedPayment"
                                        class="w-5 h-5 text-orange-600">
                                <span class="ml-3 text-lg font-medium text-gray-800">{{ $formaPago->NOMBRE }}</span>
                                
                                @if($formaPago->TIPO == 1)
                                    <svg class="w-6 h-6 ml-auto text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                @elseif($formaPago->TIPO == 4)
                                    <svg class="w-6 h-6 ml-auto text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                @elseif($formaPago->TIPO == 2)
                                    <svg class="w-6 h-6 ml-auto text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                @elseif(in_array($formaPago->TIPO,[5,7,8])) 
                                    <svg class="w-6 h-6 ml-auto text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                    </svg>
                                @endif
                            </label>
                        @endforeach
                    </div>
                    
                    @error('selectedPayment') 
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                    @enderror
                </div>
            </div>

            <!-- Resumen del pedido -->
            <div class="bg-gradient-to-br from-orange-50 to-orange-100/50 rounded-xl p-6 border border-orange-200">
                <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                    <svg class="w-7 h-7 mr-3 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Resumen del Pedido
                </h3>
                
                <div class="space-y-4 mb-6">
                    @foreach($cart as $item)
                        <div class="flex justify-between items-start py-3 border-b border-orange-200 bg-white rounded-lg p-3">
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-800 flex items-center">
                                    <span class="w-2 h-2 bg-orange-500 rounded-full mr-2"></span>
                                    {{ $item['nombre'] }}
                                </h4>
                                
                                @if(count($item['opcionales']) > 0)
                                    <div class="text-sm text-gray-600 mt-1 ml-4">
                                        @foreach($item['opcionales'] as $opcional)
                                            <div class="flex items-center">
                                                <span class="w-1 h-1 bg-orange-400 rounded-full mr-2"></span>
                                                {{ $opcional['nombre'] }}
                                                @if($opcional['quantity']>0) 
                                                    (${{ number_format($opcional['precio'], 2) }}x {{$opcional['quantity']}})
                                                    <span class="text-green-600 font-semibold ml-auto">(+${{ number_format($opcional['precio']*$opcional['quantity'], 2) }})</span>
                                                @else
                                                    @if($opcional['precio'] > 0)
                                                        <span class="text-green-600 font-semibold ml-auto">(+${{ number_format($opcional['precio'], 2) }})</span>
                                                    @endif
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                
                                <div class="text-sm text-orange-600 font-medium mt-1 ml-4">
                                    Cantidad: {{ $item['quantity'] }}
                                </div>
                            </div>
                            
                            <div class="text-right bg-orange-100 rounded-lg px-3 py-1">
                                <span class="font-bold text-gray-800">${{ number_format($item['total'], 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Total -->
                <div class="border-t-2 border-orange-300 pt-4 bg-white rounded-lg p-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-lg font-medium text-gray-700">Subtotal:</span>
                        <span class="text-lg font-medium text-gray-700">${{ number_format($this->getCartTotal(), 2) }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-gray-800">Total:</span>
                        <span class="text-3xl font-bold text-green-600">${{ number_format($this->getCartTotal(), 2) }}</span>
                    </div>
                </div>

                <!-- Información adicional -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="text-blue-800 text-sm">
                            <p class="font-medium mb-1">Instrucciones importantes:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Recibirás un número de pedido para retirar en mostrador</li>
                                <li>El tiempo de preparación puede variar según la cantidad de pedidos</li>
                                <li>Conserva tu número de pedido hasta el retiro</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="flex space-x-4 mt-8 pt-6 border-t border-orange-200">
            <button wire:click="changeView('cart')" 
                    class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-4 px-6 rounded-lg transition-all duration-300 hover:shadow-lg flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Volver al Carrito
            </button>
            
            <button wire:click="finalizeOrder" 
                    class="flex-2 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-4 px-8 rounded-lg transition-all duration-300 hover:shadow-lg transform hover:scale-105 flex items-center justify-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Confirmar Pedido
            </button>
        </div>
    </div>            
</div>