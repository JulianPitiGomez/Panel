<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Reglas generales de la tienda web PyMEs.
 *
 * Un solo lugar que sabe:
 *  - si un cliente tiene la tienda habilitada (piloto acotado por config/tienda.php),
 *  - en que modo esta (publica / privada) y donde se guarda,
 *  - como leer y escribir el JSON de configuracion web del cliente.
 *
 * Todo lo que se agregue al ecommerce tiene que pasar por aca en vez de
 * repetir "session('client_id') == 1" o leer config_clientes_web a mano.
 */
class TiendaService
{
    public const MODO_PUBLICA = 'publica';
    public const MODO_PRIVADA = 'privada';

    /** Clave dentro del JSON de config_clientes_web.configuracion */
    public const CLAVE_MODO = 'tienda_modo';

    /**
     * Habilitada para este cliente?
     * Mientras dure el piloto, solo los ids de TIENDA_CLIENTES_HABILITADOS.
     * Con la lista vacia, habilitada para todos.
     */
    public static function habilitada(?int $clienteId): bool
    {
        if (!$clienteId) {
            return false;
        }

        $habilitados = config('tienda.clientes_habilitados', []);

        return empty($habilitados) || in_array($clienteId, $habilitados, true);
    }

    /**
     * El comercio tiene que ver el item de tienda en su panel?
     * Si: cuando la tiene habilitada, o cuando ya se la estamos ofreciendo
     * (config tienda.ofrecer_servicio). Mientras el modulo no este terminado,
     * los que no la tienen no ven nada.
     */
    public static function visibleEnPanel(?int $clienteId): bool
    {
        return self::habilitada($clienteId) || config('tienda.ofrecer_servicio', false);
    }

    /** Modo de acceso del cliente, normalizado a uno de los modos conocidos. */
    public static function modo(int $clienteId): string
    {
        $config = self::configuracionWeb($clienteId);

        return self::normalizarModo($config[self::CLAVE_MODO] ?? null);
    }

    public static function guardarModo(int $clienteId, string $modo): void
    {
        self::guardarConfiguracionWeb($clienteId, [
            self::CLAVE_MODO => self::normalizarModo($modo),
        ]);
    }

    public static function esPrivada(?string $modo): bool
    {
        return self::normalizarModo($modo) === self::MODO_PRIVADA;
    }

    /**
     * Acepta los valores nuevos ('publica' / 'privada') y los viejos de codven
     * (0 / 1) para no romper sesiones o datos que quedaron con el formato anterior.
     */
    public static function normalizarModo($modo): string
    {
        if ($modo === self::MODO_PRIVADA || $modo === 1 || $modo === '1') {
            return self::MODO_PRIVADA;
        }

        if ($modo === self::MODO_PUBLICA || $modo === 0 || $modo === '0') {
            return self::MODO_PUBLICA;
        }

        return config('tienda.modo_default', self::MODO_PUBLICA);
    }

    /** Lista modo => etiqueta, para los selects del panel. */
    public static function modos(): array
    {
        return config('tienda.modos', []);
    }

    /** JSON de config_clientes_web.configuracion como array (vacio si no existe). */
    public static function configuracionWeb(int $clienteId): array
    {
        $registro = DB::connection('config')
            ->table('config_clientes_web')
            ->where('cliente_id', $clienteId)
            ->first();

        if (!$registro || !$registro->configuracion) {
            return [];
        }

        $datos = json_decode($registro->configuracion, true);

        return is_array($datos) ? $datos : [];
    }

    /**
     * Mezcla $cambios sobre el JSON existente y lo guarda.
     * Crea la fila si el cliente todavia no tiene configuracion web.
     */
    public static function guardarConfiguracionWeb(int $clienteId, array $cambios): array
    {
        $datos = array_merge(self::configuracionWeb($clienteId), $cambios);
        $json = json_encode($datos, JSON_UNESCAPED_UNICODE);

        $tabla = DB::connection('config')->table('config_clientes_web');

        if ($tabla->where('cliente_id', $clienteId)->exists()) {
            DB::connection('config')
                ->table('config_clientes_web')
                ->where('cliente_id', $clienteId)
                ->update(['configuracion' => $json]);
        } else {
            DB::connection('config')
                ->table('config_clientes_web')
                ->insert(['cliente_id' => $clienteId, 'configuracion' => $json]);
        }

        return $datos;
    }
}
