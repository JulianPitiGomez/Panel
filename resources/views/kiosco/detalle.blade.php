<div class="h-full flex flex-col bg-white/90 backdrop-blur-sm overflow-y-auto" id="main">
    <div class="min-h-full">
        <div class="max-w-7xl mx-auto px-4 py-8">
            @if($selectedProduct)
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden min-h-[calc(100vh-4rem)]">
                    <!-- Layout principal: imagen izquierda, info derecha -->
                    <div class="flex flex-col lg:flex-row h-full">
                        <!-- Columna izquierda: Imagen -->
                        <div class="lg:w-1/2 relative">
                            <div class="h-64 sm:h-80 lg:h-96 xl:h-[500px] overflow-hidden">
                                <img src="{{ $this->getProductImageUrl($selectedProduct->CODIGO, false) }}" 
                                     alt="{{ $selectedProduct->NOMBRE }}"
                                     class="w-full h-full object-cover"
                                     onerror="this.src='{{ asset('img/nofoto1.jpg') }}'">
                                
                                <!-- Iconos de características -->
                                @if($selectedProduct->cantidad_iconos > 0)
                                    <div class="absolute top-4 right-4 flex flex-wrap gap-2">
                                        @if($selectedProduct->lNuevo)
                                            <img src="{{ $this->getIconUrl('icono-nuevo.png') }}" alt="Nuevo" class="w-6 h-6 sm:w-8 sm:h-8 bg-white rounded-full p-1 shadow-md">
                                        @endif
                                        @if($selectedProduct->lVegetariano)
                                            <img src="{{ $this->getIconUrl('icono-vege.png') }}" alt="Vegetariano" class="w-6 h-6 sm:w-8 sm:h-8 bg-white rounded-full p-1 shadow-md">
                                        @endif
                                        @if($selectedProduct->lTacc)
                                            <img src="{{ $this->getIconUrl('icono-tacc.png') }}" alt="Sin TACC" class="w-6 h-6 sm:w-8 sm:h-8 bg-white rounded-full p-1 shadow-md">
                                        @endif
                                        @if($selectedProduct->lVegano)
                                            <img src="{{ $this->getIconUrl('icono-vegano.png') }}" alt="Vegano" class="w-6 h-6 sm:w-8 sm:h-8 bg-white rounded-full p-1 shadow-md">
                                        @endif
                                        @if($selectedProduct->lLactosa)
                                            <img src="{{ $this->getIconUrl('icono-lactosa.png') }}" alt="Sin Lactosa" class="w-6 h-6 sm:w-8 sm:h-8 bg-white rounded-full p-1 shadow-md">
                                        @endif
                                        @if($selectedProduct->lKosher)
                                            <img src="{{ $this->getIconUrl('icono-kosher.png') }}" alt="Kosher" class="w-6 h-6 sm:w-8 sm:h-8 bg-white rounded-full p-1 shadow-md">
                                        @endif
                                        @if($selectedProduct->lFrutos)
                                            <img src="{{ $this->getIconUrl('icono-frutos.png') }}" alt="Con Frutos" class="w-6 h-6 sm:w-8 sm:h-8 bg-white rounded-full p-1 shadow-md">
                                        @endif
                                    </div>
                                @endif

                                <!-- Indicador de precio especial -->
                                @if($selectedProduct->preciom_oferta > 0)
                                    <div class="absolute bottom-4 left-4 bg-gradient-to-r from-red-500 to-red-600 text-white px-3 py-2 sm:px-4 rounded-full text-sm sm:text-lg font-bold shadow-lg">
                                        OFERTA
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Columna derecha: Información y opcionales -->
                        <div class="lg:w-1/2 flex flex-col">
                            <!-- Contenido scrolleable -->
                            <div class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
                                <!-- Información básica del producto -->
                                <div class="mb-6">
                                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-800 mb-4">{{ $selectedProduct->NOMBRE }}</h1>
                                    <div class="h-1 bg-gradient-to-r from-orange-500 to-orange-300 rounded w-24 mb-4"></div>
                                    
                                    @if($selectedProduct->observa_web)
                                        <p class="text-gray-600 text-base lg:text-lg mb-4">{{ $selectedProduct->observa_web }}</p>
                                    @endif

                                    @if($selectedProduct->alergenos)
                                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">
                                            <h3 class="font-semibold text-amber-800 mb-2">⚠️ Alérgenos:</h3>
                                            <p class="text-amber-700">{{ $selectedProduct->alergenos }}</p>
                                        </div>
                                    @endif

                                    <!-- Precio -->
                                    <div class="flex flex-wrap items-center gap-4 mb-6">
                                        @if($selectedProduct->preciom_oferta > 0)
                                            <span class="text-2xl sm:text-3xl font-bold text-green-600">${{ number_format($selectedProduct->preciom_oferta, 0, ',', '.') }}</span>
                                            <span class="text-lg sm:text-xl text-gray-400 line-through">${{ number_format($selectedProduct->precio_m, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-2xl sm:text-3xl font-bold text-gray-800">${{ number_format($selectedProduct->precio_m, 0, ',', '.') }}</span>
                                        @endif

                                        @if($selectedProduct->solo_efectivo)
                                            <span class="bg-amber-100 text-amber-800 px-3 py-1 rounded-full text-sm font-medium">
                                                Solo Efectivo
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Opcionales -->
                                @php
                                    $opcionales = $this->getProductOptionals($selectedProduct->CODIGO);
                                @endphp

                                @if($opcionales->count() > 0)
                                    <div class="space-y-4 sm:space-y-6 pb-6">
                                        <h2 class="text-xl sm:text-2xl font-bold text-gray-800 flex items-center">
                                            <svg class="w-6 h-6 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                                            </svg>
                                            Personaliza tu pedido
                                        </h2>
                                        
                                        @foreach($opcionales as $grupo)
                                            @php
                                                $opcionesGrupo = $this->getOptionalsByGroup($grupo->id);
                                            @endphp
                                            
                                            <div class="bg-gradient-to-br from-orange-50 to-orange-100/50 rounded-xl p-4 sm:p-6 border border-orange-200">
                                                <div class="mb-4">
                                                    <h3 class="text-lg sm:text-xl font-semibold text-gray-800 flex items-center">
                                                        <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                                                        {{ $grupo->nombre }}
                                                    </h3>
                                                    
                                                    <div class="text-sm text-gray-600 mt-2 ml-5">
                                                        @if($grupo->obligatorio)
                                                            <span class="text-red-600 font-medium bg-red-50 px-2 py-1 rounded">Obligatorio</span>
                                                            @if($grupo->minimo > 0)
                                                                <span class="text-orange-600 ml-2">
                                                                    {{ $grupo->por_cantidad ? 'Cantidad mínima: ' . $grupo->minimo : 'Mínimo: ' . $grupo->minimo }}
                                                                </span>
                                                            @endif
                                                        @else
                                                            <span class="text-gray-500 bg-gray-100 px-2 py-1 rounded">Opcional</span>
                                                        @endif
                                                        
                                                        @if($grupo->maximo > 0)
                                                            <span class="text-orange-600 ml-2">
                                                                {{ $grupo->por_cantidad ? 'Cantidad máxima: ' . $grupo->maximo : 'Máximo: ' . $grupo->maximo }}
                                                            </span>
                                                        @endif
                                                        
                                                        @if($grupo->por_cantidad)
                                                            <span class="text-blue-600 ml-2 bg-blue-50 px-2 py-1 rounded text-xs">Por cantidad</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="space-y-2">
                                                    @foreach($opcionesGrupo as $opcion)
                                                        @php
                                                            $optionalKey = "{$grupo->id}_{$opcion->id}";
                                                            $isSelected = isset($selectedOptionals[$optionalKey]);
                                                            $quantity = $isSelected ? $selectedOptionals[$optionalKey]['quantity'] : 0;
                                                        @endphp
                                                        
                                                        <label class="flex items-center justify-between p-3 bg-white rounded-lg border-2 cursor-pointer transition-all duration-300 hover:shadow-md {{ $isSelected ? 'border-orange-500 bg-orange-50' : 'border-gray-200 hover:border-orange-300' }} {{ $opcion->agotado ? 'opacity-50 cursor-not-allowed' : '' }}">
                                                            <div class="flex items-center space-x-3 flex-1">
                                                                @if($grupo->por_cantidad)
                                                                    <!-- Modo cantidad: selector numérico directo -->
                                                                    <div class="flex-1">
                                                                        <div class="flex items-center justify-between">
                                                                            <div>
                                                                                <span class="font-medium text-gray-800 text-sm sm:text-base">{{ $opcion->nombre }}</span>
                                                                                @if($opcion->agotado)
                                                                                    <span class="text-red-500 text-xs ml-2 bg-red-50 px-1 rounded">(Agotado)</span>
                                                                                @endif
                                                                            </div>
                                                                            
                                                                            <div class="flex items-center space-x-3">
                                                                                <div class="flex items-center border rounded-lg bg-white">
                                                                                    <button type="button" 
                                                                                            wire:click="updateOptionalQuantity('{{ $optionalKey }}', {{ max(0, $quantity - 1) }})"
                                                                                            class="px-3 py-2 text-orange-600 hover:bg-orange-50 transition-colors {{ $quantity <= 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                                                            {{ $quantity <= 0 || $opcion->agotado ? 'disabled' : '' }}>
                                                                                        -
                                                                                    </button>
                                                                                    <span class="px-4 py-2 min-w-[3rem] text-center font-medium border-l border-r">{{ $quantity }}</span>
                                                                                    <button type="button" 
                                                                                            wire:click="updateOptionalQuantity('{{ $optionalKey }}', {{ min($grupo->maximo, $quantity + 1) }})"
                                                                                            class="px-3 py-2 text-orange-600 hover:bg-orange-50 transition-colors {{ $quantity >= $grupo->maximo ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                                                            {{ $quantity >= $grupo->maximo || $opcion->agotado ? 'disabled' : '' }}>
                                                                                        +
                                                                                    </button>
                                                                                </div>
                                                                                <span class="text-xs text-gray-500">(0-{{ $grupo->maximo }})</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    <!-- Modo tradicional: radio/checkbox simple -->
                                                                    <input type="{{ $grupo->maximo == 1 ? 'radio' : 'checkbox' }}" 
                                                                           name="optional_group_{{ $grupo->id }}"
                                                                           wire:click="toggleOptional({{ $opcion->id }}, {{ $grupo->id }}, {{ $grupo->maximo }}, {{ $grupo->por_cantidad }})"
                                                                           {{ $isSelected ? 'checked' : '' }}
                                                                           {{ $opcion->agotado ? 'disabled' : '' }}
                                                                           class="w-4 h-4 text-orange-500 focus:ring-orange-500">
                                                                    
                                                                    <div>
                                                                        <span class="font-medium text-gray-800 text-sm sm:text-base">{{ $opcion->nombre }}</span>
                                                                        @if($opcion->agotado)
                                                                            <span class="text-red-500 text-xs ml-2 bg-red-50 px-1 rounded">(Agotado)</span>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            
                                                            <div class="text-right">
                                                                @if($opcion->precio > 0)
                                                                    @if($grupo->por_cantidad && $isSelected && $quantity > 1)
                                                                        <div class="text-green-600 font-semibold text-sm sm:text-base bg-green-50 px-2 py-1 rounded">
                                                                            +${{ number_format($opcion->precio * $quantity, 0, ',', '.') }}
                                                                            <div class="text-xs text-gray-500">${{ number_format($opcion->precio, 0, ',', '.') }} c/u</div>
                                                                        </div>
                                                                    @else
                                                                        <span class="text-green-600 font-semibold text-sm sm:text-base bg-green-50 px-2 py-1 rounded">+${{ number_format($opcion->precio, 0, ',', '.') }}</span>
                                                                    @endif
                                                                @endif
                                                            </div>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <!-- Botones de acción - Siempre visibles -->
                            <div class="border-t bg-white p-4 sm:p-6">
                                <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                                    <button wire:click="changeView('menu')" 
                                            class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg transition-all duration-300 hover:shadow-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Cancelar
                                    </button>
                                    
                                    <button wire:click="addToCart({{ $selectedProduct->CODIGO }})" 
                                            class="flex-1 sm:flex-2 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-3 px-8 rounded-lg transition-all duration-300 hover:shadow-lg transform hover:scale-105 flex items-center justify-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Agregar al Carrito
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>