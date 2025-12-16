<div class="pb-8">

    {{-- Información del cliente --}}
    <div class="bg-white rounded shadow p-4">
        <div class="flex justify-between items-center mb-2 grid grid-cols-2 gap-2">
            <div class="space-x-2">
                <h2 class="font-semibold text-gray-700">{{ $cliente->NOMBRE.' ('.$cliente->CODIGO.')'}}</h2>
            </div>
            <div class="text-right">
                <p><span class="font-semibold">Pedido #: <b>{{ $pedido->id }}</b></span>
                <button wire:click="editarObservacion({{ $pedido->id }})" class="bg-yellow-500 hover:bg-yellow-600 text-white text-sm px-3 py-1 rounded shadow">
                    <i class="fa fa-comment"></i>
                </button>
                <button wire:click="editarFecha({{ $pedido->id }})" class="bg-orange-500 hover:bg-orange-600 text-white text-sm px-3 py-1 rounded shadow">
                    <i class="fa fa-calendar"></i>
                </button>
            </div>
        </div>
        <div x-data="{ mostrarInfo: false }">
            <!-- Header clickeable para expandir/contraer -->
            <button @click="mostrarInfo = !mostrarInfo" 
                    class="w-full flex items-center justify-between p-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                <span class="text-sm font-medium text-gray-700">Información del Cliente</span>
                <i class="fa text-gray-500 text-xs transition-transform duration-200"
                :class="mostrarInfo ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
            
            <!-- Contenido expandible -->
            <div x-show="mostrarInfo" class="mt-2 text-sm text-gray-700 space-y-1 bg-white p-3 rounded-lg border">            
                <p><span class="font-semibold">CUIT:</span> {{ $cliente->CUIT ?? '-' }}</p>
                <p><span class="font-semibold">Dirección:</span> {{ $cliente->DIRECCION ?? '-' }}</p>
                <p><span class="font-semibold">Teléfono:</span> {{ $cliente->TELEFONO ?? '-' }}</p>
            </div>
        </div>
    </div>
        @if($editandoObservacionId)
        <div class="fixed inset-0 backdrop-blur-xs z-40 flex items-start justify-center pt-20">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-2 relative border-1 border-gray-700 p-4">
                <button wire:click="$set('editandoObservacionId', null)" class="absolute top-2 right-2 text-gray-600 text-xl hover:text-red-600">&times;</button>

                <h2 class="text-lg font-semibold mb-2">Editar Observaciones {{$editandoObservacionId}}</h2>
                <textarea wire:model.defer="nuevaObservacion" rows="4" class="w-full border border-gray-300 rounded p-2 text-sm mb-4"></textarea>

                <div class="flex justify-end space-x-2">
                    <button wire:click="$set('editandoObservacionId', null)" class="px-4 py-2 bg-gray-300 text-gray-700 rounded">Cancelar</button>
                    <button wire:click="modificarObservacion({{ $editandoObservacionId }}, '{{ addslashes($nuevaObservacion) }}')" class="px-4 py-2 bg-yellow-500 text-white rounded">Guardar</button>
                </div>
            </div>
        </div>
    @endif

    @if($editandoFechaId)
        <div class="fixed inset-0 backdrop-blur-xs z-40 flex items-start justify-center pt-20">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-2 relative border-1 border-gray-700 p-4">
                <button wire:click="$set('editandoFechaId', null)" class="absolute top-2 right-2 text-gray-600 text-xl hover:text-red-600">&times;</button>

                <h2 class="text-lg font-semibold mb-2">Editar Fecha para</h2>
                <input type="date" wire:model.defer="nuevaFecha" class="w-full border border-gray-300 rounded px-4 py-2 text-sm mb-4">

                <div class="flex justify-end space-x-2">
                    <button wire:click="$set('editandoFechaId', null)" class="px-4 py-2 bg-gray-300 text-gray-700 rounded">Cancelar</button>
                    <button wire:click="modificarFechaPara({{ $editandoFechaId }}, '{{ $nuevaFecha }}')" class="px-4 py-2 bg-yellow-500 text-white rounded">Guardar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Buscador de productos --}}
    <div class="bg-white rounded shadow p-4">
        <h2 class="font-semibold text-gray-700 mb-2">Buscar productos</h2>
        <input type="text" wire:model.live.debounce.300ms="busqueda" placeholder="Buscar por nombre o código..."
            class="w-full border rounded px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 shadow" />
        @if($productos)
            <ul class="mt-2 divide-y max-h-64 overflow-y-auto border border-gray-200 rounded shadow">
                @foreach($productos as $producto)
                    <li class="p-3 hover:bg-gray-50">
                        <div class="flex justify-between items-start">
                            <!-- Información del producto -->
                            <div class="text-sm flex-1">
                                <div class="font-medium text-gray-800">{{ $producto->NOMBRE }}</div>
                                <div class="text-gray-500">Stock: {{ $producto->STOCKACT }}</div>
                            </div>
                            
                            <!-- Precio -->
                            <div class="text-sm text-green-600 font-semibold mx-4">
                                ${{ number_format($this->obtenerPrecioCliente($producto->CODIGO), 2) }}
                            </div>
                        </div>
                        
                        <!-- Selector de cantidad y botón agregar -->
                        <div class="flex items-center justify-end mt-3 space-x-3">
                            <!-- Selector de cantidad -->
                            <div class="flex items-center border border-gray-300 rounded-lg bg-white">
                                <button type="button" 
                                        wire:click="decrementarCantidad({{ $producto->CODIGO }})"
                                        class="px-3 py-1 text-gray-600 hover:bg-gray-100 hover:text-gray-800 transition-colors rounded-l-lg">
                                    -
                                </button>
                                <input type="number" 
                                    wire:model.live.debounce.300ms="cantidades.{{ $producto->CODIGO }}"
                                    min="0.01" 
                                    step="0.01"     
                                    value="{{ $cantidades[$producto->CODIGO] ?? '1.00' }}" 
                                    placeholder="1.00"                                   
                                    class="w-16 px-2 py-1 text-center font-medium border-l border-r border-gray-300 bg-white focus:outline-none focus:ring-1 focus:ring-yellow-400 focus:bg-yellow-50">
                                <button type="button" 
                                        wire:click="incrementarCantidad({{ $producto->CODIGO }})"
                                        class="px-3 py-1 text-gray-600 hover:bg-gray-100 hover:text-gray-800 transition-colors rounded-r-lg">
                                    +
                                </button>
                            </div>
                            <!-- Campo de descuento porcentual (solo si tiene permisos) -->
                            @if($vendedor->PERMITEDESC)
                                <div class="flex items-center">
                                    <span class="text-xs text-gray-600 mr-1">Desc:</span>
                                    <input type="number" 
                                        wire:model.live.debounce.300ms="descuentos.{{ $producto->CODIGO }}"
                                        min="0" 
                                        max="100"
                                        step="0.01"
                                        placeholder="0"
                                        class="w-16 px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-yellow-400">
                                    <span class="text-xs text-gray-500 ml-1">%</span>
                                </div>
                            @endif
                            <!-- Botón agregar -->
                            <button wire:click="agregarProducto({{ $producto->CODIGO }})"
                                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 text-sm rounded-lg shadow transition-colors">
                                Agregar
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-gray-500 mt-2">No se encontraron productos.</p>
        @endif
    </div>

    {{-- Detalle del pedido --}}
    <div class="bg-white rounded shadow p-1">
        <div class="flex justify-between items-center mb-2 border-b pb-2">
            <h2 class="font-semibold text-gray-700">Detalle del pedido</h2>
            <button class="relative bg-yellow-500 hover:bg-yellow-600 text-white text-xs px-3 py-1 rounded-full shadow">
                {{ count($detallePedido) }}                
            </button>
        </div>
        @php $total = 0; @endphp
        @if(count($detallePedido) > 0)
            <ul class="divide-y max-h-64 overflow-y-auto">
                @foreach($detallePedido as $detalle)
                    <li class="py-2 flex items-center justify-between text-sm text-gray-700">
                        <div class="flex-1">
                            {{ $detalle->detart }} x <span class="font-bold">{{ $detalle->cantidad }}</span>                            
                            @if($detalle->cantidad > 1)
                                <br>
                                <span class="text-gray-500 text-xs">P.Un.${{ number_format($detalle->punitario, 2) }}</span>
                            @endif
                            @if($detalle->descup > 0)
                                <span class="text-gray-500 text-xs"> (Desc. {{ number_format($detalle->descup, 2) }} %)</span>
                            @endif
                        </div>
                        <div class="text-right mr-4">
                            ${{ number_format($detalle->cantidad * $detalle->punitario , 2) }}
                        </div>
                        <div>
                            <button wire:click="eliminarProducto({{ $detalle->id }})"
                                    class="text-red-500 hover:text-red-600">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                        @php $total += $detalle->cantidad * $detalle->punitario; @endphp
                    </li>
                @endforeach
            </ul>
        @else
            <div class="text-center text-gray-400 text-sm py-6">
                <i class="fa fa-shopping-cart text-2xl mb-2"></i><br>
                No hay productos en el pedido
            </div>
        @endif
    </div>

    {{-- Totales y acciones --}}
    <div class="bg-white rounded shadow p-4 flex flex-col sm:flex-row justify-between items-center">
        <div class="text-sm text-gray-700 w-full sm:w-1/2">
            <p class="font-bold text-lg text-gray-800">Total: ${{ number_format($total, 2) }}</p>
        </div>
        <div class="w-full sm:w-1/2 flex justify-end mt-4 sm:mt-0 space-x-2">            
            <a href="{{route('dashboard-vendedor')}}" wire:navigate
                    class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2 px-4 rounded shadow">
                Guardar Pedido
            </a>
        </div>
    </div>
</div>
