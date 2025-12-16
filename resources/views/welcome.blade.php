@extends('layouts.app')

@section('content')
<div class="min-h-screen flex flex-col">
    <!-- Header con Logo -->
    <header class="bg-[#222036] shadow-sm">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 pt-12">
            <div class="flex justify-center">
                <div class="flex items-center space-x-6">
                    <!-- Logo -->
                    <div class="flex items-center justify-center">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-40 md:w-70">
                    </div>
                    <div class="text-center">
                        <h1 class="text-2xl md:text-5xl font-bold text-white">Acceso Clientes</h1>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="flex-grow flex items-center justify-center px-4 py-2 bg-[#222036]">
        <div class="w-full max-w-5xl mx-auto">
            <!-- Contenedor de Botones -->
            <div class="space-y-8">
                <!-- Primera fila - 3 botones -->
                <div class="flex justify-evenly items-center flex-wrap gap-6">
                    <!-- Panel de Control -->
                    <a href="{{ route('login.panel') }}" wire:navigate
                    class="group relative bg-gradient-to-r from-[#FFAF22] to-[#FF8C00] hover:from-[#FF8C00] hover:to-[#FFAF22] 
                            text-white font-bold py-2 pr-8 pl-2 rounded-full shadow-lg hover:shadow-xl 
                            transform hover:scale-105 transition-all duration-300 ease-in-out
                            w-[80%] md:min-w-[25%] md:w-auto text-center min-h-[60px]">
                        <div class="flex items-center h-full">
                            <div class="bg-[#222036] rounded-full p-4 flex items-center justify-center ml-1">
                                <i class="fas fa-tachometer-alt text-lg text-white"></i>
                            </div>
                            <div class="flex-1 flex items-center justify-center">
                                <div class="text-base font-bold leading-tight text-center">PANEL<br>DE CONTROL</div>
                            </div>
                        </div>
                        <!-- Efecto de brillo al hover -->
                        <div class="absolute inset-0 rounded-full bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                    </a>
                    
                    <!-- Adhesión Mozos -->
                    <a href="{{ route('login.mozos') }}" wire:navigate
                    class="group relative bg-gradient-to-r from-[#FFAF22] to-[#FF8C00] hover:from-[#FF8C00] hover:to-[#FFAF22] 
                            text-white font-bold py-2 pr-8 pl-2 rounded-full shadow-lg hover:shadow-xl 
                            transform hover:scale-105 transition-all duration-300 ease-in-out
                            w-[80%] md:min-w-[25%] md:w-auto text-center min-h-[60px]">
                        <div class="flex items-center h-full">
                            <div class="bg-[#222036] rounded-full p-4 flex items-center justify-center ml-1">
                                <i class="fas fa-users text-lg text-white"></i>
                            </div>
                            <div class="flex-1 flex items-center justify-center">
                                <div class="text-base font-bold leading-tight text-center">ADICIÓN<br>MOZOS</div>
                            </div>
                        </div>
                        <div class="absolute inset-0 rounded-full bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                    </a>
                    
                    <!-- Vendedores Preventista -->
                    <a href="{{ route('login.vendedores') }}" wire:navigate
                    class="group relative bg-gradient-to-r from-[#FFAF22] to-[#FF8C00] hover:from-[#FF8C00] hover:to-[#FFAF22] 
                            text-white font-bold py-2 pr-8 pl-2 rounded-full shadow-lg hover:shadow-xl 
                            transform hover:scale-105 transition-all duration-300 ease-in-out
                            w-[80%] md:min-w-[25%] md:w-auto text-center min-h-[60px]">
                        <div class="flex items-center h-full">
                            <div class="bg-[#222036] rounded-full p-4 flex items-center justify-center ml-1">
                                <i class="fas fa-handshake text-lg text-white"></i>
                            </div>
                            <div class="flex-1 flex items-center justify-center">
                                <div class="text-base font-bold leading-tight text-center">VENDEDOR<br>PREVENTISTA</div>
                            </div>
                        </div>
                        <div class="absolute inset-0 rounded-full bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                    </a>
                </div>
                
                <!-- Segunda fila - 2 botones centrados -->
                <div class="flex justify-evenly items-center flex-wrap gap-6">
                    <!-- Espacio para centrar -->
                    <div class="hidden lg:block min-w-[200px]"></div>
                    
                    <!-- Vendedores Empresas -->
                    <a href="{{ route('login.vendedores-empresas') }}" wire:navigate
                    class="group relative bg-gradient-to-r from-[#FFAF22] to-[#FF8C00] hover:from-[#FF8C00] hover:to-[#FFAF22]
                            text-white font-bold py-2 pr-8 pl-2 rounded-full shadow-lg hover:shadow-xl
                            transform hover:scale-105 transition-all duration-300 ease-in-out
                            w-[80%] md:min-w-[25%] md:w-auto text-center min-h-[60px]">
                        <div class="flex items-center h-full">
                            <div class="bg-[#222036] rounded-full p-4 flex items-center justify-center ml-1">
                                <i class="fas fa-building text-lg text-white"></i>
                            </div>
                            <div class="flex-1 flex items-center justify-center">
                                <div class="text-base font-bold leading-tight text-center">VENDEDOR<br>EMPRESAS</div>
                            </div>
                        </div>
                        <div class="absolute inset-0 rounded-full bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                    </a>
                    
                    <!-- Monitor -->
                    <a href="{{ route('login.monitor') }}" 
                    class="group relative bg-gradient-to-r from-[#FFAF22] to-[#FF8C00] hover:from-[#FF8C00] hover:to-[#FFAF22] 
                            text-white font-bold py-2 pr-8 pl-2 rounded-full shadow-lg hover:shadow-xl 
                            transform hover:scale-105 transition-all duration-300 ease-in-out
                            w-[80%] md:min-w-[25%] md:w-auto text-center min-h-[60px]">
                        <div class="flex items-center h-full">
                            <div class="bg-[#222036] rounded-full p-4 flex items-center justify-center ml-1">
                                <i class="fas fa-desktop text-lg text-white"></i>
                            </div>
                            <div class="flex-1 flex items-center justify-center">
                                <div class="text-base font-bold leading-tight text-center">VER<br>MONITOR</div>
                            </div>
                        </div>
                        <div class="absolute inset-0 rounded-full bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                    </a>
                    
                    <!-- Espacio para centrar -->
                    <div class="hidden lg:block min-w-[200px]"></div>
                </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-[#FFAF22] border-t mt-auto">
        <div class="max-w-7xl mx-auto px-2 sm:px-2 lg:px-8 py-2">
            <div class="flex flex-row justify-between items-center">
                <div class="text-left flex-1">
                    <p class="text-sm font-secondary text-white">
                        © {{ date('Y') }} Panel Web.
                        <span class="inline md:hidden"> BCN Soft.</span>
                        <span class="hidden md:inline"> BCN Soft. Todos los derechos reservados.</span>
                    </p>
                </div>
                
                <div class="flex items-center space-x-4 flex-shrink-0">
                    <a href="https://www.instagram.com/bcnsoft" target="_blank" class="text-white hover:text-orange-500 transition-colors">
                        <i class="fab fa-instagram text-xl"></i>
                    </a>
                    <a href="https://www.facebook.com/bcnsoft" target="_blank" class="text-white hover:text-orange-500 transition-colors">
                        <i class="fab fa-facebook text-xl"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>
</div>

<style>
/* Estilos adicionales para mejorar la apariencia */
.group:hover i {
    transform: rotate(5deg);
    transition: transform 0.3s ease-in-out;
}

/* Animación de pulso suave */
@keyframes pulse-soft {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

.group:hover {
    animation: pulse-soft 2s infinite;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .min-w-\[200px\] {
        min-width: 180px;
    }
}
</style>
@endsection