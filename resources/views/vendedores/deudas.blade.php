<div class="pb-28 px-3" style="padding-bottom: calc(7rem + env(safe-area-inset-bottom));">
    <!-- Bienvenida -->
    <div class="bg-[#222036] text-white rounded-lg p-4 mb-4 mt-3 shadow-md">
        <h2 class="text-lg font-semibold">CONSULTAS DE DEUDAS</h2>
        <p class="text-sm">Consultá el estado de deudas de tus clientes.</p>
    </div>

    <!-- Búsqueda -->
    <div class="mb-4">
        <input type="text"
                wire:model.live.debounce.300ms="busqueda"
                id="busqueda"
                placeholder="Buscar por Cliente..."
                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl shadow-md focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
    </div>

    <!-- Lista de clientes -->
    @forelse ($clientes as $cliente)
    <div class="bg-white shadow-md rounded-xl mb-3 pb-5 transition-all duration-200 hover:shadow-lg border-2 border-transparent hover:border-[#FFAF22]">
        <!-- Cabecera -->
        <div class="flex justify-between items-center px-5 py-4 bg-white rounded-t-xl cursor-pointer" wire:click="toggleExpand({{ $cliente->CODIGO }})">
            <div class="text-left flex-grow">
                <div class="text-md font-semibold text-gray-800">{{ $cliente->NOMBRE }}</div>
                @if($cliente->DIRECCION)
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-map-marker-alt mr-1 text-xs"></i>{{ $cliente->DIRECCION }}
                    </div>
                @endif
                @if($cliente->TELEFONO)
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-phone mr-1 text-xs"></i>{{ $cliente->TELEFONO }}
                    </div>
                @endif
            </div>
            <!-- Icono de despliegue -->
            <div class="ml-4 text-gray-600">
                <i class="fas {{ $deudaExpandido === $cliente->CODIGO ? 'fa-chevron-up' : 'fa-chevron-down' }}"></i>
            </div>
        </div>

        <!-- Cuerpo expandido -->
        @if ($deudaExpandido === $cliente->CODIGO)
        <div class="px-5 pb-6 pt-2 bg-gray-50">
            @php $total = 0; @endphp
            @if(count($detalle))
                <!-- Header de comprobantes -->
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-gray-700">
                        <i class="fas fa-file-invoice text-gray-400 mr-2"></i>Comprobantes
                    </h4>
                    <span class="text-xs text-gray-500">({{ count($detalle) }})</span>
                </div>

                <!-- Encabezado columnas -->
                <div class="flex items-center py-2 px-3 bg-[#222036] rounded-lg text-white text-xs font-medium mb-2">
                    <div class="flex-1">Comprobante</div>
                    <div class="text-right ml-2 min-w-[80px] sm:min-w-[100px]">Importe</div>
                    <div class="text-right ml-2 min-w-[80px] sm:min-w-[100px]">Saldo</div>
                </div>

                <div class="space-y-2 mb-3">
                    @foreach ($detalle as $item)
                    <div class="flex items-center text-sm py-3 px-3 bg-gray-100 rounded-lg">
                        <div class="flex-1 min-w-0">
                            <span class="text-gray-700 font-medium">{{ $item->TIPO.$item->LETRA.$item->NUMERO }}</span>
                            <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($item->FECHA)->format('d/m/Y') }}</div>
                        </div>
                        <div class="text-right ml-2 min-w-[80px] sm:min-w-[100px]">
                            <span class="text-sm font-semibold {{ $item->TIPO == 'NC' ? 'text-red-600' : 'text-blue-600' }} px-2 py-1 rounded {{ $item->TIPO == 'NC' ? 'bg-red-50' : 'bg-blue-50' }} inline-block">
                                ${{ number_format($item->IMPORTE * ($item->TIPO == 'NC' ? -1 : 1), 2) }}
                            </span>
                        </div>
                        <div class="text-right ml-2 min-w-[80px] sm:min-w-[100px]">
                            <span class="text-sm font-semibold {{ $item->TIPO == 'NC' ? 'text-red-600' : 'text-blue-600' }} px-2 py-1 rounded {{ $item->TIPO == 'NC' ? 'bg-red-50' : 'bg-blue-50' }} inline-block">
                                ${{ number_format($item->SALDO * ($item->TIPO == 'NC' ? -1 : 1), 2) }}
                            </span>
                        </div>
                    </div>
                    @php $total += $item->SALDO * ($item->TIPO == 'NC' ? -1 : 1); @endphp
                    @endforeach
                </div>

                <hr class="my-3 border-gray-300">

                <div class="flex justify-between font-bold text-base px-3">
                    <span class="text-gray-800">Total</span>
                    <span style="color: #FFAF22;">${{ number_format($total, 2) }}</span>
                </div>
            @else
                <div class="text-center text-gray-500 py-10">
                    <i class="fas fa-file-lines text-3xl mb-2 text-gray-400"></i><br>
                    No hay comprobantes de deuda
                </div>
            @endif
        </div>
        @endif
    </div>
    @empty
        <div class="text-center text-gray-500 py-10">
            No se encontraron clientes.
        </div>
    @endforelse

</div>
