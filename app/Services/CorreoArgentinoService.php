<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Cliente de la API MiCorreo de Correo Argentino (cotizacion, sucursales e
 * importacion de envios). Portado de jugosandia; aca la cuenta es de cada
 * comercio y las credenciales llegan siempre por constructor (las guarda
 * cifradas TiendaIntegracionService, servicio 'correo').
 *
 * Flujo: POST /token con Basic Auth devuelve un JWT con vencimiento, que se
 * cachea y se manda como Bearer en el resto de las llamadas. Las cotizaciones
 * tambien se cachean hasta el validTo que informa el Correo, asi el carrito y
 * el checkout ven el mismo precio. Todas las claves de cache llevan la cuenta
 * (usuario o customerId): dos comercios nunca comparten token ni tarifas.
 */
class CorreoArgentinoService
{
    public const ENTREGA_DOMICILIO = 'D';
    public const ENTREGA_SUCURSAL = 'S';

    private const MARGEN_TOKEN_SEGUNDOS = 120;
    private const TTL_TOKEN_SIN_FECHA_SEGUNDOS = 30 * 60;
    private const TTL_COTIZACION_SIN_FECHA_SEGUNDOS = 15 * 60;

    private string $env;
    private string $usuario;
    private string $password;
    private string $customerId;

    /**
     * @param  array{env?: string, usuario?: string, password?: string, customer_id?: string}  $credenciales
     */
    public function __construct(array $credenciales)
    {
        $this->env = (string) ($credenciales['env'] ?? 'produccion');
        $this->usuario = trim((string) ($credenciales['usuario'] ?? ''));
        $this->password = (string) ($credenciales['password'] ?? '');
        $this->customerId = trim((string) ($credenciales['customer_id'] ?? ''));

        if (! array_key_exists($this->env, (array) config('correo.urls'))) {
            $this->env = 'produccion';
        }
    }

    /** Hay todo lo necesario para cotizar: credenciales de API y Customer ID. */
    public function configurado(): bool
    {
        return $this->tieneCredenciales() && $this->customerId !== '';
    }

    public function tieneCredenciales(): bool
    {
        return $this->usuario !== '' && $this->password !== '';
    }

    public function env(): string
    {
        return $this->env;
    }

    public function customerId(): string
    {
        return $this->customerId;
    }

    public function urlBase(): string
    {
        return rtrim((string) config("correo.urls.{$this->env}"), '/');
    }

    /**
     * JWT vigente (cacheado). Pide uno nuevo si no hay o esta por vencer.
     *
     * @return array{token: string, expires: ?string}
     *
     * @throws CorreoArgentinoException
     */
    public function token(bool $forzar = false): array
    {
        if (! $this->tieneCredenciales()) {
            throw new CorreoArgentinoException('Faltan el usuario y la contraseña de la API de Correo Argentino.');
        }

        $clave = $this->claveCacheToken();

        if (! $forzar) {
            $guardado = Cache::get($clave);

            if (is_array($guardado) && ! empty($guardado['token'])) {
                return $guardado;
            }
        }

        $respuesta = $this->enviar(fn (PendingRequest $http) => $http
            ->withBasicAuth($this->usuario, $this->password)
            ->post($this->urlBase() . '/token'));

        if ($respuesta->status() === 401) {
            throw new CorreoArgentinoException(
                'Correo Argentino rechazó el usuario y la contraseña de la API (401). Revisá las credenciales y el ambiente elegido.'
            );
        }

        $this->verificarRespuesta($respuesta, 'obtener el token');

        $token = (string) $respuesta->json('token', '');

        if ($token === '') {
            throw new CorreoArgentinoException('Correo Argentino respondió sin token: ' . $this->resumenCuerpo($respuesta));
        }

        $datos = ['token' => $token, 'expires' => $respuesta->json('expires')];

        Cache::put($clave, $datos, $this->segundosHastaVencimiento($datos['expires'], self::TTL_TOKEN_SIN_FECHA_SEGUNDOS, self::MARGEN_TOKEN_SEGUNDOS));

        return $datos;
    }

