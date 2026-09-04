<div class="flex flex-col h-full bg-gray-50" wire:poll.6s="cargarMesas" x-data="{ mostrarConfirmacionSalir: false, mostrarSelectorSalon: false }">
    <!-- Modal de confirmación para cerrar sesión -->
    <div x-show="mostrarConfirmacionSalir"
         x-transition.opacity
         class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-50 flex items-center justify-center px-4"
         style="display: none;">
        <div @click.away="mostrarConfirmacionSalir = false"
             x-transition
             class="bg-white rounded-lg shadow-2xl max-w-md w-full p-6">
            <div class="flex items-center justify-center w-16 h-16 mx-auto bg-yellow-100 rounded-full mb-4">
                <i class="fas fa-sign-out-alt text-[#FFAF22] text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 text-center mb-2">¿Cerrar sesión?</h3>
            <p class="text-gray-600 text-center mb-6">¿Estás seguro que deseas salir del panel de mozos?</p>
            <div class="flex gap-3">
                <button @click="mostrarConfirmacionSalir = false"
                        class="flex-1 px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold rounded-lg transition-colors">
                    Cancelar
                </button>
                <a href="{{route('logout.mozos')}}"
                   class="flex-1 px-4 py-3  bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition-colors text-center">
                    Salir
                </a>
            </div>
        </div>
    </div>

    <!-- Modal de Selector de Salón -->
    <div x-show="mostrarSelectorSalon"
         x-transition.opacity
         class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-50 flex items-center justify-center px-4"
         style="display: none;">
        <div @click.away="mostrarSelectorSalon = false"
             x-transition
             class="bg-white rounded-lg shadow-2xl max-w-md w-full overflow-hidden">
            <!-- Header del modal -->
            <div class="bg-[#FFAF22] px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center">
                            <i class="fas fa-utensils text-[#FFAF22] text-xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white">Seleccionar Salón</h3>
                    </div>
                    <button @click="mostrarSelectorSalon = false"
                            class="text-white hover:text-gray-200 text-2xl">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Lista de salones -->
            <div class="max-h-96 overflow-y-auto p-4">
                <div class="space-y-2">
                    @foreach($salones as $salon)
                        <button wire:click="cambiarSalon({{ $salon->codigo }})"
                                @click="mostrarSelectorSalon = false"
                                class="w-full text-left px-4 py-4 rounded-lg transition-all border-2
                                       {{ $salonActual == $salon->codigo
                                          ? 'bg-yellow-50 border-[#FFAF22] shadow-md'
                                          : 'border-gray-200 hover:border-[#FFAF22] hover:bg-yellow-50' }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center
                                                {{ $salonActual == $salon->codigo ? 'bg-[#FFAF22] text-white' : 'bg-gray-200 text-gray-600' }}">
                                        <i class="fa fa-building"></i>
                                    </div>
                                    <span class="font-semibold text-gray-800 {{ $salonActual == $salon->codigo ? 'text-[#FFAF22]' : '' }}">
                                        {{ $salon->nombre }}
                                    </span>
                                </div>
                                @if($salonActual == $salon->codigo)
                                    <div class="w-6 h-6 bg-[#FFAF22] rounded-full flex items-center justify-center">
                                        <i class="fa fa-check text-white text-xs"></i>
                                    </div>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
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
    <div class="flex-1 overflow-y-auto p-2 pb-28" style="padding-bottom: calc(7rem + env(safe-area-inset-bottom)); -webkit-overflow-scrolling: touch; overscroll-behavior-y: contain;">
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
                        @if($mesa->nombre_mozo && $mesa->total > 0)
                            <div class="text-[10px] text-gray-500 truncate mt-0.5" title="{{ $mesa->nombre_mozo }}">
                                <i class="fas fa-user-tie mr-0.5"></i>{{ $mesa->nombre_mozo }}
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
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="grid grid-cols-5 gap-1 p-2">
            <!-- Selector de Salón -->
            <button @click="mostrarSelectorSalon = true"
                    class="flex flex-col items-center justify-center py-2 rounded-lg transition bg-[#FFAF22] text-white hover:bg-[#E89B0A]">
                <i class="fa fa-building text-base mb-0.5"></i>
                <span class="text-xs font-semibold truncate w-full px-1">
                    @foreach($salones as $salon)
                        @if($salon->codigo == $salonActual){{ $salon->nombre }}@endif
                    @endforeach
                </span>
            </button>

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
            <button @click="mostrarConfirmacionSalir = true"
                    class="flex flex-col items-center justify-center py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-yellow-50 hover:text-[#FFAF22] transition">
                <i class="fa fa-sign-out-alt text-base mb-0.5"></i>
                <span class="text-xs font-semibold">Salir</span>
            </button>
        </div>
    </div>
</div>
