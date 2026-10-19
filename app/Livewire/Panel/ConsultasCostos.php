<?php

namespace App\Livewire\Panel;

use App\Services\PreciosPosdatadosService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Artículos > Consultas y Costos.
 * Pensado para recorrer la góndola del mayorista con el celular: se busca el artículo
 * (cámara, código a mano o nombre), se ve el stock y se carga el costo nuevo como
 * precio posdatado. Los precios se aplican después desde gestión.
 */
class ConsultasCostos extends Component
{
    // Búsqueda
    public $busqueda = '';
    public $resultados = [];
    public $sinResultados = false;

    // Artículo elegido
    public $articulo = null;
    public $pendiente = null;
    public $grupo = null;

    // Formulario del costo nuevo
    public $costo = '';
    public $usarBulto = false;
    public $costoBulto = '';
    public $uxBulto = '';
    public $porcentaje = '';
    public $porcentajeRev = '';
    public $observa = '';

    public $mensaje = '';
    public $tipoMensaje = 'success';
    public $verPendientes = false;

    private $tablePrefix;

    public function mount()
    {
        $this->setupClientDatabase();
    }

    public function buscar()
    {
        $this->setupClientDatabase();
        $this->mensaje = '';
        $this->resultados = [];
        $this->sinResultados = false;

        $texto = trim($this->busqueda);
        if ($texto === '') {
            return;
        }

        // Solo dígitos: es un código (el código de barras es el CODIGO del artículo)
        if (ctype_digit($texto)) {
            if ($this->seleccionar($texto)) {
                return;
            }
        }

        $query = DB::connection('client_db')->table("{$this->tablePrefix}articu")
            ->select('codigo', 'nombre', 'precioven', 'stockact');

        foreach (preg_split('/\s+/', $texto) as $palabra) {
            $query->where(function ($q) use ($palabra) {
                $q->where('nombre', 'like', '%' . $palabra . '%')
                  ->orWhere('codigo', 'like', $palabra . '%');
            });
        }

        $this->resultados = $query->orderBy('nombre')->limit(30)->get()
            ->map(fn ($r) => (array) $r)->toArray();
        $this->sinResultados = empty($this->resultados);
    }

    // Llamado desde el lector de la cámara
    public function escaneado($codigo)
    {
        $codigo = preg_replace('/\D/', '', (string) $codigo);
        $this->busqueda = $codigo;
        $this->resultados = [];
        $this->sinResultados = false;

        if ($codigo === '' || !$this->seleccionar($codigo)) {
            $this->avisar("No encontramos ningún artículo con el código {$codigo}.", 'error');
        }
    }

