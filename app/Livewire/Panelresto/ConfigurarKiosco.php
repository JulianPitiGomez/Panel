<?php

namespace App\Livewire\Panelresto;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Services\MercadoPagoService;

class ConfigurarKiosco extends Component
{
    // Configuración Kiosco
    public $mpAccessToken = '';
    public $kioscoPassword = '';
    public $kioscoImpresora = '';
    public $kioscoFormasPago = [];
    public $kioscoSugerencias = [];
    public $kioscoSugerenciasCantidad = 4;
    public $guardado = false;

    // Mercado Pago - Tipo de integración
    public $mpTipoIntegracion = 'qr_presencial'; // qr_presencial | point | checkout_pro
    public $mpUserId = null;
    public $mpUserNombre = null;

    // QR Presencial
    public $mpStoreId = null;
    public $mpStoreExternalId = null;
    public $mpPosId = null;
    public $mpPosExternalId = null;
    public $mpPosQrImage = null;
    public $mpConfigAvanzada = false;
    public $mpManualStoreId = '';
    public $mpManualPosId = '';

    // Datos del local (para crear sucursal en MP)
    public $mpStoreName = 'Kiosco Autoservicio';
    public $mpStoreStreetName = '';
    public $mpStoreStreetNumber = '';
    public $mpStoreCityName = '';
    public $mpStoreStateName = '';
    public $mpStoreLatitude = '';
    public $mpStoreLongitude = '';

    // Búsqueda de existentes
    public $mpSucursalesExistentes = [];
    public $mpCajasExistentes = [];

    // Point
    public $mpPointDeviceId = null;
    public $mpPointDevices = [];
    public $mpPointDeviceNombre = null;

    // Estado UI MP
    public $mpValidando = false;
    public $mpTokenValido = null;
    public $mpCreandoQR = false;
    public $mpBuscandoExistentes = false;
    public $mpBuscandoDevices = false;
    public $mpMensaje = '';
    public $mpMensajeTipo = '';

    // Personalización
    public $kioscoTituloDestacados = 'Productos Destacados';
    public $kioscoPideNombre = true;
    public $kioscoPideTelefono = true;
    public $kioscoImprimeTicket = true;

    // Colores
    public $kioscoColorFondo = '#FFFFFF';
    public $kioscoColorLetra = '#1F2937';
    public $kioscoColorBoton = '#F97316';
    public $kioscoColorTextoBoton = '#FFFFFF';
    public $kioscoColorPrecio = '#16A34A';

    // Datos auxiliares
    public $formasPagoDisponibles = [];
    public $deptosDisponibles = [];
    public $mostrarGuiaMP = false;

    private $tablePrefix;

    public function mount()
    {
        $this->setupClientDatabase();
        $this->cargarConfigKiosco();
    }

    private function setupClientDatabase()
    {
        $clientId = session('client_id');
        if ($clientId) {
            $cliente = \App\Models\Cliente::find($clientId);

            if ($cliente) {
                \Illuminate\Support\Facades\Config::set('database.connections.client_db.database', $cliente->base);
                DB::purge('client_db');
                session(['client_table_prefix' => $cliente->getTablePrefix()]);
                $this->tablePrefix = $cliente->getTablePrefix();
            }
        }
    }

