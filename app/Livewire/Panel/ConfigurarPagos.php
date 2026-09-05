<?php

namespace App\Livewire\Panel;

use App\Services\MercadoPagoService;
use App\Services\TiendaIntegracionService;
use App\Services\TiendaPagoService;
use App\Services\TiendaService;
use Livewire\Component;

/**
 * Pestana Pagos de Catalogo: que medios ofrece el comercio y sus datos.
 * El access token de Mercado Pago se guarda cifrado y nunca se vuelve a mostrar.
 */
class ConfigurarPagos extends Component
{
    public $config = [];
    public $mpToken = '';          // solo escritura
    public $mpEstado = null;       // ['cargado' => bool, 'verificada' => bool, 'dato' => ?, 'fecha' => ?, 'error' => ?]
    public $mensaje = '';
    public $tipoMensaje = '';
    public $formasGestion = [];    // forpag de gestion: [CODIGO, NOMBRE, TIPO]

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
        $srvPagos = new TiendaPagoService($this->tablePrefix);
        $cfg = $srvPagos->config();
        unset($cfg['id']);
        $this->formasGestion = array_map(fn ($f) => ['CODIGO' => (int) $f->CODIGO, 'NOMBRE' => trim($f->NOMBRE), 'TIPO' => (int) $f->TIPO], $srvPagos->formasPagoGestion());
        foreach (['transferencia_activa', 'cuenta_corriente_activa', 'mercadopago_activa', 'a_convenir_activa'] as $b) {
            $cfg[$b] = (bool) $cfg[$b];
        }
        $this->config = $cfg;

        $mp = (new TiendaIntegracionService($this->tablePrefix))->obtener(TiendaIntegracionService::MERCADOPAGO);
        $this->mpEstado = [
            'cargado' => (bool) ($mp && !empty($mp->credenciales['access_token'])),
            'verificada' => (bool) ($mp->verificada ?? false),
            'dato' => $mp->verificada_dato ?? null,
            'fecha' => $mp->verificada_fecha ?? null,
            'error' => $mp->ultimo_error ?? null,
        ];
    }

    public function guardar()
    {
        $this->conectar();

        try {
            $integraciones = new TiendaIntegracionService($this->tablePrefix);

            if (trim($this->mpToken) !== '') {
                $integraciones->guardar(TiendaIntegracionService::MERCADOPAGO, ['access_token' => trim($this->mpToken)], ['proveedor' => 'mercadopago', 'activa' => 1]);
                $this->mpToken = '';
            }

            if ($this->config['mercadopago_activa'] && !$integraciones->mercadoPagoToken()) {
                $this->config['mercadopago_activa'] = false;
                $this->mensaje = 'Para activar Mercado Pago primero cargá el access token de tu cuenta.';
                $this->tipoMensaje = 'error';
            }

            (new TiendaPagoService($this->tablePrefix))->guardarConfig($this->config);

            if ($this->tipoMensaje !== 'error') {
                $this->mensaje = 'Medios de pago guardados.';
                $this->tipoMensaje = 'success';
            }
            $this->cargar();
        } catch (\Throwable $e) {
            $this->mensaje = 'No se pudo guardar: ' . $e->getMessage();
            $this->tipoMensaje = 'error';
        }
    }

    /** Llama a MP con el token cargado y anota si respondio (nombre de la cuenta). */
    public function probarMercadoPago()
    {
        $this->conectar();
        $integraciones = new TiendaIntegracionService($this->tablePrefix);
        $token = trim($this->mpToken) !== '' ? trim($this->mpToken) : $integraciones->mercadoPagoToken();

        if (!$token) {
            $this->mensaje = 'Cargá el access token antes de probar.';
            $this->tipoMensaje = 'error';
            return;
        }

        if (trim($this->mpToken) !== '') {
            $integraciones->guardar(TiendaIntegracionService::MERCADOPAGO, ['access_token' => $token], ['proveedor' => 'mercadopago', 'activa' => 1]);
            $this->mpToken = '';
        }

        $usuario = (new MercadoPagoService($token))->obtenerUsuario();
        if ($usuario && !empty($usuario['id'])) {
            $nombre = trim(($usuario['first_name'] ?? '') . ' ' . ($usuario['last_name'] ?? '')) ?: ($usuario['nickname'] ?? ('cuenta ' . $usuario['id']));
            $integraciones->marcarVerificada(TiendaIntegracionService::MERCADOPAGO, true, $nombre);
            $this->mensaje = "Mercado Pago respondió: cuenta de {$nombre}.";
            $this->tipoMensaje = 'success';
        } else {
            $integraciones->marcarVerificada(TiendaIntegracionService::MERCADOPAGO, false, null, 'Mercado Pago no reconoció el token');
            $this->mensaje = 'Mercado Pago no reconoció ese token. Revisá que sea el access token de producción de tu cuenta.';
            $this->tipoMensaje = 'error';
        }

        $this->cargar();
    }

    public function quitarMercadoPago()
    {
        $this->conectar();
        (new TiendaIntegracionService($this->tablePrefix))->eliminar(TiendaIntegracionService::MERCADOPAGO);
        $this->config['mercadopago_activa'] = false;
        (new TiendaPagoService($this->tablePrefix))->guardarConfig($this->config);
        $this->mensaje = 'Se quitó la cuenta de Mercado Pago.';
        $this->tipoMensaje = 'success';
        $this->cargar();
    }

    public function render()
    {
        return view('panel.configurar-pagos');
    }
}
