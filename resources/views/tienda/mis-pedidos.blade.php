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
                                    <tr class="border-t-2 border-gray-200">
                                        <td colspan="3" class="pt-3 text-right font-bold" style="color: {{ $colores['texto'] }}">Total:</td>
                                        <td class="pt-3 text-right font-bold" style="color: {{ $colores['precio'] }}">${{ number_format($totalPedido, 2, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>

                            <div class="mt-3 text-right">
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
