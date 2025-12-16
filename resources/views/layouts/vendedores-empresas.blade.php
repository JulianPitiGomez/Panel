<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Panel Vendedores Empresas</title>
    <link href="{{asset('images/logo.ico')}}" rel="shortcut icon" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:600,700,800&display=swap" rel="stylesheet" />

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Tailwind CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Livewire Styles -->
    @livewireStyles
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        <!-- Page Content -->
        <main>
            <div class="bg-gray-200 min-h-screen overflow-y-auto">
                <!-- Header superior -->
                <div class="w-full bg-[#1E1B35] flex justify-between items-center px-4 py-2">
                    <div class="flex items-center justify-center space-x-2">
                        <i class="fa fa-shopping-bag text-yellow-400 text-xl"></i>
                        <span class="font-bold text-yellow-400 text-xl mr-0">bcn</span>
                        <span class="font-bold text-white text-xl ml-0">soft</span>
                    </div>
                    <!-- Botón de iniciales -->
                    <div x-data="{ showInfo: false }" class="relative">
                        <div @click="showInfo = !showInfo"
                            class="bg-yellow-400 text-white font-bold rounded-full w-8 h-8 flex items-center justify-center text-sm cursor-pointer shadow">
                            {{ strtoupper(substr(session('vendedor_empresa_nombre'), 0, 1)) . strtoupper(substr(session('vendedor_empresa_nombre'), 1, 1)) }}
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
                                    {{ session('vendedor_empresa_nombre') }}<br>
                                    <span class="text-xs">Código: {{ session('vendedor_empresa_user_id') }}</span>
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
                        {{ strtoupper(substr(session('vendedor_empresa_nombre'), 0, 1)) . strtoupper(substr(session('vendedor_empresa_nombre'), 1, 1)) }}
                    </div-->
                </div>
                {{ $slot }}
            </div>
            <!-- Footer -->
            <div class="fixed bottom-0 left-0 w-full bg-white border-t shadow-inner flex justify-around py-2 text-xs text-gray-600">
                <a href="{{route('dashboard-vendedor-empresa')}}" wire:navigate>
                    <div class="flex flex-col items-center {{ request()->routeIs('dashboard-vendedor-empresa') ? 'text-yellow-500' : '' }}">
                        <i class="fa fa-home text-lg"></i>
                        <span class="text-[10px]">Inicio</span>
                    </div>
                </a>
                <a href="{{route('historial-empresa')}}" wire:navigate>
                    <div class="flex flex-col items-center {{ request()->routeIs('historial-empresa') ? 'text-yellow-500' : '' }}">
                        <i class="fa fa-history text-lg"></i>
                        <span class="text-[10px]">Historial</span>
                    </div>
                </a>
                <a href="{{route('listas-precios-empresa')}}" wire:navigate>
                    <div class="flex flex-col items-center {{ request()->routeIs('listas-precios-empresa') ? 'text-yellow-500' : '' }}">
                        <i class="fa fa-list text-lg"></i>
                        <span class="text-[10px]">Lista</span>
                    </div>
                </a>
                <a href="{{route('deudas-empresa')}}" wire:navigate>
                    <div class="flex flex-col items-center {{ request()->routeIs('deudas-empresa') ? 'text-yellow-500' : '' }}">
                        <i class="fa fa-dollar-sign text-lg"></i>
                        <span class="text-[10px]">A cobrar</span>
                    </div>
                </a>
                <a href="{{route('logout.vendedores-empresas')}}" wire:navigate>
                    <div class="flex flex-col items-center">
                        <i class="fa fa-sign-out-alt text-lg"></i>
                        <span class="text-[10px]">Salir</span>
                    </div>
                </a>
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
