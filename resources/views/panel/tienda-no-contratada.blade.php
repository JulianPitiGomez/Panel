{{-- Se muestra en lugar de la pantalla Catálogo cuando el comercio no tiene contratada la tienda web --}}
@php
    $whatsapp = 'https://api.whatsapp.com/send/?phone=' . config('tienda.contacto_whatsapp')
        . '&text=' . rawurlencode('¡Buen día! Quiero contratar la tienda web para ' . session('cliente_nombre', 'mi comercio'))
        . '&type=phone_number&app_absent=0';
@endphp

<div class="space-y-6 font-secondary">

    <div>
        <h2 class="text-2xl font-primary font-semibold text-gray-800 flex items-center gap-2">
            <i class="fas fa-store text-[#FFAF22]"></i> Tu tienda web
        </h2>
        <p class="text-sm text-gray-500">Vendé online con el mismo catálogo que ya cargás en tu sistema.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-[#222036] px-6 py-8 sm:px-10 text-white">
            <p class="text-xs uppercase tracking-widest text-[#FFAF22] font-semibold mb-2">Servicio adicional</p>
            <h3 class="text-2xl sm:text-3xl font-primary font-semibold leading-tight">
                Tu catálogo, abierto las 24 horas
            </h3>
            <p class="mt-3 text-white/80 max-w-2xl">
                La tienda web toma los artículos, precios, promociones y stock que ya tenés en gestión y los publica
                en una página propia de tu comercio. Los pedidos entran a tu sistema como cualquier otro pedido.
            </p>
        </div>

        <div class="px-6 py-8 sm:px-10 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="flex gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#FFAF22]/15 text-[#E0930A] flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-box-open"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Sin cargar nada dos veces</p>
                    <p class="text-sm text-gray-500">Artículos, listas de precios y promociones salen de tu gestión.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#FFAF22]/15 text-[#E0930A] flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-truck"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Envíos y retiro en el local</p>
                    <p class="text-sm text-gray-500">Zonas de reparto propias, correo o retiro, como vos trabajes.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#FFAF22]/15 text-[#E0930A] flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Cobrá como quieras</p>
                    <p class="text-sm text-gray-500">Mercado Pago, transferencia o cuenta corriente para tus clientes.</p>
                </div>
            </div>
        </div>

        <div class="px-6 pb-8 sm:px-10 flex flex-col sm:flex-row sm:items-center gap-4">
            <a href="{{ $whatsapp }}" target="_blank" rel="noopener"
               class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-[#FFAF22] text-white rounded-xl font-semibold hover:bg-[#E89B0A] transition">
                <i class="fab fa-whatsapp text-lg"></i> Quiero contratar la tienda
            </a>
            <p class="text-sm text-gray-500">Te contamos cómo funciona y la dejamos lista con vos.</p>
        </div>
    </div>

</div>
