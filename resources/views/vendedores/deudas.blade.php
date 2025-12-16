<div class="px-1 pb-8">
    <!-- Bienvenida -->
    <div class="bg-[#222036] text-white rounded-lg p-4 mb-4 mt-3 shadow-md">
        <h2 class="text-lg font-semibold">CONSULTAS DE DEUDAS</h2>
        <p class="text-sm">Consultá el estado de deudas de tus clientes.</p>
    </div>    
    <div class="bg-white">
                        
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 bg-white p-4 rounded-lg shadow-md">
                
                <!-- Búsqueda -->
                <div class="col-span-2">
                    <input type="text" 
                            wire:model.live.debounce.300ms="busqueda" 
                            id="busqueda"
                            placeholder="Buscar por Cliente..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
    </div>
    <!-- Lista de clientes -->    
    <!-- Tabla responsive con prioridad móvil -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <!-- Header con color personalizado -->
                <thead style="background-color: #222036;" class="text-white">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            <i class="fas fa-user mr-1"></i>
                            Cliente
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider hidden sm:table-cell">
                            <i class="fas fa-comment-alt mr-1"></i>
                            Direccion
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider hidden md:table-cell">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            Telefono
                        </th>                        
                        <th class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider">
                            <i class="fas fa-cog mr-1"></i>
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($clientes as $cliente)
                        <!-- Fila principal -->
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <!-- Cliente (siempre visible) -->
                            <td class="px-3 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-blue-600 text-xs"></i>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 truncate max-w-32 sm:max-w-none">
                                            {{ $cliente->NOMBRE }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Direccion -->
                            <td class="px-3 py-4 hidden sm:table-cell">
                                @if($cliente->DIRECCION)
                                    <div class="text-sm text-gray-800 truncate max-w-48">
                                        {{ $cliente->DIRECCION }}
                                    </div>
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>                            

                            <!-- Telefono -->
                            <td class="px-3 py-4 text-center">
                                @if($cliente->TELEFONO)
                                    <div class="text-sm text-gray-800 truncate max-w-48">
                                        {{ $cliente->TELEFONO }}
                                    </div>
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>

                            <!-- Acciones -->
                            <td class="px-3 py-4 text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <!-- Botón expandir/contraer -->
                                    <button wire:click="toggleExpand({{ $cliente->CODIGO }})" 
                                            class="text-gray-500 hover:text-gray-700 transition-colors">
                                        <i class="fas {{ $deudaExpandido === $cliente->CODIGO ? 'fa-chevron-up' : 'fa-chevron-down' }} text-sm"></i>
                                    </button>                                    
                                </div>
                            </td>
                        </tr>

                        <!-- Fila expandida (detalle de deuda) -->
                        @if ($deudaExpandido === $cliente->CODIGO)
                            <tr>
                                <td colspan="5" class="px-0 py-0">
                                    <div class="bg-gray-50 border-t border-gray-200">
                                        <div class="px-6 py-4">
                                            @php $total = 0; @endphp
                                            @if(count($detalle))
                                                <!-- Header de deudas -->
                                                <div class="mb-3">
                                                    <h4 class="text-sm font-semibold text-gray-700 flex items-center">
                                                        <i class="fas fa-file fa-2x text-gray-400 mr-2"></i>
                                                        Comprobantes de deuda de <span class="font-bold text-gray-800 ml-1">{{ $cliente->NOMBRE }}</span>
                                                        <span class="text-gray-500 ml-2">({{ count($detalle) }})</span>
                                                    </h4>
                                                </div>
                                                
                                                <!-- Lista de deudas responsive -->
                                                <div class="space-y-2 mb-4">
                                                        <div class="flex justify-between items-center m-0 py-2 px-2 bg-[#222036] rounded border text-white">
                                                            <div class="flex-1 min-w-0">
                                                                <div class="text-sm font-medium">
                                                                    Comprobante
                                                                </div>                                                                
                                                            </div>
                                                            
                                                                <!-- Importe -->
                                                            <div class="flex-shrink-0 text-right ml-2 min-w-[80px] sm:min-w-[100px]">
                                                                <span class="text-sm font-semibold ">
                                                                    Importe
                                                                </span>
                                                            </div>
                                                                
                                                            <!-- Saldo -->
                                                            <div class="flex-shrink-0 text-right ml-2 min-w-[80px] sm:min-w-[100px]">
                                                                <span class="text-sm font-semibold ">
                                                                    Saldo
                                                                </span>
                                                            </div>
                                                        </div>
                                                    @foreach ($detalle as $item)
                                                        <div class="flex m-0 py-2 px-2 bg-white rounded border border-gray-200">
                                                            <!-- Comprobante y fecha (alineado a izquierda) -->
                                                            <div class="flex-1 min-w-0">
                                                                <div class="text-sm font-medium text-gray-800 truncate">
                                                                    {{ $item->TIPO.$item->LETRA.$item->NUMERO }}
                                                                </div>
                                                                <div class="text-xs text-gray-500">
                                                                    {{ \Carbon\Carbon::parse($item->FECHA)->format('d/m/Y') }}                                                                    
                                                                </div>
                                                            </div>
                                                            
                                                            <!-- Importe (alineado a derecha) -->
                                                            <div class="flex-shrink-0 text-right ml-2 min-w-[80px] sm:min-w-[100px]">
                                                                <span class="text-sm font-semibold {{ $item->TIPO == 'NC' ? 'text-red-600' : 'text-blue-600' }} px-2 py-1 rounded {{ $item->TIPO == 'NC' ? 'bg-red-50' : 'bg-blue-50' }} inline-block">
                                                                    ${{ number_format($item->IMPORTE * ($item->TIPO == 'NC' ? -1 : 1), 2) }}
                                                                </span>
                                                            </div>
                                                                
                                                            <!-- Saldo (alineado a derecha) -->
                                                            <div class="flex-shrink-0 text-right ml-2 min-w-[80px] sm:min-w-[100px]">
                                                                <span class="text-sm font-semibold {{ $item->TIPO == 'NC' ? 'text-red-600' : 'text-blue-600' }} px-2 py-1 rounded {{ $item->TIPO == 'NC' ? 'bg-red-50' : 'bg-blue-50' }} inline-block">
                                                                    ${{ number_format($item->SALDO * ($item->TIPO == 'NC' ? -1 : 1), 2) }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        @php $total += $item->SALDO * ($item->TIPO == 'NC' ? -1 : 1); @endphp
                                                    @endforeach
                                                </div>

                                                <!-- Total -->
                                                <div class="border-t border-gray-300 pt-3">
                                                    <div class="flex justify-between items-center py-2 px-3 bg-white rounded border-2 border-gray-300">
                                                        <span class="text-lg font-bold text-gray-800">Total</span>
                                                        <span class="text-lg font-bold text-gray-900">${{ number_format($total, 2) }}</span>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="text-center py-6">
                                                    <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-2">
                                                        <i class="fas fa-file-lines text-gray-400"></i>
                                                    </div>
                                                    <p class="text-gray-500 text-sm">No hay facturas en el cliente</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="fas fa-inbox text-gray-400 text-xl"></i>
                                    </div>
                                    <p class="text-gray-500 text-lg font-medium">No hay facturas</p>
                                    <p class="text-gray-400 text-sm">No se encontraron facturas </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
