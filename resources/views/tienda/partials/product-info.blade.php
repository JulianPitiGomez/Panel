{{-- Portada (F6): los bloques y su orden los define el comercio en Mi Tienda. Solo sin filtros. --}}
@foreach($portadaBloques as $bloque)
    @switch($bloque)
        @case('bienvenida')
            @if($portadaTitulo || $portadaTexto)
                <div class="t-card mb-6 p-5 sm:p-6 border-l-4" style="border-left-color: var(--tp);">
                    @if($portadaTitulo)<h2 class="t-h text-xl sm:text-2xl font-bold leading-tight t-text">{{ $portadaTitulo }}</h2>@endif
                    @if($portadaTexto)<p class="text-sm sm:text-[15px] t-soft mt-1.5 whitespace-pre-line leading-relaxed">{{ $portadaTexto }}</p>@endif
                </div>
            @endif
            @break

        @case('categorias')
            @if(!empty($categoriasPortada['items']))
                <div class="mb-6">
                    <div class="t-section"><span class="t-section-ico"><i class="fas fa-th-large"></i></span><h2>Categorías</h2></div>
                    <div class="t-strip sm:flex-wrap">
                        @foreach($categoriasPortada['items'] as $cat)
                            <button wire:click="toggleFiltro('{{ $categoriasPortada['tipo'] }}', '{{ $cat['cod'] }}')" class="t-pill">{{ \App\Services\TiendaEstiloService::nombreBonito($cat['nom']) }}</button>
                        @endforeach
                    </div>
                </div>
            @endif
            @break

        @case('promos')
            @if(count($promosPortada))
                <div class="mb-6 relative" x-data>
                    <div class="t-section">
                        <span class="t-section-ico"><i class="fas fa-tag"></i></span><h2>Promociones</h2>
                        @if($hayPromos)<button wire:click="toggleSoloPromos" class="t-section-link t-link">Ver todas <i class="fas fa-arrow-right text-xs ml-0.5"></i></button>@endif
                    </div>
                    <button type="button" class="t-strip-arrow -left-3" @click="$refs.tiraPromos.scrollBy({left: -320, behavior: 'smooth'})"><i class="fas fa-chevron-left text-xs"></i></button>
                    <button type="button" class="t-strip-arrow -right-3" @click="$refs.tiraPromos.scrollBy({left: 320, behavior: 'smooth'})"><i class="fas fa-chevron-right text-xs"></i></button>
                    <div class="t-strip" x-ref="tiraPromos">
                        @foreach($promosPortada as $h)
                            @include('tienda.partials.tarjeta-mini', ['h' => $h, 'ancho' => 'w-40 sm:w-44', 'bloque' => 'promo'])
                        @endforeach
                    </div>
                </div>
            @endif
            @break

        @case('destacados')
            @if(count($destacados))
                <div class="mb-6 relative" x-data>
                    <div class="t-section"><span class="t-section-ico"><i class="fas fa-star"></i></span><h2>Destacados</h2></div>
                    <button type="button" class="t-strip-arrow -left-3" @click="$refs.tiraDest.scrollBy({left: -320, behavior: 'smooth'})"><i class="fas fa-chevron-left text-xs"></i></button>
                    <button type="button" class="t-strip-arrow -right-3" @click="$refs.tiraDest.scrollBy({left: 320, behavior: 'smooth'})"><i class="fas fa-chevron-right text-xs"></i></button>
                    <div class="t-strip" x-ref="tiraDest">
                        @foreach($destacados as $h)
                            @include('tienda.partials.tarjeta-mini', ['h' => $h, 'ancho' => 'w-40 sm:w-44', 'bloque' => 'dest'])
                        @endforeach
                    </div>
                </div>
            @endif
            @break

        @case('habituales')
            @if(count($habituales))
                <div class="mb-6 relative" x-data>
                    <div class="t-section"><span class="t-section-ico"><i class="fas fa-history"></i></span><h2>Lo que comprás habitualmente</h2></div>
                    <button type="button" class="t-strip-arrow -left-3" @click="$refs.tiraHab.scrollBy({left: -320, behavior: 'smooth'})"><i class="fas fa-chevron-left text-xs"></i></button>
                    <button type="button" class="t-strip-arrow -right-3" @click="$refs.tiraHab.scrollBy({left: 320, behavior: 'smooth'})"><i class="fas fa-chevron-right text-xs"></i></button>
                    <div class="t-strip" x-ref="tiraHab">
                        @foreach($habituales as $h)
                            @include('tienda.partials.tarjeta-mini', ['h' => $h, 'ancho' => 'w-36 sm:w-40', 'bloque' => 'hab'])
                        @endforeach
                    </div>
                </div>
            @endif
            @break
    @endswitch
@endforeach

<div class="flex items-center justify-between mb-3 gap-2">
    <p class="text-sm t-soft">
        @if($soloFavoritos)
            <i class="fas fa-heart mr-1" style="color: #ef4444;"></i><span class="font-semibold t-text">Tus favoritos</span> · {{ $totalProductos }} producto{{ $totalProductos != 1 ? 's' : '' }}
        @else
            {{ $totalProductos }} producto{{ $totalProductos != 1 ? 's' : '' }}{{ $soloPromos ? ' en promoción' : '' }}
        @endif
    </p>
    <div class="flex items-center gap-2">
        @if($soloFavoritos)
            <button wire:click="verTodos" class="t-pill is-on"><i class="fas fa-times text-xs"></i> Ver todo</button>
        @elseif($hayPromos)
            <button wire:click="toggleSoloPromos" class="t-pill {{ $soloPromos ? 'is-on' : '' }}"><i class="fas fa-tag text-xs"></i> {{ $soloPromos ? 'Ver todo' : 'Promociones' }}</button>
        @endif
    </div>
</div>
