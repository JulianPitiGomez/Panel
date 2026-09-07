<?php

namespace App\Services;

/**
 * Sistema visual de la tienda (F6, estetica).
 *
 * El comercio elige cuatro colores (fondo, textos, precios, botones). De ahi se
 * deriva una paleta completa (tonos claros para fondos y chips, uno oscuro para
 * hover, el color del texto sobre el boton) que se publica como variables CSS
 * en :root, y las clases .t-* de resources/css/app.css las usan. Asi ninguna
 * pantalla vuelve a poner colores a mano y un color mal elegido no rompe la
 * lectura: si el texto no contrasta con el fondo, se corrige solo.
 *
 * Tambien define los estilos preconfigurados (presets) del panel y ayudas de
 * presentacion (nombre del producto sin el codigo adelante).
 */
class TiendaEstiloService
{
    public const DEFAULTS = [
        'color_fondo'  => '#f9fafb',
        'color_texto'  => '#1f2937',
        'color_precio' => '#1E1B35',
        'color_boton'  => '#FFAF22',
    ];

    /** Estilos preconfigurados: cada uno deja lista una combinacion completa. */
    public const PRESETS = [
        'clasico' => [
            'nombre' => 'Clásico', 'descripcion' => 'Fondo claro, tarjetas blancas y el naranja de siempre.',
            'valores' => ['color_fondo' => '#f9fafb', 'color_texto' => '#1f2937', 'color_precio' => '#1E1B35', 'color_boton' => '#FFAF22', 'estilo_tarjeta' => 'estandar', 'estilo_header' => 'completo', 'layout_catalogo' => 'top', 'columnas_desktop' => 4, 'tienda_fuente' => 'moderna'],
        ],
        'minimal' => [
            'nombre' => 'Minimal', 'descripcion' => 'Blanco y negro, mucho aire, tarjetas compactas.',
            'valores' => ['color_fondo' => '#ffffff', 'color_texto' => '#111827', 'color_precio' => '#111827', 'color_boton' => '#111827', 'estilo_tarjeta' => 'compacta', 'estilo_header' => 'simple', 'layout_catalogo' => 'top', 'columnas_desktop' => 4, 'tienda_fuente' => 'limpia'],
        ],
        'vibrante' => [
            'nombre' => 'Vibrante', 'descripcion' => 'Colores cálidos, tarjetas grandes con todo el detalle.',
            'valores' => ['color_fondo' => '#fff7ed', 'color_texto' => '#1f2937', 'color_precio' => '#9a3412', 'color_boton' => '#ea580c', 'estilo_tarjeta' => 'detallada', 'estilo_header' => 'completo', 'layout_catalogo' => 'top', 'columnas_desktop' => 3, 'tienda_fuente' => 'moderna'],
        ],
        'elegante' => [
            'nombre' => 'Elegante', 'descripcion' => 'Tonos oscuros y tipografía con serifa para marcas premium.',
            'valores' => ['color_fondo' => '#f5f3ef', 'color_texto' => '#1c1917', 'color_precio' => '#1c1917', 'color_boton' => '#8b5e34', 'estilo_tarjeta' => 'estandar', 'estilo_header' => 'banner', 'layout_catalogo' => 'sidebar', 'columnas_desktop' => 3, 'tienda_fuente' => 'elegante'],
        ],
    ];

    /** Pares tipograficos disponibles (Google Fonts). */
    public const FUENTES = [
        'moderna'  => ['nombre' => 'Moderna',  'css' => "'Poppins', 'Montserrat', system-ui, sans-serif", 'google' => 'Poppins:wght@300;400;500;600;700', 'titulos' => "'Montserrat', 'Poppins', system-ui, sans-serif", 'google_titulos' => 'Montserrat:wght@600;700;800'],
        'limpia'   => ['nombre' => 'Limpia',   'css' => "'Inter', system-ui, sans-serif", 'google' => 'Inter:wght@400;500;600;700', 'titulos' => "'Inter', system-ui, sans-serif", 'google_titulos' => null],
        'elegante' => ['nombre' => 'Elegante', 'css' => "'Source Sans 3', system-ui, sans-serif", 'google' => 'Source+Sans+3:wght@400;500;600;700', 'titulos' => "'Playfair Display', Georgia, serif", 'google_titulos' => 'Playfair+Display:wght@600;700'],
    ];

