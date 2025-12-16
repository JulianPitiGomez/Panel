@extends('layouts.app')

@section('content')
<div class="min-h-screen flex flex-col">
    <header class="bg-[#222036] shadow-sm">
        <div class="max-w-8xl mx-auto px-2 sm:px-3 lg:px-4 py-2">
            <div class="flex justify-center">
                <div class="flex items-center space-x-6">
                    <div class="flex items-center justify-center">
                        <img src="{{ asset('images/logo1.png') }}" alt="Logo" class="w-10 md:w-20">
                    </div>
                    <div class="text-center">
                        <h1 class="text-2xl md:text-4xl font-bold text-white">Panel de Mozos</h1>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-grow flex items-center justify-center px-4 py-8 bg-[#222036]">
        <div class="w-full max-w-md mx-auto">
            <div class="bg-[#222036] rounded-2xl shadow-xl p-8">
                <form method="POST" action="{{ route('authenticate.mozos') }}">
                    @csrf
                    <div class="mb-6">
                        <label for="mail" class="block font-secondary font-semibold text-white text-sm mb-3">
                            <i class="fas fa-envelope text-orange-500 mr-2"></i>
                            Correo Electrónico del Restaurante
                        </label>
                        <input type="email" id="mail" name="mail" value="{{ old('mail') }}"
                               placeholder="restaurante@email.com"
                               class="bg-white w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-orange-500 @error('mail') border-red-500 @enderror"
                               required>
                        @error('mail')
                            <p class="text-red-500 text-sm mt-2"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-6">
                        <label for="usuario" class="block font-secondary font-semibold text-white text-sm mb-3">
                            <i class="fas fa-user text-orange-500 mr-2"></i>
                            Usuario del Mozo
                        </label>
                        <input type="text" id="usuario" name="usuario" placeholder="Usuario"
                               class="bg-white w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-orange-500 @error('usuario') border-red-500 @enderror"
                               required>
                        @error('usuario')
                            <p class="text-red-500 text-sm mt-2"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-8">
                        <label for="password" class="block font-secondary font-semibold text-white text-sm mb-3">
                            <i class="fas fa-lock text-orange-500 mr-2"></i>
                            Contraseña
                        </label>
                        <input type="password" id="password" name="password" placeholder="••••••••"
                               class="bg-white w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-orange-500 @error('password') border-red-500 @enderror"
                               required>
                        @error('password')
                            <p class="text-red-500 text-sm mt-2"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-4">
                        <button type="submit" class="btn-primary w-full font-secondary text-lg rounded-xl py-4">
                            <div class="flex items-center justify-center space-x-2">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>Iniciar Sesión</span>
                            </div>
                        </button>
                        <a href="{{ route('home') }}" wire:navigate
                           class="block w-full text-center py-3 px-4 border-2 border-gray-300 text-gray-700 font-secondary font-semibold rounded-xl hover:border-orange-500 hover:text-orange-500 transition-colors">
                            <div class="flex items-center justify-center space-x-2">
                                <i class="fas fa-arrow-left"></i>
                                <span>Volver al Inicio</span>
                            </div>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer class="bg-[#FFAF22] border-t mt-auto">
        <div class="max-w-7xl mx-auto px-2 sm:px-2 lg:px-8 py-2">
            <div class="flex flex-row justify-between items-center">
                <div class="text-left flex-1">
                    <p class="text-sm font-secondary text-white">
                        © {{ date('Y') }} Panel Web. BCN Soft.
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
@endsection
