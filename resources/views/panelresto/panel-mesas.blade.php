<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-primary font-bold text-gray-900 flex items-center">
                    <i class="fas fa-utensils text-orange-500 mr-3"></i>
                    Panel de Mesas
                </h1>
                <div class="flex items-center space-x-4">
                    <button wire:click="actualizarMesas" 
                            class="flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors duration-200 font-secondary text-sm">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Actualizar
                    </button>
                    <div class="text-sm text-gray-500 font-secondary">
                        {{ now()->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs de Salones -->
    @if(count($salones) > 0)
    <div class="bg-white border-b border-gray-200">
        <div class="container mx-auto px-4">
            <nav class="flex space-x-8 overflow-x-auto">
                @foreach($salones as $salon)
                <button wire:click="seleccionarSalon({{ $salon->codigo }})"
                        class="flex-shrink-0 py-4 px-1 border-b-2 font-secondary font-medium text-sm transition-colors duration-200 {{ $salonActivo == $salon->codigo ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas fa-door-open mr-2"></i>
                    {{ $salon->nombre }}
                </button>
                @endforeach
            </nav>
        </div>
    </div>
    @endif

    <!-- Estadísticas del Salón -->
    @if($salonActivo)
    <div class="bg-white border-b border-gray-200">
        <div class="container mx-auto px-4 py-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-full mx-auto mb-2">
                        <i class="fas fa-check text-green-600 text-xl"></i>
                    </div>
                    <div class="text-2xl font-primary font-bold text-green-600">{{ $this->getContadorMesasPorEstado('libre') }}</div>
                    <div class="text-sm font-secondary text-gray-600">Libres</div>
                </div>
                <div class="text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-red-100 rounded-full mx-auto mb-2">
                        <i class="fas fa-users text-red-600 text-xl"></i>
                    </div>
                    <div class="text-2xl font-primary font-bold text-red-600">{{ $this->getContadorMesasPorEstado('ocupada') }}</div>
                    <div class="text-sm font-secondary text-gray-600">Ocupadas</div>
                </div>
                <div class="text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-blue-800 rounded-full mx-auto mb-2">
                        <i class="fas fa-clock text-blue-800 text-xl"></i>
                    </div>
                    <div class="text-2xl font-primary font-bold text-blue-800">{{ $this->getContadorMesasPorEstado('pronto_cierre') }}</div>
                    <div class="text-sm font-secondary text-gray-600">Pronto Cierre</div>
                </div>
                <div class="text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-yellow-100 rounded-full mx-auto mb-2">
                        <i class="fas fa-bookmark text-yellow-600 text-xl"></i>
                    </div>
                    <div class="text-2xl font-primary font-bold text-yellow-600">{{ $this->getContadorMesasPorEstado('reservada') }}</div>
                    <div class="text-sm font-secondary text-gray-600">Reservadas</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Layout Principal -->
    <div class="flex h-[calc(100vh-280px)]">
        <!-- Panel de Mesas -->
        <div class="{{ $mostrarDetalle ? 'w-2/3' : 'w-full' }} transition-all duration-300 overflow-hidden">
            <div class="h-full overflow-y-auto p-6">
                @if($salonActivo && count($mesas) > 0)
                <!-- Grid de Mesas -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-2">
                    @foreach($mesas as $mesa)
                    <div wire:click="seleccionarMesa({{ $mesa->NUMERO }})" 
                         class="relative cursor-pointer transform hover:scale-105 transition-all duration-200 group">
                        
                        <!-- Mesa Card -->
                        <div class="relative {{ $this->getColorMesa($mesa->RECURSO) }} rounded-xl p-4 shadow-lg border-2 hover:shadow-xl">
                            
                            
                            <!-- Número de Mesa -->
                            <div class="text-center">
                                <div class="text-lg font-primary font-bold {{ $this->getColorTextoMesa($mesa->RECURSO) }}">
                                    Mesa {{ $mesa->NUMERO }}
                                </div>
                                
                                
                            </div>
                            
                            
                        </div>
                        
                        
                    </div>
                    @endforeach
                </div>
                
                @elseif($salonActivo)
                <!-- Sin Mesas -->
                <div class="text-center py-12">
                    <i class="fas fa-utensils text-gray-400 text-5xl mb-4"></i>
                    <h3 class="text-lg font-primary font-medium text-gray-900 mb-2">No hay mesas en este salón</h3>
                    <p class="text-gray-600 font-secondary">El salón "{{ $this->getSalonNombre($salonActivo) }}" no tiene mesas configuradas</p>
                </div>
                
                @else
                <!-- Sin Salón Seleccionado -->
                <div class="text-center py-12">
                    <i class="fas fa-door-open text-gray-400 text-5xl mb-4"></i>
                    <h3 class="text-lg font-primary font-medium text-gray-900 mb-2">Seleccione un salón</h3>
                    <p class="text-gray-600 font-secondary">Elija un salón de las pestañas superiores para ver sus mesas</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Panel de Detalle de Mesa -->
        @if($mostrarDetalle)
        <div class="w-1/3 bg-white border-l border-gray-200 shadow-lg overflow-hidden">
            <div class="h-full flex flex-col">
                <!-- Header del Panel -->
                <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-primary font-semibold text-gray-900 flex items-center">
                        <i class="{{ $this->getIconoMesa($mesaSeleccionada->RECURSO) }} text-orange-500 mr-2"></i>
                        Mesa {{ $mesaSeleccionada->NUMERO }}
                    </h3>
                    <button wire:click="cerrarDetalle" class="text-gray-400 hover:text-gray-600 p-1">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Información de la Mesa -->
                <div class="p-4 border-b border-gray-200 bg-gray-50">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-secondary font-medium text-gray-600">Tipo:</span>
                            <span class="font-secondary text-gray-900 ml-1">{{ $this->getTipoMesa($mesaSeleccionada->RECURSO) }}</span>
                        </div>
                        <div>
                            <span class="font-secondary font-medium text-gray-600">Estado:</span>
                            <span class="font-secondary ml-1 px-2 py-1 rounded-full text-xs {{ $this->getColorMesa($mesaSeleccionada->RECURSO) }} {{ $this->getColorTextoMesa($mesaSeleccionada->RECURSO) }}">
                                {{ $this->getEstadoMesa($mesaSeleccionada->RECURSO) }}
                            </span>
                        </div>
                        <div>
                            <span class="font-secondary font-medium text-gray-600">Comensales:</span>
                            <span class="font-secondary text-gray-900 ml-1">{{ $mesaSeleccionada->COMENSALES ?: 'Sin comensales' }}</span>
                        </div>
                        <div>
                            <span class="font-secondary font-medium text-gray-600">Salón:</span>
                            <span class="font-secondary text-gray-900 ml-1">{{ $this->getSalonNombre($mesaSeleccionada->salon) }}</span>
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
                        <div class="p-4 hover:bg-gray-50 transition-colors duration-200">
                            <div class="flex justify-between items-start mb-2">
                                <h5 class="font-secondary font-medium text-gray-900 flex-1">
                                    {{ $item->NOMART }}
                                    @if($item->IMPRESA)
                                        <i class="fas fa-print text-green-500 ml-2 text-sm" title="Impreso"></i>
                                    @endif
                                </h5>
                                <span class="font-secondary font-bold text-gray-900 ml-2">
                                    ${{ number_format($item->TOTAL, 2) }}
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2 text-sm text-gray-600">
                                <div>
                                    <span class="font-medium">Cantidad:</span>
                                    <span class="ml-1">{{ $item->CANTIDAD }}</span>
                                </div>
                                <div>
                                    <span class="font-medium">Precio Unit:</span>
                                    <span class="ml-1">${{ number_format($item->PUNITARIO, 2) }}</span>
                                </div>
                            </div>
                            
                            @if($item->caracteristicas)
                            <div class="mt-2 text-sm text-blue-600">
                                <i class="fas fa-info-circle mr-1"></i>
                                {{ $item->caracteristicas }}
                            </div>
                            @endif
                            
                            @if($item->observa)
                            <div class="mt-2 text-sm text-amber-600">
                                <i class="fas fa-comment mr-1"></i>
                                {{ $item->observa }}
                            </div>
                            @endif
                            
                            @if($item->seleccion)
                            <div class="mt-2 text-sm text-purple-600">
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
                        <span class="font-secondary font-bold text-gray-900">TOTAL:</span>
                        <span class="font-primary font-black text-xl text-orange-600">
                            ${{ number_format($totalMesa, 2) }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-600 font-secondary mt-1">
                        {{ count($detalleMesa) }} {{ count($detalleMesa) == 1 ? 'item' : 'items' }}
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Auto-actualización cada 30 segundos -->
<script>
document.addEventListener('livewire:initialized', () => {
    setInterval(() => {
        @this.call('actualizarMesas');
    }, 10000); // 10 segundos
});
</script>