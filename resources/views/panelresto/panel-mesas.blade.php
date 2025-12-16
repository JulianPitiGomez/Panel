<div class="min-h-screen bg-gray-50">
    <!-- Header Móvil Optimizado -->
    <div class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-10">
        <div class="container mx-auto px-3 py-3">
            <div class="flex items-center justify-between">
                <h1 class="text-lg md:text-2xl font-primary font-bold text-gray-900 flex items-center">
                    <i class="fas fa-utensils text-orange-500 mr-2 text-sm md:text-base"></i>
                    <span class="hidden sm:inline">Panel de Mesas</span>
                    <span class="sm:hidden">Mesas</span>
                </h1>
                <div class="flex items-center gap-2">
                    <button wire:click="actualizarMesas"
                            class="flex items-center px-2 md:px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors duration-200 font-secondary text-xs md:text-sm">
                        <i class="fas fa-sync-alt md:mr-2"></i>
                        <span class="hidden md:inline">Actualizar</span>
                    </button>
                    <div class="hidden md:block text-xs md:text-sm text-gray-500 font-secondary">
                        {{ now()->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs de Salones -->
    @if(count($salones) > 0)
    <div class="bg-white border-b border-gray-200 sticky top-[52px] md:top-[68px] z-10">
        <div class="container mx-auto px-3">
            <nav class="flex space-x-4 md:space-x-8 overflow-x-auto">
                @foreach($salones as $salon)
                <button wire:click="seleccionarSalon({{ $salon->codigo }})"
                        class="flex-shrink-0 py-3 px-1 border-b-2 font-secondary font-medium text-xs md:text-sm transition-colors duration-200 {{ $salonActivo == $salon->codigo ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas fa-door-open mr-1"></i>
                    {{ $salon->nombre }}
                </button>
                @endforeach
            </nav>
        </div>
    </div>
    @endif

    <!-- Estadísticas del Salón - Compacto para Móvil -->
    @if($salonActivo)
    <div class="bg-white border-b border-gray-200">
        <div class="container mx-auto px-3 py-3">
            <div class="grid grid-cols-4 gap-2">
                <div class="text-center">
                    <div class="flex items-center justify-center w-8 h-8 md:w-12 md:h-12 bg-green-100 rounded-full mx-auto mb-1">
                        <i class="fas fa-cutlery text-green-600 text-xs md:text-base"></i>
                    </div>
                    <div class="text-sm md:text-base font-primary font-bold text-green-600">{{ $this->getContadorMesasPorEstado('libre') }}</div>
                    <div class="text-[10px] md:text-sm font-secondary text-gray-600">Libres</div>
                </div>
                <div class="text-center">
                    <div class="flex items-center justify-center w-8 h-8 md:w-12 md:h-12 bg-red-100 rounded-full mx-auto mb-1">
                        <i class="fas fa-users text-red-600 text-xs md:text-base"></i>
                    </div>
                    <div class="text-sm md:text-base font-primary font-bold text-red-600">{{ $this->getContadorMesasPorEstado('ocupada') }}</div>
                    <div class="text-[10px] md:text-sm font-secondary text-gray-600">Ocupadas</div>
                </div>
                <div class="text-center">
                    <div class="flex items-center justify-center w-8 h-8 md:w-12 md:h-12 bg-blue-200 rounded-full mx-auto mb-1">
                        <i class="fas fa-dollar text-blue-800 text-xs md:text-base"></i>
                    </div>
                    <div class="text-sm md:text-base font-primary font-bold text-blue-800">{{ $this->getContadorMesasPorEstado('pronto_cierre') }}</div>
                    <div class="text-[10px] md:text-sm font-secondary text-gray-600 leading-tight">Pronto<br class="md:hidden"> Cierre</div>
                </div>
                <div class="text-center">
                    <div class="flex items-center justify-center w-8 h-8 md:w-12 md:h-12 bg-yellow-100 rounded-full mx-auto mb-1">
                        <i class="fas fa-bookmark text-yellow-600 text-xs md:text-base"></i>
                    </div>
                    <div class="text-sm md:text-base font-primary font-bold text-yellow-600">{{ $this->getContadorMesasPorEstado('reservada') }}</div>
                    <div class="text-[10px] md:text-sm font-secondary text-gray-600">Reservadas</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Layout Principal: Grid de Mesas + Panel de Detalle -->
    <div class="container mx-auto px-3 py-4">
        <div class="flex gap-4 lg:gap-6">
            <!-- Grid de Mesas -->
            <div class="{{ $mostrarDetalle ? 'hidden lg:block lg:w-2/3' : 'w-full' }} transition-all duration-300">
                @if($salonActivo && count($mesas) > 0)
                <!-- Grid Responsive -->
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 {{ $mostrarDetalle ? 'lg:grid-cols-4 xl:grid-cols-5' : 'lg:grid-cols-8 xl:grid-cols-10' }} gap-2">
                    @foreach($mesas as $mesa)
                    <div wire:click="seleccionarMesa({{ $mesa->NUMERO }})"
                         class="relative cursor-pointer transform hover:scale-105 transition-all duration-200 active:scale-95">

                        <!-- Mesa Card -->
                        <div class="relative {{ $this->getColorMesa($mesa->RECURSO) }} rounded-xl p-3 md:p-4 shadow-lg border-2 hover:shadow-xl">
                            <!-- Número de Mesa -->
                            <div class="text-center">
                                <div class="text-xs md:text-sm lg:text-base font-primary font-bold {{ $this->getColorTextoMesa($mesa->RECURSO) }}">
                                    Mesa<br class="hidden sm:inline">
                                    <span class="text-base md:text-lg lg:text-xl">{{ $mesa->NUMERO }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                @elseif($salonActivo)
                <!-- Sin Mesas -->
                <div class="text-center py-12">
                    <i class="fas fa-utensils text-gray-400 text-4xl md:text-5xl mb-4"></i>
                    <h3 class="text-base md:text-lg font-primary font-medium text-gray-900 mb-2">No hay mesas en este salón</h3>
                    <p class="text-sm text-gray-600 font-secondary">El salón "{{ $this->getSalonNombre($salonActivo) }}" no tiene mesas configuradas</p>
                </div>

                @else
                <!-- Sin Salón Seleccionado -->
                <div class="text-center py-12">
                    <i class="fas fa-door-open text-gray-400 text-4xl md:text-5xl mb-4"></i>
                    <h3 class="text-base md:text-lg font-primary font-medium text-gray-900 mb-2">Seleccione un salón</h3>
                    <p class="text-sm text-gray-600 font-secondary">Elija un salón de las pestañas superiores para ver sus mesas</p>
                </div>
                @endif
            </div>

            <!-- Panel de Detalle de Mesa - Modal en Móvil, Sidebar en Desktop -->
            @if($mostrarDetalle)
            <!-- Overlay móvil -->
            <div wire:click="cerrarDetalle"
                 class="lg:hidden fixed inset-0 bg-black bg-opacity-50 transition-opacity z-40"></div>

            <!-- Panel -->
            <div class="fixed inset-x-0 bottom-0 lg:relative lg:inset-auto lg:w-1/3 bg-white border-gray-200 shadow-lg
                        rounded-t-2xl lg:rounded-xl z-50 lg:z-auto">
                <div class="h-[80vh] lg:h-auto lg:max-h-[calc(100vh-200px)] flex flex-col">
                <!-- Header del Panel -->
                <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-base md:text-lg font-primary font-semibold text-gray-900 flex items-center">
                        <i class="{{ $this->getIconoMesa($mesaSeleccionada->RECURSO) }} text-orange-500 mr-2 text-sm md:text-base"></i>
                        Mesa {{ $mesaSeleccionada->NUMERO }}
                    </h3>
                    <button wire:click="cerrarDetalle" class="text-gray-400 hover:text-gray-600 p-1">
                        <i class="fas fa-times text-lg md:text-xl"></i>
                    </button>
                </div>

                <!-- Información de la Mesa -->
                <div class="p-4 border-b border-gray-200 bg-gray-50">
                    <div class="grid grid-cols-2 gap-3 text-xs md:text-sm">
                        <div>
                            <span class="font-secondary px-2 py-1 rounded-full text-xs {{ $this->getColorMesa($mesaSeleccionada->RECURSO) }} {{ $this->getColorTextoMesa($mesaSeleccionada->RECURSO) }}">
                                {{ $this->getEstadoMesa($mesaSeleccionada->RECURSO) }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="font-secondary font-medium text-gray-600">Comensales:</span>
                            <span class="font-secondary text-gray-900 ml-1">{{ $mesaSeleccionada->COMENSALES ?: '-' }}</span>
                        </div>

                        @if($mesaSeleccionada->fechaaper)
                        <div class="col-span-2">
                            <span class="font-secondary font-medium text-gray-600">Apertura:</span>
                            <span class="font-secondary text-gray-900 ml-1">
                                {{ \Carbon\Carbon::parse($mesaSeleccionada->fechaaper)->format('d/m/Y') }}
                                @if($mesaSeleccionada->horaaper)
                                    {{ $mesaSeleccionada->horaaper }}
                                @endif
                            </span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Contenido del Panel - Detalle de Consumo -->
                <div class="flex-1 overflow-y-auto">
                    @if(count($detalleMesa) > 0)
                    <!-- Lista de Items -->
                    <div class="divide-y divide-gray-200">
                        @foreach($detalleMesa as $item)
                        <div class="p-3 md:p-4 hover:bg-gray-50 transition-colors duration-200">
                            <div class="flex justify-between items-start mb-2">
                                <h5 class="font-secondary font-medium text-gray-900 flex-1 text-sm md:text-base">
                                    {{ $item->NOMART }}
                                    @if($item->IMPRESA)
                                        <i class="fas fa-print text-green-500 ml-2 text-xs" title="Impreso"></i>
                                    @endif
                                </h5>
                                <span class="font-secondary font-bold text-gray-900 ml-2 text-sm md:text-base">
                                    ${{ number_format($item->TOTAL, 2) }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs md:text-sm text-gray-600">
                                <div>
                                    <span class="font-medium">Cant:</span>
                                    <span class="ml-1">{{ $item->CANTIDAD }}</span>
                                </div>
                                <div>
                                    <span class="font-medium">P.Unit:</span>
                                    <span class="ml-1">${{ number_format($item->PUNITARIO, 2) }}</span>
                                </div>
                            </div>

                            @if($item->caracteristicas)
                            <div class="mt-2 text-xs text-blue-600">
                                <i class="fas fa-info-circle mr-1"></i>
                                {{ $item->caracteristicas }}
                            </div>
                            @endif

                            @if($item->observa)
                            <div class="mt-2 text-xs text-amber-600">
                                <i class="fas fa-comment mr-1"></i>
                                {{ $item->observa }}
                            </div>
                            @endif

                            @if($item->seleccion)
                            <div class="mt-2 text-xs text-purple-600">
                                <i class="fas fa-list mr-1"></i>
                                {{ $item->seleccion }}
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    @else
                    <div class="text-center py-8">
                        <i class="fas fa-receipt text-gray-400 text-2xl mb-2"></i>
                        <p class="text-gray-600 font-secondary text-sm">Sin consumos registrados</p>
                    </div>
                    @endif
                </div>

                <!-- Footer del Panel - Total -->
                @if(count($detalleMesa) > 0)
                <div class="border-t border-gray-200 p-4 bg-gray-50">
                    <div class="flex justify-between items-center">
                        <span class="font-secondary font-bold text-gray-900 text-sm md:text-base">TOTAL:</span>
                        <span class="font-primary font-black text-lg md:text-xl text-orange-600">
                            ${{ number_format($totalMesa, 2) }}
                        </span>
                    </div>
                    <div class="text-xs md:text-sm text-gray-600 font-secondary mt-1">
                        {{ count($detalleMesa) }} {{ count($detalleMesa) == 1 ? 'item' : 'items' }}
                    </div>
                </div>
                @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Auto-actualización cada 10 segundos -->
<script>
document.addEventListener('livewire:initialized', () => {
    setInterval(() => {
        @this.call('actualizarMesas');
    }, 10000);
});
</script>
