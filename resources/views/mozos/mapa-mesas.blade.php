<div class="flex flex-col h-full bg-gray-50" wire:poll.6s="cargarMesas">
    <!-- Header compacto solo con búsqueda -->
    <div class="bg-white shadow-sm p-3 flex-shrink-0">
        <div class="flex gap-2">
            <input type="number"
                   wire:model="numeroMesa"
                   wire:keydown.enter="irAMesa"
                   placeholder="N° Mesa..."
                   class="flex-1 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-yellow-500 text-sm">
            <button wire:click="irAMesa"
                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                <i class="fa fa-search"></i>
            </button>
        </div>
    </div>

    <!-- Grid de Mesas - Área scrolleable -->
    <div class="flex-1 overflow-y-auto p-2 pb-20">
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-7 gap-2">
            @forelse($mesasFiltradas as $mesa)
                <a href="{{ route('mozos.mesa', ['mesa' => $mesa->mesa]) }}"
                   wire:navigate
                   class="bg-white rounded-lg shadow hover:shadow-md transition p-2 relative">

                    <!-- Indicador de estado listo -->
                    @if($mesa->estado > 0)
                        <div class="absolute top-1 right-1 w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    @endif

                    <!-- Imagen de recurso de mesa -->
                    <div class="flex justify-center mb-1">
                        <img src="{{ asset('img/mozos/' . $mesa->recurso . '.png') }}"
                             alt="Mesa {{ $mesa->mesa }}"
                             class="w-12 h-12 object-contain">
                    </div>

                    <!-- Info mesa -->
                    <div class="text-center">
                        <div class="bg-blue-500 text-white px-2 py-0.5 rounded text-xs font-semibold mb-1">
                            M{{ $mesa->mesa }}
                        </div>
                        <div class="text-sm font-bold text-gray-800">
                            ${{ number_format($mesa->total, 0) }}
                        </div>
                        @if($mesa->items > 0)
                            <div class="text-xs text-gray-500">
                                {{ $mesa->items }} Items
                            </div>
                        @endif
                    </div>
                </a>
            @empty
                <div class="col-span-full text-center py-12 text-gray-500">
                    <i class="fa fa-table text-3xl mb-2"></i>
                    <p class="text-sm">No hay mesas</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Barra de navegación inferior fija -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg">
        <div class="grid grid-cols-5 gap-1 p-2">
            <!-- Selector de Salón -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open"
                        class="w-full flex flex-col items-center justify-center py-2 rounded-lg transition bg-yellow-500 text-white">
                    <i class="fa fa-building text-base mb-0.5"></i>
                    <span class="text-xs font-semibold truncate w-full px-1">
                        @foreach($salones as $salon)
                            @if($salon->codigo == $salonActual){{ $salon->nombre }}@endif
                        @endforeach
                    </span>
                </button>

                <!-- Dropdown de salones -->
                <div x-show="open" @click.away="open = false"
                     x-transition
                     class="absolute bottom-full left-0 mb-2 w-48 bg-white rounded-lg shadow-xl border z-50">
                    @foreach($salones as $salon)
                        <button wire:click="cambiarSalon({{ $salon->codigo }})"
                                @click="open = false"
                                class="w-full text-left px-4 py-3 hover:bg-yellow-50 transition border-b last:border-b-0
                                       {{ $salonActual == $salon->codigo ? 'bg-yellow-100 font-bold' : '' }}">
                            {{ $salon->nombre }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Botón Ocupadas -->
            <button wire:click="cambiarEstado('O')"
                    class="flex flex-col items-center justify-center py-2 rounded-lg transition
                           {{ $estado == 'O'
                              ? 'bg-red-500 text-white'
                              : 'bg-gray-100 text-gray-600' }}">
                <i class="fa fa-circle text-base mb-0.5"></i>
                <span class="text-xs font-semibold">Ocupadas</span>
            </button>

            <!-- Botón Propias -->
            <button wire:click="cambiarEstado('P')"
                    class="flex flex-col items-center justify-center py-2 rounded-lg transition
                           {{ $estado == 'P'
                              ? 'bg-green-500 text-white'
                              : 'bg-gray-100 text-gray-600' }}">
                <i class="fa fa-user text-base mb-0.5"></i>
                <span class="text-xs font-semibold">Propias</span>
            </button>

            <!-- Botón Todas -->
            <button wire:click="cambiarEstado('T')"
                    class="flex flex-col items-center justify-center py-2 rounded-lg transition
                           {{ $estado == 'T'
                              ? 'bg-blue-500 text-white'
                              : 'bg-gray-100 text-gray-600' }}">
                <i class="fa fa-th text-base mb-0.5"></i>
                <span class="text-xs font-semibold">Todas</span>
            </button>

            <!-- Botón Salir -->
            <a href="{{ route('logout.mozos') }}"
               class="flex flex-col items-center justify-center py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-red-50 hover:text-red-600 transition">
                <i class="fa fa-sign-out-alt text-base mb-0.5"></i>
                <span class="text-xs font-semibold">Salir</span>
            </a>
        </div>
    </div>
</div>
