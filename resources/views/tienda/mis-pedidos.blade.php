<div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold" style="color: {{ $colores['texto'] }}">
            <i class="fas fa-clipboard-list mr-2" style="color: {{ $colores['boton'] }}"></i> Mis Pedidos
        </h2>
        <button wire:click="volverAlCatalogo" class="text-sm hover:underline" style="color: {{ $colores['boton'] }}">
            <i class="fas fa-arrow-left mr-1"></i> Volver al catalogo
        </button>
    </div>

    @if(count($pedidos) === 0)
        <div class="bg-white rounded-xl shadow-sm p-12 text-center">
            <i class="fas fa-clipboard-list text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg">No tenes pedidos todavia</p>
            <button wire:click="volverAlCatalogo"
                    class="mt-4 px-6 py-2 text-white rounded-lg transition-opacity hover:opacity-90"
                    style="background-color: {{ $colores['boton'] }}">
                Ir al catalogo
            </button>
        </div>
    @else
        <div class="space-y-3">
            @foreach($pedidos as $pedido)
                @php $estado = $this->getEstado($pedido); @endphp
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <button wire:click="verDetalle({{ $pedido['id'] }})"
                            class="w-full text-left p-4 hover:bg-gray-50 transition">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                                     style="background-color: {{ $estado['color'] }}15; color: {{ $estado['color'] }}">
                                    <i class="fas {{ $estado['icono'] }}"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-sm" style="color: {{ $colores['texto'] }}">
                                        Pedido #{{ $pedido['id'] }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        {{ \Carbon\Carbon::parse($pedido['fecha'])->format('d/m/Y') }}
                                        @if($pedido['parafecha'])
                                            &mdash; Entrega: {{ \Carbon\Carbon::parse($pedido['parafecha'])->format('d/m/Y') }}
                                        @endif
                                    </p>
                                    @if($pedido['estado'] === 'F' && $pedido['facturanro'])
                                        <p class="text-xs text-gray-400">
                                            <i class="fas fa-file-invoice mr-1"></i> Factura #{{ $pedido['facturanro'] }}
                                        </p>
                                    @endif
                                    @if($pedido['web'] && $pedido['pago_medio'])
                                        @php $pg = $this->etiquetaPago($pedido); @endphp
                                        <p class="text-xs font-medium" style="color: {{ $pg['color'] }}">
                                            <i class="fas fa-wallet mr-1"></i> {{ $pg['texto'] }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full text-white"
                                      style="background-color: {{ $estado['color'] }}">
                                    {{ $estado['texto'] }}
                                </span>
                                <i class="fas fa-chevron-right text-xs text-gray-300"></i>
                            </div>
                        </div>
                    </button>

                    {{-- Detalle expandido --}}
                    @if($detallePedidoId === $pedido['id'])
                        <div class="border-t border-gray-100 px-4 py-3 bg-gray-50">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-xs text-gray-400 uppercase">
                                        <th class="text-left pb-2">Producto</th>
                                        <th class="text-center pb-2">Cant.</th>
                                        <th class="text-right pb-2">Precio</th>
                                        <th class="text-right pb-2">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $totalPedido = 0; @endphp
                                    @foreach($detallePedido as $det)
                                        @php $totalPedido += $det['subtotal']; @endphp
                                        <tr class="border-t border-gray-100">
                                            <td class="py-2 text-gray-700">
                                                {{ $det['nombre'] }}
                                                <span class="text-xs text-gray-400 block">{{ $det['codigo'] }}</span>
                                            </td>
                                            <td class="py-2 text-center text-gray-600">{{ $det['cantidad'] }}</td>
                                            <td class="py-2 text-right text-gray-600">${{ number_format($det['precio'], 2, ',', '.') }}</td>
                                            <td class="py-2 text-right font-semibold" style="color: {{ $colores['precio'] }}">${{ number_format($det['subtotal'], 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    @if($pedido['envio_importe'] > 0)
                                        <tr class="border-t border-gray-200">
                                            <td colspan="3" class="pt-2 text-right text-gray-500">Envío:</td>
                                            <td class="pt-2 text-right text-gray-600">${{ number_format($pedido['envio_importe'], 2, ',', '.') }}</td>
                                        </tr>
                                    @endif
                                    <tr class="border-t-2 border-gray-200">
                                        <td colspan="3" class="pt-3 text-right font-bold" style="color: {{ $colores['texto'] }}">Total:</td>
                                        <td class="pt-3 text-right font-bold" style="color: {{ $colores['precio'] }}">${{ number_format($totalPedido + $pedido['envio_importe'], 2, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>

                            @if($pedido['web'])
                                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                    <div class="bg-white rounded-lg p-3">
                                        <p class="text-gray-400 uppercase text-[10px] mb-1">Entrega</p>
                                        <p class="text-gray-700">
                                            {{ ['retiro' => 'Retiro en el local', 'flota' => 'Envío a domicilio', 'correo' => 'Correo', 'a_convenir' => 'A convenir'][$pedido['entrega_modo']] ?? $pedido['entrega_modo'] }}
                                            @if($pedido['entrega_direccion'])<br><span class="text-gray-500">{{ $pedido['entrega_direccion'] }}</span>@endif
                                        </p>
                                    </div>
                                    <div class="bg-white rounded-lg p-3">
                                        <p class="text-gray-400 uppercase text-[10px] mb-1">Pago</p>
                                        @php $pg = $this->etiquetaPago($pedido); @endphp
                                        <p class="font-medium" style="color: {{ $pg['color'] }}">{{ $pg['texto'] }}</p>

                                        @if($pedido['pago_medio'] === 'transferencia' && $pedido['pago_estado'] !== 'aprobado')
                                            <p class="text-gray-500 mt-1">Importe a transferir: <strong>${{ number_format($pedido['pago_importe_unico'], 2, ',', '.') }}</strong></p>
                                            @php $tr = $this->datosTransferencia(); @endphp
                                            @if($tr['cbu'] || $tr['alias'])
                                                <p class="text-gray-500 font-mono">{{ $tr['alias'] ?: $tr['cbu'] }}</p>
                                            @endif
                                            @if($pedido['pago_comprobante'])
                                                <a href="{{ $pedido['pago_comprobante'] }}" target="_blank" class="underline text-gray-600 block mt-1"><i class="fas fa-paperclip mr-1"></i>Ver comprobante enviado</a>
                                            @endif
                                            @if($comprobantePedidoId === $pedido['id'])
                                                <div class="mt-2 space-y-2">
                                                    <input type="file" wire:model="comprobante" accept="image/*,.pdf" class="block w-full text-xs">
                                                    @error('comprobante') <p class="text-red-500">{{ $message }}</p> @enderror
                                                    <div wire:loading wire:target="comprobante" class="text-gray-400">Cargando archivo…</div>
                                                    <button wire:click="subirComprobante" wire:loading.attr="disabled" class="px-3 py-1.5 text-white rounded-lg" style="background-color: {{ $colores['boton'] }}">Enviar comprobante</button>
                                                </div>
                                            @else
                                                <button wire:click="abrirComprobante({{ $pedido['id'] }})" class="mt-2 px-3 py-1.5 text-white rounded-lg" style="background-color: {{ $colores['boton'] }}">
                                                    <i class="fas fa-upload mr-1"></i> {{ $pedido['pago_comprobante'] ? 'Subir otro comprobante' : 'Subir comprobante' }}
                                                </button>
                                            @endif
                                        @elseif($pedido['pago_medio'] === 'mercadopago' && $pedido['pago_estado'] !== 'aprobado')
                                            <a href="{{ route('tienda.pago', ['tienda' => $tiendaSlug, 'pedido' => $pedido['id'], 'reintentar' => 1]) }}" class="inline-block mt-2 px-3 py-1.5 text-white rounded-lg" style="background-color: {{ $colores['boton'] }}">
                                                <i class="fas fa-credit-card mr-1"></i> Pagar con Mercado Pago
                                            </a>
                                        @endif
                                        @if($mensajePago && $comprobantePedidoId === null)
                                            <p class="text-green-600 mt-2">{{ $mensajePago }}</p>
                                        @endif
                                    </div>
                                </div>

                                @if(count($detalleEventos))
                                    <div class="mt-3 bg-white rounded-lg p-3 text-xs">
                                        <p class="text-gray-400 uppercase text-[10px] mb-1">Seguimiento</p>
                                        @foreach($detalleEventos as $ev)
                                            <p class="text-gray-600"><span class="text-gray-400">{{ \Carbon\Carbon::parse($ev['fecha'])->format('d/m H:i') }}</span> · {{ $ev['detalle'] }}</p>
                                        @endforeach
                                    </div>
                                @endif
                            @endif

                            <div class="mt-3 text-right">
                                <button wire:click="repetirPedido({{ $pedido['id'] }})" class="text-xs px-3 py-1.5 rounded-lg text-white mr-3" style="background-color: {{ $colores['boton'] }}">
                                    <i class="fas fa-redo mr-1"></i> Repetir este pedido
                                </button>
                                <button wire:click="cerrarDetalle" class="text-xs text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-chevron-up mr-1"></i> Cerrar
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