    // ------------------------------------------------------------ paleta

    /** Paleta completa (hex) a partir de los colores del comercio. */
    public static function paleta(array $datos): array
    {
        $fondo = self::hex($datos['color_fondo'] ?? null, self::DEFAULTS['color_fondo']);
        $texto = self::hex($datos['color_texto'] ?? null, self::DEFAULTS['color_texto']);
        $precio = self::hex($datos['color_precio'] ?? null, self::DEFAULTS['color_precio']);
        $primario = self::hex($datos['color_boton'] ?? null, self::DEFAULTS['color_boton']);

        // Legibilidad: el texto tiene que contrastar con el fondo y con las tarjetas (blanco)
        $textoCorregido = false;
        if (self::contraste($texto, $fondo) < 3.0 || self::contraste($texto, '#ffffff') < 3.0) {
            $texto = self::luminancia($fondo) > 0.5 ? '#1f2937' : '#f9fafb';
            $textoCorregido = true;
        }
        if (self::contraste($precio, '#ffffff') < 2.5) {
            $precio = $texto;
        }
        $fondoClaro = self::luminancia($fondo) > 0.5;

        return [
            'primario'        => $primario,
            'primario_ink'    => self::luminancia($primario) > 0.55 ? '#1f2937' : '#ffffff',   // texto sobre el boton
            'primario_dark'   => self::mezclar($primario, '#000000', 0.18),
            'primario_soft'   => self::mezclar($primario, '#ffffff', 0.85),                   // chips, fondos suaves
            'primario_tint'   => self::mezclar($primario, '#ffffff', 0.93),                   // hover de opciones
            'primario_border' => self::mezclar($primario, '#ffffff', 0.6),
            'texto'           => $texto,
            'texto_suave'     => self::mezclar($texto, $fondoClaro ? '#ffffff' : '#000000', 0.45),
            'texto_muted'     => self::mezclar($texto, $fondoClaro ? '#ffffff' : '#000000', 0.62),
            'precio'          => $precio,
            'fondo'           => $fondo,
            'fondo_claro'     => $fondoClaro,
            'tarjeta'         => $fondoClaro ? '#ffffff' : self::mezclar($fondo, '#ffffff', 0.08),
            'borde'           => $fondoClaro ? self::mezclar($fondo, '#000000', 0.08) : self::mezclar($fondo, '#ffffff', 0.15),
            'imagen_fondo'    => $fondoClaro ? '#ffffff' : self::mezclar($fondo, '#ffffff', 0.12),
            'exito'           => '#15803d',
            'exito_soft'      => '#dcfce7',
            'alerta'          => '#b45309',
            'alerta_soft'     => '#fef3c7',
            'peligro'         => '#dc2626',
            'texto_corregido' => $textoCorregido,
        ];
    }

    /** Variables CSS para :root, listas para meter en un <style>. */
    public static function cssVariables(array $datos): string
    {
        $p = self::paleta($datos);
        $fuente = self::FUENTES[$datos['tienda_fuente'] ?? 'moderna'] ?? self::FUENTES['moderna'];
        $vars = [
            '--tp' => $p['primario'], '--tp-ink' => $p['primario_ink'], '--tp-dark' => $p['primario_dark'],
            '--tp-soft' => $p['primario_soft'], '--tp-tint' => $p['primario_tint'], '--tp-border' => $p['primario_border'],
            '--tt' => $p['texto'], '--tt-soft' => $p['texto_suave'], '--tt-muted' => $p['texto_muted'],
            '--tprecio' => $p['precio'], '--tf' => $p['fondo'], '--tcard' => $p['tarjeta'], '--tborde' => $p['borde'],
            '--timg' => $p['imagen_fondo'], '--tok' => $p['exito'], '--tok-soft' => $p['exito_soft'],
            '--twarn' => $p['alerta'], '--twarn-soft' => $p['alerta_soft'], '--tdanger' => $p['peligro'],
            '--tfont' => $fuente['css'], '--tfont-titulos' => $fuente['titulos'],
        ];
        $out = ':root{';
        foreach ($vars as $k => $v) {
            $out .= "{$k}:{$v};";
        }

        return $out . '}';
    }

