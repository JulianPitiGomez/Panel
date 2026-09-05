<?php

namespace App\Livewire\Tienda;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use App\Models\Cliente;

class MisPedidos extends Component
{
    use WithFileUploads;

    public $tiendaSlug;
    public $tablePrefix;
    public $colores = [];
    public $pedidos = [];
    public $detallePedidoId = null;
    public $detallePedido = [];
    public $detalleEventos = [];

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

        // Satelite de la tienda (entrega y pago) para los pedidos que vinieron de la web
        $satelites = DB::connection('client_db')
            ->table("{$this->tablePrefix}tienda_pedidos")
            ->whereIn('idpedido', $pedidos->pluck('id')->all())
            ->get()
            ->keyBy('idpedido');

        $this->pedidos = $pedidos->map(function ($p) use ($satelites) {
            $s = $satelites[$p->id] ?? null;
            return [
                'id' => $p->id,
                'fecha' => $p->fecha,
                'parafecha' => $p->parafecha,
                'estado' => $p->estado,
                'nuevo' => $p->nuevo ?? 1,
                'facturanro' => $p->facturanro ?? null,
                'observa' => $p->observa ?? '',
                'web' => (bool) $s,
                'entrega_modo' => $s->entrega_modo ?? null,
                'entrega_direccion' => $s->entrega_direccion ?? null,
                'envio_importe' => (float) ($s->envio_importe ?? 0),
                'pago_medio' => $s->pago_medio ?? null,
                'pago_estado' => $s->pago_estado ?? null,
                'pago_importe_unico' => $s->pago_importe_unico ?? null,
                'pago_comprobante' => $s->pago_comprobante ?? null,
            ];
        })->toArray();
    }

    // ---------------------------------------------------------------- pago

    public $comprobante = null;          // archivo subido (transferencia)
    public $comprobantePedidoId = null;  // pedido al que se le esta subiendo
    public $mensajePago = '';

    public function abrirComprobante($pedidoId)
    {
        $this->comprobantePedidoId = (int) $pedidoId;
        $this->comprobante = null;
        $this->mensajePago = '';
    }

    public function subirComprobante()
    {
        $this->validate(['comprobante' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120'], [
            'comprobante.required' => 'Elegí el archivo del comprobante.',
            'comprobante.mimes' => 'Tiene que ser una imagen (JPG o PNG) o un PDF.',
            'comprobante.max' => 'El archivo no puede pesar más de 5 MB.',
        ]);

        $this->setupClientDatabase();
        $pedidoId = (int) $this->comprobantePedidoId;

        // Solo pedidos propios, por transferencia y todavia sin confirmar
        $sat = DB::connection('client_db')->table("{$this->tablePrefix}tienda_pedidos")->where('idpedido', $pedidoId)->first();
        if (!$sat || (int) $sat->comprador_id !== (int) session('tienda_comprador_web_id')
            || $sat->pago_medio !== \App\Services\TiendaPagoService::MEDIO_TRANSFERENCIA
            || $sat->pago_estado === \App\Services\TiendaPagoService::ESTADO_APROBADO) {
            $this->mensajePago = 'Ese pedido no admite comprobante.';
            return;
        }

        try {
            (new \App\Services\TiendaPagoService($this->tablePrefix))->guardarComprobante($pedidoId, $this->comprobante);
            $this->mensajePago = 'Comprobante enviado. El comercio lo va a revisar y confirmar el pago.';
        } catch (\Throwable $e) {
            $this->mensajePago = 'No se pudo subir el comprobante. Probá de nuevo.';
        }

        $this->comprobante = null;
        $this->comprobantePedidoId = null;
        $this->cargarPedidos();
    }

    public function datosTransferencia(): array
    {
        $this->setupClientDatabase();
        return (new \App\Services\TiendaPagoService($this->tablePrefix))->datosTransferencia();
    }

    public function etiquetaPago($pedido): array
    {
        $P = \App\Services\TiendaPagoService::class;
        $texto = $P::etiquetaMedio($pedido['pago_medio']);
        return match ($pedido['pago_estado']) {
            $P::ESTADO_APROBADO => ['texto' => $texto . ' · Pagado', 'color' => '#10b981'],
            $P::ESTADO_RECHAZADO => ['texto' => $texto . ' · Rechazado', 'color' => '#ef4444'],
            $P::ESTADO_PENDIENTE => ['texto' => $texto . ' · Pendiente', 'color' => '#f59e0b'],
            default => ['texto' => $texto, 'color' => '#6b7280'],
        };
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

        $this->detalleEventos = (new \App\Services\TiendaPedidoService($this->tablePrefix))
            ->eventos((int) $pedidoId)
            ->filter(fn ($e) => in_array($e->origen, ['comprador', 'comercio', 'sistema']) && !str_starts_with($e->estado, 'comision_'))
            ->map(fn ($e) => ['fecha' => $e->fecha, 'detalle' => $e->detalle ?: $e->estado])
            ->values()->all();
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
