<!-- resources/views/kiosco/menu.blade.php -->
<div class="h-full flex flex-col bg-white/90 backdrop-blur-sm overflow-y-auto" id="main">
    
    <!-- Productos Destacados -->
    @if(count($productosDestacados) > 0)
        <div class="bg-gradient-to-r from-orange-50 to-orange-100 py-6 px-4 flex-shrink-0">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-2xl font-bold text-orange-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    Productos Destacados
                </h2>
                
                <!-- Contenedor con indicador de scroll -->
                <div class="relative">
                    <!-- Indicador de scroll izquierdo -->
                    <div class="absolute left-0 top-1/2 transform -translate-y-1/2 z-10 bg-orange-500/80 text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg cursor-pointer" id="scroll-left" style="display: none;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </div>
                    
                    <!-- Indicador de scroll derecho -->
                    <div class="absolute right-0 top-1/2 transform -translate-y-1/2 z-10 bg-orange-500/80 text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg cursor-pointer" id="scroll-right">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    
                    <div class="overflow-x-auto" id="destacados-container" style="scrollbar-width: none; -ms-overflow-style: none;">
                        <div class="flex gap-4 pb-4" style="width: max-content;">
                            @foreach($productosDestacados as $destacado)
                                <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:scale-105 {{ $destacado->agotado ? 'opacity-60' : '' }}" style="width: 200px; flex-shrink: 0;">
                                    <!-- Imagen del producto -->
                                    <div class="relative h-20 overflow-hidden">
                                        <img src="{{ $this->getProductImageUrl($destacado->CODIGO, true) }}" 
                                             alt="{{ $destacado->NOMBRE }}"
                                             class="w-full h-full object-cover"
                                             onerror="this.src='{{ asset('img/nofoto1.jpg') }}'">
                                        
                                        @if($destacado->agotado)
                                            <div class="absolute inset-0 bg-red-600/80 flex items-center justify-center">
                                                <span class="text-white text-sm font-bold">AGOTADO</span>
                                            </div>
                                        @endif

                                        <!-- Indicador de precio especial -->
                                        @if($destacado->preciom_oferta > 0)
                                            <div class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                                                OFERTA
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Contenido del producto -->
                                    <div class="p-4 text-center">
                                        <div class="mb-3 h-18 flex flex-col justify-center">
                                            <h3 class="font-bold text-orange-500 mb-2 text-sm">{{ $destacado->NOMBRE }}</h3>
                                        </div>
                                        
                                        <!-- Precio -->
                                        <div class="mb-3 h-16 flex flex-col justify-center">
                                            @if($destacado->preciom_oferta > 0)
                                                <div class="text-lg font-bold text-green-600">${{ number_format($destacado->preciom_oferta, 0, ',', '.') }}</div>
                                                <div class="h-5 flex items-center justify-center">
                                                    @if($destacado->precio_m > 0 && $destacado->precio_m > $destacado->preciom_oferta)
                                                        <span class="text-sm text-gray-400 line-through">${{ number_format($destacado->precio_m, 0, ',', '.') }}</span>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="text-lg font-bold text-gray-700">${{ number_format($destacado->precio_m, 0, ',', '.') }}</div>
                                                <div class="h-5"></div>
                                            @endif
                                        </div>

                                        <!-- Botón agregar -->
                                        <button wire:click="selectProduct({{ $destacado->CODIGO }})"
                                                @disabled($destacado->agotado)
                                                class="w-full bg-orange-500 hover:bg-orange-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-bold py-2 px-3 rounded-lg transition-colors text-sm">
                                            @if($destacado->agotado)
                                                No Disponible
                                            @else
                                                Agregar
                                            @endif
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Products by Department - Scrollable content -->
    <div class="flex-1 p-4">
        <div class="max-w-7xl mx-auto">
            @foreach($departamentos as $depto)
                @php
                    $articulosDepto = array_filter($articulos, function($articulo) use ($depto) {
                        return $articulo->DEPTO == $depto->CODIGO;
                    });
                @endphp
                
                @if(count($articulosDepto) > 0)
                    <section id="depto-{{ $depto->CODIGO }}" class="mb-12">
                        <div class="mb-6">
                            <h2 class="text-3xl font-bold text-gray-800 mb-2 flex items-center">
                                {{ $depto->NOMBRE }}
                                @if($depto->cantidad_iconos > 0)
                                    <div class="ml-4 flex space-x-2">
                                        @if($depto->lPromo)
                                            <img src="{{ $this->getIconUrl('icono-promo.png') }}" alt="Promo" class="w-6 h-6">
                                        @endif
                                        @if($depto->lNuevo)
                                            <img src="{{ $this->getIconUrl('icono-nuevo.png') }}" alt="Nuevo" class="w-6 h-6">
                                        @endif
                                        @if($depto->lVegetariano)
                                            <img src="{{ $this->getIconUrl('icono-vege.png') }}" alt="Vegetariano" class="w-6 h-6">
                                        @endif
                                        @if($depto->lTacc)
                                            <img src="{{ $this->getIconUrl('icono-tacc.png') }}" alt="Sin TACC" class="w-6 h-6">
                                        @endif
                                        @if($depto->lVegano)
                                            <img src="{{ $this->getIconUrl('icono-vegano.png') }}" alt="Vegano" class="w-6 h-6">
                                        @endif
                                        @if($depto->lLactosa)
                                            <img src="{{ $this->getIconUrl('icono-lactosa.png') }}" alt="Sin Lactosa" class="w-6 h-6">
                                        @endif
                                        @if($depto->lKosher)
                                            <img src="{{ $this->getIconUrl('icono-kosher.png') }}" alt="Kosher" class="w-6 h-6">
                                        @endif
                                        @if($depto->lFrutos)
                                            <img src="{{ $this->getIconUrl('icono-frutos.png') }}" alt="Con Frutos" class="w-6 h-6">
                                        @endif
                                    </div>
                                @endif
                            </h2>
                            <div class="h-1 bg-gradient-to-r from-orange-500 to-orange-300 rounded w-32"></div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            @foreach($articulosDepto as $articulo)
                                <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 {{ $articulo->agotado ? 'opacity-60' : '' }}">
                                    <!-- Card con layout de 4 secciones -->
                                    <div class="product-card-grid p-4">
                                        <!-- Imagen del producto (izquierda) -->
                                        <div class="product-image relative">
                                            <img src="{{ $this->getProductImageUrl($articulo->CODIGO, true) }}" 
                                                alt="{{ $articulo->NOMBRE }}"
                                                class="w-full h-full object-cover rounded-lg"
                                                onerror="this.src='{{ asset('img/nofoto1.jpg') }}'">
                                            
                                            @if($articulo->agotado)
                                                <div class="absolute inset-0 bg-red-600/80 flex items-center justify-center rounded-lg">
                                                    <span class="text-white text-xs font-bold">AGOTADO</span>
                                                </div>
                                            @endif

                                            <!-- Indicador de precio especial -->
                                            @if($articulo->preciom_oferta > 0 )
                                                <div class="absolute -top-2 -right-2 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                                                    OFERTA
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Descripción del producto (derecha arriba) -->
                                        <div class="product-description">
                                            <h3 class="text-lg font-bold text-gray-800 mb-2">{{ $articulo->NOMBRE }}</h3>
                                            
                                            @if($articulo->observa_web)
                                                <p class="text-gray-600 text-sm mb-2">{{ Str::limit($articulo->observa_web, 60) }}</p>
                                            @endif

                                            <!-- Iconos de características -->
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

                                        <!-- Precio (abajo izquierda, debajo de la imagen) -->
                                        <div class="product-price flex flex-col justify-end">
                                            @if($articulo->preciom_oferta > 0)
                                                <div class="text-lg font-bold text-green-600">${{ number_format($articulo->preciom_oferta, 0, ',', '.') }}</div>
                                                @if($articulo->precio_m > 0 && $articulo->precio_m > $articulo->preciom_oferta)
                                                    <div class="text-sm text-gray-400 line-through">${{ number_format($articulo->precio_m, 0, ',', '.') }}</div>
                                                @endif
                                            @else
                                                <div class="text-lg font-bold text-gray-800">${{ number_format($articulo->precio_m, 0, ',', '.') }}</div>
                                            @endif
                                        </div>

                                        <!-- Botón agregar (abajo derecha) -->
                                        <div class="product-button flex items-end justify-end">
                                            <button wire:click="selectProduct({{ $articulo->CODIGO }})"
                                                    @disabled($articulo->agotado)
                                                    class="bg-orange-500 hover:bg-orange-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-bold py-2 px-4 rounded-lg transition-colors flex items-center">
                                                @if($articulo->agotado)
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    No Disponible
                                                @else
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                    </svg>
                                                    Agregar
                                                @endif
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        </div>
    </div>
</div>
@push('scripts')
<script>
    // Usar Livewire hooks para productos destacados
    document.addEventListener('livewire:navigated', initDestacadosScroll);
    document.addEventListener('livewire:load', initDestacadosScroll);
    
    function initDestacadosScroll() {
        const container = document.getElementById('destacados-container');
        const scrollLeft = document.getElementById('scroll-left');
        const scrollRight = document.getElementById('scroll-right');
        
        if (container && scrollLeft && scrollRight) {
            function updateScrollIndicators() {
                const isAtStart = container.scrollLeft <= 0;
                const isAtEnd = container.scrollLeft >= (container.scrollWidth - container.clientWidth);
                
                scrollLeft.style.display = isAtStart ? 'none' : 'flex';
                scrollRight.style.display = isAtEnd ? 'none' : 'flex';
            }
            
            container.addEventListener('scroll', updateScrollIndicators);
            setTimeout(updateScrollIndicators, 100);
            
            scrollLeft.addEventListener('click', function() {
                container.scrollBy({ left: -200, behavior: 'smooth' });
            });
            
            scrollRight.addEventListener('click', function() {
                container.scrollBy({ left: 200, behavior: 'smooth' });
            });
        }
    }
</script>
@endpush