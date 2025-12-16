<?php

namespace App\Livewire\Mozos;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\Cliente;
use App\Services\MozosService;

class Promociones extends Component
{
    public $numeroMesa;
    public $promociones = [];
    public $promoSeleccionada = null;
    public $opcionesPromo = [];
    public $renglones = [];
    public $unidades = 1;
    public $selecciones = []; // renglon => codigo
    public $comensales = 0; // Comensales de la mesa
    protected $tablePrefix;
    protected $mozosService;

    public function boot(MozosService $mozosService)
    {
        $this->mozosService = $mozosService;
    }

    public function mount($mesa)
    {
        $this->numeroMesa = $mesa;
        $this->setupClientDatabase();
        $this->cargarPromociones();

        // Obtener comensales actuales de la mesa
        $infoMesa = DB::connection('client_db')
                      ->table($this->tablePrefix . 'mesas')
                      ->select('COMENSALES as comensales')
                      ->where('NUMERO', $mesa)
                      ->first();
        $this->comensales = $infoMesa->comensales ?? 0;
    }

    private function setupClientDatabase()
    {
        $clientId = session('client_id');
        if ($clientId) {
            $cliente = Cliente::find($clientId);
            if ($cliente) {
                Config::set('database.connections.client_db.database', $cliente->base);
                DB::purge('client_db');
                $this->tablePrefix = $cliente->getTablePrefix();
            }
        }
    }

    private function cargarPromociones()
    {
        $this->setupClientDatabase();
        $this->promociones = DB::connection('client_db')
                               ->table($this->tablePrefix . 'promos')
                               ->select('*')
                               ->get();
    }

    public function abrirPromo($codigoPromo)
    {
        // Obtener detalles de la promoción
        $this->setupClientDatabase();
        $this->opcionesPromo = DB::connection('client_db')
                                  ->table($this->tablePrefix . 'promos_det')
                                  ->select('renglon', 'codart', 'nombre', 'precio')
                                  ->where('codpro', $codigoPromo)
                                  ->get();

        // Obtener renglones distintos
        $this->renglones = DB::connection('client_db')
                             ->table($this->tablePrefix . 'promos_det')
                             ->select(DB::raw('DISTINCT(renglon) as renglon'))
                             ->where('codpro', $codigoPromo)
                             ->orderBy('renglon')
                             ->pluck('renglon');

        $this->promoSeleccionada = $codigoPromo;
        $this->unidades = 1;

        // Inicializar selecciones con la primera opción de cada renglón
        foreach ($this->renglones as $renglon) {
            $primeraOpcion = $this->opcionesPromo->where('renglon', $renglon)->first();
            $this->selecciones[$renglon] = $primeraOpcion ? $primeraOpcion->codart : null;
        }
    }

    public function cancelar()
    {
        $this->promoSeleccionada = null;
        $this->opcionesPromo = [];
        $this->renglones = [];
        $this->selecciones = [];
        $this->unidades = 1;
    }

