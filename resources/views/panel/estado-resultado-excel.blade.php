<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; }
        th { background: #222036; color: #fff; font-weight: bold; padding: 6px; text-align: left; border: 1px solid #999; }
        td { padding: 4px 6px; border: 1px solid #ccc; }
        td.right { text-align: right; mso-number-format: "\#\,\#\#0\.00"; }
        td.percent { text-align: right; mso-number-format: "0\.00%"; }
        td.center { text-align: center; }
        tr.section td { background: #FFAF22; color: #222036; font-weight: bold; text-transform: uppercase; }
        tr.subtotal td { background: #fef3c7; font-weight: bold; }
        tr.total td { background: #d1d5db; font-weight: bold; }
        tr.title td { background: #222036; color: #fff; font-size: 14px; font-weight: bold; padding: 8px; }
        .pos { color: #15803d; }
        .neg { color: #b91c1c; }
    </style>
</head>
<body>

<table>
    <tr><td colspan="4" style="font-size:16px; font-weight:bold; padding:8px;">Estado de Resultado</td></tr>
    <tr>
        <td colspan="4" style="padding:6px;">
            @if($cliente) <strong>{{ $cliente }}</strong> · @endif
            Período: {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}
            · Generado: {{ now()->format('d/m/Y H:i') }}
        </td>
    </tr>
    <tr><td colspan="4">&nbsp;</td></tr>

    {{-- ============ DEVENGADO ============ --}}
    <tr class="title"><td colspan="4">1. Estado de Resultados (devengado)</td></tr>

    <tr class="section"><td colspan="3">Ingresos por ventas</td><td>Importe</td></tr>
    <tr><td colspan="3">Ventas fiscales (con CAE)</td><td class="right">{{ number_format($ventas['fiscal'], 2, '.', '') }}</td></tr>
    <tr><td colspan="3">Ventas no fiscales</td><td class="right">{{ number_format($ventas['no_fiscal'], 2, '.', '') }}</td></tr>
    <tr><td colspan="3">— Neto sin impuestos (según libro IVA ventas)</td><td class="right">{{ number_format($ventas['neto'], 2, '.', '') }}</td></tr>
    <tr><td colspan="3">— IVA débito fiscal (según libro IVA ventas)</td><td class="right">{{ number_format($ventas['iva'], 2, '.', '') }}</td></tr>
    <tr><td colspan="3">— Percepciones IIBB (solo fiscales)</td><td class="right">{{ number_format($ventas['iibb'], 2, '.', '') }}</td></tr>
    <tr><td colspan="3">— Sobretasa / imp. internos (solo fiscales)</td><td class="right">{{ number_format($ventas['sobretasa'], 2, '.', '') }}</td></tr>
    <tr class="subtotal"><td colspan="3">Total facturado</td><td class="right">{{ number_format($ventas['importe'], 2, '.', '') }}</td></tr>

    <tr class="section"><td colspan="3">{{ $separaGastos ? 'Egresos por compras (mercadería)' : 'Egresos por compras' }}</td><td>Importe</td></tr>
    <tr><td colspan="3">Compras fiscales (computables IVA)</td><td class="right">{{ number_format($compras['fiscal'], 2, '.', '') }}</td></tr>
    <tr><td colspan="3">Compras no fiscales</td><td class="right">{{ number_format($compras['no_fiscal'], 2, '.', '') }}</td></tr>
    <tr><td colspan="3">— No gravado</td><td class="right">{{ number_format($compras['nograbado'], 2, '.', '') }}</td></tr>
    <tr><td colspan="3">— Neto sin IVA</td><td class="right">{{ number_format($compras['neto'], 2, '.', '') }}</td></tr>
    <tr><td colspan="3">— IVA crédito fiscal (solo computables)</td><td class="right">{{ number_format($compras['iva'], 2, '.', '') }}</td></tr>
    <tr class="subtotal"><td colspan="3">Total compras</td><td class="right">{{ number_format($compras['importe'], 2, '.', '') }}</td></tr>

    @if($separaGastos)
    <tr class="section"><td colspan="3">Egresos por gastos</td><td>Importe</td></tr>
    <tr><td colspan="3">Gastos fiscales (computables IVA)</td><td class="right">{{ number_format($gastos['fiscal'], 2, '.', '') }}</td></tr>
    <tr><td colspan="3">Gastos no fiscales</td><td class="right">{{ number_format($gastos['no_fiscal'], 2, '.', '') }}</td></tr>
    <tr><td colspan="3">— No gravado</td><td class="right">{{ number_format($gastos['nograbado'], 2, '.', '') }}</td></tr>
    <tr><td colspan="3">— Neto sin IVA</td><td class="right">{{ number_format($gastos['neto'], 2, '.', '') }}</td></tr>
    <tr><td colspan="3">— IVA crédito fiscal (solo computables)</td><td class="right">{{ number_format($gastos['iva'], 2, '.', '') }}</td></tr>
    <tr class="subtotal"><td colspan="3">Total gastos</td><td class="right">{{ number_format($gastos['importe'], 2, '.', '') }}</td></tr>
    <tr class="subtotal"><td colspan="3">Total facturas de proveedores (compras + gastos)</td><td class="right">{{ number_format($egresosFacturados, 2, '.', '') }}</td></tr>
    @endif

    <tr class="section"><td colspan="3">Personal</td><td>Importe</td></tr>
    <tr><td colspan="3">Sueldos liquidados (devengado)</td><td class="right">{{ number_format($sueldosLiq, 2, '.', '') }}</td></tr>

    <tr class="total"><td colspan="3">Resultado bruto (Ventas − Compras)</td><td class="right">{{ number_format($resultadoBruto, 2, '.', '') }}</td></tr>
    @if($separaGastos)
    <tr><td colspan="3">Margen bruto sobre ventas (%)</td><td class="right">{{ number_format($margenBruto, 2, '.', '') }}</td></tr>
    @endif
    <tr class="total"><td colspan="3">Resultado operativo ({{ $separaGastos ? '− gastos y sueldos liq.' : '− sueldos liq.' }})</td><td class="right">{{ number_format($resultadoOperativo, 2, '.', '') }}</td></tr>
    <tr><td colspan="3">Saldo IVA del período (débito − crédito)</td><td class="right">{{ number_format($saldoIva, 2, '.', '') }}</td></tr>
    <tr><td colspan="3">Margen sobre ventas (%)</td><td class="right">{{ number_format($margenOperativo, 2, '.', '') }}</td></tr>

    <tr><td colspan="4">&nbsp;</td></tr>

    @foreach([['Compras agrupadas por cuenta contable', $comprasPorCuenta], ['Gastos agrupados por cuenta contable', $separaGastos ? $gastosPorCuenta : []]] as [$titulo, $filas])
    @if(count($filas) > 0)
    <tr class="title"><td colspan="4">{{ $titulo }}</td></tr>
    <tr>
        <th>Código</th>
        <th>Cuenta</th>
        <th>Neto</th>
        <th>Total</th>
    </tr>
    @foreach($filas as $row)
    <tr>
        <td>{{ $row['codigo'] ?? '' }}</td>
        <td>{{ $row['nombre'] }}</td>
        <td class="right">{{ number_format($row['neto'], 2, '.', '') }}</td>
        <td class="right">{{ number_format($row['importe'], 2, '.', '') }}</td>
    </tr>
    @endforeach
    <tr><td colspan="4">&nbsp;</td></tr>
    @endif
    @endforeach

    {{-- ============ PERCIBIDO ============ --}}
    <tr class="title"><td colspan="4">2. Flujo de Fondos (percibido)</td></tr>

    <tr class="section"><td colspan="3">Ingresos cobrados</td><td>Importe</td></tr>
    <tr><td colspan="3">Cobros a clientes</td><td class="right">{{ number_format($cobros, 2, '.', '') }}</td></tr>
    <tr class="subtotal"><td colspan="3">Total ingresos</td><td class="right">{{ number_format($cobros, 2, '.', '') }}</td></tr>

    <tr class="section"><td colspan="3">Egresos pagados</td><td>Importe</td></tr>
    <tr><td colspan="3">Pagos a proveedores (órdenes de pago)</td><td class="right">{{ number_format($pagosProv, 2, '.', '') }}</td></tr>
    <tr><td colspan="3">Sueldos pagados</td><td class="right">{{ number_format($sueldosPag, 2, '.', '') }}</td></tr>
    <tr class="subtotal"><td colspan="3">Total egresos</td><td class="right">{{ number_format($pagosProv + $sueldosPag, 2, '.', '') }}</td></tr>

    <tr class="total"><td colspan="3">Flujo neto del período</td><td class="right">{{ number_format($flujoNeto, 2, '.', '') }}</td></tr>

    <tr><td colspan="4">&nbsp;</td></tr>

    {{-- Formas de pago --}}
    <tr class="title"><td colspan="4">Cobros por forma de pago</td></tr>
    @if(count($cobrosPorForma) > 0)
        <tr>
            <th colspan="2">Forma de pago</th>
            <th>Cantidad</th>
            <th>Importe</th>
        </tr>
        @foreach($cobrosPorForma as $f)
        <tr>
            <td colspan="2">{{ $f['nombre'] }}</td>
            <td class="center">{{ $f['cantidad'] }}</td>
            <td class="right">{{ number_format($f['total'], 2, '.', '') }}</td>
        </tr>
        @endforeach
    @else
        <tr><td colspan="4">Sin cobros en el período</td></tr>
    @endif
    <tr><td colspan="4">&nbsp;</td></tr>

    <tr class="title"><td colspan="4">Pagos por forma de pago</td></tr>
    @if(count($pagosPorForma) > 0)
        <tr>
            <th colspan="2">Forma de pago</th>
            <th>Cantidad</th>
            <th>Importe</th>
        </tr>
        @foreach($pagosPorForma as $f)
        <tr>
            <td colspan="2">{{ $f['nombre'] }}</td>
            <td class="center">{{ $f['cantidad'] }}</td>
            <td class="right">{{ number_format($f['total'], 2, '.', '') }}</td>
        </tr>
        @endforeach
    @else
        <tr><td colspan="4">Sin pagos en el período</td></tr>
    @endif
    <tr><td colspan="4">&nbsp;</td></tr>

    {{-- ============ TRANSFERENCIAS ============ --}}
    <tr class="title"><td colspan="4">3. Movimientos bancarios y de caja</td></tr>
    <tr><td colspan="4">Transferencias internas entre caja y banco. No suman ni restan al flujo del negocio.</td></tr>

    <tr class="section"><td colspan="3">Movimientos bancarios (depósito)</td><td>Importe</td></tr>
    <tr><td colspan="3">Ingresos al banco (TIPO=D)</td><td class="right">{{ number_format($depositosIngresos, 2, '.', '') }}</td></tr>
    <tr><td colspan="3">Retiros del banco (TIPO=R)</td><td class="right">{{ number_format($depositosRetiros, 2, '.', '') }}</td></tr>
    <tr class="subtotal"><td colspan="3">Saldo</td><td class="right">{{ number_format($depositosIngresos - $depositosRetiros, 2, '.', '') }}</td></tr>

    <tr class="section"><td colspan="3">Movimientos de caja (retiros)</td><td>Importe</td></tr>
    <tr><td colspan="3">Ingresos a caja (TIPO=I)</td><td class="right">{{ number_format($retirosIngresos, 2, '.', '') }}</td></tr>
    <tr><td colspan="3">Salidas de caja (TIPO=R)</td><td class="right">{{ number_format($retirosSalidas, 2, '.', '') }}</td></tr>
    <tr class="subtotal"><td colspan="3">Saldo</td><td class="right">{{ number_format($retirosIngresos - $retirosSalidas, 2, '.', '') }}</td></tr>

    <tr><td colspan="4">&nbsp;</td></tr>

    {{-- ============ CONCILIACIÓN ============ --}}
    @php
        $diffVentas = $ventas['importe'] - $cobros;
        $diffCompras = $egresosFacturados - $pagosProv;
        $diffSueldos = $sueldosLiq - $sueldosPag;
    @endphp

    <tr class="title"><td colspan="4">4. Conciliación devengado vs percibido</td></tr>
    <tr>
        <th>Concepto</th>
        <th>Devengado</th>
        <th>Percibido</th>
        <th>Diferencia</th>
    </tr>
    <tr>
        <td>Ventas vs Cobros</td>
        <td class="right">{{ number_format($ventas['importe'], 2, '.', '') }}</td>
        <td class="right">{{ number_format($cobros, 2, '.', '') }}</td>
        <td class="right">{{ number_format($diffVentas, 2, '.', '') }}</td>
    </tr>
    <tr>
        <td>{{ $separaGastos ? 'Compras + gastos vs Pagos' : 'Compras vs Pagos' }}</td>
        <td class="right">{{ number_format($egresosFacturados, 2, '.', '') }}</td>
        <td class="right">{{ number_format($pagosProv, 2, '.', '') }}</td>
        <td class="right">{{ number_format($diffCompras, 2, '.', '') }}</td>
    </tr>
    <tr>
        <td>Sueldos liquidados vs pagados</td>
        <td class="right">{{ number_format($sueldosLiq, 2, '.', '') }}</td>
        <td class="right">{{ number_format($sueldosPag, 2, '.', '') }}</td>
        <td class="right">{{ number_format($diffSueldos, 2, '.', '') }}</td>
    </tr>
</table>

</body>
</html>
