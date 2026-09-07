<?php

namespace App\Livewire\Panel;

use App\Services\TiendaAgendaService;
use App\Services\TiendaService;
use Livewire\Component;

/**
 * Pestana Agenda de Catalogo: cuando entrega el comercio (anticipacion, franjas,
 * cupos, excepciones) y el cierre manual de la tienda.
 */
class ConfigurarAgenda extends Component
{
    public $config = [];
    public $franjas = [];
    public $excepciones = [];
    public $dias = TiendaAgendaService::DIAS;
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
        $srv = new TiendaAgendaService($this->tablePrefix);
        $cfg = $srv->config();
        unset($cfg['id']);
        foreach (['activa', 'abierta', 'recibe_cerrada', 'muestra_cerrada', 'permite_lo_antes_posible'] as $b) {
            $cfg[$b] = (bool) $cfg[$b];
        }
        $this->config = $cfg;

        $this->franjas = array_map(fn ($f) => [
            'id' => $f->id, 'dia_semana' => $f->dia_semana, 'hora_desde' => $f->hora_desde, 'hora_hasta' => $f->hora_hasta,
            'cupo_franja' => $f->cupo_franja, 'cupo_unidades' => $f->cupo_unidades ?? null, 'vale_envio' => (bool) $f->vale_envio, 'vale_retiro' => (bool) $f->vale_retiro, 'activa' => (bool) $f->activa,
        ], $srv->franjas());

        $this->excepciones = array_map(fn ($e) => [
            'fecha' => $e->fecha, 'cerrado' => (bool) $e->cerrado, 'motivo' => $e->motivo,
        ], $srv->excepciones(true));
    }

    public function agregarFranja($dia = 1)
    {
        $this->franjas[] = ['id' => 0, 'dia_semana' => (int) $dia, 'hora_desde' => '09:00', 'hora_hasta' => '13:00', 'cupo_franja' => null, 'cupo_unidades' => null, 'vale_envio' => true, 'vale_retiro' => true, 'activa' => true];
    }

    public function copiarFranjasA($diaOrigen, $diaDestino)
    {
        $origen = array_values(array_filter($this->franjas, fn ($f) => (int) $f['dia_semana'] === (int) $diaOrigen));
        $this->franjas = array_values(array_filter($this->franjas, fn ($f) => (int) $f['dia_semana'] !== (int) $diaDestino));
        foreach ($origen as $f) {
            $f['id'] = 0;
            $f['dia_semana'] = (int) $diaDestino;
            $this->franjas[] = $f;
        }
    }

    public function quitarFranja($i)
    {
        unset($this->franjas[$i]);
        $this->franjas = array_values($this->franjas);
    }

    public function agregarExcepcion()
    {
        $this->excepciones[] = ['fecha' => today()->addDay()->toDateString(), 'cerrado' => true, 'motivo' => ''];
    }

    public function quitarExcepcion($i)
    {
        unset($this->excepciones[$i]);
        $this->excepciones = array_values($this->excepciones);
    }

    /** Abrir / cerrar la tienda de un toque, sin pasar por Guardar. */
    public function alternarAbierta()
    {
        $this->conectar();
        $this->config['abierta'] = !$this->config['abierta'];
        (new TiendaAgendaService($this->tablePrefix))->guardarConfig([
            'abierta'         => $this->config['abierta'],
            'recibe_cerrada'  => $this->config['recibe_cerrada'],
            'muestra_cerrada' => $this->config['muestra_cerrada'],
        ]);
        $this->mensaje = $this->config['abierta'] ? 'Tienda abierta.' : 'Tienda cerrada. ' . ($this->config['recibe_cerrada'] ? 'Sigue recibiendo pedidos con aviso.' : 'No toma pedidos hasta que la abras.');
        $this->tipoMensaje = 'success';
    }

    /** "Cerrada igual recibe" y "mostrar aviso" se graban al tildarlos: no dependen del boton Guardar. */
    public function updatedConfig($valor, $clave)
    {
        if (!in_array($clave, ['recibe_cerrada', 'muestra_cerrada'], true)) {
            return;
        }
        $this->conectar();
        (new TiendaAgendaService($this->tablePrefix))->guardarConfig([$clave => (bool) $valor]);
        if (!$this->config['abierta']) {
            $this->mensaje = 'Tienda cerrada. ' . ($this->config['recibe_cerrada'] ? 'Sigue recibiendo pedidos con aviso.' : 'No toma pedidos hasta que la abras.');
            $this->tipoMensaje = 'success';
        }
    }

    public function guardar()
    {
        $this->conectar();
        try {
            $srv = new TiendaAgendaService($this->tablePrefix);
            $srv->guardarConfig($this->config);
            $srv->guardarFranjas($this->franjas);
            $srv->guardarExcepciones($this->excepciones);
            $this->cargar();
            $this->mensaje = 'Agenda guardada.';
            $this->tipoMensaje = 'success';
        } catch (\Throwable $e) {
            $this->mensaje = 'No se pudo guardar: ' . $e->getMessage();
            $this->tipoMensaje = 'error';
        }
    }

    public function render()
    {
        return view('panel.configurar-agenda');
    }
}
