<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $nombreTienda }} - Iniciar Sesion</title>
    <link href="{{ asset('images/logo.ico') }}" rel="shortcut icon" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $datos = session('tienda_datos', []);
    $colorFondo = $datos['color_fondo'] ?? '#f9fafb';
    $colorBoton = $datos['color_boton'] ?? '#FFAF22';
    $colorTexto = $datos['color_texto'] ?? '#1f2937';
@endphp
<body class="font-sans antialiased min-h-screen flex items-center justify-center px-4" style="background-color: {{ $colorFondo }}">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <!-- Logo / Nombre -->
            <div class="text-center mb-8">
                @if(session('tienda_logo'))
                    <img src="{{ session('tienda_logo') }}" alt="{{ $nombreTienda }}" class="w-20 h-20 object-contain rounded-2xl mx-auto mb-4">
                @else
                    <div class="bg-[#1E1B35] text-white font-bold rounded-2xl w-20 h-20 flex items-center justify-center text-3xl mx-auto mb-4">
                        {{ strtoupper(substr($nombreTienda, 0, 1)) }}
                    </div>
                @endif
                <h1 class="text-2xl font-bold text-gray-800">{{ $nombreTienda }}</h1>
                <p class="text-gray-500 mt-1">Ingresa para acceder a la tienda</p>
            </div>

            <!-- Errores -->
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- Formulario -->
            <form method="POST" action="{{ route('tienda.login', $tienda) }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent outline-none transition"
                                   style="--tw-ring-color: {{ $colorBoton }}"
                                   placeholder="tu@email.com">
                        </div>
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Clave</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" id="password" name="password" required
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent outline-none transition"
                                   style="--tw-ring-color: {{ $colorBoton }}"
                                   placeholder="Ingresa tu clave">
                        </div>
                    </div>
                </div>
                <button type="submit"
                        class="w-full mt-6 py-3 text-white font-semibold rounded-lg transition-opacity hover:opacity-90 shadow-lg"
                        style="background-color: {{ $colorBoton }}">
                    Ingresar
                </button>
            </form>
        </div>
    </div>
</body>
</html>
