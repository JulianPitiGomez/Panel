<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Stock disponible para la tienda web.
 *
 * STOCKACT es el stock fisico y no contempla lo que ya se prometio en pedidos
 * abiertos. La disponibilidad real es:
 *
 *   comprometido = suma de pedidos_det.cantidad de los pedidos en estados que
 *                  comprometen stock (config tienda.estados_comprometen_stock:
 *                  pendientes y en armado; los facturados ya descontaron de
 *                  STOCKACT desde gestion, los anulados no cuentan)
 *   disponible   = STOCKACT - comprometido
 *
 * Reglas:
 *  - UNA consulta agregada por pagina de catalogo (comprometidoDe con la lista
 *    de codigos visibles), nunca una por producto.
 *  - Se revalida al confirmar el pedido, no solo al agregar al carrito.
 *  - Si el comercio "permite vender sin stock" (config web, por defecto si),
 *    la disponibilidad se muestra como dato y no frena la compra.
 */
class TiendaStockService
{
    public const CLAVE_VENDE_SIN_STOCK = 'tienda_vende_sin_stock';

    private string $prefijo;

    public function __construct(string $prefijo)
    {
        $this->prefijo = $prefijo;
    }

    /** El comercio permite vender sin stock? Se lee del JSON de config web (session tienda_datos o array). */
    public static function vendeSinStock(?array $datosComercio): bool
    {
        return (bool) ($datosComercio[self::CLAVE_VENDE_SIN_STOCK] ?? true);
    }

    /**
     * Cantidad comprometida por articulo, para una lista de codigos.
     * @return array<int|string, float>  codart => cantidad
     */
    public function comprometidoDe(array $codigos): array
    {
        $codigos = array_values(array_unique(array_filter($codigos, fn ($c) => $c !== null && $c !== '')));
        if (empty($codigos)) {
            return [];
        }

        $estados = TiendaEstadoPedido::comprometenStock();
        if (empty($estados)) {
            return [];
        }

        return DB::connection('client_db')
            ->table($this->prefijo . 'pedidos_det as d')
            ->join($this->prefijo . 'pedidos_encab as e', 'e.id', '=', 'd.idpedido')
            ->whereIn('e.estado', $estados)
            ->whereIn('d.codart', $codigos)
            ->groupBy('d.codart')
            ->selectRaw('d.codart, COALESCE(SUM(d.cantidad), 0) AS comprometido')
            ->pluck('comprometido', 'codart')
            ->map(fn ($c) => (float) $c)
            ->all();
    }

    /**
     * Disponible por articulo: STOCKACT - comprometido.
     * @param  iterable<object> $productos objetos con CODIGO y STOCKACT
     * @return array<int|string, float> codigo => disponible
     */
    public function disponibleDe(iterable $productos): array
    {
        $lista = is_array($productos) ? $productos : iterator_to_array($productos);
        $comprometido = $this->comprometidoDe(array_map(fn ($p) => $p->CODIGO, $lista));

        $out = [];
        foreach ($lista as $p) {
            $out[$p->CODIGO] = round((float) $p->STOCKACT - ($comprometido[$p->CODIGO] ?? 0), 3);
        }

        return $out;
    }

    /** Disponible de un solo articulo (para el modal y el alta al carrito). */
    public function disponible($codigo): float
    {
        $stock = (float) DB::connection('client_db')
            ->table($this->prefijo . 'articu')
            ->where('CODIGO', $codigo)
            ->value('STOCKACT');

        $comprometido = $this->comprometidoDe([$codigo])[$codigo] ?? 0;

        return round($stock - $comprometido, 3);
    }

    /**
     * Revalidacion al confirmar: devuelve los items del carrito que superan el
     * disponible (vacio = todo ok). Solo tiene sentido si el comercio no vende sin stock.
     * @return array<int, array{codigo: mixed, nombre: string, pedido: float, disponible: float}>
     */
    public function faltantes(array $carrito): array
    {
        if (empty($carrito)) {
            return [];
        }

        $codigos = array_map(fn ($i) => $i['codigo'], $carrito);
        $stocks = DB::connection('client_db')
            ->table($this->prefijo . 'articu')
            ->whereIn('CODIGO', $codigos)
            ->pluck('STOCKACT', 'CODIGO');
        $comprometido = $this->comprometidoDe($codigos);

        $faltan = [];
        foreach ($carrito as $item) {
            $disp = round((float) ($stocks[$item['codigo']] ?? 0) - ($comprometido[$item['codigo']] ?? 0), 3);
            if ((float) $item['cantidad'] > $disp) {
                $faltan[] = ['codigo' => $item['codigo'], 'nombre' => $item['nombre'], 'pedido' => (float) $item['cantidad'], 'disponible' => max($disp, 0)];
            }
        }

        return $faltan;
    }

    /** Cantidad para mostrar: entera si no tiene decimales. */
    public static function formato(float $cantidad): string
    {
        return $cantidad == floor($cantidad) ? number_format($cantidad, 0, ',', '.') : number_format($cantidad, 2, ',', '.');
    }
}
