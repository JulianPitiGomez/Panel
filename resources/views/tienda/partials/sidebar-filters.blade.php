{{-- Buscador --}}
<div class="relative">
    <span class="absolute inset-y-0 left-0 flex items-center pl-3 t-muted"><i class="fas fa-search text-sm"></i></span>
    <input type="text" wire:model.live.debounce.300ms="busqueda" placeholder="Buscar..." class="t-input !pl-9 !py-2 text-sm">
</div>

@if($filtrosVisibles['depto'] || $filtrosVisibles['rubro'] || $filtrosVisibles['marca'])
    @php $hayFiltrosActivos = !empty($deptosSeleccionados) || !empty($rubrosSeleccionados) || !empty($marcasSeleccionadas); @endphp
    @if($hayFiltrosActivos)
        <button wire:click="limpiarFiltros" class="t-link text-xs w-full text-left"><i class="fas fa-times mr-1"></i> Limpiar filtros</button>
    @endif

    @foreach([
        'depto' => ['deptosSeleccionados', $deptos, 'fa-layer-group', 'Departamento'],
        'rubro' => ['rubrosSeleccionados', $rubros, 'fa-tags', 'Rubro'],
        'marca' => ['marcasSeleccionadas', $marcas, 'fa-bookmark', 'Marca']
    ] as $tipo => $cfg)
        @if($filtrosVisibles[$tipo] && count($cfg[1]) > 0)
            <div x-data="{ open: true }">
                <button @click="open = !open" class="flex items-center justify-between w-full text-sm font-semibold t-text py-1">
                    <span><i class="fas {{ $cfg[2] }} text-xs mr-1.5" style="color: var(--tp);"></i> {{ $cfg[3] }}</span>
                    <i class="fas fa-chevron-down text-[10px] t-muted transition-transform" :class="open && 'rotate-180'"></i>
                </button>
                <div x-show="open" x-collapse class="mt-1 space-y-0.5 max-h-64 overflow-y-auto pr-1">
                    @foreach($cfg[1] as $item)
                        @php $sel = in_array($item['cod'], $this->{$cfg[0]}); @endphp
                        <button wire:click="toggleFiltro('{{ $tipo }}', '{{ $item['cod'] }}')"
                                class="w-full text-left px-2 py-1.5 rounded-lg text-xs flex items-center gap-2 hover:bg-[var(--tp-tint)] transition {{ $sel ? 'font-semibold' : 't-soft' }}"
                                style="{{ $sel ? 'color: var(--tp-dark);' : '' }}">
                            <span class="w-3.5 h-3.5 rounded border flex-shrink-0 flex items-center justify-center" style="{{ $sel ? 'background-color: var(--tp); border-color: var(--tp); color: var(--tp-ink);' : 'border-color: var(--tborde);' }}">
                                @if($sel) <i class="fas fa-check text-[8px]"></i> @endif
                            </span>
                            {{ \App\Services\TiendaEstiloService::nombreBonito($item['nom']) }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
@endif
