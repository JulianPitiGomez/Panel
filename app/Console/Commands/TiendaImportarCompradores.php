<?php

namespace App\Console\Commands;

use App\Services\TiendaCompradorService;
use App\Services\TiendaService;
use Illuminate\Console\Command;

/**
 * Rutina de habilitacion de la tienda: da de alta como compradores a los
 * clientes de gestion que tienen mail.
 *
 *   php artisan tienda:importar-compradores 1 --dry-run
 *   php artisan tienda:importar-compradores 1 --password=password   (pruebas)
 *   php artisan tienda:importar-compradores 1                       (contrasena aleatoria por comprador, se lista)
 *
 * La misma logica (TiendaCompradorService::importarDesdeClientes) es la que va a
 * usar la opcion del panel cuando se prepare un cliente para produccion.
 */
class TiendaImportarCompradores extends Command
{
    protected $signature = 'tienda:importar-compradores
                            {cliente_id : id en config_clientes}
                            {--password= : contrasena inicial para todos (solo pruebas); sin esto se genera una por comprador}
                            {--dry-run : mostrar que haria sin escribir nada}';

    protected $description = 'Crea compradores de la tienda web a partir de los clientes con mail de la base del cliente';

    public function handle(): int
    {
        $clienteId = (int) $this->argument('cliente_id');

        if (!TiendaService::habilitada($clienteId)) {
            $this->error("El cliente {$clienteId} no tiene la tienda habilitada (TIENDA_CLIENTES_HABILITADOS).");
            return self::FAILURE;
        }

        if (!TiendaService::esquemaAlDia($clienteId)) {
            $this->error("El cliente {$clienteId} no tiene el esquema de tienda aplicado (docs/tienda/esquema-tienda-v" . TiendaService::ESQUEMA_VERSION . ".sql).");
            return self::FAILURE;
        }

        [$cliente, $prefijo] = TiendaService::conectarCliente($clienteId);
        $dryRun = (bool) $this->option('dry-run');
        $password = $this->option('password') ?: null;

        $this->info(($dryRun ? '[SIMULACION] ' : '') . "Cliente {$cliente->id} - " . ($cliente->nomemp ?: $cliente->nombre) . " (base {$cliente->base}, prefijo {$prefijo})");

        $resumen = (new TiendaCompradorService($prefijo))->importarDesdeClientes($password, $dryRun);

        $filas = array_map(fn ($d) => [
            $d['codcli'],
            mb_substr($d['nombre'], 0, 30),
            $d['email'],
            $d['resultado'] . (isset($d['gano_codcli']) ? " (gano codcli {$d['gano_codcli']})" : ''),
            $d['password'] ?? '',
        ], $resumen['detalle']);

        $this->table(['codcli', 'nombre', 'mail', 'resultado', 'contrasena'], $filas);

        $this->newLine();
        $this->line(sprintf(
            'Creados: %d | Ya existian: %d | Mail invalido: %d | Mail repetido: %d',
            $resumen['creados'], $resumen['ya_existian'], $resumen['mail_invalido'], $resumen['mail_repetido']
        ));

        if ($password && !$dryRun && $resumen['creados'] > 0) {
            $this->warn("Todos los compradores creados tienen la contrasena '{$password}'. Solo para pruebas.");
        }

        return self::SUCCESS;
    }
}
