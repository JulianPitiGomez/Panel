<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de Resultado</title>
    <style>
        @page { margin: 25px 30px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; margin: 0; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        h2 { font-size: 12px; margin: 14px 0 6px; padding-bottom: 3px; border-bottom: 2px solid #FFAF22; color: #222036; }
        h3 { font-size: 11px; margin: 10px 0 4px; color: #444; }
        .header { border-bottom: 2px solid #222036; padding-bottom: 6px; margin-bottom: 10px; }
        .header .meta { font-size: 9px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th { background: #f3f4f6; text-align: left; padding: 4px 6px; font-size: 9px; border-bottom: 1px solid #ddd; }
        td { padding: 4px 6px; border-bottom: 1px solid #eee; font-size: 10px; }
        td.right { text-align: right; font-family: DejaVu Sans Mono, monospace; }
        td.center { text-align: center; }
        tr.section td { background: #e5e7eb; font-weight: bold; font-size: 9px; text-transform: uppercase; padding: 5px 6px; }
        tr.subtotal td { background: #fef3c7; font-weight: bold; }
        tr.total td { background: #d1d5db; font-weight: bold; border-top: 2px solid #6b7280; padding: 6px; }
        .indent { padding-left: 18px; }
        .small { font-size: 9px; color: #666; }
        .pos { color: #15803d; }
        .neg { color: #b91c1c; }
        .kpi-row { display: table; width: 100%; margin-bottom: 10px; }
        .kpi { display: table-cell; padding: 6px; border: 1px solid #ddd; text-align: center; width: 25%; }
        .kpi-row.kpi5 .kpi { width: 20%; }
        .kpi .label { font-size: 9px; color: #666; }
        .kpi .value { font-size: 13px; font-weight: bold; margin-top: 2px; }
        .page-break { page-break-before: always; }
        .note { font-size: 9px; color: #666; font-style: italic; margin-top: 4px; }
    </style>
</head>
<body>

<div class="header">
    <h1>Estado de Resultado</h1>
    <div class="meta">
        @if($cliente) <strong>{{ $cliente }}</strong> · @endif
        Período: {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}
        · Generado: {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

{{-- ============ DEVENGADO ============ --}}
<h2>1. Estado de Resultados (devengado)</h2>

<div class="kpi-row {{ $separaGastos ? 'kpi5' : '' }}">
    <div class="kpi">
        <div class="label">Ventas totales</div>
        <div class="value">${{ number_format($ventas['importe'], 2) }}</div>
    </div>
    <div class="kpi">
        <div class="label">{{ $separaGastos ? 'Compras (mercadería)' : 'Compras totales' }}</div>
        <div class="value">${{ number_format($compras['importe'], 2) }}</div>
    </div>
    @if($separaGastos)
    <div class="kpi">
        <div class="label">Gastos</div>
        <div class="value">${{ number_format($gastos['importe'], 2) }}</div>
    </div>
    @endif
    <div class="kpi">
        <div class="label">Resultado operativo</div>
        <div class="value {{ $resultadoOperativo >= 0 ? 'pos' : 'neg' }}">${{ number_format($resultadoOperativo, 2) }}</div>
    </div>
    <div class="kpi">
        <div class="label">Margen sobre ventas</div>
        <div class="value">{{ number_format($margenOperativo, 2) }}%</div>
    </div>
</div>

<table>
    <tr class="section"><td colspan="2">Ingresos por ventas</td></tr>
    <tr><td class="indent">Ventas fiscales (con CAE)</td><td class="right">${{ number_format($ventas['fiscal'], 2) }}</td></tr>
    <tr><td class="indent">Ventas no fiscales</td><td class="right">${{ number_format($ventas['no_fiscal'], 2) }}</td></tr>
    <tr><td class="indent small">— Neto sin impuestos (según libro IVA ventas)</td><td class="right small">${{ number_format($ventas['neto'], 2) }}</td></tr>
    <tr><td class="indent small">— IVA débito fiscal (según libro IVA ventas)</td><td class="right small">${{ number_format($ventas['iva'], 2) }}</td></tr>
    <tr><td class="indent small">— Percepciones IIBB (solo fiscales)</td><td class="right small">${{ number_format($ventas['iibb'], 2) }}</td></tr>
    <tr><td class="indent small">— Sobretasa / imp. internos (solo fiscales)</td><td class="right small">${{ number_format($ventas['sobretasa'], 2) }}</td></tr>
    <tr class="subtotal"><td>Total facturado</td><td class="right">${{ number_format($ventas['importe'], 2) }}</td></tr>

    <tr class="section"><td colspan="2">{{ $separaGastos ? 'Egresos por compras (mercadería)' : 'Egresos por compras' }}</td></tr>
    <tr><td class="indent">Compras fiscales (computables IVA)</td><td class="right">${{ number_format($compras['fiscal'], 2) }}</td></tr>
    <tr><td class="indent">Compras no fiscales</td><td class="right">${{ number_format($compras['no_fiscal'], 2) }}</td></tr>
    <tr><td class="indent small">— No gravado</td><td class="right small">${{ number_format($compras['nograbado'], 2) }}</td></tr>
    <tr><td class="indent small">— Neto sin IVA</td><td class="right small">${{ number_format($compras['neto'], 2) }}</td></tr>
    <tr><td class="indent small">— IVA crédito fiscal (solo computables)</td><td class="right small">${{ number_format($compras['iva'], 2) }}</td></tr>
    <tr class="subtotal"><td>Total compras</td><td class="right">${{ number_format($compras['importe'], 2) }}</td></tr>

    @if($separaGastos)
    <tr class="section"><td colspan="2">Egresos por gastos</td></tr>
    <tr><td class="indent">Gastos fiscales (computables IVA)</td><td class="right">${{ number_format($gastos['fiscal'], 2) }}</td></tr>
    <tr><td class="indent">Gastos no fiscales</td><td class="right">${{ number_format($gastos['no_fiscal'], 2) }}</td></tr>
    <tr><td class="indent small">— No gravado</td><td class="right small">${{ number_format($gastos['nograbado'], 2) }}</td></tr>
    <tr><td class="indent small">— Neto sin IVA</td><td class="right small">${{ number_format($gastos['neto'], 2) }}</td></tr>
    <tr><td class="indent small">— IVA crédito fiscal (solo computables)</td><td class="right small">${{ number_format($gastos['iva'], 2) }}</td></tr>
    <tr class="subtotal"><td>Total gastos</td><td class="right">${{ number_format($gastos['importe'], 2) }}</td></tr>
    <tr class="subtotal"><td>Total facturas de proveedores (compras + gastos)</td><td class="right">${{ number_format($egresosFacturados, 2) }}</td></tr>
    @endif

    <tr class="section"><td colspan="2">Personal</td></tr>
    <tr><td class="indent">Sueldos liquidados (devengado)</td><td class="right">${{ number_format($sueldosLiq, 2) }}</td></tr>

    <tr class="total"><td>Resultado bruto (Ventas − Compras)</td><td class="right {{ $resultadoBruto >= 0 ? 'pos' : 'neg' }}">${{ number_format($resultadoBruto, 2) }}</td></tr>
    @if($separaGastos)
    <tr><td class="small">Margen bruto sobre ventas</td><td class="right small">{{ number_format($margenBruto, 2) }}%</td></tr>
    @endif
    <tr class="total"><td>Resultado operativo ({{ $separaGastos ? '− gastos y sueldos liq.' : '− sueldos liq.' }})</td><td class="right {{ $resultadoOperativo >= 0 ? 'pos' : 'neg' }}">${{ number_format($resultadoOperativo, 2) }}</td></tr>
    <tr><td class="small">Saldo IVA del período (débito − crédito)</td><td class="right small">${{ number_format($saldoIva, 2) }}</td></tr>
    <tr><td class="small">Margen sobre ventas</td><td class="right small">{{ number_format($margenOperativo, 2) }}%</td></tr>
</table>

@foreach([['Compras agrupadas por cuenta contable', $comprasPorCuenta], ['Gastos agrupados por cuenta contable', $separaGastos ? $gastosPorCuenta : []]] as [$titulo, $filas])
@if(count($filas) > 0)
<h3>{{ $titulo }}</h3>
<table>
    <thead>
        <tr><th>Código</th><th>Cuenta</th><th style="text-align:right">Neto</th><th style="text-align:right">Total</th></tr>
    </thead>
    <tbody>
        @foreach($filas as $row)
        <tr>
            <td>{{ $row['codigo'] ?? '—' }}</td>
            <td>{{ $row['nombre'] }}</td>
            <td class="right">${{ number_format($row['neto'], 2) }}</td>
            <td class="right">${{ number_format($row['importe'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
@endforeach

@if($separaGastos)
<p class="note">Compras y gastos se separan según el tipo de la cuenta contable imputada en cada factura de proveedor (C = compra, G = gasto). Los comprobantes sin cuenta contable se cuentan como compra.</p>
@endif

<div class="page-break"></div>

{{-- ============ PERCIBIDO ============ --}}
<h2>2. Flujo de Fondos (percibido)</h2>

<div class="kpi-row">
    <div class="kpi" style="width:33%">
        <div class="label">Total ingresos</div>
        <div class="value pos">${{ number_format($cobros, 2) }}</div>
    </div>
    <div class="kpi" style="width:33%">
        <div class="label">Total egresos</div>
        <div class="value neg">${{ number_format($pagosProv + $sueldosPag, 2) }}</div>
    </div>
    <div class="kpi" style="width:33%">
        <div class="label">Flujo neto del período</div>
        <div class="value {{ $flujoNeto >= 0 ? 'pos' : 'neg' }}">${{ number_format($flujoNeto, 2) }}</div>
    </div>
</div>

<table>
    <tr class="section"><td colspan="2">Ingresos cobrados</td></tr>
    <tr><td class="indent">Cobros a clientes</td><td class="right">${{ number_format($cobros, 2) }}</td></tr>
    <tr class="subtotal"><td>Total ingresos</td><td class="right">${{ number_format($cobros, 2) }}</td></tr>

    <tr class="section"><td colspan="2">Egresos pagados</td></tr>
    <tr><td class="indent">Pagos a proveedores (órdenes de pago)</td><td class="right">${{ number_format($pagosProv, 2) }}</td></tr>
    <tr><td class="indent">Sueldos pagados</td><td class="right">${{ number_format($sueldosPag, 2) }}</td></tr>
    <tr class="subtotal"><td>Total egresos</td><td class="right">${{ number_format($pagosProv + $sueldosPag, 2) }}</td></tr>

    <tr class="total"><td>Flujo neto del período</td><td class="right {{ $flujoNeto >= 0 ? 'pos' : 'neg' }}">${{ number_format($flujoNeto, 2) }}</td></tr>
</table>

<h3>Cobros por forma de pago</h3>
@if(count($cobrosPorForma) > 0)
<table>
    <thead><tr><th>Forma de pago</th><th class="center">Cant.</th><th style="text-align:right">Importe</th></tr></thead>
    <tbody>
        @foreach($cobrosPorForma as $f)
        <tr>
            <td>{{ $f['nombre'] }}</td>
            <td class="center">{{ $f['cantidad'] }}</td>
            <td class="right">${{ number_format($f['total'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p class="small">Sin cobros en el período.</p>
@endif

<h3>Pagos por forma de pago</h3>
@if(count($pagosPorForma) > 0)
<table>
    <thead><tr><th>Forma de pago</th><th class="center">Cant.</th><th style="text-align:right">Importe</th></tr></thead>
    <tbody>
        @foreach($pagosPorForma as $f)
        <tr>
            <td>{{ $f['nombre'] }}</td>
            <td class="center">{{ $f['cantidad'] }}</td>
            <td class="right">${{ number_format($f['total'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p class="small">Sin pagos en el período.</p>
@endif

<p class="note">Las ventas en cuenta corriente solo aparecen acá cuando el cliente las paga. Las compras solo cuando se generó orden de pago.</p>

<div class="page-break"></div>

{{-- ============ TRANSFERENCIAS ============ --}}
<h2>3. Movimientos bancarios y de caja</h2>
<p class="note">Estos movimientos son transferencias internas entre caja y banco. No suman ni restan al flujo del negocio.</p>

<table>
    <tr class="section"><td colspan="2">Movimientos bancarios (depósito)</td></tr>
    <tr><td>Ingresos al banco (TIPO=D)</td><td class="right pos">${{ number_format($depositosIngresos, 2) }}</td></tr>
    <tr><td>Retiros del banco (TIPO=R)</td><td class="right neg">${{ number_format($depositosRetiros, 2) }}</td></tr>
    <tr class="subtotal"><td>Saldo</td><td class="right">${{ number_format($depositosIngresos - $depositosRetiros, 2) }}</td></tr>

    <tr class="section"><td colspan="2">Movimientos de caja (retiros)</td></tr>
    <tr><td>Ingresos a caja (TIPO=I)</td><td class="right pos">${{ number_format($retirosIngresos, 2) }}</td></tr>
    <tr><td>Salidas de caja (TIPO=R)</td><td class="right neg">${{ number_format($retirosSalidas, 2) }}</td></tr>
    <tr class="subtotal"><td>Saldo</td><td class="right">${{ number_format($retirosIngresos - $retirosSalidas, 2) }}</td></tr>
</table>

{{-- ============ CONCILIACIÓN ============ --}}
<h2>4. Conciliación devengado vs percibido</h2>
@php
    $diffVentas = $ventas['importe'] - $cobros;
    $diffCompras = $egresosFacturados - $pagosProv;
    $diffSueldos = $sueldosLiq - $sueldosPag;
@endphp

<table>
    <thead>
        <tr><th>Concepto</th><th style="text-align:right">Devengado</th><th style="text-align:right">Percibido</th><th style="text-align:right">Diferencia</th></tr>
    </thead>
    <tbody>
        <tr>
            <td>Ventas vs Cobros</td>
            <td class="right">${{ number_format($ventas['importe'], 2) }}</td>
            <td class="right">${{ number_format($cobros, 2) }}</td>
            <td class="right" style="font-weight:bold">${{ number_format($diffVentas, 2) }}</td>
        </tr>
        <tr>
            <td>{{ $separaGastos ? 'Compras + gastos vs Pagos' : 'Compras vs Pagos' }}</td>
            <td class="right">${{ number_format($egresosFacturados, 2) }}</td>
            <td class="right">${{ number_format($pagosProv, 2) }}</td>
            <td class="right" style="font-weight:bold">${{ number_format($diffCompras, 2) }}</td>
        </tr>
        <tr>
            <td>Sueldos liquidados vs pagados</td>
            <td class="right">${{ number_format($sueldosLiq, 2) }}</td>
            <td class="right">${{ number_format($sueldosPag, 2) }}</td>
            <td class="right" style="font-weight:bold">${{ number_format($diffSueldos, 2) }}</td>
        </tr>
    </tbody>
</table>

<p class="note">Diferencia positiva: facturé/liquidé más de lo que cobré/pagué. Diferencia negativa: cobré/pagué más de lo que facturé/liquidé.</p>

</body>
</html>
