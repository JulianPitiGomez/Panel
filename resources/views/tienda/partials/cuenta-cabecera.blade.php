{{-- Cabecera compartida de las pantallas de cuenta (login, registro, recuperar). Requiere $nombreTienda, $subtitulo --}}
@php
    $datos = session('tienda_datos', []);
    $colorFondo = $datos['color_fondo'] ?? '#f9fafb';
    $colorBoton = $datos['color_boton'] ?? '#FFAF22';
    $colorTexto = $datos['color_texto'] ?? '#1f2937';
@endphp
<div class="text-center mb-8">
    @if(session('tienda_logo'))
        <img src="{{ session('tienda_logo') }}" alt="{{ $nombreTienda }}" class="w-20 h-20 object-contain rounded-2xl mx-auto mb-4">
    @else
        <div class="bg-[#1E1B35] text-white font-bold rounded-2xl w-20 h-20 flex items-center justify-center text-3xl mx-auto mb-4">
            {{ strtoupper(substr($nombreTienda, 0, 1)) }}
        </div>
    @endif
    <h1 class="text-2xl font-bold text-gray-800">{{ $nombreTienda }}</h1>
    <p class="text-gray-500 mt-1">{{ $subtitulo }}</p>
</div>

@if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
        @foreach ($errors->all() as $error)
            <p class="text-sm">{{ $error }}</p>
        @endforeach
    </div>
@endif
