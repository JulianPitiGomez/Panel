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

    /**
     * Pedido web en revision: entro por la tienda y el comercio todavia no lo paso a Pedidos.
     * La ventana Pedidos de gestion lista solo 'P', asi que no lo ve hasta que lo pasan
     * (desde el modulo Pedidos Web de gestion o desde la pestana del panel).
     */
    public const WEB_REVISION = 'W';

    /** Estado con el que nace un pedido de la tienda: 'W' (en revision) o 'P' si la revision esta apagada. */
    public static function estadoInicialWeb(): string
    {
        return config('tienda.pedidos_en_revision', true) ? self::WEB_REVISION : self::PENDIENTE;
    }

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
