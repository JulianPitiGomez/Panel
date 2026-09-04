@if($avisoStock)
    <div class="mb-3 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-center gap-2">
        <i class="fas fa-box-open"></i> <span>{{ $avisoStock }}</span>
        <button type="button" wire:click="$set('avisoStock', '')" class="ml-auto text-amber-500 hover:text-amber-700"><i class="fas fa-times"></i></button>
    </div>
@endif
@if(count($productos) > 0)
    <div class="grid grid-cols-2 gap-3
        @if($columnasDesktop == 2) md:grid-cols-2
        @elseif($columnasDesktop == 3) md:grid-cols-3
        @elseif($columnasDesktop == 5) md:grid-cols-5
        @else md:grid-cols-4
        @endif
    ">
        @foreach($productos as $producto)
            @switch($estiloTarjeta)
                {{-- COMPACTA --}}
                @case('compacta')
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow group flex flex-col">
                        <div class="aspect-square bg-gray-50 flex items-center justify-center overflow-hidden p-2">
                            @if($producto->foto_url)
                                <img src="{{ $producto->foto_url }}" alt="{{ $producto->NOMBRE }}"
                                     class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="text-gray-300"><i class="fas fa-image text-2xl"></i></div>
                            @endif
                        </div>
                        <div class="p-2 flex flex-col flex-1">
                            <h3 class="text-xs font-medium line-clamp-1 leading-tight" style="color: {{ $colores['texto'] }}">
                                {{ $producto->NOMBRE }}
                            </h3>
                            <div class="flex items-center justify-between mt-auto pt-1.5">
                                <span class="text-sm font-bold" style="color: {{ $colores['precio'] }}">
                                    ${{ number_format($this->obtenerPrecio($producto), 2, ',', '.') }}
                                </span>
                                @php $ivaCard = $this->ivaIncluido($producto); $sinStock = !$vendeSinStock && $this->disponibleDe($producto->CODIGO) <= 0; @endphp
                                @if($ivaCard > 0)
                                    <span class="text-[9px] text-gray-400 block leading-tight">Incl. IVA ${{ number_format($ivaCard, 2, ",", ".") }}</span>
                                @endif
                                @if($sinStock)
                                    <span class="text-[10px] text-red-400 block"><i class="fas fa-circle text-[6px] mr-0.5"></i> Sin stock</span>
                                @endif
                                <button wire:click="{{ $modoCantidad === 'modal' ? 'abrirModalCantidad' : 'agregarAlCarrito' }}('{{ $producto->CODIGO }}')"
                                        @if(!$vendeSinStock && $this->disponibleDe($producto->CODIGO) <= 0) disabled title="Sin stock" @endif
                                        wire:loading.attr="disabled" wire:target="agregarAlCarrito('{{ $producto->CODIGO }}')"
                                        class="w-7 h-7 text-white text-xs rounded-md flex items-center justify-center transition-opacity hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed"
                                        style="background-color: {{ $colores['boton'] }}">
                                    <span wire:loading.remove wire:target="agregarAlCarrito('{{ $producto->CODIGO }}')"><i class="fas fa-cart-plus"></i></span>
                                    <span wire:loading wire:target="agregarAlCarrito('{{ $producto->CODIGO }}')"><i class="fas fa-spinner fa-spin"></i></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    @break

                {{-- DETALLADA --}}
                @case('detallada')
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow group flex flex-col">
                        <div class="aspect-square bg-gray-50 flex items-center justify-center overflow-hidden p-3">
                            @if($producto->foto_url)
                                <img src="{{ $producto->foto_url }}" alt="{{ $producto->NOMBRE }}"
                                     class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="text-gray-300"><i class="fas fa-image text-4xl"></i></div>
                            @endif
                        </div>
                        <div class="p-3 flex flex-col flex-1">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide">{{ $producto->CODIGO }}</p>
                            <h3 class="text-sm font-medium line-clamp-2 leading-tight mt-0.5" style="color: {{ $colores['texto'] }}">
                                {{ $producto->NOMBRE }}
                            </h3>
                            <div class="flex items-center gap-2 mt-1">
                                @if($this->disponibleDe($producto->CODIGO) > 0)
                                    <span class="text-[10px] text-green-600"><i class="fas fa-circle text-[6px] mr-0.5"></i> Disponible: {{ AppServicesTiendaStockService::formato($this->disponibleDe($producto->CODIGO)) }}</span>
                                    @else
                                    <span class="text-[10px] text-red-400"><i class="fas fa-circle text-[6px] mr-0.5"></i> Sin stock</span>
                                    @endif
                            </div>
                            <div class="mt-auto pt-2">
                                <span class="text-base font-bold block" style="color: {{ $colores['precio'] }}">
                                    ${{ number_format($this->obtenerPrecio($producto), 2, ',', '.') }}
                                </span>
                                @php $ivaCard = $this->ivaIncluido($producto); $sinStock = !$vendeSinStock && $this->disponibleDe($producto->CODIGO) <= 0; @endphp
                                @if($ivaCard > 0)
                                    <span class="text-[9px] text-gray-400 block leading-tight">Incl. IVA ${{ number_format($ivaCard, 2, ",", ".") }}</span>
                                @endif
                                @if($sinStock)
                                    <span class="text-[10px] text-red-400 block"><i class="fas fa-circle text-[6px] mr-0.5"></i> Sin stock</span>
                                @endif
                                <button wire:click="{{ $modoCantidad === 'modal' ? 'abrirModalCantidad' : 'agregarAlCarrito' }}('{{ $producto->CODIGO }}')"
                                        @if(!$vendeSinStock && $this->disponibleDe($producto->CODIGO) <= 0) disabled title="Sin stock" @endif
                                        wire:loading.attr="disabled" wire:target="agregarAlCarrito('{{ $producto->CODIGO }}')"
                                        class="w-full mt-2 py-2 text-white text-xs font-semibold rounded-lg transition-opacity hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-1"
                                        style="background-color: {{ $colores['boton'] }}">
                                    <span wire:loading.remove wire:target="agregarAlCarrito('{{ $producto->CODIGO }}')"><i class="fas fa-cart-plus mr-1"></i> Agregar al carrito</span>
                                    <span wire:loading wire:target="agregarAlCarrito('{{ $producto->CODIGO }}')"><i class="fas fa-spinner fa-spin"></i></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    @break

                {{-- ESTANDAR (default) --}}
                @default
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow group flex flex-col">
                        <div class="aspect-square bg-gray-50 flex items-center justify-center overflow-hidden p-2">
                            @if($producto->foto_url)
                                <img src="{{ $producto->foto_url }}" alt="{{ $producto->NOMBRE }}"
                                     class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="text-gray-300"><i class="fas fa-image text-3xl"></i></div>
                            @endif
                        </div>
                        <div class="p-2.5 flex flex-col flex-1">
                            <h3 class="text-xs font-medium line-clamp-2 leading-tight" style="color: {{ $colores['texto'] }}">
                                {{ $producto->NOMBRE }}
                            </h3>
                            <div class="mt-auto pt-2">
                                <span class="text-sm font-bold block" style="color: {{ $colores['precio'] }}">
                                    ${{ number_format($this->obtenerPrecio($producto), 2, ',', '.') }}
                                </span>
                                @php $ivaCard = $this->ivaIncluido($producto); $sinStock = !$vendeSinStock && $this->disponibleDe($producto->CODIGO) <= 0; @endphp
                                @if($ivaCard > 0)
                                    <span class="text-[9px] text-gray-400 block leading-tight">Incl. IVA ${{ number_format($ivaCard, 2, ",", ".") }}</span>
                                @endif
                                @if($sinStock)
                                    <span class="text-[10px] text-red-400 block"><i class="fas fa-circle text-[6px] mr-0.5"></i> Sin stock</span>
                                @endif
                                <button wire:click="{{ $modoCantidad === 'modal' ? 'abrirModalCantidad' : 'agregarAlCarrito' }}('{{ $producto->CODIGO }}')"
                                        @if(!$vendeSinStock && $this->disponibleDe($producto->CODIGO) <= 0) disabled title="Sin stock" @endif
                                        wire:loading.attr="disabled" wire:target="agregarAlCarrito('{{ $producto->CODIGO }}')"
                                        class="w-full mt-2 py-1.5 text-white text-xs font-semibold rounded-lg transition-opacity hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-1"
                                        style="background-color: {{ $colores['boton'] }}">
                                    <span wire:loading.remove wire:target="agregarAlCarrito('{{ $producto->CODIGO }}')"><i class="fas fa-cart-plus"></i> Agregar</span>
                                    <span wire:loading wire:target="agregarAlCarrito('{{ $producto->CODIGO }}')"><i class="fas fa-spinner fa-spin"></i></span>
                                </button>
                            </div>
                        </div>
                    </div>
            @endswitch
        @endforeach
    </div>

    {{-- Paginación --}}
    @if($totalPaginas > 1)
        <div class="flex items-center justify-center gap-2 mt-8">
            <button wire:click="paginaAnterior" @if($paginaActual <= 1) disabled @endif
                    class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-chevron-left"></i>
            </button>
            @php $inicio = max(1, $paginaActual - 2); $fin = min($totalPaginas, $paginaActual + 2); @endphp
            @for($i = $inicio; $i <= $fin; $i++)
                <button wire:click="irAPagina({{ $i }})"
                        class="px-3 py-2 rounded-lg text-sm {{ $i == $paginaActual ? 'text-white' : 'bg-white border border-gray-300 hover:bg-gray-50' }}"
                        @if($i == $paginaActual) style="background-color: {{ $colores['boton'] }}" @endif>
                    {{ $i }}
                </button>
            @endfor
            <button wire:click="paginaSiguiente" @if($paginaActual >= $totalPaginas) disabled @endif
                    class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    @endif
@else
    <div class="bg-white rounded-xl shadow-sm p-12 text-center">
        <i class="fas fa-box-open text-5xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 text-lg">No se encontraron productos</p>
        @if($busqueda || !empty($rubrosSeleccionados) || !empty($marcasSeleccionadas) || !empty($deptosSeleccionados))
            <button wire:click="limpiarFiltros" class="mt-4 hover:underline" style="color: {{ $colores['boton'] }}">
                Limpiar filtros
            </button>
        @endif
    </div>
@endif
