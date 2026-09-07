@if($avisoStock)
    <div class="mb-3 px-3 py-2 rounded-xl text-xs flex items-center gap-2" style="background-color: var(--twarn-soft); color: var(--twarn);">
        <i class="fas fa-box-open"></i> <span>{{ $avisoStock }}</span>
        <button type="button" wire:click="$set('avisoStock', '')" class="ml-auto opacity-70 hover:opacity-100"><i class="fas fa-times"></i></button>
    </div>
@endif
@if(count($productos) > 0)
    @php
        $cols = ['2' => 'md:grid-cols-2', '3' => 'md:grid-cols-3', '5' => 'md:grid-cols-5'][(string) $columnasDesktop] ?? 'md:grid-cols-4';
        $compacta = $estiloTarjeta === 'compacta';
        $detallada = $estiloTarjeta === 'detallada';
    @endphp
    <div class="grid grid-cols-2 {{ $cols }} gap-3 sm:gap-4 transition-opacity" wire:loading.class="opacity-60" wire:target="busqueda, toggleFiltro, quitarFiltro, limpiarFiltros, irAPagina, paginaSiguiente, paginaAnterior, toggleSoloPromos">
        @foreach($productos as $producto)
            @php
                $codigo = (string) $producto->CODIGO;
                $foto = $producto->foto_url ?: session('tienda_foto_default');
                $precio = $this->obtenerPrecio($producto);
                $ivaCard = $this->ivaIncluido($producto);
                $sinStock = !$vendeSinStock && $this->disponibleDe($codigo) <= 0;
                $promosCard = $this->promosDe($codigo);
                $esFav = in_array($codigo, $favoritos, true);
                $nombre = \App\Services\TiendaEstiloService::nombreBonito($producto->NOMBRE, $codigo);
                $accion = $modoCantidad === 'modal' ? 'abrirModalCantidad' : 'agregarAlCarrito';
            @endphp
            <div class="t-card t-card-hover overflow-hidden flex flex-col group relative" wire:key="prod-{{ $codigo }}">
                <a href="{{ $this->urlProducto($codigo) }}" wire:click.prevent="abrirModalCantidad('{{ $codigo }}')" class="block relative t-img aspect-square overflow-hidden">
                    @if($foto)
                        <img src="{{ $foto }}" alt="{{ $nombre }}" loading="lazy" class="w-full h-full object-contain {{ $compacta ? 'p-2' : 'p-3' }} group-hover:scale-105 transition-transform duration-300">
                    @else
                        <div class="w-full h-full flex items-center justify-center t-muted"><i class="fas fa-image text-3xl opacity-50"></i></div>
                    @endif
                    @if(count($promosCard))
                        <span class="t-badge-promo"><i class="fas fa-tag mr-1"></i>{{ $promosCard[0] }}</span>
                    @endif
                    @if($sinStock)
                        <div class="t-badge-stock"><span>Sin stock</span></div>
                    @endif
                </a>
                <button type="button" wire:click.stop="toggleFavorito('{{ $codigo }}')" class="t-heart absolute top-2 right-2 {{ $esFav ? 'is-on' : '' }}" title="{{ $esFav ? 'Quitar de favoritos' : 'Guardar en favoritos' }}" aria-label="Favorito">
                    <i class="{{ $esFav ? 'fas' : 'far' }} fa-heart text-sm"></i>
                </button>

                <div class="{{ $compacta ? 'p-2.5' : 'p-3' }} flex flex-col flex-1">
                    @if($detallada)
                        <p class="text-[10px] t-muted tracking-wide">#{{ $codigo }}</p>
                    @endif
                    <h3 class="{{ $compacta ? 'text-xs' : 'text-sm' }} font-semibold leading-snug line-clamp-2 t-text">
                        <a href="{{ $this->urlProducto($codigo) }}" wire:click.prevent="abrirModalCantidad('{{ $codigo }}')">{{ $nombre }}</a>
                    </h3>
                    @if(count($promosCard) > 1)
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach(array_slice($promosCard, 1) as $pc)
                                <span class="t-chip t-chip-ok"><i class="fas fa-tag"></i>{{ $pc }}</span>
                            @endforeach
                        </div>
                    @endif
                    @if($detallada && !$sinStock && !$vendeSinStock)
                        <p class="text-[11px] mt-1" style="color: var(--tok);"><i class="fas fa-circle text-[6px] mr-1"></i>Disponible: {{ \App\Services\TiendaStockService::formato($this->disponibleDe($codigo)) }}</p>
                    @endif

                    <div class="mt-auto pt-2">
                        <div class="flex items-end justify-between gap-2">
                            <div class="min-w-0">
                                <span class="t-precio {{ $compacta ? 'text-base' : 'text-lg' }} block leading-none">${{ number_format($precio, 2, ',', '.') }}</span>
                                @if($ivaCard > 0)
                                    <span class="block text-[10px] t-muted mt-1">IVA incl. ${{ number_format($ivaCard, 2, ',', '.') }}</span>
                                @endif
                            </div>
                            @if(!$esVitrina && $compacta)
                                <button wire:click="{{ $accion }}('{{ $codigo }}')" @disabled($sinStock) wire:loading.attr="disabled" wire:target="agregarAlCarrito('{{ $codigo }}')"
                                        class="t-btn t-btn-sm !rounded-full w-9 h-9 !p-0 flex-shrink-0" title="Agregar al carrito">
                                    <span wire:loading.remove wire:target="agregarAlCarrito('{{ $codigo }}')"><i class="fas fa-plus"></i></span>
                                    <span wire:loading wire:target="agregarAlCarrito('{{ $codigo }}')"><i class="fas fa-spinner fa-spin"></i></span>
                                </button>
                            @endif
                        </div>
                        @if(!$esVitrina && !$compacta)
                            <button wire:click="{{ $accion }}('{{ $codigo }}')" @disabled($sinStock) wire:loading.attr="disabled" wire:target="agregarAlCarrito('{{ $codigo }}')"
                                    class="t-btn t-btn-sm w-full mt-2.5">
                                <span wire:loading.remove wire:target="agregarAlCarrito('{{ $codigo }}')"><i class="fas fa-cart-plus"></i> {{ $sinStock ? 'Sin stock' : 'Agregar' }}</span>
                                <span wire:loading wire:target="agregarAlCarrito('{{ $codigo }}')"><i class="fas fa-spinner fa-spin"></i></span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Paginación --}}
    @if($totalPaginas > 1)
        <div class="flex items-center justify-center gap-1.5 mt-8">
            <button wire:click="paginaAnterior" @if($paginaActual <= 1) disabled @endif class="t-pill !px-3 disabled:opacity-40 disabled:cursor-not-allowed"><i class="fas fa-chevron-left text-xs"></i></button>
            @php $inicio = max(1, $paginaActual - 2); $fin = min($totalPaginas, $paginaActual + 2); @endphp
            @if($inicio > 1)<button wire:click="irAPagina(1)" class="t-pill !px-3">1</button>@if($inicio > 2)<span class="t-muted text-xs">…</span>@endif @endif
            @for($i = $inicio; $i <= $fin; $i++)
                <button wire:click="irAPagina({{ $i }})" class="t-pill !px-3 {{ $i == $paginaActual ? 'is-on' : '' }}">{{ $i }}</button>
            @endfor
            @if($fin < $totalPaginas)@if($fin < $totalPaginas - 1)<span class="t-muted text-xs">…</span>@endif<button wire:click="irAPagina({{ $totalPaginas }})" class="t-pill !px-3">{{ $totalPaginas }}</button>@endif
            <button wire:click="paginaSiguiente" @if($paginaActual >= $totalPaginas) disabled @endif class="t-pill !px-3 disabled:opacity-40 disabled:cursor-not-allowed"><i class="fas fa-chevron-right text-xs"></i></button>
        </div>
    @endif
