{{-- Tarjeta chica para las tiras de la portada: $h = articulo, $ancho = clase de ancho --}}
@php
    $codigoMini = (string) $h->CODIGO;
    $fotoMini = $h->foto_url ?: session('tienda_foto_default');
    $promosMini = $this->promosDe($codigoMini);
    $favMini = in_array($codigoMini, $favoritos, true);
    $nombreMini = \App\Services\TiendaEstiloService::nombreBonito($h->NOMBRE, $codigoMini);
@endphp
<div class="flex-shrink-0 {{ $ancho ?? 'w-40' }} t-card t-card-hover overflow-hidden flex flex-col relative group" wire:key="mini-{{ $bloque ?? 'x' }}-{{ $codigoMini }}">
    <a href="{{ $this->urlProducto($codigoMini) }}" wire:click.prevent="abrirModalCantidad('{{ $codigoMini }}')" class="block relative t-img aspect-square overflow-hidden">
        @if($fotoMini)
            <img src="{{ $fotoMini }}" alt="{{ $nombreMini }}" loading="lazy" class="w-full h-full object-contain p-2 group-hover:scale-105 transition-transform duration-300">
        @else
            <div class="w-full h-full flex items-center justify-center t-muted"><i class="fas fa-image text-2xl opacity-50"></i></div>
        @endif
        @if(count($promosMini))
            <span class="t-badge-promo"><i class="fas fa-tag mr-1"></i>{{ $promosMini[0] }}</span>
        @endif
    </a>
    <button type="button" wire:click.stop="toggleFavorito('{{ $codigoMini }}')" class="t-heart absolute top-1.5 right-1.5 !w-7 !h-7 {{ $favMini ? 'is-on' : '' }}" aria-label="Favorito">
        <i class="{{ $favMini ? 'fas' : 'far' }} fa-heart text-xs"></i>
    </button>
    <div class="p-2.5 flex flex-col flex-1">
        <p class="text-xs font-semibold leading-snug line-clamp-2 t-text">
            <a href="{{ $this->urlProducto($codigoMini) }}" wire:click.prevent="abrirModalCantidad('{{ $codigoMini }}')">{{ $nombreMini }}</a>
        </p>
        <div class="flex items-center justify-between gap-2 mt-auto pt-2">
            <span class="t-precio text-sm">${{ number_format($this->obtenerPrecio($h), 2, ',', '.') }}</span>
            @if(!$esVitrina)
                <button wire:click="{{ $modoCantidad === 'modal' ? 'abrirModalCantidad' : 'agregarAlCarrito' }}('{{ $codigoMini }}')" class="t-btn !rounded-full w-8 h-8 !p-0 flex-shrink-0" title="Agregar al carrito">
                    <i class="fas fa-plus text-xs"></i>
                </button>
            @endif
        </div>
    </div>
</div>
