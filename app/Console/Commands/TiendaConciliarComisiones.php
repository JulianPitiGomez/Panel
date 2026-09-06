<?php

namespace App\Console\Commands;

use App\Services\TiendaComisionService;
use App\Services\TiendaService;
use Illuminate\Console\Command;

/**
 * Conciliacion de comisiones contra pedidos_encab.
 *
 * Fivewin anula o borra pedidos escribiendo directo en MySQL, asi que Laravel
 * no se entera por ningun evento. Este comando corre periodicamente (cron) y
 * revierte la comision de los anulados / rechazados y aparta para revision la
 * de los pedidos que ya no existen.
 *
 *   php artisan tienda:conciliar-comisiones        (todos los clientes habilitados)
 *   php artisan tienda:conciliar-comisiones 1      (un cliente)
 */
class TiendaConciliarComisiones extends Command
{
    protected $signature = 'tienda:conciliar-comisiones {cliente_id? : id en config_clientes; sin esto, todos los habilitados}';

    protected $description = 'Revierte la comisión de los pedidos anulados en gestión y aparta para revisión los que ya no existen';

    public function handle(): int
    {
        $ids = $this->argument('cliente_id')
            ? [(int) $this->argument('cliente_id')]
            : $this->clientesHabilitados();

        $totales = ['revisadas' => 0, 'revertidas' => 0, 'a_revisar' => 0];

        foreach ($ids as $clienteId) {
            if (!TiendaService::habilitada($clienteId) || !TiendaService::esquemaAlDia($clienteId)) {
                $this->warn("Cliente {$clienteId}: sin tienda habilitada o sin esquema, se saltea.");
                continue;
            }

            [$cliente, $prefijo] = TiendaService::conectarCliente($clienteId);
            $r = (new TiendaComisionService($prefijo))->conciliar((int) $cliente->id);

            $this->line(sprintf(
                'Cliente %d - %s: %d devengadas revisadas, %d revertidas, %d a revisar',
                $cliente->id, $cliente->nomemp ?: $cliente->nombre, $r['revisadas'], $r['revertidas'], $r['a_revisar']
            ));

            foreach ($r['detalle'] as $d) {
                $this->line(sprintf('   pedido %d  $%s  -> %s%s', $d['idpedido'], number_format($d['importe'], 2, ',', '.'), $d['resultado'], isset($d['estado']) ? " (estado {$d['estado']})" : ''));
            }

            foreach ($totales as $k => $v) {
                $totales[$k] += $r[$k];
            }
        }

        $this->info(sprintf('Total: %d revisadas, %d revertidas, %d a revisar', $totales['revisadas'], $totales['revertidas'], $totales['a_revisar']));

        return self::SUCCESS;
    }

    /** Ids de clientes GE3 con tienda: los del piloto, o todos los que tienen slug si la lista esta vacia. */
    private function clientesHabilitados(): array
    {
        $lista = config('tienda.clientes_habilitados', []);
        if (!empty($lista)) {
            return $lista;
        }

        return \App\Models\Cliente::where('pack', 'GE3')
            ->whereNotNull('tienda')->where('tienda', '<>', '')
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
