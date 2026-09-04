@php
    $hayFiltrosActivos = !empty($deptosSeleccionados) || !empty($rubrosSeleccionados) || !empty($marcasSeleccionadas);
    $deptoNombres = collect($deptos)->pluck('nom', 'cod')->toArray();
    $rubroNombres = collect($rubros)->pluck('nom', 'cod')->toArray();
    $marcaNombres = collect($marcas)->pluck('nom', 'cod')->toArray();
@endphp

@if($hayFiltrosActivos)
    <div class="flex items-center gap-2 flex-wrap mb-3">
        <span class="text-xs font-medium" style="color: {{ $colores['texto'] }}80">Filtros:</span>
        @foreach($deptosSeleccionados as $cod)
            <button wire:click="quitarFiltro('depto', '{{ $cod }}')"
                    class="inline-flex items-center gap-1 px-2.5 py-1 text-white text-xs rounded-full transition-opacity hover:opacity-80"
                    style="background-color: {{ $colores['boton'] }}">
                {{ $deptoNombres[$cod] ?? $cod }} <i class="fas fa-times text-[10px]"></i>
            </button>
        @endforeach
        @foreach($rubrosSeleccionados as $cod)
            <button wire:click="quitarFiltro('rubro', '{{ $cod }}')"
                    class="inline-flex items-center gap-1 px-2.5 py-1 text-white text-xs rounded-full transition-opacity hover:opacity-80"
                    style="background-color: {{ $colores['boton'] }}">
                {{ $rubroNombres[$cod] ?? $cod }} <i class="fas fa-times text-[10px]"></i>
            </button>
        @endforeach
        @foreach($marcasSeleccionadas as $cod)
            <button wire:click="quitarFiltro('marca', '{{ $cod }}')"
                    class="inline-flex items-center gap-1 px-2.5 py-1 text-white text-xs rounded-full transition-opacity hover:opacity-80"
                    style="background-color: {{ $colores['boton'] }}">
                {{ $marcaNombres[$cod] ?? $cod }} <i class="fas fa-times text-[10px]"></i>
            </button>
        @endforeach
        <button wire:click="limpiarFiltros" class="text-xs text-gray-400 hover:text-gray-600 ml-1">Limpiar todo</button>
    </div>
@endif
