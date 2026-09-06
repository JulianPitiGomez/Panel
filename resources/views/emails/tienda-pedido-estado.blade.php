<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Pedido #{{ $idpedido }}: {{ $etiqueta }}</title></head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family: Arial, Helvetica, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; padding: 24px 0;">
        <tr><td align="center">
            <table width="520" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius: 12px; overflow:hidden; max-width: 96%;">
                <tr>
                    <td style="background-color: {{ $colorBoton }}; padding: 22px 30px; text-align:center;">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $nombreTienda }}" style="height: 48px; display:inline-block; margin-bottom: 8px;">
                        @endif
                        <div style="color:#ffffff; font-size: 20px; font-weight: 700;">{{ $nombreTienda }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 30px;">
                        <p style="margin:0 0 6px; color:#9ca3af; font-size: 12px; text-transform: uppercase; letter-spacing: .08em;">Pedido #{{ $idpedido }}</p>
                        <h1 style="margin:0 0 12px; font-size: 22px; color:#1f2937;">{{ $etiqueta }}</h1>
                        <p style="margin:0 0 22px; color:#4b5563; font-size: 14px; line-height: 1.5;">{{ $texto }}</p>

                        @if(count($pasos))
                            <table cellpadding="0" cellspacing="0" style="margin: 0 0 24px; width:100%;">
                                @foreach($pasos as $paso)
                                    <tr>
                                        <td style="width: 26px; padding: 4px 0; vertical-align: top;">
                                            <span style="display:inline-block; width:16px; height:16px; border-radius:50%; background-color: {{ $paso['hecho'] ? $colorBoton : '#e5e7eb' }}; color:#fff; font-size:11px; line-height:16px; text-align:center;">{{ $paso['hecho'] ? '✓' : '' }}</span>
                                        </td>
                                        <td style="padding: 4px 0; font-size: 13px; color: {{ $paso['actual'] ? '#1f2937' : ($paso['hecho'] ? '#6b7280' : '#9ca3af') }}; font-weight: {{ $paso['actual'] ? '700' : '400' }};">{{ $paso['etiqueta'] }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif

                        @if($urlSeguimiento)
                            <p style="text-align:center; margin: 0 0 12px;">
                                <a href="{{ $urlSeguimiento }}" style="display:inline-block; background-color: #1f2937; color:#ffffff; text-decoration:none; font-weight: 700; padding: 12px 26px; border-radius: 8px;">Seguir el envío ({{ $seguimiento }})</a>
                            </p>
                        @elseif($seguimiento)
                            <p style="margin:0 0 12px; color:#4b5563; font-size: 13px; text-align:center;">Número de seguimiento: <strong>{{ $seguimiento }}</strong></p>
                        @endif

                        <p style="text-align:center; margin: 0 0 24px;">
                            <a href="{{ $urlPedidos }}" style="display:inline-block; background-color: {{ $colorBoton }}; color:#ffffff; text-decoration:none; font-weight: 700; padding: 12px 26px; border-radius: 8px;">Ver mi pedido</a>
                        </p>
                        <p style="margin:0; color:#9ca3af; font-size: 12px;">Este aviso lo manda la tienda de {{ $nombreTienda }} cada vez que tu pedido avanza. Si tenés dudas, respondé este mail o escribile al comercio.</p>
                    </td>
                </tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
