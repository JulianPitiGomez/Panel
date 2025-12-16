<div class="h-screen bg-gray-50 flex flex-col">
    <style>
        /* Scroll horizontal para navegación de departamentos */
        .scrollbar-hide {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;  /* Chrome, Safari and Opera */
        }

        .floating-nav {
            position: fixed;
            /*top: 0;*/
            left: 0;
            right: 0;
            z-index: 100;
            transform: translateY(0);
            transition: transform 0.3s ease-in-out;
        }

        .floating-nav.hide {
            transform: translateY(-100%);
        }

        /* Grid para productos */
        .product-card-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            grid-template-rows: 1fr 1fr;
            height: 200px;
            gap: 1rem;
        }

        .product-image {
            grid-row: 1 ;
            grid-column: 1 / 2;
        }

        .product-description {
            grid-row: 1;
            grid-column: 2;
        }

        .product-price {
            grid-row: 2;
            grid-column: 1 / 2;
            align-self: end;
        }

        .product-button {
            grid-row: 2;
            grid-column: 2;
            align-self: end;
        }

        /* Productos destacados */
        .destacados-scroll::-webkit-scrollbar {
            display: none;
        }

        .destacados-scroll {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Optimizaciones para pantalla táctil vertical */
        @media (orientation: portrait) {
            .kiosco-container {
                max-width: 100vw;
                padding: 0.5rem;
            }
            
            .kiosco-header {
                padding: 1rem 0.5rem;
            }
            
            .product-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 1rem;
            }
            
            .product-card {
                min-height: 400px;
            }
            
            .department-nav {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .department-nav button {
                font-size: 1.1rem;
                padding: 0.75rem 1.5rem;
                min-width: 150px;
            }
        }

        /* Optimizaciones para pantalla horizontal */
        @media (orientation: landscape) {
            .product-grid {
                grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
                gap: 1.5rem;
            }
            
            .kiosco-header {
                padding: 1.5rem;
            }
            
            .department-nav button {
                font-size: 1.2rem;
                padding: 1rem 2rem;
            }
        }

        /* Botones más grandes para touch */
        .touch-button {
            min-height: 60px;
            font-size: 1.1rem;
            touch-action: manipulation;
        }

        .touch-button:hover {
            transform: scale(1.02);
        }

        .touch-button:active {
            transform: scale(0.98);
        }

        /* Animaciones suaves */
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-up {
            animation: slideUp 0.4s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive font sizes */
        @media (max-width: 768px) {
            .text-responsive-lg {
                font-size: 1.5rem;
            }
            
            .text-responsive-xl {
                font-size: 1.75rem;
            }
            
            .text-responsive-2xl {
                font-size: 2rem;
            }
        }

        @media (min-width: 769px) {
            .text-responsive-lg {
                font-size: 1.75rem;
            }
            
            .text-responsive-xl {
                font-size: 2rem;
            }
            
            .text-responsive-2xl {
                font-size: 2.5rem;
            }
        }

        /* Mejoras para el carrito flotante */
        .cart-badge {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Estados de productos */
        .product-available {
            transition: all 0.3s ease;
        }

        .product-available:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .product-unavailable {
            filter: grayscale(50%);
            opacity: 0.7;
        }

        /* Mejoras para formularios */
        .form-input-large {
            font-size: 1.2rem;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            border: 2px solid #e5e7eb;
            transition: all 0.2s ease;
        }

        .form-input-large:focus {
            border-color: #FF9800;
            box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.1);
            outline: none;
        }

        /* Estilos para iconos de características */
        .feature-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: white;
            padding: 2px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        /* Estilos para botones de scroll de departamentos */
        #dept-scroll-left,
        #dept-scroll-right {
            position: absolute;
            z-index: 200;
            pointer-events: auto;
            cursor: pointer;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }

        #departamentos-container {
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            position: relative;
        }

        /* Asegurar que los botones no interfieran con el scroll */
        #departamentos-container::-webkit-scrollbar {
            display: none;
        }

        /* Mejorar la interacción táctil */
        #dept-scroll-left:active,
        #dept-scroll-right:active {
            transform: scale(0.95);
        }

        #dept-scroll-left:hover,
        #dept-scroll-right:hover {
            background-color: rgba(249, 115, 22, 0.9);
        }
    </style>

    <!-- Header con información del comercio y Carrito -->
    <header class="bg-white/95 backdrop-blur-sm shadow-lg z-50 flex-shrink-0" style="background-image: url('{{ asset('img/' . $clientId . '/fondo.jpg') }}'); background-size: cover; background-position: center;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <!-- Información del comercio -->
                <div class="flex items-center space-x-4">
                    <img src="{{ asset('img/' . $clientId . '/logo.jpg') }}" alt="Logo" class="h-16 w-auto">
                    <div class="text-left">
                        @if($comercioData)
                            <h1 class="text-2xl font-bold text-white">{{ $comercioData->nombre ?? 'Restaurante' }}</h1>
                            @if($comercioData->direccion)
                                <p class="text-sm text-white/90"> <i class="fa fa-map-marker" aria-hidden="true"></i> {{ $comercioData->direccion }}</p>
                            @endif
                            @if($comercioData->slogan)
                                <p class="text-sm font-medium text-orange-200">{{ $comercioData->slogan }}</p>
                            @endif
                            @if($comercioData->texto_extra)
                                <p class="text-xs text-white/80">{{ $comercioData->texto_extra }}</p>
                            @endif
                        @else
                            <h1 class="text-2xl font-bold text-white">Restaurante</h1>
                        @endif
                        <h1 class="text-2xl font-bold text-white items-center justify-center">KIOSCO AUTOGESTIÓN</h1>
                    </div>
                </div>
                
                <!-- Botones de navegación y Carrito -->
                <div class="flex items-center space-x-4">
                    @if($currentView !== 'menu')
                        <button wire:click="changeView('menu')" 
                                class="bg-orange-500 hover:bg-orange-600 text-white px-3 sm:px-6 py-3 rounded-lg font-medium transition-colors flex items-center">
                            <svg class="w-5 h-5 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            <span class="hidden sm:inline">Volver al Menú</span>
                        </button>
                    @endif
                    
                    <button wire:click="changeView('cart')" 
                            class="bg-orange-500 hover:bg-orange-600 text-white px-3 sm:px-6 py-3 rounded-lg font-medium transition-colors flex items-center relative">
                        <svg class="w-6 h-6 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 2.5M7 13l2.5 2.5m6-2.5a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
                        </svg>
                        <span class="hidden sm:inline">Ver Pedido</span>
                        @if(count($cart) > 0)
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full text-xs w-6 h-6 flex items-center justify-center">
                                {{ count($cart) }}
                            </span>
                        @endif
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Navegación de departamentos fija en el header -->
        @if($currentView === 'menu' && count($departamentos) > 0)
            <div class="bg-black/30 backdrop-blur-sm border-t border-white/20">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="relative">
                        <!-- Indicador de scroll izquierdo -->
                        <div class="absolute left-0 top-1/2 transform -translate-y-1/2 z-50 bg-orange-500 text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg cursor-pointer" id="dept-scroll-left" style="display: none;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </div>
                        
                        <!-- Indicador de scroll derecho -->
                        <div class="absolute right-0 top-1/2 transform -translate-y-1/2 z-50 bg-orange-500 text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg cursor-pointer" id="dept-scroll-right">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                        
                        <div class="overflow-x-auto scrollbar-hide py-3" id="departamentos-container">
                            <div class="flex gap-3" style="width: max-content;">
                                @foreach($departamentos as $depto)
                                    <button wire:click="scrollToSection({{ $depto->CODIGO }})"
                                            class="bg-orange-500/80 hover:bg-orange-600 text-white px-4 py-2 rounded-full font-medium transition-all transform hover:scale-105 flex items-center whitespace-nowrap flex-shrink-0 backdrop-blur-sm">
                                        {{ $depto->NOMBRE }}
                                        @if($depto->cantidad_iconos > 0)
                                            <div class="ml-2 flex space-x-1">
                                                @if($depto->lPromo)
                                                    <img src="{{ $this->getIconUrl('icono-promo.png') }}" alt="Promo" class="w-4 h-4">
                                                @endif
                                                @if($depto->lNuevo)
                                                    <img src="{{ $this->getIconUrl('icono-nuevo.png') }}" alt="Nuevo" class="w-4 h-4">
                                                @endif
                                                @if($depto->lVegetariano)
                                                    <img src="{{ $this->getIconUrl('icono-vege.png') }}" alt="Vegetariano" class="w-4 h-4">
                                                @endif
                                                @if($depto->lTacc)
                                                    <img src="{{ $this->getIconUrl('icono-tacc.png') }}" alt="Sin TACC" class="w-4 h-4">
                                                @endif
                                                @if($depto->lVegano)
                                                    <img src="{{ $this->getIconUrl('icono-vegano.png') }}" alt="Vegano" class="w-4 h-4">
                                                @endif
                                                @if($depto->lLactosa)
                                                    <img src="{{ $this->getIconUrl('icono-lactosa.png') }}" alt="Sin Lactosa" class="w-4 h-4">
                                                @endif
                                                @if($depto->lKosher)
                                                    <img src="{{ $this->getIconUrl('icono-kosher.png') }}" alt="Kosher" class="w-4 h-4">
                                                @endif
                                                @if($depto->lFrutos)
                                                    <img src="{{ $this->getIconUrl('icono-frutos.png') }}" alt="Con Frutos" class="w-4 h-4">
                                                @endif
                                            </div>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </header>

    

    <!-- Contenido Principal -->
    <main class="h-full flex flex-col bg-white/90 backdrop-blur-sm overflow-y-auto">
            @if($currentView === 'menu')
                @include('kiosco.menu')            
            @elseif($currentView === 'product')
                @include('kiosco.detalle')
            @elseif($currentView === 'cart')
                @include('kiosco.carrito')
            @elseif($currentView === 'checkout')
                @include('kiosco.checkout')
            @elseif($currentView === 'success')
                @include('kiosco.success')
            @endif
    </main>