    public function olvidarToken(): void
    {
        Cache::forget($this->claveCacheToken());
    }

    /**
     * Devuelve el Customer ID de una cuenta MiCorreo a partir del email y la
     * contraseña con los que el comercio entra a la web de MiCorreo. La
     * contraseña se usa solo para esta llamada, no se guarda.
     *
     * @return array{customerId: string, createdAt: ?string}
     *
     * @throws CorreoArgentinoException
     */
    public function validarUsuario(string $email, string $password): array
    {
        $email = trim($email);

        if ($email === '' || $password === '') {
            throw new CorreoArgentinoException('Faltan el email y la contraseña de la cuenta MiCorreo.');
        }

        $respuesta = $this->autenticado('post', '/users/validate', [
            'email' => $email,
            'password' => $password,
        ]);

        if ($respuesta->status() === 404) {
            throw new CorreoArgentinoException(
                'MiCorreo no reconoce ese email y contraseña (' . $this->mensajeDeError($respuesta) . ').'
            );
        }

        $this->verificarRespuesta($respuesta, 'validar la cuenta MiCorreo');

        $customerId = trim((string) $respuesta->json('customerId', ''));

        if ($customerId === '') {
            throw new CorreoArgentinoException('MiCorreo respondió sin Customer ID: ' . $this->resumenCuerpo($respuesta));
        }

        return ['customerId' => $customerId, 'createdAt' => $respuesta->json('createdAt')];
    }

    /**
     * Cotiza un bulto. Sin tipo de entrega devuelve domicilio y sucursal juntos.
     *
     * @param  array{peso_gramos: int, ancho_cm: int, alto_cm: int, largo_cm: int}  $bulto
     * @return array{
     *     valid_to: ?string,
     *     rates: list<array{deliveredType: string, productType: ?string, productName: ?string, price: float, deliveryTimeMin: mixed, deliveryTimeMax: mixed}>
     * }
     *
     * @throws CorreoArgentinoException
     */
    public function cotizar(string $cpOrigen, string $cpDestino, array $bulto, ?string $tipoEntrega = null): array
    {
        if ($this->customerId === '') {
            throw new CorreoArgentinoException('Falta el Customer ID de la cuenta MiCorreo.');
        }

        $origen = self::normalizarCodigoPostal($cpOrigen);
        $destino = self::normalizarCodigoPostal($cpDestino);

        if ($origen === null) {
            throw new CorreoArgentinoException('El código postal de origen (el del local, en Envíos) no es válido.');
        }

        if ($destino === null) {
            throw new CorreoArgentinoException('El código postal de destino no es válido.');
        }

        $bulto = self::normalizarBulto($bulto);

        $cuerpo = [
            'customerId' => $this->customerId,
            'postalCodeOrigin' => $origen,
            'postalCodeDestination' => $destino,
            'dimensions' => [
                'weight' => $bulto['peso_gramos'],
                'height' => $bulto['alto_cm'],
                'width' => $bulto['ancho_cm'],
                'length' => $bulto['largo_cm'],
            ],
        ];

        if ($tipoEntrega !== null) {
            $cuerpo['deliveredType'] = $tipoEntrega;
        }

        // customerId va en el cuerpo: la clave ya es por cuenta
        $claveCache = 'correo.cotizacion.' . md5($this->env . '|' . json_encode($cuerpo));
        $cacheada = Cache::get($claveCache);

        if (is_array($cacheada)) {
            return $cacheada;
        }

        $respuesta = $this->autenticado('post', '/rates', $cuerpo);
        $this->verificarRespuesta($respuesta, 'cotizar el envío');

        $rates = [];

        foreach ((array) $respuesta->json('rates', []) as $rate) {
            if (! is_array($rate) || ! isset($rate['price'])) {
                continue;
            }

            $rates[] = [
                'deliveredType' => (string) ($rate['deliveredType'] ?? ''),
                'productType' => $rate['productType'] ?? null,
                'productName' => $rate['productName'] ?? null,
                'price' => (float) $rate['price'],
                'deliveryTimeMin' => $rate['deliveryTimeMin'] ?? null,
                'deliveryTimeMax' => $rate['deliveryTimeMax'] ?? null,
            ];
        }

        if ($rates === []) {
            throw new CorreoArgentinoException('Correo Argentino no devolvió tarifas para ese destino: ' . $this->resumenCuerpo($respuesta));
        }

        $resultado = ['valid_to' => $respuesta->json('validTo'), 'rates' => $rates];

        Cache::put($claveCache, $resultado, $this->segundosHastaVencimiento($resultado['valid_to'], self::TTL_COTIZACION_SIN_FECHA_SEGUNDOS, 0));

        return $resultado;
    }