    public function seleccionar($codigo): bool
    {
        $this->setupClientDatabase();
        $p = $this->tablePrefix;

        $codigo = ltrim((string) $codigo, '0');
        if ($codigo === '' || !ctype_digit($codigo) || strlen($codigo) > 14) {
            return false;
        }

        $art = DB::connection('client_db')->selectOne("
            SELECT a.codigo, a.nombre, a.siniva, a.iva, a.precossiva,
                   a.desc1, a.desc2, a.desc3, a.desc4, a.desc5, a.impint, a.flete,
                   a.preciocos, a.porcentaje, a.precioven, a.porcentajerev, a.reventa,
                   a.uxbulto, a.stockact, a.stockmin, a.unimed, a.fecmod,
                   i.tasa
            FROM {$p}articu a
            LEFT JOIN {$p}ivas i ON i.codigo = a.iva
            WHERE a.codigo = ?
        ", [$codigo]);

        if (!$art) {
            return false;
        }

        $this->articulo = (array) $art;
        $this->resultados = [];
        $this->sinResultados = false;
        $this->pendiente = PreciosPosdatadosService::pendiente($p, $codigo);
        $this->grupo = $this->buscarGrupo($codigo);

        // Si ya hay un pendiente, se edita ese; si no, se arranca de los valores actuales
        $base = $this->pendiente ?? $this->articulo;
        $this->costo = $this->numeroInput($base['precossiva']);
        $this->porcentaje = $this->numeroInput($base['porcentaje']);
        $this->porcentajeRev = $this->numeroInput($base['porcentajerev']);
        $this->observa = $this->pendiente['observa'] ?? '';
        $this->uxBulto = (float) $art->uxbulto > 0 ? $this->numeroInput($art->uxbulto) : '';
        $this->costoBulto = '';
        $this->usarBulto = false;

        return true;
    }

    public function updatedCostoBulto()
    {
        $this->costoDesdeBulto();
    }

    public function updatedUxBulto()
    {
        $this->costoDesdeBulto();
    }

    private function costoDesdeBulto()
    {
        $bulto = $this->numero($this->costoBulto);
        $unidades = $this->numero($this->uxBulto);
        if ($bulto > 0 && $unidades > 0) {
            $this->costo = $this->numeroInput(round($bulto / $unidades, 2));
        }
    }

    public function cerrarArticulo()
    {
        $this->articulo = null;
        $this->pendiente = null;
        $this->grupo = null;
        $this->busqueda = '';
        $this->dispatch('consultas-enfocar');
    }

    /** Valores con los que se calcula: los del pendiente si existe, si no los del artículo. */
    private function baseCalculo(): ?array
    {
        if (!$this->articulo) {
            return null;
        }
        $base = $this->pendiente ?? $this->articulo;
        $tasa = (float) $this->articulo['tasa'];

        if ($this->pendiente && (int) $this->pendiente['iva'] !== (int) $this->articulo['iva']) {
            $this->setupClientDatabase();
            $tasa = (float) DB::connection('client_db')->table("{$this->tablePrefix}ivas")
                ->where('codigo', $this->pendiente['iva'])->value('tasa');
        }

        return [
            'siniva'      => (bool) $base['siniva'],
            'iva'         => (int) $base['iva'],
            'tasa'        => $tasa,
            'descuentos'  => [$base['desc1'], $base['desc2'], $base['desc3'], $base['desc4'], $base['desc5']],
            'impint'      => (float) $base['impint'],
            'flete'       => (float) $base['flete'],
        ];
    }

    private function calculo(): ?array
    {
        $base = $this->baseCalculo();
        $costo = $this->numero($this->costo);
        if (!$base || $costo <= 0) {
            return null;
        }

        return PreciosPosdatadosService::calcular(
            $costo,
            $base['siniva'],
            $base['tasa'],
            $base['descuentos'],
            $base['impint'],
            $base['flete'],
            $this->numero($this->porcentaje),
            $this->numero($this->porcentajeRev)
        ) + ['base' => $base];
    }

    public function guardar()
    {
        $this->setupClientDatabase();
        $calc = $this->calculo();

        if (!$calc || $calc['preciocos'] <= 0) {
            $this->avisar('Ingresá un costo mayor a cero.', 'error');
            return;
        }
        // Misma validación que PREPOS.PRG
        if (!in_array($calc['base']['iva'], [3, 4, 5])) {
            $this->avisar('Este artículo tiene una condición de IVA que no sirve para facturar. Corregila desde gestión antes de cambiarle el costo.', 'error');
            return;
        }

        $art = $this->articulo;
        $sinCambios = !$this->pendiente
            && round((float) $art['preciocos'], 2) == $calc['preciocos']
            && round((float) $art['precioven'], 2) == $calc['precioven']
            && round((float) $art['reventa'], 2) == $calc['reventa'];
        if ($sinCambios) {
            $this->avisar('Los precios quedan iguales a los actuales, no hace falta cargar nada.', 'error');
            return;
        }

        $base = $this->pendiente ?? $art;
        try {
            PreciosPosdatadosService::guardar($this->tablePrefix, [
                'codigo'        => $art['codigo'],
                'nombre'        => $art['nombre'],
                'siniva'        => (int) $base['siniva'],
                'iva'           => (int) $base['iva'],
                'precossiva'    => round($this->numero($this->costo), 2),
                'desc1'         => $base['desc1'],
                'desc2'         => $base['desc2'],
                'desc3'         => $base['desc3'],
                'desc4'         => $base['desc4'],
                'desc5'         => $base['desc5'],
                'impint'        => $base['impint'],
                'flete'         => $base['flete'],
                'preciocos'     => $calc['preciocos'],
                'porcentaje'    => round($this->numero($this->porcentaje), 2),
                'precioven'     => $calc['precioven'],
                'porcentajerev' => round($this->numero($this->porcentajeRev), 2),
                'reventa'       => $calc['reventa'],
                'observa'       => trim($this->observa) !== '' ? trim($this->observa) : 'Cargado desde el panel',
            ], request()->ip());
        } catch (\Throwable $e) {
            report($e);
            $this->avisar('No se pudo guardar el cambio. Probá de nuevo en un momento.', 'error');
            return;
        }

        $nombre = $art['nombre'];
        $this->cerrarArticulo();
        $this->avisar("Listo: {$nombre} queda con venta $ " . number_format($calc['precioven'], 2, ',', '.')
            . '. Se aplica cuando proceses los precios posdatados en gestión.');
    }

    public function quitarPendiente($id)
    {
        $this->setupClientDatabase();
        PreciosPosdatadosService::quitar($this->tablePrefix, (int) $id);

        if ($this->pendiente && (int) $this->pendiente['id'] === (int) $id) {
            $this->seleccionar($this->articulo['codigo']);
        }
        $this->avisar('Se quitó el cambio pendiente.');
    }

    private function buscarGrupo($codigo): ?array
    {
        $p = $this->tablePrefix;
        try {
            $codgru = DB::connection('client_db')->table("{$p}gruposdet")->where('codart', $codigo)->value('codgru');
            if (!$codgru) {
                return null;
            }
            $otros = DB::connection('client_db')->table("{$p}gruposdet as g")
                ->join("{$p}articu as a", 'a.codigo', '=', 'g.codart')
                ->where('g.codgru', $codgru)
                ->where('g.codart', '<>', $codigo)
                ->orderBy('a.nombre')
                ->pluck('a.nombre')->toArray();
        } catch (\Throwable $e) {
            return null; // el cliente no usa grupos de precios
        }

        return $otros ? ['codigo' => $codgru, 'articulos' => $otros] : null;
    }

    private function avisar($texto, $tipo = 'success')
    {
        $this->mensaje = $texto;
        $this->tipoMensaje = $tipo;
    }

    // Acepta coma o punto como separador decimal
    private function numero($valor): float
    {
        $valor = trim((string) $valor);
        if (str_contains($valor, ',')) {
            $valor = str_replace(['.', ','], ['', '.'], $valor);
        }

        return is_numeric($valor) ? (float) $valor : 0.0;
    }

    private function numeroInput($valor): string
    {
        return rtrim(rtrim(number_format((float) $valor, 2, '.', ''), '0'), '.');
    }

    public function render()
    {
        $this->setupClientDatabase();

        return view('panel.consultas-costos', [
            'calculo'    => $this->calculo(),
            'pendientes' => PreciosPosdatadosService::listar($this->tablePrefix),
        ]);
    }

    private function setupClientDatabase()
    {
        $clientId = session('client_id');
        if ($clientId) {
            $cliente = \App\Models\Cliente::find($clientId);

            if ($cliente) {
                \Illuminate\Support\Facades\Config::set('database.connections.client_db.database', $cliente->base);
                DB::purge('client_db');
                session(['client_table_prefix' => $cliente->getTablePrefix()]);
                $this->tablePrefix = $cliente->getTablePrefix();
            }
        }
    }
}
