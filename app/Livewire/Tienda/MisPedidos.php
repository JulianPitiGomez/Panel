<?php

namespace App\Livewire\Tienda;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Cliente;

class MisPedidos extends Component
{
    public $tiendaSlug;
    public $tablePrefix;
    public $colores = [];
    public $pedidos = [];
    public $detallePedidoId = null;
    public $detallePedido = [];

    public function mount($tienda)
    {
        $this->tiendaSlug = $tienda;

        if (!session('tienda_comprador_id')) {
            return redirect()->route('tienda.catalogo', $tienda);
        }

        $this->setupClientDatabase();

        $datos = session('tienda_datos', []);
        $this->colores = [
            'fondo' => $datos['color_fondo'] ?? '#f9fafb',
            'texto' => $datos['color_texto'] ?? '#1f2937',
            'precio' => $datos['color_precio'] ?? '#1E1B35',
            'boton' => $datos['color_boton'] ?? '#FFAF22',
        ];

        $this->cargarPedidos();
    }

    private function setupClientDatabase()
    {
        $configId = session('tienda_config_id');
        if (!$configId) return;

        $cliente = Cliente::find($configId);
        if ($cliente) {
            Config::set('database.connections.client_db.database', $cliente->base);
            DB::purge('client_db');
            $this->tablePrefix = $cliente->getTablePrefix();
        }
    }

    private function cargarPedidos()
    {
        $this->setupClientDatabase();
        $compradorId = session('tienda_comprador_id');

        $pedidos = DB::connection('client_db')
            ->table("{$this->tablePrefix}pedidos_encab")
            ->where('codcli', $compradorId)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $this->pedidos = $pedidos->map(function ($p) {
            return [
                'id' => $p->id,
                'fecha' => $p->fecha,
                'parafecha' => $p->parafecha,
                'estado' => $p->estado,
                'nuevo' => $p->nuevo ?? 1,
                'facturanro' => $p->facturanro ?? null,
                'observa' => $p->observa ?? '',
            ];
        })->toArray();
    }

    public function getEstado($pedido)
    {
        $estado = $pedido['estado'];
        $nuevo = $pedido['nuevo'];

        return match(true) {
            $estado === 'F' => ['texto' => 'Facturado', 'color' => '#10b981', 'icono' => 'fa-file-invoice'],
            $estado === 'A' => ['texto' => 'En armado', 'color' => '#f59e0b', 'icono' => 'fa-box-open'],
            $estado === 'P' && $nuevo == 0 => ['texto' => 'Recibido', 'color' => '#3b82f6', 'icono' => 'fa-check-circle'],
            default => ['texto' => 'Pendiente', 'color' => '#6b7280', 'icono' => 'fa-clock'],
        };
    }

    public function verDetalle($pedidoId)
    {
        $this->setupClientDatabase();

        $this->detallePedidoId = $pedidoId;
        $this->detallePedido = DB::connection('client_db')
            ->table("{$this->tablePrefix}pedidos_det")
            ->where('idpedido', $pedidoId)
            ->get()
            ->map(fn($d) => [
                'codigo' => $d->codart,
                'nombre' => $d->detart,
                'cantidad' => $d->cantidad,
                'precio' => $d->punitario,
                'subtotal' => $d->cantidad * $d->punitario,
            ])
            ->toArray();
    }

    public function cerrarDetalle()
    {
        $this->detallePedidoId = null;
        $this->detallePedido = [];
    }

    public function volverAlCatalogo()
    {
        return redirect()->route('tienda.catalogo', $this->tiendaSlug);
    }

    #[Layout('layouts.tienda')]
    public function render()
    {
        return view('tienda.mis-pedidos');
    }
}
