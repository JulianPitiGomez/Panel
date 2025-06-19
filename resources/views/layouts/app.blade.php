<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Panel') }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&family=Source+Sans+Pro:wght@200;300;400;600;700;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        .font-primary { font-family: 'Montserrat', sans-serif; }
        .font-secondary { font-family: 'Source Sans Pro', sans-serif; }
        .btn-primary {
            background-color: #FFAF22;
            color: white;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            transform: translateY(0);
            box-shadow: 0 4px 6px rgba(255, 175, 34, 0.2);
        }
        .btn-primary:hover {
            background-color: #E69A1E;
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(255, 175, 34, 0.3);
        }
        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(255, 175, 34, 0.2);
        }
    </style>
</head>
<body class="font-secondary antialiased bg-gray-50">
    <div class="min-h-screen">
        @yield('content')        
        @if (isset($slot))
            {{ $slot }}
        @endif
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>