    /**
     * Sucursales activas de una provincia (cacheadas 12 horas por cuenta).
     *
     * @return list<array<string, mixed>>
     *
     * @throws CorreoArgentinoException
     */
    public function sucursales(string $codigoProvincia): array
    {
        $codigoProvincia = strtoupper(trim($codigoProvincia));

        if (! isset(self::PROVINCIAS[$codigoProvincia])) {
            throw new CorreoArgentinoException('El código de provincia no es válido.');
        }

        if ($this->customerId === '') {
            throw new CorreoArgentinoException('Falta el Customer ID de la cuenta MiCorreo.');
        }

        $claveCache = "correo.sucursales.{$this->env}.{$this->customerId}.{$codigoProvincia}";
        $cacheadas = Cache::get($claveCache);

        if (is_array($cacheadas)) {
            return $cacheadas;
        }

        $respuesta = $this->autenticado('get', '/agencies', [
            'customerId' => $this->customerId,
            'provinceCode' => $codigoProvincia,
        ]);

        $this->verificarRespuesta($respuesta, 'listar las sucursales');

        $sucursales = [];

        foreach ((array) $respuesta->json() as $sucursal) {
            if (! is_array($sucursal) || empty($sucursal['code'])) {
                continue;
            }

            if (isset($sucursal['status']) && strtoupper((string) $sucursal['status']) !== 'ACTIVE') {
                continue;
            }

            $direccion = (array) ($sucursal['location']['address'] ?? []);

            $sucursales[] = [
                'code' => (string) $sucursal['code'],
                'name' => trim((string) ($sucursal['name'] ?? '')),
                'streetName' => $direccion['streetName'] ?? null,
                'streetNumber' => $direccion['streetNumber'] ?? null,
                'locality' => $direccion['locality'] ?? null,
                'city' => $direccion['city'] ?? null,
                'postalCode' => $direccion['postalCode'] ?? null,
                'hours' => $sucursal['hours'] ?? null,
                'pickupAvailability' => (bool) ($sucursal['services']['pickupAvailability'] ?? true),
            ];
        }

        usort($sucursales, fn (array $a, array $b) => strcasecmp($a['name'], $b['name']));

        Cache::put($claveCache, $sucursales, 12 * 60 * 60);

        return $sucursales;
    }

