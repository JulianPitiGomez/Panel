<?php

namespace App\Console\Commands;

use App\Services\TiendaComisionService;
use App\Services\TiendaService;
use Illuminate\Console\Command;

/**
 * Vista interna (BCN): comision devengada por cliente en un mes.
 * Hasta que exista la pantalla en el CRM, este comando es el informe.
 *
 *   php artisan tienda:comisiones-informe            (mes actual)
 *   php artisan tienda:comisiones-informe 2026-08
 */
class TiendaComisionesInforme extends Command
{
    protected $signature = 'tienda:comisiones-informe {mes? : YYYY-MM, por defecto el mes actual}';

    protected $description = 'Comisión de la tienda web devengada por cliente y por mes';

    public function handle(): int
    {
        $mes = $this->argument('mes') ?: now()->format('Y-m');

        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $this->error('El mes va como YYYY-MM.');
            return self::FAILURE;
        }

        $ids = config('tienda.clientes_habilitados', []);
        if (empty($ids)) {
            $ids = \App\Models\Cliente::where('pack', 'GE3')
                ->whereNotNull('tienda')->where('tienda', '<>', '')
                ->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $filas = [];
        $tot = ['pedidos' => 0, 'base' => 0, 'devengada' => 0, 'revertida' => 0, 'a_revisar' => 0, 'saldo' => 0];

        foreach ($ids as $clienteId) {
            if (!TiendaService::esquemaAlDia($clienteId)) {
                continue;
            }

            [$cliente, $prefijo] = TiendaService::conectarCliente($clienteId);
            $r = (new TiendaComisionService($prefijo))->informeMes($mes);
            $pct = TiendaComisionService::porcentajeCliente($clienteId);

            $filas[] = [
                $cliente->id,
                mb_substr($cliente->nomemp ?: $cliente->nombre, 0, 28),
                number_format($pct, 2) . '%',
                $r['pedidos'],
                number_format($r['base'], 2, ',', '.'),
                number_format($r['devengada'], 2, ',', '.'),
                number_format($r['revertida'], 2, ',', '.'),
                number_format($r['a_revisar'], 2, ',', '.'),
                number_format($r['saldo'], 2, ',', '.'),
            ];

            foreach ($tot as $k => $v) {
                $tot[$k] += $r[$k];
            }
        }

        $this->info("Comisiones tienda web - {$mes}");
        $this->table(
            ['id', 'cliente', '%', 'pedidos', 'base', 'devengada', 'revertida', 'a revisar', 'saldo a cobrar'],
            $filas
        );
        $this->line(sprintf(
            'Total: %d pedidos | base $%s | devengada $%s | revertida $%s | a revisar $%s | saldo a cobrar $%s',
            $tot['pedidos'],
            number_format($tot['base'], 2, ',', '.'),
            number_format($tot['devengada'], 2, ',', '.'),
            number_format($tot['revertida'], 2, ',', '.'),
            number_format($tot['a_revisar'], 2, ',', '.'),
            number_format($tot['saldo'], 2, ',', '.')
        ));

        return self::SUCCESS;
    }
}
