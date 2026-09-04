<?php

namespace App\Services;

/**
 * Estados del pedido de gestion (pedidos_encab.estado), leidos de config/tienda.php.
 * Unico lugar que sabe que letra significa que. Los componentes preguntan aca.
 */
class TiendaEstadoPedido
{
    public const PENDIENTE = 'P';
    public const EN_ARMADO = 'A';
    public const FACTURADO = 'F';

    /** letra => etiqueta */
    public static function todos(): array
    {
        return config('tienda.estados_gestion', []);
    }

    public static function etiqueta(?string $estado): string
    {
        return self::todos()[$estado] ?? 'Desconocido';
    }

    /** El pedido fue anulado o rechazado en gestion (revierte comision, libera stock). */
    public static function esAnulado(?string $estado): bool
    {
        return in_array($estado, config('tienda.estados_anulados', []), true);
    }

    /** Estados cuyos renglones se restan del stock disponible (F2). */
    public static function comprometenStock(): array
    {
        return config('tienda.estados_comprometen_stock', []);
    }

    public static function anulados(): array
    {
        return config('tienda.estados_anulados', []);
    }
}
