<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Promociones de la tienda: LEE las mismas tablas que gestion (promociones,
 * promo_grupo/promo_grupo_det, forpag_desc, clientes.excluyepromo) y aplica el
 * mismo orden que FACT.PRG::CalcularPromos() y promogrupo.prg::CalcularPrGrupo():
 *
 *   1. promos por articulo, un solo nivel de prioridad por articulo (el mas alto)
 *   2. combinadas (grupos), recorriendo el carrito de mayor a menor precio
 *   3. descuento por forma de pago sobre el total ya rebajado
 *
 * El descuento NO modifica el precio de la linea: se agrega como linea negativa
 * aparte, igual que en gestion. El calculo de la tienda es informativo: al
 * facturar, gestion recalcula (zona gris aceptada: puede diferir si se factura
 * otro dia u otra hora). Por eso lo aplicado se guarda en el satelite.
 *
 * Forma de pago: promociones.formapago es una lista de forpag.TIPO; forpag_desc
 * va por forpag.CODIGO. La tienda mapea cada medio de pago a una forpag de
 * gestion (tienda_pagos_config.forpag_*).
 */
class TiendaPromoService
{
    public const TIPO_PRECIO_ESPECIAL = 1;
    public const TIPO_LLEVA_PAGA      = 2;
    public const TIPO_DESC_UNIDAD     = 3;
    public const TIPO_PRECIO_CANTIDAD = 4;
    public const TIPO_PRECIO_CADA_N   = 5;

    private string $prefijo;
    private ?array $tasas = null;

    public function __construct(string $prefijo)
    {
        $this->prefijo = $prefijo;
    }

    private function tabla(string $nombre)
    {
        return DB::connection('client_db')->table($this->prefijo . $nombre);
    }

    private function existe(string $nombre): bool
    {
        static $cache = [];
        $k = $this->prefijo . $nombre;
        if (!isset($cache[$k])) {
            $cache[$k] = !empty(DB::connection('client_db')->select("SHOW TABLES LIKE '{$k}'"));
        }
        return $cache[$k];
    }

    // ---------------------------------------------------------------- calculo

    /**
     * Calcula las promos para un carrito.
     * @param array $carrito items con codigo, nombre, cantidad, precio, tasa
     * @param object|null $forpag forma de pago de gestion elegida: {CODIGO, TIPO, NOMBRE} (null = catalogo, sin forma)
     * @param int|null $codcli cliente de gestion (para excluyepromo)
     * @return array{lineas: array, descuento: float, excluido: bool}
     *   lineas: [['nombre','importe' (negativo),'tipo','promo_id','codart','tasa','origen' => articulo|grupo|formapago]]
     */
    public function calcular(array $carrito, ?object $forpag, ?int $codcli, ?\Carbon\Carbon $ahora = null): array
    {
        $out = ['lineas' => [], 'descuento' => 0.0, 'excluido' => false];
        if (empty($carrito) || !$this->existe('promociones')) {
            return $out;
        }

        if ($codcli && (bool) $this->tabla('clientes')->where('CODIGO', $codcli)->value('excluyepromo')) {
            $out['excluido'] = true;
            return $out;
        }

        $ahora ??= now();
        $tipoFp = $forpag ? (int) $forpag->TIPO : null;

        // Carrito agrupado por articulo (como VENTAS_DET_H1 GROUP BY CODART)
        $porArt = [];
        foreach ($carrito as $i) {
            $c = (string) $i['codigo'];
            if (!isset($porArt[$c])) {
                $porArt[$c] = ['codigo' => $i['codigo'], 'nombre' => $i['nombre'], 'cantidad' => 0.0, 'precio' => (float) $i['precio'], 'tasa' => (float) ($i['tasa'] ?? 0)];
            }
            $porArt[$c]['cantidad'] += (float) $i['cantidad'];
        }

        // 1) promos por articulo
        $promos = $this->promosVigentes(array_keys($porArt), $ahora, $tipoFp);
        $prioPorArt = [];   // codart => prioridad aplicada (para la jerarquia con los grupos)
        foreach ($porArt as $c => $a) {
            $lista = $promos[$c] ?? [];
            if (empty($lista)) {
                continue;
            }
            $tier = null;
            foreach ($lista as $p) {   // ya vienen por prioridad DESC
                if (!$this->promoAplica($p, $a['cantidad'])) {
                    continue;
                }
                if ($tier === null) {
                    $tier = (int) $p->prioridad;
                }
                if ((int) $p->prioridad !== $tier) {
                    break;   // solo el nivel mas alto que aplica
                }
                $linea = $this->lineaPromo($p, $a);
                if ($linea) {
                    $out['lineas'][] = $linea;
                    $prioPorArt[$c] = $tier;
                }
            }
        }

        // 2) combinadas
        foreach ($this->calcularGrupos($porArt, $tipoFp, $prioPorArt, $ahora, $out['lineas']) as $l) {
            $out['lineas'][] = $l;
        }

        // 3) descuento por forma de pago sobre el total resultante
        if ($forpag && $this->existe('forpag_desc')) {
            $total = 0.0;
            foreach ($porArt as $a) {
                $total += $a['precio'] * $a['cantidad'];
            }
            foreach ($out['lineas'] as $l) {
                $total += $l['importe'];
            }
            $fd = $this->tabla('forpag_desc')
                ->where('codfor', (int) $forpag->CODIGO)
                ->where('desde_importe', '<=', $total)->where('hasta_importe', '>=', $total)
                ->first();
            if ($fd && (float) $fd->porcentaje > 0) {
                // Una linea por tasa de IVA, igual que gestion
                $porTasa = [];
                foreach ($porArt as $a) {
                    $porTasa[(string) $a['tasa']] = ($porTasa[(string) $a['tasa']] ?? 0) + $a['precio'] * $a['cantidad'];
                }
                foreach ($out['lineas'] as $l) {
                    $porTasa[(string) $l['tasa']] = ($porTasa[(string) $l['tasa']] ?? 0) + $l['importe'];
                }
                foreach ($porTasa as $tasa => $monto) {
                    $imp = round(-$monto * (float) $fd->porcentaje / 100, 2);
                    if ($imp != 0) {
                        $out['lineas'][] = ['nombre' => 'Desc. ' . trim($forpag->NOMBRE), 'importe' => $imp, 'tipo' => 0, 'promo_id' => (int) $fd->id, 'codart' => 0, 'tasa' => (float) $tasa, 'origen' => 'formapago'];
                    }
                }
            }
        }

        $out['descuento'] = round(-array_sum(array_column($out['lineas'], 'importe')), 2);

        return $out;
    }

