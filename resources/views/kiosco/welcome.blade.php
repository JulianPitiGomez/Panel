{{-- WELCOME / ATTRACT SCREEN --}}
<div class="h-full flex flex-col items-center justify-center text-center p-8 cursor-pointer"
     style="background-color: var(--kiosco-fondo);"
     wire:click="changeView('menu')">

    <!-- Logo -->
    <div class="mb-6 animate-fade-in">
        <img src="{{ asset('img/' . $clientId . '/logo.jpg') }}"
             alt="Logo"
             class="h-32 w-auto mx-auto rounded-2xl shadow-xl"
             onerror="this.style.display='none'">
    </div>

    <!-- Nombre del comercio -->
    @if($comercioData)
        <h1 class="text-4xl md:text-5xl font-bold mb-2 animate-fade-in" style="color: var(--kiosco-letra);">
            {{ $comercioData->nombre ?? 'Bienvenido' }}
        </h1>
        @if($comercioData->slogan)
            <p class="text-lg md:text-xl mb-8 opacity-70" style="color: var(--kiosco-letra);">
                {{ $comercioData->slogan }}
            </p>
        @endif
    @else
        <h1 class="text-4xl md:text-5xl font-bold mb-8 animate-fade-in" style="color: var(--kiosco-letra);">
            Bienvenido
        </h1>
    @endif

    <!-- Call to action -->
    <div class="mt-4 animate-pulse-slow">
        <div class="inline-flex items-center gap-3 px-10 py-5 rounded-2xl text-xl md:text-2xl font-bold shadow-xl transition-transform hover:scale-105"
             style="background-color: var(--kiosco-boton); color: var(--kiosco-texto-boton);">
            <i class="fas fa-hand-pointer"></i>
            Tocá para comenzar
        </div>
    </div>

    <p class="mt-6 text-sm opacity-50" style="color: var(--kiosco-letra);">
        Kiosco de Autogestión
    </p>
</div>

<style>
    .animate-fade-in {
        animation: welcomeFadeIn 0.8s ease-out;
    }

    .animate-pulse-slow {
        animation: welcomePulse 2.5s ease-in-out infinite;
    }

    @keyframes welcomeFadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes welcomePulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
</style>
