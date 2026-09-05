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
                        @if($h->foto_url)
                            <img src="{{ $h->foto_url }}" alt="{{ $h->NOMBRE }}" class="max-w-full max-h-full object-contain">
                        @else
                            <i class="fas fa-image text-gray-300 text-2xl"></i>
                        @endif
                    </div>
                    <p class="text-[11px] leading-tight line-clamp-2 flex-1" style="color: {{ $colores['texto'] }}">{{ $h->NOMBRE }}</p>
                    <div class="flex items-center justify-between mt-1.5">
                        <span class="text-xs font-bold" style="color: {{ $colores['precio'] }}">${{ number_format($this->obtenerPrecio($h), 2, ',', '.') }}</span>
                        <button wire:click="{{ $modoCantidad === 'modal' ? 'abrirModalCantidad' : 'agregarAlCarrito' }}('{{ $h->CODIGO }}')"
                                class="w-7 h-7 text-white text-xs rounded-md flex items-center justify-center" style="background-color: {{ $colores['boton'] }}">
                            <i class="fas fa-cart-plus"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="flex items-center justify-between mb-4">
    <p class="text-sm" style="color: {{ $colores['texto'] }}80">
        {{ $totalProductos }} producto{{ $totalProductos != 1 ? 's' : '' }}
    </p>
</div>
