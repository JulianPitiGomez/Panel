<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Comprobante de venta con el MISMO formato A4 que imprime gestion (F6):
 *   - letra distinta de X: ventas.prg -> PrintFactuElec (factura electronica con CAE y QR de ARCA)
 *   - letra X:             ventas.prg -> FacturaNoFiscal (comprobante no fiscal)
 *
 * Esta clase arma los datos con las mismas consultas y reglas que el .prg
 * (subtotales por letra, alicuotas, descuentos, agrupacion de IVA, texto de
 * transparencia fiscal, QR de ARCA) y la vista tienda/factura los dibuja con
 * las mismas coordenadas en centimetros. Solo el formato A4, una copia
 * (ORIGINAL); lo que depende de la PC que imprime (2 x hoja, tamano de letra,
 * comandera) no aplica.
 *
 * Tablas de gestion que lee: ventas_encab, ventas_det, clientes, articu, ivas,
 * parametros, config, punto, cond_venta, forpag. No escribe nada.
 */
class TiendaFacturaService
{
    /** aiva del .prg: indice = coniva. */
    public const CONDICIONES_IVA = [
        1 => 'IVA Responsable Inscripto', 2 => 'IVA Responsable no Inscripto', 3 => 'IVA no Responsable',
        4 => 'IVA Sujeto Exento', 5 => 'Consumidor Final', 6 => 'Responsable Monotributo',
        7 => 'Sujeto no Categorizado', 8 => 'Proveedor del Exterior', 9 => 'Cliente del Exterior',
        10 => 'IVA Liberado Ley Nº 19.640', 11 => 'IVA Responsable Inscripto Agente de Percepción',
        12 => 'Pequeño Contribuyente Eventual', 13 => 'Monotributista Social',
        14 => 'Pequeño Contribuyente Eventual Social', 15 => 'IVA NO Alcanzado',
    ];

    /** Alto util para renglones antes de "CONTINUA EN OTRA HOJA" (y > 21 en el .prg). */
    public const Y_INICIO = 9.2;
    public const Y_TOPE = 21.0;
    public const ALTO_LINEA = 0.45;      // cm por linea de texto en la columna de descripcion
    public const CHARS_LINEA = 42;       // caracteres por linea en 6.5 cm con la letra chica

    private string $prefijo;

    public function __construct(string $prefijo)
    {
        $this->prefijo = $prefijo;
    }

    private function tabla(string $nombre)
    {
        return DB::connection('client_db')->table($this->prefijo . $nombre);
    }

    /** Fila como array con claves en minuscula (las tablas de gestion mezclan mayusculas). */
    private static function fila($obj): array
    {
        return $obj ? array_change_key_case((array) $obj, CASE_LOWER) : [];
    }