@else
    <div class="t-card t-empty">
        @if($soloFavoritos)
            <div class="t-empty-ico"><i class="far fa-heart"></i></div>
            <p class="t-h text-lg font-bold t-text">Todavía no guardaste favoritos</p>
            <p class="text-sm t-soft mt-1 max-w-sm mx-auto">Tocá el corazón de un producto para tenerlo a mano acá.</p>
            <button wire:click="verTodos" class="t-btn mt-5"><i class="fas fa-th-large"></i> Ver el catálogo</button>
        @else
            <div class="t-empty-ico"><i class="fas fa-search"></i></div>
            <p class="t-h text-lg font-bold t-text">No encontramos productos</p>
            <p class="text-sm t-soft mt-1 max-w-sm mx-auto">{{ $busqueda ? 'Probá con otra palabra o menos específica.' : 'Probá cambiando los filtros.' }}</p>
            <div class="flex flex-wrap gap-2 justify-center mt-5">
                @if($busqueda || !empty($rubrosSeleccionados) || !empty($marcasSeleccionadas) || !empty($deptosSeleccionados) || $soloPromos)
                    <button wire:click="limpiarFiltros" class="t-btn"><i class="fas fa-eraser"></i> Limpiar la búsqueda</button>
                @endif
                @if($hayPromos && !$soloPromos)
                    <button wire:click="toggleSoloPromos" class="t-btn-outline"><i class="fas fa-tag"></i> Ver promociones</button>
                @endif
            </div>
        @endif
    </div>
@endif
