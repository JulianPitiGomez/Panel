<div class="h-screen bg-gray-50 flex">
    
    <!-- ===== OVERLAY SOLO PARA MÓVIL 
    <div class="sm:hidden {{ $sidebarOpen ? 'fixed inset-0 z-40 bg-black bg-opacity-50' : 'hidden' }}" 
     wire:click="toggleSidebar"></div> -->

    <!-- ===== SIDEBAR ===== -->
    <div class="
        {{ $sidebarOpen ? 'translate-x-0' : '-translate-x-full sm:translate-x-0' }}
        fixed sm:static inset-y-0 left-0 z-50 sm:z-auto
        w-64 bg-[#222036] shadow-xl border-r border-gray-200
        transform lg:transform-none transition-transform lg:transition-none duration-300 ease-in-out
        flex flex-col
        {{ !$sidebarOpen ? 'sm:hidden' : 'sm:flex' }}
    ">
        
        <!-- Header del Sidebar -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-[#222036] flex-shrink-0">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-orange-500 rounded-lg flex items-center justify-center shadow-md">
                    <span class="text-white font-bold text-lg font-primary">P</span>
                </div>
                <div>
                    <h1 class="text-lg font-primary font-semibold text-white">Resto</h1>
                    <p class="text-xs font-secondary text-white">Panel Web</p>
                </div>
            </div>
            <button 
                wire:click="toggleSidebar"
                class="p-2 rounded-lg hover:bg-orange-500 transition-colors duration-200 lg:block">
                <i class="fas fa-times text-white"></i>
            </button>
        </div>

        <!-- Menú de Navegación -->
        <nav class="flex-1 overflow-y-auto px-4 py-6">
            <div class="space-y-2">
                
                <!-- Inicio/Dashboard -->
                <button 
                    wire:click="navigateTo('inicio') ; if(window.innerWidth < 640) $wire.call('toggleSidebar')"
                    class="w-full flex items-center px-4 py-3 text-left rounded-xl transition-all duration-200 hover:bg-orange-50 hover:translate-x-1 group {{ $currentPage === 'inicio' ? 'bg-gradient-to-r from-orange-400 to-orange-500 text-white shadow-lg' : 'text-white hover:text-orange-600' }}">
                    <i class="fas fa-home mr-3 text-lg {{ $currentPage === 'inicio' ? 'text-white' : 'text-orange-500 group-hover:text-orange-600' }}"></i>
                    <span class="font-secondary font-medium">Inicio</span>
                </button>

                <!-- Panel Mesas -->
                <button 
                    wire:click="navigateTo('panel-mesas') ; if(window.innerWidth < 640) $wire.call('toggleSidebar')"
                    class="w-full flex items-center px-4 py-3 text-left rounded-xl transition-all duration-200 hover:bg-orange-50 hover:translate-x-1 group {{ $currentPage === 'panel-mesas' ? 'bg-gradient-to-r from-orange-400 to-orange-500 text-white shadow-lg' : 'text-white hover:text-orange-600' }}">
                    <i class="fas fa-chair mr-3 text-lg {{ $currentPage === 'panel-mesas' ? 'text-white' : 'text-orange-500 group-hover:text-orange-600' }}"></i>
                    <span class="font-secondary font-medium">Panel de Mesas</span>
                </button>

                <!-- Compras por fechas -->
                <button 
                    wire:click="navigateTo('estado-delivery') ; if(window.innerWidth < 640) $wire.call('toggleSidebar')"
                    class="w-full flex items-center px-4 py-3 text-left rounded-xl transition-all duration-200 hover:bg-orange-50 hover:translate-x-1 group {{ $currentPage === 'estado-delivery' ? 'bg-gradient-to-r from-orange-400 to-orange-500 text-white shadow-lg' : 'text-white hover:text-orange-600' }}">
                    <i class="fas fa-motorcycle mr-3 text-lg {{ $currentPage === 'estado-delivery' ? 'text-white' : 'text-orange-500 group-hover:text-orange-600' }}"></i>
                    <span class="font-secondary font-medium">Estado Delivery</span>
                </button>

                <!-- Articulos Vendidos -->
                <button 
                    wire:click="navigateTo('estado-mostrador') ; if(window.innerWidth < 640) $wire.call('toggleSidebar')"
                    class="w-full flex items-center px-4 py-3 text-left rounded-xl transition-all duration-200 hover:bg-orange-50 hover:translate-x-1 group {{ $currentPage === 'estado-mostrador' ? 'bg-gradient-to-r from-orange-400 to-orange-500 text-white shadow-lg' : 'text-white hover:text-orange-600' }}">
                    <i class="fas fa-cash-register mr-3 text-lg {{ $currentPage === 'estado-mostrador' ? 'text-white' : 'text-orange-500 group-hover:text-orange-600' }}"></i>
                    <span class="font-secondary font-medium">Estado Mostrador</span>
                </button>

                <!-- Cobranzas a clientes -->
                <button 
                    wire:click="navigateTo('cobranzas-clientes'); if(window.innerWidth < 640) $wire.call('toggleSidebar')"
                    class="w-full flex items-center px-4 py-3 text-left rounded-xl transition-all duration-200 hover:bg-orange-50 hover:translate-x-1 group {{ $currentPage === 'cobranzas-clientes' ? 'bg-gradient-to-r from-orange-400 to-orange-500 text-white shadow-lg' : 'text-white hover:text-orange-600' }}">
                    <i class="fas fa-hand-holding-usd mr-3 text-lg {{ $currentPage === 'cobranzas-clientes' ? 'text-white' : 'text-orange-500 group-hover:text-orange-600' }}"></i>
                    <span class="font-secondary font-medium">Cobranzas a clientes</span>
                </button>

                <!-- Pagos a proveedores -->
                <button
                    wire:click="navigateTo('pagos-proveedores') ; if(window.innerWidth < 640) $wire.call('toggleSidebar')"
                    class="w-full flex items-center px-4 py-3 text-left rounded-xl transition-all duration-200 hover:bg-orange-50 hover:translate-x-1 group {{ $currentPage === 'pagos-proveedores' ? 'bg-gradient-to-r from-orange-400 to-orange-500 text-white shadow-lg' : 'text-white hover:text-orange-600' }}">
                    <i class="fas fa-credit-card mr-3 text-lg {{ $currentPage === 'pagos-proveedores' ? 'text-white' : 'text-orange-500 group-hover:text-orange-600' }}"></i>
                    <span class="font-secondary font-medium">Pagos a proveedores</span>
                </button>

                <!-- Stock -->
                <button 
                    wire:click="navigateTo('stock') ; if(window.innerWidth < 640) $wire.call('toggleSidebar')"
                    class="w-full flex items-center px-4 py-3 text-left rounded-xl transition-all duration-200 hover:bg-orange-50 hover:translate-x-1 group {{ $currentPage === 'stock' ? 'bg-gradient-to-r from-orange-400 to-orange-500 text-white shadow-lg' : 'text-white hover:text-orange-600' }}">
                    <i class="fas fa-warehouse mr-3 text-lg {{ $currentPage === 'stock' ? 'text-white' : 'text-orange-500 group-hover:text-orange-600' }}"></i>
                    <span class="font-secondary font-medium">Stock</span>
                </button>

                <!-- Cajas -->
                <button 
                    wire:click="navigateTo('cajas'); if(window.innerWidth < 640) $wire.call('toggleSidebar')"
                    class="w-full flex items-center px-4 py-3 text-left rounded-xl transition-all duration-200 hover:bg-orange-50 hover:translate-x-1 group {{ $currentPage === 'cajas' ? 'bg-gradient-to-r from-orange-400 to-orange-500 text-white shadow-lg' : 'text-white hover:text-orange-600' }}">
                    <i class="fas fa-cash-register mr-3 text-lg {{ $currentPage === 'cajas' ? 'text-white' : 'text-orange-500 group-hover:text-orange-600' }}"></i>
                    <span class="font-secondary font-medium">Cajas</span>
                </button>

                <!-- Buscador de precios -->
                <button 
                    wire:click="navigateTo('monitor'); if(window.innerWidth < 640) $wire.call('toggleSidebar')"
                    class="w-full flex items-center px-4 py-3 text-left rounded-xl transition-all duration-200 hover:bg-orange-50 hover:translate-x-1 group {{ $currentPage === 'monitor' ? 'bg-gradient-to-r from-orange-400 to-orange-500 text-white shadow-lg' : 'text-white hover:text-orange-600' }}">
                    <i class="fas fa-search-dollar mr-3 text-lg {{ $currentPage === 'buscador-precios' ? 'text-white' : 'text-orange-500 group-hover:text-orange-600' }}"></i>
                    <span class="font-secondary font-medium">Pedidos Mostrador</span>
                </button>

                <!-- Separador -->
                <div class="border-t border-gray-200 my-4"></div>

                <!-- Cerrar Sesión -->
                <button 
                    wire:click="logout"
                    class="w-full flex items-center px-4 py-3 text-left rounded-xl transition-all duration-200 hover:bg-red-50 hover:translate-x-1 group text-white hover:text-red-600">
                    <i class="fas fa-sign-out-alt mr-3 text-lg text-red-500 group-hover:text-red-600"></i>
                    <span class="font-secondary font-medium">Cerrar Sesión</span>
                </button>
            </div>
        </nav>

        <!-- Footer del Sidebar -->
        <div class="border-t border-gray-200 p-4 bg-[#222036] flex-shrink-0">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-gradient-to-br from-orange-400 to-orange-500 rounded-md flex items-center justify-center">
                    <span class="text-white font-bold text-sm font-primary">{{ session('panel_user_id') }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-secondary font-semibold text-white truncate">Usuario</p>
                    <p class="text-xs font-secondary text-white">{{ session('cliente_nombre') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== CONTENIDO PRINCIPAL ===== -->
    <div class="flex-1 flex flex-col min-w-0 bg-[#222036]">
        
        <!-- Header Superior -->
        <header class="bg-[#222036] shadow-sm border-b border-gray-200 flex-shrink-0 z-10">
            <div class="px-4 lg:px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <!-- Botón hamburguesa -->
                        <button 
                            wire:click="toggleSidebar"
                            class="p-2 rounded-lg hover:bg-orange-500 transition-colors duration-200">
                            <i class="fas fa-bars text-white text-lg"></i>
                        </button>
                        
                        <div>
                            <h1 class="text-xl lg:text-2xl font-primary  text-white">
                                @switch($currentPage)
                                    @case('inicio')
                                        Dashboard Principal
                                        @break
                                    @case('panel-mesas')
                                        Panel de Mesas
                                        @break
                                    @case('estado-delivery')
                                        Estado Delivery
                                        @break
                                    @case('estado-mostrador')
                                        Estado Mostrador
                                        @break
                                    @case('cobranzas-clientes')
                                        Cobranzas a Clientes
                                        @break
                                    @case('pagos-proveedores')
                                        Pagos a Proveedores
                                        @break
                                    @case('stock')
                                        Control de Stock
                                        @break
                                    @case('cajas')
                                        Gestión de Cajas
                                        @break
                                    @case('monitor')
                                        Pedidos Mostrador
                                        @break
                                    @default
                                        Panel de Gestión
                                @endswitch
                            </h1>
                            <p class="text-sm font-secondary text-white hidden sm:block">Sistema de gestión restó</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-secondary font-semibold text-white">{{ date('d/m/Y') }}</p>
                            <p class="text-xs font-secondary text-white">{{ date('H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Contenido Principal -->
        <main class="flex-1 overflow-y-auto p-4 lg:p-6 bg-gray-200">
            <div class="max-w-7xl mx-auto h-full">
                @switch($currentPage)
                    @case('inicio')
                        @livewire('panelresto.inicio')
                        @break
                    @case('panel-mesas')
                        @livewire('panelresto.panel-mesas')
                        @break
                    @case('estado-delivery')
                        @livewire('panelresto.estado-delivery')
                        @break
                    @case('estado-mostrador')
                        @livewire('panelresto.estado-mostrador')
                        @break
                    @case('cobranzas-clientes')
                        @livewire('panelresto.cobranzas-clientes')
                        @break
                    @case('pagos-proveedores')
                        @livewire('panelresto.pagos-proveedores')
                        @break
                    @case('stock')
                        @livewire('panelresto.stock')
                        @break
                    @case('cajas')
                        @livewire('panelresto.cajas', key('cajas'))
                        @break                    
                    @case('monitor')
                        @livewire('panelresto.monitor')
                        @break
                    @default
                        @livewire('panelresto.inicio')
                @endswitch
            </div>
        </main>
    </div>
</div>