    /** Condicion de cantidad por tipo (WHERE de CalcularPromos). */
    private function promoAplica(object $p, float $cant): bool
    {
        return match ((int) $p->tipo) {
            self::TIPO_PRECIO_ESPECIAL => true,
            self::TIPO_LLEVA_PAGA      => (int) $p->cantidad_requerida > 0 && $cant >= (int) $p->cantidad_requerida,
            self::TIPO_DESC_UNIDAD     => (int) $p->descuento_a_unidad > 0 && $cant >= (int) $p->descuento_a_unidad,
            self::TIPO_PRECIO_CANTIDAD => $cant >= (int) $p->cantidad_minima && $cant <= (int) $p->cantidad_maxima,
            self::TIPO_PRECIO_CADA_N   => (int) $p->cantidad_minima > 0 && $cant >= (int) $p->cantidad_minima,
            default => false,
        };
    }

    /** Linea negativa de una promo por articulo (mismas formulas que el SELECT de CalcularPromos). */
    private function lineaPromo(object $p, array $a): ?array
    {
        $punit = $a['precio'];
        $cant = $a['cantidad'];
        $importe = 0.0;

        switch ((int) $p->tipo) {
            case self::TIPO_PRECIO_ESPECIAL:
                $importe = -($punit - (float) $p->precio_especial) * $cant;
                break;
            case self::TIPO_LLEVA_PAGA:
                $req = (int) $p->cantidad_requerida;
                $gratis = $cant - (floor($cant / $req) * (int) $p->cantidad_a_pagar + fmod($cant, $req));
                $importe = -$punit * $gratis;
                break;
            case self::TIPO_DESC_UNIDAD:
                $n = (int) $p->descuento_a_unidad;
                $pct = (float) $p->descuento_porcentual * floor($cant / $n);
                $importe = -$punit * $pct / 100;
                break;
            case self::TIPO_PRECIO_CANTIDAD:
                $importe = -($punit - (float) $p->precio_unitario) * $cant;
                break;
            case self::TIPO_PRECIO_CADA_N:
                $min = (int) $p->cantidad_minima;
                $multiplos = floor($cant / $min) * $min;
                $importe = -($punit - (float) $p->precio_unitario) * $multiplos;
                break;
        }

        $importe = round($importe, 2);
        if ($importe == 0) {
            return null;
        }

        return ['nombre' => trim($p->nompromo), 'importe' => $importe, 'tipo' => (int) $p->tipo, 'promo_id' => (int) $p->id, 'codart' => $a['codigo'], 'tasa' => $a['tasa'], 'origen' => 'articulo'];
    }

