@php
    $hayFiltrosActivos = !empty($deptosSeleccionados) || !empty($rubrosSeleccionados) || !empty($marcasSeleccionadas);
    $deptoNombres = collect($deptos)->pluck('nom', 'cod')->toArray();
    $rubroNombres = collect($rubros)->pluck('nom', 'cod')->toArray();
    $marcaNombres = collect($marcas)->pluck('nom', 'cod')->toArray();
@endphp

@if($hayFiltrosActivos)
    <div class="flex items-center gap-2 flex-wrap mb-4">
        @foreach(['depto' => [$deptosSeleccionados, $deptoNombres], 'rubro' => [$rubrosSeleccionados, $rubroNombres], 'marca' => [$marcasSeleccionadas, $marcaNombres]] as $tipo => [$seleccion, $nombres])
            @foreach($seleccion as $cod)
                <button wire:click="quitarFiltro('{{ $tipo }}', '{{ $cod }}')" class="t-chip !py-1.5 !px-3 !text-xs hover:opacity-80">
                    {{ \App\Services\TiendaEstiloService::nombreBonito($nombres[$cod] ?? $cod) }} <i class="fas fa-times text-[10px]"></i>
                </button>
            @endforeach
        @endforeach
        <button wire:click="limpiarFiltros" class="text-xs t-muted hover:underline ml-1">Limpiar todo</button>
    </div>
@endif
