@php
    use App\Services\TiendaPagoService as P;
    $aprobado = $sat->pago_estado === P::ESTADO_APROBADO;
    $rechazado = $sat->pago_estado === P::ESTADO_RECHAZADO;
    $pendiente = $sat->pago_estado === P::ESTADO_PENDIENTE;
@endphp
<div class="max-w-lg mx-auto py-10" id="pago-pedido" data-poll="{{ $sat->pago_medio === P::MEDIO_MERCADOPAGO && $pendiente ? route('mp.status-tienda', ['clientId' => $clienteId, 'pedido' => $pedidoId]) : '' }}">
    <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
        <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-5 {{ $aprobado ? 'bg-green-100 text-green-500' : ($rechazado ? 'bg-red-100 text-red-500' : 'bg-amber-100 text-amber-500') }}">
            <i class="fas {{ $aprobado ? 'fa-check' : ($rechazado ? 'fa-times' : 'fa-clock') }} text-4xl"></i>
        </div>

        <h2 class="text-2xl font-bold mb-1" style="color: {{ $colores['texto'] }}">
            @if($aprobado) Pago recibido
            @elseif($rechazado) El pago no se acreditó
            @elseif($pendiente) Pago pendiente
            @else Pedido registrado
            @endif
        </h2>
        <p class="text-gray-500 mb-6">Pedido #{{ $pedidoId }} · {{ P::etiquetaMedio($sat->pago_medio) }} · ${{ number_format($total, 2, ',', '.') }}</p>

        @if($sat->pago_medio === P::MEDIO_MERCADOPAGO)
            @if($pendiente)
                <p class="text-sm text-gray-500 mb-4"><i class="fas fa-spinner fa-spin mr-1"></i> Estamos esperando la confirmación de Mercado Pago. Esta página se actualiza sola.</p>
                <a href="{{ route('tienda.pago', ['tienda' => $tienda, 'pedido' => $pedidoId, 'reintentar' => 1]) }}"
                   class="inline-block px-5 py-2.5 text-white font-semibold rounded-lg" style="background-color: {{ $colores['boton'] }}">
                    <i class="fas fa-credit-card mr-1"></i> Pagar con Mercado Pago
                </a>
            @elseif($rechazado)
                <p class="text-sm text-gray-500 mb-4">Podés intentarlo de nuevo con otro medio dentro de Mercado Pago, o consultar al comercio.</p>
                <a href="{{ route('tienda.pago', ['tienda' => $tienda, 'pedido' => $pedidoId, 'reintentar' => 1]) }}"
                   class="inline-block px-5 py-2.5 text-white font-semibold rounded-lg" style="background-color: {{ $colores['boton'] }}">
                    <i class="fas fa-redo mr-1"></i> Reintentar el pago
                </a>
            @endif
        @elseif($sat->pago_medio === P::MEDIO_TRANSFERENCIA && !$aprobado)
            @include('tienda.partials.datos-transferencia', ['transferencia' => $transferencia, 'importe' => $sat->pago_importe_unico, 'colores' => $colores])
        @endif

        <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('tienda.mis-pedidos', $tienda) }}" class="px-6 py-3 text-white font-semibold rounded-lg" style="background-color: {{ $colores['precio'] }}">
                <i class="fas fa-clipboard-list mr-2"></i> Ver mis pedidos
            </a>
            <a href="{{ route('tienda.catalogo', $tienda) }}" class="px-6 py-3 text-white font-semibold rounded-lg" style="background-color: {{ $colores['boton'] }}">
                <i class="fas fa-arrow-left mr-2"></i> Volver a la tienda
            </a>
        </div>
    </div>
</div>

<script>
(() => {
    const el = document.getElementById('pago-pedido');
    const url = el && el.dataset.poll;
    if (!url) return;
    let intentos = 0;
    const t = setInterval(async () => {
        intentos++;
        try {
            const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const j = await r.json();
            if (j.status && j.status !== 'pendiente') { clearInterval(t); window.location.reload(); }
        } catch (e) {}
        if (intentos > 60) clearInterval(t);   // 5 minutos
    }, 5000);
})();
</script>
