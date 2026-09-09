<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Services\TiendaSeguimientoService;
use App\Services\TiendaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Avisa al comprador los cambios de estado web hechos desde gestion (Fivewin, modulo
 * PEDIDOSWEB.PRG). Gestion no manda mails: deja el evento en tienda_eventos con
 * notificado = 0 y este comando lo toma y manda el mismo aviso (mail y WhatsApp) que
 * manda el panel cuando el cambio se hace desde Pedidos web.
 *
 * Reglas para no marear al comprador:
 *  - Solo el ultimo evento sin avisar de cada pedido, y solo si su estado es el estado
 *    web actual del pedido (si el comercio avanzo tres pasos seguidos, un solo mail).
 *    Los eventos anteriores del mismo pedido se marcan como avisados sin mandar nada.
 *  - Solo eventos de los ultimos --dias (default 2): lo viejo no se manda de golpe.
 *  - Sin mail del comprador, el evento se marca como avisado (no hay a quien).
 *  - Si el envio falla, el evento queda en 0 y se reintenta en la proxima corrida.
 *
 * Correr cada pocos minutos desde el cron (esta programado en routes/console.php para
 * schedule:run) o a mano: php artisan tienda:avisos-pendientes --dry-run
 */
class TiendaAvisosPendientes extends Command
{
    protected $signature = 'tienda:avisos-pendientes
        {--cliente= : Solo este id de config_clientes}
        {--dias=2 : Mirar eventos de los ultimos N dias}
        {--dry-run : Muestra que mandaria sin mandar ni marcar nada}';

    protected $description = 'Manda al comprador los avisos de estado web pendientes (cambios hechos desde gestion)';

    public function handle(): int
    {
        $ids = $this->option('cliente') ? [(int) $this->option('cliente')] : config('tienda.clientes_habilitados', []);
        $clientes = empty($ids)
            ? Cliente::where('pack', 'GE3')->whereNotNull('tienda')->where('tienda', '<>', '')->get()
            : Cliente::whereIn('id', $ids)->get();
        $dias = max(1, (int) $this->option('dias'));
        $dry = (bool) $this->option('dry-run');

        $enviados = 0;
        $omitidos = 0;
        $fallidos = 0;
        foreach ($clientes as $cliente) {
            if (!TiendaService::habilitada($cliente->id) || !$cliente->base) {
                continue;
            }
            try {
                [, $prefijo] = TiendaService::conectarCliente($cliente->id);
                $pendientes = $this->pendientes($prefijo, $dias);
            } catch (\Throwable $e) {
                $this->warn("{$cliente->nomemp}: no se pudo leer la tienda ({$e->getMessage()})");
                continue;
            }
            if ($pendientes->isEmpty()) {
                continue;
            }

            $nombreTienda = $cliente->nomemp ?: $cliente->nombre;
            $srv = new TiendaSeguimientoService($prefijo, (int) $cliente->id);
            $eventos = DB::connection('client_db')->table($prefijo . 'tienda_eventos');

            foreach ($pendientes->groupBy('idpedido') as $idpedido => $grupo) {
                $idpedido = (int) $idpedido;
                $ultimo = $grupo->sortByDesc('id')->first();
                $viejos = $grupo->where('id', '<>', $ultimo->id)->pluck('id')->all();

                // Los pasos intermedios no se avisan: el comprador recibe solo donde esta el pedido hoy
                if (!empty($viejos) && !$dry) {
                    $eventos->whereIn('id', $viejos)->update(['notificado' => 1]);
                }
                $omitidos += count($viejos);

                $estadoActual = TiendaSeguimientoService::normalizar($ultimo->estado_web);
                $estadoEvento = TiendaSeguimientoService::normalizar($ultimo->estado);
                if ($estadoEvento !== $estadoActual) {
                    // Despues de ese evento el estado volvio a cambiar (o se deshizo): ya no corresponde
                    $this->line(sprintf('%s: pedido #%d evento "%s" ya no es el estado actual (%s), se descarta', $nombreTienda, $idpedido, $ultimo->estado, $estadoActual));
                    if (!$dry) {
                        $eventos->where('id', $ultimo->id)->update(['notificado' => 1]);
                    }
                    $omitidos++;
                    continue;
                }

                if (!$srv->tieneDestinatario($idpedido)) {
                    $this->line(sprintf('%s: pedido #%d "%s" sin mail del comprador, se marca como avisado', $nombreTienda, $idpedido, $ultimo->estado));
                    if (!$dry) {
                        $eventos->where('id', $ultimo->id)->update(['notificado' => 1]);
                    }
                    $omitidos++;
                    continue;
                }

                $this->line(sprintf('%s: pedido #%d -> %s%s', $nombreTienda, $idpedido, TiendaSeguimientoService::etiqueta($estadoEvento, $ultimo->entrega_modo), $ultimo->envio_seguimiento ? " (seguimiento {$ultimo->envio_seguimiento})" : ''));
                if ($dry) {
                    $enviados++;
                    continue;
                }
                if ($srv->avisarEstado($idpedido, $estadoEvento, $ultimo->envio_seguimiento ?: null, (int) $ultimo->id)) {
                    $enviados++;
                } else {
                    $fallidos++;
                    $this->warn("  no se pudo mandar; se reintenta en la próxima corrida");
                }
            }
        }

        $this->info(($dry ? 'Mandaría' : 'Avisos mandados') . ": {$enviados} · omitidos: {$omitidos}" . ($fallidos ? " · fallidos: {$fallidos}" : ''));

        return self::SUCCESS;
    }

    /** Eventos de estado web sin avisar, hechos por el comercio, de los ultimos N dias, con el estado actual del pedido. */
    private function pendientes(string $prefijo, int $dias)
    {
        $estados = array_diff(array_keys(TiendaSeguimientoService::estados()), [TiendaSeguimientoService::RECIBIDO]);

        return DB::connection('client_db')->table($prefijo . 'tienda_eventos as ev')
            ->join($prefijo . 'tienda_pedidos as t', 't.idpedido', '=', 'ev.idpedido')
            ->where('ev.notificado', 0)
            ->where('ev.origen', 'comercio')
            ->whereIn('ev.estado', array_merge($estados, ['enviado']))
            ->where('ev.fecha', '>=', now()->subDays($dias))
            ->orderBy('ev.id')
            ->get(['ev.id', 'ev.idpedido', 'ev.estado', 'ev.fecha', 't.estado_web', 't.entrega_modo', 't.envio_seguimiento']);
    }
}
