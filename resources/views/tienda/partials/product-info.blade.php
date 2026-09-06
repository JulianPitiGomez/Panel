{{-- Portada (F6): los bloques y su orden los define el comercio en Mi Tienda. Solo sin filtros. --}}
@foreach($portadaBloques as $bloque)
    @switch($bloque)
        @case('bienvenida')
            @if($portadaTitulo || $portadaTexto)
                <div class="mb-5 bg-white rounded-xl shadow-sm p-4 sm:p-5">
                    @if($portadaTitulo)<h2 class="text-lg sm:text-xl font-bold leading-tight" style="color: {{ $colores['texto'] }}">{{ $portadaTitulo }}</h2>@endif
                    @if($portadaTexto)<p class="text-sm text-gray-600 mt-1 whitespace-pre-line">{{ $portadaTexto }}</p>@endif
                </div>
            @endif
            @break

        @case('categorias')
            @if(!empty($categoriasPortada['items']))
                <div class="mb-5">
                    <p class="text-sm font-semibold mb-2" style="color: {{ $colores['texto'] }}"><i class="fas fa-th-large mr-1" style="color: {{ $colores['boton'] }}"></i> Categorías</p>
                    <div class="flex gap-2 overflow-x-auto pb-2 sm:flex-wrap">
                        @foreach($categoriasPortada['items'] as $cat)
                            <button wire:click="toggleFiltro('{{ $categoriasPortada['tipo'] }}', '{{ $cat['cod'] }}')"
                                    class="flex-shrink-0 px-3 py-2 bg-white rounded-full shadow-sm text-xs font-medium border border-gray-100 hover:shadow transition" style="color: {{ $colores['texto'] }}">
                                {{ $cat['nom'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
            @break

        @case('promos')
            @if(count($promosPortada))
                <div class="mb-5">
                    <p class="text-sm font-semibold mb-2" style="color: {{ $colores['texto'] }}"><i class="fas fa-tag mr-1" style="color: {{ $colores['boton'] }}"></i> Promociones</p>
                    <div class="flex gap-3 overflow-x-auto pb-2">
                        @foreach($promosPortada as $h)
                            @include('tienda.partials.tarjeta-mini', ['h' => $h, 'ancho' => 'w-40'])
                        @endforeach
                    </div>
                    @if($hayPromos)
                        <button wire:click="toggleSoloPromos" class="text-xs font-semibold hover:underline" style="color: {{ $colores['boton'] }}">Ver todas las promociones <i class="fas fa-arrow-right ml-1"></i></button>
                    @endif
                </div>
            @endif
            @break

        @case('destacados')
            @if(count($destacados))
                <div class="mb-5">
                    <p class="text-sm font-semibold mb-2" style="color: {{ $colores['texto'] }}"><i class="fas fa-star mr-1" style="color: {{ $colores['boton'] }}"></i> Destacados</p>
                    <div class="flex gap-3 overflow-x-auto pb-2">
                        @foreach($destacados as $h)
                            @include('tienda.partials.tarjeta-mini', ['h' => $h, 'ancho' => 'w-40'])
                        @endforeach
                    </div>
                </div>
            @endif
            @break

        @case('habituales')
            @if(count($habituales))
                <div class="mb-5">
                    <p class="text-sm font-semibold mb-2" style="color: {{ $colores['texto'] }}"><i class="fas fa-history mr-1" style="color: {{ $colores['boton'] }}"></i> Lo que comprás habitualmente</p>
                    <div class="flex gap-3 overflow-x-auto pb-2">
                        @foreach($habituales as $h)
                            @include('tienda.partials.tarjeta-mini', ['h' => $h, 'ancho' => 'w-36'])
                        @endforeach
                    </div>
                </div>
            @endif
            @break
    @endswitch
@endforeach

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