    /**
     * Importa un envio a la cuenta MiCorreo del comercio: queda listo en la web
     * de MiCorreo para pagarlo e imprimir el rotulo. El remitente va en null
     * para que MiCorreo use los datos del perfil de la cuenta.
     *
     * @param  array{
     *     ext_order_id: string, order_number: string,
     *     destinatario: array{nombre: string, telefono?: ?string, celular?: ?string, email?: ?string},
     *     tipo_entrega: string, sucursal?: ?string,
     *     direccion: array{calle?: ?string, numero?: ?string, piso?: ?string, depto?: ?string, localidad?: ?string, provincia?: ?string, codigo_postal?: ?string},
     *     bulto: array{peso_gramos: int, ancho_cm: int, alto_cm: int, largo_cm: int},
     *     valor_declarado: float
     * }  $envio
     * @return array{createdAt: ?string}
     *
     * @throws CorreoArgentinoException
     */
    public function importarEnvio(array $envio): array
    {
        if ($this->customerId === '') {
            throw new CorreoArgentinoException('Falta el Customer ID de la cuenta MiCorreo.');
        }

        $tipoEntrega = $envio['tipo_entrega'] === self::ENTREGA_SUCURSAL ? self::ENTREGA_SUCURSAL : self::ENTREGA_DOMICILIO;
        $direccion = (array) ($envio['direccion'] ?? []);
        $bulto = self::normalizarBulto((array) $envio['bulto']);

        if ($tipoEntrega === self::ENTREGA_SUCURSAL && empty($envio['sucursal'])) {
            throw new CorreoArgentinoException('Para un envío a sucursal hace falta el código de la sucursal.');
        }

        if ($tipoEntrega === self::ENTREGA_DOMICILIO) {
            foreach (['calle' => 'la calle', 'numero' => 'el número', 'localidad' => 'la localidad', 'provincia' => 'la provincia', 'codigo_postal' => 'el código postal'] as $campo => $nombre) {
                if (trim((string) ($direccion[$campo] ?? '')) === '') {
                    throw new CorreoArgentinoException("Para un envío a domicilio falta {$nombre} del destinatario.");
                }
            }
        }

        $codigoPostal = self::normalizarCodigoPostal($direccion['codigo_postal'] ?? null);

        $cuerpo = [
            'customerId' => $this->customerId,
            'extOrderId' => (string) $envio['ext_order_id'],
            'orderNumber' => (string) $envio['order_number'],
            'sender' => [
                'name' => null, 'phone' => null, 'cellPhone' => null, 'email' => null,
                'originAddress' => [
                    'streetName' => null, 'streetNumber' => null, 'floor' => null, 'apartment' => null,
                    'city' => null, 'provinceCode' => null, 'postalCode' => null,
                ],
            ],
            'recipient' => [
                'name' => mb_substr(trim((string) $envio['destinatario']['nombre']), 0, 100),
                'phone' => (string) ($envio['destinatario']['telefono'] ?? ''),
                'cellPhone' => (string) ($envio['destinatario']['celular'] ?? $envio['destinatario']['telefono'] ?? ''),
                'email' => (string) ($envio['destinatario']['email'] ?? ''),
            ],
            'shipping' => [
                'deliveryType' => $tipoEntrega,
                'agency' => $tipoEntrega === self::ENTREGA_SUCURSAL ? (string) $envio['sucursal'] : null,
                'address' => [
                    'streetName' => (string) ($direccion['calle'] ?? ''),
                    'streetNumber' => (string) ($direccion['numero'] ?? ''),
                    'floor' => mb_substr((string) ($direccion['piso'] ?? ''), 0, 3),
                    'apartment' => mb_substr((string) ($direccion['depto'] ?? ''), 0, 3),
                    'city' => (string) ($direccion['localidad'] ?? ''),
                    'provinceCode' => strtoupper((string) ($direccion['provincia'] ?? '')),
                    'postalCode' => $codigoPostal ?? (string) ($direccion['codigo_postal'] ?? ''),
                ],
                'weight' => $bulto['peso_gramos'],
                'declaredValue' => round((float) $envio['valor_declarado'], 2),
                'height' => $bulto['alto_cm'],
                'length' => $bulto['largo_cm'],
                'width' => $bulto['ancho_cm'],
            ],
        ];

        $respuesta = $this->autenticado('post', '/shipping/import', $cuerpo);
        $this->verificarRespuesta($respuesta, 'importar el envío a MiCorreo');

        return ['createdAt' => $respuesta->json('createdAt')];
    }

