<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Panel Vendedores</title>
    <link href="{{asset('images/logo.ico')}}" rel="shortcut icon" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Livewire Styles -->
    @livewireStyles
</head>
<body class="font-sans antialiased" x-data="{ mostrarConfirmacionSalir: false }">
    <!-- Modal de confirmación para cerrar sesión -->
    <div x-show="mostrarConfirmacionSalir"
         x-transition.opacity
         class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-50 flex items-center justify-center px-4"
         style="display: none;">
        <div @click.away="mostrarConfirmacionSalir = false"
             x-transition
             class="bg-white rounded-lg shadow-2xl max-w-md w-full p-6">
            <div class="flex items-center justify-center w-16 h-16 mx-auto bg-yellow-100 rounded-full mb-4">
                <i class="fas fa-sign-out-alt text-[#FFAF22] text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 text-center mb-2">¿Cerrar sesión?</h3>
            <p class="text-gray-600 text-center mb-6">¿Estás seguro que deseas salir del panel de vendedores?</p>
            <div class="flex gap-3">
                <button @click="mostrarConfirmacionSalir = false"
                        class="flex-1 px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold rounded-lg transition-colors">
                    Cancelar
                </button>
                <a href="{{route('logout.vendedores')}}"
                   class="flex-1 px-4 py-3  bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition-colors text-center">
                    Salir
                </a>
            </div>
        </div>
    </div>

    <div class="min-h-screen bg-gray-100">
        <!-- Page Content -->
        <main>
            <div class="bg-gray-200 min-h-screen overflow-y-auto">
                <!-- Header superior -->
                <div class="w-full bg-[#1E1B35] flex justify-between items-center px-4 py-2">
                    <div class="flex items-center justify-center">
                        <img src="{{ asset('images/logo.png') }}" alt="BCN Soft" class="h-8">
                    </div>
                    <!-- Botón de iniciales -->
                    <div x-data="{ showInfo: false }" class="relative">
                        <div @click="showInfo = !showInfo"
                            class="bg-yellow-400 text-white font-bold rounded-full w-8 h-8 flex items-center justify-center text-sm cursor-pointer shadow">
                            {{ strtoupper(substr(session('vendedor_nombre'), 0, 1)) . strtoupper(substr(session('vendedor_nombre'), 1, 1)) }}
                        </div>

                        <!-- Popover lateral -->
                        <div x-show="showInfo" @click.away="showInfo = false"
                            x-transition
                            class="absolute right-0 mt-2 w-64 bg-[#1E1B35] rounded-lg shadow-xl border border-gray-300 z-50 text-sm text-white">
                            
                            <!-- Encabezado -->
                            <div class="flex justify-between items-center bg-bg-[#1E1B35] px-4 py-2 rounded-t">
                                <span class="font-semibold text-yellow-400">Datos del Vendedor</span>
                                <button @click="showInfo = false" class="text-gray-500 hover:text-gray-800 text-lg leading-none">
                                    &times;
                                </button>
                            </div>

                            <!-- Cuerpo -->
                            <div class="p-4 space-y-2">
                                <div>
                                    <span class="font-semibold">Vendedor:</span><br>
                                    {{ session('vendedor_nombre') }}<br>
                                    <span class="text-xs">Código: {{ session('vendedor_user_id') }}</span>
                                </div>
                                <hr>
                                <div>
                                    <span class="font-semibold">Cliente:</span><br>
                                    {{ session('cliente_nombre') }}<br>                        
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--div class="bg-yellow-400 text-white font-bold rounded-full w-8 h-8 flex items-center justify-center text-sm">
                        {{ strtoupper(substr(session('vendedor_nombre'), 0, 1)) . strtoupper(substr(session('vendedor_nombre'), 1, 1)) }}
                    </div-->
                </div>
                {{ $slot }}
            </div>
            <!-- Footer -->
            <div class="fixed bottom-0 left-0 w-full bg-white flex justify-around items-center py-4 text-sm text-gray-700" style="padding-bottom: calc(1rem + env(safe-area-inset-bottom)); box-shadow: 0 -2px 20px rgba(0, 0, 0, 0.08);">
                <a href="{{route('historial')}}" wire:navigate class="transition-colors duration-200">
                    <div class="flex flex-col items-center {{ request()->routeIs('historial') ? 'text-[#FFAF22]' : 'hover:text-[#FFAF22]' }}">
                        <i class="fa fa-history text-xl mb-1"></i>
                        <span class="text-xs font-medium">Historial</span>
                    </div>
                </a>
                <a href="{{route('listas-precios')}}" wire:navigate class="transition-colors duration-200">
                    <div class="flex flex-col items-center {{ request()->routeIs('listas-precios') ? 'text-[#FFAF22]' : 'hover:text-[#FFAF22]' }}">
                        <i class="fa fa-list text-xl mb-1"></i>
                        <span class="text-xs font-medium">Lista</span>
                    </div>
                </a>
                <a href="{{route('dashboard-vendedor')}}" wire:navigate class="transition-colors duration-200">
                    <div class="flex flex-col items-center justify-center w-14 h-14 rounded-full bg-[#1E1B35] text-white">
                        <i class="fa fa-home text-xl mb-1"></i>
                        <span class="text-xs font-medium">Inicio</span>
                    </div>
                </a>
                <a href="{{route('deudas')}}" wire:navigate class="transition-colors duration-200">
                    <div class="flex flex-col items-center {{ request()->routeIs('deudas') ? 'text-[#FFAF22]' : 'hover:text-[#FFAF22]' }}">
                        <i class="fa fa-dollar-sign text-xl mb-1"></i>
                        <span class="text-xs font-medium">A cobrar</span>
                    </div>
                </a>
                <button @click="mostrarConfirmacionSalir = true" class="transition-colors duration-200">
                    <div class="flex flex-col items-center hover:text-[#FFAF22]">
                        <i class="fa fa-sign-out-alt text-xl mb-1"></i>
                        <span class="text-xs font-medium">Salir</span>
                    </div>
                </button>
            </div>
        </main>
    </div>

    <!-- Livewire Scripts -->
    @livewireScripts
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Livewire.on('scrollToTop', () => {
                document.getElementById('main').scrollTo({ top: 0, behavior: 'smooth' });
                console.log('Scrolled to top');
            });
        });
    </script>    
    <!-- Additional Scripts -->
    @stack('scripts')    
</body>
</html>