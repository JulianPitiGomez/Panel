{{-- Tarjeta chica para las tiras de la portada: $h = articulo, $ancho = clase de ancho --}}
<div class="flex-shrink-0 {{ $ancho ?? 'w-40' }} bg-white rounded-xl shadow-sm p-2 flex flex-col">
    <a href="{{ $this->urlProducto($h->CODIGO) }}" wire:click.prevent="abrirModalCantidad('{{ $h->CODIGO }}')" class="aspect-square bg-gray-50 rounded-lg flex items-center justify-center overflow-hidden mb-1.5">
        @if($h->foto_url ?: session('tienda_foto_default'))
            <img src="{{ $h->foto_url ?: session('tienda_foto_default') }}" alt="{{ $h->NOMBRE }}" class="max-w-full max-h-full object-contain" loading="lazy">
        @else
            <i class="fas fa-image text-gray-300 text-2xl"></i>
        @endif
    </a>
    <p class="text-[11px] leading-tight line-clamp-2 flex-1" style="color: {{ $colores['texto'] }}">
        <a href="{{ $this->urlProducto($h->CODIGO) }}" wire:click.prevent="abrirModalCantidad('{{ $h->CODIGO }}')">{{ $h->NOMBRE }}</a>
    </p>
    @foreach($this->promosDe($h->CODIGO) as $pc)
        <span class="inline-block text-[9px] font-semibold px-1.5 py-0.5 rounded-full bg-green-100 text-green-700 mt-1 self-start"><i class="fas fa-tag mr-0.5"></i>{{ $pc }}</span>
    @endforeach
    <div class="flex items-center justify-between mt-1.5">
        <span class="text-xs font-bold" style="color: {{ $colores['precio'] }}">${{ number_format($this->obtenerPrecio($h), 2, ',', '.') }}</span>
        @if(!$esVitrina)
            <button wire:click="{{ $modoCantidad === 'modal' ? 'abrirModalCantidad' : 'agregarAlCarrito' }}('{{ $h->CODIGO }}')"
                    class="w-7 h-7 text-white text-xs rounded-md flex items-center justify-center" style="background-color: {{ $colores['boton'] }}">
                <i class="fas fa-cart-plus"></i>
            </button>
        @endif
    </div>
</div>
