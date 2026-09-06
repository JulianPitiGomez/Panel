<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $nombreTienda }} - Nueva contraseña</title>
    <link href="{{ asset('images/logo.ico') }}" rel="shortcut icon" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php $datos = session('tienda_datos', []); $colorFondo = $datos['color_fondo'] ?? '#f9fafb'; $colorBoton = $datos['color_boton'] ?? '#FFAF22'; @endphp
<body class="font-sans antialiased min-h-screen flex items-center justify-center px-4" style="background-color: {{ $colorFondo }}">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            @include('tienda.partials.cuenta-cabecera', ['subtitulo' => 'Elegí una contraseña nueva'])

            <form method="POST" action="{{ route('tienda.nueva-clave.post', ['tienda' => $tienda, 'token' => $token]) }}" class="space-y-4">
                @csrf
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña nueva</label>
                    <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent outline-none" style="--tw-ring-color: {{ $colorBoton }}">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Repetila</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="6" autocomplete="new-password"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent outline-none" style="--tw-ring-color: {{ $colorBoton }}">
                </div>
                <button type="submit" class="w-full py-3 text-white font-semibold rounded-lg transition-opacity hover:opacity-90 shadow-lg" style="background-color: {{ $colorBoton }}">
                    Guardar contraseña
                </button>
            </form>
        </div>
    </div>
</body>
</html>
