<div class="flex flex-col h-full bg-gray-50">
    <!-- Header compacto -->
    <div class="bg-white shadow-sm p-3 flex-shrink-0">
        <h2 class="text-lg font-bold text-gray-800">{{ $producto->nombre }}</h2>
        @if($producto->precio > 0.01)
            <p class="text-sm text-green-600 font-semibold">${{ number_format($producto->precio, 2) }}</p>
        @endif
        @if($producto->observa)
            <p class="text-xs text-gray-600 mt-1">{{ $producto->observa }}</p>
        @endif
    </div>

    <!-- Área scrolleable -->
    <div class="flex-1 overflow-y-auto p-3 pb-36">
        <!-- Cantidad (si no es solo_unitario) -->
        @if(!$cambiar)
            <div class="bg-white rounded-lg shadow-sm p-3 mb-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-gray-700">Cantidad:</span>
                    <div class="flex items-center gap-3">
                        <button wire:click="decrementarCantidad"
                                class="px-3 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                            <i class="fa fa-minus"></i>
                        </button>
                        <span class="text-xl font-bold w-12 text-center">{{ $cantidad }}</span>
                        <button wire:click="incrementarCantidad"
                                class="px-3 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Opcionales -->
        @foreach($opcionales as $grupo => $items)
            @php $primerItem = $items->first(); @endphp
            <div class="bg-white rounded-lg shadow-sm p-3 mb-3 {{ isset($erroresGrupos[$grupo]) ? 'border-2 border-red-500' : '' }}">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-bold text-gray-800 text-sm">{{ $primerItem->nomgru }}</h3>
                    <span class="text-xs {{ $primerItem->obligatorio ? 'text-red-600' : 'text-gray-500' }} font-semibold">
                        {{ $primerItem->obligatorio ? 'Obligatorio' : 'Opcional' }}
                    </span>
                </div>
                <p class="text-xs text-gray-600 mb-2">
                    @if($primerItem->minimo > 1) Mín {{ $primerItem->minimo }} - @endif
                    Máx {{ $primerItem->maximo }}
                </p>

                @if(isset($erroresGrupos[$grupo]))
                    <div class="bg-red-50 border border-red-300 text-red-700 px-2 py-1 rounded mb-2 text-xs flex items-center gap-1">
                        <i class="fa fa-exclamation-circle"></i>
                        <span>{{ $erroresGrupos[$grupo] }}</span>
                    </div>
                @endif

                @if($primerItem->por_cantidad)
                    <!-- Opcionales con cantidad -->
                    @foreach($items->values() as $index => $item)
                        @if(!$item->agotado_opc)
                            <div class="flex items-center justify-between py-2 border-b last:border-b-0">
                                <span class="text-sm text-gray-700 flex-1">{{ $item->nomopc }}</span>
                                <div class="flex items-center gap-2">
                                    @if($item->precio_opc > 0)
                                        <span class="text-xs text-green-600 font-semibold">${{ number_format($item->precio_opc, 2) }}</span>
                                    @endif
                                    <div class="flex items-center gap-1">
                                        <button wire:click="decrementarOpcional({{ $grupo }}, {{ $index }})"
                                                class="px-2 py-1 bg-gray-200 rounded text-sm hover:bg-gray-300">-</button>
                                        <span class="w-6 text-center font-bold text-sm">{{ $cantidades[$grupo . '_' . $index] ?? 0 }}</span>
                                        <button wire:click="incrementarOpcional({{ $grupo }}, {{ $index }})"
                                                class="px-2 py-1 bg-gray-200 rounded text-sm hover:bg-gray-300">+</button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                @else
                    <!-- Opcionales checkbox -->
                    @foreach($items as $item)
                        @if($item->nomopc && !$item->agotado_opc)
                            <label class="flex items-center justify-between py-2 border-b last:border-b-0 cursor-pointer hover:bg-gray-50">
                                <div class="flex items-center gap-2 flex-1">
                                    <input type="checkbox"
                                           wire:model.live="seleccionados.{{ $grupo }}"
                                           value="{{ $item->iddet }}"
                                           class="w-4 h-4 text-yellow-500 rounded">
                                    <span class="text-sm text-gray-700">{{ $item->nomopc }}</span>
                                </div>
                                @if($item->precio_opc > 0)
                                    <span class="text-xs text-green-600 font-semibold">${{ number_format($item->precio_opc, 2) }}</span>
                                @endif
                            </label>
                        @endif
                    @endforeach
                @endif
            </div>
        @endforeach

        <!-- Observaciones -->
        <div class="bg-white rounded-lg shadow-sm p-3">
            <label class="block text-sm text-gray-700 font-semibold mb-2">Observaciones:</label>
            <textarea wire:model="observaciones"
                      class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-yellow-500 text-sm"
                      rows="2"
                      placeholder="Ej: Sin cebolla..."></textarea>
        </div>

        <!-- Mensajes de error -->
        @if(session()->has('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg mt-3 text-sm">
                {{ session('error') }}
            </div>
        @endif
    </div>

    <!-- Barra inferior fija con total y botones -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg">
        <div class="p-3">
            <!-- Total -->
            <div class="flex justify-between items-center mb-3 pb-3 border-b">
                <span class="text-base font-bold text-gray-800">Total:</span>
                <span class="text-xl font-bold text-green-600">${{ number_format($total, 2) }}</span>
            </div>

            <!-- Botones de acción -->
            <div class="grid grid-cols-2 gap-2">
                <a href="{{ route('mozos.mesa', ['mesa' => $numeroMesa]) }}"
                   wire:navigate
                   class="flex items-center justify-center py-2 bg-gray-500 text-white rounded-lg font-semibold hover:bg-gray-600 transition">
                    <i class="fa fa-times mr-2"></i>
                    Cancelar
                </a>
                <button wire:click="validarYGuardar"
                        class="flex items-center justify-center py-2 bg-green-500 text-white rounded-lg font-semibold hover:bg-green-600 transition">
                    <i class="fa fa-check mr-2"></i>
                    Agregar
                </button>
            </div>
        </div>
    </div>
</div>