    private function cargarConfigKiosco()
    {
        $clientId = session('client_id');

        // Cargar formas de pago táctiles disponibles
        if ($this->tablePrefix) {
            $this->formasPagoDisponibles = DB::connection('client_db')
                ->table($this->tablePrefix . 'forpag')
                ->where('TACTIL', 1)
                ->where('TIPO', '!=', 3)
                ->get()
                ->toArray();

            // Cargar departamentos de mostrador para config de sugerencias
            $this->deptosDisponibles = DB::connection('client_db')
                ->table($this->tablePrefix . 'deptos')
                ->where('lMostrador', 1)
                ->orderBy('orden')
                ->get()
                ->toArray();
        }

        $registro = DB::connection('config')
            ->table('config_clientes_web')
            ->where('cliente_id', $clientId)
            ->first();

        if ($registro && $registro->configuracion) {
            $config = json_decode($registro->configuracion, true);
            if (is_array($config)) {
                $this->mpAccessToken = $config['mp_access_token'] ?? '';
                $this->kioscoPassword = $config['kiosco_password'] ?? '';
                $this->kioscoImpresora = $config['kiosco_impresora'] ?? '';
                $this->kioscoFormasPago = $config['kiosco_formas_pago'] ?? [];
                $this->kioscoSugerencias = $config['kiosco_sugerencias'] ?? [];
                $this->kioscoSugerenciasCantidad = $config['kiosco_sugerencias_cantidad'] ?? 4;

                // MP Integración
                $this->mpTipoIntegracion = $config['mp_tipo_integracion'] ?? 'qr_presencial';
                $this->mpUserId = $config['mp_user_id'] ?? null;
                $this->mpUserNombre = $config['mp_user_nombre'] ?? null;
                $this->mpStoreId = $config['mp_store_id'] ?? null;
                $this->mpStoreExternalId = $config['mp_store_external_id'] ?? null;
                $this->mpPosId = $config['mp_pos_id'] ?? null;
                $this->mpPosExternalId = $config['mp_pos_external_id'] ?? null;
                $this->mpPosQrImage = $config['mp_pos_qr_image'] ?? null;
                $this->mpPointDeviceId = $config['mp_point_device_id'] ?? null;
                $this->mpPointDeviceNombre = $config['mp_point_device_nombre'] ?? null;

                // Datos del local
                $this->mpStoreName = $config['mp_store_name'] ?? 'Kiosco Autoservicio';
                $this->mpStoreStreetName = $config['mp_store_street_name'] ?? '';
                $this->mpStoreStreetNumber = $config['mp_store_street_number'] ?? '';
                $this->mpStoreCityName = $config['mp_store_city_name'] ?? '';
                $this->mpStoreStateName = $config['mp_store_state_name'] ?? '';
                $this->mpStoreLatitude = $config['mp_store_latitude'] ?? '';
                $this->mpStoreLongitude = $config['mp_store_longitude'] ?? '';

                // Si tiene userId, el token fue validado previamente
                if ($this->mpUserId) {
                    $this->mpTokenValido = true;
                }

                // Personalización
                $this->kioscoTituloDestacados = $config['kiosco_titulo_destacados'] ?? 'Productos Destacados';
                $this->kioscoPideNombre = $config['kiosco_pide_nombre'] ?? true;
                $this->kioscoPideTelefono = $config['kiosco_pide_telefono'] ?? true;
                $this->kioscoImprimeTicket = $config['kiosco_imprime_ticket'] ?? true;

                // Colores
                $this->kioscoColorFondo = $config['kiosco_color_fondo'] ?? '#FFFFFF';
                $this->kioscoColorLetra = $config['kiosco_color_letra'] ?? '#1F2937';
                $this->kioscoColorBoton = $config['kiosco_color_boton'] ?? '#F97316';
                $this->kioscoColorTextoBoton = $config['kiosco_color_texto_boton'] ?? '#FFFFFF';
                $this->kioscoColorPrecio = $config['kiosco_color_precio'] ?? '#16A34A';
            }
        }
    }

    // =============================================
    // Mercado Pago - Validación y configuración
    // =============================================

    public function validarToken()
    {
        if (empty($this->mpAccessToken)) {
            $this->setMpMensaje('Ingresá un Access Token', 'error');
            return;
        }

        $this->mpValidando = true;
        $this->mpMensaje = '';

        $mp = new MercadoPagoService($this->mpAccessToken);
        $usuario = $mp->obtenerUsuario();

        $this->mpValidando = false;

        if ($usuario) {
            $this->mpUserId = $usuario['id'];
            $this->mpUserNombre = trim(($usuario['first_name'] ?? '') . ' ' . ($usuario['last_name'] ?? '')) ?: $usuario['nickname'];
            $this->mpTokenValido = true;
            $this->guardarConfigKiosco();
            $this->setMpMensaje('Token válido - Cuenta: ' . $this->mpUserNombre . ' (ID: ' . $this->mpUserId . ')', 'success');
        } else {
            $this->mpUserId = null;
            $this->mpUserNombre = null;
            $this->mpTokenValido = false;
            $this->setMpMensaje('Token inválido o error de conexión con Mercado Pago', 'error');
        }
    }

