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
                'estado_web' => $s ? \App\Services\TiendaSeguimientoService::normalizar($s->estado_web) : null,
                'envio_seguimiento' => $s->envio_seguimiento ?? null,
                'envio_transportista' => $s->envio_transportista ?? null,
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

        // Pedido web: manda el seguimiento propio de la tienda (F6); gestion solo pisa cuando anula
        if (!empty($pedido['web']) && !empty($pedido['estado_web'])) {
            $ew = \App\Services\TiendaEstadoPedido::esAnulado($estado) ? 'cancelado' : $pedido['estado_web'];
            $iconos = ['recibido' => 'fa-check-circle', 'preparacion' => 'fa-box-open', 'listo' => 'fa-check-double', 'despachado' => 'fa-truck', 'en_camino' => 'fa-motorcycle', 'entregado' => 'fa-flag-checkered', 'cancelado' => 'fa-times-circle'];
            $coloresEw = ['recibido' => '#3b82f6', 'preparacion' => '#f59e0b', 'listo' => '#8b5cf6', 'despachado' => '#0ea5e9', 'en_camino' => '#0ea5e9', 'entregado' => '#10b981', 'cancelado' => '#ef4444'];
            return ['texto' => \App\Services\TiendaSeguimientoService::etiqueta($ew, $pedido['entrega_modo']), 'color' => $coloresEw[$ew] ?? '#6b7280', 'icono' => $iconos[$ew] ?? 'fa-clock'];
        }

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

        // Pasos del seguimiento (F6): el camino completo con lo hecho marcado
        $pedido = collect($this->pedidos)->firstWhere('id', (int) $pedidoId);
        $this->detallePasos = [];
        if ($pedido && $pedido['web'] && $pedido['estado_web'] && $pedido['estado_web'] !== 'cancelado' && !\App\Services\TiendaEstadoPedido::esAnulado($pedido['estado'])) {
            $flujo = \App\Services\TiendaSeguimientoService::flujo($pedido['entrega_modo']);
            $pos = array_search($pedido['estado_web'], $flujo, true);
            if ($pos === false && in_array($pedido['estado_web'], ['en_camino', 'despachado'])) {
                $flujo = ['recibido', 'preparacion', 'listo', $pedido['estado_web'], 'entregado'];
                $pos = 3;
            }
            foreach ($flujo as $i => $e) {
                $this->detallePasos[] = ['etiqueta' => \App\Services\TiendaSeguimientoService::etiqueta($e, $pedido['entrega_modo']), 'hecho' => $pos !== false && $i <= $pos, 'actual' => $i === $pos];
            }
            $this->detalleSeguimientoUrl = \App\Services\TiendaSeguimientoService::urlSeguimiento($pedido['envio_transportista'], $pedido['envio_seguimiento']);
        }
    }

    public $detallePasos = [];
    public $detalleSeguimientoUrl = null;

    public function cerrarDetalle()
    {
        $this->detallePedidoId = null;
        $this->detallePedido = [];
    }

    /**
     * Repetir un pedido: arma el carrito con los mismos articulos (los que siguen a la venta),
     * a precio de hoy y con la lista del comprador, y manda al carrito.
     */
    public function repetirPedido($pedidoId)
    {
        $this->setupClientDatabase();

        $renglones = DB::connection('client_db')->table("{$this->tablePrefix}pedidos_det")
            ->where('idpedido', (int) $pedidoId)->get(['codart', 'cantidad']);
        if ($renglones->isEmpty()) {
            return;
        }

        $productos = DB::connection('client_db')
            ->table("{$this->tablePrefix}articu as a")
            ->leftJoin("{$this->tablePrefix}articu_fotos as f", 'a.CODIGO', '=', 'f.codigo')
            ->leftJoin("{$this->tablePrefix}ivas as i", 'a.IVA', '=', 'i.codigo')
            ->whereIn('a.CODIGO', $renglones->pluck('codart')->all())
            ->where('a.NOSALE', 0)
            ->get(['a.CODIGO', 'a.NOMBRE', 'a.PRECIOVEN', 'a.REVENTA', 'f.url as foto_url', 'i.tasa as tasa_iva'])
            ->keyBy('CODIGO');

        $lispreesp = session('tienda_comprador_lispreesp');
        $lispre = session('tienda_comprador_lispre');
        $especiales = $lispreesp
            ? DB::connection('client_db')->table("{$this->tablePrefix}lispredet")->where('CODLIS', $lispreesp)->whereIn('CODART', $productos->keys()->all())->pluck('PRECIO', 'CODART')
            : collect();

        $carrito = [];
        foreach ($renglones as $r) {
            $p = $productos[$r->codart] ?? null;
            if (!$p) {
                continue;   // ya no se vende
            }
            $precio = (float) ($especiales[$r->codart] ?? ($lispre == 2 ? $p->REVENTA : $p->PRECIOVEN));
            $carrito[] = [
                'codigo' => $p->CODIGO, 'nombre' => $p->NOMBRE, 'precio' => $precio,
                'precio_ven' => $p->PRECIOVEN, 'precio_rev' => $p->REVENTA,
                'cantidad' => (float) $r->cantidad, 'subtotal' => $precio * (float) $r->cantidad,
                'foto_url' => $p->foto_url, 'tasa' => (float) ($p->tasa_iva ?? 0),
            ];
        }

        session(['tienda_carrito' => $carrito]);

        return redirect()->route('tienda.carrito', $this->tiendaSlug);
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
