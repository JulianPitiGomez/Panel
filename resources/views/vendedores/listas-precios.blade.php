<div class="px-1 pb-8" x-data="{ mostrarFiltros: false }">
    <!-- Header -->
    <div class="bg-[#222036] text-white rounded-lg p-4 mb-4 mt-3 shadow-md">
        <h2 class="text-lg font-semibold">LISTAS DE PRECIOS</h2>
        <p class="text-sm">Consulta precios de listas y especiales</p>
    </div>
    <div class="bg-white flex py-2 flex-col sm:flex-row sm:items-center rounded-t sm:justify-between">
        <!-- Botones de acción -->
        <div class="flex space-x-2 mt-4 mb-2 sm:mt-0">
            <button @click="mostrarFiltros = !mostrarFiltros" 
                    class="inline-flex items-center px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700  rounded-lg border border-gray-300 transition-all duration-200"
                    :class="{ 'bg-orange-100 border-orange-300 text-orange-700': mostrarFiltros }">
                <i class="fas fa-filter text-sm transition-transform duration-200" 
                    :class="{ 'text-orange-600': mostrarFiltros }"></i>
                <span class="ml-2 text-sm">Filtros</span>
                <i class="fas fa-chevron-down ml-2 text-xs transition-transform duration-200" 
                    :class="{ 'rotate-180': mostrarFiltros }"></i>
            </button>
            
            <button wire:click="imprimirLista" 
                    class="inline-flex items-center px-2 py-1 bg-orange-500 hover:bg-orange-600 text-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                <i class="fas fa-print mr-2"></i>
                Imprimir Lista
            </button>
        </div>
    </div>

    <!-- Panel de filtros colapsable -->
    <div x-show="mostrarFiltros" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2"
         class="mb-6">
        
        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mb-4">
                <!-- Búsqueda -->
                <div class="sm:col-span-2">
                    <label for="busqueda" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-search mr-1 text-orange-500"></i>Buscar Producto
                    </label>
                    <input type="text" 
                           wire:model.live.debounce.300ms="busqueda" 
                           id="busqueda"
                           placeholder="Buscar por código o nombre..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all duration-200">
                </div>

                <!-- Fecha Desde -->
                <div>
                    <label for="fechaDesde" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar-alt mr-1 text-orange-500"></i>Modificado Desde
                    </label>
                    <input type="date" 
                           wire:model.live.debounce.300ms="fechaDesde" 
                           id="fechaDesde"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all duration-200">
                </div>

                <!-- Fecha Hasta -->
                <div>
                    <label for="fechaHasta" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar-alt mr-1 text-orange-500"></i>Modificado Hasta
                    </label>
                    <input type="date" 
                           wire:model.live.debounce.300ms="fechaHasta" 
                           id="fechaHasta"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all duration-200">
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-4">
                <!-- Rubro -->
                <div>
                    <label for="rubro" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tag mr-1 text-orange-500"></i>Rubro
                    </label>
                    <select wire:model.live="rubro" 
                            id="rubro"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="">Todos los rubros</option>
                        @foreach($rubros as $rubro)
                            <option value="{{ $rubro->CODIGO }}">{{ $rubro->NOMBRE }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Marca -->
                <div>
                    <label for="marca" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-copyright mr-1 text-orange-500"></i>Marca
                    </label>
                    <select wire:model.live="marca" 
                            id="marca"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="">Todas las marcas</option>
                        @foreach($marcas as $marca)
                            <option value="{{ $marca->CODIGO }}">{{ $marca->NOMBRE }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Departamento -->
                <div>
                    <label for="depto" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-building mr-1 text-orange-500"></i>Departamento
                    </label>
                    <select wire:model.live="depto" 
                            id="depto"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="">Todos los deptos</option>
                        @foreach($deptos as $depto)
                            <option value="{{ $depto->CODIGO }}">{{ $depto->NOMBRE }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Proveedor -->
                <div>
                    <label for="proveedor" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-truck mr-1 text-orange-500"></i>Proveedor
                    </label>
                    <select wire:model.live="proveedor" 
                            id="proveedor"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="">Todos los proveedores</option>
                        @foreach($proveedores as $proveedor)
                            <option value="{{ $proveedor->CODIGO }}">{{ $proveedor->NOMBRE }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Lista Especial -->
                <div>
                    <label for="listaEspecial" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-list mr-1 text-orange-500"></i>Lista Especial
                    </label>
                    <select wire:model.live="listaEspecial" 
                            id="listaEspecial"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="">Precios normales</option>
                        @foreach($listasEspeciales as $lista)
                            <option value="{{ $lista->CODIGO }}">{{ $lista->NOMBRE }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Botón limpiar filtros -->
            <div class="flex justify-end">
                <button wire:click="limpiarFiltros" 
                        class="inline-flex items-center px-3 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium rounded-md transition-colors duration-200">
                    <i class="fas fa-eraser mr-2"></i>
                    Limpiar Filtros
                </button>
            </div>
        </div>
    </div>

    <!-- Tabla de productos -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <!-- Header -->
                <thead style="background-color: #222036;" class="text-white">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            <i class="fas fa-barcode mr-1"></i>
                            Producto
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider hidden sm:table-cell">
                            <i class="fas fa-tag mr-1"></i>
                            Categoría
                        </th>
                        <th class="px-3 py-3 text-right text-xs font-medium uppercase tracking-wider">
                            <i class="fas fa-dollar-sign mr-1"></i>
                            Precios
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider hidden md:table-cell">
                            <i class="fas fa-boxes mr-1"></i>
                            Stock
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider hidden lg:table-cell">
                            <i class="fas fa-clock mr-1"></i>
                            Actualizado
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($productos as $producto)
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <!-- Producto -->
                            <td class="px-3 py-4 whitespace-nowrap">
                                <div class="flex items-start">

                                    <div class="ml-3 min-w-0 flex-1">
                                        <div class="text-sm font-medium text-gray-900 truncate max-w-48 sm:max-w-none">
                                            {{ $producto->NOMBRE }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <span class="bg-gray-100 px-2 mt-1 rounded">{{ $producto->CODIGO }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Categoría (oculta en móvil) -->
                            <td class="px-3 py-4 hidden sm:table-cell">
                                <div class="text-sm text-gray-900">
                                    @if($producto->rubro_nombre)
                                        <div class="font-medium">{{ $producto->rubro_nombre }}</div>
                                    @endif
                                    @if($producto->marca_nombre)
                                        <div class="text-xs text-gray-500">{{ $producto->marca_nombre }}</div>
                                    @endif
                                    @if($producto->depto_nombre)
                                        <div class="text-xs text-gray-500">{{ $producto->depto_nombre }}</div>
                                    @endif
                                </div>
                            </td>

                            <!-- Precios -->
                            <td class="px-3 py-4 text-right">
                                <div class="space-y-1">
                                    <div class="text-sm font-semibold text-gray-900">
                                        ${{ number_format($producto->PRECIOVEN, 2) }}
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        Lista 2: ${{ number_format($producto->REVENTA, 2) }}
                                    </div>
                                    @if(!empty($listaEspecial) && isset($preciosEspeciales[$producto->CODIGO]))
                                        <div class="text-sm font-medium text-green-600">
                                            Especial: ${{ number_format($preciosEspeciales[$producto->CODIGO], 2) }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Stock (oculto en móvil) -->
                            <td class="px-3 py-4 text-center hidden md:table-cell">
                                <div class="text-sm text-gray-900">
                                    {{ number_format($producto->STOCKACT, 2) }}
                                </div>
                                @if($producto->STOCKACT <= 0)
                                    <div class="text-xs text-red-600 font-medium">Sin stock</div>
                                @elseif($producto->STOCKACT <= 5)
                                    <div class="text-xs text-orange-600 font-medium">Stock bajo</div>
                                @endif
                            </td>

                            <!-- Fecha actualización (oculto en móvil y tablet) -->
                            <td class="px-3 py-4 text-center hidden lg:table-cell">
                                <div class="text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($producto->FECMOD)->format('d/m/Y') }}
                                </div>                                
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="fas fa-search text-gray-400 text-xl"></i>
                                    </div>
                                    <p class="text-gray-500 text-lg font-medium">No se encontraron productos</p>
                                    <p class="text-gray-400 text-sm">Prueba ajustando los filtros de búsqueda</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        @if($productos->hasPages())
            <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
                {{ $productos->links() }}
            </div>
        @endif
    </div>

    <!-- Información de resultados -->
    <div class="mt-4 text-sm text-gray-600 text-center">
        Mostrando {{ $productos->firstItem() ?? 0 }} - {{ $productos->lastItem() ?? 0 }} de {{ $productos->total() }} productos
    </div>
</div>

<!-- JavaScript para impresión -->
<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('abrir-impresion-lista', (data) => {
        const ventana = window.open(data[0].url, '_blank', 'width=900,height=700,scrollbars=yes');
        ventana.onload = function() {
            ventana.print();
        };
    });
});
</script>