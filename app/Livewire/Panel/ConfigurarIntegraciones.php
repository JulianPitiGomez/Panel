<?php

namespace App\Livewire\Panel;

use App\Services\TiendaAsistenteIaService;
use App\Services\TiendaCorreoService;
use App\Services\TiendaEnvioService;
use App\Services\TiendaIntegracionService;
use App\Services\TiendaService;
use App\Services\TiendaWhatsappService;
use Livewire\Component;

/**
 * Pestana Integraciones de Catalogo (F7): las cuentas del comercio en servicios de
 * terceros que van mas alla del cobro: la IA del asistente, WhatsApp por la API de
 * Meta y la API MiCorreo de Correo Argentino para cotizar envios. Regla: la cuenta
 * es del comercio; las claves se guardan cifradas y no se vuelven a mostrar.
 */
class ConfigurarIntegraciones extends Component
{
    public $mensaje = '';
    public $tipoMensaje = '';

    // Correo Argentino (API MiCorreo)
    public $correoPassword = '';            // contraseña de la API, solo escritura
    public $correoMicorreoPassword = '';    // contraseña de la web MiCorreo: solo para pedir el Customer ID, no se guarda
    public $correo = ['env' => 'produccion', 'usuario' => '', 'customer_id' => '', 'micorreo_email' => ''];
    public $correoEstado = [];
    public $correoCpPrueba = '1425';
    public $correoResultadoPrueba = [];     // lineas del resultado de la ultima prueba
    public $correoOrigenCp = '';            // CP del local (se carga en Envios)
    public $correoProveedorElegido = null;  // transportista elegido en Envios

    // IA (clave del comercio)
    public $iaClave = '';           // solo escritura
    public $iaModelo = '';
    public $iaEstado = [];
    public $iaBusqueda = true;      // busqueda que entiende
    public $iaPedido = true;        // pedido desde un mensaje

    // WhatsApp (API de Meta)
    public $waToken = '';           // solo escritura
    public $wa = ['phone_number_id' => '', 'idioma' => 'es_AR', 'plantilla_pedido' => '', 'plantilla_estado' => '', 'plantilla_carrito' => '', 'telefono_comercio' => '', 'avisa_pedido' => true, 'avisa_estado' => true, 'avisa_carrito' => false, 'carrito_horas' => 6];
    public $waEstado = [];
    public $waTelefonoPrueba = '';

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
        $integraciones = new TiendaIntegracionService($this->tablePrefix);

        $ia = $integraciones->obtener(TiendaIntegracionService::IA);
        $this->iaEstado = [
            'cargada' => (bool) ($ia && !empty($ia->credenciales['api_key'])),
            'activa' => (bool) ($ia->activa ?? false),
            'verificada' => (bool) ($ia->verificada ?? false),
            'dato' => $ia->verificada_dato ?? null,
            'error' => $ia->ultimo_error ?? null,
        ];
        $this->iaModelo = $ia->modelo ?? '';
        $this->iaBusqueda = (bool) ($ia->credenciales['busqueda'] ?? true);
        $this->iaPedido = (bool) ($ia->credenciales['pedido'] ?? true);

        $wa = $integraciones->obtener(TiendaIntegracionService::WHATSAPP);
        $cred = $wa->credenciales ?? [];
        foreach (array_keys($this->wa) as $k) {
            if (array_key_exists($k, $cred)) {
                $this->wa[$k] = in_array($k, ['avisa_pedido', 'avisa_estado', 'avisa_carrito'], true) ? (bool) $cred[$k] : $cred[$k];
            }
        }
        $this->waEstado = [
            'cargado' => (bool) ($wa && !empty($cred['token'])),
            'activa' => (bool) ($wa->activa ?? false),
            'verificada' => (bool) ($wa->verificada ?? false),
            'dato' => $wa->verificada_dato ?? null,
            'error' => $wa->ultimo_error ?? null,
        ];

        $co = $integraciones->obtener(TiendaIntegracionService::CORREO);
        $credCo = $co->credenciales ?? [];
        $this->correo = [
            'env' => in_array($credCo['env'] ?? '', ['test', 'produccion'], true) ? $credCo['env'] : 'produccion',
            'usuario' => (string) ($credCo['usuario'] ?? ''),
            'customer_id' => (string) ($credCo['customer_id'] ?? ''),
            'micorreo_email' => (string) ($credCo['micorreo_email'] ?? ''),
        ];
        $this->correoEstado = [
            'cargado' => (bool) ($co && !empty($credCo['password'])),
            'activa' => (bool) ($co->activa ?? false),
            'verificada' => (bool) ($co->verificada ?? false),
            'dato' => $co->verificada_dato ?? null,
            'error' => $co->ultimo_error ?? null,
        ];

