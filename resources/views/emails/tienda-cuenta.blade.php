<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>{{ $titulo }}</title></head>
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
                        <h1 style="margin:0 0 12px; font-size: 20px; color:#1f2937;">{{ $titulo }}</h1>
                        <p style="margin:0 0 24px; color:#4b5563; font-size: 14px; line-height: 1.5;">{{ $texto }}</p>
                        <p style="text-align:center; margin: 0 0 24px;">
                            <a href="{{ $url }}" style="display:inline-block; background-color: {{ $colorBoton }}; color:#ffffff; text-decoration:none; font-weight: 700; padding: 12px 26px; border-radius: 8px;">{{ $boton }}</a>
                        </p>
                        <p style="margin:0; color:#9ca3af; font-size: 12px;">Si el botón no funciona, copiá este enlace en el navegador:<br><a href="{{ $url }}" style="color:#6b7280; word-break: break-all;">{{ $url }}</a></p>
                        <p style="margin:16px 0 0; color:#9ca3af; font-size: 12px;">El enlace vence en 48 horas.</p>
                    </td>
                </tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