    /**
     * Port de CalcularPrGrupo(): mix (mismo_grupo) y cruzada A -> Z.
     * Modifica $lineas cuando un grupo le gana a una promo por articulo (la da de baja).
     */
    private function calcularGrupos(array $porArt, ?int $tipoFp, array $prioPorArt, \Carbon\Carbon $ahora, array &$lineas): array
    {
        if (!$this->existe('promo_grupo') || !$this->existe('promo_grupo_det')) {
            return [];
        }

        $grupos = $this->tabla('promo_grupo')
            ->whereDate('fecha_inicio', '<=', $ahora->toDateString())->whereDate('fecha_fin', '>=', $ahora->toDateString())
            ->where('hora_inicio', '<=', $ahora->format('H:i:s'))->where('hora_fin', '>=', $ahora->format('H:i:s'))
            ->when($tipoFp !== null, fn ($q) => $q->whereRaw('FIND_IN_SET(?, formapago) > 0', [(string) $tipoFp]))
            ->orderByDesc('prioridad')->orderBy('id')
            ->get();
        if ($grupos->isEmpty()) {
            return [];
        }

        // Carrito por precio DESC: [codart, punit, tasa, cantidad, beneficiadas]
        $carro = array_values($porArt);
        usort($carro, fn ($x, $y) => $y['precio'] <=> $x['precio']);
        $carro = array_map(fn ($a) => ['codigo' => (string) $a['codigo'], 'precio' => $a['precio'], 'tasa' => $a['tasa'], 'cantidad' => $a['cantidad'], 'usadas' => 0.0], $carro);

        $out = [];
        foreach ($grupos as $g) {
            $det = $this->tabla('promo_grupo_det')->where('grupo_id', $g->id)->get(['codart', 'rol']);
            $A = $det->filter(fn ($d) => strtoupper(trim((string) $d->rol)) !== 'Z')->map(fn ($d) => (string) $d->codart)->all();
            $Z = $det->filter(fn ($d) => strtoupper(trim((string) $d->rol)) === 'Z')->map(fn ($d) => (string) $d->codart)->all();
            if ((bool) $g->mismo_grupo) {
                $Z = $A;
                $cantZ = (int) $g->cant_a;
            } else {
                $cantZ = (int) $g->cant_z;
            }
            if (empty($A) || empty($Z) || (int) $g->cant_a <= 0 || $cantZ <= 0) {
                continue;
            }

            $sumA = 0.0;
            foreach ($carro as $c) {
                if (in_array($c['codigo'], $A, true)) {
                    $sumA += $c['cantidad'];
                }
            }
            if ($sumA < (int) $g->cant_a) {
                continue;
            }
            $k = (int) floor($sumA / (int) $g->cant_a);
            if ((int) $g->max_aplica > 0) {
                $k = min($k, (int) $g->max_aplica);
            }
            $aplicar = $k * $cantZ;
            if ($aplicar <= 0) {
                continue;
            }

            $benef = [];   // tasa => monto
            $bajas = [];
            $resta = $aplicar;
            foreach ($carro as $i => $c) {
                if ($resta <= 0) {
                    break;
                }
                if (!in_array($c['codigo'], $Z, true) || $c['cantidad'] - $c['usadas'] <= 0) {
                    continue;
                }
                $benefUnit = (int) $g->tipo_benef === 1 ? $c['precio'] - (float) $g->precio_unitario : $c['precio'] * (float) $g->porcentaje / 100;
                if ($benefUnit <= 0) {
                    continue;
                }
                if (isset($prioPorArt[$c['codigo']])) {
                    if ((int) $g->prioridad <= $prioPorArt[$c['codigo']]) {
                        continue;   // gana la promo por articulo
                    }
                    $bajas[$c['codigo']] = true;
                }
                $toma = min($c['cantidad'] - $c['usadas'], $resta);
                $benef[(string) $c['tasa']] = ($benef[(string) $c['tasa']] ?? 0) + $toma * $benefUnit;
                $carro[$i]['usadas'] += $toma;
                $resta -= $toma;
            }

            if (empty($benef)) {
                continue;
            }
            // El grupo le gano a la promo por articulo: se da de baja esa linea
            foreach (array_keys($bajas) as $cod) {
                $cod = (string) $cod;   // PHP convierte las claves numericas a int
                $lineas = array_values(array_filter($lineas, fn ($l) => !($l['origen'] === 'articulo' && (string) $l['codart'] === $cod)));
                unset($prioPorArt[$cod]);
            }
            foreach ($benef as $tasa => $monto) {
                $out[] = ['nombre' => mb_substr(trim($g->nompromo), 0, 60), 'importe' => round(-$monto, 2), 'tipo' => 9, 'promo_id' => (int) $g->id, 'codart' => 0, 'tasa' => (float) $tasa, 'origen' => 'grupo'];
            }
        }

        return $out;
    }