    /** Provincias con el codigo de una letra que usa la API. */
    public const PROVINCIAS = [
        'C' => 'Ciudad Autónoma de Buenos Aires',
        'B' => 'Buenos Aires',
        'K' => 'Catamarca',
        'H' => 'Chaco',
        'U' => 'Chubut',
        'X' => 'Córdoba',
        'W' => 'Corrientes',
        'E' => 'Entre Ríos',
        'P' => 'Formosa',
        'Y' => 'Jujuy',
        'L' => 'La Pampa',
        'F' => 'La Rioja',
        'M' => 'Mendoza',
        'N' => 'Misiones',
        'Q' => 'Neuquén',
        'R' => 'Río Negro',
        'A' => 'Salta',
        'J' => 'San Juan',
        'D' => 'San Luis',
        'Z' => 'Santa Cruz',
        'S' => 'Santa Fe',
        'G' => 'Santiago del Estero',
        'V' => 'Tierra del Fuego',
        'T' => 'Tucumán',
    ];

    public static function nombreProvincia(?string $codigo): ?string
    {
        return $codigo ? (self::PROVINCIAS[strtoupper($codigo)] ?? null) : null;
    }

    /**
     * Codigo de una letra a partir del nombre como lo escribe el comprador
     * ("Buenos Aires", "CABA", "Capital Federal", "Cordoba"). Null si no se reconoce.
     */
    public static function codigoProvincia(?string $nombre): ?string
    {
        $n = TiendaEnvioService::normalizarProvincia($nombre);
        if ($n === '') {
            return null;
        }
        if ($n === 'caba') {
            return 'C';
        }
        if (strlen($n) === 1 && isset(self::PROVINCIAS[strtoupper($n)])) {
            return strtoupper($n);
        }
        foreach (self::PROVINCIAS as $codigo => $oficial) {
            if (TiendaEnvioService::normalizarProvincia($oficial) === $n) {
                return $codigo;
            }
        }

        return null;
    }

    /**
     * La tarifa mas barata para un tipo de entrega, o null si no vino.
     *
     * @param  array{rates: list<array<string, mixed>>}  $cotizacion
     */
    public static function tarifaPara(array $cotizacion, string $tipoEntrega): ?array
    {
        $candidatas = array_filter($cotizacion['rates'], fn (array $r) => $r['deliveredType'] === $tipoEntrega);

        if ($candidatas === []) {
            return null;
        }

        usort($candidatas, fn (array $a, array $b) => $a['price'] <=> $b['price']);

        return $candidatas[0];
    }

    /**
     * La API espera el CP numerico de 4 digitos: acepta "1425", "C1425ABC"
     * o "B 1842 ZAB" y devuelve "1425"/"1842". Null si no hay 4 digitos.
     */
    public static function normalizarCodigoPostal(?string $cp): ?string
    {
        if ($cp === null) {
            return null;
        }

        return preg_match('/(\d{4})/', $cp, $m) ? $m[1] : null;
    }

    /**
     * Enteros dentro de los limites de /rates. Lo que falte o sea cero se
     * reemplaza por el bulto por defecto de config/correo.php.
     *
     * @return array{peso_gramos: int, ancho_cm: int, alto_cm: int, largo_cm: int}
     */
    public static function normalizarBulto(array $bulto): array
    {
        $defecto = (array) config('correo.bulto_por_defecto');
        $limites = (array) config('correo.limites');
        $ladoMax = (int) ($limites['lado_max_cm'] ?? 150);

        $peso = (int) ceil((float) ($bulto['peso_gramos'] ?? 0));
        $peso = $peso > 0 ? $peso : (int) ($defecto['peso_gramos'] ?? 500);
        $peso = max((int) ($limites['peso_min_gramos'] ?? 1), min($peso, (int) ($limites['peso_max_gramos'] ?? 25000)));

        $lado = function (string $campo) use ($bulto, $defecto, $ladoMax): int {
            $valor = (int) ceil((float) ($bulto[$campo] ?? 0));
            $valor = $valor > 0 ? $valor : (int) ($defecto[$campo] ?? 10);

            return max(1, min($valor, $ladoMax));
        };

        return [
            'peso_gramos' => $peso,
            'ancho_cm' => $lado('ancho_cm'),
            'alto_cm' => $lado('alto_cm'),
            'largo_cm' => $lado('largo_cm'),
        ];
    }

