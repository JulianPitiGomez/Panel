{{-- Buscador --}}
<div class="relative">
    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-search"></i></span>
    <input type="text" wire:model.live.debounce.300ms="busqueda" placeholder="Buscar..."
           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 outline-none text-sm"
           style="--tw-ring-color: {{ $colores['boton'] }}">
</div>

@if($filtrosVisibles['depto'] || $filtrosVisibles['rubro'] || $filtrosVisibles['marca'])
    @php $hayFiltrosActivos = !empty($deptosSeleccionados) || !empty($rubrosSeleccionados) || !empty($marcasSeleccionadas); @endphp
    @if($hayFiltrosActivos)
        <button wire:click="limpiarFiltros" class="text-xs hover:underline w-full text-left" style="color: {{ $colores['boton'] }}">
            <i class="fas fa-times mr-1"></i> Limpiar filtros
        </button>
    @endif

    @foreach([
        'depto' => ['deptosSeleccionados', $deptos, 'fa-layer-group', 'Departamento'],
        'rubro' => ['rubrosSeleccionados', $rubros, 'fa-tags', 'Rubro'],
        'marca' => ['marcasSeleccionadas', $marcas, 'fa-bookmark', 'Marca']
    ] as $tipo => $cfg)
        @if($filtrosVisibles[$tipo] && count($cfg[1]) > 0)
            <div x-data="{ open: true }">
                <button @click="open = !open" class="flex items-center justify-between w-full text-sm font-semibold text-gray-700 py-1">
                    <span><i class="fas {{ $cfg[2] }} text-xs mr-1" style="color: {{ $colores['boton'] }}"></i> {{ $cfg[3] }}</span>
                    <i class="fas fa-chevron-down text-[10px] text-gray-400 transition-transform" :class="open && 'rotate-180'"></i>
                </button>
                <div x-show="open" x-collapse class="mt-1 space-y-0.5">
                    @foreach($cfg[1] as $item)
                        @php $sel = in_array($item['cod'], $this->{$cfg[0]}); @endphp
                        <button wire:click="toggleFiltro('{{ $tipo }}', '{{ $item['cod'] }}')"
                                class="w-full text-left px-2 py-1.5 rounded text-xs flex items-center gap-2 hover:bg-gray-50 transition {{ $sel ? 'font-semibold' : 'text-gray-600' }}"
                                style="{{ $sel ? 'color: ' . $colores['boton'] : '' }}">
                            <span class="w-3.5 h-3.5 rounded border flex-shrink-0 flex items-center justify-center {{ $sel ? 'text-white' : 'border-gray-300' }}"
                                  style="{{ $sel ? 'background-color: ' . $colores['boton'] . '; border-color: ' . $colores['boton'] : '' }}">
                                @if($sel) <i class="fas fa-check text-[8px]"></i> @endif
                            </span>
                            {{ $item['nom'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
@endif