        $envios = (new TiendaEnvioService($this->tablePrefix))->config();
        $this->correoOrigenCp = (string) ($envios['origen_cp'] ?? '');
        $this->correoProveedorElegido = $envios['correo_activo'] ? ($envios['correo_proveedor'] ?: '') : null;
    }

    // ------------------------------------------------------------ IA

    public function guardarIa()
    {
        $this->conectar();
        $integraciones = new TiendaIntegracionService($this->tablePrefix);
        $actual = $integraciones->obtener(TiendaIntegracionService::IA);
        $clave = trim($this->iaClave) !== '' ? trim($this->iaClave) : ($actual->credenciales['api_key'] ?? null);
        if (!$clave) {
            $this->mensaje = 'Pegá la clave de API de Anthropic del comercio.';
            $this->tipoMensaje = 'error';
            return;
        }
        $integraciones->guardar(TiendaIntegracionService::IA, ['api_key' => $clave, 'busqueda' => $this->iaBusqueda, 'pedido' => $this->iaPedido], ['proveedor' => 'anthropic', 'modelo' => trim($this->iaModelo) ?: null, 'activa' => 1]);
        if ($actual && $actual->verificada && trim($this->iaClave) === '') {
            $integraciones->marcarVerificada(TiendaIntegracionService::IA, true, $actual->verificada_dato);
        }
        $this->iaClave = '';
        $this->mensaje = 'Asistente de IA guardado.';
        $this->tipoMensaje = 'success';
        $this->cargar();
    }

    public function probarIa()
    {
        $this->conectar();
        $integraciones = new TiendaIntegracionService($this->tablePrefix);
        $actual = $integraciones->obtener(TiendaIntegracionService::IA);
        $clave = trim($this->iaClave) !== '' ? trim($this->iaClave) : ($actual->credenciales['api_key'] ?? null);
        if (!$clave) {
            $this->mensaje = 'Pegá la clave antes de probar.';
            $this->tipoMensaje = 'error';
            return;
        }
        $this->guardarIa();
        try {
            $modelo = (new TiendaAsistenteIaService($this->tablePrefix))->probar($clave, $this->iaModelo);
            $integraciones->marcarVerificada(TiendaIntegracionService::IA, true, $modelo);
            $this->mensaje = "La clave funciona. Modelo: {$modelo}.";
            $this->tipoMensaje = 'success';
        } catch (\Throwable $e) {
            $integraciones->marcarVerificada(TiendaIntegracionService::IA, false, null, mb_substr($e->getMessage(), 0, 200));
            $this->mensaje = 'Anthropic rechazó la clave: ' . mb_substr($e->getMessage(), 0, 160);
            $this->tipoMensaje = 'error';
        }
        $this->cargar();
    }

    public function quitarIa()
    {
        $this->conectar();
        (new TiendaIntegracionService($this->tablePrefix))->eliminar(TiendaIntegracionService::IA);
        $this->mensaje = 'Se quitó la clave de IA. El asistente deja de ofrecerse en la tienda.';
        $this->tipoMensaje = 'success';
        $this->cargar();
    }

    // ------------------------------------------------------------ WhatsApp

    private function credencialesWa(?object $actual): ?array
    {
        $token = trim($this->waToken) !== '' ? trim($this->waToken) : ($actual->credenciales['token'] ?? null);
        if (!$token || trim((string) $this->wa['phone_number_id']) === '') {
            return null;
        }
        $cred = $this->wa;
        $cred['token'] = $token;
        $cred['phone_number_id'] = preg_replace('/\D/', '', (string) $cred['phone_number_id']);
        $cred['idioma'] = trim((string) $cred['idioma']) ?: 'es_AR';
        $cred['carrito_horas'] = max(1, (int) $cred['carrito_horas']);
        foreach (['plantilla_pedido', 'plantilla_estado', 'plantilla_carrito'] as $k) {
            $cred[$k] = trim((string) $cred[$k]);
        }

        return $cred;
    }

    public function guardarWhatsapp()
    {
        $this->conectar();
        $integraciones = new TiendaIntegracionService($this->tablePrefix);
        $actual = $integraciones->obtener(TiendaIntegracionService::WHATSAPP);
        $cred = $this->credencialesWa($actual);
        if (!$cred) {
            $this->mensaje = 'Hacen falta el token y el ID del número de teléfono de tu app de Meta.';
            $this->tipoMensaje = 'error';
            return;
        }
        $integraciones->guardar(TiendaIntegracionService::WHATSAPP, $cred, ['proveedor' => 'meta', 'activa' => 1]);
        if ($actual && $actual->verificada && trim($this->waToken) === '') {
            $integraciones->marcarVerificada(TiendaIntegracionService::WHATSAPP, true, $actual->verificada_dato);
        }
        $this->waToken = '';
        $this->mensaje = 'WhatsApp guardado.';
        $this->tipoMensaje = 'success';
        $this->cargar();
    }

    public function probarWhatsapp()
    {
        $this->conectar();
        $integraciones = new TiendaIntegracionService($this->tablePrefix);
        $actual = $integraciones->obtener(TiendaIntegracionService::WHATSAPP);
        $cred = $this->credencialesWa($actual);
        if (!$cred) {
            $this->mensaje = 'Cargá el token y el ID del número antes de probar.';
            $this->tipoMensaje = 'error';
            return;
        }
        if (!TiendaWhatsappService::normalizar($this->waTelefonoPrueba)) {
            $this->mensaje = 'Escribí el número al que mandar la prueba (ej. 2324 123456).';
            $this->tipoMensaje = 'error';
            return;
        }
        $this->guardarWhatsapp();
        $r = (new TiendaWhatsappService($this->tablePrefix))->probar($cred, $this->waTelefonoPrueba);
        if ($r['ok']) {
            $integraciones->marcarVerificada(TiendaIntegracionService::WHATSAPP, true, 'prueba a ' . TiendaWhatsappService::normalizar($this->waTelefonoPrueba));
            $this->mensaje = 'WhatsApp mandó la plantilla de prueba (hello_world). Fijate en el teléfono.';
            $this->tipoMensaje = 'success';
        } else {
            $integraciones->marcarVerificada(TiendaIntegracionService::WHATSAPP, false, null, $r['error']);
            $this->mensaje = 'WhatsApp respondió con error: ' . $r['error'];
            $this->tipoMensaje = 'error';
        }
        $this->cargar();
    }

    public function quitarWhatsapp()
    {
        $this->conectar();
        (new TiendaIntegracionService($this->tablePrefix))->eliminar(TiendaIntegracionService::WHATSAPP);
        $this->mensaje = 'Se quitó WhatsApp. Los avisos siguen saliendo por mail.';
        $this->tipoMensaje = 'success';
        $this->wa = ['phone_number_id' => '', 'idioma' => 'es_AR', 'plantilla_pedido' => '', 'plantilla_estado' => '', 'plantilla_carrito' => '', 'telefono_comercio' => '', 'avisa_pedido' => true, 'avisa_estado' => true, 'avisa_carrito' => false, 'carrito_horas' => 6];
        $this->cargar();
    }

    // ------------------------------------------------------------ Correo Argentino (MiCorreo)

    /** Credenciales para guardar o probar: lo del formulario, con la contraseña ya guardada si no se pego una nueva. */
    private function credencialesCorreo(?object $actual): ?array
    {
        $password = trim($this->correoPassword) !== '' ? trim($this->correoPassword) : ($actual->credenciales['password'] ?? null);
        $usuario = trim((string) $this->correo['usuario']);
        if (!$password || $usuario === '') {
            return null;
        }

        return [
            'env' => $this->correo['env'] === 'test' ? 'test' : 'produccion',
            'usuario' => $usuario,
            'password' => $password,
            'customer_id' => trim((string) $this->correo['customer_id']),
            'micorreo_email' => trim((string) $this->correo['micorreo_email']),
        ];
    }

    public function guardarCorreo()
    {
        $this->conectar();
        $this->correoResultadoPrueba = [];
        $integraciones = new TiendaIntegracionService($this->tablePrefix);
        $actual = $integraciones->obtener(TiendaIntegracionService::CORREO);
        $cred = $this->credencialesCorreo($actual);
        if (!$cred) {
            $this->mensaje = 'Hacen falta el usuario y la contraseña de la API que te dio Correo Argentino.';
            $this->tipoMensaje = 'error';
            return;
        }
        $activa = $actual ? (int) $actual->activa : 1;
        $integraciones->guardar(TiendaIntegracionService::CORREO, $cred, ['proveedor' => TiendaCorreoService::PROVEEDOR, 'activa' => $activa]);
        $sinCambios = $actual && trim($this->correoPassword) === ''
            && ($actual->credenciales['usuario'] ?? '') === $cred['usuario']
            && ($actual->credenciales['env'] ?? 'produccion') === $cred['env']
            && ($actual->credenciales['customer_id'] ?? '') === $cred['customer_id'];
        if ($sinCambios && $actual->verificada) {
            $integraciones->marcarVerificada(TiendaIntegracionService::CORREO, true, $actual->verificada_dato);
        }
        $this->correoPassword = '';
        $this->mensaje = 'Correo Argentino guardado.' . ($cred['customer_id'] === '' ? ' Falta el Customer ID: obtenelo con tu email y contraseña de MiCorreo.' : '');
        $this->tipoMensaje = $cred['customer_id'] === '' ? 'error' : 'success';
        $this->cargar();
    }

    /** Pide el Customer ID a MiCorreo con el email y la contraseña de la web. La contraseña no se guarda. */
    public function obtenerCustomerIdCorreo()
    {
        $this->conectar();
        $this->correoResultadoPrueba = [];
        $integraciones = new TiendaIntegracionService($this->tablePrefix);
        $actual = $integraciones->obtener(TiendaIntegracionService::CORREO);
        $cred = $this->credencialesCorreo($actual);
        if (!$cred) {
            $this->mensaje = 'Primero cargá el usuario y la contraseña de la API.';
            $this->tipoMensaje = 'error';
            return;
        }
        $email = trim((string) $this->correo['micorreo_email']);
        if ($email === '' || trim($this->correoMicorreoPassword) === '') {
            $this->mensaje = 'Escribí el email y la contraseña con los que entrás a la web de MiCorreo.';
            $this->tipoMensaje = 'error';
            return;
        }
        $r = (new TiendaCorreoService($this->tablePrefix))->obtenerCustomerId($cred, $email, $this->correoMicorreoPassword);
        $this->correoMicorreoPassword = '';
        if (!$r['ok']) {
            $this->mensaje = $r['error'];
            $this->tipoMensaje = 'error';
            return;
        }
        $this->correo['customer_id'] = $r['customer_id'];
        $this->guardarCorreo();
        $this->mensaje = "Customer ID obtenido y guardado: {$r['customer_id']}. Ahora probá una cotización.";
        $this->tipoMensaje = 'success';
    }

    /** Guarda y prueba: token y una cotizacion de un bulto chico desde el CP del local al CP indicado. */
    public function probarCorreo()
    {
        $this->conectar();
        $integraciones = new TiendaIntegracionService($this->tablePrefix);
        $actual = $integraciones->obtener(TiendaIntegracionService::CORREO);
        $cred = $this->credencialesCorreo($actual);
        if (!$cred) {
            $this->mensaje = 'Cargá el usuario y la contraseña de la API antes de probar.';
            $this->tipoMensaje = 'error';
            return;
        }
        $this->guardarCorreo();
        $this->correoResultadoPrueba = [];

        $origen = $this->correoOrigenCp !== '' ? $this->correoOrigenCp : '1000';
        $r = (new TiendaCorreoService($this->tablePrefix))->probar($cred, $origen, (string) $this->correoCpPrueba);
        if ($r['ok']) {
            $integraciones->marcarVerificada(TiendaIntegracionService::CORREO, true, 'Customer ID ' . $cred['customer_id'] . ($cred['env'] === 'test' ? ' (ambiente de prueba)' : ''));
            $this->correoResultadoPrueba = $r['lineas'];
            $this->mensaje = 'La API de Correo Argentino respondió bien.' . ($this->correoOrigenCp === '' ? ' Cargá el código postal del local en Envíos para que la tienda cotice desde tu dirección.' : '');
            $this->tipoMensaje = 'success';
        } else {
            $integraciones->marcarVerificada(TiendaIntegracionService::CORREO, false, null, $r['error']);
            $this->mensaje = 'Correo Argentino respondió con error: ' . $r['error'];
            $this->tipoMensaje = 'error';
        }
        $this->cargar();
    }

    /** Prende o apaga la cotizacion por API sin borrar las credenciales. Apagada, la tienda usa el tarifario manual. */
    public function alternarCorreo()
    {
        $this->conectar();
        $integraciones = new TiendaIntegracionService($this->tablePrefix);
        $actual = $integraciones->obtener(TiendaIntegracionService::CORREO);
        if (!$actual) {
            return;
        }
        $nuevo = $actual->activa ? 0 : 1;
        $integraciones->guardar(TiendaIntegracionService::CORREO, $actual->credenciales, ['proveedor' => TiendaCorreoService::PROVEEDOR, 'activa' => $nuevo]);
        if ($actual->verificada) {
            $integraciones->marcarVerificada(TiendaIntegracionService::CORREO, true, $actual->verificada_dato);
        }
        $this->mensaje = $nuevo ? 'La tienda vuelve a cotizar con la API de Correo Argentino.' : 'Cotización por API apagada: la tienda usa el tarifario manual de Envíos.';
        $this->tipoMensaje = 'success';
        $this->cargar();
    }

    public function quitarCorreo()
    {
        $this->conectar();
        (new TiendaIntegracionService($this->tablePrefix))->eliminar(TiendaIntegracionService::CORREO);
        $this->correoPassword = '';
        $this->correoMicorreoPassword = '';
        $this->correoResultadoPrueba = [];
        $this->mensaje = 'Se quitó la cuenta de Correo Argentino. La tienda sigue cotizando con el tarifario manual de Envíos.';
        $this->tipoMensaje = 'success';
        $this->cargar();
    }

    public function render()
    {
        return view('panel.configurar-integraciones');
    }
}
