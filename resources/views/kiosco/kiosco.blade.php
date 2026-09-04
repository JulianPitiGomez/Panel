<div class="h-screen bg-gray-50 flex flex-col">
    <style>
        :root {
            --kiosco-fondo: {{ $kioscoColores['fondo'] ?? '#FFFFFF' }};
            --kiosco-letra: {{ $kioscoColores['letra'] ?? '#1F2937' }};
            --kiosco-boton: {{ $kioscoColores['boton'] ?? '#F97316' }};
            --kiosco-texto-boton: {{ $kioscoColores['texto_boton'] ?? '#FFFFFF' }};
            --kiosco-precio: {{ $kioscoColores['precio'] ?? '#16A34A' }};
        }

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

        /* Grid interno de cada card de producto - base angosto */
        .product-card-grid {
            display: grid;
            grid-template-columns: 65px 1fr;
            grid-template-rows: 1fr auto;
            height: 180px;
            gap: 0.3rem 0.5rem;
            overflow: hidden;
        }

        .product-image {
            grid-row: 1;
            grid-column: 1;
            min-height: 0;
            overflow: hidden;
            aspect-ratio: 1;
            align-self: center;
            border-radius: 0.5rem;
        }

        .product-description {
            grid-row: 1;
            grid-column: 2;
            min-height: 0;
            overflow: hidden;
        }

        .product-price {
            grid-row: 2;
            grid-column: 1;
            align-self: end;
        }

        .product-button {
            grid-row: 2;
            grid-column: 2;
            align-self: end;
        }

        .product-name { font-size: 0.8rem; }
        .product-obs { font-size: 0.65rem; }
        .product-btn { font-size: 0.7rem; padding: 0.3rem 0.6rem; }
        .product-price-text { font-size: 0.85rem; }

        /* Pantallas medianas */
        @media (min-width: 768px) {
            .product-card-grid {
                grid-template-columns: 120px 1fr;
                height: 220px;
                gap: 0.5rem 0.8rem;
            }
            .product-name { font-size: 0.95rem; }
            .product-obs { font-size: 0.75rem; }
            .product-btn { font-size: 0.85rem; padding: 0.4rem 0.8rem; }
            .product-price-text { font-size: 1rem; }
        }

        /* Pantallas grandes */
        @media (min-width: 1280px) {
            .product-card-grid {
                grid-template-columns: 1fr 2fr;
                height: 250px;
                gap: 0.5rem 1rem;
            }
            .product-name { font-size: 1.1rem; }
            .product-obs { font-size: 0.85rem; }
            .product-btn { font-size: 0.9rem; padding: 0.5rem 1rem; }
            .product-price-text { font-size: 1.1rem; }
        }

        /* Productos destacados */
        .destacados-scroll::-webkit-scrollbar {
            display: none;
        }

        .destacados-scroll {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Landscape compacto (monitores chicos horizontales) */
        @media (orientation: landscape) and (max-height: 700px) {
            .product-card-grid {
                height: 190px;
                gap: 0.3rem 0.5rem;
            }

            .kiosco-header-inner {
                padding-top: 0.5rem;
                padding-bottom: 0.5rem;
            }

            .kiosco-header-inner .kiosco-logo {
                height: 2.5rem;
            }

            .kiosco-header-inner .kiosco-titulo {
                font-size: 1.125rem;
            }

            .kiosco-header-inner .kiosco-subtexto {
                display: none;
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

        /* Cart bounce animation */
        .cart-bounce {
            animation: cartBounce 0.6s ease;
        }

        @keyframes cartBounce {
            0% { transform: scale(1); }
            20% { transform: scale(1.25); }
            40% { transform: scale(0.9); }
            60% { transform: scale(1.1); }
            80% { transform: scale(0.97); }
            100% { transform: scale(1); }
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
            opacity: 0.9;
        }
    </style>

    <!-- Header con información del comercio y Carrito -->
    <header class="bg-white/95 backdrop-blur-sm shadow-lg z-50 flex-shrink-0 {{ $currentView === 'welcome' ? 'hidden' : '' }}" style="background-image: url('{{ asset('img/' . $clientId . '/fondo.jpg') }}'); background-size: cover; background-position: center;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="kiosco-header-inner flex justify-between items-center py-4">
                <!-- Información del comercio -->
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('img/' . $clientId . '/logo.jpg') }}" alt="Logo" class="kiosco-logo h-14 w-auto">
                    <div class="text-left">
                        @if($comercioData)
                            <h1 class="kiosco-titulo text-xl font-bold text-white">{{ $comercioData->nombre ?? 'Restaurante' }}</h1>
                            @if($comercioData->direccion)
                                <p class="kiosco-subtexto text-sm text-white/90"> <i class="fa fa-map-marker" aria-hidden="true"></i> {{ $comercioData->direccion }}</p>
                            @endif
                            @if($comercioData->slogan)
                                <p class="kiosco-subtexto text-sm font-medium text-orange-200">{{ $comercioData->slogan }}</p>
                            @endif
                            @if($comercioData->texto_extra)
                                <p class="kiosco-subtexto text-xs text-white/80">{{ $comercioData->texto_extra }}</p>
                            @endif
                        @else
                            <h1 class="kiosco-titulo text-xl font-bold text-white">Restaurante</h1>
                        @endif
                        <h1 class="kiosco-titulo text-lg font-bold text-white">KIOSCO AUTOGESTIÓN</h1>
                    </div>
                </div>
                
                <!-- Botones de navegación y Carrito -->
                <div class="flex items-center space-x-4">
                    @if(session('kiosco_authenticated'))
                        <div x-data="{ showLogout: false, logoutPass: '', logoutError: '' }">
                            <button @click="showLogout = true; logoutPass = ''; logoutError = ''"
                                    class="bg-red-500/80 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-sm transition-colors flex items-center"
                                    title="Cerrar sesión">
                                <i class="fas fa-sign-out-alt"></i>
                            </button>

                            <!-- Modal contraseña -->
                            <div x-show="showLogout" x-transition
                                 class="fixed inset-0 bg-black/60 z-[999] flex items-center justify-center p-4"
                                 @click.self="showLogout = false">
                                <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-xs" @click.stop>
                                    <h3 class="text-lg font-bold text-gray-800 mb-3 text-center">
                                        <i class="fas fa-lock text-red-500 mr-1"></i> Cerrar Kiosco
                                    </h3>
                                    <p class="text-sm text-gray-500 mb-3 text-center">Ingresá la contraseña para salir</p>
                                    <input type="password" x-model="logoutPass"
                                           @keydown.enter="
                                               fetch('{{ route('kiosco.logout') }}?password=' + encodeURIComponent(logoutPass))
                                               .then(r => r.json())
                                               .then(d => {
                                                   if (d.ok) { window.location.href = '{{ route('kiosco.login') }}'; }
                                                   else { logoutError = d.error; }
                                               })
                                           "
                                           placeholder="Contraseña"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-400 focus:border-transparent mb-2">
                                    <p x-show="logoutError" x-text="logoutError" class="text-red-500 text-xs mb-2"></p>
                                    <div class="flex gap-2">
                                        <button @click="showLogout = false"
                                                class="flex-1 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 transition">
                                            Cancelar
                                        </button>
                                        <button @click="
                                                fetch('{{ route('kiosco.logout') }}?password=' + encodeURIComponent(logoutPass))
                                                .then(r => r.json())
                                                .then(d => {
                                                    if (d.ok) { window.location.href = '{{ route('kiosco.login') }}'; }
                                                    else { logoutError = d.error; }
                                                })
                                            "
                                                class="flex-1 py-2 bg-red-500 text-white rounded-lg text-sm font-medium hover:bg-red-600 transition">
                                            Salir
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if($currentView !== 'menu')
                        <button wire:click="changeView('menu')"
                                class="hover:opacity-90 px-3 sm:px-6 py-3 rounded-lg font-medium transition-colors flex items-center"
                                style="background-color: var(--kiosco-boton); color: var(--kiosco-texto-boton);">
                            <svg class="w-5 h-5 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            <span class="hidden sm:inline">Volver al Menú</span>
                        </button>
                    @endif
                    
                    <button wire:click="changeView('cart')"
                            id="cart-button"
                            class="hover:opacity-90 px-3 sm:px-6 py-3 rounded-lg font-medium transition-colors flex items-center relative"
                            style="background-color: var(--kiosco-boton); color: var(--kiosco-texto-boton);">
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
                        <div class="absolute left-0 top-1/2 transform -translate-y-1/2 z-50 rounded-full w-8 h-8 flex items-center justify-center shadow-lg cursor-pointer" id="dept-scroll-left" style="display: none; background-color: var(--kiosco-boton); color: var(--kiosco-texto-boton);">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </div>
                        
                        <!-- Indicador de scroll derecho -->
                        <div class="absolute right-0 top-1/2 transform -translate-y-1/2 z-50 rounded-full w-8 h-8 flex items-center justify-center shadow-lg cursor-pointer" id="dept-scroll-right" style="background-color: var(--kiosco-boton); color: var(--kiosco-texto-boton);">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                        
                        <div class="overflow-x-auto scrollbar-hide py-3" id="departamentos-container">
                            <div class="flex gap-3" style="width: max-content;">
                                @foreach($departamentos as $depto)
                                    @php
                                        $tieneProductos = collect($articulos)->contains(fn($a) => $a->DEPTO == $depto->CODIGO && !$a->agotado);
                                    @endphp
                                    @if($tieneProductos)
                                    <button wire:click="scrollToSection({{ $depto->CODIGO }})"
                                            class="hover:opacity-90 px-4 py-2 rounded-full font-medium transition-all transform hover:scale-105 flex items-center whitespace-nowrap flex-shrink-0 backdrop-blur-sm"
                                            style="background-color: var(--kiosco-boton); color: var(--kiosco-texto-boton);">
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
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </header>

    

    <!-- Contenido Principal -->
    <main class="h-full flex flex-col backdrop-blur-sm overflow-y-auto" style="background-color: var(--kiosco-fondo);">
            @if($currentView === 'welcome')
                @include('kiosco.welcome')
            @elseif($currentView === 'menu')
                @include('kiosco.menu')
            @elseif($currentView === 'product')
                @include('kiosco.detalle')
            @elseif($currentView === 'cart')
                @include('kiosco.carrito')
            @elseif($currentView === 'checkout')
                @include('kiosco.checkout')
            @elseif($currentView === 'sugerencias')
                @include('kiosco.sugerencias')
            @elseif($currentView === 'pago-mp')
                @include('kiosco.pago-mp')
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

    // ===== INACTIVITY TIMEOUT =====
    const INACTIVITY_TIMEOUT = 120000; // 2 minutos
    let inactivityTimer = null;

    function resetInactivityTimer() {
        clearTimeout(inactivityTimer);
        inactivityTimer = setTimeout(() => {
            // No resetear si estamos en welcome o pago-mp
            const currentView = @this.get('currentView');
            if (currentView === 'welcome' || currentView === 'pago-mp') {
                resetInactivityTimer();
                return;
            }
            @this.call('resetKiosco');
        }, INACTIVITY_TIMEOUT);
    }

    // Escuchar cualquier interacción
    ['click', 'touchstart', 'scroll', 'keydown'].forEach(evt => {
        document.addEventListener(evt, resetInactivityTimer, { passive: true });
    });

    resetInactivityTimer();

    // ===== CART ANIMATION =====
    Livewire.on('product-added', () => {
        const cartBtn = document.getElementById('cart-button');
        if (cartBtn) {
            cartBtn.classList.add('cart-bounce');
            setTimeout(() => cartBtn.classList.remove('cart-bounce'), 600);
        }
        playSound('add');
    });

    // ===== ORDER CONFIRMED SOUND =====
    Livewire.on('order-confirmed', () => {
        playSound('success');
    });

    // ===== SOUND SYSTEM =====
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

    function playSound(type) {
        try {
            if (audioCtx.state === 'suspended') audioCtx.resume();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);

            if (type === 'add') {
                // Beep corto y agudo
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, audioCtx.currentTime);
                osc.frequency.setValueAtTime(1100, audioCtx.currentTime + 0.08);
                gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.2);
                osc.start(audioCtx.currentTime);
                osc.stop(audioCtx.currentTime + 0.2);
            } else if (type === 'success') {
                // Melodía de éxito (3 notas ascendentes)
                osc.type = 'sine';
                osc.frequency.setValueAtTime(523, audioCtx.currentTime);       // C5
                osc.frequency.setValueAtTime(659, audioCtx.currentTime + 0.15); // E5
                osc.frequency.setValueAtTime(784, audioCtx.currentTime + 0.3);  // G5
                gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.5);
                osc.start(audioCtx.currentTime);
                osc.stop(audioCtx.currentTime + 0.5);
            }
        } catch (e) {
            // Silenciar errores de audio
        }
    }
</script>
@endscript