    public function buscarExistentes()
    {
        if (!$this->mpUserId) {
            $this->setMpMensaje('Primero validá el Access Token', 'error');
            return;
        }

        $this->mpBuscandoExistentes = true;
        $this->mpMensaje = '';

        $mp = new MercadoPagoService($this->mpAccessToken);

        $this->mpSucursalesExistentes = $mp->obtenerSucursales($this->mpUserId);
        $this->mpCajasExistentes = $mp->obtenerCajas();

        $this->mpBuscandoExistentes = false;

        $totalS = count($this->mpSucursalesExistentes);
        $totalC = count($this->mpCajasExistentes);

        if ($totalS === 0 && $totalC === 0) {
            $this->setMpMensaje('No se encontraron sucursales ni cajas en la cuenta de Mercado Pago', 'error');
        } else {
            $this->setMpMensaje("{$totalS} sucursal(es) y {$totalC} caja(s) encontrada(s) - Seleccioná la que quieras usar", 'success');
        }
    }

    public function seleccionarSucursal($index)
    {
        $store = $this->mpSucursalesExistentes[$index] ?? null;
        if (!$store) return;

        $this->mpStoreId = $store['id'];
        $this->mpStoreExternalId = $store['external_id'] ?? '';
        $this->mpStoreName = $store['name'] ?? $this->mpStoreName;
    }

    public function seleccionarCaja($index)
    {
        $caja = $this->mpCajasExistentes[$index] ?? null;
        if (!$caja) return;

        $this->mpPosId = $caja['id'];
        $this->mpPosExternalId = $caja['external_id'] ?? '';
        $this->mpPosQrImage = $caja['qr']['image'] ?? $caja['qr']['template_image'] ?? null;

        $this->mpSucursalesExistentes = [];
        $this->mpCajasExistentes = [];
        $this->guardarConfigKiosco();
        $this->setMpMensaje('Caja seleccionada correctamente (guardado automático)', 'success');
    }

    public function crearSucursalYCaja()
    {
        if (!$this->mpUserId) {
            $this->setMpMensaje('Primero validá el Access Token', 'error');
            return;
        }

        if (empty($this->mpStoreName)) {
            $this->setMpMensaje('Ingresá al menos el nombre del local', 'error');
            return;
        }

        $this->mpCreandoQR = true;
        $this->mpMensaje = '';

        $mp = new MercadoPagoService($this->mpAccessToken);
        $clientId = session('client_id');

        // Crear sucursal
        $storeExternalId = 'KIOSCOSTORE' . $clientId;
        $resultado = $mp->crearSucursal($this->mpUserId, [
            'name' => $this->mpStoreName,
            'external_id' => $storeExternalId,
            'street_name' => $this->mpStoreStreetName,
            'street_number' => $this->mpStoreStreetNumber ?: '0',
            'city_name' => $this->mpStoreCityName,
            'state_name' => $this->mpStoreStateName,
            'latitude' => $this->mpStoreLatitude,
            'longitude' => $this->mpStoreLongitude,
        ]);

        $sucursal = null;

        if ($resultado['success']) {
            $sucursal = $resultado['data'];
        } else {
            // Puede que ya exista (error 409 o similar), intentar buscarla
            $sucursales = $mp->obtenerSucursales($this->mpUserId);
            foreach ($sucursales as $s) {
                if (($s['external_id'] ?? '') === $storeExternalId) {
                    $sucursal = [
                        'id' => $s['id'],
                        'name' => $s['name'],
                        'external_id' => $s['external_id'],
                    ];
                    break;
                }
            }

            if (!$sucursal) {
                $this->mpCreandoQR = false;
                $this->setMpMensaje('Error al crear sucursal: ' . ($resultado['error'] ?? 'Error desconocido'), 'error');
                return;
            }
        }

        $this->mpStoreId = $sucursal['id'];
        $this->mpStoreExternalId = $sucursal['external_id'];

        // Crear caja/POS
        $posExternalId = 'KIOSCOPOS' . $clientId;
        $resultadoCaja = $mp->crearCaja([
            'name' => $this->mpStoreName,
            'external_id' => $posExternalId,
            'store_id' => $this->mpStoreId,
            'external_store_id' => $this->mpStoreExternalId,
        ]);

        $caja = null;

        if ($resultadoCaja['success']) {
            $caja = $resultadoCaja['data'];
        } else {
            // Puede que ya exista, intentar buscarla
            $cajas = $mp->obtenerCajas($this->mpStoreExternalId);
            foreach ($cajas as $c) {
                if (($c['external_id'] ?? '') === $posExternalId) {
                    $caja = [
                        'id' => $c['id'],
                        'name' => $c['name'],
                        'external_id' => $c['external_id'],
                        'qr' => $c['qr']['image'] ?? null,
                        'qr_template' => $c['qr']['template_document'] ?? null,
                        'qr_template_image' => $c['qr']['template_image'] ?? null,
                    ];
                    break;
                }
            }

            if (!$caja) {
                $this->mpCreandoQR = false;
                $this->setMpMensaje('Sucursal creada, pero error al crear caja: ' . ($resultadoCaja['error'] ?? 'Error desconocido'), 'error');
                return;
            }
        }

        $this->mpCreandoQR = false;
        $this->mpPosId = $caja['id'];
        $this->mpPosExternalId = $caja['external_id'];
        $this->mpPosQrImage = $caja['qr'] ?? $caja['qr_template_image'] ?? null;

        $this->guardarConfigKiosco();
        $this->setMpMensaje('Sucursal y caja creadas correctamente (guardado automático)', 'success');
    }

