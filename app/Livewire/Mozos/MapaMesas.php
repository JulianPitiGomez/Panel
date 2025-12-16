<?php

namespace App\Livewire\Mozos;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\MozosService;

class MapaMesas extends Component
{
    public $mesas = [];
    public $salones = [];
    public $salonActual;
    public $estado = 'T'; // T=Todas, O=Ocupadas, P=Propias
    public $numeroMesa = '';

    protected $mozosService;

    public function boot(MozosService $mozosService)
    {
        $this->mozosService = $mozosService;
    }

    public function mount()
    {
        $this->salones = $this->mozosService->obtenerSalones();

        // Recuperar el estado y salón desde la sesión si existen
        $this->salonActual = session('mozo_salon_actual', $this->salones->first()->codigo ?? null);
        $this->estado = session('mozo_estado_filtro', 'T');

        $this->cargarMesas();
    }

    public function cargarMesas()
    {
        if ($this->salonActual) {
            $this->mesas = collect($this->mozosService->obtenerMesas($this->salonActual));
        }
    }

    public function cambiarSalon($salon)
    {
        $this->salonActual = $salon;
        session(['mozo_salon_actual' => $salon]);
        $this->cargarMesas();
    }

    public function cambiarEstado($nuevoEstado)
    {
        $this->estado = $nuevoEstado;
        session(['mozo_estado_filtro' => $nuevoEstado]);
    }

    public function irAMesa()
    {
        if ($this->numeroMesa) {
            $this->redirectRoute('mozos.mesa', ['mesa' => $this->numeroMesa], navigate: true);
        }
    }

    public function getMesasFiltradas()
    {
        $mozoId = session('mozo_user_id');

        return $this->mesas->filter(function($mesa) use ($mozoId) {
            switch ($this->estado) {
                case 'O': // Ocupadas
                    return $mesa->total > 0;
                case 'P': // Propias
                    return $mesa->mozo == $mozoId;
                case 'T': // Todas
                default:
                    return true;
            }
        });
    }

    #[Layout('layouts.mozos')]
    public function render()
    {
        return view('mozos.mapa-mesas', [
            'mesasFiltradas' => $this->getMesasFiltradas()
        ]);
    }
}