    // ------------------------------------------------------------- consultas

    /**
     * Promos vigentes por articulo, ordenadas por prioridad DESC (y por id).
     * @param int|null $tipoFp forpag.TIPO; null = sin filtrar por forma de pago
     * @return array<string, array<object>> codart => promos
     */
    public function promosVigentes(array $codigos, ?\Carbon\Carbon $ahora = null, ?int $tipoFp = null): array
    {
        if (empty($codigos) || !$this->existe('promociones')) {
            return [];
        }
        $ahora ??= now();

        $q = $this->tabla('promociones')
            ->whereIn('codart', $codigos)
            ->whereDate('fecha_inicio', '<=', $ahora->toDateString())->whereDate('fecha_fin', '>=', $ahora->toDateString())
            ->where('hora_inicio', '<=', $ahora->format('H:i:s'))->where('hora_fin', '>=', $ahora->format('H:i:s'))
            ->orderBy('codart')->orderByDesc('prioridad')->orderBy('id');
        if ($tipoFp !== null) {
            $q->whereRaw('FIND_IN_SET(?, formapago) > 0', [(string) $tipoFp]);
        }

        $out = [];
        foreach ($q->get() as $p) {
            $out[(string) $p->codart][] = $p;
        }
        return $out;
    }

    /**
     * Codigos de articulo con alguna promo vigente ahora (para la seccion Promociones).
     * Con $tiposFp, solo las que valen para todas esas formas de pago (lo que muestra el catalogo).
     */
    public function codigosConPromo(array $tiposFp = [], ?\Carbon\Carbon $ahora = null): array
    {
        if (!$this->existe('promociones')) {
            return [];
        }
        $ahora ??= now();
        $promos = $this->tabla('promociones')
            ->whereDate('fecha_inicio', '<=', $ahora->toDateString())->whereDate('fecha_fin', '>=', $ahora->toDateString())
            ->where('hora_inicio', '<=', $ahora->format('H:i:s'))->where('hora_fin', '>=', $ahora->format('H:i:s'))
            ->get(['codart', 'formapago']);

        $out = [];
        foreach ($promos as $p) {
            if (self::valeParaTodas($p, $tiposFp)) {
                $out[(string) $p->codart] = true;
            }
        }
        return array_keys($out);
    }

    /** La promo vale para TODAS las formas de pago de la tienda (tipos forpag dados)? */
    public static function valeParaTodas(object $p, array $tiposFp): bool
    {
        if (empty($tiposFp)) {
            return true;
        }
        $lista = array_map('trim', explode(',', (string) $p->formapago));
        foreach ($tiposFp as $t) {
            if (!in_array((string) $t, $lista, true)) {
                return false;
            }
        }
        return true;
    }

    /** Texto corto de la promo para el chip (generado desde los campos, como TextoPromo en gestion). */
    public static function textoPromo(object $p): string
    {
        $m = fn ($n) => '$' . number_format((float) $n, 2, ',', '.');
        return match ((int) $p->tipo) {
            self::TIPO_PRECIO_ESPECIAL => 'Oferta ' . $m($p->precio_especial),
            self::TIPO_LLEVA_PAGA      => "Llevá {$p->cantidad_requerida}, pagá {$p->cantidad_a_pagar}",
            self::TIPO_DESC_UNIDAD     => rtrim(rtrim(number_format((float) $p->descuento_porcentual, 2, ',', '.'), '0'), ',') . "% en la unidad {$p->descuento_a_unidad}",
            self::TIPO_PRECIO_CANTIDAD => ((int) $p->cantidad_maxima >= 9999 ? "Desde {$p->cantidad_minima} un. " : "De {$p->cantidad_minima} a {$p->cantidad_maxima} un. ") . $m($p->precio_unitario) . ' c/u',
            self::TIPO_PRECIO_CADA_N   => "Cada {$p->cantidad_minima} un. " . $m($p->precio_unitario) . ' c/u',
            default => trim($p->nompromo),
        };
    }
}