    /**
     * Llamada con Bearer. Si el token cacheado ya no sirve (401), pide uno
     * nuevo y reintenta una sola vez.
     */
    private function autenticado(string $metodo, string $ruta, array $cuerpo): Response
    {
        $respuesta = $this->conToken($this->token()['token'], $metodo, $ruta, $cuerpo);

        if ($respuesta->status() !== 401) {
            return $respuesta;
        }

        return $this->conToken($this->token(true)['token'], $metodo, $ruta, $cuerpo);
    }

    private function conToken(string $token, string $metodo, string $ruta, array $cuerpo): Response
    {
        return $this->enviar(fn (PendingRequest $http) => $http
            ->withToken($token)
            ->acceptJson()
            ->{$metodo}($this->urlBase() . $ruta, $cuerpo));
    }

    /**
     * @param  callable(PendingRequest): Response  $llamada
     */
    private function enviar(callable $llamada): Response
    {
        $http = Http::timeout((int) config('correo.timeout', 15))
            ->connectTimeout(min(10, (int) config('correo.timeout', 15)));

        try {
            return $llamada($http);
        } catch (ConnectionException $e) {
            throw new CorreoArgentinoException(
                'No se pudo conectar con Correo Argentino (' . parse_url($this->urlBase(), PHP_URL_HOST) . '): ' . $e->getMessage(),
                0,
                $e
            );
        } catch (Throwable $e) {
            throw new CorreoArgentinoException('Error al llamar a Correo Argentino: ' . $e->getMessage(), 0, $e);
        }
    }

    private function verificarRespuesta(Response $respuesta, string $accion): void
    {
        if ($respuesta->successful()) {
            return;
        }

        $estado = $respuesta->status();
        $detalle = $this->mensajeDeError($respuesta);

        $mensaje = match (true) {
            $estado === 401 => 'el token de acceso no es válido',
            $estado === 403 => 'las credenciales no tienen permiso para esta operación',
            $estado === 429 => 'demasiadas consultas seguidas, reintentá en unos segundos',
            $estado >= 500 => 'el servicio de Correo Argentino no está disponible',
            default => $detalle,
        };

        throw new CorreoArgentinoException("No se pudo {$accion}: {$mensaje} (HTTP {$estado}" . ($detalle !== '' && $detalle !== $mensaje ? ", {$detalle}" : '') . ').');
    }

    private function mensajeDeError(Response $respuesta): string
    {
        $mensaje = $respuesta->json('message');

        if (is_string($mensaje) && trim($mensaje) !== '') {
            return trim($mensaje);
        }

        return $this->resumenCuerpo($respuesta);
    }

    private function resumenCuerpo(Response $respuesta): string
    {
        $cuerpo = trim(strip_tags((string) $respuesta->body()));

        return $cuerpo === '' ? 'respuesta vacía' : mb_substr(preg_replace('/\s+/', ' ', $cuerpo), 0, 200);
    }

    private function claveCacheToken(): string
    {
        return 'correo.token.' . md5($this->env . '|' . $this->usuario . '|' . $this->password);
    }

    /**
     * Segundos hasta una fecha que informa el Correo (hora de Argentina),
     * menos un margen. Si no hay fecha o ya paso, el TTL por defecto.
     */
    private function segundosHastaVencimiento(?string $fecha, int $porDefecto, int $margen): int
    {
        if (! $fecha) {
            return $porDefecto;
        }

        try {
            $vence = Carbon::parse($fecha, 'America/Argentina/Buenos_Aires');
        } catch (Throwable) {
            return $porDefecto;
        }

        $segundos = $vence->getTimestamp() - time() - $margen;

        return $segundos > 60 ? $segundos : $porDefecto;
    }
}
