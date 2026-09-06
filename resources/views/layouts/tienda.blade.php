<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ session('tienda_nombre', 'Tienda') }}</title>
    <link href="{{ asset('images/logo.ico') }}" rel="shortcut icon" />

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    {{-- iOS / mobile: prevenir rebote horizontal y overflow lateral --}}
    <style>
        html, body {
            overflow-x: hidden;
            max-width: 100vw;
            overscroll-behavior-x: none;
            -webkit-text-size-adjust: 100%;
        }
        body { position: relative; }
        /* Safe-area para iPhone con home indicator/notch */
        .safe-bottom { padding-bottom: env(safe-area-inset-bottom); }
        .safe-right { padding-right: env(safe-area-inset-right); }
        .safe-left { padding-left: env(safe-area-inset-left); }
    </style>
</head>
@php
    $datos = session('tienda_datos', []);
    $colorFondo = $datos['color_fondo'] ?? '#f9fafb';
    $estiloHeader = $datos['estilo_header'] ?? 'completo';
    // Fallback: si es banner pero no hay portada, usar simple
    if ($estiloHeader === 'banner' && !session('tienda_portada')) {
        $estiloHeader = 'simple';
    }
@endphp
<body class="font-sans antialiased overflow-x-hidden" style="background-color: {{ $colorFondo }};">
    <div class="min-h-screen w-full overflow-x-hidden">

        {{-- Header bar (visible en completo y simple) --}}
        @if($estiloHeader !== 'banner')
            <header class="bg-white shadow-sm sticky top-0 z-40">
                <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
                    <a href="{{ route('tienda.index', session('tienda_slug')) }}" class="flex items-center gap-3">
                        @if(session('tienda_logo'))
                            <img src="{{ session('tienda_logo') }}" alt="{{ session('tienda_nombre') }}" class="h-10 w-10 object-contain rounded-lg">
                        @else
                            <div class="bg-[#1E1B35] text-white font-bold rounded-lg w-10 h-10 flex items-center justify-center text-lg">
                                {{ strtoupper(substr(session('tienda_nombre', 'T'), 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <span class="text-lg font-semibold text-gray-800 block leading-tight">{{ session('tienda_nombre', 'Tienda') }}</span>
                            @if(!empty($datos['slogan']))
                                <span class="text-xs text-gray-400">{{ $datos['slogan'] }}</span>
                            @endif
                        </div>
                    </a>
                    <div class="flex items-center gap-4">
                        @if(session('tienda_comprador_nombre'))
                            <a href="{{ route('tienda.mis-pedidos', session('tienda_slug')) }}"
                               class="text-sm text-gray-500 hover:text-gray-700 transition">
                                <i class="fas fa-clipboard-list mr-1"></i><span class="hidden sm:inline">Mis Pedidos</span>
                            </a>
                            <span class="text-sm text-gray-600 hidden sm:inline">
                                <i class="fas fa-user mr-1"></i> {{ session('tienda_comprador_nombre') }}
                            </span>
                            <a href="{{ route('tienda.logout', session('tienda_slug')) }}"
                               class="text-sm text-red-500 hover:text-red-700">
                                <i class="fas fa-sign-out-alt"></i>
                            </a>
                        @elseif(!\App\Services\TiendaService::esVitrina(session('tienda_modo')))
                            <a href="{{ route('tienda.ingresar', session('tienda_slug')) }}"
                               class="text-sm text-gray-500 hover:text-gray-700 transition">
                                <i class="fas fa-user mr-1"></i><span class="hidden sm:inline">Ingresar</span>
                            </a>
                            @if(\App\Services\TiendaService::permiteRegistro(session('tienda_modo')))
                                <a href="{{ route('tienda.registro', session('tienda_slug')) }}"
                                   class="text-sm font-semibold px-3 py-1.5 rounded-lg text-white" style="background-color: {{ $datos['color_boton'] ?? '#FFAF22' }}">
                                    Crear cuenta
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            </header>
        @endif

        {{-- Portada/Banner --}}
        @if($estiloHeader === 'completo' && session('tienda_portada'))
            {{-- Completo: header bar + portada con info --}}
            <div class="relative">
                <img src="{{ session('tienda_portada') }}" alt="Portada" class="w-full h-64 sm:h-80 object-cover">
                <div class="absolute inset-0 bg-black/50"></div>
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-4">
                    @if(session('tienda_logo'))
                        <img src="{{ session('tienda_logo') }}" alt="{{ session('tienda_nombre') }}" class="w-24 h-24 sm:w-28 sm:h-28 object-contain rounded-2xl shadow-lg mb-3 bg-white/10 backdrop-blur-sm p-1">
                    @endif
                    <h2 class="text-2xl sm:text-3xl font-bold text-white drop-shadow-lg">{{ session('tienda_nombre', 'Tienda') }}</h2>
                    @if(!empty($datos['slogan']))
                        <p class="text-sm sm:text-base text-white/80 mt-1">{{ $datos['slogan'] }}</p>
                    @endif
                    <div class="flex flex-wrap items-center justify-center gap-3 sm:gap-4 text-white/70 text-xs sm:text-sm mt-3">
                        @if(!empty($datos['direccion']))
                            <span><i class="fas fa-map-marker-alt mr-1"></i> {{ $datos['direccion'] }}{{ !empty($datos['localidad']) ? ', ' . $datos['localidad'] : '' }}</span>
                        @endif
                        @if(!empty($datos['telefono']))
                            <span><i class="fas fa-phone mr-1"></i> {{ $datos['telefono'] }}</span>
                        @endif
                        @if(!empty($datos['horario']))
                            <span><i class="fas fa-clock mr-1"></i> {{ $datos['horario'] }}</span>
                        @endif
                    </div>
                </div>
            </div>
        @elseif($estiloHeader === 'banner')
            {{-- Banner only: portada con nav superpuesto, sin header bar --}}
            <div class="relative">
                <img src="{{ session('tienda_portada') }}" alt="Portada" class="w-full h-64 sm:h-80 object-cover">
                <div class="absolute inset-0 bg-black/50"></div>
                {{-- Nav overlay --}}
                <div class="absolute top-0 left-0 right-0 z-10 px-4 py-3">
                    <div class="max-w-7xl mx-auto flex items-center justify-between">
                        <a href="{{ route('tienda.index', session('tienda_slug')) }}" class="flex items-center gap-3">
                            @if(session('tienda_logo'))
                                <img src="{{ session('tienda_logo') }}" class="h-10 w-10 object-contain rounded-lg">
                            @endif
                            <span class="text-white font-semibold text-lg drop-shadow">{{ session('tienda_nombre', 'Tienda') }}</span>
                        </a>
                        <div class="flex items-center gap-4">
                            @if(session('tienda_comprador_nombre'))
                                <a href="{{ route('tienda.mis-pedidos', session('tienda_slug')) }}" class="text-sm text-white/80 hover:text-white transition">
                                    <i class="fas fa-clipboard-list mr-1"></i><span class="hidden sm:inline">Mis Pedidos</span>
                                </a>
                                <span class="text-sm text-white/70 hidden sm:inline">
                                    <i class="fas fa-user mr-1"></i> {{ session('tienda_comprador_nombre') }}
                                </span>
                                <a href="{{ route('tienda.logout', session('tienda_slug')) }}" class="text-sm text-red-300 hover:text-red-100">
                                    <i class="fas fa-sign-out-alt"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                {{-- Center info --}}
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-4">
                    <h2 class="text-2xl sm:text-3xl font-bold text-white drop-shadow-lg">{{ session('tienda_nombre', 'Tienda') }}</h2>
                    @if(!empty($datos['slogan']))
                        <p class="text-sm sm:text-base text-white/80 mt-1">{{ $datos['slogan'] }}</p>
                    @endif
                    <div class="flex flex-wrap items-center justify-center gap-3 sm:gap-4 text-white/70 text-xs sm:text-sm mt-3">
                        @if(!empty($datos['direccion']))
                            <span><i class="fas fa-map-marker-alt mr-1"></i> {{ $datos['direccion'] }}{{ !empty($datos['localidad']) ? ', ' . $datos['localidad'] : '' }}</span>
                        @endif
                        @if(!empty($datos['telefono']))
                            <span><i class="fas fa-phone mr-1"></i> {{ $datos['telefono'] }}</span>
                        @endif
                        @if(!empty($datos['horario']))
                            <span><i class="fas fa-clock mr-1"></i> {{ $datos['horario'] }}</span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
        {{-- Simple: no muestra portada, solo el header bar que ya se renderizó arriba --}}

        <!-- Content -->
        <main class="max-w-7xl mx-auto px-3 sm:px-4 py-6 pb-24 w-full overflow-x-hidden">
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="bg-[#1E1B35] text-white py-6 mt-auto">
            <div class="max-w-7xl mx-auto px-4 text-center text-sm">
                @php $datos = session('tienda_datos', []); @endphp
                <p class="font-semibold mb-2">{{ session('tienda_nombre', 'Tienda') }}</p>
                @if(!empty($datos['razon_social']) || !empty($datos['cuit']))
                    <p class="text-xs text-gray-400 mb-2">{{ $datos['razon_social'] ?? '' }}{{ !empty($datos['cuit']) ? ' · CUIT ' . $datos['cuit'] : '' }}</p>
                @endif
                <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-xs text-gray-400 mb-3">
                    <a href="{{ route('tienda.legales', session('tienda_slug')) }}" class="hover:text-[#FFAF22]">Términos y privacidad</a>
                    @if(!\App\Services\TiendaService::esVitrina(session('tienda_modo')))
                        <a href="{{ route('tienda.arrepentimiento', session('tienda_slug')) }}" class="hover:text-[#FFAF22] font-semibold">Botón de arrepentimiento</a>
                    @endif
                </div>
                <div class="flex items-center justify-center gap-4 text-gray-400">
                    @if(!empty($datos['instagram']))
                        <a href="https://instagram.com/{{ ltrim($datos['instagram'], '@') }}" target="_blank" class="hover:text-[#FFAF22] transition">
                            <i class="fab fa-instagram text-lg"></i>
                        </a>
                    @endif
                    @if(!empty($datos['facebook']))
                        <a href="https://{{ str_contains($datos['facebook'], 'facebook.com') ? $datos['facebook'] : 'facebook.com/' . $datos['facebook'] }}" target="_blank" class="hover:text-[#FFAF22] transition">
                            <i class="fab fa-facebook text-lg"></i>
                        </a>
                    @endif
                    @if(!empty($datos['telefono']))
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $datos['telefono']) }}" target="_blank" class="hover:text-[#FFAF22] transition">
                            <i class="fab fa-whatsapp text-lg"></i>
                        </a>
                    @endif
                </div>
                @if(!empty($datos['direccion']))
                    <p class="text-gray-500 text-xs mt-2">{{ $datos['direccion'] }}{{ !empty($datos['localidad']) ? ', ' . $datos['localidad'] : '' }}</p>
                @endif
            </div>
        </footer>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
