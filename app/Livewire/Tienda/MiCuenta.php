<?php

namespace App\Livewire\Tienda;

use App\Models\Cliente;
use App\Services\TiendaCuentaService;
use App\Services\TiendaPagoService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Mi cuenta (F6): el cliente mayorista ve su saldo, sus comprobantes y su cuenta
 * corriente, y paga la cuenta por transferencia o Mercado Pago sin llamar a la
 * distribuidora. Solo para compradores enlazados a un cliente de gestion (codcli).
 */
class MiCuenta extends Component
{
    use WithFileUploads;

    public $tiendaSlug;
    public $colores = [];
    public $tab = 'resumen';   // resumen | comprobantes | movimientos | pagar

    public $resumen = [];
    public $comprobantes = [];
    public $movimientos = [];
    public $pagosWeb = [];

    public $detalle = null;          // comprobante abierto
    public $detalleClave = null;

    // Pagar la cuenta
    public $pagoImporte = '';
    public $pagoMedio = 'transferencia';
    public $mediosDisponibles = [];
    public $pagoCreado = null;       // fila recien creada (transferencia) para mostrar datos
    public $mensajePago = '';
    public $tipoMensajePago = '';
    public $comprobante = null;      // archivo
    public $comprobantePagoId = null;

    private $tablePrefix;
    private $codcli;

    public function mount($tienda)
    {
        $this->tiendaSlug = $tienda;
        if (!session('tienda_comprador_web_id') || !session('tienda_comprador_id')) {
            return redirect()->route('tienda.mis-pedidos', $tienda);
        }
        $this->conectar();

        $datos = session('tienda_datos', []);
        $this->colores = [
            'fondo'  => $datos['color_fondo'] ?? '#f9fafb',
            'texto'  => $datos['color_texto'] ?? '#1f2937',
            'precio' => $datos['color_precio'] ?? '#1E1B35',
            'boton'  => $datos['color_boton'] ?? '#FFAF22',
        ];

        // Volviendo de Mercado Pago: refrescar ese pago
        if ($pagoId = (int) request()->query('pago')) {
            $estado = (new TiendaCuentaService($this->tablePrefix))->consultarMercadoPago($pagoId, (int) session('tienda_config_id'));
            $this->tab = 'pagar';
            $this->mensajePago = match ($estado) {
                TiendaCuentaService::ESTADO_APROBADO => '¡Pago acreditado! El comercio lo aplica a tu cuenta.',
                TiendaCuentaService::ESTADO_RECHAZADO => 'Mercado Pago rechazó el pago. Podés intentar de nuevo.',
                default => 'Mercado Pago todavía no confirmó el pago. Actualizá en unos minutos.',
            };
            $this->tipoMensajePago = $estado === TiendaCuentaService::ESTADO_APROBADO ? 'success' : ($estado === TiendaCuentaService::ESTADO_RECHAZADO ? 'error' : 'info');
        }

        $this->cargar();
    }

    private function conectar()
    {
        $cliente = Cliente::find(session('tienda_config_id'));
        if ($cliente) {
            Config::set('database.connections.client_db.database', $cliente->base);
            DB::purge('client_db');
            $this->tablePrefix = $cliente->getTablePrefix();
        }
        $this->codcli = (int) session('tienda_comprador_id');
    }

    private function cargar()
    {
        $srv = new TiendaCuentaService($this->tablePrefix);
        $this->resumen = $srv->resumen($this->codcli);
        $this->comprobantes = $srv->comprobantes($this->codcli);
        $this->movimientos = $srv->movimientos($this->codcli);
        $this->pagosWeb = $srv->pagosDe($this->codcli);

        $cfg = (new TiendaPagoService($this->tablePrefix))->config();
        $this->mediosDisponibles = array_values(array_filter([
            !empty($cfg['transferencia_activa']) ? TiendaPagoService::MEDIO_TRANSFERENCIA : null,
            !empty($cfg['mercadopago_activa']) ? TiendaPagoService::MEDIO_MERCADOPAGO : null,
        ]));
        if (!in_array($this->pagoMedio, $this->mediosDisponibles, true)) {
            $this->pagoMedio = $this->mediosDisponibles[0] ?? '';
        }
        if ($this->pagoImporte === '' && ($this->resumen['saldo'] ?? 0) > 0) {
            $this->pagoImporte = number_format((float) $this->resumen['saldo'], 2, '.', '');
        }
    }

