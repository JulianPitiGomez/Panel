{{-- resources/views/livewire/kiosco/success.blade.php --}}
<div class="h-full flex flex-col bg-white/90 backdrop-blur-sm overflow-y-auto" id="main">        
    <!-- Header de éxito -->
    <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-8 py-2 items-center text-center">
        <div class="flex justify-center mb-4">
            <div class="bg-white rounded-full p-4 shadow-lg">
                <svg class="w-16 h-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
        <h1 class="text-4xl font-bold text-white mb-2">¡Pedido Confirmado!</h1>
        <p class="text-orange-100 text-lg">Tu pedido ha sido recibido correctamente</p>
    </div>

    <div class="p-8  items-center text-center">
        <!-- Número de pedido -->
        <div class="bg-gradient-to-br from-orange-50 to-orange-100/50 rounded-xl p-8 mb-8 border border-orange-200">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center justify-center">
                <svg class="w-8 h-8 mr-3 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                </svg>
                Tu número de pedido es:
            </h2>
            
            <div class="bg-white border-4 border-orange-500 rounded-lg p-6 mb-6 shadow-lg">
                <span class="text-5xl font-bold text-orange-600 tracking-wider">{{ $orderNumber ?? 'PED-000' }}</span>
            </div>
            
            <p class="text-gray-600 text-lg">
                Por favor, conserva este número para retirar tu pedido en mostrador
            </p>
        </div>

        <!-- Información del cliente -->
        <div class="bg-blue-50 rounded-xl p-6 mb-8 text-left border border-blue-200">
            <h3 class="text-xl font-bold text-gray-800 mb-4 text-center flex items-center justify-center">
                <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Datos del Pedido
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white rounded-lg p-3">
                    <span class="block text-sm font-medium text-gray-600 mb-1">Cliente:</span>
                    <span class="text-lg text-gray-800">{{ $customerData['nombre'] ?? 'Sin datos' }}</span>
                </div>
                
                <div class="bg-white rounded-lg p-3">
                    <span class="block text-sm font-medium text-gray-600 mb-1">Teléfono:</span>
                    <span class="text-lg text-gray-800">{{ $customerData['telefono'] ?? 'Sin datos' }}</span>
                </div>
                
                <div class="bg-white rounded-lg p-3">
                    <span class="block text-sm font-medium text-gray-600 mb-1">Forma de Pago:</span>
                    <span class="text-lg text-gray-800">
                        @php
                            $formaPagoSeleccionada = 'No seleccionada';
                            if ($selectedPayment && is_array($formasPago)) {
                                foreach($formasPago as $formaPago) {
                                    $codigo = is_object($formaPago) ? $formaPago->CODIGO : $formaPago['CODIGO'] ?? null;
                                    if ($codigo == $selectedPayment) {
                                        $formaPagoSeleccionada = is_object($formaPago) ? $formaPago->NOMBRE : $formaPago['NOMBRE'] ?? 'Desconocida';
                                        break;
                                    }
                                }
                            }
                        @endphp
                        {{ $formaPagoSeleccionada }}
                    </span>
                </div>
                
                <div class="bg-white rounded-lg p-3">
                    <span class="block text-sm font-medium text-gray-600 mb-1">Total:</span>
                    <span class="text-xl font-bold text-green-600">${{ number_format($finalOrderTotal ?? 0, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Resumen de productos -->
        @if(isset($finalOrderItems) && count($finalOrderItems) > 0)
        <div class="bg-gray-50 rounded-xl p-6 mb-8 border border-gray-200">
            <h3 class="text-xl font-bold text-gray-800 mb-4 text-center flex items-center justify-center">
                <svg class="w-6 h-6 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                Productos Solicitados
            </h3>
            
            <div class="space-y-3 max-h-60 overflow-y-auto">
                @foreach($finalOrderItems as $item)
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-800 flex items-center">
                                <span class="w-2 h-2 bg-orange-500 rounded-full mr-2"></span>
                                {{ $item['nombre'] ?? 'Producto' }}
                            </h4>
                            
                            @if(isset($item['opcionales']) && is_array($item['opcionales']) && count($item['opcionales']) > 0)
                                <div class="text-sm text-gray-600 mt-1 ml-4">
                                    @foreach($item['opcionales'] as $opcional)
                                        <div class="flex items-center">
                                            <span class="w-1 h-1 bg-orange-400 rounded-full mr-2"></span>
                                            {{ $opcional['nombre'] ?? 'Opcional' }}
                                            @if($opcional['quantity'] > 0)
                                                <span class="text-gray-500 ml-2">x{{ $opcional['quantity'] }}</span>
                                            @endif
                                            @if(isset($opcional['precio']) && $opcional['precio'] > 0)
                                                <span class="text-green-600 font-semibold ml-auto">(+${{ number_format($opcional['precio'], 2) }})</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            
                            <div class="text-sm text-orange-600 font-medium mt-1 ml-4">
                                Cantidad: {{ $item['quantity'] ?? 1 }}
                            </div>
                        </div>
                        
                        <div class="text-right bg-orange-100 rounded-lg px-3 py-1">
                            <span class="font-bold text-gray-800">${{ number_format($item['total'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Instrucciones -->
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 mb-8">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-amber-600 mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="text-left">
                    <h4 class="text-lg font-bold text-amber-800 mb-2">Instrucciones para el retiro:</h4>
                    <ul class="text-amber-700 space-y-2">
                        <li class="flex items-start">
                            <span class="inline-block w-2 h-2 bg-amber-600 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                            Dirígete al mostrador con tu número de pedido
                        </li>
                        <li class="flex items-start">
                            <span class="inline-block w-2 h-2 bg-amber-600 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                            Ten listo el pago en la forma seleccionada
                        </li>
                        <li class="flex items-start">
                            <span class="inline-block w-2 h-2 bg-amber-600 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                            El tiempo de preparación puede variar según la demanda
                        </li>
                        <li class="flex items-start">
                            <span class="inline-block w-2 h-2 bg-amber-600 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                            Si tienes alguna consulta, menciona tu número de pedido
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Tiempo estimado -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 mb-8 border border-blue-200">
            <div class="flex items-center justify-center">
                <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="text-center">
                    <p class="text-lg font-semibold text-blue-800">Tiempo estimado de preparación</p>
                    <p class="text-3xl font-bold text-blue-600">15 - 25 minutos</p>
                </div>
            </div>
        </div>

        <!-- Botón para nuevo pedido -->
        <button wire:click="startNewOrder" 
                class="w-full bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-4 px-8 rounded-lg transition-all duration-300 transform hover:scale-105 hover:shadow-lg flex items-center justify-center text-lg">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Realizar Nuevo Pedido
        </button>

        <!-- Mensaje de agradecimiento -->
        <div class="mt-8 text-center">
            <p class="text-gray-600 text-lg">
                ¡Gracias por tu preferencia! 
                <span class="text-xl">😊🍽️</span>
            </p>
        </div>
    </div>
</div>