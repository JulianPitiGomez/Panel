<div class="h-full flex flex-col overflow-y-auto" id="main">
    <!-- Header -->
    <div class="bg-gradient-to-r from-green-500 to-green-600 px-4 sm:px-6 py-3">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-white flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    Producto agregado
                </h1>
                <p class="text-green-100 text-sm">Te puede interesar agregar...</p>
            </div>
            <span class="bg-white/20 text-white px-3 py-1 rounded-full text-sm font-medium">
                <i class="fas fa-shopping-cart mr-1"></i> {{ count($cart) }} items
            </span>
        </div>
    </div>

    <!-- Sugerencias con mismo formato que el menú -->
    <div class="flex-1 p-4">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach($sugerencias as $articulo)
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300">
                        <div class="product-card-grid p-4">
                            <!-- Imagen del producto (izquierda) -->
                            <div class="product-image relative">
                                <img src="{{ $this->getProductImageUrl($articulo->CODIGO, true) }}"
                                    alt="{{ $articulo->NOMBRE }}"
                                    class="w-full h-full object-cover rounded-lg"
                                    onerror="this.src='{{ asset('img/nofoto1.jpg') }}'">

                                @if($articulo->preciom_oferta > 0)
                                    <div class="absolute -top-2 -right-2 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                                        OFERTA
                                    </div>
                                @endif
                            </div>

                            <!-- Descripción del producto (derecha arriba) -->
                            <div class="product-description">
                                <h3 class="text-lg font-bold mb-2" style="color: var(--kiosco-letra);">{{ $articulo->NOMBRE }}</h3>

                                @if($articulo->observa_web)
                                    <p class="text-sm mb-2" style="color: var(--kiosco-letra); opacity: 0.7;">{{ Str::limit($articulo->observa_web, 60) }}</p>
                                @endif

                                @if($articulo->cantidad_iconos > 0)
                                    <div class="flex flex-wrap gap-1 mb-2">
                                        @if($articulo->lNuevo)
                                            <img src="{{ $this->getIconUrl('icono-nuevo.png') }}" alt="Nuevo" class="w-5 h-5 feature-icon">
                                        @endif
                                        @if($articulo->lVegetariano)
                                            <img src="{{ $this->getIconUrl('icono-vege.png') }}" alt="Vegetariano" class="w-5 h-5 feature-icon">
                                        @endif
                                        @if($articulo->lTacc)
                                            <img src="{{ $this->getIconUrl('icono-tacc.png') }}" alt="Sin TACC" class="w-5 h-5 feature-icon">
                                        @endif
                                        @if($articulo->lVegano)
                                            <img src="{{ $this->getIconUrl('icono-vegano.png') }}" alt="Vegano" class="w-5 h-5 feature-icon">
                                        @endif
                                        @if($articulo->lLactosa)
                                            <img src="{{ $this->getIconUrl('icono-lactosa.png') }}" alt="Sin Lactosa" class="w-5 h-5 feature-icon">
                                        @endif
                                        @if($articulo->lKosher)
                                            <img src="{{ $this->getIconUrl('icono-kosher.png') }}" alt="Kosher" class="w-5 h-5 feature-icon">
                                        @endif
                                        @if($articulo->lFrutos)
                                            <img src="{{ $this->getIconUrl('icono-frutos.png') }}" alt="Con Frutos" class="w-5 h-5 feature-icon">
                                        @endif
                                    </div>
                                @endif

                                @if($articulo->solo_efectivo)
                                    <span class="inline-block bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-medium">
                                        Solo Efectivo
                                    </span>
                                @endif
                            </div>

                            <!-- Precio (abajo izquierda) -->
                            <div class="product-price flex flex-col justify-end">
                                @if($articulo->preciom_oferta > 0)
                                    <div class="text-lg font-bold" style="color: var(--kiosco-precio);">${{ number_format($articulo->preciom_oferta, 0, ',', '.') }}</div>
                                    @if($articulo->precio_m > 0 && $articulo->precio_m > $articulo->preciom_oferta)
                                        <div class="text-sm text-gray-400 line-through">${{ number_format($articulo->precio_m, 0, ',', '.') }}</div>
                                    @endif
                                @else
                                    <div class="text-lg font-bold" style="color: var(--kiosco-precio);">${{ number_format($articulo->precio_m, 0, ',', '.') }}</div>
                                @endif
                            </div>

                            <!-- Botón agregar (abajo derecha) -->
                            <div class="product-button flex items-end justify-end">
                                <button wire:click="selectProduct({{ $articulo->CODIGO }})"
                                        class="hover:opacity-90 font-bold py-2 px-4 rounded-lg transition-colors flex items-center"
                                        style="background-color: var(--kiosco-boton); color: var(--kiosco-texto-boton);">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Agregar
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Botones inferiores -->
    <div class="flex-shrink-0 p-4 bg-gray-50 border-t border-gray-200">
        <div class="flex gap-3">
            <button wire:click="descartarSugerencias"
                    class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-4 rounded-lg transition flex items-center justify-center text-sm">
                <i class="fas fa-arrow-left mr-2"></i>
                Seguir Comprando
            </button>
            <button wire:click="changeView('cart')"
                    class="flex-1 hover:opacity-90 font-bold py-3 px-4 rounded-lg transition hover:shadow-lg flex items-center justify-center text-sm"
                    style="background-color: var(--kiosco-boton); color: var(--kiosco-texto-boton);">
                <i class="fas fa-shopping-cart mr-2"></i>
                Ver Pedido (${{ number_format($this->getCartTotal(), 2) }})
            </button>
        </div>
    </div>
</div>
