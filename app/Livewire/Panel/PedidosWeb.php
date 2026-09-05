<?php

namespace App\Livewire\Panel;

use App\Services\TiendaEstadoPedido;
use App\Services\TiendaPagoService;
use App\Services\TiendaPedidoService;
use App\Services\TiendaService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Pestana Pedidos web de Catalogo: los pedidos que entraron por la tienda, con su
 * entrega y su pago. Desde aca el comercio confirma transferencias en un clic.
 * No cambia nada en pedidos_encab: eso sigue siendo de gestion.
 */
class PedidosWeb extends Component
{
    public $filtroPago = 'todos';   // todos | pendiente | aprobado | rechazado | no_aplica
    public $busqueda = '';
    public $pedidos = [];
    public $detalleId = null;
    public $detalle = [];
    public $eventos = [];
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

    public function updatedFiltroPago()
    {
        $this->conectar();
        $this->cargar();
    }

    public function updatedBusqueda()
    {
        $this->conectar();
        $this->cargar();
    }

    private function cargar()
    {
        $p = $this->tablePrefix;
        $q = DB::connection('client_db')
            ->table("{$p}tienda_pedidos as t")
            ->join("{$p}pedidos_encab as e", 'e.id', '=', 't.idpedido')
            ->leftJoin("{$p}tienda_compradores as c", 'c.id', '=', 't.comprador_id')
            ->select('t.*', 'e.cliente', 'e.fecha', 'e.parafecha', 'e.estado', 'e.observa', 'c.email as comprador_email', 'c.telefono as comprador_telefono')
            ->selectSub(
                DB::connection('client_db')->table("{$p}pedidos_det")
                    ->whereColumn('idpedido', 't.idpedido')
                    ->selectRaw('COALESCE(SUM(cantidad * (punitario - COALESCE(descu, 0))), 0)'),
                'subtotal'
            )
            ->orderByDesc('t.idpedido')
            ->limit(200);

        if ($this->filtroPago !== 'todos') {
            $q->where('t.pago_estado', $this->filtroPago);
        }
        if (trim($this->busqueda) !== '') {
            $b = trim($this->busqueda);
            $q->where(function ($w) use ($b) {
                $w->where('e.cliente', 'like', "%{$b}%")->orWhere('t.idpedido', $b)->orWhere('c.email', 'like', "%{$b}%");
            });
        }

        $this->pedidos = $q->get()->map(fn ($r) => (array) $r)->all();
    }

    public function verDetalle($id)
    {
        $this->conectar();
        $this->detalleId = (int) $id;
        $this->detalle = DB::connection('client_db')->table("{$this->tablePrefix}pedidos_det")
            ->where('idpedido', $this->detalleId)->get()->map(fn ($d) => (array) $d)->all();
        $this->eventos = (new TiendaPedidoService($this->tablePrefix))->eventos($this->detalleId)->map(fn ($e) => (array) $e)->all();
    }

    public function cerrarDetalle()
    {
        $this->detalleId = null;
        $this->detalle = [];
        $this->eventos = [];
    }

    public function confirmarPago($id)
    {
        $this->conectar();
        (new TiendaPagoService($this->tablePrefix))->confirmarPago((int) $id, session('cliente_nombre') ?: 'panel');
        $this->mensaje = "Pago del pedido #{$id} confirmado.";
        $this->tipoMensaje = 'success';
        $this->cargar();
        if ($this->detalleId === (int) $id) {
            $this->verDetalle($id);
        }
    }

    public function rechazarPago($id)
    {
        $this->conectar();
        (new TiendaPagoService($this->tablePrefix))->rechazarPago((int) $id, null, session('cliente_nombre') ?: 'panel');
        $this->mensaje = "Pago del pedido #{$id} marcado como rechazado.";
        $this->tipoMensaje = 'success';
        $this->cargar();
        if ($this->detalleId === (int) $id) {
            $this->verDetalle($id);
        }
    }

    public function etiquetaEstadoGestion($estado): string
    {
        return TiendaEstadoPedido::etiqueta($estado);
    }

    public function etiquetaMedio($medio): string
    {
        return TiendaPagoService::etiquetaMedio($medio);
    }

    public function etiquetaPago($estado): string
    {
        return TiendaPagoService::etiquetaEstado($estado);
    }

    public function render()
    {
        return view('panel.pedidos-web');
    }
}