</div>

@script
<script>
    // Usar Livewire hooks en lugar de DOMContentLoaded
    document.addEventListener('livewire:navigated', initDepartmentScroll);
    document.addEventListener('livewire:load', initDepartmentScroll);
    document.addEventListener('livewire:navigated', initDestacadosScroll);
    document.addEventListener('livewire:load', initDestacadosScroll);

    Livewire.on('view-changed', (data) => {
        // Pequeño delay para que el DOM se actualice
        setTimeout(() => {
            initScrolls();
        }, 100);
    });

    Livewire.on('scroll-to-section', (data) => {
        const sectionId = data.sectionId; // Livewire 3 usa objeto
        const anchor = document.getElementById(sectionId);
        
        if (anchor) {
            anchor.scrollIntoView({
                behavior: 'smooth',
                block: 'start', // 'start', 'center', 'end', 'nearest'
                inline: 'nearest'
            });
        }
    });
    
    function initScrolls() {
        initDepartmentScroll();
        initDestacadosScroll();
    }
    
    function initDepartmentScroll() {
        const container = document.getElementById('departamentos-container');
        const scrollLeft = document.getElementById('dept-scroll-left');
        const scrollRight = document.getElementById('dept-scroll-right');
        
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

    // Inicializar el scroll horizontal para productos destacados
    
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
@endscript