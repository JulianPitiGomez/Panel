<div class="space-y-4 font-secondary">
    <p class="text-sm text-gray-500">Pedidos que entraron por la tienda web, con su entrega y su pago. En gestión los ves como cualquier otro pedido.</p>

    @if($mensaje)
        <div class="px-4 py-3 rounded-xl text-sm {{ $tipoMensaje === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
            {{ $mensaje }} <button wire:click="$set('mensaje', '')" class="float-right">&times;</button>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col sm:flex-row gap-3">
        <input type="text" wire:model.live.debounce.400ms="busqueda" placeholder="Buscar por nº de pedido, cliente o mail…"
               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
        <select wire:model.live="filtroPago" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="todos">Todos los pagos</option>
            <option value="pendiente">Pago pendiente</option>
            <option value="aprobado">Pagados</option>
            <option value="rechazado">Rechazados</option>
            <option value="no_aplica">Sin cobro online</option>
        </select>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if(count($pedidos) === 0)
            <p class="p-8 text-center text-sm text-gray-400">No hay pedidos web con ese filtro.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-[11px] uppercase text-gray-500">
                    <tr>
                        <th class="text-left px-4 py-3">Pedido</th>
                        <th class="text-left px-4 py-3">Cliente</th>
                        <th class="text-left px-4 py-3">Entrega</th>
                        <th class="text-left px-4 py-3">Pago</th>
                        <th class="text-right px-4 py-3">Total</th>
                        <th class="text-left px-4 py-3">Gestión</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($pedidos as $p)
                    @php
                        $total = (float) $p['subtotal'] + (float) $p['envio_importe'];
                        $colorPago = ['aprobado' => 'text-green-600', 'pendiente' => 'text-amber-600', 'rechazado' => 'text-red-500'][$p['pago_estado']] ?? 'text-gray-500';
                    @endphp
                    <tr class="border-t border-gray-100 hover:bg-gray-50 cursor-pointer" wire:click="{{ $detalleId === (int) $p['idpedido'] ? 'cerrarDetalle' : 'verDetalle(' . $p['idpedido'] . ')' }}">
                        <td class="px-4 py-3">
                            <span class="font-semibold text-gray-800">#{{ $p['idpedido'] }}</span>
                            <span class="block text-xs text-gray-400">{{ \Carbon\Carbon::parse($p['creado'])->format('d/m/Y H:i') }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-gray-800">{{ $p['cliente'] }}</span>
                            @if($p['comprador_email'])<span class="block text-xs text-gray-400">{{ $p['comprador_email'] }}</span>@endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ ['retiro' => 'Retiro', 'flota' => 'Envío', 'correo' => 'Correo', 'a_convenir' => 'A convenir'][$p['entrega_modo']] ?? $p['entrega_modo'] }}
                            @if($p['entrega_direccion'])<span class="block text-xs text-gray-400 max-w-[220px] truncate">{{ $p['entrega_direccion'] }}{{ $p['entrega_localidad'] ? ', ' . $p['entrega_localidad'] : '' }}</span>@endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-gray-700">{{ $this->etiquetaMedio($p['pago_medio']) }}</span>
                            <span class="block text-xs font-medium {{ $colorPago }}">{{ $this->etiquetaPago($p['pago_estado']) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-800">${{ number_format($total, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $this->etiquetaEstadoGestion($p['estado']) }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap" wire:click.stop>
                            @if($p['pago_medio'] === 'transferencia' && $p['pago_estado'] === 'pendiente')
                                <button wire:click="confirmarPago({{ $p['idpedido'] }})" wire:confirm="¿Confirmar que llegó la transferencia del pedido #{{ $p['idpedido'] }}?"
                                        class="px-3 py-1.5 bg-green-500 text-white rounded-lg text-xs font-semibold hover:bg-green-600">
                                    <i class="fas fa-check mr-1"></i>Confirmar pago
                                </button>
                            @endif
                            @if($p['pago_comprobante'])
                                <a href="{{ $p['pago_comprobante'] }}" target="_blank" class="ml-1 px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-xs hover:bg-gray-200"><i class="fas fa-paperclip mr-1"></i>Comprobante</a>
                            @endif
                        </td>
                    </tr>
                    @if($detalleId === (int) $p['idpedido'])
                        <tr class="bg-gray-50">
                            <td colspan="7" class="px-4 py-4">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <p class="text-[11px] uppercase text-gray-400 mb-2">Artículos</p>
                                        <table class="w-full text-xs">
                                            @foreach($detalle as $d)
                                                <tr class="border-t border-gray-100">
                                                    <td class="py-1.5 text-gray-700">{{ $d['detart'] }} <span class="text-gray-400">({{ $d['codart'] }})</span></td>
                                                    <td class="py-1.5 text-center text-gray-500">{{ rtrim(rtrim(number_format($d['cantidad'], 2, ',', '.'), '0'), ',') }}</td>
                                                    <td class="py-1.5 text-right text-gray-700">${{ number_format($d['cantidad'] * ($d['punitario'] - ($d['descu'] ?? 0)), 2, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                            @if($p['envio_importe'] > 0)
                                                <tr class="border-t border-gray-100"><td colspan="2" class="py-1.5 text-gray-500">Envío</td><td class="py-1.5 text-right text-gray-700">${{ number_format($p['envio_importe'], 2, ',', '.') }}</td></tr>
                                            @endif
                                        </table>
                                        @if($p['observa'])<p class="text-xs text-gray-500 mt-2"><i class="fas fa-comment mr-1"></i>{{ $p['observa'] }}</p>@endif
                                        <div class="mt-3 text-xs text-gray-500 space-y-0.5">
                                            <p>Entrega para el {{ $p['parafecha'] ? \Carbon\Carbon::parse($p['parafecha'])->format('d/m/Y') : '-' }}</p>
                                            @if($p['comprador_telefono'])<p>Tel: {{ $p['comprador_telefono'] }}</p>@endif
                                            @if($p['pago_medio'] === 'transferencia')<p>Importe a transferir: <strong>${{ number_format($p['pago_importe_unico'], 2, ',', '.') }}</strong></p>@endif
                                            @if($p['pago_referencia'])<p>Referencia de pago: {{ $p['pago_referencia'] }}</p>@endif
                                            <p>IVA incluido: ${{ number_format($p['iva_incluido'], 2, ',', '.') }} · Comisión: ${{ number_format($p['comision_importe'], 2, ',', '.') }} ({{ $p['comision_estado'] }})</p>
                                        </div>
                                        @if($p['pago_medio'] === 'transferencia' && $p['pago_estado'] === 'pendiente')
                                            <button wire:click="rechazarPago({{ $p['idpedido'] }})" wire:confirm="¿Marcar el pago del pedido #{{ $p['idpedido'] }} como rechazado?" class="mt-3 text-xs text-red-500 hover:underline">Marcar pago como rechazado</button>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-[11px] uppercase text-gray-400 mb-2">Seguimiento</p>
                                        <div class="space-y-1.5 text-xs">
                                            @foreach($eventos as $e)
                                                <p class="text-gray-600"><span class="text-gray-400">{{ \Carbon\Carbon::parse($e['fecha'])->format('d/m H:i') }}</span> · <span class="text-gray-400">[{{ $e['origen'] }}]</span> {{ $e['detalle'] ?: $e['estado'] }}</p>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
