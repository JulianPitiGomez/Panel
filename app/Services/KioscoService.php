<?php

namespace App\Services;

/**
 * Habilitacion del kiosco de autoservicio de resto por cliente.
 * Unico lugar donde se decide; lo consultan el menu de panelresto, el login
 * del kiosco, KioscoMiddleware y KioscoRestaurant.
 */
class KioscoService
{
    public static function habilitado(?int $clienteId): bool
    {
        if (!$clienteId) {
            return false;
        }

        $habilitados = config('kiosco.clientes_habilitados', []);

        return empty($habilitados) || in_array($clienteId, $habilitados, true);
    }
}
