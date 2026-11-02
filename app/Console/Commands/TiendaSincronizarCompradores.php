<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Services\TiendaCompradorService;
use App\Services\TiendaInvitacionService;
use App\Services\TiendaService;
use Illuminate\Console\Command;

/**
 * Gestion manda: mantiene los compradores de la tienda al dia con los clientes de gestion.
 *
 *  - Cliente con mail valido que no es comprador -> se crea invitado y se le manda el
 *    mail para elegir contrasena (si el comercio no apago "invitar automaticamente").
 *  - Le cambiaron el mail en gestion -> se cambia en la tienda (y si nunca entro, se lo
 *    invita al mail nuevo).
 *
 * Programado en routes/console.php (cada 15 minutos). A mano:
 *   php artisan tienda:sincronizar-compradores --cliente=2409 --dry-run
 */
class TiendaSincronizarCompradores extends Command
{
    protected $signature = 'tienda:sincronizar-compradores
        {--cliente= : Solo este id de config_clientes}
        {--limite=50 : Maximo de invitaciones por cliente en cada corrida}
        {--dry-run : Muestra que haria sin escribir ni mandar nada}';

    protected $description = 'Crea e invita compradores desde los clientes de gestion con mail y actualiza los mails cambiados';

    public function handle(): int
    {
        $ids = $this->option('cliente') ? [(int) $this->option('cliente')] : config('tienda.clientes_habilitados', []);
        $clientes = empty($ids)
            ? Cliente::where('pack', 'GE3')->whereNotNull('tienda')->where('tienda', '<>', '')->get()
            : Cliente::whereIn('id', $ids)->get();
        $dry = (bool) $this->option('dry-run');
        $limite = max(1, (int) $this->option('limite'));

        foreach ($clientes as $cliente) {
            if (!$cliente->tienda || !$cliente->base || !TiendaService::habilitada($cliente->id) || !TiendaService::esquemaAlDia($cliente->id)) {
                continue;
            }
            if (TiendaService::esVitrina(TiendaService::modo($cliente->id))) {
                continue;   // vitrina: no hay cuentas
            }
            $nombre = $cliente->nomemp ?: $cliente->nombre;

            try {
                [, $prefijo] = TiendaService::conectarCliente($cliente->id);
                $srv = new TiendaCompradorService($prefijo);
                $invitar = TiendaService::invitaCompradores(TiendaService::configuracionWeb($cliente->id));
                $r = $srv->sincronizarDesdeGestion($dry, $invitar);
            } catch (\Throwable $e) {
                $this->warn("{$nombre}: no se pudo sincronizar ({$e->getMessage()})");
                continue;
            }

            foreach ($r['detalle'] as $d) {
                $this->line("{$nombre}: #{$d['codcli']} {$d['nombre']} <{$d['email']}> {$d['resultado']}");
            }

            $enviadas = 0;
            $fallidas = 0;
            if (!$dry && $invitar) {
                $inv = new TiendaInvitacionService($cliente->id, $prefijo);
                foreach ($srv->invitacionesPorEnviar($limite) as $c) {
                    $inv->enviar($c) ? $enviadas++ : $fallidas++;
                }
            }

            if ($r['creados'] || $r['mail_cambiado'] || $r['enlazados'] || $r['conflictos'] || $enviadas || $fallidas) {
                $this->info(sprintf(
                    '%s: %s %d · mail cambiado %d · enlazados %d · conflictos %d · invitaciones mandadas %d%s',
                    $nombre, $dry ? 'se invitarian' : 'nuevos', $r['creados'], $r['mail_cambiado'], $r['enlazados'], $r['conflictos'],
                    $enviadas, $fallidas ? " · fallidas {$fallidas} (se reintentan)" : ''
                ));
            }
        }

        return self::SUCCESS;
    }
}
