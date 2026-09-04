<div class="w-full max-w-full overflow-x-hidden">

    {{-- ===== PARTIALS: Buscador ===== --}}
    @php $searchBar = true; @endphp

    {{-- ===== LAYOUT: SIDEBAR ===== --}}
    @if($layoutCatalogo === 'sidebar')
        @php
            $hayFiltros = $filtrosVisibles['depto'] || $filtrosVisibles['rubro'] || $filtrosVisibles['marca'];
        @endphp
        <div class="flex gap-6 min-w-0 w-full" x-data="{ filtrosOpen: false }">
            {{-- Sidebar filtros (contenido reutilizable) --}}
            @php $sidebarFilterContent = true; @endphp

            {{-- Sidebar DESKTOP: siempre visible, sin Alpine --}}
            <aside class="hidden md:block w-64 flex-shrink-0">
                <div class="bg-white rounded-xl shadow-sm p-4 sticky top-20 space-y-4 max-h-[calc(100vh-6rem)] overflow-y-auto">
                    @include('tienda.partials.sidebar-filters')
                </div>
            </aside>

            {{-- Drawer MOBILE: controlado por Alpine --}}
            <div x-show="filtrosOpen" x-transition.opacity @click="filtrosOpen = false"
                 class="fixed inset-0 bg-black/40 z-40 md:hidden" style="display: none;"></div>
            <div x-show="filtrosOpen"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                 class="fixed inset-y-0 left-0 z-50 w-72 bg-white shadow-xl md:hidden overflow-y-auto" style="display: none;">
                <div class="p-4 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-700"><i class="fas fa-filter mr-1" style="color: {{ $colores['boton'] }}"></i> Filtros</span>
                        <button @click="filtrosOpen = false" class="p-1 text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                    </div>
                    @include('tienda.partials.sidebar-filters')
                </div>
            </div>

            {{-- Main content --}}
            <div class="flex-1 min-w-0">
                {{-- Buscador + botón filtros mobile --}}
                <div class="md:hidden bg-white rounded-xl shadow-sm p-4 mb-4">
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-search"></i></span>
                            <input type="text" wire:model.live.debounce.300ms="busqueda" placeholder="Buscar productos..."
                                   class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 outline-none"
                                   style="--tw-ring-color: {{ $colores['boton'] }}">
                        </div>
                        @if($hayFiltros)
                            <button @click="filtrosOpen = true"
                                    class="px-3 py-2.5 text-white rounded-lg text-sm font-semibold flex items-center gap-1.5"
                                    style="background-color: {{ $colores['boton'] }}">
                                <i class="fas fa-sliders-h"></i>
                                @php $totalFiltros = count($deptosSeleccionados) + count($rubrosSeleccionados) + count($marcasSeleccionadas); @endphp
                                @if($totalFiltros > 0)
                                    <span class="bg-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center" style="color: {{ $colores['boton'] }}">{{ $totalFiltros }}</span>
                                @endif
                            </button>
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
        {{-- Solo buscador --}}
        <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-search"></i></span>
                <input type="text" wire:model.live.debounce.300ms="busqueda" placeholder="Buscar productos..."
                       class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 outline-none"
                       style="--tw-ring-color: {{ $colores['boton'] }}">
            </div>
        </div>

        @include('tienda.partials.product-info')
        @include('tienda.partials.product-grid')

    {{-- ===== LAYOUT: TOP (actual/default) ===== --}}
    @else
        {{-- Buscador --}}
        <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-search"></i></span>
                <input type="text" wire:model.live.debounce.300ms="busqueda" placeholder="Buscar productos..."
                       class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 outline-none"
                       style="--tw-ring-color: {{ $colores['boton'] }}">
            </div>
        </div>

        {{-- Filtros colapsables multi-select --}}
        @if($filtrosVisibles['depto'] || $filtrosVisibles['rubro'] || $filtrosVisibles['marca'])
            @php
                $hayFiltrosActivos = !empty($deptosSeleccionados) || !empty($rubrosSeleccionados) || !empty($marcasSeleccionadas);
                $deptoNombres = collect($deptos)->pluck('nom', 'cod')->toArray();
                $rubroNombres = collect($rubros)->pluck('nom', 'cod')->toArray();
                $marcaNombres = collect($marcas)->pluck('nom', 'cod')->toArray();
            @endphp
            <div x-data="{ abierto: null }" class="mb-4 space-y-2">
                @include('tienda.partials.active-filters')

                <div class="flex gap-2 flex-wrap">
                    @foreach(['depto' => ['deptosSeleccionados', $deptos, 'fa-layer-group', 'Departamento'], 'rubro' => ['rubrosSeleccionados', $rubros, 'fa-tags', 'Rubro'], 'marca' => ['marcasSeleccionadas', $marcas, 'fa-bookmark', 'Marca']] as $tipo => $cfg)
                        @if($filtrosVisibles[$tipo] && count($cfg[1]) > 0)
                            <div class="relative" x-data>
                                <button @click="abierto === '{{ $tipo }}' ? abierto = null : abierto = '{{ $tipo }}'"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border rounded-lg text-sm transition-all hover:shadow-sm"
                                        :class="abierto === '{{ $tipo }}' ? 'border-2 shadow-sm' : 'border-gray-300'"
                                        :style="abierto === '{{ $tipo }}' ? 'border-color: {{ $colores['boton'] }}' : ''"
                                        style="{{ count($this->{$cfg[0]}) ? 'color: ' . $colores['boton'] . '; font-weight: 600;' : '' }}">
                                    <i class="fas {{ $cfg[2] }} text-xs"></i>
                                    {{ $cfg[3] }}
                                    @if(count($this->{$cfg[0]}))
                                        <span class="inline-flex items-center justify-center w-5 h-5 text-[10px] text-white rounded-full"
                                              style="background-color: {{ $colores['boton'] }}">{{ count($this->{$cfg[0]}) }}</span>
                                    @endif
                                    <i class="fas fa-chevron-down text-[10px] transition-transform" :class="abierto === '{{ $tipo }}' && 'rotate-180'"></i>
                                </button>
                                <div x-show="abierto === '{{ $tipo }}'" @click.outside="abierto = null"
                                     x-transition
                                     class="absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-30 w-60 max-w-[calc(100vw-2rem)] max-h-72 overflow-y-auto"
                                     style="display: none;">
                                    <div class="p-2">
                                        @foreach($cfg[1] as $item)
                                            @php $seleccionado = in_array($item['cod'], $this->{$cfg[0]}); @endphp
                                            <button wire:click="toggleFiltro('{{ $tipo }}', '{{ $item['cod'] }}')"
                                                    class="w-full text-left px-3 py-2 rounded-lg text-sm flex items-center gap-2.5 hover:bg-gray-50 transition">
                                                <span class="w-4 h-4 rounded border flex-shrink-0 flex items-center justify-center transition-colors {{ $seleccionado ? 'text-white' : 'border-gray-300' }}"
                                                      style="{{ $seleccionado ? 'background-color: ' . $colores['boton'] . '; border-color: ' . $colores['boton'] : '' }}">
                                                    @if($seleccionado) <i class="fas fa-check text-[9px]"></i> @endif
                                                </span>
                                                <span class="{{ $seleccionado ? 'font-semibold' : 'text-gray-700' }}"
                                                      style="{{ $seleccionado ? 'color: ' . $colores['boton'] : '' }}">{{ $item['nom'] }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        @include('tienda.partials.product-info')
        @include('tienda.partials.product-grid')
    @endif

    {{-- Botones flotantes --}}
    <div class="fixed bottom-6 right-4 sm:right-6 z-50 flex flex-col gap-3 items-end safe-bottom safe-right">
        @php $telefonoWa = session('tienda_datos')['telefono'] ?? null; @endphp
        @if($telefonoWa)
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $telefonoWa) }}" target="_blank"
               class="w-12 h-12 bg-[#25D366] text-white rounded-full shadow-lg flex items-center justify-center hover:bg-[#1ebe57] transition-colors hover:scale-110 transform duration-200">
                <i class="fab fa-whatsapp text-xl"></i>
            </a>
        @endif
        @if($cantidadCarrito > 0)
            <button wire:click="irAlCarrito"
                    class="text-white rounded-full w-14 h-14 flex items-center justify-center shadow-xl hover:scale-110 transition-transform relative"
                    style="background-color: {{ $colores['boton'] }}">
                <i class="fas fa-shopping-cart text-lg"></i>
                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">{{ $cantidadCarrito }}</span>
            </button>
        @endif
    </div>

    <div x-data="{ show: false }" x-on:producto-agregado.window="show = true; setTimeout(() => show = false, 2000)"
         x-show="show" x-transition.opacity
         class="fixed top-20 right-4 bg-green-500 text-white px-4 py-3 rounded-lg shadow-lg z-50" style="display: none;">
        <i class="fas fa-check mr-2"></i> Producto agregado
    </div>

    {{-- Modal: elegir cantidad --}}
    @if($modalCantidadAbierto && $productoModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4" wire:click="cerrarModalCantidad">
            <div class="absolute inset-0 bg-black/50"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6" wire:click.stop>
                <button wire:click="cerrarModalCantidad" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 p-1">
                    <i class="fas fa-times"></i>
                </button>

                <div class="flex items-center gap-3 mb-4">
                    <div class="w-16 h-16 bg-gray-50 rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0">
                        @if($productoModal['foto_url'])
                            <img src="{{ $productoModal['foto_url'] }}" alt="{{ $productoModal['nombre'] }}" class="max-w-full max-h-full object-contain">
                        @else
                            <i class="fas fa-image text-gray-300 text-2xl"></i>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-semibold leading-tight" style="color: {{ $colores['texto'] }}">{{ $productoModal['nombre'] }}</h3>
                        <p class="text-base font-bold mt-1" style="color: {{ $colores['precio'] }}">
                            ${{ number_format($productoModal['precio'], 2, ',', '.') }}
                        </p>
                    </div>
                </div>

                <label class="block text-xs font-medium text-gray-600 mb-2">Cantidad</label>
                <div class="flex items-center gap-2">
                    <button wire:click="decrementarCantidadModal" type="button"
                            class="w-10 h-10 rounded-lg border border-gray-300 hover:bg-gray-50 text-gray-600 flex items-center justify-center text-lg">
                        <i class="fas fa-minus text-xs"></i>
                    </button>
                    <input type="number"
                           wire:model.live.debounce.300ms="cantidadModal"
                           min="{{ $permiteDecimal ? '0.01' : '1' }}"
                           step="{{ $permiteDecimal ? '0.01' : '1' }}"
                           class="flex-1 text-center text-lg font-semibold border border-gray-300 rounded-lg py-2 focus:ring-2 outline-none"
                           style="--tw-ring-color: {{ $colores['boton'] }}">
                    <button wire:click="incrementarCantidadModal" type="button"
                            class="w-10 h-10 rounded-lg border border-gray-300 hover:bg-gray-50 text-gray-600 flex items-center justify-center text-lg">
                        <i class="fas fa-plus text-xs"></i>
                    </button>
                </div>
                @if($permiteDecimal)
                    <p class="text-[10px] text-gray-400 mt-1 text-center">Podés ingresar valores decimales (ej: 0.5, 1.25)</p>
                @endif

                <div class="mt-4 flex items-center justify-between text-sm">
                    <span class="text-gray-500">Subtotal:</span>
                    <span class="font-bold text-base" style="color: {{ $colores['precio'] }}">
                        ${{ number_format($productoModal['precio'] * (float) $cantidadModal, 2, ',', '.') }}
                    </span>
                </div>

                <button wire:click="confirmarCantidadModal"
                        class="w-full mt-4 py-3 text-white font-semibold rounded-lg transition-opacity hover:opacity-90 flex items-center justify-center gap-2"
                        style="background-color: {{ $colores['boton'] }}">
                    <i class="fas fa-cart-plus"></i> Agregar al carrito
                </button>
            </div>
        </div>
    @endif
</div>
