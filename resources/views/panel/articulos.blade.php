<div class="space-y-6 w-full max-w-full overflow-x-hidden">

    <!-- Tabs -->
    <div class="flex gap-1 bg-white rounded-xl shadow-sm border border-gray-200 p-1">
        <button wire:click="setTab('vendidos')"
                class="flex-1 flex items-center justify-center gap-2 px-2 sm:px-4 py-2.5 sm:py-3 rounded-lg text-xs sm:text-sm font-semibold transition-all
                {{ $tabActivo === 'vendidos' ? 'bg-[#1E1B35] text-white shadow' : 'text-gray-600 hover:bg-gray-100' }}">
            <i class="fas fa-chart-bar"></i>
            <span>Vendidos</span>
        </button>
        <button wire:click="setTab('consultas')"
                class="flex-1 flex items-center justify-center gap-2 px-2 sm:px-4 py-2.5 sm:py-3 rounded-lg text-xs sm:text-sm font-semibold transition-all
                {{ $tabActivo === 'consultas' ? 'bg-[#1E1B35] text-white shadow' : 'text-gray-600 hover:bg-gray-100' }}">
            <i class="fas fa-barcode"></i>
            <span>Consultas y Costos</span>
        </button>
    </div>

    @if($tabActivo === 'consultas')
        @livewire('panel.consultas-costos', [], key('consultas-costos'))
    @else
        @livewire('panel.articulos-vendidos', [], key('articulos-vendidos'))
    @endif
</div>
