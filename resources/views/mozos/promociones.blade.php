<div class="flex flex-col h-full bg-gray-50">
    <!-- Header compacto -->
    <div class="bg-white shadow-sm p-3 flex-shrink-0">
        <h2 class="text-lg font-bold text-gray-800">Promociones</h2>
        <p class="text-xs text-gray-600">Mesa N° {{ $numeroMesa }}</p>
    </div>

    <!-- Área scrolleable -->
    <div class="flex-1 overflow-y-auto p-3 pb-20">
        @if(!$promoSeleccionada)
            <!-- Lista de Promociones -->
            @if(count($promociones) > 0)
                <div class="space-y-2">
                    @foreach($promociones as $promo)
                        <button wire:click="abrirPromo({{ $promo->codigo }})"
                                class="w-full text-left p-4 bg-white border-2 border-yellow-400 rounded-lg hover:bg-yellow-50 transition shadow-sm">
                            <div class="flex justify-between items-center">
                                <div class="flex-1">
                                    <h3 class="font-bold text-gray-800 text-base">{{ $promo->nombre }}</h3>
                                </div>
                                <div class="text-right ml-3">
                                    <div class="text-green-600 font-bold text-lg">${{ number_format($promo->precio, 2) }}</div>
                                    <i class="fa fa-chevron-right text-gray-400 text-sm"></i>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-full text-gray-400">
                    <i class="fa fa-tags text-5xl mb-3"></i>
                    <p class="text-sm">No hay promociones disponibles</p>
                </div>
            @endif
        @else
            <!-- Formulario de Selección de Opciones -->
            <div class="space-y-3">
                <!-- Unidades -->
                <div class="bg-white rounded-lg shadow-sm p-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-700">Unidades:</span>
                        <div class="flex items-center gap-3">
                            <button wire:click="$set('unidades', {{ max(1, $unidades - 1) }})"
                                    class="px-3 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                                <i class="fa fa-minus"></i>
                            </button>
                            <span class="text-xl font-bold w-12 text-center">{{ $unidades }}</span>
                            <button wire:click="$set('unidades', {{ $unidades + 1 }})"
                                    class="px-3 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Opciones por Renglón -->
                @foreach($renglones as $renglon)
                    <div class="bg-white rounded-lg shadow-sm p-3">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Opción {{ $renglon }}:
                        </label>
                        <select wire:model="selecciones.{{ $renglon }}"
                                class="w-full px-3 py-2 border-2 border-yellow-500 rounded-lg focus:ring-2 focus:ring-yellow-500 text-sm">
                            @foreach($opcionesPromo->where('renglon', $renglon) as $opcion)
                                <option value="{{ $opcion->codart }}">
                                    {{ $opcion->nombre }} - ${{ number_format($opcion->precio, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Barra inferior fija -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg">
        <div class="p-3">
            @if($promoSeleccionada)
                <!-- Botones cuando hay promoción seleccionada -->
                <div class="grid grid-cols-2 gap-2">
                    <button wire:click="cancelar"
                            class="flex items-center justify-center py-2 bg-gray-500 text-white rounded-lg font-semibold hover:bg-gray-600 transition">
                        <i class="fa fa-times mr-2"></i>
                        Cancelar
                    </button>
                    <button wire:click="agregarPromocion"
                            class="flex items-center justify-center py-2 bg-green-500 text-white rounded-lg font-semibold hover:bg-green-600 transition">
                        <i class="fa fa-check mr-2"></i>
                        Agregar
                    </button>
                </div>
            @else
                <!-- Botón volver cuando no hay promoción seleccionada -->
                <a href="{{ route('mozos.mesa', ['mesa' => $numeroMesa]) }}"
                   wire:navigate
                   class="flex items-center justify-center py-2 bg-gray-500 text-white rounded-lg font-semibold hover:bg-gray-600 transition w-full">
                    <i class="fa fa-arrow-left mr-2"></i>
                    Volver a la Mesa
                </a>
            @endif
        </div>
    </div>
</div>
