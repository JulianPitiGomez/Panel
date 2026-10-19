<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Carga de precios posdatados desde el panel.
 *
 * Replica el módulo PREPOS.PRG de gestión (Fivewin): el panel solo deja el cambio
 * pendiente en {prefijo}prepos; la aplicación (articu, historial campre y grupos de
 * precios) se sigue haciendo desde gestión con el botón Aplicar.
 */
class PreciosPosdatadosService
{
    public const USUARIO = 'PANEL';

    /**
     * Misma cuenta que CalcularNetoP / Formu / CalcularPrecioP de PREPOS.PRG, sin redondeo
     * (solo a 2 decimales, que es lo que guarda la tabla prepos).
     */
    public static function calcular(
        float $costo,
        bool $sinIva,
        float $tasa,
        array $descuentos,
        float $impInt,
        float $flete,
        float $porcentaje,
        float $porcentajeRev
    ): array {
        $neto = $sinIva ? $costo : $costo / (1 + $tasa / 100);
        foreach ($descuentos as $desc) {
            $neto = $neto - $neto * ((float) $desc / 100);
        }

        $costoFinal = round($neto + $impInt + $neto * ($flete / 100) + ($neto * $tasa) / 100, 2);

        return [
            'neto'       => round($neto, 2),
            'preciocos'  => $costoFinal,
            'precioven'  => round($costoFinal + ($costoFinal * $porcentaje) / 100, 2),
            'reventa'    => round($costoFinal + ($costoFinal * $porcentajeRev) / 100, 2),
        ];
    }

    /** Misma estructura que CrearTabla() de PREPOS.PRG (idempotente). */
    public static function crearTabla(string $prefijo): void
    {
        DB::connection('client_db')->statement("CREATE TABLE IF NOT EXISTS {$prefijo}prepos (
            id INT(8) NOT NULL AUTO_INCREMENT,
            codigo BIGINT(14) NOT NULL,
            nombre VARCHAR(150) NOT NULL DEFAULT '',
            siniva TINYINT(1) DEFAULT 0,
            iva INT(2) DEFAULT 0,
            precossiva DECIMAL(12,2) DEFAULT 0,
            desc1 DECIMAL(5,2) DEFAULT 0,
            desc2 DECIMAL(5,2) DEFAULT 0,
            desc3 DECIMAL(5,2) DEFAULT 0,
            desc4 DECIMAL(5,2) DEFAULT 0,
            desc5 DECIMAL(5,2) DEFAULT 0,
            impint DECIMAL(7,2) DEFAULT 0,
            flete DECIMAL(5,2) DEFAULT 0,
            preciocos DECIMAL(12,2) DEFAULT 0,
            porcentaje DECIMAL(10,2) DEFAULT 0,
            precioven DECIMAL(12,2) DEFAULT 0,
            porcentajerev DECIMAL(10,2) DEFAULT 0,
            reventa DECIMAL(12,2) DEFAULT 0,
            fecprev DATE,
            fecalta DATE,
            horalta TIME,
            usuario VARCHAR(15) DEFAULT '',
            ip VARCHAR(20) DEFAULT '',
            observa VARCHAR(255) DEFAULT '',
            PRIMARY KEY (id),
            UNIQUE KEY codigo_unq (codigo)) ENGINE=INNODB DEFAULT CHARSET=utf8");
    }

    public static function existeTabla(string $prefijo): bool
    {
        return Schema::connection('client_db')->hasTable("{$prefijo}prepos");
    }

    public static function pendiente(string $prefijo, $codigo): ?array
    {
        if (!self::existeTabla($prefijo)) {
            return null;
        }
        $fila = DB::connection('client_db')->table("{$prefijo}prepos")->where('codigo', $codigo)->first();

        return $fila ? (array) $fila : null;
    }

    /** Un pendiente por artículo: pisa el que hubiera, igual que las cargas masivas de PREPOS. */
    public static function guardar(string $prefijo, array $fila, ?string $ip): void
    {
        self::crearTabla($prefijo);

        $fila['nombre']  = mb_substr(str_replace("'", '`', (string) $fila['nombre']), 0, 150);
        $fila['observa'] = mb_substr((string) ($fila['observa'] ?? ''), 0, 255);
        $fila['fecprev'] = now()->toDateString();
        $fila['fecalta'] = now()->toDateString();
        $fila['horalta'] = now()->format('H:i:s');
        $fila['usuario'] = self::USUARIO;
        $fila['ip']      = mb_substr((string) $ip, 0, 20);

        $campos = array_keys($fila);
        $actualizar = array_values(array_diff($campos, ['codigo']));

        DB::connection('client_db')->table("{$prefijo}prepos")->upsert([$fila], ['codigo'], $actualizar);
    }

    public static function quitar(string $prefijo, int $id): void
    {
        DB::connection('client_db')->table("{$prefijo}prepos")->where('id', $id)->delete();
    }

    public static function listar(string $prefijo): array
    {
        if (!self::existeTabla($prefijo)) {
            return [];
        }

        return array_map(fn ($r) => (array) $r, DB::connection('client_db')->select("
            SELECT p.id, p.codigo, p.nombre, p.preciocos, p.precioven, p.reventa,
                   p.usuario, p.fecalta, p.horalta, p.observa,
                   a.preciocos AS cos_actual, a.precioven AS ven_actual
            FROM {$prefijo}prepos p
            LEFT JOIN {$prefijo}articu a ON a.codigo = p.codigo
            ORDER BY p.fecalta DESC, p.horalta DESC, p.nombre
        "));
    }
}
