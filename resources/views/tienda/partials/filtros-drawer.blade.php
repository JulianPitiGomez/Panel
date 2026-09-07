{{-- Panel deslizable de filtros para el celular. Requiere x-data con filtrosOpen en el padre. --}}
@if($filtrosVisibles['depto'] || $filtrosVisibles['rubro'] || $filtrosVisibles['marca'])
    <div x-show="filtrosOpen" x-transition.opacity @click="filtrosOpen = false" class="fixed inset-0 bg-black/40 z-40 md:hidden" style="display: none;"></div>
    <div x-show="filtrosOpen"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
         class="fixed inset-y-0 left-0 z-50 w-80 max-w-[85vw] shadow-2xl md:hidden overflow-y-auto t-body" style="display: none; background-color: var(--tcard);">
        <div class="p-4 space-y-4">
            <div class="flex items-center justify-between">
                <span class="t-h font-bold t-text"><i class="fas fa-sliders-h mr-1.5" style="color: var(--tp);"></i> Filtros</span>
                <button @click="filtrosOpen = false" class="t-icon-btn"><i class="fas fa-times"></i></button>
            </div>
            @include('tienda.partials.sidebar-filters')
            <button @click="filtrosOpen = false" class="t-btn w-full">Ver {{ $totalProductos }} producto{{ $totalProductos != 1 ? 's' : '' }}</button>
        </div>
    </div>
@endif