    public function aplicarConfigAvanzada()
    {
        if (empty($this->mpManualStoreId) || empty($this->mpManualPosId)) {
            $this->setMpMensaje('Completá ambos IDs (Sucursal y Caja)', 'error');
            return;
        }

        if (!$this->mpUserId) {
            $this->setMpMensaje('Primero validá el Access Token', 'error');
            return;
        }

        $this->mpStoreExternalId = $this->mpManualStoreId;
        $this->mpPosExternalId = $this->mpManualPosId;

        // Intentar obtener el QR de la caja
        $mp = new MercadoPagoService($this->mpAccessToken);
        $cajas = $mp->obtenerCajas($this->mpManualStoreId);
        foreach ($cajas as $c) {
            if (($c['external_id'] ?? '') === $this->mpManualPosId) {
                $this->mpPosId = $c['id'];
                $this->mpPosQrImage = $c['qr']['image'] ?? $c['qr']['template_image'] ?? null;
                break;
            }
        }

        $this->mpConfigAvanzada = false;
        $this->guardarConfigKiosco();
        $this->setMpMensaje('IDs aplicados correctamente (guardado automático)', 'success');
    }

    public function cargarDispositivos()
    {
        if (empty($this->mpAccessToken)) {
            $this->setMpMensaje('Ingresá un Access Token primero', 'error');
            return;
        }

        $this->mpBuscandoDevices = true;
        $this->mpMensaje = '';

        $mp = new MercadoPagoService($this->mpAccessToken);
        $devices = $mp->obtenerDispositivos();

        $this->mpBuscandoDevices = false;
        $this->mpPointDevices = $devices;

        if (empty($devices)) {
            $this->setMpMensaje('No se encontraron dispositivos Point vinculados a esta cuenta', 'error');
        } else {
            $this->setMpMensaje(count($devices) . ' dispositivo(s) encontrado(s)', 'success');
        }
    }

    public function seleccionarDevice($deviceId)
    {
        $this->mpPointDeviceId = $deviceId;
        foreach ($this->mpPointDevices as $d) {
            if ($d['id'] === $deviceId) {
                $this->mpPointDeviceNombre = $d['id'];
                break;
            }
        }
    }

    public function testConexionPoint()
    {
        if (empty($this->mpPointDeviceId)) {
            $this->setMpMensaje('Seleccioná un dispositivo primero', 'error');
            return;
        }

        $mp = new MercadoPagoService($this->mpAccessToken);
        $devices = $mp->obtenerDispositivos();

        $encontrado = false;
        foreach ($devices as $d) {
            if ($d['id'] === $this->mpPointDeviceId) {
                $encontrado = true;
                $status = $d['operating_mode'] ?? 'desconocido';
                $this->setMpMensaje("Dispositivo encontrado - Estado: {$status}", 'success');
                break;
            }
        }

        if (!$encontrado) {
            $this->setMpMensaje('El dispositivo no responde o fue desvinculado', 'error');
        }
    }

    private function setMpMensaje(string $texto, string $tipo)
    {
        $this->mpMensaje = $texto;
        $this->mpMensajeTipo = $tipo;
    }

    // =============================================
    // Guardar configuración
    // =============================================

