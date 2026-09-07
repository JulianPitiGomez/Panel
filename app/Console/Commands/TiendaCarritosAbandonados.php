<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Services\TiendaCarritoService;
use App\Services\TiendaService;
use App\Services\TiendaWhatsappService;
use Illuminate\Console\Command;

/**
 * Recupera carritos abandonados (F7): para cada tienda con WhatsApp activo y el aviso
 * de carrito prendido, manda la plantilla a los compradores que dejaron el carrito
 * quieto mas de las horas configuradas. Una vez por carrito. Correr desde el cron,
 * por ejemplo una vez por dia a media manana.
 */
class TiendaCarritosAbandonados extends Command
{
    protected $signature = 'tienda:carritos-abandonados {--cliente= : Solo este id de config_clientes} {--dry-run : Muestra sin mandar}';

    protected $description = 'Manda por WhatsApp el aviso de carrito abandonado a los compradores que lo dejaron quieto';

    public function handle(): int
    {
        $ids = $this->option('cliente') ? [(int) $this->option('cliente')] : config('tienda.clientes_habilitados', []);
        $clientes = empty($ids)
            ? Cliente::where('pack', 'GE3')->whereNotNull('tienda')->where('tienda', '<>', '')->get()
            : Cliente::whereIn('id', $ids)->get();

        $total = 0;
        foreach ($clientes as $cliente) {
            if (!TiendaService::habilitada($cliente->id) || !$cliente->base) {
                continue;
            }
            [, $prefijo] = TiendaService::conectarCliente($cliente->id);
            $wa = new TiendaWhatsappService($prefijo);
            $cfg = $wa->config();
            if (!$cfg || empty($cfg['avisa_carrito']) || empty($cfg['plantilla_carrito'])) {
                continue;
            }
            $carritos = new TiendaCarritoService($prefijo);
            $nombreTienda = $cliente->nomemp ?: $cliente->nombre;
            $link = route('tienda.carrito', $cliente->tienda);
            foreach ($carritos->abandonados((int) ($cfg['carrito_horas'] ?? 6)) as $c) {
                $tel = $c->telefono ?: null;
                $this->line(sprintf('%s: carrito #%d de %s (%d productos, $%s) -> %s', $nombreTienda, $c->id, $c->nombre, $c->cantidad, number_format($c->total, 2, ',', '.'), $tel ?: 'sin teléfono'));
                if ($this->option('dry-run')) {
                    continue;
                }
                if (!$tel) {
                    $carritos->marcarAvisado((int) $c->id, 'sin telefono');
                    continue;
                }
                $r = $wa->avisarCarritoAbandonado($tel, (string) $c->nombre, (int) $c->cantidad, $nombreTienda, $link);
                $carritos->marcarAvisado((int) $c->id, $r['ok'] ? 'enviado' : ('error: ' . ($r['error'] ?? '')));
                $total += $r['ok'] ? 1 : 0;
            }
        }
        $this->info("Avisos mandados: {$total}");

        return self::SUCCESS;
    }
}
