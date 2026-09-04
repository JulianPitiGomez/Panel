<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use App\Services\BusinessHealthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

class SaludNegocio extends Component
{
    public array $data = [];
    public bool $cargando = true;
    public ?string $error = null;

    protected string $pack = 'GE3';
    protected string $viewName = 'panel.salud-negocio';

    public function mount()
    {
        $this->cargar();
    }

    public function refrescar()
    {
        $clientId = session('client_id');
        if ($clientId) {
            Cache::forget($this->cacheKey($clientId));
        }
        $this->cargar();
    }

    private function cacheKey($clientId): string
    {
        // v2: se agregó el bloque "De dónde vienen tus ventas" y cambió la forma
        // del array de concentración. Bumpear al cambiar la estructura de $data.
        return 'salud_negocio:v2:' . $clientId . ':' . now()->format('Y-m-d');
    }

    private function cargar(): void
    {
        $this->cargando = true;
        $this->error = null;

        $clientId = session('client_id');
        $cliente = $clientId ? \App\Models\Cliente::find($clientId) : null;

        if (!$cliente) {
            $this->error = 'No se pudo identificar el comercio.';
            $this->cargando = false;
            return;
        }

        try {
            Config::set('database.connections.client_db.database', $cliente->base);
            DB::purge('client_db');
            $prefix = $cliente->getTablePrefix();

            $this->data = Cache::remember(
                $this->cacheKey($clientId),
                600, // 10 minutos
                fn () => app(BusinessHealthService::class)->resumen($prefix, $this->pack)
            );
        } catch (\Throwable $e) {
            $this->error = 'No se pudieron calcular los indicadores en este momento.';
            report($e);
        }

        $this->cargando = false;
    }

    public function render()
    {
        return view($this->viewName);
    }
}
