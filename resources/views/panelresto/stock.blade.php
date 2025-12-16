<div class="space-y-3 md:space-y-6">

    <!-- Pestañas -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-2 md:p-4">
        <div class="flex gap-2">
            <button wire:click="cambiarPestana('articulos')"
                    class="flex-1 px-3 md:px-6 py-2 md:py-3 rounded-lg transition-all duration-200 font-secondary text-xs md:text-sm font-medium
                           {{ $pestanaActiva === 'articulos' ? 'bg-gradient-to-r from-orange-400 to-orange-500 text-white shadow-lg' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                <i class="fas fa-box mr-2"></i>
                Artículos
            </button>
            <button wire:click="cambiarPestana('materias')"
                    class="flex-1 px-3 md:px-6 py-2 md:py-3 rounded-lg transition-all duration-200 font-secondary text-xs md:text-sm font-medium
                           {{ $pestanaActiva === 'materias' ? 'bg-gradient-to-r from-orange-400 to-orange-500 text-white shadow-lg' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                <i class="fas fa-flask mr-2"></i>
                Materias Primas
            </button>
        </div>
    </div>

    @if($pestanaActiva === 'articulos')
        <!-- SECCIÓN ARTÍCULOS -->

        <!-- Resumen Artículos -->
        <div class="bg-gradient-to-r from-orange-50 to-orange-100 rounded-xl p-3 md:p-6 border border-orange-200">
            <div class="grid grid-cols-2 gap-3 md:gap-6">
                <div>
                    <h3 class="text-sm md:text-lg font-primary font-semibold text-gray-800">Total Artículos</h3>
                    <p class="text-xl md:text-3xl font-primary font-bold text-orange-600">{{ number_format($totalArticulos) }}</p>
                </div>
                <div class="text-right">
                    <h3 class="text-sm md:text-lg font-primary font-semibold text-gray-800">Valorizado Total</h3>
                    <p class="text-xl md:text-3xl font-primary font-bold text-green-600">${{ number_format($totalValorizadoArticulos, 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Filtros Artículos -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-6">
            <div class="flex items-center justify-between mb-3 md:mb-4">
                <h2 class="text-base md:text-lg font-primary font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-filter text-orange-500 mr-2 text-sm md:text-base"></i>
                    <span class="hidden sm:inline">Filtros de Búsqueda</span>
                    <span class="sm:hidden">Filtros</span>
                </h2>
                <button wire:click="limpiarFiltrosArticulos"
                        class="px-2 md:px-4 py-1.5 md:py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors duration-200 text-xs md:text-sm font-secondary">
                    <i class="fas fa-broom mr-1"></i>
                    <span class="hidden sm:inline">Limpiar</span>
                </button>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 md:gap-4">
                <!-- Buscar -->
                <div class="col-span-2 lg:col-span-1">
                    <label class="block text-xs md:text-sm font-secondary font-medium text-gray-700 mb-1 md:mb-2">Buscar</label>
                    <input type="text"
                           wire:model.live.debounce.500ms="buscarArticulo"
                           placeholder="Código o nombre..."
                           class="w-full px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
                </div>

                <!-- Departamento -->
                <div class="col-span-2 lg:col-span-1">
                    <label class="block text-xs md:text-sm font-secondary font-medium text-gray-700 mb-1 md:mb-2">Departamento</label>
                    <select wire:model.live="departamentoSeleccionado"
                            class="w-full px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
                        <option value="">Todos los departamentos</option>
                        @foreach($departamentos as $depto)
                            <option value="{{ $depto->CODIGO }}">{{ $depto->NOMBRE }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Estado Stock -->
                <div class="col-span-2 lg:col-span-1">
                    <label class="block text-xs md:text-sm font-secondary font-medium text-gray-700 mb-1 md:mb-2">Estado Stock</label>
                    <select wire:model.live="estadoStockArticulo"
                            class="w-full px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
                        <option value="">Todos</option>
                        <option value="negativo">Stock Negativo</option>
                        <option value="positivo">Stock Positivo</option>
                        <option value="sin_stock">Sin Stock</option>
                    </select>
                </div>

                <!-- Items por página -->
                <div class="col-span-2 lg:col-span-1">
                    <label class="block text-xs md:text-sm font-secondary font-medium text-gray-700 mb-1 md:mb-2">Mostrar</label>
                    <select wire:model.live="porPaginaArticulos"
                            class="w-full px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tabla/Cards Artículos -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($articulos->count() > 0)
                <!-- Vista Desktop: Tabla -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-secondary font-semibold text-gray-700 uppercase tracking-wider">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-secondary font-semibold text-gray-700 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-secondary font-semibold text-gray-700 uppercase tracking-wider">Departamento</th>
                                <th class="px-6 py-3 text-right text-xs font-secondary font-semibold text-gray-700 uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-3 text-right text-xs font-secondary font-semibold text-gray-700 uppercase tracking-wider">Precio Costo</th>
                                <th class="px-6 py-3 text-right text-xs font-secondary font-semibold text-gray-700 uppercase tracking-wider">Valorizado</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($articulos as $articulo)
                            <tr class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-secondary font-medium text-gray-900">{{ $articulo->CODIGO }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-secondary text-gray-900">{{ $articulo->NOMBRE }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-secondary text-gray-600">{{ $articulo->nombre_depto ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-secondary font-bold
                                        {{ $articulo->STOCK < 0 ? 'text-red-600' : ($articulo->STOCK > 0 ? 'text-green-600' : 'text-gray-600') }}">
                                        {{ number_format($articulo->STOCK, 2) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-secondary text-gray-900">${{ number_format($articulo->PRECIOCOS, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-secondary font-bold text-blue-600">${{ number_format($articulo->valorizado, 2) }}</div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Vista Mobile: Cards -->
                <div class="lg:hidden divide-y divide-gray-200">
                    @foreach($articulos as $articulo)
                    <div class="p-3 hover:bg-gray-50 transition-colors duration-200">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-1">
                                <div class="text-xs font-secondary font-bold text-gray-900 mb-1">{{ $articulo->CODIGO }}</div>
                                <div class="text-sm font-secondary font-medium text-gray-900 mb-1">{{ $articulo->NOMBRE }}</div>
                                <div class="text-xs font-secondary text-gray-500">{{ $articulo->nombre_depto ?? 'Sin departamento' }}</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-gray-600">Stock:</span>
                                <span class="font-bold ml-1
                                    {{ $articulo->STOCK < 0 ? 'text-red-600' : ($articulo->STOCK > 0 ? 'text-green-600' : 'text-gray-600') }}">
                                    {{ number_format($articulo->STOCK, 2) }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-gray-600">P. Costo:</span>
                                <span class="font-medium text-gray-900 ml-1">${{ number_format($articulo->PRECIOCOS, 2) }}</span>
                            </div>
                            <div class="col-span-2 pt-2 border-t border-gray-200">
                                <span class="text-gray-600">Valorizado:</span>
                                <span class="font-bold text-blue-600 ml-1">${{ number_format($articulo->valorizado, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Paginación -->
                @if($articulos->hasPages())
                <div class="px-3 md:px-6 py-3 md:py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="text-xs md:text-sm font-secondary text-gray-600">
                            <span class="hidden sm:inline">Mostrando {{ $articulos->firstItem() }} - {{ $articulos->lastItem() }} de {{ $articulos->total() }} registros</span>
                            <span class="sm:hidden">{{ $articulos->firstItem() }}-{{ $articulos->lastItem() }} de {{ $articulos->total() }}</span>
                        </div>

                        <nav class="flex items-center justify-center space-x-1">
                            @if($articulos->onFirstPage())
                                <span class="px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm font-secondary text-gray-400 cursor-not-allowed">
                                    <i class="fas fa-chevron-left"></i>
                                </span>
                            @else
                                <button wire:click="previousPage"
                                        class="px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                            @endif

                            @php
                                $start = max(1, $articulos->currentPage() - 1);
                                $end = min($articulos->lastPage(), $articulos->currentPage() + 1);
                            @endphp

                            @if($start > 1)
                                <button wire:click="gotoPage(1)"
                                        class="hidden sm:inline-flex px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                                    1
                                </button>
                                @if($start > 2)
                                    <span class="hidden sm:inline px-1 md:px-2 py-1.5 md:py-2 text-xs md:text-sm font-secondary text-gray-400">...</span>
                                @endif
                            @endif

                            @for($page = $start; $page <= $end; $page++)
                                @if($page == $articulos->currentPage())
                                    <span class="px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm font-secondary bg-orange-500 text-white rounded-lg">
                                        {{ $page }}
                                    </span>
                                @else
                                    <button wire:click="gotoPage({{ $page }})"
                                            class="px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                                        {{ $page }}
                                    </button>
                                @endif
                            @endfor

                            @if($end < $articulos->lastPage())
                                @if($end < $articulos->lastPage() - 1)
                                    <span class="hidden sm:inline px-1 md:px-2 py-1.5 md:py-2 text-xs md:text-sm font-secondary text-gray-400">...</span>
                                @endif
                                <button wire:click="gotoPage({{ $articulos->lastPage() }})"
                                        class="hidden sm:inline-flex px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                                    {{ $articulos->lastPage() }}
                                </button>
                            @endif

                            @if($articulos->hasMorePages())
                                <button wire:click="nextPage"
                                        class="px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            @else
                                <span class="px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm font-secondary text-gray-400 cursor-not-allowed">
                                    <i class="fas fa-chevron-right"></i>
                                </span>
                            @endif
                        </nav>
                    </div>
                </div>
                @endif

            @else
                <div class="text-center py-8 md:py-12">
                    <i class="fas fa-box-open text-gray-400 text-4xl md:text-5xl mb-3 md:mb-4"></i>
                    <h3 class="text-base md:text-lg font-primary font-medium text-gray-900 mb-2">No hay artículos</h3>
                    <p class="text-gray-600 font-secondary text-sm md:text-base">No se encontraron artículos con los filtros seleccionados</p>
                </div>
            @endif
        </div>

    @else
        <!-- SECCIÓN MATERIAS PRIMAS -->

        <!-- Resumen Materias -->
        <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl p-3 md:p-6 border border-blue-200">
            <div class="grid grid-cols-2 gap-3 md:gap-6">
                <div>
                    <h3 class="text-sm md:text-lg font-primary font-semibold text-gray-800">Total Materias Primas</h3>
                    <p class="text-xl md:text-3xl font-primary font-bold text-blue-600">{{ number_format($totalMaterias) }}</p>
                </div>
                <div class="text-right">
                    <h3 class="text-sm md:text-lg font-primary font-semibold text-gray-800">Valorizado Total</h3>
                    <p class="text-xl md:text-3xl font-primary font-bold text-green-600">${{ number_format($totalValorizadoMaterias, 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Filtros Materias -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-6">
            <div class="flex items-center justify-between mb-3 md:mb-4">
                <h2 class="text-base md:text-lg font-primary font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-filter text-orange-500 mr-2 text-sm md:text-base"></i>
                    <span class="hidden sm:inline">Filtros de Búsqueda</span>
                    <span class="sm:hidden">Filtros</span>
                </h2>
                <button wire:click="limpiarFiltrosMaterias"
                        class="px-2 md:px-4 py-1.5 md:py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors duration-200 text-xs md:text-sm font-secondary">
                    <i class="fas fa-broom mr-1"></i>
                    <span class="hidden sm:inline">Limpiar</span>
                </button>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-3 gap-2 md:gap-4">
                <!-- Buscar -->
                <div class="col-span-2 lg:col-span-1">
                    <label class="block text-xs md:text-sm font-secondary font-medium text-gray-700 mb-1 md:mb-2">Buscar</label>
                    <input type="text"
                           wire:model.live.debounce.500ms="buscarMateria"
                           placeholder="Código o nombre..."
                           class="w-full px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
                </div>

                <!-- Estado Stock -->
                <div class="col-span-2 lg:col-span-1">
                    <label class="block text-xs md:text-sm font-secondary font-medium text-gray-700 mb-1 md:mb-2">Estado Stock</label>
                    <select wire:model.live="estadoStockMateria"
                            class="w-full px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
                        <option value="">Todos</option>
                        <option value="negativo">Stock Negativo</option>
                        <option value="positivo">Stock Positivo</option>
                        <option value="sin_stock">Sin Stock</option>
                    </select>
                </div>

                <!-- Items por página -->
                <div class="col-span-2 lg:col-span-1">
                    <label class="block text-xs md:text-sm font-secondary font-medium text-gray-700 mb-1 md:mb-2">Mostrar</label>
                    <select wire:model.live="porPaginaMaterias"
                            class="w-full px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-secondary">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tabla/Cards Materias -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($materias->count() > 0)
                <!-- Vista Desktop: Tabla -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-secondary font-semibold text-gray-700 uppercase tracking-wider">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-secondary font-semibold text-gray-700 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-center text-xs font-secondary font-semibold text-gray-700 uppercase tracking-wider">Unidad</th>
                                <th class="px-6 py-3 text-right text-xs font-secondary font-semibold text-gray-700 uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-3 text-right text-xs font-secondary font-semibold text-gray-700 uppercase tracking-wider">Precio Costo</th>
                                <th class="px-6 py-3 text-right text-xs font-secondary font-semibold text-gray-700 uppercase tracking-wider">Valorizado</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($materias as $materia)
                            <tr class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-secondary font-medium text-gray-900">{{ $materia->CODIGO }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-secondary text-gray-900">{{ $materia->NOMBRE }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-secondary font-medium bg-gray-100 text-gray-800">
                                        {{ $materia->UNIDAD ?? 'UN' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-secondary font-bold
                                        {{ $materia->STOCK < 0 ? 'text-red-600' : ($materia->STOCK > 0 ? 'text-green-600' : 'text-gray-600') }}">
                                        {{ number_format($materia->STOCK, 2) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-secondary text-gray-900">${{ number_format($materia->PCOSTO, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-secondary font-bold text-blue-600">${{ number_format($materia->valorizado, 2) }}</div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Vista Mobile: Cards -->
                <div class="lg:hidden divide-y divide-gray-200">
                    @foreach($materias as $materia)
                    <div class="p-3 hover:bg-gray-50 transition-colors duration-200">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-1">
                                <div class="text-xs font-secondary font-bold text-gray-900 mb-1">{{ $materia->CODIGO }}</div>
                                <div class="text-sm font-secondary font-medium text-gray-900 mb-1">{{ $materia->NOMBRE }}</div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-secondary font-medium bg-gray-100 text-gray-800">
                                    {{ $materia->UNIDAD ?? 'UN' }}
                                </span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-gray-600">Stock:</span>
                                <span class="font-bold ml-1
                                    {{ $materia->STOCK < 0 ? 'text-red-600' : ($materia->STOCK > 0 ? 'text-green-600' : 'text-gray-600') }}">
                                    {{ number_format($materia->STOCK, 2) }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-gray-600">P. Costo:</span>
                                <span class="font-medium text-gray-900 ml-1">${{ number_format($materia->PCOSTO, 2) }}</span>
                            </div>
                            <div class="col-span-2 pt-2 border-t border-gray-200">
                                <span class="text-gray-600">Valorizado:</span>
                                <span class="font-bold text-blue-600 ml-1">${{ number_format($materia->valorizado, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Paginación -->
                @if($materias->hasPages())
                <div class="px-3 md:px-6 py-3 md:py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="text-xs md:text-sm font-secondary text-gray-600">
                            <span class="hidden sm:inline">Mostrando {{ $materias->firstItem() }} - {{ $materias->lastItem() }} de {{ $materias->total() }} registros</span>
                            <span class="sm:hidden">{{ $materias->firstItem() }}-{{ $materias->lastItem() }} de {{ $materias->total() }}</span>
                        </div>

                        <nav class="flex items-center justify-center space-x-1">
                            @if($materias->onFirstPage())
                                <span class="px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm font-secondary text-gray-400 cursor-not-allowed">
                                    <i class="fas fa-chevron-left"></i>
                                </span>
                            @else
                                <button wire:click="previousPage"
                                        class="px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                            @endif

                            @php
                                $start = max(1, $materias->currentPage() - 1);
                                $end = min($materias->lastPage(), $materias->currentPage() + 1);
                            @endphp

                            @if($start > 1)
                                <button wire:click="gotoPage(1)"
                                        class="hidden sm:inline-flex px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                                    1
                                </button>
                                @if($start > 2)
                                    <span class="hidden sm:inline px-1 md:px-2 py-1.5 md:py-2 text-xs md:text-sm font-secondary text-gray-400">...</span>
                                @endif
                            @endif

                            @for($page = $start; $page <= $end; $page++)
                                @if($page == $materias->currentPage())
                                    <span class="px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm font-secondary bg-orange-500 text-white rounded-lg">
                                        {{ $page }}
                                    </span>
                                @else
                                    <button wire:click="gotoPage({{ $page }})"
                                            class="px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                                        {{ $page }}
                                    </button>
                                @endif
                            @endfor

                            @if($end < $materias->lastPage())
                                @if($end < $materias->lastPage() - 1)
                                    <span class="hidden sm:inline px-1 md:px-2 py-1.5 md:py-2 text-xs md:text-sm font-secondary text-gray-400">...</span>
                                @endif
                                <button wire:click="gotoPage({{ $materias->lastPage() }})"
                                        class="hidden sm:inline-flex px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                                    {{ $materias->lastPage() }}
                                </button>
                            @endif

                            @if($materias->hasMorePages())
                                <button wire:click="nextPage"
                                        class="px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm font-secondary text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            @else
                                <span class="px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm font-secondary text-gray-400 cursor-not-allowed">
                                    <i class="fas fa-chevron-right"></i>
                                </span>
                            @endif
                        </nav>
                    </div>
                </div>
                @endif

            @else
                <div class="text-center py-8 md:py-12">
                    <i class="fas fa-flask text-gray-400 text-4xl md:text-5xl mb-3 md:mb-4"></i>
                    <h3 class="text-base md:text-lg font-primary font-medium text-gray-900 mb-2">No hay materias primas</h3>
                    <p class="text-gray-600 font-secondary text-sm md:text-base">No se encontraron materias primas con los filtros seleccionados</p>
                </div>
            @endif
        </div>
    @endif

</div>
