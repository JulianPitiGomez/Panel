<div class="space-y-6 font-secondary">
    <p class="text-sm text-gray-500">Cómo te pagan los pedidos de la tienda web.</p>

    @if($mensaje)
        <div class="px-4 py-3 rounded-xl text-sm {{ $tipoMensaje === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
            {{ $mensaje }}
        </div>
    @endif

    {{-- Transferencia --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6">
        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" wire:model.live="config.transferencia_activa" class="mt-1 w-4 h-4 rounded text-[#FFAF22] focus:ring-[#FFAF22]">
            <div>
                <span class="font-primary font-semibold text-gray-800"><i class="fas fa-university text-[#FFAF22] mr-2"></i>Transferencia bancaria</span>
                <p class="text-xs text-gray-400">El comprador ve estos datos y un importe con centavos únicos para identificar su pago. Después sube el comprobante y vos lo confirmás desde Pedidos web.</p>
            </div>
        </label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4" x-show="$wire.config.transferencia_activa" x-transition>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Titular de la cuenta</label>
                <input type="text" wire:model="config.transferencia_titular" maxlength="100" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Banco</label>
                <input type="text" wire:model="config.transferencia_banco" maxlength="60" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CBU / CVU</label>
                <input type="text" wire:model="config.transferencia_cbu" maxlength="30" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Alias</label>
                <input type="text" wire:model="config.transferencia_alias" maxlength="40" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Indicaciones (opcional)</label>
                <input type="text" wire:model="config.transferencia_texto" maxlength="200" placeholder="Ej: Si preferís, mandanos el comprobante por WhatsApp" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
        </div>
    </div>

    {{-- Mercado Pago --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6">
        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" wire:model.live="config.mercadopago_activa" class="mt-1 w-4 h-4 rounded text-[#FFAF22] focus:ring-[#FFAF22]">
            <div>
                <span class="font-primary font-semibold text-gray-800"><i class="fas fa-credit-card text-[#FFAF22] mr-2"></i>Mercado Pago</span>
                <p class="text-xs text-gray-400">Con tu propia cuenta: la plata entra directo ahí. Necesitás el <strong>access token de producción</strong> de tu aplicación en Mercado Pago Developers.</p>
            </div>
        </label>
        <div class="mt-4 space-y-3">
            @if($mpEstado['cargado'])
                <div class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-3 text-sm">
                    <div>
                        <span class="font-medium text-gray-700"><i class="fas fa-key mr-1 text-gray-400"></i>Token cargado</span>
                        @if($mpEstado['verificada'])
                            <span class="ml-2 text-green-600"><i class="fas fa-check-circle mr-1"></i>Verificado{{ $mpEstado['dato'] ? ': ' . $mpEstado['dato'] : '' }}</span>
                        @elseif($mpEstado['error'])
                            <span class="ml-2 text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>{{ $mpEstado['error'] }}</span>
                        @else
                            <span class="ml-2 text-gray-400">Sin probar todavía</span>
                        @endif
                    </div>
                    <button type="button" wire:click="quitarMercadoPago" wire:confirm="¿Quitar la cuenta de Mercado Pago de la tienda?" class="text-xs text-red-500 hover:underline">Quitar</button>
                </div>
            @endif
            <div class="flex flex-col sm:flex-row gap-2">
                <input type="password" wire:model="mpToken" autocomplete="off" placeholder="{{ $mpEstado['cargado'] ? 'Pegá un token nuevo solo si querés reemplazarlo' : 'APP_USR-…' }}"
                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono">
                <button type="button" wire:click="probarMercadoPago" wire:loading.attr="disabled" class="px-4 py-2 rounded-lg text-sm font-semibold bg-[#FFAF22]/15 text-[#B8860B] hover:bg-[#FFAF22]/30">
                    <i class="fas fa-plug mr-1"></i> Probar conexión
                </button>
            </div>
        </div>
    </div>

    {{-- Cuenta corriente y a convenir --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <label class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100">
            <input type="checkbox" wire:model.live="config.cuenta_corriente_activa" class="mt-1 w-4 h-4 rounded text-[#FFAF22] focus:ring-[#FFAF22]">
            <div>
                <span class="text-sm font-medium text-gray-700">Cuenta corriente</span>
                <p class="text-xs text-gray-400">Solo para tus clientes con límite de crédito cargado en gestión. La tienda no deja superar ese límite; el cobro lo hacés después desde gestión.</p>
            </div>
        </label>
        <label class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100">
            <input type="checkbox" wire:model.live="config.a_convenir_activa" class="mt-1 w-4 h-4 rounded text-[#FFAF22] focus:ring-[#FFAF22]">
            <div>
                <span class="text-sm font-medium text-gray-700">Pago al recibir / a convenir</span>
                <p class="text-xs text-gray-400">El pedido entra sin cobro online y lo arreglás con el cliente.</p>
            </div>
        </label>
    </div>

    <div class="flex justify-end">
        <button type="button" wire:click="guardar" wire:loading.attr="disabled"
                class="px-6 py-2.5 bg-[#1E1B35] text-white rounded-lg hover:bg-[#2d2952] transition font-semibold text-sm disabled:opacity-50">
            <i class="fas fa-save mr-1"></i> Guardar medios de pago
        </button>
    </div>
</div>
