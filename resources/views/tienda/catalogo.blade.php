<div class="w-full max-w-full overflow-x-hidden">
    <div wire:loading.delay class="t-progress" wire:target="busqueda, toggleFiltro, quitarFiltro, limpiarFiltros, irAPagina, paginaSiguiente, paginaAnterior, toggleSoloPromos, verTodos"></div>

    @if($mensajeCerrada)
        <div class="mb-4 px-4 py-3 rounded-xl text-sm flex items-center gap-2" style="background-color: var(--twarn-soft); color: var(--twarn);">
            <i class="fas fa-door-closed"></i> {{ $mensajeCerrada }}
        </div>
    @endif

    @php
        $hayFiltros = $filtrosVisibles['depto'] || $filtrosVisibles['rubro'] || $filtrosVisibles['marca'];
        $totalFiltros = count($deptosSeleccionados) + count($rubrosSeleccionados) + count($marcasSeleccionadas);
        $deptoNombres = collect($deptos)->pluck('nom', 'cod')->toArray();
        $rubroNombres = collect($rubros)->pluck('nom', 'cod')->toArray();
        $marcaNombres = collect($marcas)->pluck('nom', 'cod')->toArray();
        $gruposFiltro = ['depto' => ['deptosSeleccionados', $deptos, 'fa-layer-group', 'Departamento'], 'rubro' => ['rubrosSeleccionados', $rubros, 'fa-tags', 'Rubro'], 'marca' => ['marcasSeleccionadas', $marcas, 'fa-bookmark', 'Marca']];
    @endphp

    {{-- ===== LAYOUT: SIDEBAR ===== --}}
    @if($layoutCatalogo === 'sidebar')
        <div class="flex gap-6 min-w-0 w-full" x-data="{ filtrosOpen: false }">
            <aside class="hidden md:block w-64 flex-shrink-0">
                <div class="t-card p-4 sticky top-20 space-y-4 max-h-[calc(100vh-6rem)] overflow-y-auto">
                    @include('tienda.partials.sidebar-filters')
                </div>
            </aside>

            @include('tienda.partials.filtros-drawer')

            <div class="flex-1 min-w-0">
                <div class="md:hidden t-card p-3 mb-4 sticky top-[4.25rem] z-30">
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 t-muted"><i class="fas fa-search text-sm"></i></span>
                            <input type="text" wire:model.live.debounce.300ms="busqueda" placeholder="Buscar productos..." class="t-input !pl-9">
                        </div>
                        @if($hayFiltros)
                            <button @click="filtrosOpen = true" class="t-btn !px-3"><i class="fas fa-sliders-h"></i>@if($totalFiltros > 0)<span class="bg-white/25 text-xs font-bold rounded-full px-1.5">{{ $totalFiltros }}</span>@endif</button>
                        @endif
                    </div>
                </div>
                @include('tienda.partials.active-filters')
                @include('tienda.partials.product-info')
                @include('tienda.partials.product-grid')
            </div>
        </div>

    {{-- ===== LAYOUT: MINIMAL ===== --}}
    @elseif($layoutCatalogo === 'minimal')
        <div class="t-card p-3 sm:p-4 mb-5 sticky md:static top-[4.25rem] z-30">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 t-muted"><i class="fas fa-search text-sm"></i></span>
                <input type="text" wire:model.live.debounce.300ms="busqueda" placeholder="Buscar productos..." class="t-input !pl-9">
            </div>
        </div>
        @include('tienda.partials.product-info')
        @include('tienda.partials.product-grid')

    {{-- ===== LAYOUT: TOP (default) ===== --}}
    @else
        <div x-data="{ filtrosOpen: false, abierto: null }">
            <div class="t-card p-3 sm:p-4 mb-4 sticky md:static top-[4.25rem] z-30">
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 t-muted"><i class="fas fa-search text-sm"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="busqueda" placeholder="Buscar productos..." class="t-input !pl-9">
                        @if($busqueda)
                            <button type="button" wire:click="$set('busqueda', '')" class="absolute inset-y-0 right-0 flex items-center pr-3 t-muted hover:t-text"><i class="fas fa-times text-sm"></i></button>
                        @endif
                    </div>
                    @if($iaPedido)
                        <button type="button" wire:click="abrirPedidoIa" class="t-btn-outline !px-3 whitespace-nowrap" title="Escribí tu pedido y te lo armamos"><i class="fas fa-magic"></i><span class="hidden sm:inline"> Pedir escribiendo</span></button>
                    @endif
                    @if($hayFiltros)
                        <button @click="filtrosOpen = true" class="md:hidden t-btn !px-3"><i class="fas fa-sliders-h"></i>@if($totalFiltros > 0)<span class="bg-white/25 text-xs font-bold rounded-full px-1.5">{{ $totalFiltros }}</span>@endif</button>
                    @endif
                </div>
                @if($busquedaIa && $busquedaIaTexto === $busqueda)
                    <p class="text-xs t-soft mt-2"><i class="fas fa-magic mr-1" style="color: var(--tp);"></i> No había nada con "{{ $busqueda }}"; te mostramos lo parecido: {{ implode(', ', $busquedaIa['palabras']) }}.</p>
                @endif

                {{-- Filtros en escritorio: chips desplegables --}}
                @if($hayFiltros)
                    <div class="hidden md:flex gap-2 flex-wrap mt-3">
                        @foreach($gruposFiltro as $tipo => $cfg)
                            @if($filtrosVisibles[$tipo] && count($cfg[1]) > 0)
                                <div class="relative">
                                    <button @click="abierto === '{{ $tipo }}' ? abierto = null : abierto = '{{ $tipo }}'" class="t-pill {{ count($this->{$cfg[0]}) ? 'is-on' : '' }}">
                                        <i class="fas {{ $cfg[2] }} text-xs"></i> {{ $cfg[3] }}
                                        @if(count($this->{$cfg[0]}))<span class="rounded-full px-1.5 text-[10px]" style="background-color: var(--tp); color: var(--tp-ink);">{{ count($this->{$cfg[0]}) }}</span>@endif
                                        <i class="fas fa-chevron-down text-[10px] transition-transform" :class="abierto === '{{ $tipo }}' && 'rotate-180'"></i>
                                    </button>
                                    <div x-show="abierto === '{{ $tipo }}'" @click.outside="abierto = null" x-transition class="absolute top-full left-0 mt-1.5 t-card shadow-xl z-30 w-64 max-w-[calc(100vw-2rem)] max-h-72 overflow-y-auto p-1.5" style="display: none;">
                                        @foreach($cfg[1] as $item)
                                            @php $sel = in_array($item['cod'], $this->{$cfg[0]}); @endphp
                                            <button wire:click="toggleFiltro('{{ $tipo }}', '{{ $item['cod'] }}')" class="w-full text-left px-3 py-2 rounded-lg text-sm flex items-center gap-2.5 hover:bg-[var(--tp-tint)] {{ $sel ? 'font-semibold' : 't-soft' }}" style="{{ $sel ? 'color: var(--tp-dark);' : '' }}">
                                                <span class="w-4 h-4 rounded border flex-shrink-0 flex items-center justify-center" style="{{ $sel ? 'background-color: var(--tp); border-color: var(--tp); color: var(--tp-ink);' : 'border-color: var(--tborde);' }}">@if($sel)<i class="fas fa-check text-[9px]"></i>@endif</span>
                                                {{ \App\Services\TiendaEstiloService::nombreBonito($item['nom']) }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            @include('tienda.partials.filtros-drawer')
            @include('tienda.partials.active-filters')
            @include('tienda.partials.product-info')
            @include('tienda.partials.product-grid')
        </div>
    @endif

    {{-- Botones flotantes --}}
    <div class="fixed bottom-6 right-4 sm:right-6 z-50 flex flex-col gap-3 items-end safe-bottom safe-right">
        @php $telefonoWa = session('tienda_datos')['telefono'] ?? null; @endphp
        @if($telefonoWa)
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $telefonoWa) }}" target="_blank" class="w-12 h-12 bg-[#25D366] text-white rounded-full shadow-lg flex items-center justify-center hover:scale-110 transition-transform" title="Escribinos por WhatsApp">
                <i class="fab fa-whatsapp text-xl"></i>
            </a>
        @endif
        @if($cantidadCarrito > 0 && !$esVitrina)
            <button wire:click="irAlCarrito" class="t-btn !rounded-full w-14 h-14 !p-0 shadow-xl hover:scale-110 transition-transform relative" title="Ver el carrito">
                <i class="fas fa-shopping-bag text-lg"></i>
                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">{{ $cantidadCarrito }}</span>
            </button>
        @endif
    </div>

    {{-- Aviso "agregado" con la foto del producto --}}
    <div x-data="{ show: false, nombre: '', foto: '' }"
         x-on:producto-agregado.window="nombre = $event.detail?.nombre ?? ''; foto = $event.detail?.foto ?? ''; show = true; clearTimeout(window._tToast); window._tToast = setTimeout(() => show = false, 2200)"
         x-show="show" x-transition.opacity.duration.200ms
         class="fixed bottom-24 sm:bottom-auto sm:top-20 left-1/2 -translate-x-1/2 sm:left-auto sm:right-4 sm:translate-x-0 z-50 w-[calc(100%-2rem)] sm:w-80" style="display: none;">
        <div class="t-toast">
            <template x-if="foto"><img :src="foto" class="w-10 h-10 rounded-lg object-contain bg-white flex-shrink-0"></template>
            <template x-if="!foto"><span class="w-10 h-10 rounded-lg bg-white/15 flex items-center justify-center flex-shrink-0"><i class="fas fa-check"></i></span></template>
            <div class="min-w-0 flex-1">
                <p class="text-xs opacity-70">Agregado al carrito</p>
                <p class="text-sm font-semibold truncate" x-text="nombre || 'Producto'"></p>
            </div>
            @if(!$esVitrina)<button wire:click="irAlCarrito" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg flex-shrink-0" style="background-color: var(--tp); color: var(--tp-ink);">Ver</button>@endif
        </div>
    </div>

    {{-- Pedido desde un mensaje (F7) --}}
    @if($pedidoIaAbierto)
        <div class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4" wire:click="cerrarPedidoIa">
            <div class="absolute inset-0 bg-black/50"></div>
            <div class="relative t-card !rounded-t-2xl sm:!rounded-2xl shadow-2xl w-full sm:max-w-2xl max-h-[92vh] overflow-y-auto safe-bottom p-5 sm:p-6" wire:click.stop>
                <button wire:click="cerrarPedidoIa" class="absolute top-3 right-3 t-icon-btn"><i class="fas fa-times"></i></button>
                <h3 class="t-h text-xl font-bold t-text"><i class="fas fa-magic mr-1.5" style="color: var(--tp);"></i> Pedí escribiendo</h3>
                <p class="text-sm t-soft mt-1">Escribí tu pedido como se lo dirías al comercio y te lo armamos. Después revisás y agregás al carrito.</p>

                @if(empty($pedidoIaItems))
                    <textarea wire:model="pedidoIaTexto" rows="4" class="t-input mt-4" placeholder="Ej: 2 kg de arroz, 3 gaseosas cola de 2 litros y una yerba de medio kilo"></textarea>
                    @if($pedidoIaError)<p class="text-sm mt-2" style="color: var(--tdanger);">{{ $pedidoIaError }}</p>@endif
                    <button wire:click="armarPedidoIa" wire:loading.attr="disabled" class="t-btn t-btn-lg w-full mt-4">
                        <span wire:loading.remove wire:target="armarPedidoIa"><i class="fas fa-magic"></i> Armar mi pedido</span>
                        <span wire:loading wire:target="armarPedidoIa"><i class="fas fa-spinner fa-spin"></i> Leyendo tu pedido...</span>
                    </button>
                @else
                    <div class="mt-4 divide-y rounded-xl border" style="border-color: var(--tborde);">
                        @foreach($pedidoIaItems as $i => $it)
                            <div class="p-3 flex gap-3 items-start" style="border-color: var(--tborde);" wire:key="ia-{{ $i }}">
                                <div class="w-12 h-12 rounded-lg t-img flex items-center justify-center overflow-hidden flex-shrink-0 border" style="border-color: var(--tborde);">
                                    @if($it['foto'])<img src="{{ $it['foto'] }}" class="w-full h-full object-contain p-0.5">@else<i class="fas {{ $it['codigo'] ? 'fa-box' : 'fa-question' }} t-muted"></i>@endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] t-muted">Pediste: {{ $it['texto'] }}</p>
                                    @if($it['codigo'])
                                        <p class="text-sm font-semibold t-text">{{ $it['nombre'] }} <span class="t-precio text-sm ml-1">${{ number_format($it['precio'], 2, ',', '.') }}</span></p>
                                    @else
                                        <p class="text-sm font-semibold" style="color: var(--twarn);">No lo encontramos en el catálogo</p>
                                    @endif
                                    @if(count($it['alternativas']))
                                        <select wire:change="elegirAlternativaIa({{ $i }}, $event.target.value)" class="t-input !py-1 !text-xs mt-1.5 !w-auto max-w-full">
                                            <option value="">{{ $it['codigo'] ? '¿No era este? Elegí otro…' : 'Elegí uno parecido…' }}</option>
                                            @foreach($it['alternativas'] as $alt)
                                                <option value="{{ $alt['codigo'] }}">{{ $alt['nombre'] }} · ${{ number_format($alt['precio'], 2, ',', '.') }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                                <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                    <input type="number" step="{{ $permiteDecimal ? '0.01' : '1' }}" min="0.01" value="{{ $it['cantidad'] }}" wire:change="cambiarCantidadIa({{ $i }}, $event.target.value)" class="t-input !w-20 !py-1 text-center text-sm">
                                    <button wire:click="quitarItemIa({{ $i }})" class="text-xs t-muted hover:underline">Quitar</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @php $encontrados = count(array_filter($pedidoIaItems, fn ($x) => !empty($x['codigo']))); @endphp
                    <div class="flex gap-2 mt-4">
                        <button wire:click="$set('pedidoIaItems', [])" class="t-btn-outline"><i class="fas fa-pen"></i> Corregir el texto</button>
                        <button wire:click="agregarPedidoIa" @disabled($encontrados === 0) class="t-btn flex-1"><i class="fas fa-cart-plus"></i> Agregar {{ $encontrados }} al carrito</button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Ficha del producto --}}
    @if($modalCantidadAbierto && $productoModal)
        @php
            $fotosFicha = $productoModal['fotos'] ?? [];
            $fotoPrincipal = $productoModal['foto_url'] ?: session('tienda_foto_default');
            $nombreFicha = \App\Services\TiendaEstiloService::nombreBonito($productoModal['nombre'], $productoModal['codigo']);
            $favFicha = in_array((string) $productoModal['codigo'], $favoritos, true);
        @endphp
        <div class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4" wire:click="cerrarModalCantidad">
            <div class="absolute inset-0 bg-black/50"></div>
            <div class="relative t-card !rounded-t-2xl sm:!rounded-2xl shadow-2xl w-full sm:max-w-3xl max-h-[92vh] overflow-y-auto safe-bottom" wire:click.stop x-data="{ actual: 0 }">
                <button wire:click="cerrarModalCantidad" class="absolute top-3 right-3 z-10 t-icon-btn bg-white/80 shadow"><i class="fas fa-times"></i></button>

                <div class="grid sm:grid-cols-2">
                    {{-- Imagen / galeria --}}
                    <div class="p-4 sm:p-6 sm:border-r" style="border-color: var(--tborde);">
                        <div class="relative w-full aspect-square t-img rounded-xl flex items-center justify-center overflow-hidden">
                            @if(count($fotosFicha))
                                @foreach($fotosFicha as $i => $ff)
                                    <img src="{{ $ff['medium'] }}" alt="{{ $nombreFicha }}" x-show="actual === {{ $i }}" class="max-w-full max-h-full object-contain p-2" @if($i > 0) style="display:none" @endif>
                                @endforeach
                            @elseif($fotoPrincipal)
                                <img src="{{ $fotoPrincipal }}" alt="{{ $nombreFicha }}" class="max-w-full max-h-full object-contain p-2">
                            @else
                                <i class="fas fa-image text-5xl t-muted opacity-40"></i>
                            @endif
                            <button type="button" wire:click="toggleFavorito('{{ $productoModal['codigo'] }}')" class="t-heart absolute top-2 left-2 {{ $favFicha ? 'is-on' : '' }}"><i class="{{ $favFicha ? 'fas' : 'far' }} fa-heart text-sm"></i></button>
                        </div>
                        @if(count($fotosFicha) > 1)
                            <div class="flex gap-2 mt-3 overflow-x-auto">
                                @foreach($fotosFicha as $i => $ff)
                                    <button type="button" @click="actual = {{ $i }}" :class="actual === {{ $i }} ? 'ring-2' : 'opacity-60 hover:opacity-100'" class="w-14 h-14 flex-shrink-0 rounded-lg overflow-hidden t-img border" style="border-color: var(--tborde); --tw-ring-color: var(--tp);">
                                        <img src="{{ $ff['thumb'] }}" class="w-full h-full object-contain">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Datos --}}
                    <div class="p-4 sm:p-6 flex flex-col">
                        <p class="text-[11px] t-muted">#{{ $productoModal['codigo'] }}</p>
                        <h3 class="t-h text-xl font-bold leading-tight t-text mt-0.5">{{ $nombreFicha }}</h3>
                        <div class="mt-3 flex items-end gap-2 flex-wrap">
                            <span class="t-precio text-3xl">${{ number_format($productoModal['precio'], 2, ',', '.') }}</span>
                            @if(($productoModal['iva'] ?? 0) > 0)
                                <span class="text-xs t-muted mb-1.5">IVA {{ \App\Services\TiendaPrecioService::etiquetaTasa($productoModal['tasa']) }} incl. ${{ number_format($productoModal['iva'], 2, ',', '.') }}</span>
                            @endif
                        </div>
                        @if(!empty($productoModal['promos']))
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                @foreach($productoModal['promos'] as $pc)
                                    <span class="t-chip t-chip-ok"><i class="fas fa-tag"></i>{{ $pc }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if(!$vendeSinStock)
                            <p class="text-xs mt-2 {{ $productoModal['disponible'] > 0 ? '' : 'font-semibold' }}" style="color: {{ $productoModal['disponible'] > 0 ? 'var(--tok)' : 'var(--tdanger)' }};">
                                <i class="fas fa-circle text-[6px] mr-1"></i>{{ $productoModal['disponible'] > 0 ? 'Disponible: ' . \App\Services\TiendaStockService::formato($productoModal['disponible']) : 'Sin stock por el momento' }}
                            </p>
                        @endif
                        @if(!empty($productoModal['descripcion']))
                            <div class="mt-4 text-sm t-soft leading-relaxed whitespace-pre-line max-h-40 overflow-y-auto">{{ $productoModal['descripcion'] }}</div>
                        @endif

                        @if(!$esVitrina)
                            <div class="mt-auto pt-5">
                                <label class="t-label">Cantidad</label>
                                <div class="flex items-center gap-2">
                                    <button wire:click="decrementarCantidadModal" type="button" class="t-btn-outline !rounded-full w-11 h-11 !p-0"><i class="fas fa-minus text-xs"></i></button>
                                    <input type="number" wire:model.live.debounce.300ms="cantidadModal" min="{{ $permiteDecimal ? '0.01' : '1' }}" step="{{ $permiteDecimal ? '0.01' : '1' }}" class="t-input !w-24 text-center text-lg font-bold">
                                    <button wire:click="incrementarCantidadModal" type="button" class="t-btn-outline !rounded-full w-11 h-11 !p-0"><i class="fas fa-plus text-xs"></i></button>
                                    <div class="ml-auto text-right">
                                        <p class="text-[11px] t-muted">Subtotal</p>
                                        <p class="t-precio text-lg">${{ number_format($productoModal['precio'] * (float) $cantidadModal, 2, ',', '.') }}</p>
                                    </div>
                                </div>
                                @if($permiteDecimal)<p class="text-[10px] t-muted mt-1">Podés cargar decimales, por ejemplo 0.5 o 1.25.</p>@endif
                                <button wire:click="confirmarCantidadModal" @disabled(!$vendeSinStock && $productoModal['disponible'] <= 0) class="t-btn t-btn-lg w-full mt-4">
                                    <i class="fas fa-cart-plus"></i> Agregar al carrito
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
