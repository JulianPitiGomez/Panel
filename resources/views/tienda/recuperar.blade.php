<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $nombreTienda }} - Recuperar contraseña</title>
    <link href="{{ asset('images/logo.ico') }}" rel="shortcut icon" />
    @include('tienda.partials.estilo')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php $datos = session('tienda_datos', []); $colorFondo = $datos['color_fondo'] ?? '#f9fafb'; $colorBoton = $datos['color_boton'] ?? '#FFAF22'; @endphp
<body class="t-body font-sans antialiased min-h-screen flex items-center justify-center px-4" style="background-color: {{ $colorFondo }}">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            @include('tienda.partials.cuenta-cabecera', ['subtitulo' => 'Recuperá tu contraseña'])

            <form method="POST" action="{{ route('tienda.recuperar.post', $tienda) }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">El mail de tu cuenta</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent outline-none" style="--tw-ring-color: {{ $colorBoton }}">
                </div>
                <button type="submit" class="w-full py-3 text-white font-semibold rounded-lg transition-opacity hover:opacity-90 shadow-lg" style="background-color: {{ $colorBoton }}">
                    Mandarme el enlace
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-6">
                <a href="{{ route('tienda.ingresar', $tienda) }}" class="underline">Volver a ingresar</a>
            </p>
        </div>
    </div>
</body>
</html>
