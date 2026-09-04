{{-- CHECKOUT VIEW --}}
<div class="h-full flex flex-col overflow-y-auto" id="main">
    <!-- Header compacto -->
    <div class="px-6 py-3" style="background-color: var(--kiosco-boton);">
        <h1 class="text-xl font-bold flex items-center" style="color: var(--kiosco-texto-boton);">
            <i class="fas fa-cash-register mr-2"></i>
            Finalizar Pedido
        </h1>
    </div>

    <div class="p-4 lg:p-6">
        @if(session('error'))
            <div class="bg-red-50 border border-red-300 rounded-lg p-3 mb-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                    <span class="text-red-700 text-sm font-medium">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
            <!-- Formulario de datos del cliente -->
            <div class="space-y-4">
                @if($kioscoPideNombre || $kioscoPideTelefono)
                <div>
                    <h2 class="text-lg font-bold text-gray-800 mb-3 flex items-center">
                        <span class="w-2 h-2 bg-orange-500 rounded-full mr-2"></span>
                        Datos del Cliente
                    </h2>

                    <div class="space-y-3">
                        @if($kioscoPideNombre)
                        <div>
                            <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                            <input type="text"
                                    id="nombre"
                                    wire:model="customerData.nombre"
                                    autocomplete="off"
                                    class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500 text-base"
                                    placeholder="Ingresá tu nombre">
                            @error('customerData.nombre')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                        @endif

                        @if($kioscoPideTelefono)
                        <div>
                            <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">Teléfono *</label>
                            <input type="tel"
                                    id="telefono"
                                    wire:model="customerData.telefono"
                                    autocomplete="off"
                                    class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500 text-base"
                                    placeholder="Ej: 011-1234-5678">
                            @error('customerData.telefono')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Forma de pago -->
                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center">
                        <span class="w-2 h-2 bg-orange-500 rounded-full mr-2"></span>
                        Forma de Pago
                    </h3>

                    @if($this->hasOnlyEffectiveProducts())
                        <div class="bg-amber-100 border border-amber-300 rounded-lg p-3 mb-3">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-amber-600 mr-2"></i>
                                <span class="text-amber-800 font-medium text-sm">Solo podés pagar en efectivo por los productos seleccionados</span>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-2">
                        @foreach($this->getAvailablePaymentMethods() as $formaPago)
                            <label class="flex items-center p-3 rounded-lg border-2 cursor-pointer transition-all hover:shadow-md {{ $selectedPayment == $formaPago->CODIGO ? 'border-orange-500 bg-orange-50 shadow-md' : 'border-gray-200 bg-white hover:border-orange-300' }}">
                                <input type="radio"
                                        name="payment_method"
                                        value="{{ $formaPago->CODIGO }}"
                                        wire:model="selectedPayment"
                                        class="w-4 h-4 text-orange-600">
                                <span class="ml-2 text-base font-medium text-gray-800">{{ $formaPago->NOMBRE }}</span>

                                @if($formaPago->TIPO == 1)
                                    <i class="fas fa-money-bill-wave ml-auto text-green-600"></i>
                                @elseif($formaPago->TIPO == 4)
                                    <i class="fas fa-credit-card ml-auto text-blue-600"></i>
                                @elseif($formaPago->TIPO == 2)
                                    <i class="fas fa-exchange-alt ml-auto text-purple-600"></i>
                                @elseif(in_array($formaPago->TIPO,[5,7,8]))
                                    <i class="fas fa-qrcode ml-auto text-[#009ee3]"></i>
                                @endif
                            </label>
                        @endforeach
                    </div>

                    @error('selectedPayment')
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Resumen del pedido -->
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-receipt mr-2 text-orange-500"></i>
                    Resumen del Pedido
                </h3>

                <div class="space-y-2 mb-4 max-h-48 overflow-y-auto">
                    @foreach($cart as $item)
                        <div class="flex justify-between items-start py-2 border-b border-gray-200">
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-800 text-sm">{{ $item['nombre'] }}</h4>

                                @if(count($item['opcionales']) > 0)
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        @foreach($item['opcionales'] as $opcional)
                                            <span>+ {{ $opcional['nombre'] }}
                                                @if($opcional['quantity']>0) x{{$opcional['quantity']}} @endif
                                                @if($opcional['precio'] > 0) (${{ number_format($opcional['precio'] * max($opcional['quantity'],1), 2) }}) @endif
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                <span class="text-xs text-gray-500">Cant: {{ $item['quantity'] }}</span>
                            </div>

                            <span class="font-bold text-gray-800 text-sm ml-2">${{ number_format($item['total'], 2) }}</span>
                        </div>
                    @endforeach
                </div>

                <!-- Total -->
                <div class="border-t-2 border-orange-300 pt-3">
                    <div class="flex justify-between items-center">
                        <span class="text-xl font-bold text-gray-800">Total:</span>
                        <span class="text-2xl font-bold" style="color: var(--kiosco-precio);">${{ number_format($this->getCartTotal(), 2) }}</span>
                    </div>
                </div>

                <!-- Info breve -->
                <div class="bg-blue-50 rounded-lg p-3 mt-3">
                    <p class="text-blue-800 text-xs">
                        <i class="fas fa-info-circle mr-1"></i>
                        Recibirás un número de pedido para retirar en mostrador.
                    </p>
                </div>
            </div>
        </div>

        <!-- Botones de acción -->
        <div x-data="{ showConfirmModal: false }">
            <div class="flex gap-3 mt-4 pt-4 border-t border-gray-200">
                <button wire:click="changeView('cart')"
                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-4 rounded-lg transition-all flex items-center justify-center">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver
                </button>

                <button @click="showConfirmModal = true"
                        class="flex-[2] hover:opacity-90 font-bold py-3 px-6 rounded-lg transition-all hover:shadow-lg flex items-center justify-center"
                        style="background-color: var(--kiosco-boton); color: var(--kiosco-texto-boton);">
                    <i class="fas fa-check-circle mr-2"></i>
                    Confirmar Pedido
                </button>
            </div>

            <!-- Modal de Confirmación -->
            <div x-show="showConfirmModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-black/60 z-[999] flex items-center justify-center p-4"
                 @click.self="showConfirmModal = false"
                 style="display: none;">
                <div x-show="showConfirmModal"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-90"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-90"
                     class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden"
                     @click.stop>

                    <!-- Header del modal -->
                    <div class="px-6 py-4 text-center" style="background-color: var(--kiosco-boton);">
                        <i class="fas fa-clipboard-check text-3xl mb-1" style="color: var(--kiosco-texto-boton);"></i>
                        <h3 class="text-xl font-bold" style="color: var(--kiosco-texto-boton);">
                            Confirmar tu pedido
                        </h3>
                    </div>

                    <!-- Resumen compacto -->
                    <div class="px-6 py-4">
                        <div class="space-y-2 max-h-40 overflow-y-auto mb-3">
                            @foreach($cart as $item)
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-700">{{ $item['quantity'] }}x {{ $item['nombre'] }}</span>
                                    <span class="font-semibold text-gray-800">${{ number_format($item['total'], 2) }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="border-t-2 border-gray-200 pt-3 mb-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-gray-800">Total a pagar:</span>
                                <span class="text-2xl font-bold" style="color: var(--kiosco-precio);">
                                    ${{ number_format($this->getCartTotal(), 2) }}
                                </span>
                            </div>
                        </div>

                        <p class="text-center text-gray-500 text-sm mb-4">
                            Una vez confirmado, tu pedido será enviado a la cocina.
                        </p>

                        <!-- Botones del modal -->
                        <div class="flex gap-3">
                            <button @click="showConfirmModal = false"
                                    class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-3 px-4 rounded-xl transition-all flex items-center justify-center">
                                <i class="fas fa-times mr-2"></i>
                                Revisar
                            </button>

                            <button @click="showConfirmModal = false"
                                    wire:click="finalizeOrder"
                                    class="flex-[2] hover:opacity-90 font-bold py-3 px-4 rounded-xl transition-all shadow-lg flex items-center justify-center"
                                    style="background-color: var(--kiosco-boton); color: var(--kiosco-texto-boton);">
                                <i class="fas fa-check mr-2"></i>
                                Confirmar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
