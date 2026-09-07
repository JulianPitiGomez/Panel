<div class="space-y-4 font-secondary">
    <p class="text-sm text-gray-500">Pedidos que entraron por la tienda web, con su entrega y su pago. En gestión los ves como cualquier otro pedido.</p>

    @if($mensaje)
        <div class="px-4 py-3 rounded-xl text-sm {{ $tipoMensaje === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
            {{ $mensaje }} <button wire:click="$set('mensaje', '')" class="float-right">&times;</button>
        </div>
    @endif

    @php $pendientesCuenta = collect($pagosCuenta)->where('estado', 'pendiente'); @endphp
    @if(count($pagosCuenta))
        <div class="bg-white rounded-2xl shadow-sm border {{ $pendientesCuenta->count() ? 'border-amber-200' : 'border-gray-100' }} overflow-hidden">
            <div class="px-4 py-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800"><i class="fas fa-hand-holding-usd text-[#FFAF22] mr-1"></i> Pagos a cuenta desde la tienda
                    @if($pendientesCuenta->count())<span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">{{ $pendientesCuenta->count() }} por confirmar</span>@endif
                </h3>
                <span class="text-[11px] text-gray-400">Al confirmar, cargalo en gestión como cobro: la tienda no toca la caja.</span>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($pagosCuenta as $pc)
                    <div class="px-4 py-2.5 flex items-center justify-between gap-3 flex-wrap text-sm">
                        <div class="min-w-0">
                            <p class="text-gray-800 font-semibold">{{ $pc['cliente'] ?: 'Cliente ' . $pc['codcli'] }} <span class="text-gray-400 font-normal text-xs">#{{ $pc['id'] }} · {{ \Carbon\Carbon::parse($pc['creado'])->format('d/m/Y H:i') }}</span></p>
                            <p class="text-xs text-gray-500">{{ $this->etiquetaMedio($pc['medio']) }}{{ $pc['importe_unico'] ? ' · a transferir $' . number_format($pc['importe_unico'], 2, ',', '.') : '' }}{{ $pc['referencia'] ? ' · ' . $pc['referencia'] : '' }} · saldo del cliente ${{ number_format($pc['saldo_cliente'] ?? 0, 2, ',', '.') }}</p>
                        </div>
                        <p class="font-bold text-gray-800">${{ number_format($pc['importe'], 2, ',', '.') }}</p>
                        <div class="flex items-center gap-2">
                            @if($pc['comprobante'])<a href="{{ $pc['comprobante'] }}" target="_blank" class="px-2.5 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-xs hover:bg-gray-200"><i class="fas fa-paperclip mr-1"></i>Comprobante</a>@endif
                            @if($pc['estado'] === 'pendiente')
                                <button wire:click="confirmarPagoCuenta({{ $pc['id'] }})" wire:confirm="¿Confirmar que recibiste ${{ number_format($pc['importe'], 2, ',', '.') }} de {{ $pc['cliente'] }}?" class="px-3 py-1.5 bg-green-500 text-white rounded-lg text-xs font-semibold hover:bg-green-600"><i class="fas fa-check mr-1"></i>Confirmar</button>
                                <button wire:click="rechazarPagoCuenta({{ $pc['id'] }})" wire:confirm="¿Rechazar este pago a cuenta?" class="text-xs text-red-500 hover:underline">Rechazar</button>
                            @else
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $pc['estado'] === 'aprobado' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">{{ $pc['estado'] === 'aprobado' ? 'Confirmado' : 'Rechazado' }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col sm:flex-row gap-3">
        <input type="text" wire:model.live.debounce.400ms="busqueda" placeholder="Buscar por nº de pedido, cliente o mail…"
               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
        <select wire:model.live="filtroEstado" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="todos">Todos los estados</option>
            <option value="abiertos">En curso (sin entregar)</option>
            @foreach(config('tienda.estados_web') as $ek => $ev)
                <option value="{{ $ek }}">{{ $ev }}</option>
            @endforeach
        </select>
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
                        <th class="text-left px-4 py-3">Estado</th>
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
                        @php $ew = \App\Services\TiendaSeguimientoService::normalizar($p['estado_web']); $colorEw = ['entregado' => 'bg-green-100 text-green-700', 'cancelado' => 'bg-red-100 text-red-600', 'recibido' => 'bg-blue-50 text-blue-700'][$ew] ?? 'bg-amber-50 text-amber-700'; @endphp
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $colorEw }}">{{ $this->etiquetaEstadoWeb($ew, $p['entrega_modo']) }}</span>
                            <span class="block text-xs text-gray-400 mt-0.5">Gestión: {{ $this->etiquetaEstadoGestion($p['estado']) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap" wire:click.stop>
                            @if(in_array($p['pago_medio'], ['transferencia', 'debin']) && $p['pago_estado'] === 'pendiente')
                                <button wire:click="confirmarPago({{ $p['idpedido'] }})" wire:confirm="¿Confirmar que se acreditó el pago del pedido #{{ $p['idpedido'] }}?"
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
                                        @php
                                            $ew = \App\Services\TiendaSeguimientoService::normalizar($p['estado_web']);
                                            $sig = $this->siguientesEstados($ew, $p['entrega_modo']);
                                            $ant = $this->estadoAnterior($ew, $p['entrega_modo']);
                                            $pideSeguimiento = in_array('despachado', $sig) || ($ew === 'despachado');
                                        @endphp
                                        <div class="mt-3 p-3 bg-white rounded-lg border border-gray-200" wire:click.stop>
                                            <p class="text-[11px] uppercase text-gray-400 mb-1.5">Estado del pedido web</p>
                                            <p class="text-sm text-gray-800 font-semibold mb-2">{{ $this->etiquetaEstadoWeb($ew, $p['entrega_modo']) }}</p>
                                            @if($pideSeguimiento)
                                                <input type="text" wire:model="seguimientoInput" maxlength="60" placeholder="{{ $p['envio_seguimiento'] ?: 'Nº de seguimiento del correo' }}"
                                                       class="px-2 py-1.5 border border-gray-300 rounded-lg text-xs w-56 mb-2">
                                            @endif
                                            <div class="flex flex-wrap items-center gap-2">
                                                @foreach($sig as $e)
                                                    <button wire:click="cambiarEstado({{ $p['idpedido'] }}, '{{ $e }}')" class="px-3 py-1.5 bg-[#1E1B35] text-white rounded-lg text-xs font-semibold hover:bg-[#2d2952]">
                                                        <i class="fas {{ ['preparacion' => 'fa-box-open', 'listo' => 'fa-check', 'despachado' => 'fa-truck', 'en_camino' => 'fa-motorcycle', 'entregado' => 'fa-flag-checkered'][$e] ?? 'fa-arrow-right' }} mr-1"></i>{{ $this->etiquetaEstadoWeb($e, $p['entrega_modo']) }}
                                                    </button>
                                                @endforeach
                                                @if($ant)
                                                    <button wire:click="cambiarEstado({{ $p['idpedido'] }}, '{{ $ant }}')" class="text-xs text-gray-400 hover:text-gray-600"><i class="fas fa-undo mr-1"></i>Volver a "{{ $this->etiquetaEstadoWeb($ant, $p['entrega_modo']) }}"</button>
                                                @endif
                                                @if(empty($sig) && !$ant)
                                                    <span class="text-xs text-gray-400">Sin cambios posibles.</span>
                                                @endif
                                            </div>
                                            @if($p['envio_seguimiento'])
                                                <p class="text-xs text-gray-500 mt-2">Seguimiento: <strong>{{ $p['envio_seguimiento'] }}</strong>{{ $p['envio_transportista'] ? ' · ' . ucwords(str_replace('_', ' ', $p['envio_transportista'])) : '' }}
                                                    @if($u = $this->urlSeguimiento($p['envio_transportista'], $p['envio_seguimiento']))<a href="{{ $u }}" target="_blank" class="ml-1 text-blue-600 hover:underline">Ver en el transportista</a>@endif
                                                </p>
                                            @endif
                                            <p class="text-[11px] text-gray-400 mt-2">Cada avance le llega al comprador por mail.</p>
                                        </div>
                                        @if(in_array($p['pago_medio'], ['transferencia', 'debin']) && $p['pago_estado'] === 'pendiente')
                                            <button wire:click="rechazarPago({{ $p['idpedido'] }})" wire:confirm="¿Marcar el pago del pedido #{{ $p['idpedido'] }} como rechazado?" class="mt-3 text-xs text-red-500 hover:underline">Marcar pago como rechazado</button>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-[11px] uppercase text-gray-400 mb-2">Seguimiento</p>
                                        <div class="space-y-1.5 text-xs">
                                            @foreach($eventos as $e)
                                                <p class="text-gray-600"><span class="text-gray-400">{{ \Carbon\Carbon::parse($e['fecha'])->format('d/m H:i') }}</span> · <span class="text-gray-400">[{{ $e['origen'] }}]</span> {{ $e['detalle'] ?: $e['estado'] }}@if(!empty($e['notificado'])) <i class="fas fa-envelope text-gray-300 ml-1" title="Avisado por mail"></i>@endif</p>
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