    public function agregarPromocion()
    {
        $this->setupClientDatabase();

        DB::connection('client_db')->beginTransaction();
        try {
            // Obtener próximo renglón
            $maxRenglon = DB::connection('client_db')
                            ->table($this->tablePrefix . 'detalle')
                            ->where('mesa', $this->numeroMesa)
                            ->max('renglon');

            $renglon = $maxRenglon ? $maxRenglon + 1 : 1;
            $esApertura = ($renglon == 1);

            // Si es apertura de mesa, abrir primero
            if ($esApertura) {
                $this->mozosService->abrirMesa($this->numeroMesa, $this->comensales);
                // Recalcular renglón después de abrir (puede haber agregado servicio de mesa)
                $maxRenglon = DB::connection('client_db')
                                ->table($this->tablePrefix . 'detalle')
                                ->where('mesa', $this->numeroMesa)
                                ->max('renglon');
                $renglon = $maxRenglon ? $maxRenglon + 1 : 1;
            }

            // Insertar cada producto seleccionado (iterar por renglones en orden)
            foreach ($this->renglones as $renglonPromo) {
                $codigoArticulo = $this->selecciones[$renglonPromo] ?? null;

                if (!$codigoArticulo) {
                    throw new \Exception('Debe seleccionar una opción para el renglón ' . $renglonPromo);
                }

                $articu = DB::connection('client_db')
                            ->table($this->tablePrefix . 'articu as a')
                            ->select('a.IVA as iva', 'p.precio as precio', 'i.tasa AS tasa', 'a.NOMBRE AS nombre')
                            ->leftJoin($this->tablePrefix . 'ivas as i', 'a.IVA', '=', 'i.codigo')
                            ->leftJoin($this->tablePrefix . 'promos_det as p', 'p.codart', '=', 'a.CODIGO')
                            ->where('a.CODIGO', $codigoArticulo)
                            ->where('p.codart', $codigoArticulo)
                            ->where('p.renglon', $renglonPromo)
                            ->where('p.codpro', $this->promoSeleccionada)
                            ->first();

                if (!$articu) {
                    throw new \Exception('Artículo no encontrado para renglón ' . $renglonPromo);
                }

                $tasa = $articu->tasa ?? 0;
                $total = $articu->precio * $this->unidades;
                $neto = $tasa > 0 ? $total / (1 + $tasa / 100) : $total;
                $iva = $total - $neto;

                // Truncar nombre si es muy largo (máximo 30 caracteres)
                $nombreTruncado = mb_strlen($articu->nombre) > 30
                    ? mb_substr($articu->nombre, 0, 30)
                    : $articu->nombre;

                DB::connection('client_db')
                  ->table($this->tablePrefix . 'detalle')
                  ->insert([
                      'MESA' => $this->numeroMesa,
                      'RENGLON' => $renglon,
                      'CODART' => $codigoArticulo,
                      'NOMART' => $nombreTruncado,
                      'CANTIDAD' => $this->unidades,
                      'PUNITARIO' => $articu->precio,
                      'NETO' => $neto,
                      'IVA' => $iva,
                      'TOTAL' => $total,
                      'guarnicion' => '',
                      'caracteristicas' => '',
                      'CODIVA' => $articu->iva,
                      'sabores' => ' ',
                      'estado' => 1,
                      'hora' => DB::raw('CURTIME()'),
                      'IMPRESA' => false,
                      'OBSERVA' => ''
                  ]);

                $renglon++;
            }

            // Actualizar mesa (solo si NO es apertura, porque ya se hizo en abrirMesa)
            if (!$esApertura) {
                DB::connection('client_db')
                  ->table($this->tablePrefix . 'mesas')
                  ->where('NUMERO', $this->numeroMesa)
                  ->update([
                      'RECURSO' => DB::raw("IF(LEFT(recurso,1)='N',CONCAT(LEFT(recurso,6),'0'),CONCAT(LEFT(recurso,5),'0'))"),
                      'ocupada' => true,
                      'MOZO' => session('mozo_user_id'),
                  ]);
            }

            // Actualizar puntos
            DB::connection('client_db')
              ->statement("INSERT INTO {$this->tablePrefix}actualizar (punto, mesa)
                           (SELECT ip, ? FROM {$this->tablePrefix}punto)", [$this->numeroMesa]);

            DB::connection('client_db')->commit();

            session()->flash('success', 'Promoción agregada correctamente');
            $this->redirectRoute('mozos.mesa', ['mesa' => $this->numeroMesa], navigate: true);

        } catch (\Exception $e) {
            DB::connection('client_db')->rollBack();
            \Log::error('Error al agregar promoción: ' . $e->getMessage());
            session()->flash('error', 'Error: ' . $e->getMessage());
            return;
        }
    }

    #[Layout('layouts.mozos')]
    public function render()
    {
        return view('mozos.promociones');
    }
}