    /**
     * Todo lo que necesita la vista. Null si el comprobante no existe o no es del cliente.
     */
    public function datos(int $codcli, string $ticomp, string $letra, string $numero): ?array
    {
        $v = self::fila($this->tabla('ventas_encab as v')
            ->leftJoin($this->prefijo . 'clientes as c', 'c.CODIGO', '=', 'v.CODCLI')
            ->where('v.CODCLI', $codcli)->where('v.TICOMP', $ticomp)->where('v.LETRA', $letra)->where('v.NUMCOMP', $numero)
            ->first(['v.*', 'c.CODPOS as codpos']));
        if (!$v) {
            return null;
        }
        $nroFac = $ticomp . $letra . $numero;
        $par = self::fila($this->tabla('parametros')->first());
        $cfg = self::fila($this->tabla('config')->first());
        $puntoVta = (int) substr($numero, 0, 4);
        $punto = self::fila($this->tabla('punto')->where('punto', $puntoVta)->where('direccionf', '<>', '')->first())
            ?: self::fila($this->tabla('punto')->first());
        // "Imprime datos de la empresa" en el no fiscal es por PC en gestion; en la web no hay PC:
        // se imprimen si alguna de las PCs del comercio los imprime.
        $imprimeDatNoFiscal = $this->tabla('punto')->where('imprimedat', 1)->exists();

        $esFiscal = strtoupper($letra) !== 'X';
        $enDolares = !empty($v['endolares']);
        $indice = $enDolares ? (float) ($v['cotiza'] ?: 1) : 1.0;
        $signo = $enDolares ? 'u$s' : '$';

        // Domicilio fiscal: el del punto de venta si lo tiene, si no el de la empresa (como el .prg)
        $direEmp = trim((string) ($par['dire_emp'] ?? '')) . ',' . trim((string) ($par['loca_emp'] ?? ''));
        $domicilioFiscal = ($esFiscal && !empty(trim((string) ($punto['direccionf'] ?? '')))) ? trim($punto['direccionf']) : $direEmp;

        // Renglones: mismas columnas calculadas que el SELECT del .prg (sin agrupar)
        $det = $this->tabla('ventas_det as vd')
            ->leftJoin($this->prefijo . 'articu as a', 'a.CODIGO', '=', 'vd.CODART')
            ->leftJoin($this->prefijo . 'ivas as i', 'i.CODIGO', '=', 'vd.CODIVA')
            ->where('vd.NROFAC', $nroFac)->orderBy('vd.ID')
            ->get(['vd.CODART', 'vd.DETART', 'vd.CANTIDAD', 'vd.PUNIT', 'vd.IMPORTE', 'vd.NETO', 'vd.IVA', 'vd.CODIVA', 'vd.NETON', 'vd.DESCU', 'vd.BULTOS', 'vd.descup', 'a.codigopro', 'a.condicioncompra', 'i.tasa'])
            ->map(fn ($d) => self::fila($d));

        $usaBultos = !empty($par['usabultosventa']);
        $impCodPro = !empty($par['impcodpro']);
        $imprimeDescuImp = !empty($par['imprimedescuimp']);
        $esA = strtoupper($letra) === 'A';
        $esAM = in_array(strtoupper($letra), ['A', 'M'], true);

        $renglones = [];
        $descTotal = 0.0;
        $bultos = 0.0;
        foreach ($det as $d) {
            $cant = (float) $d['cantidad'];
            $descupor = (float) $d['descup'] > 0 ? (float) $d['descup'] : 0.0;
            $codigoPro = trim((string) ($d['codigopro'] ?? ''));
            $codiva = (int) $d['codiva'];
            $punitFiscal = $esAM && $cant != 0 ? (float) $d['neton'] / $cant : (float) $d['punit'];
            $renglones[] = [
                'codigo'       => ($impCodPro && $codigoPro !== '') ? substr($codigoPro, 0, 13) : (string) $d['codart'],
                'detalle'      => trim((string) $d['detart']) . (trim((string) ($d['condicioncompra'] ?? '')) !== '' ? ' ' . trim($d['condicioncompra']) : ''),
                'cantidad'     => $cant,
                'bultos'       => (float) $d['bultos'],
                'punit'        => ($esFiscal ? $punitFiscal : ($esA ? $punitFiscal : (float) $d['punit'])) / $indice,
                'punit_bruto'  => (float) $d['punit'],
                'descupor'     => $descupor,
                'descu'        => (float) $d['descu'],
                'desc_texto'   => $descupor > 0
                    ? (($descupor == floor($descupor)) ? (string) (int) $descupor : number_format($descupor, 2, '.', '')) . '% ' . ($imprimeDescuImp ? self::num((float) $d['descu']) : '')
                    : ' ',
                'subtot'       => ($esA ? (float) $d['neto'] : (float) $d['importe']) / $indice,
                'alicu'        => $codiva === 5 ? '21%' : ($codiva === 3 ? '0%' : '10.5%'),
                'subtotalciva' => (float) $d['importe'] / $indice,
                'es_promo'     => (float) $d['punit'] < 0,
                'lineas'       => max(1, min(5, (int) ceil(mb_strlen(trim((string) $d['detart']) . ' ' . trim((string) ($d['condicioncompra'] ?? ''))) / self::CHARS_LINEA))),
            ];
            $descTotal += (float) $d['descu'];
            $bultos += (float) $d['bultos'];
        }

        // IVA agrupado por alicuota (para letra A / M)
        $ivas = $this->tabla('ventas_det as v')
            ->join($this->prefijo . 'ivas as i', 'v.CODIVA', '=', 'i.CODIGO')
            ->where('v.NROFAC', $nroFac)->groupBy('v.CODIVA', 'i.NOMBRE')
            ->selectRaw('SUM(v.IVA) AS iva, SUM(v.NETO) AS neto, v.CODIVA AS codiva, i.NOMBRE AS nomiva')
            ->get()->map(fn ($r) => ['nombre' => trim((string) $r->nomiva), 'iva' => (float) $r->iva / $indice, 'neto' => (float) $r->neto])->all();

        $condVenta = trim((string) $this->tabla('cond_venta')->where('CODIGO', $v['condven'] ?? 0)->value('NOMBRE'));
        $formaPago = trim((string) $this->tabla('forpag')->where('CODIGO', $v['formapag'] ?? 0)->value('NOMBRE'));

        $coniva = (int) ($v['coniva'] ?? 5);
        $paginas = $this->paginar($renglones, (float) ($cfg['salto'] ?? 0));

        return [
            'fiscal'          => $esFiscal,
            'ticomp'          => $ticomp,
            'letra'           => strtoupper($letra),
            'numero'          => $numero,
            'punto_venta'     => substr($numero, 0, 4),
            'nro'             => substr($numero, -8),
            'titulo'          => $esFiscal
                ? (in_array($ticomp, ['FC', 'FR'], true) ? 'FACTURA' : ($ticomp === 'NC' ? 'NOTA DE CREDITO' : 'NOTA DE DEBITO'))
                : trim((string) ($par['textonofiscal'] ?? 'COMPROBANTE')) . ($ticomp === 'NC' ? ' NC' : ''),
            'letra_cuadro'    => $esFiscal ? strtoupper($letra) : trim((string) ($par['letranofiscal'] ?? 'X')),
            'tipfor'          => $v['tipfor'] ?? '',
            'fecha_emision'   => self::fecha($esFiscal ? ($v['fecmod'] ?? $v['fecha']) : $v['fecha']) . ' ' . trim((string) ($v['hora'] ?? '')),
            'signo'           => $signo,
            'en_dolares'      => $enDolares,
            'cotiza'          => (float) ($v['cotiza'] ?? 0),
            'empresa' => [
                'x1'          => (int) ($cfg['x1'] ?? 1),   // 1 texto, 2 logo, 3 ambos
                'imprime_dat' => $esFiscal ? true : $imprimeDatNoFiscal,
                'nombre'      => trim((string) ($par['nomb_emp'] ?? '')),
                'domicilio'   => $domicilioFiscal,
                'coniva'      => self::CONDICIONES_IVA[(int) ($par['coniva'] ?? 1)] ?? '',
                'cuit'        => trim((string) ($par['cuit_emp'] ?? '')),
                'ingb'        => trim((string) ($par['ingb_emp'] ?? '')),
                'inicio'      => self::fecha($par['inac_emp'] ?? null),
            ],
            'cliente' => [
                'cuit'      => trim((string) ($v['cuit'] ?? '')),
                'nombre'    => trim((string) ($v['nombre'] ?? '')) . ' (' . (int) $v['codcli'] . ')',
                'coniva'    => self::CONDICIONES_IVA[$coniva] ?? '',
                'direccion' => trim(trim((string) ($v['direccion'] ?? '')) . ' ' . trim((string) ($v['localidad'] ?? '')) . ' ' . trim((string) ($v['codpos'] ?? ''))),
                'cond_venta'=> $condVenta,
                'forma_pago'=> $formaPago,
            ],
            'usa_bultos'      => $usaBultos,
            'total_bultos'    => $bultos,
            'paginas'         => $paginas,
            'observa'         => $esFiscal
                ? trim((string) ($v['observa'] ?? '')) . ($descTotal > 0 ? ' Desc.' . $signo . ' ' . self::num($descTotal / $indice) . ' (IVA Incl.) ' : ' ')
                : trim(str_replace('PUNTO DE VENTA', '', (string) ($v['observa'] ?? ''))),
            'leyenda_monotributo' => $esFiscal && $esA && $coniva === 6
                ? 'El crédito fiscal discriminado en el presente comprobante, sólo podrá ser computado a efectos del Régimen de Sostenimiento e Inclusión Fiscal para Pequeños Contribuyentes de la Ley Nº 27.618'
                : null,
            'desc_total'      => $descTotal,
            'vendedor'        => (!$esFiscal || !empty($par['imprimevendedor'])) && trim((string) ($v['vendedor'] ?? '')) !== '' ? 'Vendedor: ' . trim($v['vendedor']) : '',
            'totales' => [
                'es_am'         => $esAM,
                'percep_pct'    => (float) ($v['percep'] ?? 0),
                'iibb'          => (float) ($v['iibb'] ?? 0) / $indice,
                'subtotal'      => ($esAM ? (float) $v['neto'] : (float) $v['importe'] - (float) ($v['sobretasa'] ?? 0)) / $indice,
                'otros'         => (float) ($v['sobretasa'] ?? 0) / $indice,
                'total'         => (float) $v['importe'] / $indice,
                'iva_contenido' => (float) ($v['iva'] ?? 0) / $indice,
                'ivas'          => $ivas,
            ],
            'cae'             => $esFiscal ? trim((string) ($v['cae'] ?? '')) : null,
            'cae_vto'         => $esFiscal ? self::fecha($v['fecvto'] ?? null) : null,
            'qr_url'          => $esFiscal && !empty($v['cae']) ? $this->urlQr($v, $par) : null,
            'texto_dolares'   => $enDolares ? 'El total de este comprobante expresado en moneda de curso legal, PESOS ARGENTINOS, considerando el tipo de cambio consiganado de ' . number_format((float) $v['cotiza'], 2, '.', '') . ' asciende a $ ' . number_format((float) $v['importe'], 2, '.', '') : null,
            'texto_puntos'    => !empty($par['usar_puntos'] ?? $par['usapuntos'] ?? null) && (int) $v['codcli'] > 1 ? '(Puntos de esta venta: ' . (int) ($v['puntos'] ?? 0) . ' - Acumulados: ' . (int) ($v['puntosacu'] ?? 0) . ' - Total: ' . ((int) ($v['puntos'] ?? 0) + (int) ($v['puntosacu'] ?? 0)) : null,
            'pie_fiscal'      => ($esFiscal && !empty($par['texto_pie_fiscal']) && trim($par['texto_pie_fiscal']) !== '.') ? trim($par['texto_pie_fiscal']) : null,
        ];
    }

