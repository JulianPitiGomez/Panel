<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Tienda no disponible</title>
    <link href="{{ asset('images/logo.ico') }}" rel="shortcut icon" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center px-4" style="font-family: 'Poppins', sans-serif;">
    <div class="max-w-md w-full text-center">
        <div class="mx-auto w-20 h-20 rounded-2xl bg-[#1E1B35] text-[#FFAF22] flex items-center justify-center text-3xl mb-6">
            <i class="fas fa-store-slash"></i>
        </div>
        <h1 class="text-2xl font-semibold text-gray-800">Tienda no disponible</h1>
        <p class="mt-3 text-gray-500">
            @if(!empty($nombreTienda))
                La tienda de <span class="font-medium text-gray-700">{{ $nombreTienda }}</span> no está activa en este momento.
            @else
                Esta tienda no existe o no está activa en este momento.
            @endif
        </p>
        <p class="mt-1 text-sm text-gray-400">Si llegaste por un enlace del comercio, consultale directamente cómo hacer tu pedido.</p>
    </div>
</body>
</html>
