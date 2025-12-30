<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesión Expirada - BcnSoft</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Card principal -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <!-- Header con ícono -->
            <div class="bg-gradient-to-r from-orange-500 to-red-500 p-8 text-center">
                <div class="inline-block bg-white rounded-full p-4 mb-4 shadow-lg">
                    <i class="fas fa-clock text-5xl text-orange-500"></i>
                </div>
                <h1 class="text-3xl font-bold text-white">Sesión Expirada</h1>
            </div>

            <!-- Contenido -->
            <div class="p-8">
                <div class="text-center mb-8">
                    <p class="text-gray-600 text-lg mb-4">
                        Tu sesión ha expirado por inactividad.
                    </p>
                    <p class="text-gray-500 text-sm">
                        Por motivos de seguridad, debes iniciar sesión nuevamente para continuar.
                    </p>
                </div>

                <!-- Información del módulo -->
                @if(isset($moduleName))
                <div class="bg-gray-50 rounded-lg p-4 mb-6 border-l-4 border-orange-500">
                    <div class="flex items-center">
                        <i class="fas fa-info-circle text-orange-500 mr-3 text-xl"></i>
                        <div>
                            <p class="text-sm text-gray-600">Último módulo usado:</p>
                            <p class="text-lg font-semibold text-gray-800">{{ $moduleName }}</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Botón de login -->
                <a href="{{ $loginRoute }}"
                   class="block w-full bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white font-bold py-4 px-6 rounded-lg shadow-lg transition-all duration-300 transform hover:scale-105 text-center">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Volver a Iniciar Sesión
                </a>

                <!-- Link alternativo -->
                <div class="mt-6 text-center">
                    <a href="{{ route('home') }}" class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
                        <i class="fas fa-home mr-1"></i>
                        Ir a la página principal
                    </a>
                </div>
            </div>

            <!-- Footer con información de seguridad -->
            <div class="bg-gray-50 px-8 py-4 border-t border-gray-200">
                <div class="flex items-start text-sm text-gray-600">
                    <i class="fas fa-shield-alt text-orange-500 mr-2 mt-1"></i>
                    <p>
                        <strong>Consejo de seguridad:</strong> Cierra tu navegador completamente si estás usando una computadora compartida.
                    </p>
                </div>
            </div>
        </div>

        <!-- Información adicional -->
        <div class="mt-6 text-center text-sm text-gray-600">
            <p>¿Necesitas ayuda? Contacta al administrador del sistema</p>
        </div>
    </div>

    <!-- Script para auto-redirección después de cierto tiempo (opcional) -->
    <script>
        // Opcional: Auto-redirigir después de 30 segundos
        // setTimeout(() => {
        //     window.location.href = "{{ $loginRoute }}";
        // }, 30000);
    </script>
</body>
</html>
