<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <button wire:click="volverInicio" class="flex items-center gap-2 text-sm text-gray-600 hover:text-[#FFAF22] transition-colors">
            <i class="fas fa-arrow-left"></i>
            <span>Volver a Inicio</span>
        </button>
        <div class="flex items-center gap-2 flex-wrap">
            <button wire:click="exportarExcel" wire:loading.attr="disabled" wire:target="exportarExcel"
                    class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-xs sm:text-sm font-semibold px-3 sm:px-4 py-2 rounded-lg shadow disabled:opacity-50">
                <i class="fas fa-file-excel" wire:loading.remove wire:target="exportarExcel"></i>
                <i class="fas fa-spinner fa-spin" wire:loading wire:target="exportarExcel"></i>
                <span wire:loading.remove wire:target="exportarExcel"><span class="hidden sm:inline">Exportar </span>Excel</span>
                <span wire:loading wire:target="exportarExcel">Generando...</span>
            </button>
            <button wire:click="exportarPdf" wire:loading.attr="disabled" wire:target="exportarPdf"
                    class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs sm:text-sm font-semibold px-3 sm:px-4 py-2 rounded-lg shadow disabled:opacity-50">
                <i class="fas fa-file-pdf" wire:loading.remove wire:target="exportarPdf"></i>
                <i class="fas fa-spinner fa-spin" wire:loading wire:target="exportarPdf"></i>
                <span wire:loading.remove wire:target="exportarPdf"><span class="hidden sm:inline">Exportar </span>PDF</span>
                <span wire:loading wire:target="exportarPdf">Generando...</span>
            </button>
        </div>
    </div>

    {{-- Filtros de fecha --}}
    <div class="bg-white rounded-xl shadow-md p-3 sm:p-4 border border-gray-100">
        <div class="grid grid-cols-2 sm:flex sm:flex-row gap-2 sm:gap-3 sm:items-end">
            <div class="sm:flex-1">
                <label class="block text-xs font-secondary text-gray-600 mb-1">Desde</label>
                <input type="date" wire:model.live.debounce.500ms="fechaDesde" class="w-full min-w-0 px-2 sm:px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FFAF22] focus:border-[#FFAF22]">
            </div>
            <div class="sm:flex-1">
                <label class="block text-xs font-secondary text-gray-600 mb-1">Hasta</label>
                <input type="date" wire:model.live.debounce.500ms="fechaHasta" class="w-full min-w-0 px-2 sm:px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FFAF22] focus:border-[#FFAF22]">
            </div>
            <button wire:click="cargarDatos" wire:loading.attr="disabled" class="col-span-2 sm:col-auto bg-[#FFAF22] hover:bg-[#FFAF22]/90 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 whitespace-nowrap">
                <i class="fas fa-sync-alt mr-2" wire:loading.class="animate-spin"></i>
                <span wire:loading.remove>Actualizar</span>
                <span wire:loading>Cargando...</span>
            </button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="flex border-b border-gray-200 overflow-x-auto">
            @foreach([
                'devengado' => ['Estado de Resultados', 'fa-chart-line'],
                'percibido' => ['Flujo de Fondos', 'fa-money-bill-wave'],
                'transferencias' => ['Mov. Bancarios', 'fa-university'],
                'conciliacion' => ['Conciliación', 'fa-balance-scale'],
                'graficos' => ['Gráficos', 'fa-chart-pie'],
            ] as $key => $info)
                <button wire:click="setTab('{{ $key }}')"
                    class="flex items-center gap-2 px-4 py-3 text-sm font-medium whitespace-nowrap transition-colors {{ $tabActiva === $key ? 'text-[#FFAF22] border-b-2 border-[#FFAF22] bg-[#FFAF22]/5' : 'text-gray-600 hover:text-[#FFAF22]' }}">
                    <i class="fas {{ $info[1] }}"></i>
                    <span>{{ $info[0] }}</span>
                </button>
            @endforeach
        </div>

        <div class="p-3 sm:p-6">
            {{-- ============ DEVENGADO ============ --}}
            @if($tabActiva === 'devengado')
                @php
                    $signoBruto = $this->resultadoBruto >= 0 ? 'text-green-700' : 'text-red-700';
                    $signoOper = $this->resultadoOperativo >= 0 ? 'text-green-700' : 'text-red-700';
                    $bgOper = $this->resultadoOperativo >= 0 ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200';
                @endphp

                {{-- KPIs resumen --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 {{ $separaGastos ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }} gap-3 mb-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 sm:p-4">
                        <p class="text-xs text-blue-700">Ventas totales</p>
                        <p class="text-base sm:text-xl font-bold text-blue-800 break-words">${{ number_format($ventas['importe'], 2) }}</p>
                    </div>
                    <div class="bg-orange-50 border border-orange-200 rounded-xl p-3 sm:p-4">
                        <p class="text-xs text-orange-700">{{ $separaGastos ? 'Compras (mercadería)' : 'Compras totales' }}</p>
                        <p class="text-base sm:text-xl font-bold text-orange-800 break-words">${{ number_format($compras['importe'], 2) }}</p>
                    </div>
                    @if($separaGastos)
                    <div class="bg-purple-50 border border-purple-200 rounded-xl p-3 sm:p-4">
                        <p class="text-xs text-purple-700">Gastos</p>
                        <p class="text-base sm:text-xl font-bold text-purple-800 break-words">${{ number_format($gastos['importe'], 2) }}</p>
                    </div>
                    @endif
                    <div class="rounded-xl p-3 sm:p-4 border {{ $bgOper }}">
                        <p class="text-xs {{ $signoOper }}">Resultado operativo</p>
                        <p class="text-base sm:text-xl font-bold {{ $signoOper }} break-words">${{ number_format($this->resultadoOperativo, 2) }}</p>
                    </div>
                    <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-3 sm:p-4">
                        <p class="text-xs text-indigo-700">Margen operativo</p>
                        <p class="text-base sm:text-xl font-bold text-indigo-800">{{ number_format($this->margenOperativo, 2) }}%</p>
                    </div>
                </div>

                {{-- Cuadro --}}
                <div class="-mx-3 sm:mx-0 overflow-x-auto">
                    <table class="w-full text-xs sm:text-sm min-w-[480px]">
                        <tbody class="divide-y divide-gray-100">
                            {{-- INGRESOS --}}
                            <tr class="bg-gray-50">
                                <td colspan="2" class="px-3 sm:px-4 py-2 font-bold text-gray-700 uppercase text-xs">Ingresos por ventas</td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-2 pl-5 sm:pl-8">Ventas fiscales (con CAE)</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($ventas['fiscal'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-2 pl-5 sm:pl-8">Ventas no fiscales</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($ventas['no_fiscal'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-2 pl-5 sm:pl-8 text-gray-600">— Neto sin impuestos <span class="text-xs text-gray-400">(según libro IVA ventas)</span></td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap text-gray-600">${{ number_format($ventas['neto'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-2 pl-5 sm:pl-8 text-gray-600">— IVA débito fiscal <span class="text-xs text-gray-400">(según libro IVA ventas)</span></td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap text-gray-600">${{ number_format($ventas['iva'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-2 pl-5 sm:pl-8 text-gray-600">— Percepciones IIBB <span class="text-xs text-gray-400">(solo fiscales)</span></td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap text-gray-600">${{ number_format($ventas['iibb'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-2 pl-5 sm:pl-8 text-gray-600">— Sobretasa / imp. internos <span class="text-xs text-gray-400">(solo fiscales)</span></td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap text-gray-600">${{ number_format($ventas['sobretasa'], 2) }}</td>
                            </tr>
                            <tr class="bg-blue-50 font-semibold">
                                <td class="px-3 sm:px-4 py-2">Total facturado</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($ventas['importe'], 2) }}</td>
                            </tr>

                            {{-- EGRESOS: COMPRAS --}}
                            <tr class="bg-gray-50">
                                <td colspan="2" class="px-3 sm:px-4 py-2 font-bold text-gray-700 uppercase text-xs">
                                    {{ $separaGastos ? 'Egresos por compras (mercadería)' : 'Egresos por compras' }}
                                    @if($separaGastos)
                                        <span class="normal-case font-normal text-gray-400">— cuentas contables tipo Compra</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-2 pl-5 sm:pl-8">Compras fiscales (computables IVA)</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($compras['fiscal'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-2 pl-5 sm:pl-8">Compras no fiscales</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($compras['no_fiscal'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-2 pl-5 sm:pl-8 text-gray-600">— No gravado</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap text-gray-600">${{ number_format($compras['nograbado'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-2 pl-5 sm:pl-8 text-gray-600">— Neto sin IVA</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap text-gray-600">${{ number_format($compras['neto'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-2 pl-5 sm:pl-8 text-gray-600">— IVA crédito fiscal <span class="text-xs text-gray-400">(solo computables)</span></td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap text-gray-600">${{ number_format($compras['iva'], 2) }}</td>
                            </tr>
                            <tr class="bg-orange-50 font-semibold">
                                <td class="px-3 sm:px-4 py-2">Total compras</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($compras['importe'], 2) }}</td>
                            </tr>

                            {{-- EGRESOS: GASTOS --}}
                            @if($separaGastos)
                            <tr class="bg-gray-50">
                                <td colspan="2" class="px-3 sm:px-4 py-2 font-bold text-gray-700 uppercase text-xs">
                                    Egresos por gastos
                                    <span class="normal-case font-normal text-gray-400">— cuentas contables tipo Gasto</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-2 pl-5 sm:pl-8">Gastos fiscales (computables IVA)</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($gastos['fiscal'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-2 pl-5 sm:pl-8">Gastos no fiscales</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($gastos['no_fiscal'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-2 pl-5 sm:pl-8 text-gray-600">— No gravado</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap text-gray-600">${{ number_format($gastos['nograbado'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-2 pl-5 sm:pl-8 text-gray-600">— Neto sin IVA</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap text-gray-600">${{ number_format($gastos['neto'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-2 pl-5 sm:pl-8 text-gray-600">— IVA crédito fiscal <span class="text-xs text-gray-400">(solo computables)</span></td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap text-gray-600">${{ number_format($gastos['iva'], 2) }}</td>
                            </tr>
                            <tr class="bg-purple-50 font-semibold">
                                <td class="px-3 sm:px-4 py-2">Total gastos</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($gastos['importe'], 2) }}</td>
                            </tr>
                            <tr class="bg-gray-50 font-semibold">
                                <td class="px-3 sm:px-4 py-2">Total facturas de proveedores (compras + gastos)</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($this->egresosFacturados, 2) }}</td>
                            </tr>
                            @endif

                            {{-- SUELDOS --}}
                            <tr class="bg-gray-50">
                                <td colspan="2" class="px-3 sm:px-4 py-2 font-bold text-gray-700 uppercase text-xs">Personal</td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-2 pl-5 sm:pl-8">Sueldos liquidados (devengado)</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($sueldosLiq, 2) }}</td>
                            </tr>

                            {{-- RESULTADOS --}}
                            <tr class="bg-gray-100 font-bold border-t-2 border-gray-300">
                                <td class="px-3 sm:px-4 py-3">Resultado bruto (Ventas − Compras)</td>
                                <td class="px-3 sm:px-4 py-3 text-right font-mono whitespace-nowrap {{ $signoBruto }}">${{ number_format($this->resultadoBruto, 2) }}</td>
                            </tr>
                            @if($separaGastos)
                            <tr class="bg-gray-100">
                                <td class="px-3 sm:px-4 py-2 text-xs sm:text-sm text-gray-600">Margen bruto sobre ventas</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap text-gray-600">{{ number_format($this->margenBruto, 2) }}%</td>
                            </tr>
                            @endif
                            <tr class="bg-gray-100 font-bold">
                                <td class="px-3 sm:px-4 py-3">Resultado operativo ({{ $separaGastos ? '− gastos y sueldos liq.' : '− sueldos liq.' }})</td>
                                <td class="px-3 sm:px-4 py-3 text-right font-mono whitespace-nowrap {{ $signoOper }}">${{ number_format($this->resultadoOperativo, 2) }}</td>
                            </tr>
                            <tr class="bg-gray-100">
                                <td class="px-3 sm:px-4 py-2 text-xs sm:text-sm text-gray-600">Saldo IVA del período (débito − crédito)</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap text-gray-600">${{ number_format($this->saldoIva, 2) }}</td>
                            </tr>
                            <tr class="bg-gray-100">
                                <td class="px-3 sm:px-4 py-2 text-xs sm:text-sm text-gray-600">Margen sobre ventas</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap text-gray-600">{{ number_format($this->margenOperativo, 2) }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Compras / gastos por cuenta contable --}}
                @php
                    $bloquesCuenta = [
                        ['titulo' => 'Compras agrupadas por cuenta contable', 'filas' => $comprasPorCuenta, 'icono' => 'fa-list-ul', 'color' => 'text-[#FFAF22]'],
                    ];
                    if ($separaGastos) {
                        $bloquesCuenta[] = ['titulo' => 'Gastos agrupados por cuenta contable', 'filas' => $gastosPorCuenta, 'icono' => 'fa-file-invoice-dollar', 'color' => 'text-purple-500'];
                    }
                @endphp
                @foreach($bloquesCuenta as $bloque)
                @if(count($bloque['filas']) > 0)
                <div class="mt-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                        <i class="fas {{ $bloque['icono'] }} {{ $bloque['color'] }}"></i>
                        {{ $bloque['titulo'] }}
                    </h3>
                    <div class="-mx-3 sm:mx-0 overflow-x-auto">
                        <table class="w-full text-xs sm:text-sm min-w-[520px]">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 sm:px-4 py-2 text-left text-xs font-semibold text-gray-600">Código</th>
                                    <th class="px-3 sm:px-4 py-2 text-left text-xs font-semibold text-gray-600">Cuenta</th>
                                    <th class="px-3 sm:px-4 py-2 text-right text-xs font-semibold text-gray-600">Neto</th>
                                    <th class="px-3 sm:px-4 py-2 text-right text-xs font-semibold text-gray-600">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($bloque['filas'] as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 sm:px-4 py-2 text-gray-500 font-mono text-xs">{{ $row['codigo'] ?? '—' }}</td>
                                    <td class="px-3 sm:px-4 py-2">{{ $row['nombre'] }}</td>
                                    <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($row['neto'], 2) }}</td>
                                    <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($row['importe'], 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
                @endforeach

                @if($separaGastos)
                <p class="mt-4 text-xs text-gray-500">
                    La separación entre compras y gastos surge del plan de cuentas: cada cuenta contable está marcada como
                    <strong>Compra</strong> o <strong>Gasto</strong>, y cada factura de proveedor se imputa según la cuenta que tenga asignada.
                    Los comprobantes sin cuenta contable se cuentan como compra.
                </p>
                @endif
            @endif

            {{-- ============ PERCIBIDO ============ --}}
            @if($tabActiva === 'percibido')
                @php
                    $signoFlujo = $this->flujoNeto >= 0 ? 'text-green-700' : 'text-red-700';
                    $bgFlujo = $this->flujoNeto >= 0 ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200';
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
                    <div class="bg-green-50 border border-green-200 rounded-xl p-3 sm:p-4">
                        <p class="text-xs text-green-700">Total ingresos</p>
                        <p class="text-base sm:text-xl font-bold text-green-800 break-words">${{ number_format($cobros, 2) }}</p>
                    </div>
                    <div class="bg-red-50 border border-red-200 rounded-xl p-3 sm:p-4">
                        <p class="text-xs text-red-700">Total egresos</p>
                        <p class="text-base sm:text-xl font-bold text-red-800 break-words">${{ number_format($pagosProv + $sueldosPag, 2) }}</p>
                    </div>
                    <div class="rounded-xl p-3 sm:p-4 border {{ $bgFlujo }}">
                        <p class="text-xs {{ $signoFlujo }}">Flujo neto del período</p>
                        <p class="text-base sm:text-xl font-bold {{ $signoFlujo }} break-words">${{ number_format($this->flujoNeto, 2) }}</p>
                    </div>
                </div>

                <div class="-mx-3 sm:mx-0 overflow-x-auto">
                    <table class="w-full text-xs sm:text-sm min-w-[480px]">
                        <tbody class="divide-y divide-gray-100">
                            <tr class="bg-gray-50">
                                <td colspan="2" class="px-3 sm:px-4 py-2 font-bold text-gray-700 uppercase text-xs">Ingresos cobrados</td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-2 pl-5 sm:pl-8">Cobros a clientes</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($cobros, 2) }}</td>
                            </tr>
                            <tr class="bg-green-50 font-semibold">
                                <td class="px-3 sm:px-4 py-2">Total ingresos</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($cobros, 2) }}</td>
                            </tr>

                            <tr class="bg-gray-50">
                                <td colspan="2" class="px-3 sm:px-4 py-2 font-bold text-gray-700 uppercase text-xs">Egresos pagados</td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-2 pl-5 sm:pl-8">Pagos a proveedores (órdenes de pago)</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($pagosProv, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-2 pl-5 sm:pl-8">Sueldos pagados</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($sueldosPag, 2) }}</td>
                            </tr>
                            <tr class="bg-red-50 font-semibold">
                                <td class="px-3 sm:px-4 py-2">Total egresos</td>
                                <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($pagosProv + $sueldosPag, 2) }}</td>
                            </tr>

                            <tr class="bg-gray-100 font-bold border-t-2 border-gray-300">
                                <td class="px-3 sm:px-4 py-3">Flujo neto del período</td>
                                <td class="px-3 sm:px-4 py-3 text-right font-mono whitespace-nowrap {{ $signoFlujo }}">${{ number_format($this->flujoNeto, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Formas de pago --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-6">
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="bg-green-50 px-4 py-2 font-semibold text-sm border-b border-gray-200 text-green-800">
                            <i class="fas fa-arrow-down mr-2"></i>Cobros por forma de pago
                        </div>
                        @if(count($cobrosPorForma) > 0)
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 sm:px-4 py-2 text-left text-xs font-semibold text-gray-600">Forma de pago</th>
                                        <th class="px-3 sm:px-4 py-2 text-center text-xs font-semibold text-gray-600">Cant.</th>
                                        <th class="px-3 sm:px-4 py-2 text-right text-xs font-semibold text-gray-600">Importe</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($cobrosPorForma as $f)
                                    <tr>
                                        <td class="px-3 sm:px-4 py-2">{{ $f['nombre'] }}</td>
                                        <td class="px-3 sm:px-4 py-2 text-center text-gray-600">{{ $f['cantidad'] }}</td>
                                        <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($f['total'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="px-4 py-3 text-sm text-gray-400 italic">Sin cobros en el período</p>
                        @endif
                    </div>

                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="bg-red-50 px-4 py-2 font-semibold text-sm border-b border-gray-200 text-red-800">
                            <i class="fas fa-arrow-up mr-2"></i>Pagos por forma de pago
                        </div>
                        @if(count($pagosPorForma) > 0)
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 sm:px-4 py-2 text-left text-xs font-semibold text-gray-600">Forma de pago</th>
                                        <th class="px-3 sm:px-4 py-2 text-center text-xs font-semibold text-gray-600">Cant.</th>
                                        <th class="px-3 sm:px-4 py-2 text-right text-xs font-semibold text-gray-600">Importe</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($pagosPorForma as $f)
                                    <tr>
                                        <td class="px-3 sm:px-4 py-2">{{ $f['nombre'] }}</td>
                                        <td class="px-3 sm:px-4 py-2 text-center text-gray-600">{{ $f['cantidad'] }}</td>
                                        <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($f['total'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="px-4 py-3 text-sm text-gray-400 italic">Sin pagos en el período</p>
                        @endif
                    </div>
                </div>

                <p class="mt-4 text-xs text-gray-500 italic">
                    Las ventas en cuenta corriente solo aparecen acá cuando el cliente las paga. Las compras solo cuando se generó orden de pago.
                </p>
            @endif

            {{-- ============ TRANSFERENCIAS / MOV BANCARIOS ============ --}}
            @if($tabActiva === 'transferencias')
                <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    Estos movimientos son transferencias internas entre caja y banco. No suman ni restan al flujo del negocio.
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 font-semibold text-sm border-b">
                            <i class="fas fa-university text-[#FFAF22] mr-2"></i>Movimientos bancarios (depósito)
                        </div>
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-gray-100">
                                <tr>
                                    <td class="px-3 sm:px-4 py-2 text-green-700">Ingresos al banco (TIPO=D)</td>
                                    <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap text-green-700">${{ number_format($depositosIngresos, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="px-3 sm:px-4 py-2 text-red-700">Retiros del banco (TIPO=R)</td>
                                    <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap text-red-700">${{ number_format($depositosRetiros, 2) }}</td>
                                </tr>
                                <tr class="bg-gray-50 font-semibold">
                                    <td class="px-3 sm:px-4 py-2">Saldo</td>
                                    <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($depositosIngresos - $depositosRetiros, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 font-semibold text-sm border-b">
                            <i class="fas fa-cash-register text-[#FFAF22] mr-2"></i>Movimientos de caja (retiros)
                        </div>
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-gray-100">
                                <tr>
                                    <td class="px-3 sm:px-4 py-2 text-green-700">Ingresos a caja (TIPO=I)</td>
                                    <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap text-green-700">${{ number_format($retirosIngresos, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="px-3 sm:px-4 py-2 text-red-700">Salidas de caja (TIPO=R)</td>
                                    <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap text-red-700">${{ number_format($retirosSalidas, 2) }}</td>
                                </tr>
                                <tr class="bg-gray-50 font-semibold">
                                    <td class="px-3 sm:px-4 py-2">Saldo</td>
                                    <td class="px-3 sm:px-4 py-2 text-right font-mono whitespace-nowrap">${{ number_format($retirosIngresos - $retirosSalidas, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- ============ CONCILIACIÓN ============ --}}
            @if($tabActiva === 'conciliacion')
                @php
                    $diffVentas = $ventas['importe'] - $cobros;
                    $diffCompras = $this->egresosFacturados - $pagosProv;
                    $diffSueldos = $sueldosLiq - $sueldosPag;
                @endphp

                <p class="text-sm text-gray-600 mb-4">
                    Diferencias entre lo facturado/liquidado (devengado) y lo cobrado/pagado (percibido) en el período.
                    Un valor positivo significa que se devengó más de lo que se movió en plata.
                </p>

                <div class="-mx-3 sm:mx-0 overflow-x-auto">
                    <table class="w-full text-xs sm:text-sm min-w-[480px]">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 sm:px-4 py-2 text-left text-xs font-semibold text-gray-600">Concepto</th>
                                <th class="px-3 sm:px-4 py-2 text-right text-xs font-semibold text-gray-600">Devengado</th>
                                <th class="px-3 sm:px-4 py-2 text-right text-xs font-semibold text-gray-600">Percibido</th>
                                <th class="px-3 sm:px-4 py-2 text-right text-xs font-semibold text-gray-600">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr>
                                <td class="px-3 sm:px-4 py-3 font-medium">Ventas vs Cobros</td>
                                <td class="px-3 sm:px-4 py-3 text-right font-mono whitespace-nowrap">${{ number_format($ventas['importe'], 2) }}</td>
                                <td class="px-3 sm:px-4 py-3 text-right font-mono whitespace-nowrap">${{ number_format($cobros, 2) }}</td>
                                <td class="px-3 sm:px-4 py-3 text-right font-mono whitespace-nowrap font-bold {{ $diffVentas >= 0 ? 'text-blue-700' : 'text-amber-700' }}">${{ number_format($diffVentas, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-3 font-medium">{{ $separaGastos ? 'Compras + gastos vs Pagos' : 'Compras vs Pagos' }}</td>
                                <td class="px-3 sm:px-4 py-3 text-right font-mono whitespace-nowrap">${{ number_format($this->egresosFacturados, 2) }}</td>
                                <td class="px-3 sm:px-4 py-3 text-right font-mono whitespace-nowrap">${{ number_format($pagosProv, 2) }}</td>
                                <td class="px-3 sm:px-4 py-3 text-right font-mono whitespace-nowrap font-bold {{ $diffCompras >= 0 ? 'text-blue-700' : 'text-amber-700' }}">${{ number_format($diffCompras, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 sm:px-4 py-3 font-medium">Sueldos liquidados vs pagados</td>
                                <td class="px-3 sm:px-4 py-3 text-right font-mono whitespace-nowrap">${{ number_format($sueldosLiq, 2) }}</td>
                                <td class="px-3 sm:px-4 py-3 text-right font-mono whitespace-nowrap">${{ number_format($sueldosPag, 2) }}</td>
                                <td class="px-3 sm:px-4 py-3 text-right font-mono whitespace-nowrap font-bold {{ $diffSueldos >= 0 ? 'text-blue-700' : 'text-amber-700' }}">${{ number_format($diffSueldos, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800">
                        <strong>Diferencia positiva</strong>: facturé/liquidé más de lo que cobré/pagué (queda pendiente de cobrar/pagar).
                    </div>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800">
                        <strong>Diferencia negativa</strong>: cobré/pagué más de lo que facturé/liquidé (cobranza/pago de períodos anteriores).
                    </div>
                </div>
            @endif

            {{-- ============ GRÁFICOS ============ --}}
            @if($tabActiva === 'graficos')
                <div x-data="estadoResultadoCharts()" x-init="render()">
                    {{-- Data source oculto: Livewire actualiza los atributos y el MutationObserver dispara el redraw --}}
                    <div id="estadoResultadoChartsData"
                         data-resumen="{{ json_encode([
                            'resultadoBruto' => $this->resultadoBruto,
                            'resultadoOperativo' => $this->resultadoOperativo,
                            'saldoIva' => $this->saldoIva,
                            'flujoNeto' => $this->flujoNeto,
                         ]) }}"
                         data-conciliacion="{{ json_encode([
                            ['concepto' => 'Ventas / Cobros', 'devengado' => $ventas['importe'], 'percibido' => $cobros],
                            ['concepto' => $separaGastos ? 'Compras+gastos / Pagos' : 'Compras / Pagos', 'devengado' => $this->egresosFacturados, 'percibido' => $pagosProv],
                            ['concepto' => 'Sueldos liq / pag', 'devengado' => $sueldosLiq, 'percibido' => $sueldosPag],
                         ]) }}"
                         data-cobros="{{ json_encode($cobrosPorForma) }}"
                         data-pagos="{{ json_encode($pagosPorForma) }}"
                         data-cuentas="{{ json_encode(array_slice($comprasPorCuenta, 0, 10)) }}"
                         data-gastoscuentas="{{ json_encode($separaGastos ? array_slice($gastosPorCuenta, 0, 10) : []) }}"
                         data-ventascomp="{{ json_encode([
                            'fiscal' => $ventas['fiscal'], 'no_fiscal' => $ventas['no_fiscal'],
                         ]) }}"
                         data-comprascomp="{{ json_encode([
                            'fiscal' => $compras['fiscal'], 'no_fiscal' => $compras['no_fiscal'],
                         ]) }}"
                         data-egresoscomp="{{ json_encode($separaGastos ? [
                            'compras' => $compras['importe'], 'gastos' => $gastos['importe'],
                         ] : []) }}"
                         style="display:none;"></div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        {{-- Resumen del período --}}
                        <div class="border border-gray-200 rounded-xl p-3 sm:p-4">
                            <h3 class="text-xs sm:text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <i class="fas fa-chart-bar text-[#FFAF22]"></i>
                                Resumen del período
                            </h3>
                            <div class="relative" style="height: 300px;" wire:ignore>
                                <canvas x-ref="chartResumen"></canvas>
                            </div>
                        </div>

                        {{-- Devengado vs Percibido --}}
                        <div class="border border-gray-200 rounded-xl p-3 sm:p-4">
                            <h3 class="text-xs sm:text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <i class="fas fa-balance-scale text-[#FFAF22]"></i>
                                Devengado vs Percibido
                            </h3>
                            <div class="relative" style="height: 300px;" wire:ignore>
                                <canvas x-ref="chartConciliacion"></canvas>
                            </div>
                        </div>

                        {{-- Composición de ventas --}}
                        <div class="border border-gray-200 rounded-xl p-3 sm:p-4">
                            <h3 class="text-xs sm:text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <i class="fas fa-receipt text-blue-500"></i>
                                Composición de ventas (fiscal vs no fiscal)
                            </h3>
                            <div class="relative" style="height: 280px;" wire:ignore>
                                <canvas x-ref="chartVentasComp"></canvas>
                            </div>
                        </div>

                        {{-- Composición de compras --}}
                        <div class="border border-gray-200 rounded-xl p-3 sm:p-4">
                            <h3 class="text-xs sm:text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <i class="fas fa-shopping-cart text-orange-500"></i>
                                Composición de compras (computables vs no)
                            </h3>
                            <div class="relative" style="height: 280px;" wire:ignore>
                                <canvas x-ref="chartComprasComp"></canvas>
                            </div>
                        </div>

                        {{-- Compras vs Gastos --}}
                        @if($separaGastos && ($compras['importe'] != 0 || $gastos['importe'] != 0))
                        <div class="border border-gray-200 rounded-xl p-3 sm:p-4">
                            <h3 class="text-xs sm:text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <i class="fas fa-scale-balanced text-purple-500"></i>
                                Compras vs Gastos
                            </h3>
                            <div class="relative" style="height: 280px;" wire:ignore>
                                <canvas x-ref="chartEgresosComp"></canvas>
                            </div>
                        </div>
                        @endif

                        {{-- Cobros por forma de pago --}}
                        <div class="border border-gray-200 rounded-xl p-3 sm:p-4">
                            <h3 class="text-xs sm:text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <i class="fas fa-arrow-down text-green-600"></i>
                                Cobros por forma de pago
                            </h3>
                            @if(count($cobrosPorForma) > 0)
                                <div class="relative" style="height: 300px;" wire:ignore>
                                    <canvas x-ref="chartCobros"></canvas>
                                </div>
                            @else
                                <p class="text-sm text-gray-400 italic py-12 text-center">Sin cobros en el período</p>
                            @endif
                        </div>

                        {{-- Pagos por forma de pago --}}
                        <div class="border border-gray-200 rounded-xl p-3 sm:p-4">
                            <h3 class="text-xs sm:text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <i class="fas fa-arrow-up text-red-600"></i>
                                Pagos por forma de pago
                            </h3>
                            @if(count($pagosPorForma) > 0)
                                <div class="relative" style="height: 300px;" wire:ignore>
                                    <canvas x-ref="chartPagos"></canvas>
                                </div>
                            @else
                                <p class="text-sm text-gray-400 italic py-12 text-center">Sin pagos en el período</p>
                            @endif
                        </div>
                    </div>

                    {{-- Top compras por cuenta contable --}}
                    @if(count($comprasPorCuenta) > 0)
                    <div class="border border-gray-200 rounded-xl p-3 sm:p-4 mt-4">
                        <h3 class="text-xs sm:text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                            <i class="fas fa-list-ul text-[#FFAF22]"></i>
                            Top {{ min(10, count($comprasPorCuenta)) }} compras por cuenta contable
                        </h3>
                        <div class="relative h-[320px] sm:h-[400px]" wire:ignore>
                            <canvas x-ref="chartCuentas"></canvas>
                        </div>
                    </div>
                    @endif

                    {{-- Top gastos por cuenta contable --}}
                    @if($separaGastos && count($gastosPorCuenta) > 0)
                    <div class="border border-gray-200 rounded-xl p-3 sm:p-4 mt-4">
                        <h3 class="text-xs sm:text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                            <i class="fas fa-file-invoice-dollar text-purple-500"></i>
                            Top {{ min(10, count($gastosPorCuenta)) }} gastos por cuenta contable
                        </h3>
                        <div class="relative h-[320px] sm:h-[400px]" wire:ignore>
                            <canvas x-ref="chartGastosCuentas"></canvas>
                        </div>
                    </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
