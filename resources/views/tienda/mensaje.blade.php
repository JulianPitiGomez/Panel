<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $nombreTienda }} - {{ $titulo }}</title>
    <link href="{{ asset('images/logo.ico') }}" rel="shortcut icon" />
    @include('tienda.partials.estilo')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php $datos = session('tienda_datos', []); $colorFondo = $datos['color_fondo'] ?? '#f9fafb'; $colorBoton = $datos['color_boton'] ?? '#FFAF22'; $error = $error ?? false; @endphp
<body class="t-body font-sans antialiased min-h-screen flex items-center justify-center px-4" style="background-color: {{ $colorFondo }}">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl p-8 text-center">
            <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-5 {{ $error ? 'bg-red-100 text-red-500' : 'bg-green-100 text-green-500' }}">
                <i class="fas {{ $icono ?? 'fa-check' }} text-3xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ $titulo }}</h1>
            <p class="text-gray-500">{{ $texto }}</p>
            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('tienda.catalogo', $tienda) }}" class="px-6 py-3 text-white font-semibold rounded-lg" style="background-color: {{ $colorBoton }}">
                    <i class="fas fa-arrow-left mr-2"></i> Ir a la tienda
                </a>
                @if($error)
                    <a href="{{ route('tienda.recuperar', $tienda) }}" class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg">Recuperar contraseña</a>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