    /** Links de Google Fonts para el par elegido. */
    public static function fuentesGoogle(array $datos): array
    {
        $fuente = self::FUENTES[$datos['tienda_fuente'] ?? 'moderna'] ?? self::FUENTES['moderna'];
        $familias = array_filter([$fuente['google'], $fuente['google_titulos']]);

        return array_map(fn ($f) => 'https://fonts.googleapis.com/css2?family=' . $f . '&display=swap', array_unique($familias));
    }

    /** Aviso para el panel cuando los colores elegidos no se leen bien. */
    public static function avisoContraste(array $datos): ?string
    {
        $p = self::paleta($datos);
        if ($p['texto_corregido']) {
            return 'El color de los textos no se lee sobre el fondo elegido: en la tienda se usa uno legible en su lugar.';
        }
        if (self::contraste(self::hex($datos['color_boton'] ?? null, self::DEFAULTS['color_boton']), $p['fondo']) < 1.6) {
            return 'El color de los botones es casi igual al fondo: los botones se van a notar poco.';
        }

        return null;
    }

    // ------------------------------------------------------------ presentacion

    /** "110 MATEA BANDANA CON MOÑO" con codigo 110 -> "Matea Bandana Con Moño" (sin el codigo adelante, sin gritar). */
    public static function nombreBonito(?string $nombre, $codigo = null): string
    {
        $n = trim((string) $nombre);
        if ($codigo !== null && $codigo !== '') {
            $n = preg_replace('/^' . preg_quote((string) $codigo, '/') . '\s*[-.]?\s+/u', '', $n);
        }
        if ($n === '') {
            return trim((string) $nombre);
        }
        // Si esta todo en mayusculas, se pasa a capitalizado; si ya tiene minusculas, se respeta
        if (mb_strtoupper($n, 'UTF-8') === $n && preg_match('/\p{L}/u', $n)) {
            $n = mb_convert_case(mb_strtolower($n, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
            // Unidades y siglas cortas quedan como estaban (X, KG, CC, ML...)
            $n = preg_replace_callback('/\b(x|kg|gr|g|cc|ml|lt|l|un|cm|mm)\b/iu', fn ($m) => mb_strtolower($m[1]), $n);
        }

        return $n;
    }

    // ------------------------------------------------------------ color

    public static function hex(?string $color, string $default): string
    {
        $c = trim((string) $color);
        if (preg_match('/^#?([0-9a-f]{6})$/i', $c, $m)) {
            return '#' . strtolower($m[1]);
        }
        if (preg_match('/^#?([0-9a-f]{3})$/i', $c, $m)) {
            $s = strtolower($m[1]);

            return '#' . $s[0] . $s[0] . $s[1] . $s[1] . $s[2] . $s[2];
        }

        return $default;
    }

    private static function rgb(string $hex): array
    {
        $h = ltrim($hex, '#');

        return [hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2))];
    }

    /** Mezcla $color con $con en proporcion $peso (0 = color puro, 1 = $con puro). */
    public static function mezclar(string $color, string $con, float $peso): string
    {
        [$r1, $g1, $b1] = self::rgb($color);
        [$r2, $g2, $b2] = self::rgb($con);
        $mix = fn ($a, $b) => (int) round($a * (1 - $peso) + $b * $peso);

        return sprintf('#%02x%02x%02x', $mix($r1, $r2), $mix($g1, $g2), $mix($b1, $b2));
    }

    /** Luminancia relativa (WCAG), 0 = negro, 1 = blanco. */
    public static function luminancia(string $hex): float
    {
        $lin = array_map(function ($c) {
            $c = $c / 255;

            return $c <= 0.03928 ? $c / 12.92 : pow(($c + 0.055) / 1.055, 2.4);
        }, self::rgb($hex));

        return 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
    }

    /** Relacion de contraste WCAG entre dos colores (1 a 21). */
    public static function contraste(string $a, string $b): float
    {
        $la = self::luminancia($a);
        $lb = self::luminancia($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }
}
