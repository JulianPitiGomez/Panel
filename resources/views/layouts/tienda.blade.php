<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php $meta = $meta ?? []; @endphp
    <title>{{ $meta['title'] ?? session('tienda_nombre', 'Tienda') }}</title>
    @if(!empty($meta['description']))<meta name="description" content="{{ $meta['description'] }}">@endif
    <meta name="robots" content="{{ $meta['robots'] ?? 'noindex, nofollow' }}">
    @if(!empty($meta['url']))<link rel="canonical" href="{{ $meta['url'] }}">@endif
    <meta property="og:site_name" content="{{ session('tienda_nombre', 'Tienda') }}">
    <meta property="og:type" content="{{ $meta['type'] ?? 'website' }}">
    <meta property="og:title" content="{{ $meta['title'] ?? session('tienda_nombre', 'Tienda') }}">
    @if(!empty($meta['description']))<meta property="og:description" content="{{ $meta['description'] }}">@endif
    @if(!empty($meta['url']))<meta property="og:url" content="{{ $meta['url'] }}">@endif
    @if(!empty($meta['image']))<meta property="og:image" content="{{ $meta['image'] }}">@endif
    <meta name="twitter:card" content="{{ !empty($meta['image']) ? 'summary_large_image' : 'summary' }}">
    @if(!empty($meta['jsonld']))<script type="application/ld+json">{!! json_encode($meta['jsonld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>@endif
    @if(session('tienda_slug'))<link rel="sitemap" type="application/xml" href="{{ route('tienda.sitemap', session('tienda_slug')) }}">@endif
    <link href="{{ session('tienda_logo') ?: asset('images/logo.ico') }}" rel="icon" />

    @include('tienda.partials.estilo')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        html, body { overflow-x: hidden; max-width: 100vw; overscroll-behavior-x: none; -webkit-text-size-adjust: 100%; }
        body { position: relative; }
        .safe-bottom { padding-bottom: env(safe-area-inset-bottom); }
        .safe-right { padding-right: env(safe-area-inset-right); }
        .safe-left { padding-left: env(safe-area-inset-left); }
    </style>
</head>
@php
    $datos = session('tienda_datos', []);
    $slug = session('tienda_slug');
    $nombreTienda = session('tienda_nombre', 'Tienda');
    $estiloHeader = $datos['estilo_header'] ?? 'completo';
    if ($estiloHeader === 'banner' && !session('tienda_portada')) {
        $estiloHeader = 'simple';
    }
    $esVitrinaLayout = \App\Services\TiendaService::esVitrina(session('tienda_modo'));
    $cantCarrito = count(session('tienda_carrito', []));
    $cantFavoritos = session('tienda_prefix') ? (new \App\Services\TiendaFavoritoService(session('tienda_prefix')))->cantidad() : 0;
    $rutaActual = request()->route()?->getName();
    $headerOscuro = $estiloHeader === 'banner';
@endphp
<body class="t-body font-sans antialiased overflow-x-hidden">
    <div class="min-h-screen w-full overflow-x-hidden flex flex-col">

        {{-- Header --}}
        <header class="{{ $headerOscuro ? 'absolute inset-x-0 top-0 z-40 bg-transparent' : 'sticky top-0 z-40 border-b shadow-sm' }}" style="{{ $headerOscuro ? '' : 'background-color: var(--tcard); border-color: var(--tborde);' }}">
            <div class="max-w-7xl mx-auto px-3 sm:px-4 h-16 flex items-center gap-3 sm:gap-6">
                <a href="{{ route('tienda.index', $slug) }}" class="flex items-center gap-2.5 min-w-0">
                    @if(session('tienda_logo'))
                        <img src="{{ session('tienda_logo') }}" alt="{{ $nombreTienda }}" class="h-10 w-10 object-contain rounded-xl flex-shrink-0 {{ $headerOscuro ? 'bg-white/90 p-0.5' : '' }}">
                    @else
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg font-bold flex-shrink-0" style="background-color: var(--tp); color: var(--tp-ink);">{{ strtoupper(substr($nombreTienda, 0, 1)) }}</div>
                    @endif
                    <div class="min-w-0">
                        <span class="t-h block text-base sm:text-lg font-bold leading-tight truncate {{ $headerOscuro ? 'text-white drop-shadow' : 't-text' }}">{{ $nombreTienda }}</span>
                        @if(!empty($datos['slogan']) && !$headerOscuro)
                            <span class="hidden sm:block text-[11px] t-muted truncate">{{ $datos['slogan'] }}</span>
                        @endif
                    </div>
                </a>

                {{-- Buscador rapido en escritorio (fuera del catalogo, que tiene el suyo) --}}
                @if(!$headerOscuro && !in_array($rutaActual, ['tienda.catalogo', 'tienda.producto', 'tienda.favoritos'], true))
                    <form action="{{ route('tienda.catalogo', $slug) }}" method="get" class="hidden md:flex flex-1 max-w-md mx-auto">
                        <div class="relative w-full">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 t-muted"><i class="fas fa-search text-sm"></i></span>
                            <input type="search" name="q" placeholder="Buscar productos..." class="t-input !pl-9 !py-2 !rounded-full text-sm">
                        </div>
                    </form>
                @else
                    <div class="flex-1"></div>
                @endif

                <nav class="flex items-center gap-0.5 sm:gap-1 flex-shrink-0">
                    @php $claseIcono = $headerOscuro ? '!text-white hover:!bg-white/15' : ''; @endphp
                    @if(!$esVitrinaLayout || $cantFavoritos)
                        <a href="{{ route('tienda.favoritos', $slug) }}" class="t-icon-btn relative {{ $claseIcono }}" title="Mis favoritos">
                            <i class="{{ $cantFavoritos ? 'fas' : 'far' }} fa-heart" style="{{ $cantFavoritos ? 'color: #ef4444;' : '' }}"></i>
                            @if($cantFavoritos)<span class="absolute -top-0.5 -right-0.5 min-w-[1.1rem] h-[1.1rem] px-1 rounded-full text-[10px] font-bold flex items-center justify-center" style="background-color: var(--tp); color: var(--tp-ink);">{{ $cantFavoritos }}</span>@endif
                        </a>
                    @endif
                    @if(!$esVitrinaLayout)
                        <a href="{{ route('tienda.carrito', $slug) }}" class="t-icon-btn relative {{ $claseIcono }}" title="Carrito">
                            <i class="fas fa-shopping-bag"></i>
                            @if($cantCarrito)<span class="absolute -top-0.5 -right-0.5 min-w-[1.1rem] h-[1.1rem] px-1 rounded-full text-[10px] font-bold flex items-center justify-center" style="background-color: var(--tp); color: var(--tp-ink);">{{ $cantCarrito }}</span>@endif
                        </a>
                    @endif

                    @if(session('tienda_comprador_nombre'))
                        <div class="relative" x-data="{ abierto: false }" @click.outside="abierto = false">
                            <button type="button" @click="abierto = !abierto" class="t-btn-ghost !px-2 sm:!px-3 {{ $claseIcono }}">
                                <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold" style="background-color: var(--tp-soft); color: var(--tp-dark);">{{ strtoupper(substr(trim(session('tienda_comprador_nombre')), 0, 1)) }}</span>
                                <span class="hidden sm:inline text-sm max-w-[9rem] truncate">{{ session('tienda_comprador_nombre') }}</span>
                                <i class="fas fa-chevron-down text-[10px] opacity-60"></i>
                            </button>
                            <div x-show="abierto" x-transition class="absolute right-0 mt-2 w-56 t-card p-1.5 shadow-xl z-50 t-text" style="display: none;">
                                <p class="px-3 py-2 text-xs t-muted truncate">{{ session('tienda_comprador_email') }}</p>
                                <a href="{{ route('tienda.mis-pedidos', $slug) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm hover:bg-[var(--tp-tint)]"><i class="fas fa-clipboard-list w-4 t-muted"></i> Mis pedidos</a>
                                @if(session('tienda_comprador_id'))
                                    <a href="{{ route('tienda.mi-cuenta', $slug) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm hover:bg-[var(--tp-tint)]"><i class="fas fa-wallet w-4 t-muted"></i> Mi cuenta</a>
                                @endif
                                <a href="{{ route('tienda.favoritos', $slug) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm hover:bg-[var(--tp-tint)]"><i class="fas fa-heart w-4 t-muted"></i> Mis favoritos</a>
                                <div class="my-1 border-t" style="border-color: var(--tborde);"></div>
                                <a href="{{ route('tienda.logout', $slug) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm hover:bg-red-50" style="color: var(--tdanger);"><i class="fas fa-sign-out-alt w-4"></i> Salir</a>
                            </div>
                        </div>
                    @elseif(!$esVitrinaLayout)
                        <a href="{{ route('tienda.ingresar', $slug) }}" class="t-btn-ghost !px-2 sm:!px-3 {{ $claseIcono }}"><i class="far fa-user"></i><span class="hidden sm:inline text-sm">Ingresar</span></a>
                        @if(\App\Services\TiendaService::permiteRegistro(session('tienda_modo')))
                            <a href="{{ route('tienda.registro', $slug) }}" class="t-btn t-btn-sm ml-1 hidden sm:inline-flex">Crear cuenta</a>
                        @endif
                    @endif
                </nav>
            </div>
        </header>

        {{-- Portada --}}
        @if(($estiloHeader === 'completo' || $estiloHeader === 'banner') && session('tienda_portada'))
            <section class="relative overflow-hidden">
                <img src="{{ session('tienda_portada') }}" alt="" class="absolute inset-0 w-full h-full object-cover">
                <div class="absolute inset-0 t-hero-overlay"></div>
                <div class="relative max-w-7xl mx-auto px-4 {{ $headerOscuro ? 'pt-24 pb-12 sm:pt-28 sm:pb-16' : 'py-12 sm:py-16' }} flex flex-col items-center text-center">
                    @if(session('tienda_logo'))
                        <img src="{{ session('tienda_logo') }}" alt="{{ $nombreTienda }}" class="w-20 h-20 sm:w-24 sm:h-24 object-contain rounded-2xl shadow-xl mb-4 bg-white/90 p-1.5">
                    @endif
                    <h1 class="t-h text-3xl sm:text-5xl font-extrabold text-white drop-shadow-lg tracking-tight">{{ $nombreTienda }}</h1>
                    @if(!empty($datos['slogan']))
                        <p class="text-base sm:text-lg text-white/90 mt-2 max-w-xl">{{ $datos['slogan'] }}</p>
                    @endif
                    @if(!empty($datos['direccion']) || !empty($datos['telefono']) || !empty($datos['horario']))
                        <div class="flex flex-wrap items-center justify-center gap-2 mt-5">
                            @if(!empty($datos['direccion']))<span class="t-hero-pill"><i class="fas fa-map-marker-alt"></i> {{ $datos['direccion'] }}{{ !empty($datos['localidad']) ? ', ' . $datos['localidad'] : '' }}</span>@endif
                            @if(!empty($datos['telefono']))<a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $datos['telefono']) }}" target="_blank" class="t-hero-pill hover:bg-white/25"><i class="fab fa-whatsapp"></i> {{ $datos['telefono'] }}</a>@endif
                            @if(!empty($datos['horario']))<span class="t-hero-pill"><i class="far fa-clock"></i> {{ $datos['horario'] }}</span>@endif
                        </div>
                    @endif
                </div>
            </section>
        @elseif($headerOscuro)
            <div class="h-16"></div>
        @endif

        <main class="max-w-7xl mx-auto px-3 sm:px-4 py-6 pb-24 w-full overflow-x-hidden flex-1">
            {{ $slot }}
        </main>

        {{-- Footer --}}
        <footer class="mt-auto border-t" style="background-color: var(--tcard); border-color: var(--tborde);">
            <div class="max-w-7xl mx-auto px-4 py-8 grid gap-6 sm:grid-cols-3 text-sm">
                <div>
                    <p class="t-h font-bold t-text">{{ $nombreTienda }}</p>
                    @if(!empty($datos['razon_social']) || !empty($datos['cuit']))
                        <p class="text-xs t-muted mt-1">{{ $datos['razon_social'] ?? '' }}{{ !empty($datos['cuit']) ? ' · CUIT ' . $datos['cuit'] : '' }}</p>
                    @endif
                    @if(!empty($datos['direccion']))<p class="text-xs t-muted mt-1"><i class="fas fa-map-marker-alt mr-1"></i>{{ $datos['direccion'] }}{{ !empty($datos['localidad']) ? ', ' . $datos['localidad'] : '' }}</p>@endif
                    @if(!empty($datos['horario']))<p class="text-xs t-muted mt-1"><i class="far fa-clock mr-1"></i>{{ $datos['horario'] }}</p>@endif
                </div>
                <div class="text-xs t-soft space-y-1.5">
                    <a href="{{ route('tienda.catalogo', $slug) }}" class="block hover:underline">Catálogo</a>
                    @if(!$esVitrinaLayout)
                        <a href="{{ route('tienda.mis-pedidos', $slug) }}" class="block hover:underline">Mis pedidos</a>
                        <a href="{{ route('tienda.arrepentimiento', $slug) }}" class="block hover:underline font-semibold">Botón de arrepentimiento</a>
                    @endif
                    <a href="{{ route('tienda.legales', $slug) }}" class="block hover:underline">Términos y privacidad</a>
                </div>
                <div class="flex sm:justify-end items-start gap-2">
                    @if(!empty($datos['instagram']))<a href="https://instagram.com/{{ ltrim($datos['instagram'], '@') }}" target="_blank" class="t-icon-btn" title="Instagram"><i class="fab fa-instagram text-lg"></i></a>@endif
                    @if(!empty($datos['facebook']))<a href="https://{{ str_contains($datos['facebook'], 'facebook.com') ? $datos['facebook'] : 'facebook.com/' . $datos['facebook'] }}" target="_blank" class="t-icon-btn" title="Facebook"><i class="fab fa-facebook text-lg"></i></a>@endif
                    @if(!empty($datos['telefono']))<a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $datos['telefono']) }}" target="_blank" class="t-icon-btn" title="WhatsApp"><i class="fab fa-whatsapp text-lg"></i></a>@endif
                </div>
            </div>
        </footer>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
