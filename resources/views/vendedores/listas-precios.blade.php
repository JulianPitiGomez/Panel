<div class="px-1 pb-28" x-data="{ mostrarFiltros: false, mostrarCompartir: false }" style="padding-bottom: calc(7rem + env(safe-area-inset-bottom));">
    <!-- Header -->
    <div class="bg-[#222036] text-white rounded-lg p-4 mb-4 mt-3 shadow-md">
        <h2 class="text-lg font-semibold">LISTAS DE PRECIOS</h2>
        <p class="text-sm">Consulta precios de listas y especiales</p>
    </div>
    <div class="bg-white flex py-2 flex-col sm:flex-row sm:items-center rounded-t sm:justify-between">
        <!-- Botones de acción (una sola fila en móvil, texto acortado) -->
        <div class="flex items-center gap-1 sm:gap-2 mt-4 mb-2 sm:mt-0 flex-nowrap">
            <button @click="mostrarFiltros = !mostrarFiltros"
                    class="inline-flex items-center px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg border border-gray-300 transition-all duration-200 whitespace-nowrap"
                    :class="{ 'bg-[#FFF5E6] border-[#FFD68F] text-[#E89B0A]': mostrarFiltros }">
                <i class="fas fa-filter text-xs sm:text-sm transition-transform duration-200"
                    :class="{ 'text-[#FFAF22]': mostrarFiltros }"></i>
                <span class="ml-1 sm:ml-2 text-xs sm:text-sm">Filtros</span>
                <i class="fas fa-chevron-down ml-1 sm:ml-2 text-[10px] sm:text-xs transition-transform duration-200"
                    :class="{ 'rotate-180': mostrarFiltros }"></i>
            </button>

            <button @click="mostrarCompartir = !mostrarCompartir"
                    class="inline-flex items-center px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg border border-gray-300 transition-all duration-200 whitespace-nowrap"
                    :class="{ 'bg-blue-50 border-blue-300 text-blue-700': mostrarCompartir }">
                <i class="fas fa-share-alt text-xs sm:text-sm transition-transform duration-200"
                    :class="{ 'text-blue-600': mostrarCompartir }"></i>
                <span class="ml-1 sm:ml-2 text-xs sm:text-sm">
                    <span class="sm:hidden">Cliente</span>
                    <span class="hidden sm:inline">Compartir con cliente</span>
                </span>
                <i class="fas fa-chevron-down ml-1 sm:ml-2 text-[10px] sm:text-xs transition-transform duration-200"
                    :class="{ 'rotate-180': mostrarCompartir }"></i>
            </button>
            
            <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                <button type="button" @click="open = !open"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        wire:target="imprimirLista"
                        class="inline-flex items-center px-2 py-1 bg-[#FFAF22] hover:bg-[#E89B0A] text-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200 whitespace-nowrap">
                    <i class="fas fa-file-pdf mr-1 sm:mr-2 text-xs sm:text-sm" wire:loading.remove wire:target="imprimirLista"></i>
                    <i class="fas fa-spinner fa-spin mr-1 sm:mr-2 text-xs sm:text-sm" wire:loading wire:target="imprimirLista"></i>
                    <span class="text-xs sm:text-sm" wire:loading.remove wire:target="imprimirLista">
                        <span class="sm:hidden">PDF</span>
                        <span class="hidden sm:inline">Lista PDF</span>
                    </span>
                    <span class="text-xs sm:text-sm" wire:loading wire:target="imprimirLista">Generando...</span>
                    <i class="fas fa-chevron-down ml-1 sm:ml-2 text-[10px] sm:text-xs" wire:loading.remove wire:target="imprimirLista"></i>
                </button>
                <div x-show="open" x-transition x-cloak
                     class="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50 overflow-hidden">
                    <button type="button"
                            @click="open = false; $wire.imprimirLista('compartir')"
                            class="w-full flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 text-left">
                        <i class="fas fa-share-alt w-5 text-green-600"></i> Compartir
                    </button>
                    <button type="button"
                            @click="open = false; $wire.imprimirLista('ver')"
                            class="w-full flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 text-left border-t border-gray-100">
                        <i class="fas fa-eye w-5 text-gray-500"></i> Previsualizar
                    </button>
                    <button type="button"
                            @click="open = false; $wire.imprimirLista('descargar')"
                            class="w-full flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 text-left border-t border-gray-100">
                        <i class="fas fa-download w-5 text-gray-500"></i> Descargar
                    </button>
                    <button type="button"
                            @click="open = false; $wire.imprimirLista('imprimir')"
                            class="w-full flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 text-left border-t border-gray-100">
                        <i class="fas fa-print w-5 text-gray-500"></i> Imprimir
                    </button>
                </div>
            </div>
        </div>
        <!-- Nota informativa -->
        <div class="text-xs text-gray-500 italic px-2 pb-2">
            <i class="fas fa-info-circle mr-1"></i>La impresión incluye hasta 2000 productos según los filtros aplicados
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
        
        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4 text-sm">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mb-4">
                <!-- Búsqueda -->
                <div class="sm:col-span-2">
                    <label for="busqueda" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-search mr-1 text-[#FFAF22]"></i>Buscar Producto
                    </label>
                    <input type="text" 
                           wire:model.live.debounce.300ms="busqueda" 
                           id="busqueda"
                           placeholder="Buscar por código o nombre..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#FFAF22] transition-all duration-200">
                </div>

                <!-- Fecha Desde -->
                <div>
                    <label for="fechaDesde" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar-alt mr-1 text-[#FFAF22]"></i>Modificado Desde
                    </label>
                    <input type="date" 
                           wire:model.live.debounce.300ms="fechaDesde" 
                           id="fechaDesde"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#FFAF22] transition-all duration-200">
                </div>

                <!-- Fecha Hasta -->
                <div>
                    <label for="fechaHasta" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar-alt mr-1 text-[#FFAF22]"></i>Modificado Hasta
                    </label>
                    <input type="date" 
                           wire:model.live.debounce.300ms="fechaHasta" 
                           id="fechaHasta"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#FFAF22] transition-all duration-200">
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-4">
                <!-- Rubro -->
                <div>
                    <label for="rubro" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tag mr-1 text-[#FFAF22]"></i>Rubro
                    </label>
                    <select wire:model.live="rubro" 
                            id="rubro"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
                        <option value="">Todos los rubros</option>
                        @foreach($rubros as $rubro)
                            <option value="{{ $rubro->CODIGO }}">{{ $rubro->NOMBRE }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Marca -->
                <div>
                    <label for="marca" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-copyright mr-1 text-[#FFAF22]"></i>Marca
                    </label>
                    <select wire:model.live="marca" 
                            id="marca"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
                        <option value="">Todas las marcas</option>
                        @foreach($marcas as $marca)
                            <option value="{{ $marca->CODIGO }}">{{ $marca->NOMBRE }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Departamento -->
                <div>
                    <label for="depto" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-building mr-1 text-[#FFAF22]"></i>Departamento
                    </label>
                    <select wire:model.live="depto" 
                            id="depto"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
                        <option value="">Todos los deptos</option>
                        @foreach($deptos as $depto)
                            <option value="{{ $depto->CODIGO }}">{{ $depto->NOMBRE }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Proveedor -->
                <div>
                    <label for="proveedor" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-truck mr-1 text-[#FFAF22]"></i>Proveedor
                    </label>
                    <select wire:model.live="proveedor" 
                            id="proveedor"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
                        <option value="">Todos los proveedores</option>
                        @foreach($proveedores as $proveedor)
                            <option value="{{ $proveedor->CODIGO }}">{{ $proveedor->NOMBRE }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Lista Especial -->
                <div>
                    <label for="listaEspecial" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-list mr-1 text-[#FFAF22]"></i>Lista Especial
                    </label>
                    <select wire:model.live="listaEspecial" 
                            id="listaEspecial"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
                        <option value="">Precios normales</option>
                        @foreach($listasEspeciales as $lista)
                            <option value="{{ $lista->CODIGO }}">{{ $lista->NOMBRE }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <button @click="mostrarFiltros = !mostrarFiltros"
                        class="inline-flex items-center px-3 py-2  bg-[#FFAF22] hover:bg-[#E89B0A] text-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-filter mr-2"></i>
                    Aplicar
                </button>
                <button wire:click="limpiarFiltros"
                        class="inline-flex items-center px-3 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium rounded-md transition-colors duration-200">
                    <i class="fas fa-eraser mr-2"></i>
                    Limpiar
                </button>
            </div>

        </div>
    </div>

    <!-- Panel "Compartir con cliente" colapsable -->
    <div x-show="mostrarCompartir"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2"
         class="mb-6">

        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4 text-sm">
            <h3 class="text-base font-semibold text-gray-700 mb-3">
                <i class="fas fa-share-alt mr-2 text-blue-600"></i>Personalizar lista para el cliente
            </h3>
            <p class="text-xs text-gray-500 mb-4">
                Estas opciones afectan sólo al PDF/impresión que se comparte. En pantalla seguís viendo todo.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                <!-- Selector de cliente asignado -->
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-1 text-blue-600"></i>Cliente (opcional)
                    </label>
                    <select wire:model.live="clienteSeleccionado"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Cliente manual / sin especificar —</option>
                        @foreach($clientesVendedor as $c)
                            <option value="{{ $c->CODIGO }}">
                                {{ $c->NOMBRE }}{{ !empty($c->mail) ? ' ✉' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Nombre del cliente (editable) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nombre que aparece en la lista
                    </label>
                    <input type="text" wire:model="clienteNombre"
                           placeholder="Ej: Supermercado Central"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Fecha emisión -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar-day mr-1 text-blue-600"></i>Fecha de emisión
                    </label>
                    <input type="date" wire:model="fechaEmision"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Fecha validez -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar-check mr-1 text-blue-600"></i>Válida hasta
                    </label>
                    <input type="date" wire:model="fechaValidez"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <!-- Opciones de salida -->
            <div class="border-t border-gray-200 pt-3 mt-2">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Qué mostrar en el PDF</h4>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2 text-sm">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="mostrarLista1"
                               class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500">
                        <span>Lista 1 (venta)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="mostrarLista2"
                               class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500">
                        <span>Lista 2 (reventa)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer" title="Sólo tiene efecto si hay una lista especial seleccionada arriba">
                        <input type="checkbox" wire:model="mostrarListaEspecial"
                               class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500">
                        <span>Lista especial</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer" title="Cuando hay una lista especial seleccionada, oculta los productos que no están en esa lista">
                        <input type="checkbox" wire:model="soloEnListaEspecial"
                               class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500">
                        <span>Sólo ítems en lista especial</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="mostrarTotales"
                               class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500">
                        <span>Totales al final</span>
                    </label>
                </div>
            </div>

            <!-- Envío por mail -->
            @if(!empty($clienteSeleccionado))
                <div class="border-t border-gray-200 pt-3 mt-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-1 text-blue-600"></i>Enviar por email
                    </h4>
                    @if(!empty($clienteMail))
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                            <div class="text-sm text-gray-600">
                                Destinatario: <strong>{{ $clienteMail }}</strong>
                            </div>
                            <button type="button" wire:click="enviarListaPorMail"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50 cursor-not-allowed"
                                    wire:target="enviarListaPorMail"
                                    class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-md text-sm font-medium transition-all">
                                <i class="fas fa-paper-plane mr-2" wire:loading.remove wire:target="enviarListaPorMail"></i>
                                <i class="fas fa-spinner fa-spin mr-2" wire:loading wire:target="enviarListaPorMail"></i>
                                <span wire:loading.remove wire:target="enviarListaPorMail">Enviar lista por mail</span>
                                <span wire:loading wire:target="enviarListaPorMail">Enviando...</span>
                            </button>
                        </div>
                    @else
                        <div class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded p-2">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            El cliente seleccionado no tiene email cargado.
                        </div>
                    @endif

                    {{-- Mensaje de resultado de envío --}}
                    @if(!empty($mensajeMail))
                        @php
                            [$tipo, $texto] = explode(':', $mensajeMail, 2) + [1 => ''];
                        @endphp
                        <div class="mt-2 text-sm rounded p-2
                                    {{ $tipo === 'ok' ? 'bg-green-50 border border-green-200 text-green-800'
                                                      : 'bg-red-50 border border-red-200 text-red-800' }}">
                            <i class="fas {{ $tipo === 'ok' ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                            {{ $texto }}
                        </div>
                    @endif
                </div>
            @endif
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
                                    <div class="text-xs text-[#FFAF22] font-medium">Stock bajo</div>
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
        @if($paginationInfo->last_page > 1)
            <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Mostrando <span class="font-medium">{{ $paginationInfo->from }}</span> a <span class="font-medium">{{ $paginationInfo->to }}</span> de <span class="font-medium">{{ $paginationInfo->total }}</span> resultados
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            @if($paginationInfo->current_page > 1)
                                <button wire:click="previousPage" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    Anterior
                                </button>
                            @else
                                <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                    Anterior
                                </span>
                            @endif

                            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                Página {{ $paginationInfo->current_page }} de {{ $paginationInfo->last_page }}
                            </span>

                            @if($paginationInfo->current_page < $paginationInfo->last_page)
                                <button wire:click="nextPage" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    Siguiente
                                </button>
                            @else
                                <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                    Siguiente
                                </span>
                            @endif
                        </nav>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Información de resultados -->
    <div class="mt-4 text-sm text-gray-600 text-center">
        Mostrando {{ $paginationInfo->from }} - {{ $paginationInfo->to }} de {{ $paginationInfo->total }} productos
    </div>

    {{-- Toast visual al generar / descargar el PDF (position:fixed, sale del flujo) --}}
    <div id="pdfToast"
         style="display:none; position:fixed; left:50%; bottom:24px; transform:translateX(-50%);
                background:#222036; color:white; padding:12px 20px; border-radius:8px;
                box-shadow:0 4px 12px rgba(0,0,0,0.3); z-index:9999; font-size:14px;
                max-width:90%; text-align:center;">
        <span id="pdfToastMsg"></span>
    </div>

    {{-- JS para previsualización / descarga. Dentro de @script para que
         Livewire lo re-ejecute en cada navegación SPA (wire:navigate). --}}
    @script
    <script>
    window.mostrarToastPDF = function(texto, duracion) {
        var el = document.getElementById('pdfToast');
        if (!el) return;
        document.getElementById('pdfToastMsg').textContent = texto;
        el.style.display = 'block';
        clearTimeout(window._pdfToastT);
        window._pdfToastT = setTimeout(function() { el.style.display = 'none'; }, duracion || 4500);
    };

    async function compartirPDF(url, clienteNombre) {
        try {
            window.mostrarToastPDF('📄 Preparando PDF para compartir...', 10000);
            const resp = await fetch(url, { credentials: 'same-origin' });
            if (!resp.ok) throw new Error('No se pudo generar el PDF');
            const blob = await resp.blob();
            const archivo = new File([blob], 'lista-precios.pdf', { type: 'application/pdf' });

            if (navigator.canShare && navigator.canShare({ files: [archivo] })) {
                await navigator.share({
                    title: 'Lista de Precios',
                    text: clienteNombre
                        ? ('Hola ' + clienteNombre + ', te envío la lista de precios.')
                        : 'Te comparto la lista de precios.',
                    files: [archivo],
                });
                window.mostrarToastPDF('✅ Compartido.', 2500);
            } else {
                // Fallback: sin Web Share API (desktop o navegador viejo).
                // Abrir el PDF en una pestaña — el usuario puede descargar
                // y adjuntarlo manualmente a WhatsApp/mail/lo que quiera.
                window.mostrarToastPDF('Tu navegador no soporta compartir archivos. Abriendo PDF para que lo descargues...', 4500);
                const dlUrl = URL.createObjectURL(blob);
                window.open(dlUrl, '_blank');
                setTimeout(function() { URL.revokeObjectURL(dlUrl); }, 60000);
            }
        } catch (e) {
            if (e && e.name === 'AbortError') {
                // El usuario canceló la bandeja de compartir: ocultar toast silenciosamente.
                window.mostrarToastPDF('', 1);
                return;
            }
            console.error(e);
            window.mostrarToastPDF('❌ No se pudo compartir: ' + (e.message || e), 4500);
        }
    }

    $wire.on('abrir-impresion-lista', (data) => {
        const payload = Array.isArray(data) ? data[0] : data;
        const url = payload?.url;
        const modo = payload?.modo || 'ver';
        const clienteNombre = payload?.clienteNombre || '';

        if (modo === 'compartir') {
            compartirPDF(url, clienteNombre);
            return;
        }

        if (modo === 'descargar') {
            window.mostrarToastPDF('📄 Generando PDF, se descargará en unos segundos...', 6000);
            const a = document.createElement('a');
            a.href = url;
            a.rel = 'noopener';
            a.setAttribute('download', '');
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            setTimeout(function() { window.mostrarToastPDF('✅ PDF listo. Revisá tus descargas.', 4500); }, 3500);
            return;
        }

        if (modo === 'imprimir') {
            // La página HTML se abre y llama window.print() al cargar,
            // que en móvil muestra la bandeja nativa (Imprimir / Guardar como PDF).
            window.mostrarToastPDF('🖨 Abriendo diálogo de impresión...', 3500);
            const ventana = window.open(url, '_blank');
            if (!ventana) {
                window.location.href = url;
            }
            return;
        }

        // Modo "ver"
        window.mostrarToastPDF('📄 Abriendo PDF...', 2500);
        const ventana = window.open(url, '_blank');
        if (!ventana) {
            window.location.href = url;
        }
    });
    </script>
    @endscript
</div>