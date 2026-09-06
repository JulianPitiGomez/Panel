<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Services\TiendaDominioService;
use App\Services\TiendaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Lista los dominios propios de las tiendas (F6) y, con --verificar, vuelve a
 * chequear el DNS de cada uno. Sirve para el cron de certificados con certbot
 * (nginx) y para ver de un vistazo quien apunto bien y quien no.
 */
class TiendaDominios extends Command
{
    protected $signature = 'tienda:dominios {--verificados : Solo los verificados, uno por linea (para scripts)} {--verificar : Vuelve a consultar el DNS de cada dominio}';

    protected $description = 'Lista los dominios propios de las tiendas; con --verificar los vuelve a chequear contra el DNS';

    public function handle(): int
    {
        $filas = DB::connection('config')->table('config_clientes_web')
            ->where('configuracion', 'like', '%"tienda_dominio":"%')
            ->get(['cliente_id', 'configuracion']);

        $salida = [];
        foreach ($filas as $fila) {
            $datos = json_decode($fila->configuracion, true) ?: [];
            $dominio = $datos['tienda_dominio'] ?? null;
            if (!$dominio) {
                continue;
            }
            $cliente = Cliente::find($fila->cliente_id);
            if (!$cliente || !TiendaService::habilitada($cliente->id)) {
                continue;
            }

            if ($this->option('verificar')) {
                $r = TiendaDominioService::verificar($cliente->id);
                $datos = TiendaService::configuracionWeb($cliente->id);
            }

            $verificado = !empty($datos['tienda_dominio_verificado']);
            if ($this->option('verificados')) {
                if ($verificado) {
                    $this->line($dominio);
                }
                continue;
            }
            $salida[] = [$cliente->id, $cliente->nomemp ?: $cliente->nombre, $cliente->tienda, $dominio, $verificado ? 'si' : 'no', $datos['tienda_dominio_ultimo_error'] ?? ''];
        }

        if (!$this->option('verificados')) {
            $this->table(['Cliente', 'Comercio', 'Slug', 'Dominio', 'Verificado', 'Último error'], $salida);
        }

        return self::SUCCESS;
    }
}
