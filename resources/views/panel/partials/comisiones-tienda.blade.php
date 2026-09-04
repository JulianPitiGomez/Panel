{{-- Estado de cuenta de la tienda web (comisiones). Requiere $tieneComisiones, $comisiones, $discIva, $cbu, $alias, $mostrarModalComisiones --}}
@php
    $saldo = $comisiones['saldo'] ?? 0;
    $diasLim = $comisiones['dias_lim'];
    $vencido = $diasLim !== null && $diasLim < 0;
    $porVencer = $diasLim !== null && $diasLim >= 0 && $diasLim <= 5;
    $ivaComision = $discIva == 3 ? $saldo * 0.21 : 0;
    $saldoFinal = $saldo + $ivaComision;
    $fmtFecha = fn ($f, $fmt = 'd/m/Y') => $f ? \Carbon\Carbon::parse($f)->format($fmt) : '-';
@endphp

@if($saldo > 0)
<button type="button" wire:click="$set('mostrarModalComisiones', true)"
    class="w-full text-left rounded-xl shadow-md border overflow-hidden transition-all hover:shadow-lg {{ $vencido ? 'bg-gradient-to-br from-red-50 to-red-100 border-red-200' : 'bg-gradient-to-br from-[#FFF8E7] to-[#FFEDCC] border-[#FFAF22]/30' }}">
    <div class="p-4">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <div class="w-9 h-9 {{ $vencido ? 'bg-red-500' : 'bg-[#FFAF22]' }} rounded-lg flex items-center justify-center">
                    <i class="fas {{ $vencido ? 'fa-ban' : 'fa-store' }} text-white text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-primary font-bold {{ $vencido ? 'text-red-800' : 'text-[#222036]' }}">
                        {{ $vencido ? 'Tienda web: cuenta vencida' : 'Tienda web: estado de cuenta' }}
                    </h3>
                    <p class="text-[10px] {{ $vencido ? 'text-red-500' : 'text-[#B8860B]' }}">
                        {{ $fmtFecha($comisiones['fecha_ini']) }} - Hoy
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-1">
                @if($porVencer)
                    <span class="bg-[#FFAF22] text-white text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $diasLim }}d</span>
                @elseif($vencido)
                    <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full animate-pulse">!</span>
                @endif
                <i class="fas fa-chevron-right {{ $vencido ? 'text-red-400' : 'text-[#FFAF22]' }} text-xs"></i>
            </div>
        </div>

        <div class="flex items-center justify-between gap-2 bg-white/60 rounded-lg p-2">
            <div class="flex-1 text-center border-r {{ $vencido ? 'border-red-200' : 'border-[#FFAF22]/20' }}">
                <p class="text-lg font-bold {{ $vencido ? 'text-red-700' : 'text-[#222036]' }}">{{ $comisiones['cantidad'] }}</p>
                <p class="text-[10px] {{ $vencido ? 'text-red-500' : 'text-[#B8860B]' }}">Pedidos</p>
            </div>
            <div class="flex-1 text-center border-r {{ $vencido ? 'border-red-200' : 'border-[#FFAF22]/20' }}">
                <p class="text-lg font-bold {{ $vencido ? 'text-red-700' : 'text-[#FFAF22]' }}">${{ number_format($saldoFinal, 0, ',', '.') }}</p>
                <p class="text-[10px] {{ $vencido ? 'text-red-500' : 'text-[#B8860B]' }}">Saldo</p>
            </div>
            <div class="flex-1 text-center">
                <p class="text-lg font-bold {{ $vencido ? 'text-red-700' : 'text-[#222036]' }}">{{ $fmtFecha($comisiones['fecha_lim'], 'd/m') }}</p>
                <p class="text-[10px] {{ $vencido ? 'text-red-500' : 'text-[#B8860B]' }}">{{ $vencido ? 'Vencido' : 'Vence' }}</p>
            </div>
        </div>
    </div>
</button>
@else
<div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl shadow-md border border-green-100 p-4 flex items-center gap-3">
    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
        <i class="fas fa-store text-white text-sm"></i>
    </div>
    <div>
        <h3 class="text-sm font-primary font-bold text-green-800">Tienda web: sin saldo pendiente</h3>
        <p class="text-green-600 text-xs">No tenés comisiones a pagar por ahora.</p>
    </div>
</div>
@endif

