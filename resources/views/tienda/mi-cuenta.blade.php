<div>
    <div class="flex items-center justify-between mb-5">
        <h2 class="text-xl font-bold" style="color: {{ $colores['texto'] }}">
            <i class="fas fa-wallet mr-2" style="color: {{ $colores['boton'] }}"></i> Mi cuenta
        </h2>
        <a href="{{ route('tienda.catalogo', $tiendaSlug) }}" class="text-sm hover:underline" style="color: {{ $colores['boton'] }}">
            <i class="fas fa-arrow-left mr-1"></i> Volver al catálogo
        </a>
    </div>

    @if(empty($resumen['existe']))
        <div class="bg-white rounded-xl shadow-sm p-10 text-center text-gray-500">Todavía no tenés cuenta corriente con el comercio.</div>
    @else
        {{-- Tarjetas de saldo --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <p class="text-[11px] uppercase text-gray-400">Saldo de tu cuenta</p>
                <p class="text-2xl font-bold mt-1" style="color: {{ $resumen['saldo'] > 0 ? $colores['precio'] : '#10b981' }}">${{ number_format($resumen['saldo'], 2, ',', '.') }}</p>
                <p class="text-[11px] text-gray-400">{{ $resumen['saldo'] > 0 ? 'lo que debés al comercio' : 'estás al día' }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <p class="text-[11px] uppercase text-gray-400">Vencido</p>
                <p class="text-2xl font-bold mt-1 {{ $resumen['vencido_total'] > 0 ? 'text-red-500' : 'text-gray-700' }}">${{ number_format($resumen['vencido_total'], 2, ',', '.') }}</p>
                <p class="text-[11px] text-gray-400">{{ $resumen['vencidas'] }} cuota{{ $resumen['vencidas'] != 1 ? 's' : '' }} vencida{{ $resumen['vencidas'] != 1 ? 's' : '' }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <p class="text-[11px] uppercase text-gray-400">Límite de crédito</p>
                <p class="text-2xl font-bold mt-1 text-gray-700">{{ $resumen['limite'] > 0 ? '$' . number_format($resumen['limite'], 2, ',', '.') : '—' }}</p>
                <p class="text-[11px] text-gray-400">{{ $resumen['disponible'] !== null ? 'disponible $' . number_format($resumen['disponible'], 2, ',', '.') : 'sin límite asignado' }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <p class="text-[11px] uppercase text-gray-400">Pagos en revisión</p>
                <p class="text-2xl font-bold mt-1 text-gray-700">${{ number_format($resumen['pendientes_web'], 2, ',', '.') }}</p>
                <p class="text-[11px] text-gray-400">enviados por la tienda, sin confirmar</p>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="flex gap-1 mb-4 overflow-x-auto">
            @foreach(['resumen' => 'Cuotas pendientes', 'comprobantes' => 'Comprobantes', 'movimientos' => 'Cuenta corriente', 'pagar' => 'Pagar la cuenta'] as $k => $t)
                <button wire:click="irA('{{ $k }}')" class="px-4 py-2 rounded-lg text-sm font-semibold whitespace-nowrap {{ $tab === $k ? 'text-white' : 'bg-white text-gray-600 shadow-sm' }}" style="{{ $tab === $k ? 'background-color: ' . $colores['boton'] : '' }}">{{ $t }}</button>
            @endforeach
        </div>

        {{-- Cuotas pendientes --}}
        @if($tab === 'resumen')
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                @if(count($resumen['cuotas']) === 0)
                    <p class="p-8 text-center text-sm text-gray-400"><i class="fas fa-check-circle text-green-400 text-2xl block mb-2"></i>No tenés cuotas pendientes.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-[11px] uppercase text-gray-500"><tr><th class="text-left px-4 py-2">Comprobante</th><th class="text-left px-4 py-2">Vence</th><th class="text-right px-4 py-2">Importe</th><th class="text-right px-4 py-2">Pendiente</th></tr></thead>
                            <tbody>
                                @foreach($resumen['cuotas'] as $q)
                                    <tr class="border-t border-gray-100">
                                        <td class="px-4 py-2 text-gray-700">{{ $q['comprobante'] }}{{ $q['cuota'] ? ' · cuota ' . $q['cuota'] : '' }}<span class="block text-xs text-gray-400">{{ \Carbon\Carbon::parse($q['fecha'])->format('d/m/Y') }}</span></td>
                                        <td class="px-4 py-2 {{ $q['vencida'] ? 'text-red-500 font-semibold' : 'text-gray-600' }}">{{ $q['vence'] ? \Carbon\Carbon::parse($q['vence'])->format('d/m/Y') : '-' }}{{ $q['vencida'] ? ' · vencida' : '' }}</td>
                                        <td class="px-4 py-2 text-right text-gray-600">${{ number_format($q['importe'], 2, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-right font-semibold text-gray-800">${{ number_format($q['saldo'], 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot><tr class="border-t border-gray-200 bg-gray-50"><td colspan="3" class="px-4 py-2 text-right text-sm text-gray-500">Total pendiente</td><td class="px-4 py-2 text-right font-bold" style="color: {{ $colores['precio'] }}">${{ number_format($resumen['cuotas_total'], 2, ',', '.') }}</td></tr></tfoot>
                        </table>
                    </div>
                @endif
            </div>
            @if($resumen['saldo'] > 0 && count($mediosDisponibles))
                <div class="mt-4 text-center">
                    <button wire:click="irA('pagar')" class="px-6 py-2.5 rounded-lg text-white font-semibold text-sm" style="background-color: {{ $colores['boton'] }}"><i class="fas fa-hand-holding-usd mr-1"></i> Pagar la cuenta</button>
                </div>
            @endif
        @endif

        {{-- Comprobantes --}}
        @if($tab === 'comprobantes')
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                @if(count($comprobantes) === 0)
                    <p class="p-8 text-center text-sm text-gray-400">Todavía no hay comprobantes emitidos a tu nombre.</p>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach($comprobantes as $c)
                            <div class="px-4 py-3 flex items-center justify-between gap-3 flex-wrap">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-800">{{ $c['etiqueta'] }}</p>
                                    <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($c['fecha'])->format('d/m/Y') }}{{ $c['cae'] ? ' · CAE ' . $c['cae'] : '' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold {{ $c['credito'] ? 'text-green-600' : '' }}" style="{{ $c['credito'] ? '' : 'color: ' . $colores['precio'] }}">{{ $c['credito'] ? '-' : '' }}${{ number_format($c['importe'], 2, ',', '.') }}</p>
                                    @if($c['pendiente'] > 0)<p class="text-[11px] text-amber-600">Pendiente ${{ number_format($c['pendiente'], 2, ',', '.') }}</p>@else<p class="text-[11px] text-gray-400">{{ $c['credito'] ? 'nota de crédito' : 'pagado' }}</p>@endif
                                </div>
                                <div class="flex gap-2">
                                    <button wire:click="verComprobante('{{ $c['ticomp'] }}', '{{ $c['letra'] }}', '{{ $c['numero'] }}')" class="px-3 py-1.5 rounded-lg text-xs border border-gray-200 text-gray-600 hover:bg-gray-50"><i class="fas fa-eye mr-1"></i>Ver</button>
                                    <a href="{{ $this->urlComprobante($c['ticomp'], $c['letra'], $c['numero'], true) }}" target="_blank" class="px-3 py-1.5 rounded-lg text-xs text-white" style="background-color: {{ $colores['boton'] }}"><i class="fas fa-file-pdf mr-1"></i>PDF</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <p class="text-[11px] text-gray-400 mt-2">El PDF tiene el mismo formato que el comprobante que emite el comercio.</p>
        @endif

        {{-- Cuenta corriente --}}
        @if($tab === 'movimientos')
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                @if(count($movimientos['movimientos']) === 0)
                    <p class="p-8 text-center text-sm text-gray-400">Sin movimientos en los últimos 12 meses.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-[11px] uppercase text-gray-500"><tr><th class="text-left px-4 py-2">Fecha</th><th class="text-left px-4 py-2">Concepto</th><th class="text-right px-4 py-2">Debe</th><th class="text-right px-4 py-2">Haber</th><th class="text-right px-4 py-2">Saldo</th></tr></thead>
                            <tbody>
                                @foreach($movimientos['movimientos'] as $m)
                                    <tr class="border-t border-gray-100">
                                        <td class="px-4 py-2 text-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($m['fecha'])->format('d/m/Y') }}</td>
                                        <td class="px-4 py-2 text-gray-700">
                                            @if($m['clave'])<button wire:click="verComprobante('{{ $m['clave'][0] }}', '{{ $m['clave'][1] }}', '{{ $m['clave'][2] }}')" class="hover:underline text-left">{{ $m['concepto'] }}</button>@else{{ $m['concepto'] }}@endif
                                        </td>
                                        <td class="px-4 py-2 text-right text-gray-700">{{ $m['debe'] > 0 ? '$' . number_format($m['debe'], 2, ',', '.') : '' }}</td>
                                        <td class="px-4 py-2 text-right text-green-600">{{ $m['haber'] > 0 ? '$' . number_format($m['haber'], 2, ',', '.') : '' }}</td>
                                        <td class="px-4 py-2 text-right font-semibold text-gray-800">${{ number_format($m['saldo'], 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <p class="text-[11px] text-gray-400 mt-2">Últimos 12 meses. El saldo de cada línea es el que quedaba después de ese movimiento.</p>
        @endif

        {{-- Pagar la cuenta --}}
        @if($tab === 'pagar')
            <div class="grid lg:grid-cols-2 gap-4">
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <h3 class="font-semibold mb-3" style="color: {{ $colores['texto'] }}">Pagar la cuenta</h3>
                    @if(count($mediosDisponibles) === 0)
                        <p class="text-sm text-gray-500">El comercio todavía no habilitó pagos por la tienda. Coordiná el pago directamente con ellos.</p>
                    @else
                        @if($mensajePago)
                            <p class="text-sm mb-3 px-3 py-2 rounded-lg {{ $tipoMensajePago === 'success' ? 'bg-green-50 text-green-700' : ($tipoMensajePago === 'error' ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-700') }}">{{ $mensajePago }}</p>
                        @endif
                        @if($pagoCreado)
                            @include('tienda.partials.datos-transferencia', ['transferencia' => $this->datosTransferencia(), 'importe' => $pagoCreado['importe_unico'], 'colores' => $colores])
                            <div class="mt-3">
                                <label class="block text-xs text-gray-500 mb-1">Subí el comprobante de la transferencia</label>
                                <x-subir-archivo model="comprobante" accept="image/*,.pdf" tema="tienda" titulo="Arrastrá el comprobante acá o hacé clic para elegirlo" ayuda="Foto o PDF, hasta 5 MB" />
                                @error('comprobante')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                                <button wire:click="subirComprobante" wire:loading.attr="disabled" class="mt-2 px-3 py-1.5 text-white rounded-lg text-sm" style="background-color: {{ $colores['boton'] }}">Enviar comprobante</button>
                            </div>
                        @else
                            <label class="block text-xs text-gray-500 mb-1">Importe a pagar</label>
                            <input type="number" step="0.01" min="1" wire:model="pagoImporte" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-lg font-semibold mb-3">
                            <div class="space-y-2 mb-4">
                                @foreach($mediosDisponibles as $m)
                                    <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer {{ $pagoMedio === $m ? 'border-gray-400 bg-gray-50' : 'border-gray-200' }}">
                                        <input type="radio" wire:model.live="pagoMedio" value="{{ $m }}">
                                        <span class="text-sm text-gray-700">{{ $this->etiquetaMedio($m) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <button wire:click="pagar" wire:loading.attr="disabled" class="w-full py-3 text-white font-semibold rounded-lg" style="background-color: {{ $colores['boton'] }}">
                                <span wire:loading.remove wire:target="pagar"><i class="fas fa-hand-holding-usd mr-1"></i> {{ $pagoMedio === 'mercadopago' ? 'Pagar con Mercado Pago' : 'Pagar por transferencia' }}</span>
                                <span wire:loading wire:target="pagar"><i class="fas fa-spinner fa-spin mr-1"></i> Un momento...</span>
                            </button>
                            <p class="text-[11px] text-gray-400 mt-2">El pago queda en revisión hasta que el comercio lo confirma y lo aplica a tu cuenta.</p>
                        @endif
                    @endif
                </div>
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <h3 class="font-semibold mb-3" style="color: {{ $colores['texto'] }}">Pagos enviados por la tienda</h3>
                    @if(count($pagosWeb) === 0)
                        <p class="text-sm text-gray-400">Todavía no hiciste pagos desde acá.</p>
                    @else
                        <div class="divide-y divide-gray-100">
                            @foreach($pagosWeb as $p)
                                <div class="py-2.5 text-sm">
                                    <div class="flex items-center justify-between gap-2">
                                        <div>
                                            <p class="font-semibold text-gray-800">${{ number_format($p['importe'], 2, ',', '.') }} <span class="text-xs font-normal text-gray-400">· {{ $this->etiquetaMedio($p['medio']) }}</span></p>
                                            <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($p['creado'])->format('d/m/Y H:i') }}{{ $p['importe_unico'] ? ' · a transferir $' . number_format($p['importe_unico'], 2, ',', '.') : '' }}</p>
                                        </div>
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ ['aprobado' => 'bg-green-100 text-green-700', 'rechazado' => 'bg-red-100 text-red-600'][$p['estado']] ?? 'bg-amber-100 text-amber-700' }}">{{ ['aprobado' => 'Aplicado', 'rechazado' => 'Rechazado'][$p['estado']] ?? 'En revisión' }}</span>
                                    </div>
                                    @if($p['estado'] === 'pendiente' && $p['medio'] === 'transferencia')
                                        @if($comprobantePagoId === (int) $p['id'])
                                            <div class="mt-2">
                                                <x-subir-archivo model="comprobante" accept="image/*,.pdf" tema="tienda" titulo="Arrastrá el comprobante acá o hacé clic para elegirlo" ayuda="Foto o PDF, hasta 5 MB" />
                                                @error('comprobante')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                                                <button wire:click="subirComprobante" wire:loading.attr="disabled" class="mt-2 px-3 py-1.5 text-white rounded-lg text-xs" style="background-color: {{ $colores['boton'] }}">Enviar comprobante</button>
                                            </div>
                                        @else
                                            <button wire:click="abrirComprobante({{ $p['id'] }})" class="mt-1 text-xs hover:underline" style="color: {{ $colores['boton'] }}"><i class="fas fa-paperclip mr-1"></i>{{ $p['comprobante'] ? 'Reemplazar comprobante' : 'Subir comprobante' }}</button>
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif

    {{-- Detalle de comprobante --}}
    @if($detalle)
        <div class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4" wire:click="cerrarComprobante">
            <div class="absolute inset-0 bg-black/50"></div>
            <div class="relative bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl w-full sm:max-w-lg p-5 max-h-[92vh] overflow-y-auto" wire:click.stop>
                <button wire:click="cerrarComprobante" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 p-1"><i class="fas fa-times"></i></button>
                <p class="text-[11px] uppercase text-gray-400">{{ \Carbon\Carbon::parse($detalle['encab']['fecha'])->format('d/m/Y') }}</p>
                <h3 class="text-lg font-bold mb-3" style="color: {{ $colores['texto'] }}">{{ $detalle['encab']['etiqueta'] }}</h3>
                <table class="w-full text-xs mb-3">
                    <thead class="text-gray-400 uppercase text-[10px]"><tr><th class="text-left pb-1">Detalle</th><th class="text-center pb-1">Cant.</th><th class="text-right pb-1">Importe</th></tr></thead>
                    <tbody>
                        @forelse($detalle['renglones'] as $r)
                            <tr class="border-t border-gray-100"><td class="py-1 text-gray-700">{{ $r['detalle'] }}</td><td class="py-1 text-center text-gray-500">{{ rtrim(rtrim(number_format($r['cantidad'], 2, ',', '.'), '0'), ',') }}</td><td class="py-1 text-right text-gray-700">${{ number_format($r['importe'], 2, ',', '.') }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="py-2 text-gray-400">Sin renglones cargados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="text-sm space-y-0.5 text-right">
                    @if($detalle['encab']['neto'] > 0)<p class="text-gray-500">Neto ${{ number_format($detalle['encab']['neto'], 2, ',', '.') }}</p>@endif
                    @if($detalle['encab']['iva'] > 0)<p class="text-gray-500">IVA ${{ number_format($detalle['encab']['iva'], 2, ',', '.') }}</p>@endif
                    <p class="font-bold text-base" style="color: {{ $colores['precio'] }}">Total ${{ number_format($detalle['encab']['importe'], 2, ',', '.') }}</p>
                </div>
                @if($detalle['encab']['cae'])<p class="text-[11px] text-gray-400 mt-2">CAE {{ $detalle['encab']['cae'] }}{{ $detalle['encab']['cae_vto'] ? ' · vto ' . \Carbon\Carbon::parse($detalle['encab']['cae_vto'])->format('d/m/Y') : '' }}</p>@endif
                <a href="{{ $this->urlComprobante($detalle['encab']['ticomp'], $detalle['encab']['letra'], $detalle['encab']['numero'], true) }}" target="_blank" class="mt-4 block text-center px-4 py-2.5 rounded-lg text-white font-semibold text-sm" style="background-color: {{ $colores['boton'] }}"><i class="fas fa-file-pdf mr-1"></i> Descargar PDF</a>
            </div>
        </div>
    @endif
</div>