    /** Corta los renglones en hojas como el .prg (y > 21 -> CONTINUA EN OTRA HOJA). */
    private function paginar(array $renglones, float $salto): array
    {
        $paginas = [];
        $actual = [];
        $y = self::Y_INICIO;
        $promoImpresa = false;
        foreach ($renglones as $r) {
            $altoPromo = 0;
            if ($r['es_promo'] && !$promoImpresa) {
                $r['titulo_promo'] = true;
                $promoImpresa = true;
                $altoPromo = 0.7;
            }
            $alto = $altoPromo + $r['lineas'] * self::ALTO_LINEA + 0.1 + $salto / 10;
            if ($y + $alto > self::Y_TOPE && count($actual)) {
                $paginas[] = ['renglones' => $actual, 'continua' => true, 'y_fin' => $y];
                $actual = [];
                $y = self::Y_INICIO;
            }
            $r['y'] = $y + $altoPromo;
            $actual[] = $r;
            $y += $alto;
        }
        $paginas[] = ['renglones' => $actual, 'continua' => false, 'y_fin' => $y];

        return $paginas;
    }

    /** URL del QR de ARCA, identica a GeneraQr() de UTILES.PRG. */
    private function urlQr(array $v, array $par): string
    {
        $cuitEmite = str_replace('-', '', trim((string) ($par['cuit_emp'] ?? '')));
        $importe = (string) (int) floor((float) $v['importe']);   // el .prg le saca los decimales
        $cuit = (string) ($v['cuit'] ?? '');
        if (trim(str_replace('-', '', $cuit)) === '') {
            $tipoDoc = 96;
            $doc = (string) (int) ($v['dni'] ?? 0);
        } else {
            $tipoDoc = 80;
            $doc = $cuit;
        }
        $doc = str_replace([' ', '-'], '', $doc);
        if ($doc === '0' || $doc === '') {
            $doc = '1000000';
        }
        $json = '{"ver":1,"fecha":"' . substr((string) $v['fecha'], 0, 10) . '","cuit":' . $cuitEmite
            . ',"ptoVta":' . (int) substr($v['numcomp'], 0, 4) . ',"tipoCmp":' . (int) $v['tipfor'] . ',"nroCmp":' . (int) substr($v['numcomp'], -8)
            . ',"importe":' . $importe . ',"moneda":"PES","ctz":1,"tipoDocRec":' . $tipoDoc . ',"nroDocRec":' . $doc
            . ',"tipoCodAut":"E","codAut":' . trim((string) $v['cae']) . '}';

        return 'https://www.afip.gob.ar/fe/qr/?p=' . base64_encode($json);
    }

    /** PNG del QR en data URI (chillerlan/php-qrcode con GD); null si no hay libreria. */
    public static function qrDataUri(?string $url): ?string
    {
        if (!$url || !class_exists(\chillerlan\QRCode\QRCode::class)) {
            return null;
        }
        try {
            $opciones = new \chillerlan\QRCode\QROptions([
                'outputType'  => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG,
                'imageBase64' => true,
                'scale'       => 4,
                'quietzoneSize' => 1,
                'eccLevel'    => \chillerlan\QRCode\Common\EccLevel::M,
            ]);

            return (new \chillerlan\QRCode\QRCode($opciones))->render($url);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function num(float $n): string
    {
        return number_format($n, 2, ',', '.');   // TRANSFORM(n, "@E 999,999,999.99"): @E = formato europeo, 1.856.688,20
    }

    public static function fecha($f): string
    {
        return $f && substr((string) $f, 0, 4) !== '0000' ? \Carbon\Carbon::parse($f)->format('d/m/Y') : '  /  /    ';
    }
}
