{{-- SUCCESS VIEW --}}
<div class="h-full flex flex-col overflow-y-auto" id="main"
     x-data="{
        init() {
            @if($kioscoImprimeTicket)
            setTimeout(() => {
                this.imprimirTicket();
            }, 1000);
            @endif
        },
        imprimirTicket() {
            window.print();
        }
     }">

    <!-- Contenido visible en pantalla -->
    <div class="no-print">
        <!-- Header compacto -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-3 text-center">
            <div class="flex items-center justify-center gap-3">
                <div class="bg-white rounded-full p-2">
                    <i class="fas fa-check text-green-600 text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-white">Pedido Confirmado</h1>
                    <p class="text-green-100 text-xs">Tu pedido ha sido recibido</p>
                </div>
            </div>
        </div>

        <div class="p-4 text-center max-w-lg mx-auto">
            <!-- Número de pedido -->
            <div class="bg-orange-50 rounded-xl p-4 mb-4 border border-orange-200">
                <p class="text-sm text-gray-600 mb-1">Tu número de pedido:</p>
                <div class="bg-white border-3 border-orange-500 rounded-lg p-3 shadow-md">
                    <span class="text-4xl font-bold text-orange-600 tracking-wider">{{ $orderNumber ?? 'PED-000' }}</span>
                </div>
                <p class="text-gray-500 text-xs mt-2">Conservá este número para retirar en mostrador</p>
            </div>

            <!-- Resumen compacto -->
            <div class="bg-gray-50 rounded-xl p-4 mb-4 border border-gray-200 text-left">
                <div class="grid grid-cols-2 gap-2 text-sm">
                    @if($kioscoPideNombre)
                    <div>
                        <span class="text-gray-500 text-xs">Cliente:</span>
                        <p class="font-medium text-gray-800">{{ $customerData['nombre'] ?? '-' }}</p>
                    </div>
                    @endif
                    @if($kioscoPideTelefono)
                    <div>
                        <span class="text-gray-500 text-xs">Teléfono:</span>
                        <p class="font-medium text-gray-800">{{ $customerData['telefono'] ?? '-' }}</p>
                    </div>
                    @endif
                    <div>
                        <span class="text-gray-500 text-xs">Forma de pago:</span>
                        <p class="font-medium text-gray-800">
                            @php
                                $fpNombre = 'No seleccionada';
                                if ($selectedPayment && is_array($formasPago)) {
                                    foreach($formasPago as $fp) {
                                        $cod = is_object($fp) ? $fp->CODIGO : ($fp['CODIGO'] ?? null);
                                        if ($cod == $selectedPayment) {
                                            $fpNombre = is_object($fp) ? $fp->NOMBRE : ($fp['NOMBRE'] ?? '');
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            {{ $fpNombre }}
                        </p>
                    </div>
                    <div>
                        <span class="text-gray-500 text-xs">Total:</span>
                        <p class="font-bold text-lg" style="color: var(--kiosco-precio);">${{ number_format($finalOrderTotal ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Productos -->
            @if(isset($finalOrderItems) && count($finalOrderItems) > 0)
            <div class="bg-gray-50 rounded-xl p-4 mb-4 border border-gray-200 text-left">
                <h3 class="text-sm font-bold text-gray-700 mb-2">
                    <i class="fas fa-utensils mr-1 text-orange-500"></i> Productos
                </h3>
                <div class="space-y-1.5 max-h-40 overflow-y-auto">
                    @foreach($finalOrderItems as $item)
                    <div class="flex justify-between items-center text-sm">
                        <div class="flex-1">
                            <span class="text-gray-800">{{ $item['quantity'] }}x {{ $item['nombre'] ?? 'Producto' }}</span>
                            @if(isset($item['opcionales']) && count($item['opcionales']) > 0)
                                <span class="text-xs text-gray-500 block pl-4">
                                    @foreach($item['opcionales'] as $op)
                                        + {{ $op['nombre'] }}@if(!$loop->last), @endif
                                    @endforeach
                                </span>
                            @endif
                        </div>
                        <span class="font-semibold text-gray-800 ml-2">${{ number_format($item['total'] ?? 0, 2) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Botones -->
            <div class="space-y-2">
                @if($kioscoImprimeTicket)
                <button @click="imprimirTicket()"
                        class="w-full bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded-lg transition-all flex items-center justify-center">
                    <i class="fas fa-print mr-2"></i>
                    Reimprimir Ticket
                </button>
                @endif

                <button wire:click="startNewOrder"
                        class="w-full hover:opacity-90 font-bold py-3 px-6 rounded-lg transition-all flex items-center justify-center"
                        style="background-color: var(--kiosco-boton); color: var(--kiosco-texto-boton);">
                    <i class="fas fa-plus mr-2"></i>
                    Nuevo Pedido
                </button>
            </div>
        </div>
    </div>

    <!-- Ticket para impresión (solo visible al imprimir) -->
    <div class="print-only" id="ticket-print">
        <div style="width: 280px; font-family: 'Courier New', monospace; font-size: 12px; padding: 8px;">
            <!-- Encabezado -->
            <div style="text-align: center; border-bottom: 1px dashed #000; padding-bottom: 8px; margin-bottom: 8px;">
                @if($comercioData)
                    <div style="font-size: 16px; font-weight: bold;">{{ $comercioData->nombre ?? 'Restaurante' }}</div>
                    @if($comercioData->direccion)
                        <div style="font-size: 10px;">{{ $comercioData->direccion }}</div>
                    @endif
                @endif
                <div style="font-size: 10px; margin-top: 4px;">{{ date('d/m/Y H:i') }}</div>
            </div>

            <!-- Número de pedido -->
            <div style="text-align: center; margin-bottom: 8px;">
                <div style="font-size: 11px;">PEDIDO N°</div>
                <div style="font-size: 28px; font-weight: bold; letter-spacing: 2px;">{{ $orderNumber ?? '000' }}</div>
            </div>

            <div style="border-bottom: 1px dashed #000; margin-bottom: 8px;"></div>

            <!-- Cliente -->
            @if($kioscoPideNombre || $kioscoPideTelefono)
            <div style="margin-bottom: 8px;">
                @if($kioscoPideNombre)
                <div>Cliente: {{ $customerData['nombre'] ?? '-' }}</div>
                @endif
                @if($kioscoPideTelefono)
                <div>Tel: {{ $customerData['telefono'] ?? '-' }}</div>
                @endif
            </div>

            <div style="border-bottom: 1px dashed #000; margin-bottom: 8px;"></div>
            @endif

            <!-- Productos -->
            @if(isset($finalOrderItems) && count($finalOrderItems) > 0)
                @foreach($finalOrderItems as $item)
                    <div style="margin-bottom: 4px;">
                        <div style="display: flex; justify-content: space-between;">
                            <span>{{ $item['quantity'] }}x {{ $item['nombre'] ?? '' }}</span>
                            <span style="font-weight: bold;">${{ number_format($item['total'] ?? 0, 2) }}</span>
                        </div>
                        @if(isset($item['opcionales']) && count($item['opcionales']) > 0)
                            @foreach($item['opcionales'] as $op)
                                <div style="padding-left: 12px; font-size: 10px;">+ {{ $op['nombre'] }}</div>
                            @endforeach
                        @endif
                    </div>
                @endforeach
            @endif

            <div style="border-top: 1px dashed #000; margin-top: 8px; padding-top: 8px;">
                <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: bold;">
                    <span>TOTAL</span>
                    <span>${{ number_format($finalOrderTotal ?? 0, 2) }}</span>
                </div>
            </div>

            <div style="border-bottom: 1px dashed #000; margin: 8px 0;"></div>

            <!-- Forma de pago -->
            <div style="text-align: center; font-size: 11px;">
                Pago: {{ $fpNombre ?? 'No seleccionada' }}
            </div>

            <!-- Pie -->
            <div style="text-align: center; margin-top: 12px; font-size: 10px;">
                <div>Gracias por tu compra</div>
                <div>Retirá tu pedido en mostrador</div>
            </div>
        </div>
    </div>
</div>
