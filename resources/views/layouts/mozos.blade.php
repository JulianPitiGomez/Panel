<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Panel Mozos - {{ session('cliente_nombre') }}</title>
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
<body class="font-sans antialiased bg-gray-100 h-screen overflow-hidden">
    <div class="h-screen flex flex-col">
        <!-- Header compacto -->
        <div class="bg-[#1E1B35] shadow-sm flex-shrink-0">
            <div class="px-4 py-2">
                <div class="flex justify-between items-center">
                    <!-- Logo y nombre cliente -->
                    <div class="flex items-center space-x-2">
                        <img src="{{ asset('images/logo1.png') }}" alt="Logo" class="h-10">
                        <div>
                            <h1 class="text-sm font-bold text-white">{{ session('cliente_nombre') }}</h1>
                            <p class="text-xs text-gray-300">{{ session('mozo_nombre') }}</p>
                        </div>
                    </div>

                    <!-- Info mozo -->
                    <div x-data="{ showInfo: false }" class="relative">
                        <div @click="showInfo = !showInfo"
                            class="bg-yellow-400 text-white font-bold rounded-full w-10 h-10 flex items-center justify-center text-sm cursor-pointer shadow-lg hover:bg-yellow-500 transition">
                            {{ strtoupper(substr(session('mozo_nombre'), 0, 1)) . strtoupper(substr(session('mozo_nombre'), 1, 1)) }}
                        </div>

                        <!-- Popover -->
                        <div x-show="showInfo" @click.away="showInfo = false"
                            x-transition
                            class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-300 z-50 text-sm">

                            <div class="flex justify-between items-center bg-[#1E1B35] px-4 py-2 rounded-t-lg">
                                <span class="font-semibold text-yellow-400">Datos del Mozo</span>
                                <button @click="showInfo = false" class="text-gray-300 hover:text-white text-lg leading-none">
                                    &times;
                                </button>
                            </div>

                            <div class="p-4 space-y-2 text-gray-700">
                                <div>
                                    <span class="font-semibold">Mozo:</span><br>
                                    {{ session('mozo_nombre') }}<br>
                                    <span class="text-xs">Usuario: {{ session('mozo_user') }}</span>
                                </div>
                                <hr>
                                <div>
                                    <span class="font-semibold">Permisos:</span><br>
                                    <div class="text-xs space-y-1 mt-1">
                                        <div class="flex items-center">
                                            <i class="fa fa-{{ session('mozo_comanda') ? 'check text-green-500' : 'times text-red-500' }} mr-2"></i>
                                            Comanda
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fa fa-{{ session('mozo_precuenta') ? 'check text-green-500' : 'times text-red-500' }} mr-2"></i>
                                            Precuenta
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fa fa-{{ session('mozo_borracc') ? 'check text-green-500' : 'times text-red-500' }} mr-2"></i>
                                            Borrar con comanda
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fa fa-{{ session('mozo_borrasc') ? 'check text-green-500' : 'times text-red-500' }} mr-2"></i>
                                            Borrar sin comanda
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <main class="flex-1 overflow-hidden">
            {{ $slot }}
        </main>
    </div>

    <!-- Livewire Scripts -->
    @livewireScripts

    <!-- Additional Scripts -->
    @stack('scripts')
</body>
</html>
