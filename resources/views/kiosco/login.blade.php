<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 px-4">
    <div class="w-full max-w-sm">
        <!-- Logo / Header -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-[#FFAF22] to-[#FF9500] rounded-2xl shadow-lg mb-3">
                <i class="fas fa-store text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white">Kiosco</h1>
            <p class="text-gray-400 text-sm mt-1">Ingresá tus credenciales para acceder</p>
        </div>

        <!-- Error -->
        @if($error)
            <div class="bg-red-500/20 border border-red-500/50 text-red-300 px-4 py-2.5 rounded-lg mb-4 text-sm flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                {{ $error }}
            </div>
        @endif

        <!-- Form -->
        <form wire:submit="login" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">
                    <i class="fas fa-envelope text-[#FFAF22] mr-1"></i> Email
                </label>
                <input type="email"
                       wire:model="email"
                       placeholder="tu@email.com"
                       autofocus
                       class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent transition">
                @error('email')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">
                    <i class="fas fa-lock text-[#FFAF22] mr-1"></i> Contraseña
                </label>
                <input type="password"
                       wire:model="password"
                       placeholder="Tu contraseña del kiosco"
                       class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent transition">
                @error('password')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    wire:loading.attr="disabled"
                    class="w-full py-3 bg-gradient-to-r from-[#FFAF22] to-[#FF9500] text-white font-bold rounded-lg hover:from-[#FF9500] hover:to-[#FF8400] transition shadow-lg disabled:opacity-50">
                <span wire:loading.remove>
                    <i class="fas fa-sign-in-alt mr-2"></i> Ingresar
                </span>
                <span wire:loading>
                    <i class="fas fa-spinner fa-spin mr-2"></i> Verificando...
                </span>
            </button>
        </form>

        <div class="text-center mt-6">
            <a href="{{ route('home') }}" class="text-gray-500 hover:text-gray-300 text-sm transition">
                <i class="fas fa-arrow-left mr-1"></i> Volver al inicio
            </a>
        </div>
    </div>
</div>
