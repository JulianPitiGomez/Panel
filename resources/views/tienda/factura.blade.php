{{-- Comprobante con el formato A4 de gestion (ventas.prg: PrintFactuElec / FacturaNoFiscal). Coordenadas en cm, como el .prg. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $f['titulo'] }} {{ $f['letra'] }} {{ $f['numero'] }}</title>
    <style>
        @page { size: A4 portrait; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: Helvetica, Arial, sans-serif; font-size: 8pt; color: #000; }
        .hoja { position: relative; width: 21cm; height: 29.7cm; overflow: hidden; page-break-after: always; background: #fff; }
        .hoja:last-child { page-break-after: auto; }
        .p { position: absolute; margin: 0; line-height: 1.15; white-space: nowrap; overflow: hidden; }
        .w { white-space: normal; }
        .box { position: absolute; border: 1px solid #000; }
        .b { font-weight: bold; }
        .r { text-align: right; }
        .c { text-align: center; }
        .f1 { font-size: 12pt; font-weight: bold; }
        .f2 { font-size: 26pt; font-weight: bold; line-height: 1; }
        .f5 { font-size: 7pt; font-weight: bold; }
        .f0 { font-size: 8pt; font-weight: normal; }
        img.logo { position: absolute; top: 1.6cm; left: .6cm; width: 8cm; height: 3cm; object-fit: contain; }
        @media print { .no-print { display: none; } }
        .no-print { position: fixed; top: 8px; right: 8px; font-family: Arial, sans-serif; font-size: 12px; }
    </style>
</head>
<body>
@php
    $e = $f['empresa']; $cl = $f['cliente']; $t = $f['totales'];
    $num = fn ($n) => \App\Services\TiendaFacturaService::num((float) $n);
    $totalPaginas = count($f['paginas']);
@endphp
@foreach($f['paginas'] as $np => $pag)
<div class="hoja">
    {{-- Cuadros (oPrn:CmBox) --}}
    <div class="box" style="top:.5cm; left:.5cm; width:20cm; height:1cm;"></div>
    <div class="box" style="top:1.5cm; left:.5cm; width:20cm; height:3.5cm;"></div>
    <div class="box" style="top:5.3cm; left:.5cm; width:20cm; height:2.2cm;"></div>
    <div class="box" style="top:8cm; left:.5cm; width:20cm; height:1cm;"></div>
    <div class="box" style="top:22cm; left:.5cm; width:20cm; height:3cm;"></div>

    <p class="p f1 c" style="top:.8cm; left:1.15cm; width:18cm;">ORIGINAL{{ $np > 0 ? ' Hoja:' . ($np + 1) : '' }}</p>

    {{-- Datos de la empresa (izquierda) --}}
    @if($e['imprime_dat'])
        @if(in_array($e['x1'], [2, 3]) && !empty($logo))
            <img class="logo" src="{{ $logo }}" alt="">
        @endif
        @if(in_array($e['x1'], [1, 3]))
            <div class="p w c" style="top:2cm; left:1cm; width:9cm; height:3cm;">
                <div class="f1">{{ $e['nombre'] }}</div>
                <div class="f0">Domicilio Comercial:{{ $e['domicilio'] }}</div>
                <div class="f0">{{ $e['coniva'] }}</div>
            </div>
        @endif
    @endif

    {{-- Tipo, letra y numero (derecha) --}}
    <p class="p f1" style="top:1.7cm; left:11cm;">{{ $f['titulo'] }}</p>
    <div class="box" style="top:1.5cm; left:9.4cm; width:1.3cm; height:1.2cm;"></div>
    <p class="p f2" style="top:1.55cm; left:9.4cm; width:1.3cm; text-align:center;">{{ $f['letra_cuadro'] }}</p>
    @if($f['fiscal'])
        <p class="p f0" style="top:2.8cm; left:9.4cm; width:1.4cm; text-align:center;">Cod:{{ $f['tipfor'] }}</p>
    @endif
    <p class="p f0" style="top:2.5cm; left:11cm;">Punto de venta:{{ $f['punto_venta'] }} Comprobante Nro:{{ $f['nro'] }}</p>
    <p class="p f0" style="top:3cm; left:11cm;">Fecha de emision:{{ $f['fecha_emision'] }}</p>
    @if($e['imprime_dat'])
        <p class="p f0" style="top:3.5cm; left:11cm;">CUIT:{{ $e['cuit'] }}</p>
        <p class="p f0" style="top:4cm; left:11cm;">Ingresos brutos:{{ $e['ingb'] }}</p>
        <p class="p f0" style="top:4.5cm; left:11cm;">Inicio de Actividades:{{ $e['inicio'] }}</p>
    @endif

    {{-- Datos del cliente --}}
    <p class="p b r" style="top:5.5cm; left:1cm; width:3cm;">C.U.I.T.:</p>
    <p class="p" style="top:5.5cm; left:4.1cm; width:6cm;">{{ $cl['cuit'] }}</p>
    <p class="p b r" style="top:5.5cm; left:9.5cm; width:2.5cm;">Razon Social:</p>
    <p class="p w" style="top:5.5cm; left:12.5cm; width:8cm; height:.9cm;">{{ $cl['nombre'] }}</p>
    <p class="p b r" style="top:6cm; left:1cm; width:3cm;">Condicion IVA:</p>
    <p class="p" style="top:6cm; left:4.1cm; width:6cm;">{{ $cl['coniva'] }}</p>
    @php $yDir = mb_strlen($cl['nombre']) > 44 ? 6.4 : 6; @endphp
    <p class="p b r" style="top:{{ $yDir }}cm; left:9.5cm; width:2.5cm;">Direccion:</p>
    <p class="p w" style="top:{{ $yDir }}cm; left:12.5cm; width:8cm; height:.9cm;">{{ $cl['direccion'] }}</p>
    <p class="p b r" style="top:6.5cm; left:1cm; width:3cm;">Condicion venta:</p>
    <p class="p" style="top:6.5cm; left:4.1cm; width:6cm;">{{ $cl['cond_venta'] }}</p>
    <p class="p b r" style="top:7cm; left:1cm; width:3cm;">Forma de pago:</p>
    <p class="p" style="top:7cm; left:4.1cm; width:6cm;">{{ $cl['forma_pago'] }}</p>

    {{-- Titulos de columnas --}}
    <p class="p f5" style="top:8.3cm; left:.7cm; width:2.5cm;">Codigo</p>
    <p class="p f5" style="top:8.3cm; left:3.5cm; width:6.5cm;">Descripcion del producto</p>
    <p class="p f5 r" style="top:8.3cm; left:10cm; width:2cm;">Cantidad</p>
    @if($f['usa_bultos'])<p class="p f5 r" style="top:8.3cm; left:12.05cm; width:.5cm;">Btos</p>@endif
    <p class="p f5 r" style="top:8.3cm; left:12cm; width:2cm;">Pr.Unitario</p>
    <p class="p f5 c" style="top:8.3cm; left:14cm; width:1.5cm;">{{ $f['fiscal'] ? 'Desc.' : 'Desc.%' }}</p>
    <p class="p f5 r" style="top:8.3cm; left:15.5cm; width:2cm;">Subtotal</p>
    @if($f['fiscal'])
        <p class="p f5 c" style="top:8.3cm; left:17.5cm; width:1cm;">Alic. IVA</p>
        <p class="p f5 c w" style="top:8.1cm; left:18.5cm; width:2cm; line-height:1;">Subtotal con IVA Incluído</p>
    @endif

    {{-- Renglones --}}
    @foreach($pag['renglones'] as $r)
        @if(!empty($r['titulo_promo']))
            <p class="p f0 c" style="top:{{ $r['y'] - .6 }}cm; left:1cm; width:10cm;">Descuentos Promociones</p>
        @endif
        <p class="p f5" style="top:{{ $r['y'] }}cm; left:1cm; width:2.5cm;">{{ $r['codigo'] }}</p>
        <p class="p f5 w" style="top:{{ $r['y'] }}cm; left:3.5cm; width:6.5cm; height:{{ $r['lineas'] * .45 + .1 }}cm;">{{ $r['detalle'] }}</p>
        <p class="p f5 r" style="top:{{ $r['y'] }}cm; left:10cm; width:2cm;">{{ number_format($r['cantidad'], 2, '.', '') }}</p>
        @if($f['usa_bultos'])<p class="p f5 r" style="top:{{ $r['y'] }}cm; left:12.05cm; width:.5cm;">{{ $r['bultos'] == floor($r['bultos']) ? (int) $r['bultos'] : number_format($r['bultos'], 1, '.', '') }}</p>@endif
        <p class="p f5 r" style="top:{{ $r['y'] }}cm; left:12cm; width:2cm;">{{ $num($r['punit']) }}</p>
        <p class="p f5 r" style="top:{{ $r['y'] }}cm; left:14.2cm; width:1.8cm;">{{ $r['desc_texto'] }}</p>
        <p class="p f5 r" style="top:{{ $r['y'] }}cm; left:15.5cm; width:2cm;">{{ $num($r['subtot']) }}</p>
        @if($f['fiscal'])
            <p class="p f5 c" style="top:{{ $r['y'] }}cm; left:17.7cm; width:.7cm;">{{ $r['alicu'] }}</p>
            <p class="p f5 r" style="top:{{ $r['y'] }}cm; left:18.3cm; width:2cm;">{{ $num($r['subtotalciva']) }}</p>
        @endif
    @endforeach

    @if($pag['continua'])
        <p class="p f0" style="top:23cm; left:5cm; width:9cm;">CONTINUA EN OTRA HOJA</p>
    @else
        @php $yObs = min($pag['y_fin'] + .5, 19.5); @endphp
        {{-- Observaciones y pie de renglones --}}
        <div class="p w f0" style="top:{{ $yObs }}cm; left:1cm; width:18cm; height:2.2cm;">
            {{ $f['observa'] }}
            @if(!$f['fiscal'] && $f['desc_total'] > 0)<br>Descuento Gral:{{ $num($f['desc_total']) }}@endif
            @if($f['leyenda_monotributo'])<br>{{ $f['leyenda_monotributo'] }}@endif
        </div>
        @if($f['usa_bultos'])
            <p class="p f0" style="top:{{ $yObs + 1.6 }}cm; left:1cm; width:3cm;">Total Bultos:{{ str_pad((string) (int) $f['total_bultos'], 4, ' ', STR_PAD_LEFT) }}</p>
        @endif

        {{-- Cuadro de totales --}}
        @if($f['fiscal'])
            <p class="p b r" style="top:22.5cm; left:1cm; width:5cm;">Percepcion IIBB {{ number_format($t['percep_pct'], 2, '.', '') }}% {{ $f['signo'] }}:</p>
            <p class="p r" style="top:22.5cm; left:6.1cm; width:3cm;">{{ $num($t['iibb']) }}</p>
            @if(!$t['es_am'])
                <p class="p b r" style="top:22.5cm; left:12cm; width:4cm;">Subtotal {{ $f['signo'] }}:</p>
                <p class="p r" style="top:22.5cm; left:17cm; width:3cm;">{{ $num($t['subtotal']) }}</p>
                <p class="p b r" style="top:23cm; left:12cm; width:4cm;">Otros Tributos {{ $f['signo'] }}:</p>
                <p class="p r" style="top:23cm; left:17cm; width:3cm;">{{ $num($t['otros']) }}</p>
                <p class="p b r" style="top:23.5cm; left:12cm; width:4cm;">Total {{ $f['signo'] }}:</p>
                <p class="p r" style="top:23.5cm; left:17cm; width:3cm;">{{ $num($t['total']) }}</p>
                <p class="p f5 r" style="top:23.7cm; left:1cm; width:7cm;">Regimen de transparencia fiscal al Consumidor (Ley 27.743)</p>
                <p class="p f5 r" style="top:24.1cm; left:1cm; width:7cm;">IVA Contenido: ${{ $num($t['iva_contenido']) }}</p>
            @else
                <p class="p b r" style="top:22.5cm; left:12cm; width:4cm;">Subtotal {{ $f['signo'] }}:</p>
                <p class="p r" style="top:22.5cm; left:17cm; width:3cm;">{{ $num($t['subtotal']) }}</p>
                <p class="p b r" style="top:23cm; left:12cm; width:4cm;">Otros Tributos {{ $f['signo'] }}:</p>
                <p class="p r" style="top:23cm; left:17cm; width:3cm;">{{ $num($t['otros']) }}</p>
                @php $yIva = 23.5; @endphp
                @foreach($t['ivas'] as $iv)
                    <p class="p b r" style="top:{{ $yIva }}cm; left:12cm; width:4cm;">I.V.A. {{ $iv['nombre'] }} {{ $f['signo'] }}:</p>
                    <p class="p r" style="top:{{ $yIva }}cm; left:17cm; width:3cm;">{{ $num($iv['iva']) }}</p>
                    @php $yIva += .5; @endphp
                @endforeach
                <p class="p b r" style="top:{{ $yIva }}cm; left:12cm; width:4cm;">Total {{ $f['signo'] }}:</p>
                <p class="p r" style="top:{{ $yIva }}cm; left:17cm; width:3cm;">{{ $num($t['total']) }}</p>
            @endif
            @if($f['vendedor'])<p class="p b" style="top:23cm; left:1cm; width:5cm;">{{ $f['vendedor'] }}</p>@endif

            {{-- CAE, QR y ARCA --}}
            @if(!empty($arca))<img src="{{ $arca }}" alt="" style="position:absolute; top:25cm; left:1cm; width:2.5cm; height:2.2cm;">@endif
            @if(!empty($qr))<img src="{{ $qr }}" alt="" style="position:absolute; top:25.5cm; left:6cm; width:2.5cm; height:2.5cm;">@endif
            <p class="p b r" style="top:25.5cm; left:13cm; width:3.9cm;">CAE Nro:</p>
            <p class="p" style="top:25.5cm; left:17cm; width:6cm;">{{ $f['cae'] }}</p>
            <p class="p b r" style="top:26cm; left:13cm; width:3.9cm;">Fecha Vto CAE:</p>
            <p class="p" style="top:26cm; left:17cm; width:6cm;">{{ $f['cae_vto'] }}</p>
            @php $yPie = 28.1; @endphp
            @if($f['texto_dolares'])<p class="p f5 w" style="top:{{ $yPie }}cm; left:1cm; width:16cm;">{{ $f['texto_dolares'] }}</p>@php $yPie += .5; @endphp@endif
            @if($f['texto_puntos'])<p class="p f5" style="top:{{ $yPie }}cm; left:1cm; width:16cm;">{{ $f['texto_puntos'] }}</p>@endif
            @if($f['pie_fiscal'])<p class="p f5 w" style="top:27.5cm; left:13cm; width:7.5cm;">{{ $f['pie_fiscal'] }}</p>@endif
        @else
            @if($f['vendedor'])<p class="p b" style="top:23cm; left:1cm; width:5cm;">{{ $f['vendedor'] }}</p>@endif
            <p class="p b r" style="top:23.5cm; left:12cm; width:4cm;">Total $:</p>
            <p class="p r" style="top:23.5cm; left:17cm; width:3cm;">{{ $num($t['total']) }}</p>
            @if($f['texto_puntos'])<p class="p f5" style="top:24.1cm; left:1cm; width:16cm;">{{ $f['texto_puntos'] }}</p>@endif
        @endif
    @endif
</div>
@endforeach
@if(empty($pdf))
    <div class="no-print"><a href="javascript:window.print()">Imprimir</a></div>
@endif
</body>
</html>
