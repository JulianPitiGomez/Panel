<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 flex flex-col">
    <!-- Header -->
    <div class="bg-gray-800 border-b border-gray-700 p-4">
        <div class="container mx-auto flex items-center justify-between">
            <h1 class="text-2xl font-primary font-bold text-white flex items-center">
                <i class="fas fa-barcode text-orange-500 mr-3"></i>
                Consulta de Precios
            </h1>
            <div class="text-sm text-gray-300 font-secondary">
                {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="flex-1 flex flex-col justify-center items-center p-4">
        
        @if(!$mostrarInfo)
        <!-- Estado Inicial - Esperando Código -->
        <div class="text-center space-y-1 max-w-2xl mx-auto">
            
            <!-- Título -->
            <h2 class="text-4xl font-primary font-bold text-white mb-2">
                Escanee el código de barras
            </h2>
            
            <!-- Campo de Entrada -->
            <div class="space-y-4">
                <div class="relative max-w-md mx-auto">
                    <div
                            x-data
                            x-init="$nextTick(() => { 
                                $refs.input?.focus();
                            })"
                            wire:ignore.self
                        >
                        <input 
                            type="text" 
                            wire:model.live.debounce.500ms="codigoProducto" 
                            wire:keydown.enter="buscarProducto"
                            placeholder="Código del producto..."
                            class="w-full px-6 py-4 text-2xl font-secondary text-center border-2 border-gray-600 rounded-xl bg-gray-800 text-white placeholder-gray-400 focus:border-orange-500 focus:ring-2 focus:ring-orange-500 focus:ring-opacity-50 transition-all duration-200"
                            autofocus
                            autocomplete="off"
                            autocorrect="off"
                            autocapitalize="off"
                            spellcheck="false"
                            id="codigoInput">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-4">
                            <i class="fas fa-search text-gray-400 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <p class="text-sm text-gray-400 font-secondary">
                    O escriba el código manualmente y presione Enter
                </p>
            </div>

        </div>
        
        @else
        <!-- Información del Producto -->
        <div class="text-center space-y-6 max-w-4xl mx-auto animate-fadeIn">
            
            @if($producto && $producto->nombre !== 'PRODUCTO NO ENCONTRADO')
            <!-- Producto Encontrado -->
            <div class="space-y-8">
                <!-- Nombre del Producto -->
                <div class="bg-gray-800 bg-opacity-50 rounded-2xl p-8 border border-gray-600">
                    <h2 class="text-4xl md:text-3xl font-primary font-bold text-white leading-tight">
                        {{ $producto->nombre }}
                    </h2>
                </div>
                
                <!-- Precio -->
                @if($esPromocion)
                <!-- Promoción Activa -->
                <div class="relative">
                    <!-- Badge de Oferta -->
                    <div class="absolute -top-4 -right-4 bg-red-500 text-white px-6 py-2 rounded-full transform rotate-12 shadow-lg z-10">
                        <span class="font-bold text-lg">¡OFERTA!</span>
                    </div>
                    
                    <div class="bg-gradient-to-r from-red-500 to-orange-500 rounded-2xl p-8 shadow-2xl border-4 border-red-400">
                        <!-- Precio Original Tachado -->
                        <div class="text-2xl text-red-100 font-secondary mb-2">
                            <span class="line-through opacity-75">Precio normal: ${{ $this->getPrecioOriginal() }}</span>
                        </div>
                        
                        <!-- Precio de Oferta -->
                        <div class="text-4xl md:text-8xl font-primary font-black text-white mb-4">
                            ${{ $this->getPrecioMostrar() }}
                        </div>
                        
                        <!-- Información de la Promoción -->
                        <div class="space-y-2">
                            <p class="text-2xl font-secondary font-bold text-red-100">
                                {{ $promocion->nompromo }}
                            </p>
                            <p class="text-xl font-secondary text-red-100">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                Válida hasta: {{ $this->getFechaVencimientoPromocion() }}
                            </p>
                        </div>
                        
                        <!-- Ahorro -->
                        @php
                            $ahorro = $producto->precioven - $promocion->precio_especial;
                        @endphp
                        @if($ahorro > 0)
                        <div class="mt-4 bg-red-600 bg-opacity-50 rounded-xl p-4">
                            <p class="text-xl font-secondary font-bold text-white">
                                <i class="fas fa-piggy-bank mr-2"></i>
                                Ahorra: ${{ number_format($ahorro, 2) }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
                
                @else
                <!-- Precio Normal -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-2xl p-8 shadow-2xl border-2 border-blue-500">
                    <div class="text-4xl md:text-8xl font-primary font-black text-white">
                        ${{ $this->getPrecioMostrar() }}
                    </div>
                    <p class="text-2xl font-secondary text-blue-100 mt-4">
                        Precio vigente
                    </p>
                </div>
                @endif
                
                <!-- Código del Producto -->
                <div class="bg-gray-700 bg-opacity-50 rounded-xl p-4">
                    <p class="text-xl font-secondary text-gray-300">
                        <i class="fas fa-barcode mr-2"></i>
                        Código: {{ $codigoProducto }}
                    </p>
                </div>
            </div>
            
            @else
            <!-- Producto No Encontrado -->
            <div class="space-y-8">
                <div class="bg-red-600 bg-opacity-20 rounded-full p-8 mx-auto w-32 h-32 flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-500 text-6xl"></i>
                </div>
                
                <div class="bg-red-800 bg-opacity-50 rounded-2xl p-8 border-2 border-red-600">
                    <h2 class="text-4xl md:text-5xl font-primary font-bold text-red-300 mb-4">
                        PRODUCTO NO ENCONTRADO
                    </h2>
                    <p class="text-xl font-secondary text-red-200">
                        <i class="fas fa-barcode mr-2"></i>
                        Código: {{ $codigoProducto }}
                    </p>
                </div>
                
                <p class="text-lg font-secondary text-gray-300">
                    Verifique el código e intente nuevamente
                </p>
            </div>
            @endif
            
            <!-- Contador de Tiempo -->
            <div class="mt-8">
                <div class="bg-gray-800 bg-opacity-50 rounded-xl p-4">
                    <p class="text-lg font-secondary text-gray-300">
                        <i class="fas fa-clock mr-2"></i>
                        La pantalla se limpiará automáticamente en <span id="contador" class="font-bold text-orange-500">{{ $tiempoMostrar }}</span> segundos
                    </p>
                </div>
            </div>
            
            <!-- Botón Manual de Limpiar -->
            <div class="mt-6">
                <button 
                    wire:click="limpiarPantalla" 
                    class="px-8 py-4 bg-gray-700 hover:bg-gray-600 text-white rounded-xl font-secondary text-lg transition-colors duration-200">
                    <i class="fas fa-refresh mr-2"></i>
                    Nueva Consulta
                </button>
            </div>
        </div>
        @endif
    </div>
    
    <!-- Footer -->
    <div class="bg-gray-800 border-t border-gray-700 p-4">
        <div class="container mx-auto text-center">
            <p class="text-sm text-gray-400 font-secondary">
                Escanee un código de barras o escriba el código del producto para consultar precios
            </p>
        </div>
    </div>
</div>
    <!-- JavaScript para Control de Tiempo y Enfoque -->
    @push('scripts')
    <script>
    document.addEventListener('livewire:initialized', () => {
        let temporizadorActivo = null;

        // Función para enfocar el campo de entrada
        function enfocarCampo() {
            setTimeout(() => {
                const input = document.getElementById('codigoInput');
                if (input) {
                    input.focus();
                    input.select();
                }
            }, 100);
        }

        // Función para iniciar el temporizador
        function iniciarTemporizador(tiempo = 10) {
            if (temporizadorActivo) {
                clearInterval(temporizadorActivo);
            }

            let segundosRestantes = tiempo;
            const contadorElement = document.getElementById('contador');

            if (contadorElement) {
                contadorElement.textContent = segundosRestantes;
            }

            temporizadorActivo = setInterval(() => {
                segundosRestantes--;

                if (contadorElement) {
                    contadorElement.textContent = segundosRestantes;

                    // Cambiar color según el tiempo restante
                    if (segundosRestantes <= 3) {
                        contadorElement.className = 'font-bold text-red-500 animate-pulse';
                    } else if (segundosRestantes <= 5) {
                        contadorElement.className = 'font-bold text-yellow-500';
                    } else {
                        contadorElement.className = 'font-bold text-orange-500';
                    }
                }

                if (segundosRestantes <= 0) {
                    clearInterval(temporizadorActivo);
                    Livewire.dispatch('limpiarPantalla');
                    enfocarCampo();
                }
            }, 1000);
        }

        // Escuchar eventos Livewire
        Livewire.on('iniciarTemporizador', (data = {}) => {
            const tiempo = data.tiempo || 10;
            iniciarTemporizador(tiempo);
        });

        Livewire.on('enfocarCampo', () => {
            enfocarCampo();
        });

        // Enfocar cuando se actualiza Livewire (ej: se limpia la pantalla)
        Livewire.hook('message.processed', (message, component) => {
            enfocarCampo();
        });

        // Enfocar al cargar
        enfocarCampo();

        // Limpiar temporizador al salir
        window.addEventListener('beforeunload', () => {
            if (temporizadorActivo) {
                clearInterval(temporizadorActivo);
            }
        });
    });
    </script>


    <!-- Estilos adicionales -->
    <style>
    .animate-fadeIn {
        animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Evitar zoom en móviles */
    input[type="text"] {
        font-size: 16px !important;
    }

    @media (max-width: 768px) {
        input[type="text"] {
            font-size: 18px !important;
        }
    }
    </style>
    @endpush


