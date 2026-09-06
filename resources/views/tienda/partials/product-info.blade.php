{{-- Destacados: los elige el comercio (sin filtros) --}}
@if(count($destacados))
    <div class="mb-5">
        <p class="text-sm font-semibold mb-2" style="color: {{ $colores['texto'] }}">
            <i class="fas fa-star mr-1" style="color: {{ $colores['boton'] }}"></i> Destacados
        </p>
        <div class="flex gap-3 overflow-x-auto pb-2">
            @foreach($destacados as $h)
                <div class="flex-shrink-0 w-40 bg-white rounded-xl shadow-sm p-2 flex flex-col">
                    <div class="aspect-square bg-gray-50 rounded-lg flex items-center justify-center overflow-hidden mb-1.5">
                        @if($h->foto_url ?: session('tienda_foto_default'))
                            <img src="{{ $h->foto_url ?: session('tienda_foto_default') }}" alt="{{ $h->NOMBRE }}" class="max-w-full max-h-full object-contain">
                        @else
                            <i class="fas fa-image text-gray-300 text-2xl"></i>
                        @endif
                    </div>
                    <p class="text-[11px] leading-tight line-clamp-2 flex-1" style="color: {{ $colores['texto'] }}">{{ $h->NOMBRE }}</p>
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
            @endforeach
        </div>
    </div>
@endif

{{-- Lo que comprás habitualmente (solo comprador logueado, sin filtros) --}}
@if(count($habituales))
    <div class="mb-5">
        <p class="text-sm font-semibold mb-2" style="color: {{ $colores['texto'] }}">
            <i class="fas fa-history mr-1" style="color: {{ $colores['boton'] }}"></i> Lo que comprás habitualmente
        </p>
        <div class="flex gap-3 overflow-x-auto pb-2">
            @foreach($habituales as $h)
                <div class="flex-shrink-0 w-36 bg-white rounded-xl shadow-sm p-2 flex flex-col">
                    <div class="aspect-square bg-gray-50 rounded-lg flex items-center justify-center overflow-hidden mb-1.5">
                        @if($h->foto_url ?: session('tienda_foto_default'))
                            <img src="{{ $h->foto_url ?: session('tienda_foto_default') }}" alt="{{ $h->NOMBRE }}" class="max-w-full max-h-full object-contain">
                        @else
                            <i class="fas fa-image text-gray-300 text-2xl"></i>
                        @endif
                    </div>
                    <p class="text-[11px] leading-tight line-clamp-2 flex-1" style="color: {{ $colores['texto'] }}">{{ $h->NOMBRE }}</p>
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
            @endforeach
        </div>
    </div>
@endif

<div class="flex items-center justify-between mb-4">
    <p class="text-sm" style="color: {{ $colores['texto'] }}80">
        {{ $totalProductos }} producto{{ $totalProductos != 1 ? 's' : '' }}{{ $soloPromos ? ' en promoción' : '' }}
    </p>
    @if($hayPromos)
        <button wire:click="toggleSoloPromos"
                class="text-xs font-semibold px-3 py-1.5 rounded-full border transition {{ $soloPromos ? 'text-white border-transparent' : 'bg-green-50 text-green-700 border-green-200' }}"
                style="{{ $soloPromos ? 'background-color: ' . $colores['precio'] : '' }}">
            <i class="fas fa-tag mr-1"></i> {{ $soloPromos ? 'Ver todo' : 'Promociones' }}
        </button>
    @endif
</div>
