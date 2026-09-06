<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $encab['etiqueta'] }} - {{ $nombreTienda }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color: #1f2937; margin: 24px; }
        .cab { display: table; width: 100%; border-bottom: 2px solid #1f2937; padding-bottom: 10px; margin-bottom: 14px; }
        .cab > div { display: table-cell; vertical-align: top; }
        .cab .der { text-align: right; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        .muted { color: #6b7280; font-size: 11px; }
        .aviso { background: #fef3c7; color: #92400e; padding: 8px 10px; border-radius: 6px; font-size: 11px; margin-bottom: 14px; }
        table.det { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.det th { text-align: left; font-size: 10px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #d1d5db; padding: 6px 4px; }
        table.det td { padding: 6px 4px; border-bottom: 1px solid #f3f4f6; }
        .r { text-align: right; }
        .c { text-align: center; }
        .totales { margin-top: 12px; width: 100%; }
        .totales td { padding: 3px 4px; }
        .total { font-size: 15px; font-weight: bold; }
        .pie { margin-top: 24px; font-size: 10px; color: #9ca3af; }
        @media print { .no-print { display: none; } body { margin: 10mm; } }
    </style>
</head>
<body>
    <div class="cab">
        <div>
            <h1>{{ $nombreTienda }}</h1>
            @if($razonSocial)<div>{{ $razonSocial }}</div>@endif
            @if($cuitComercio)<div class="muted">CUIT {{ $cuitComercio }}</div>@endif
            @if($direccionComercio)<div class="muted">{{ $direccionComercio }}</div>@endif
        </div>
        <div class="der">
            <h1>{{ $encab['etiqueta'] }}</h1>
            <div>Fecha: {{ \Carbon\Carbon::parse($encab['fecha'])->format('d/m/Y') }}</div>
            @if($encab['cae'])<div class="muted">CAE {{ $encab['cae'] }}{{ $encab['cae_vto'] ? ' · Vto ' . \Carbon\Carbon::parse($encab['cae_vto'])->format('d/m/Y') : '' }}</div>@endif
        </div>
    </div>

    <div class="aviso">Copia del detalle para control del cliente, generada por la tienda online. No reemplaza al comprobante fiscal original emitido por el comercio.</div>

    <div><strong>Cliente:</strong> {{ $encab['nombre'] ?: $cliente['nombre'] }}@if($encab['cuit']) · CUIT {{ $encab['cuit'] }}@endif</div>
    @if($encab['direccion'])<div class="muted">{{ $encab['direccion'] }}</div>@endif

    <table class="det">
        <thead><tr><th>Código</th><th>Detalle</th><th class="c">Cant.</th><th class="r">P. unit.</th><th class="r">Importe</th></tr></thead>
        <tbody>
            @forelse($renglones as $r)
                <tr>
                    <td>{{ $r['codigo'] }}</td>
                    <td>{{ $r['detalle'] }}</td>
                    <td class="c">{{ rtrim(rtrim(number_format($r['cantidad'], 3, ',', '.'), '0'), ',') }}</td>
                    <td class="r">${{ number_format($r['punit'], 2, ',', '.') }}</td>
                    <td class="r">${{ number_format($r['importe'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Sin renglones cargados para este comprobante.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="totales">
        <tr><td></td><td class="r" style="width: 160px;">Neto</td><td class="r" style="width: 120px;">${{ number_format($encab['neto'], 2, ',', '.') }}</td></tr>
        @if($encab['iva'] > 0)<tr><td></td><td class="r">IVA</td><td class="r">${{ number_format($encab['iva'], 2, ',', '.') }}</td></tr>@endif
        @if($encab['iibb'] > 0)<tr><td></td><td class="r">Ing. Brutos</td><td class="r">${{ number_format($encab['iibb'], 2, ',', '.') }}</td></tr>@endif
        @if($encab['percep'] > 0)<tr><td></td><td class="r">Percepciones</td><td class="r">${{ number_format($encab['percep'], 2, ',', '.') }}</td></tr>@endif
        <tr class="total"><td></td><td class="r">TOTAL</td><td class="r">${{ number_format($encab['importe'], 2, ',', '.') }}</td></tr>
    </table>

    @if($encab['observa'])<p class="muted">{{ $encab['observa'] }}</p>@endif

    <p class="pie">Generado el {{ now()->format('d/m/Y H:i') }} desde la tienda online de {{ $nombreTienda }}.</p>
    <p class="no-print" style="margin-top: 16px;"><a href="javascript:window.print()">Imprimir</a></p>
</body>
</html>
