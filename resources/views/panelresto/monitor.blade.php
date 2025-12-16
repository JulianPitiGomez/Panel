<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900" wire:poll.10s="cargarPedidos">

    <!-- Contenido principal con las dos columnas -->
    <div class="flex p-2 gap-8">
        
        <!-- Columna de pedidos en preparación -->
        <div class="flex-1 bg-white rounded-3xl shadow-2xl overflow-hidden">
            <!-- Header de la columna -->
            <div class="bg-gradient-to-r from-amber-500 to-orange-500 text-white p-6">
                <div class="flex items-center justify-center">
                    <svg class="w-10 h-10 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h2 class="text-4xl font-bold">EN PREPARACIÓN </h2>
                    <span class="bg-white text-orange-600 bg-opacity-20 px-4 py-2 rounded-full text-lg font-semibold">
                        {{ count($pedidosEnPreparacion) }} 
                    </span>
                </div>                
            </div>

            <!-- Lista de pedidos en preparación -->
            <div id="preparacion-container" class="p-6 overflow-hidden relative" style="height: calc(100vh - 180px);">
                @if(count($pedidosEnPreparacion) > 0)
                    <div id="preparacion-list" class="space-y-4">
                        @foreach($pedidosEnPreparacion as $pedido)
                            <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-l-8 border-amber-500 rounded-xl p-6 shadow-lg transform hover:scale-102 transition-transform duration-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="bg-amber-500 text-white rounded h-16 flex items-center justify-center mr-6">
                                            <span class="text-2xl font-bold">#{{ $pedido->id }}</span>
                                        </div>
                                        <div>
                                            <h3 class="text-3xl font-bold text-gray-800">{{ $pedido->nombre ?: 'Pedido #' . $pedido->id }}</h3>
                                            <p class="text-lg text-amber-600 font-semibold">Preparando...</p>
                                        </div>
                                    </div>
                                    <div class="animate-spin">
                                        <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center text-gray-400">
                            <svg class="w-24 h-24 mx-auto mb-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <h3 class="text-2xl font-medium mb-2">No hay pedidos en preparación</h3>
                            <p class="text-lg">Los nuevos pedidos aparecerán aquí</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Columna de pedidos listos -->
        <div class="flex-1 bg-white rounded-3xl shadow-2xl overflow-hidden">
            <!-- Header de la columna -->
            <div class="bg-gradient-to-r from-green-500 to-emerald-500 text-white p-6">
                <div class="flex items-center justify-center">
                    <svg class="w-10 h-10 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h2 class="text-4xl font-bold">PEDIDOS LISTOS </h2>
                    <span class="bg-white text-green-600 bg-opacity-20 px-4 py-2 rounded-full text-lg font-semibold">
                        {{ count($pedidosListos) }} 
                    </span>
                </div>
            </div>

            <!-- Lista de pedidos listos -->
            <div id="listos-container" class="p-6 overflow-hidden relative" style="height: calc(100vh - 180px);">
                @if(count($pedidosListos) > 0)
                    <div id="listos-list" class="space-y-4">
                        @foreach($pedidosListos as $pedido)
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-8 border-green-500 rounded-xl p-6 shadow-lg transform hover:scale-102 transition-transform duration-200 animate-pulse">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="bg-green-500 text-white rounded h-16 flex items-center justify-center mr-6">
                                            <span class="text-2xl font-bold">#{{ $pedido->id }}</span>
                                        </div>
                                        <div>
                                            <h3 class="text-3xl font-bold text-gray-800">{{ $pedido->nombre ?: 'Pedido #' . $pedido->id }}</h3>
                                            <p class="text-lg text-green-600 font-semibold">¡Listo para retirar!</p>
                                        </div>
                                    </div>
                                    <div class="text-green-500">
                                        <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center text-gray-400">
                            <svg class="w-24 h-24 mx-auto mb-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h3 class="text-2xl font-medium mb-2">No hay pedidos listos</h3>
                            <p class="text-lg">Los pedidos completados aparecerán aquí</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Indicador de actualización automática -->
    <div class="fixed bottom-4 right-4 bg-gray-800 bg-opacity-90 text-white px-4 py-2 rounded-full text-sm">
        <div class="flex items-center">
            <div class="w-2 h-2 bg-orange-400 rounded-full mr-2 animate-pulse"></div>
            BCN Soft
        </div>
    </div>
    @if(session()->has('monitor_user_id'))
    <button wire:click="logout" 
            class="fixed top-4 left-4 z-50 opacity-20 hover:opacity-100 transition-opacity duration-300 bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white p-3 rounded-full shadow-lg">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
    </button>
    @endif
</div>

@push('scripts')
<script>
function startAutoScroll() {
    console.log('Iniciando auto scroll...');
    
    function scrollContainer(containerId) {
        const container = document.getElementById(containerId);
        if (!container) {
            console.log(`Container ${containerId} no encontrado`);
            return;
        }
        
        const containerHeight = container.clientHeight;
        const contentHeight = container.scrollHeight;
        
        console.log(`${containerId}: Container=${containerHeight}px, Content=${contentHeight}px`);
        
        // Solo hacer scroll si el contenido es mayor que el contenedor
        if (contentHeight <= containerHeight) {
            console.log(`${containerId}: No necesita scroll`);
            return;
        }
        
        let scrollPosition = 0;
        const scrollSpeed = 1; // píxeles por frame
        let isScrolling = true;
        
        function doScroll() {
            if (!isScrolling) return;
            
            scrollPosition += scrollSpeed;
            const maxScroll = contentHeight - containerHeight;
            
            if (scrollPosition >= maxScroll) {
                // Pausa al final
                setTimeout(() => {
                    scrollPosition = 0;
                    container.scrollTop = 0;
                    if (isScrolling) {
                        setTimeout(doScroll, 100);
                    }
                }, 3000);
                return;
            }
            
            container.scrollTop = scrollPosition;
            requestAnimationFrame(doScroll);
        }
        
        // Iniciar scroll
        setTimeout(doScroll, 1000);
        
        // Detener scroll cuando Livewire actualice
        document.addEventListener('livewire:updated', () => {
            isScrolling = false;
        }, { once: true });
    }
    
    // Iniciar scroll en ambas columnas
    scrollContainer('preparacion-container');
    scrollContainer('listos-container');
}

// Iniciar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(startAutoScroll, 1000);
});

// Reiniciar después de actualizaciones de Livewire
document.addEventListener('livewire:updated', () => {
    console.log('Livewire updated - reiniciando scroll');
    setTimeout(startAutoScroll, 500);
});

// También al inicializar Livewire
document.addEventListener('livewire:init', () => {
    console.log('Livewire init - iniciando scroll');
    setTimeout(startAutoScroll, 1000);
});

// Evitar que la pantalla se apague en dispositivos móviles
if ('wakeLock' in navigator) {
    navigator.wakeLock.request('screen').catch((err) => {
        console.log('Wake Lock no disponible:', err);
    });
}
</script>
@endpush