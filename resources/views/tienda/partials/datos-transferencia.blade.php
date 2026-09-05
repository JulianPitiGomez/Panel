{{-- Datos para transferir. Requiere $transferencia (array), $importe (float|null), $colores --}}
<div class="text-left bg-gray-50 rounded-xl p-4 space-y-3">
    <p class="text-sm font-semibold" style="color: {{ $colores['texto'] }}"><i class="fas fa-university mr-1"></i> Transferí exactamente este importe:</p>
    <p class="text-3xl font-bold text-center" style="color: {{ $colores['precio'] }}">${{ number_format((float) $importe, 2, ',', '.') }}</p>
    <p class="text-[11px] text-gray-400 text-center -mt-2">Los centavos identifican tu pedido: no los redondees.</p>

    <div class="space-y-1.5 text-sm">
        @foreach([['Titular', $transferencia['titular']], ['Banco', $transferencia['banco']], ['CBU', $transferencia['cbu']], ['Alias', $transferencia['alias']]] as [$etiqueta, $valor])
            @if($valor)
                <div class="flex items-center justify-between bg-white rounded-lg px-3 py-2">
                    <div class="min-w-0">
                        <span class="text-[10px] uppercase text-gray-400">{{ $etiqueta }}</span>
                        <p class="font-mono text-sm text-gray-800 truncate">{{ $valor }}</p>
                    </div>
                    @if(in_array($etiqueta, ['CBU', 'Alias']))
                        <button type="button" onclick="navigator.clipboard.writeText('{{ $valor }}'); this.innerHTML='<i class=\'fas fa-check text-green-500\'></i>'" class="ml-2 p-2 rounded-lg hover:bg-gray-100 flex-shrink-0" title="Copiar">
                            <i class="fas fa-copy text-gray-400"></i>
                        </button>
                    @endif
                </div>
            @endif
        @endforeach
    </div>

    @if($transferencia['texto'])
        <p class="text-xs text-gray-500">{{ $transferencia['texto'] }}</p>
    @endif
    <p class="text-xs text-gray-500">Después subí el comprobante desde <strong>Mis pedidos</strong> para que el comercio lo confirme.</p>
</div>
