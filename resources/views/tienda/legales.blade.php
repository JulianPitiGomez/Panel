@php
    $razon = $datos['razon_social'] ?? '';
    $cuit = $datos['cuit'] ?? '';
    $terminos = trim((string) ($datos['tienda_terminos'] ?? ''));
    $privacidad = trim((string) ($datos['tienda_privacidad'] ?? ''));
@endphp
<div class="max-w-3xl mx-auto py-8 space-y-6">
    <div class="bg-white rounded-2xl shadow-sm p-6 sm:p-8">
        <h1 class="text-2xl font-bold mb-4" style="color: {{ $colores['texto'] }}">Información legal</h1>

        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-2">Datos del vendedor</h2>
        <div class="text-sm text-gray-700 space-y-1 mb-6">
            <p><span class="text-gray-400">Nombre comercial:</span> {{ $nombreTienda }}</p>
            @if($razon)<p><span class="text-gray-400">Razón social:</span> {{ $razon }}</p>@endif
            @if($cuit)<p><span class="text-gray-400">CUIT:</span> {{ $cuit }}</p>@endif
            @if(!empty($datos['direccion']))<p><span class="text-gray-400">Domicilio:</span> {{ $datos['direccion'] }}{{ !empty($datos['localidad']) ? ', ' . $datos['localidad'] : '' }}</p>@endif
            @if(!empty($datos['telefono']))<p><span class="text-gray-400">Teléfono:</span> {{ $datos['telefono'] }}</p>@endif
            @if(!empty($datos['email_comercio']))<p><span class="text-gray-400">Contacto:</span> {{ $datos['email_comercio'] }}</p>@endif
        </div>

        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-2">Términos y condiciones</h2>
        <div class="text-sm text-gray-700 whitespace-pre-line mb-6">{{ $terminos ?: "Los precios publicados incluyen IVA y pueden cambiar sin aviso hasta la confirmación del pedido. El pedido se confirma cuando el comercio lo acepta; la factura se emite al momento de la entrega o el retiro. Los envíos se realizan según la modalidad y el plazo elegidos al comprar. Ante cualquier consulta, comunicate por los medios de contacto indicados arriba." }}</div>

        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-2">Política de privacidad</h2>
        <div class="text-sm text-gray-700 whitespace-pre-line mb-6">{{ $privacidad ?: "Los datos que cargás (nombre, mail, teléfono y dirección) se usan únicamente para gestionar tus pedidos y comunicarnos con vos. No se venden ni se comparten con terceros, salvo los necesarios para la entrega (transportista) y el cobro (procesador de pagos). Podés pedir la corrección o eliminación de tus datos escribiendo al contacto del comercio. Ley 25.326 de Protección de los Datos Personales." }}</div>

        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-2">Derecho de arrepentimiento</h2>
        <p class="text-sm text-gray-700 mb-4">Si comprás a distancia, podés revocar la compra dentro de los 10 días corridos desde que recibís el producto, sin costo ni justificación (art. 34, Ley 24.240). Para hacerlo usá el botón de arrepentimiento.</p>
        <a href="{{ route('tienda.arrepentimiento', $tienda) }}" class="inline-block px-5 py-2.5 rounded-lg text-white font-semibold" style="background-color: {{ $colores['boton'] }}">
            <i class="fas fa-undo mr-1"></i> Botón de arrepentimiento
        </a>
    </div>
</div>
