<div class="space-y-6">

    {{-- Toast de notificaciones --}}
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    {{-- ===== SECCIÓN TIENDA WEB ===== --}}
    @if($tieneTiendaWeb)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        {{-- Card Toggle Tienda --}}
        <div class="bg-gradient-to-br from-[#222036] to-[#2d2a4a] rounded-xl shadow-lg p-4 border border-[#FFAF22]/20 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-[#FFAF22]/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>

            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-9 h-9 bg-[#FFAF22] rounded-lg flex items-center justify-center shadow">
                            <i class="fas fa-store text-white text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-primary font-bold text-white">Tienda Web</h3>
                            @if($nombreTienda)
                            <p class="text-xs text-gray-400">{{ $nombreTienda }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $tiendaAbierta ? 'bg-green-400' : 'bg-red-400' }} opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 {{ $tiendaAbierta ? 'bg-green-500' : 'bg-red-500' }}"></span>
                        </span>
                        <span class="text-xs font-semibold {{ $tiendaAbierta ? 'text-green-400' : 'text-red-400' }}">
                            {{ $tiendaAbierta ? 'ABIERTA' : 'CERRADA' }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between bg-white/5 rounded-lg p-3">
                    <div>
                        <p class="text-white text-sm font-medium">Estado de la tienda</p>
                        <p class="text-gray-400 text-xs">{{ $tiendaAbierta ? 'Clientes pueden pedir' : 'Tienda cerrada' }}</p>
                    </div>

                    <button
                        wire:click="toggleTiendaAbierta"
                        wire:loading.attr="disabled"
                        class="relative inline-flex h-6 w-12 items-center rounded-full transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-[#FFAF22] {{ $tiendaAbierta ? 'bg-green-500' : 'bg-gray-600' }}"
                    >
                        <span class="sr-only">Toggle tienda</span>
                        <span
                            class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform duration-300 {{ $tiendaAbierta ? 'translate-x-6' : 'translate-x-0.5' }}"
                            wire:loading.class="opacity-50"
                        >
                            <span class="flex h-full w-full items-center justify-center">
                                <i class="fas {{ $tiendaAbierta ? 'fa-check text-green-500' : 'fa-times text-gray-400' }} text-[8px]"></i>
                            </span>
                        </span>
                    </button>
                </div>

                {{-- Botón ir a la tienda --}}
                @if($nombreTienda)
                <a
                    href="https://bcnresto.com/{{ $nombreTienda }}"
                    target="_blank"
                    class="mt-3 flex items-center justify-center gap-2 bg-[#FFAF22] hover:bg-[#e69e1f] text-white text-xs font-semibold py-2 px-3 rounded-lg transition-all hover:shadow-lg"
                >
                    <i class="fas fa-external-link-alt"></i>
                    <span>Ir a la tienda</span>
                </a>
                @endif

                <div wire:loading wire:target="toggleTiendaAbierta" class="mt-2 flex items-center gap-2 text-[#FFAF22]">
                    <i class="fas fa-spinner fa-spin text-xs"></i>
                    <span class="text-xs">Actualizando...</span>
                </div>
            </div>
        </div>

        {{-- Card Estado de Cuenta / Comisiones (Compacto) --}}
        @php
            $saldoFinal = $saldoComision;
            $ivaComision = 0;
            if($discIva == 3) {
                $ivaComision = $saldoComision * 0.21;
                $saldoFinal = $saldoComision * 1.21;
            }
        @endphp

        @if($tieneComisiones && $saldoComision > 0)
        <button
            wire:click="$set('mostrarModalComisiones', true)"
            class="w-full text-left rounded-xl shadow-lg border overflow-hidden transition-all hover:shadow-xl hover:scale-[1.01] {{ $diasLimComision < 0 ? 'bg-gradient-to-br from-red-50 to-red-100 border-red-200' : 'bg-gradient-to-br from-[#FFF8E7] to-[#FFEDCC] border-[#FFAF22]/30' }}"
        >
            <div class="p-4">
                {{-- Header --}}
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-9 h-9 {{ $diasLimComision < 0 ? 'bg-red-500' : 'bg-[#FFAF22]' }} rounded-lg flex items-center justify-center">
                            <i class="fas {{ $diasLimComision < 0 ? 'fa-ban' : 'fa-file-invoice-dollar' }} text-white text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-primary font-bold {{ $diasLimComision < 0 ? 'text-red-800' : 'text-[#222036]' }}">
                                {{ $diasLimComision < 0 ? 'Cuenta Inhabilitada' : 'Estado de Cuenta' }}
                            </h3>
                            <p class="text-[10px] {{ $diasLimComision < 0 ? 'text-red-500' : 'text-[#B8860B]' }}">
                                {{ $fechaIniComision ? \Carbon\Carbon::parse($fechaIniComision)->format('d/m/Y') : '-' }} - Hoy
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        @if($diasLimComision <= 5 && $diasLimComision >= 0)
                        <span class="bg-[#FFAF22] text-white text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $diasLimComision }}d</span>
                        @elseif($diasLimComision < 0)
                        <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full animate-pulse">!</span>
                        @endif
                        <i class="fas fa-chevron-right {{ $diasLimComision < 0 ? 'text-red-400' : 'text-[#FFAF22]' }} text-xs"></i>
                    </div>
                </div>

                {{-- Info resumida --}}
                <div class="flex items-center justify-between gap-2 bg-white/60 rounded-lg p-2">
                    <div class="flex-1 text-center border-r {{ $diasLimComision < 0 ? 'border-red-200' : 'border-[#FFAF22]/20' }}">
                        <p class="text-lg font-bold {{ $diasLimComision < 0 ? 'text-red-700' : 'text-[#222036]' }}">{{ $cantidadPedidos }}</p>
                        <p class="text-[10px] {{ $diasLimComision < 0 ? 'text-red-500' : 'text-[#B8860B]' }}">Pedidos</p>
                    </div>
                    <div class="flex-1 text-center border-r {{ $diasLimComision < 0 ? 'border-red-200' : 'border-[#FFAF22]/20' }}">
                        <p class="text-lg font-bold {{ $diasLimComision < 0 ? 'text-red-700' : 'text-[#FFAF22]' }}">{{ $moneda }}{{ number_format($saldoFinal, 0) }}</p>
                        <p class="text-[10px] {{ $diasLimComision < 0 ? 'text-red-500' : 'text-[#B8860B]' }}">Saldo</p>
                    </div>
                    <div class="flex-1 text-center">
                        <p class="text-lg font-bold {{ $diasLimComision < 0 ? 'text-red-700' : 'text-[#222036]' }}">{{ $fechaLimComision ? \Carbon\Carbon::parse($fechaLimComision)->format('d/m') : '-' }}</p>
                        <p class="text-[10px] {{ $diasLimComision < 0 ? 'text-red-500' : 'text-[#B8860B]' }}">{{ $diasLimComision < 0 ? 'Vencido' : 'Vence' }}</p>
                    </div>
                </div>
            </div>
        </button>
        @else
        {{-- Card sin comisiones pendientes --}}
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl shadow-lg border border-green-100 p-4 flex flex-col justify-center">
            <div class="flex items-center gap-2">
                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-check text-white text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-primary font-bold text-green-800">Sin Saldo Pendiente</h3>
                    <p class="text-green-600 text-xs">No tienes comisiones pendientes</p>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Modal de Comisiones --}}
    @if($mostrarModalComisiones)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            {{-- Overlay --}}
            <div
                class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                wire:click="$set('mostrarModalComisiones', false)"
            ></div>

            {{-- Modal --}}
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full mx-auto transform transition-all overflow-hidden">
                {{-- Header --}}
                <div class="px-5 py-4 {{ $diasLimComision < 0 ? 'bg-gradient-to-r from-red-500 to-red-600' : 'bg-gradient-to-r from-[#FFAF22] to-[#E69A1E]' }}">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                                <i class="fas {{ $diasLimComision < 0 ? 'fa-ban' : 'fa-file-invoice-dollar' }} text-white text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-white font-primary font-bold">
                                    {{ $diasLimComision < 0 ? 'Cuenta Inhabilitada' : 'Estado de Cuenta' }}
                                </h3>
                                <p class="text-white/80 text-xs">{{ $fechaIniComision ? \Carbon\Carbon::parse($fechaIniComision)->format('d/m/Y') : '-' }} - Hoy</p>
                            </div>
                        </div>
                        <button
                            wire:click="$set('mostrarModalComisiones', false)"
                            class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center hover:bg-white/30 transition-colors"
                        >
                            <i class="fas fa-times text-white"></i>
                        </button>
                    </div>
                </div>

                {{-- Content --}}
                <div class="p-5">
                    {{-- Alerta si está inhabilitado --}}
                    @if($diasLimComision < 0)
                    <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-exclamation-triangle text-red-500 mt-0.5"></i>
                            <p class="text-red-700 text-sm">Tu cuenta está deshabilitada por falta de pago. Cancela tu saldo para rehabilitarla.</p>
                        </div>
                    </div>
                    @elseif($diasLimComision <= 5 && $diasLimComision >= 0)
                    <div class="bg-[#FFF8E7] border border-[#FFAF22]/30 rounded-xl p-3 mb-4">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-clock text-[#FFAF22] mt-0.5"></i>
                            <p class="text-[#8B6914] text-sm">Quedan <strong>{{ $diasLimComision }} días</strong> para el vencimiento. Después se inhabilitará tu suscripción.</p>
                        </div>
                    </div>
                    @endif

                    {{-- Métricas --}}
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="text-center p-3 bg-gray-50 rounded-xl">
                            <p class="text-2xl font-bold text-[#222036]">{{ $cantidadPedidos }}</p>
                            <p class="text-xs text-gray-500">Pedidos</p>
                        </div>
                        <div class="text-center p-3 {{ $diasLimComision < 0 ? 'bg-red-50' : 'bg-[#FFF8E7]' }} rounded-xl">
                            <p class="text-2xl font-bold {{ $diasLimComision < 0 ? 'text-red-600' : 'text-[#FFAF22]' }}">{{ $moneda }}{{ number_format($saldoFinal, 2) }}</p>
                            <p class="text-xs {{ $diasLimComision < 0 ? 'text-red-500' : 'text-[#B8860B]' }}">Saldo a Pagar</p>
                        </div>
                    </div>

                    {{-- Fecha límite --}}
                    <div class="flex items-center justify-between text-sm p-3 bg-gray-50 rounded-xl mb-4">
                        <span class="text-gray-600">Fecha límite:</span>
                        <span class="font-bold {{ $diasLimComision < 0 ? 'text-red-600' : 'text-[#222036]' }}">
                            {{ $fechaLimComision ? \Carbon\Carbon::parse($fechaLimComision)->format('d/m/Y') : '-' }}
                        </span>
                    </div>

                    {{-- Datos de pago --}}
                    @if($cbu || $alias)
                    <div class="border-t pt-4">
                        <p class="text-sm font-semibold text-gray-700 mb-3">Datos para transferencia:</p>
                        <div class="space-y-2">
                            @if($cbu)
                            <div class="flex items-center justify-between bg-gray-50 rounded-xl p-3">
                                <div class="min-w-0 flex-1">
                                    <span class="text-[10px] text-gray-500 uppercase">CBU</span>
                                    <p class="font-mono text-sm text-gray-800 truncate">{{ $cbu }}</p>
                                </div>
                                <button
                                    onclick="navigator.clipboard.writeText('{{ $cbu }}'); this.innerHTML='<i class=\'fas fa-check text-green-500\'></i>'; setTimeout(() => this.innerHTML='<i class=\'fas fa-copy text-[#FFAF22]\'></i>', 2000)"
                                    class="ml-2 p-2 hover:bg-gray-200 rounded-lg transition-colors flex-shrink-0"
                                    title="Copiar CBU"
                                >
                                    <i class="fas fa-copy text-[#FFAF22]"></i>
                                </button>
                            </div>
                            @endif
                            @if($alias)
                            <div class="flex items-center justify-between bg-gray-50 rounded-xl p-3">
                                <div class="min-w-0 flex-1">
                                    <span class="text-[10px] text-gray-500 uppercase">ALIAS</span>
                                    <p class="font-mono text-sm text-gray-800 truncate">{{ $alias }}</p>
                                </div>
                                <button
                                    onclick="navigator.clipboard.writeText('{{ $alias }}'); this.innerHTML='<i class=\'fas fa-check text-green-500\'></i>'; setTimeout(() => this.innerHTML='<i class=\'fas fa-copy text-[#FFAF22]\'></i>', 2000)"
                                    class="ml-2 p-2 hover:bg-gray-200 rounded-lg transition-colors flex-shrink-0"
                                    title="Copiar Alias"
                                >
                                    <i class="fas fa-copy text-[#FFAF22]"></i>
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-5 py-4 bg-gray-50 border-t">
                    <button
                        wire:click="$set('mostrarModalComisiones', false)"
                        class="w-full bg-[#222036] hover:bg-[#2d2a4a] text-white font-semibold py-2.5 px-4 rounded-xl transition-colors"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif

    <!-- Filtros de Fecha -->
    <div class="bg-white rounded-xl shadow-md p-4 border border-gray-100">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h3 class="text-lg font-primary font-semibold text-gray-800 flex items-center">
                <i class="fas fa-calendar-alt text-[#FFAF22] mr-3"></i>
                Filtros de Período
            </h3>
            <div class="flex flex-col sm:flex-row gap-3">
                <div>
                    <label class="block text-xs font-secondary text-gray-600 mb-1">Desde</label>
                    <input type="date" wire:model.live.debounce.500ms="fechaDesde"
                           class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FFAF22] focus:border-[#FFAF22]">
                </div>
                <div>
                    <label class="block text-xs font-secondary text-gray-600 mb-1">Hasta</label>
                    <input type="date" wire:model.live.debounce.500ms="fechaHasta"
                           class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FFAF22] focus:border-[#FFAF22]">
                </div>
                <div class="flex items-end">
                    <button wire:click="cargarDatos" wire:loading.attr="disabled"
                            class="bg-[#FFAF22] hover:bg-[#FFAF22]/90 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 transition-colors">
                        <i class="fas fa-sync-alt mr-2" wire:loading.class="animate-spin"></i>
                        <span wire:loading.remove>Actualizar</span>
                        <span wire:loading>Cargando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- KPIs Principales -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Ventas del Período -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-5 border border-green-200 shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-secondary text-green-700 mb-1">Ventas del Período</p>
                    <p class="text-2xl font-primary font-bold text-green-800">${{ number_format($ventasDelPeriodo, 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-dollar-sign text-white text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Compras del Período -->
        <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-5 border border-red-200 shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-secondary text-red-700 mb-1">Compras del Período</p>
                    <p class="text-2xl font-primary font-bold text-red-800">${{ number_format($comprasDelPeriodo, 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-shopping-cart text-white text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Deudas a Cobrar -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-5 border border-blue-200 shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-secondary text-blue-700 mb-1">Deudas a Cobrar</p>
                    <p class="text-2xl font-primary font-bold text-blue-800">${{ number_format($deudasACobrar, 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-hand-holding-usd text-white text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Deudas a Pagar -->
        <div class="bg-white rounded-xl p-5 border-l-4 border-[#FFAF22] shadow-md border border-gray-200 shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-secondary text-[#FFAF22] mb-1">Deudas a Pagar</p>
                    <p class="text-2xl font-primary font-bold text-[#222036]">${{ number_format($deudasAPagar, 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-[#FFAF22] rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-credit-card text-white text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen Financiero -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Sueldos del Período -->
        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-5 border border-orange-200 shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-secondary text-orange-700 mb-1">Sueldos del Período</p>
                    <p class="text-2xl font-primary font-bold text-orange-800">${{ number_format($sueldosDelPeriodo, 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-users text-white text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Ganancia Neta -->
        <div class="bg-gradient-to-br {{ $gananciaNeta >= 0 ? 'from-emerald-50 to-emerald-100 border-emerald-200' : 'from-rose-50 to-rose-100 border-rose-200' }} rounded-xl p-5 border shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-secondary {{ $gananciaNeta >= 0 ? 'text-emerald-700' : 'text-rose-700' }} mb-1">Ganancia Neta</p>
                    <p class="text-2xl font-primary font-bold {{ $gananciaNeta >= 0 ? 'text-emerald-800' : 'text-rose-800' }}">${{ number_format($gananciaNeta, 2) }}</p>
                </div>
                <div class="w-12 h-12 {{ $gananciaNeta >= 0 ? 'bg-emerald-500' : 'bg-rose-500' }} rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas {{ $gananciaNeta >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }} text-white text-xl"></i>
                </div>
            </div>
        </div>

        <!-- % Utilidad Global -->
        <div class="bg-gradient-to-br {{ $utilidadGlobal >= 0 ? 'from-indigo-50 to-indigo-100 border-indigo-200' : 'from-rose-50 to-rose-100 border-rose-200' }} rounded-xl p-5 border shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-secondary {{ $utilidadGlobal >= 0 ? 'text-indigo-700' : 'text-rose-700' }} mb-1">% Utilidad Global</p>
                    <p class="text-2xl font-primary font-bold {{ $utilidadGlobal >= 0 ? 'text-indigo-800' : 'text-rose-800' }}">{{ number_format($utilidadGlobal, 2) }}%</p>
                </div>
                <div class="w-12 h-12 {{ $utilidadGlobal >= 0 ? 'bg-indigo-500' : 'bg-rose-500' }} rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-percentage text-white text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Botón Estado de Resultado -->
    <div class="flex justify-center">
        <button wire:click="verEstadoResultado"
                class="inline-flex items-center gap-3 bg-gradient-to-r from-[#222036] to-[#2d2a4a] hover:from-[#2d2a4a] hover:to-[#222036] text-white font-semibold px-6 py-3 rounded-xl shadow-lg hover:shadow-xl transition-all">
            <i class="fas fa-chart-pie text-[#FFAF22] text-lg"></i>
            <span>Ver Estado de Resultado completo</span>
            <i class="fas fa-arrow-right text-sm"></i>
        </button>
    </div>

    <!-- Gráfico de Barras: Ventas y Compras -->
    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
        <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-chart-bar text-[#FFAF22] mr-3"></i>
            Ventas y Compras por Día
        </h3>
        <div class="relative" style="height: 350px;"
             data-ventas="{{ json_encode($ventasPorDia) }}"
             data-compras="{{ json_encode($comprasPorDia) }}">
            <canvas id="chartVentasComprasResto"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Productos Más Vendidos -->
        @if(count($productosMasVendidos) > 0)
        <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
            <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-star text-yellow-500 mr-3"></i>
                Productos Más Vendidos
            </h3>
            <div class="overflow-y-auto" style="max-height: 340px;">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-secondary font-semibold text-gray-600">Producto</th>
                            <th class="px-4 py-3 text-center text-xs font-secondary font-semibold text-gray-600">Cantidad</th>
                            <th class="px-4 py-3 text-right text-xs font-secondary font-semibold text-gray-600">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($productosMasVendidos as $producto)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-secondary text-gray-800">
                                <div class="flex items-center gap-2">
                                    @if($loop->iteration <= 3)
                                        @if($loop->iteration == 1)
                                            <div class="flex items-center justify-center w-7 h-7 bg-[#FFAF22] rounded-full">
                                                <i class="fas fa-medal text-white text-sm"></i>
                                            </div>
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded-full">1º</span>
                                        @elseif($loop->iteration == 2)
                                            <i class="fas fa-medal text-gray-400 text-lg"></i>
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-200 rounded-full">2º</span>
                                        @elseif($loop->iteration == 3)
                                            <i class="fas fa-medal text-orange-600 text-lg"></i>
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-semibold text-orange-800 bg-orange-100 rounded-full">3º</span>
                                        @endif
                                    @endif
                                    <span>{{ str_replace('(w)', '', $producto['nombre']) }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm font-secondary text-center text-gray-600">{{ number_format($producto['cantidad']) }}</td>
                            <td class="px-4 py-3 text-sm font-secondary text-right font-semibold text-gray-800">${{ number_format($producto['total'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Ventas por Departamento - Tabla -->
        @if(count($ventasPorDepartamento) > 0)
        <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
            <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-utensils text-purple-500 mr-3"></i>
                Ventas por Departamento
            </h3>
            <div class="overflow-y-auto" style="max-height: 340px;">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-secondary font-semibold text-gray-600">Departamento</th>
                            <th class="px-4 py-3 text-right text-xs font-secondary font-semibold text-gray-600">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($ventasPorDepartamento as $depto)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-secondary text-gray-800">{{ $depto['departamento'] }}</td>
                            <td class="px-4 py-3 text-sm font-secondary text-right font-semibold text-purple-800">${{ number_format($depto['total'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <!-- Gráfico de Departamentos -->
    @if(count($ventasPorDepartamento) > 0)
    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
        <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-chart-bar text-purple-500 mr-3"></i>
            Distribución de Ventas por Departamento
        </h3>
        <div class="relative" style="height: 300px;"
             data-departamentos="{{ json_encode($ventasPorDepartamento) }}">
            <canvas id="chartDepartamentosResto"></canvas>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('livewire:init', () => {
        let chartVentasCompras = null;
        let chartDepartamentos = null;

        const renderCharts = () => {
            // Gráfico de Ventas y Compras
            const ctxVentas = document.getElementById('chartVentasComprasResto');
            if (ctxVentas) {
                if (chartVentasCompras) {
                    chartVentasCompras.destroy();
                }

                // Leer datos desde los atributos data
                const container = ctxVentas.parentElement;
                const ventasPorDia = JSON.parse(container.getAttribute('data-ventas') || '[]');
                const comprasPorDia = JSON.parse(container.getAttribute('data-compras') || '[]');

                console.log('Ventas por día:', ventasPorDia);
                console.log('Compras por día:', comprasPorDia);

                const todasFechas = [...new Set([
                    ...ventasPorDia.map(v => v.fecha),
                    ...comprasPorDia.map(c => c.fecha)
                ])].sort();

                console.log('Todas las fechas:', todasFechas);

                const ventasData = todasFechas.map(fecha => {
                    const item = ventasPorDia.find(v => v.fecha === fecha);
                    return item ? item.total : 0;
                });

                const comprasData = todasFechas.map(fecha => {
                    const item = comprasPorDia.find(c => c.fecha === fecha);
                    return item ? item.total : 0;
                });

                console.log('Datos de ventas para gráfico:', ventasData);
                console.log('Datos de compras para gráfico:', comprasData);

                chartVentasCompras = new Chart(ctxVentas, {
                    type: 'bar',
                    data: {
                        labels: todasFechas,
                        datasets: [
                            {
                                label: 'Ventas',
                                data: ventasData,
                                backgroundColor: 'rgba(34, 197, 94, 0.8)',
                                borderColor: 'rgb(34, 197, 94)',
                                borderWidth: 1
                            },
                            {
                                label: 'Compras',
                                data: comprasData,
                                backgroundColor: 'rgba(239, 68, 68, 0.8)',
                                borderColor: 'rgb(239, 68, 68)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += '$' + context.parsed.y.toLocaleString();
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Gráfico de Departamentos
            const ctxDeptos = document.getElementById('chartDepartamentosResto');
            if (ctxDeptos) {
                if (chartDepartamentos) {
                    chartDepartamentos.destroy();
                }

                // Leer datos desde los atributos data
                const containerDeptos = ctxDeptos.parentElement;
                const departamentos = JSON.parse(containerDeptos.getAttribute('data-departamentos') || '[]');

                console.log('Departamentos:', departamentos);

                if (departamentos && departamentos.length > 0) {
                    const labels = departamentos.map(d => d.departamento);
                    const data = departamentos.map(d => d.total);

                    chartDepartamentos = new Chart(ctxDeptos, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Ventas por Departamento',
                                data: data,
                                backgroundColor: 'rgba(147, 51, 234, 0.8)',
                                borderColor: 'rgb(147, 51, 234)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return '$' + context.parsed.x.toLocaleString();
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return '$' + value.toLocaleString();
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
        };

        // Renderizar al cargar
        renderCharts();

        // Re-renderizar cuando se actualicen los datos
        Livewire.on('datos-actualizados', () => {
            setTimeout(renderCharts, 200);
        });

        // Sistema de notificaciones toast
        Livewire.on('notify', (params) => {
            const { type, message } = params[0] || params;
            showToast(type, message);
        });

        function showToast(type, message) {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-[#FFAF22]';
            const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-times-circle' : 'fa-info-circle';

            toast.className = `${bgColor} text-white px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3 transform translate-x-full opacity-0 transition-all duration-300 max-w-sm`;
            toast.innerHTML = `
                <i class="fas ${icon} text-xl"></i>
                <span class="font-medium">${message}</span>
            `;

            container.appendChild(toast);

            // Animar entrada
            requestAnimationFrame(() => {
                toast.classList.remove('translate-x-full', 'opacity-0');
            });

            // Remover después de 3 segundos
            setTimeout(() => {
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    });
</script>
@endpush
