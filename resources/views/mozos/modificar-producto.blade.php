<div class="flex flex-col h-full bg-gray-50">
    <!-- Header compacto -->
    <div class="bg-white shadow-sm p-3 flex-shrink-0">
        <h2 class="text-lg font-bold text-gray-800">{{ $producto->nombre }}</h2>
        <p class="text-sm text-green-600 font-semibold">${{ number_format($producto->precio, 2) }}</p>
    </div>

    <!-- Área scrolleable -->
    <div class="flex-1 overflow-y-auto p-3 pb-20">
        <!-- Cantidad -->
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

        <!-- Observaciones -->
        <div class="bg-white rounded-lg shadow-sm p-3">
            <label class="block text-sm text-gray-700 font-semibold mb-2">Observaciones:</label>
            <textarea wire:model="observaciones"
                      class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-yellow-500 text-sm"
                      rows="3"></textarea>
        </div>
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
                <button wire:click="guardar"
                        class="flex items-center justify-center py-2 bg-blue-500 text-white rounded-lg font-semibold hover:bg-blue-600 transition">
                    <i class="fa fa-save mr-2"></i>
                    Guardar
                </button>
            </div>
        </div>
    </div>
</div>