@if($mostrarModalComisiones)
<div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="$set('mostrarModalComisiones', false)"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full mx-auto text-left overflow-hidden">
            <div class="px-5 py-4 {{ $vencido ? 'bg-gradient-to-r from-red-500 to-red-600' : 'bg-gradient-to-r from-[#FFAF22] to-[#E69500]' }}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                            <i class="fas {{ $vencido ? 'fa-ban' : 'fa-store' }} text-white text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-white font-primary font-bold">{{ $vencido ? 'Cuenta vencida' : 'Estado de cuenta' }}</h3>
                            <p class="text-white/80 text-xs">Tienda web · desde {{ $fmtFecha($comisiones['fecha_ini']) }}</p>
                        </div>
                    </div>
                    <button type="button" wire:click="$set('mostrarModalComisiones', false)"
                        class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center hover:bg-white/30">
                        <i class="fas fa-times text-white"></i>
                    </button>
                </div>
            </div>

            <div class="p-5">
                @if($vencido)
                    <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4 flex items-start gap-2">
                        <i class="fas fa-exclamation-triangle text-red-500 mt-0.5"></i>
                        <p class="text-red-700 text-sm">El plazo para pagar la comisión venció. Cancelá el saldo para mantener la tienda activa.</p>
                    </div>
                @elseif($porVencer)
                    <div class="bg-[#FFF8E7] border border-[#FFAF22]/30 rounded-xl p-3 mb-4 flex items-start gap-2">
                        <i class="fas fa-clock text-[#FFAF22] mt-0.5"></i>
                        <p class="text-[#8B6914] text-sm">Quedan <strong>{{ $diasLim }} días</strong> para el vencimiento.</p>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="text-center p-3 bg-gray-50 rounded-xl">
                        <p class="text-2xl font-bold text-[#222036]">{{ $comisiones['cantidad'] }}</p>
                        <p class="text-xs text-gray-500">Pedidos con saldo</p>
                    </div>
                    <div class="text-center p-3 {{ $vencido ? 'bg-red-50' : 'bg-[#FFF8E7]' }} rounded-xl">
                        <p class="text-2xl font-bold {{ $vencido ? 'text-red-600' : 'text-[#FFAF22]' }}">${{ number_format($saldoFinal, 2, ',', '.') }}</p>
                        <p class="text-xs {{ $vencido ? 'text-red-500' : 'text-[#B8860B]' }}">Saldo a pagar</p>
                    </div>
                </div>

                <div class="space-y-2 text-sm mb-4">
                    <div class="flex justify-between p-3 bg-gray-50 rounded-xl">
                        <span class="text-gray-600">Vendido por la tienda</span>
                        <span class="font-semibold text-[#222036]">${{ number_format($comisiones['facturado'], 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between p-3 bg-gray-50 rounded-xl">
                        <span class="text-gray-600">Ticket promedio</span>
                        <span class="font-semibold text-[#222036]">${{ number_format($comisiones['promedio'], 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between p-3 bg-gray-50 rounded-xl">
                        <span class="text-gray-600">Comisión</span>
                        <span class="font-semibold text-[#222036]">{{ number_format($comisionPct, 2, ',', '.') }}% · ${{ number_format($saldo, 2, ',', '.') }}</span>
                    </div>
                    @if($ivaComision > 0)
                    <div class="flex justify-between p-3 bg-gray-50 rounded-xl">
                        <span class="text-gray-600">IVA 21%</span>
                        <span class="font-semibold text-[#222036]">${{ number_format($ivaComision, 2, ',', '.') }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between p-3 bg-gray-50 rounded-xl">
                        <span class="text-gray-600">Fecha límite</span>
                        <span class="font-bold {{ $vencido ? 'text-red-600' : 'text-[#222036]' }}">{{ $fmtFecha($comisiones['fecha_lim']) }}</span>
                    </div>
                </div>

                @if($cbu || $alias)
                <div class="border-t pt-4">
                    <p class="text-sm font-semibold text-gray-700 mb-3">Datos para transferir:</p>
                    <div class="space-y-2">
                        @foreach([['CBU', $cbu], ['ALIAS', $alias]] as [$etiqueta, $valor])
                            @if($valor)
                            <div class="flex items-center justify-between bg-gray-50 rounded-xl p-3">
                                <div class="min-w-0 flex-1">
                                    <span class="text-[10px] text-gray-500 uppercase">{{ $etiqueta }}</span>
                                    <p class="font-mono text-sm text-gray-800 truncate">{{ $valor }}</p>
                                </div>
                                <button type="button"
                                    onclick="navigator.clipboard.writeText('{{ $valor }}'); this.innerHTML='<i class=\'fas fa-check text-green-500\'></i>'; setTimeout(() => this.innerHTML='<i class=\'fas fa-copy text-[#FFAF22]\'></i>', 2000)"
                                    class="ml-2 p-2 hover:bg-gray-200 rounded-lg flex-shrink-0" title="Copiar">
                                    <i class="fas fa-copy text-[#FFAF22]"></i>
                                </button>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <div class="px-5 py-4 bg-gray-50 border-t">
                <button type="button" wire:click="$set('mostrarModalComisiones', false)"
                    class="w-full bg-[#222036] hover:bg-[#2d2a4a] text-white font-semibold py-2.5 px-4 rounded-xl">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
@endif
