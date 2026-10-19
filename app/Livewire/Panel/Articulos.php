<?php

namespace App\Livewire\Panel;

use Livewire\Component;

// Contenedor de la opción "Artículos": pestañas Vendidos / Consultas y Costos
class Articulos extends Component
{
    public $tabActivo = 'vendidos';

    public function setTab($tab)
    {
        if (in_array($tab, ['vendidos', 'consultas'])) {
            $this->tabActivo = $tab;
        }
    }

    public function render()
    {
        return view('panel.articulos');
    }
}