    public function irA($tab)
    {
        $this->tab = in_array($tab, ['resumen', 'comprobantes', 'movimientos', 'pagar'], true) ? $tab : 'resumen';
    }

    public function verComprobante($ticomp, $letra, $numero)
    {
        $this->conectar();
        $this->detalle = (new TiendaCuentaService($this->tablePrefix))->detalleComprobante($this->codcli, (string) $ticomp, (string) $letra, (string) $numero);
        $this->detalleClave = $this->detalle ? [$ticomp, $letra, $numero] : null;
    }

    public function cerrarComprobante()
    {
        $this->detalle = null;
        $this->detalleClave = null;
    }

    public function urlComprobante($ticomp, $letra, $numero, bool $pdf = false): string
    {
        return route('tienda.comprobante', ['tienda' => $this->tiendaSlug, 'ticomp' => $ticomp, 'letra' => $letra, 'numero' => $numero] + ($pdf ? ['pdf' => 1] : []));
    }

    // ---------------------------------------------------------- pagar

    public function pagar()
    {
        $this->conectar();
        $this->mensajePago = '';
        $this->pagoCreado = null;
        $importe = (float) str_replace(',', '.', (string) $this->pagoImporte);

        $r = (new TiendaCuentaService($this->tablePrefix))->crearPago(
            (int) session('tienda_comprador_web_id'), $this->codcli, $importe, $this->pagoMedio,
            (int) session('tienda_config_id'), $this->tiendaSlug
        );
        if (!$r['ok']) {
            $this->mensajePago = $r['mensaje'];
            $this->tipoMensajePago = 'error';
            return;
        }
        if ($r['init_point']) {
            return redirect()->away($r['init_point']);
        }
        $this->pagoCreado = ['id' => $r['id'], 'importe_unico' => $r['importe_unico']];
        $this->comprobantePagoId = $r['id'];
        $this->mensajePago = $r['mensaje'];
        $this->tipoMensajePago = 'success';
        $this->cargar();
    }

    public function abrirComprobante($pagoId)
    {
        $this->comprobantePagoId = (int) $pagoId;
        $this->comprobante = null;
    }

    public function subirComprobante()
    {
        $this->validate(['comprobante' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120'], [
            'comprobante.required' => 'Elegí el archivo del comprobante.',
            'comprobante.mimes' => 'Tiene que ser una imagen (JPG o PNG) o un PDF.',
            'comprobante.max' => 'El archivo no puede pesar más de 5 MB.',
        ]);
        $this->conectar();
        $pago = DB::connection('client_db')->table("{$this->tablePrefix}tienda_pagos_cuenta")->where('id', (int) $this->comprobantePagoId)->first();
        if (!$pago || (int) $pago->codcli !== $this->codcli || $pago->estado === TiendaCuentaService::ESTADO_APROBADO) {
            $this->mensajePago = 'Ese pago no admite comprobante.';
            $this->tipoMensajePago = 'error';
            return;
        }
        try {
            (new TiendaCuentaService($this->tablePrefix))->guardarComprobante((int) $pago->id, $this->comprobante);
            $this->mensajePago = 'Comprobante enviado. El comercio lo revisa y lo aplica a tu cuenta.';
            $this->tipoMensajePago = 'success';
        } catch (\Throwable $e) {
            $this->mensajePago = 'No se pudo subir el comprobante. Probá de nuevo.';
            $this->tipoMensajePago = 'error';
        }
        $this->comprobante = null;
        $this->comprobantePagoId = null;
        $this->pagoCreado = null;
        $this->cargar();
    }

    public function datosTransferencia(): array
    {
        $this->conectar();

        return (new TiendaPagoService($this->tablePrefix))->datosTransferencia();
    }

    public function etiquetaMedio($medio): string
    {
        return TiendaPagoService::etiquetaMedio($medio);
    }

    #[Layout('layouts.tienda')]
    public function render()
    {
        return view('tienda.mi-cuenta');
    }
}
