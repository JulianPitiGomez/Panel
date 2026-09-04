<div class="h-full flex flex-col overflow-y-auto" id="main">
    <div class="min-h-full">
        <div class="max-w-7xl mx-auto px-4 py-4">
            @if($selectedProduct)
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <!-- Layout: imagen izquierda, info derecha -->
                    <div class="flex flex-col lg:flex-row h-full">
                        <!-- Columna izquierda: Imagen -->
                        <div class="lg:w-2/5 relative">
                            <div class="h-48 sm:h-56 lg:h-72 overflow-hidden">
                                <img src="{{ $this->getProductImageUrl($selectedProduct->CODIGO, false) }}"
                                     alt="{{ $selectedProduct->NOMBRE }}"
                                     class="w-full h-full object-cover"
                                     onerror="this.src='{{ asset('img/nofoto1.jpg') }}'">

                                @if($selectedProduct->cantidad_iconos > 0)
                                    <div class="absolute top-2 right-2 flex flex-wrap gap-1">
                                        @if($selectedProduct->lNuevo)
                                            <img src="{{ $this->getIconUrl('icono-nuevo.png') }}" alt="Nuevo" class="w-5 h-5 bg-white rounded-full p-0.5 shadow">
                                        @endif
                                        @if($selectedProduct->lVegetariano)
                                            <img src="{{ $this->getIconUrl('icono-vege.png') }}" alt="Vegetariano" class="w-5 h-5 bg-white rounded-full p-0.5 shadow">
                                        @endif
                                        @if($selectedProduct->lTacc)
                                            <img src="{{ $this->getIconUrl('icono-tacc.png') }}" alt="Sin TACC" class="w-5 h-5 bg-white rounded-full p-0.5 shadow">
                                        @endif
                                        @if($selectedProduct->lVegano)
                                            <img src="{{ $this->getIconUrl('icono-vegano.png') }}" alt="Vegano" class="w-5 h-5 bg-white rounded-full p-0.5 shadow">
                                        @endif
                                        @if($selectedProduct->lLactosa)
                                            <img src="{{ $this->getIconUrl('icono-lactosa.png') }}" alt="Sin Lactosa" class="w-5 h-5 bg-white rounded-full p-0.5 shadow">
                                        @endif
                                        @if($selectedProduct->lKosher)
                                            <img src="{{ $this->getIconUrl('icono-kosher.png') }}" alt="Kosher" class="w-5 h-5 bg-white rounded-full p-0.5 shadow">
                                        @endif
                                        @if($selectedProduct->lFrutos)
                                            <img src="{{ $this->getIconUrl('icono-frutos.png') }}" alt="Con Frutos" class="w-5 h-5 bg-white rounded-full p-0.5 shadow">
                                        @endif
                                    </div>
                                @endif

                                @if($selectedProduct->preciom_oferta > 0)
                                    <div class="absolute bottom-2 left-2 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-bold shadow">
                                        OFERTA
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Columna derecha: Info y opcionales -->
                        <div class="lg:w-3/5 flex flex-col">
                            <div class="flex-1 p-4 overflow-y-auto">
                                <!-- Info básica -->
                                <div class="mb-3">
                                    <h1 class="text-xl lg:text-2xl font-bold mb-1" style="color: var(--kiosco-letra);">{{ $selectedProduct->NOMBRE }}</h1>

                                    @if($selectedProduct->observa_web)
                                        <p class="text-sm mb-2" style="color: var(--kiosco-letra); opacity: 0.7;">{{ $selectedProduct->observa_web }}</p>
                                    @endif

                                    @if($selectedProduct->alergenos)
                                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-2 mb-2 text-xs">
                                            <span class="font-semibold text-amber-800">Alérgenos:</span>
                                            <span class="text-amber-700">{{ $selectedProduct->alergenos }}</span>
                                        </div>
                                    @endif

                                    <!-- Precio -->
                                    <div class="flex items-center gap-3 mb-3">
                                        @if($selectedProduct->preciom_oferta > 0)
                                            <span class="text-2xl font-bold" style="color: var(--kiosco-precio);">${{ number_format($selectedProduct->preciom_oferta, 0, ',', '.') }}</span>
                                            <span class="text-base text-gray-400 line-through">${{ number_format($selectedProduct->precio_m, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-2xl font-bold" style="color: var(--kiosco-precio);">${{ number_format($selectedProduct->precio_m, 0, ',', '.') }}</span>
                                        @endif

                                        @if($selectedProduct->solo_efectivo)
                                            <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full text-xs font-medium">Solo Efectivo</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Opcionales -->
                                @php
                                    $opcionales = $this->getProductOptionals($selectedProduct->CODIGO);
                                @endphp

                                @if($opcionales->count() > 0)
                                    <div class="space-y-3">
                                        <h2 class="text-base font-bold text-gray-800 flex items-center">
                                            <i class="fas fa-sliders-h mr-2 text-orange-500"></i>
                                            Personalizá tu pedido
                                        </h2>

                                        @foreach($opcionales as $grupo)
                                            @php
                                                $opcionesGrupo = $this->getOptionalsByGroup($grupo->id);
                                            @endphp

                                            <div class="bg-orange-50 rounded-lg p-3 border border-orange-200">
                                                <div class="mb-2">
                                                    <h3 class="text-sm font-semibold text-gray-800 flex items-center">
                                                        <span class="w-1.5 h-1.5 bg-orange-500 rounded-full mr-2"></span>
                                                        {{ $grupo->nombre }}
                                                    </h3>

                                                    <div class="text-xs text-gray-500 mt-1 ml-3.5 flex flex-wrap gap-1">
                                                        @if($grupo->obligatorio)
                                                            <span class="text-red-600 font-medium bg-red-50 px-1.5 py-0.5 rounded">Obligatorio</span>
                                                            @if($grupo->minimo > 0)
                                                                <span class="text-orange-600">Mín: {{ $grupo->minimo }}</span>
                                                            @endif
                                                        @else
                                                            <span class="text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">Opcional</span>
                                                        @endif

                                                        @if($grupo->maximo > 0)
                                                            <span class="text-orange-600">Máx: {{ $grupo->maximo }}</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="space-y-1.5">
                                                    @foreach($opcionesGrupo as $opcion)
                                                        @php
                                                            $optionalKey = "{$grupo->id}_{$opcion->id}";
                                                            $isSelected = isset($selectedOptionals[$optionalKey]);
                                                            $quantity = $isSelected ? $selectedOptionals[$optionalKey]['quantity'] : 0;
                                                        @endphp

                                                        <label class="flex items-center justify-between p-2 bg-white rounded-lg border cursor-pointer transition-all {{ $isSelected ? 'border-orange-500 bg-orange-50' : 'border-gray-200 hover:border-orange-300' }} {{ $opcion->agotado ? 'opacity-50 cursor-not-allowed' : '' }}">
                                                            <div class="flex items-center flex-1 min-w-0">
                                                                @if($grupo->por_cantidad)
                                                                    <div class="flex items-center justify-between w-full">
                                                                        <span class="font-medium text-gray-800 text-sm truncate">
                                                                            {{ $opcion->nombre }}
                                                                            @if($opcion->agotado) <span class="text-red-500 text-xs">(Agotado)</span> @endif
                                                                        </span>

                                                                        <div class="flex items-center gap-2 ml-2 flex-shrink-0">
                                                                            @if($opcion->precio > 0)
                                                                                <span class="text-green-600 font-semibold text-xs">
                                                                                    @if($isSelected && $quantity > 1)
                                                                                        +${{ number_format($opcion->precio * $quantity, 0, ',', '.') }}
                                                                                    @else
                                                                                        +${{ number_format($opcion->precio, 0, ',', '.') }}
                                                                                    @endif
                                                                                </span>
                                                                            @endif

                                                                            <div class="flex items-center border rounded bg-white text-sm">
                                                                                <button type="button"
                                                                                        wire:click="updateOptionalQuantity('{{ $optionalKey }}', {{ max(0, $quantity - 1) }})"
                                                                                        class="px-2 py-1 text-orange-600 hover:bg-orange-50 {{ $quantity <= 0 ? 'opacity-50' : '' }}"
                                                                                        {{ $quantity <= 0 || $opcion->agotado ? 'disabled' : '' }}>
                                                                                    -
                                                                                </button>
                                                                                <span class="px-2 py-1 min-w-[2rem] text-center font-medium border-l border-r text-xs">{{ $quantity }}</span>
                                                                                <button type="button"
                                                                                        wire:click="updateOptionalQuantity('{{ $optionalKey }}', {{ min($grupo->maximo, $quantity + 1) }})"
                                                                                        class="px-2 py-1 text-orange-600 hover:bg-orange-50 {{ $quantity >= $grupo->maximo ? 'opacity-50' : '' }}"
                                                                                        {{ $quantity >= $grupo->maximo || $opcion->agotado ? 'disabled' : '' }}>
                                                                                    +
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    <input type="{{ $grupo->maximo == 1 ? 'radio' : 'checkbox' }}"
                                                                           name="optional_group_{{ $grupo->id }}"
                                                                           wire:click="toggleOptional({{ $opcion->id }}, {{ $grupo->id }}, {{ $grupo->maximo }}, {{ $grupo->por_cantidad }})"
                                                                           {{ $isSelected ? 'checked' : '' }}
                                                                           {{ $opcion->agotado ? 'disabled' : '' }}
                                                                           class="w-3.5 h-3.5 text-orange-500 focus:ring-orange-500 mr-2 flex-shrink-0">

                                                                    <span class="font-medium text-gray-800 text-sm truncate">
                                                                        {{ $opcion->nombre }}
                                                                        @if($opcion->agotado) <span class="text-red-500 text-xs">(Agotado)</span> @endif
                                                                    </span>
                                                                @endif
                                                            </div>

                                                            @if(!$grupo->por_cantidad && $opcion->precio > 0)
                                                                <span class="text-green-600 font-semibold text-xs ml-2 flex-shrink-0">+${{ number_format($opcion->precio, 0, ',', '.') }}</span>
                                                            @endif
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <!-- Botones - Siempre visibles abajo -->
                            <div class="border-t bg-white p-3">
                                <div class="flex gap-3">
                                    <button wire:click="changeView('menu')"
                                            class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2.5 px-4 rounded-lg transition-all flex items-center justify-center text-sm">
                                        <i class="fas fa-times mr-2"></i>
                                        Cancelar
                                    </button>

                                    <button wire:click="addToCart({{ $selectedProduct->CODIGO }})"
                                            class="flex-[2] hover:opacity-90 font-bold py-2.5 px-6 rounded-lg transition-all hover:shadow-lg flex items-center justify-center text-sm"
                                            style="background-color: var(--kiosco-boton); color: var(--kiosco-texto-boton);">
                                        <i class="fas fa-plus mr-2"></i>
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
