<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Precios e IVA de la tienda web.
 *
 * PRECIOVEN (y las listas) ya son PRECIO FINAL con IVA incluido: lo que se
 * muestra es lo que se cobra. La ley obliga a indicar cuanto de ese precio es
 * IVA, y eso se calcula:
 *
 *   tasa         <- articu.IVA -> ivas.codigo -> ivas.tasa
 *   neto         =  precio_final / (1 + tasa/100)
 *   iva_incluido =  precio_final - neto
 *
 * Reglas (ver plan, "Precios, IVA y stock"):
 *  - Se calcula y redondea POR ITEM, no sobre el total: si no, no coincide con
 *    la factura que despues emite gestion.
 *  - Un carrito puede mezclar tasas: el resumen discrimina por tasa.
 *  - Exentos y tasa cero existen: IVA 0 y no se muestra el renglon. Nunca se
 *    asume 21% por defecto ni se divide por cero.
 */
class TiendaPrecioService
{
    private string $prefijo;
    private ?array $tasas = null;

    public function __construct(string $prefijo)
    {
        $this->prefijo = $prefijo;
    }

    /** codigo de ivas => tasa (%), leido una vez por request. */
    public function tasas(): array
    {
        if ($this->tasas === null) {
            $this->tasas = DB::connection('client_db')
                ->table($this->prefijo . 'ivas')
                ->pluck('tasa', 'codigo')
                ->map(fn ($t) => (float) $t)
                ->all();
        }

        return $this->tasas;
    }

    /** Tasa de un articulo por su articu.IVA (0 si no resuelve). */
    public function tasaDe($codigoIva): float
    {
        return $this->tasas()[(int) $codigoIva] ?? 0.0;
    }

    /**
     * Desglosa un precio final unitario.
     * @return array{neto: float, iva: float, tasa: float}
     */
    public static function desglosar(float $precioFinal, ?float $tasa): array
    {
        $tasa = (float) ($tasa ?? 0);

        if ($tasa <= 0 || $precioFinal <= 0) {
            return ['neto' => round($precioFinal, 2), 'iva' => 0.0, 'tasa' => 0.0];
        }

        $neto = round($precioFinal / (1 + $tasa / 100), 2);

        return ['neto' => $neto, 'iva' => round($precioFinal - $neto, 2), 'tasa' => $tasa];
    }

    /**
     * IVA incluido en una linea del carrito (precio unitario x cantidad), redondeado por item.
     */
    public static function ivaLinea(float $precio, float $cantidad, ?float $tasa): float
    {
        return round(self::desglosar($precio, $tasa)['iva'] * $cantidad, 2);
    }

    /**
     * Resumen del carrito discriminado por tasa.
     * Cada item necesita 'precio', 'cantidad' y 'tasa' (los carritos viejos sin
     * 'tasa' cuentan como tasa 0).
     *
     * @return array{total: float, neto: float, iva: float, por_tasa: array<string, array{tasa: float, neto: float, iva: float}>}
     */
    public static function resumenIva(array $carrito): array
    {
        $porTasa = [];
        $total = 0.0;
        $iva = 0.0;

        foreach ($carrito as $item) {
            $tasa = (float) ($item['tasa'] ?? 0);
            $precio = (float) $item['precio'];
            $cantidad = (float) $item['cantidad'];
            $subtotal = round($precio * $cantidad, 2);
            $ivaItem = self::ivaLinea($precio, $cantidad, $tasa);

            $total += $subtotal;
            $iva += $ivaItem;

            if ($tasa > 0) {
                $clave = number_format($tasa, 2, '.', '');
                $porTasa[$clave] ??= ['tasa' => $tasa, 'neto' => 0.0, 'iva' => 0.0];
                $porTasa[$clave]['neto'] += $subtotal - $ivaItem;
                $porTasa[$clave]['iva'] += $ivaItem;
            }
        }

        krsort($porTasa, SORT_NUMERIC);

        return [
            'total'    => round($total, 2),
            'neto'     => round($total - $iva, 2),
            'iva'      => round($iva, 2),
            'por_tasa' => array_map(fn ($t) => ['tasa' => $t['tasa'], 'neto' => round($t['neto'], 2), 'iva' => round($t['iva'], 2)], $porTasa),
        ];
    }

    /** "21%" / "10,5%" para mostrar. */
    public static function etiquetaTasa(float $tasa): string
    {
        return rtrim(rtrim(number_format($tasa, 2, ',', '.'), '0'), ',') . '%';
    }
}