    public function guardarConfigKiosco()
    {
        $clientId = session('client_id');

        $registro = DB::connection('config')
            ->table('config_clientes_web')
            ->where('cliente_id', $clientId)
            ->first();

        $config = [];
        if ($registro && $registro->configuracion) {
            $config = json_decode($registro->configuracion, true) ?: [];
        }

        $config['mp_access_token'] = $this->mpAccessToken;
        $config['kiosco_password'] = $this->kioscoPassword;
        $config['kiosco_impresora'] = $this->kioscoImpresora;
        $config['kiosco_formas_pago'] = array_map('intval', $this->kioscoFormasPago);

        // MP Integración
        $config['mp_tipo_integracion'] = $this->mpTipoIntegracion;
        $config['mp_user_id'] = $this->mpUserId;
        $config['mp_user_nombre'] = $this->mpUserNombre;
        $config['mp_store_id'] = $this->mpStoreId;
        $config['mp_store_external_id'] = $this->mpStoreExternalId;
        $config['mp_pos_id'] = $this->mpPosId;
        $config['mp_pos_external_id'] = $this->mpPosExternalId;
        $config['mp_pos_qr_image'] = $this->mpPosQrImage;
        $config['mp_point_device_id'] = $this->mpPointDeviceId;
        $config['mp_point_device_nombre'] = $this->mpPointDeviceNombre;

        // Datos del local
        $config['mp_store_name'] = $this->mpStoreName;
        $config['mp_store_street_name'] = $this->mpStoreStreetName;
        $config['mp_store_street_number'] = $this->mpStoreStreetNumber;
        $config['mp_store_city_name'] = $this->mpStoreCityName;
        $config['mp_store_state_name'] = $this->mpStoreStateName;
        $config['mp_store_latitude'] = $this->mpStoreLatitude;
        $config['mp_store_longitude'] = $this->mpStoreLongitude;

        // Limpiar sugerencias vacías y convertir IDs a int
        $sugerenciasLimpias = [];
        foreach ($this->kioscoSugerencias as $deptoId => $deptosSugeridos) {
            $ids = array_map('intval', array_filter((array) $deptosSugeridos));
            if (!empty($ids)) {
                $sugerenciasLimpias[(string) $deptoId] = $ids;
            }
        }
        $config['kiosco_sugerencias'] = $sugerenciasLimpias;
        $config['kiosco_sugerencias_cantidad'] = (int) $this->kioscoSugerenciasCantidad;

        // Personalización
        $config['kiosco_titulo_destacados'] = $this->kioscoTituloDestacados;
        $config['kiosco_pide_nombre'] = (bool) $this->kioscoPideNombre;
        $config['kiosco_pide_telefono'] = (bool) $this->kioscoPideTelefono;
        $config['kiosco_imprime_ticket'] = (bool) $this->kioscoImprimeTicket;

        // Colores
        $config['kiosco_color_fondo'] = $this->kioscoColorFondo;
        $config['kiosco_color_letra'] = $this->kioscoColorLetra;
        $config['kiosco_color_boton'] = $this->kioscoColorBoton;
        $config['kiosco_color_texto_boton'] = $this->kioscoColorTextoBoton;
        $config['kiosco_color_precio'] = $this->kioscoColorPrecio;

        $json = json_encode($config, JSON_UNESCAPED_UNICODE);

        if ($registro) {
            DB::connection('config')
                ->table('config_clientes_web')
                ->where('cliente_id', $clientId)
                ->update(['configuracion' => $json]);
        } else {
            DB::connection('config')
                ->table('config_clientes_web')
                ->insert([
                    'cliente_id' => $clientId,
                    'configuracion' => $json,
                ]);
        }

        $this->guardado = true;
    }

    public function toggleSugerencia($deptoId, $deptoSugeridoId)
    {
        $deptoId = (string) $deptoId;

        if (!isset($this->kioscoSugerencias[$deptoId]) || !is_array($this->kioscoSugerencias[$deptoId])) {
            $this->kioscoSugerencias[$deptoId] = [];
        }

        $index = array_search($deptoSugeridoId, $this->kioscoSugerencias[$deptoId]);
        if ($index !== false) {
            array_splice($this->kioscoSugerencias[$deptoId], $index, 1);
            if (empty($this->kioscoSugerencias[$deptoId])) {
                unset($this->kioscoSugerencias[$deptoId]);
            }
        } else {
            $this->kioscoSugerencias[$deptoId][] = $deptoSugeridoId;
        }
    }

    public function restaurarColores()
    {
        $this->kioscoColorFondo = '#FFFFFF';
        $this->kioscoColorLetra = '#1F2937';
        $this->kioscoColorBoton = '#F97316';
        $this->kioscoColorTextoBoton = '#FFFFFF';
        $this->kioscoColorPrecio = '#16A34A';
    }

    public function render()
    {
        return view('panelresto.configurar-kiosco');
    }
}
