<?php

namespace App\Livewire\Panelresto;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class ConfigurarDelivery extends Component
{
    public $ubicacion = '';
    public $kmentrega = 5;
    public $porzona = false;
    public $zonas = [];

    private $tablePrefix;

    public function mount()
    {
        $this->setupClientDatabase();
        $this->cargarConfiguracion();
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

    private function cargarConfiguracion()
    {
        if (!$this->tablePrefix) {
            return;
        }

        // Cargar configuración del comercio
        $comercio = DB::connection('client_db')->table($this->tablePrefix . 'comercio_web')
            ->select('*')
            ->first();

        if ($comercio) {
            $this->ubicacion = $comercio->ubicacion ?? '-34.60024897372449, -58.38179087851561';
            $this->kmentrega = $comercio->kmentrega ?? 5;
            $this->porzona = $comercio->porzona == 1;
        } else {
            $this->ubicacion = '-34.60024897372449, -58.38179087851561';
        }

        // Cargar zonas
        $this->zonas = DB::connection('client_db')->table($this->tablePrefix . 'zonas')
            ->select('*')
            ->get()
            ->toArray();
    }

    public function guardar($datosZonas)
    {
        $this->setupClientDatabase();

        if (!$this->tablePrefix) {
            session()->flash('error', 'No se pudo conectar a la base de datos del cliente');
            return;
        }

        DB::beginTransaction();
        try {
            // Actualizar configuración del comercio
            DB::connection('client_db')->table($this->tablePrefix . 'comercio_web')
                ->update([
                    'kmentrega' => $this->kmentrega,
                    'ubicacion' => $this->ubicacion,
                    'porzona' => $this->porzona ? 1 : 0
                ]);

            // Obtener zonas actuales
            $zonasActuales = DB::connection('client_db')->table($this->tablePrefix . 'zonas')
                ->select('id')
                ->get()
                ->pluck('id')
                ->toArray();

            // IDs de zonas que vienen del formulario
            $zonasFormulario = [];

            // Procesar cada zona del formulario
            foreach ($datosZonas as $zona) {
                if (empty($zona['poligono'])) {
                    continue;
                }

                $habilitada = isset($zona['habilitada']) && $zona['habilitada'] ? 1 : 0;

                if ($zona['id'] == 0) {
                    // Nueva zona
                    DB::connection('client_db')->table($this->tablePrefix . 'zonas')->insert([
                        'poligono' => $zona['poligono'],
                        'precio' => $zona['precio'],
                        'nombre' => $zona['nombre'],
                        'habilitada' => $habilitada
                    ]);
                } else {
                    // Actualizar zona existente
                    DB::connection('client_db')->table($this->tablePrefix . 'zonas')
                        ->where('id', $zona['id'])
                        ->update([
                            'poligono' => $zona['poligono'],
                            'precio' => $zona['precio'],
                            'nombre' => $zona['nombre'],
                            'habilitada' => $habilitada
                        ]);
                    $zonasFormulario[] = $zona['id'];
                }
            }

            // Eliminar zonas que ya no están en el formulario
            $zonasAEliminar = array_diff($zonasActuales, $zonasFormulario);
            if (!empty($zonasAEliminar)) {
                DB::connection('client_db')->table($this->tablePrefix . 'zonas')
                    ->whereIn('id', $zonasAEliminar)
                    ->delete();
            }

            DB::commit();

            $this->cargarConfiguracion();
            session()->flash('success', 'Configuración guardada correctamente');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error al guardar la configuración: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('panelresto.configurar-delivery');
    }
}
