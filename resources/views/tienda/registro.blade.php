<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $nombreTienda }} - Crear cuenta</title>
    <link href="{{ asset('images/logo.ico') }}" rel="shortcut icon" />
    @include('tienda.partials.estilo')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php $datos = session('tienda_datos', []); $colorFondo = $datos['color_fondo'] ?? '#f9fafb'; $colorBoton = $datos['color_boton'] ?? '#FFAF22'; @endphp
<body class="t-body font-sans antialiased min-h-screen flex items-center justify-center px-4 py-8" style="background-color: {{ $colorFondo }}">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            @include('tienda.partials.cuenta-cabecera', ['subtitulo' => 'Creá tu cuenta para comprar'])

            <form method="POST" action="{{ route('tienda.registro.post', $tienda) }}" class="space-y-4">
                @csrf
                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre y apellido</label>
                    <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}" required maxlength="100" autocomplete="name"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent outline-none" style="--tw-ring-color: {{ $colorBoton }}">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent outline-none" style="--tw-ring-color: {{ $colorBoton }}">
                </div>
                <div>
                    <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">Teléfono <span class="text-gray-400 font-normal">(para coordinar la entrega)</span></label>
                    <input type="tel" id="telefono" name="telefono" value="{{ old('telefono') }}" maxlength="40" autocomplete="tel"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent outline-none" style="--tw-ring-color: {{ $colorBoton }}">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                        <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent outline-none" style="--tw-ring-color: {{ $colorBoton }}">
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Repetila</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required minlength="6" autocomplete="new-password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent outline-none" style="--tw-ring-color: {{ $colorBoton }}">
                    </div>
                </div>
                <label class="flex items-start gap-2 text-xs text-gray-500">
                    <input type="checkbox" name="acepto" value="1" class="mt-0.5 w-4 h-4 rounded" {{ old('acepto') ? 'checked' : '' }}>
                    <span>Acepto los <a href="{{ route('tienda.legales', $tienda) }}" target="_blank" class="underline">términos y condiciones y la política de privacidad</a>.</span>
                </label>
                <button type="submit" class="w-full py-3 text-white font-semibold rounded-lg transition-opacity hover:opacity-90 shadow-lg" style="background-color: {{ $colorBoton }}">
                    Crear cuenta
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-6">
                ¿Ya tenés cuenta? <a href="{{ route('tienda.ingresar', $tienda) }}" class="font-semibold underline" style="color: {{ $colorBoton }}">Ingresá</a>
            </p>
        </div>
    </div>
</body>
</html>
