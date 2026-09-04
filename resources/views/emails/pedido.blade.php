<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.07);">

                    {{-- Header con logo --}}
                    <tr>
                        <td style="background-color: {{ $colorBoton }}; padding: 30px; text-align: center;">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $nombreTienda }}" style="width: 70px; height: 70px; object-fit: contain; border-radius: 12px; margin-bottom: 10px; background: rgba(255,255,255,0.15); padding: 4px;">
                                <br>
                            @endif
                            <span style="color: #ffffff; font-size: 22px; font-weight: 700;">{{ $nombreTienda }}</span>
                        </td>
                    </tr>

                    {{-- Titulo --}}
                    <tr>
                        <td style="padding: 30px 30px 10px;">
                            <h1 style="margin: 0; font-size: 20px; color: #1f2937;">
                                @if($esComerciante)
                                    Nuevo pedido recibido
                                @else
                                    Tu pedido fue confirmado
                                @endif
                            </h1>
                            <p style="margin: 8px 0 0; color: #6b7280; font-size: 14px;">
                                Pedido <strong>#{{ $pedidoId }}</strong> &mdash; {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}
                            </p>
                        </td>
                    </tr>

                    {{-- Info cliente --}}
                    <tr>
                        <td style="padding: 15px 30px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; border-radius: 10px; padding: 15px;">
                                <tr>
                                    <td style="padding: 10px 15px;">
                                        <span style="font-size: 12px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px;">Cliente</span><br>
                                        <span style="font-size: 15px; color: #1f2937; font-weight: 600;">{{ $clienteNombre }}</span>
                                    </td>
                                    <td style="padding: 10px 15px; text-align: right;">
                                        <span style="font-size: 12px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px;">Entrega</span><br>
                                        <span style="font-size: 15px; color: #1f2937; font-weight: 600;">{{ \Carbon\Carbon::parse($parafecha)->format('d/m/Y') }}</span>
                                    </td>
                                </tr>
                                @if($observaciones)
                                    <tr>
                                        <td colspan="2" style="padding: 5px 15px 10px;">
                                            <span style="font-size: 12px; color: #9ca3af;">Observaciones:</span><br>
                                            <span style="font-size: 13px; color: #4b5563;">{{ $observaciones }}</span>
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </td>
                    </tr>

                    {{-- Tabla de productos --}}
                    <tr>
                        <td style="padding: 10px 30px 5px;">
                            <span style="font-size: 13px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Detalle del pedido</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 30px 20px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                                <tr style="border-bottom: 2px solid #e5e7eb;">
                                    <td style="padding: 10px 0; font-size: 12px; color: #9ca3af; text-transform: uppercase;">Producto</td>
                                    <td style="padding: 10px 0; font-size: 12px; color: #9ca3af; text-transform: uppercase; text-align: center;">Cant.</td>
                                    <td style="padding: 10px 0; font-size: 12px; color: #9ca3af; text-transform: uppercase; text-align: right;">Precio</td>
                                    <td style="padding: 10px 0; font-size: 12px; color: #9ca3af; text-transform: uppercase; text-align: right;">Subtotal</td>
                                </tr>
                                @foreach($items as $item)
                                    <tr style="border-bottom: 1px solid #f3f4f6;">
                                        <td style="padding: 10px 0; font-size: 13px; color: #374151;">
                                            {{ $item['nombre'] }}<br>
                                            <span style="font-size: 11px; color: #9ca3af;">Cod: {{ $item['codigo'] }}</span>
                                        </td>
                                        <td style="padding: 10px 0; font-size: 13px; color: #374151; text-align: center;">{{ $item['cantidad'] }}</td>
                                        <td style="padding: 10px 0; font-size: 13px; color: #374151; text-align: right;">${{ number_format($item['precio'], 2, ',', '.') }}</td>
                                        <td style="padding: 10px 0; font-size: 13px; color: #374151; text-align: right; font-weight: 600;">${{ number_format($item['subtotal'], 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td colspan="3" style="padding: 15px 0 10px; font-size: 16px; font-weight: 700; color: #1f2937; text-align: right;">
                                        Total:
                                    </td>
                                    <td style="padding: 15px 0 10px; font-size: 16px; font-weight: 700; text-align: right; color: {{ $colorBoton }};">
                                        ${{ number_format($total, 2, ',', '.') }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; font-size: 12px; color: #9ca3af;">
                                {{ $nombreTienda }}
                                @if(!empty($datos['direccion']))
                                    &mdash; {{ $datos['direccion'] }}{{ !empty($datos['localidad']) ? ', ' . $datos['localidad'] : '' }}
                                @endif
                            </p>
                            @if(!empty($datos['telefono']))
                                <p style="margin: 4px 0 0; font-size: 12px; color: #9ca3af;">Tel: {{ $datos['telefono'] }}</p>
                            @endif
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
