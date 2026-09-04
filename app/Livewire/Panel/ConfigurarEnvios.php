<?php

namespace App\Livewire\Panel;

use App\Services\TiendaEnvioService;
use App\Services\TiendaService;
use Livewire\Component;

/**
 * Envios de la tienda web: modalidades, ubicacion del local y zonas de reparto.
 * Equivalente PyME de Panelresto/ConfigurarDelivery, con tres tipos de zona
 * (radio, codigos postales, poligono) y tarifa / minimo bonificado / demora por zona.
 */
class ConfigurarEnvios extends Component
{
    public $config = [];
    public $zonas = [];
    public $mensaje = '';
    public $tipoMensaje = '';

    private $tablePrefix;

    public function mount()
    {
        $this->conectar();
        $this->cargar();
    }

    private function conectar()
    {
        [, $this->tablePrefix] = TiendaService::conectarCliente((int) session('client_id'));
    }

    private function cargar()
    {
        $srv = new TiendaEnvioService($this->tablePrefix);
        $cfg = $srv->config();
        unset($cfg['id']);
        foreach (['retiro_local', 'flota_propia', 'correo_activo', 'a_convenir'] as $b) {
            $cfg[$b] = (bool) $cfg[$b];   // wire:model de checkbox espera booleanos
        }
        $this->config = $cfg;

        $this->zonas = array_map(fn ($z) => [
            'id'              => $z->id,
            'nombre'          => $z->nombre,
            'tipo'            => $z->tipo,
            'poligono'        => $z->poligono_puntos,
            'cp_lista'        => $z->cp_lista,
            'radio_km'        => $z->radio_km,
            'tarifa'          => $z->tarifa,
            'tarifa_por_km'   => $z->tarifa_por_km,
            'minimo_gratis'   => $z->minimo_gratis,
            'demora_estimada' => $z->demora_estimada,
            'activa'          => (bool) $z->activa,
        ], $srv->zonas());
    }

    /** Lo llama el mapa cuando se mueve el marcador o se elige una direccion. */
    public function actualizarOrigen($lat, $lng, $direccion = null)
    {
        $this->config['origen_lat'] = $lat;
        $this->config['origen_lng'] = $lng;
        if ($direccion) {
            $this->config['origen_direccion'] = mb_substr($direccion, 0, 160);
        }
    }

    /** Guarda config + zonas. $zonas viene armado por el JS de la pantalla (con los poligonos). */
    public function guardar(array $zonas)
    {
        $this->conectar();

        try {
            $srv = new TiendaEnvioService($this->tablePrefix);
            $srv->guardarConfig($this->config);
            $srv->guardarZonas($zonas);

            $this->cargar();
            $this->mensaje = 'Configuración de envíos guardada.';
            $this->tipoMensaje = 'success';
            $this->dispatch('envios-guardados');
        } catch (\Throwable $e) {
            $this->mensaje = 'No se pudo guardar: ' . $e->getMessage();
            $this->tipoMensaje = 'error';
        }
    }

    public function render()
    {
        return view('panel.configurar-envios', [
            'googleMapsApiKey' => config('app.google_maps_api_key'),
        ]);
    }
}
