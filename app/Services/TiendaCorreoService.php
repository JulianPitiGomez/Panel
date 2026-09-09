<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Correo Argentino para la tienda de un comercio: une las credenciales cifradas
 * del comercio ({prefijo}tienda_integraciones, servicio 'correo') con el cliente
 * de la API MiCorreo. Mismo patron que TiendaWhatsappService: nunca lanza hacia
 * afuera, devuelve ['ok' => bool, ...] y deja el detalle en el log.
 *
 * Es opcional por comercio: si no cargo credenciales o las desactivo, la tienda
 * sigue cotizando con el tarifario manual de TiendaEnvioService.
 */
class TiendaCorreoService
{
    public const PROVEEDOR = 'correo_argentino';

    private string $prefijo;

    public function __construct(string $prefijo)
    {
        $this->prefijo = $prefijo;
    }

    /** Credenciales activas del comercio (env, usuario, password, customer_id), o null. */
    public function credenciales(): ?array
    {
        return (new TiendaIntegracionService($this->prefijo))->correo();
    }

    /** El comercio tiene la API cargada, activa y con Customer ID: se puede cotizar. */
    public function activo(): bool
    {
        $cred = $this->credenciales();

        return $cred !== null && (new CorreoArgentinoService($cred))->configurado();
    }

    public function cliente(?array $cred = null): CorreoArgentinoService
    {
        return new CorreoArgentinoService($cred ?? $this->credenciales() ?? []);
    }

    /**
     * Cotiza un bulto a domicilio y a sucursal en una sola llamada (queda cacheada).
     * Nunca lanza: si algo falla devuelve ok=false con el motivo, y el que llama
     * decide si cae al tarifario manual. Cada tarifa: importe, producto, demora.
     *
     * @param  array{peso_gramos: int|float, ancho_cm: int|float, alto_cm: int|float, largo_cm: int|float}  $bulto
     * @return array{ok: bool, domicilio?: ?array, sucursal?: ?array, valid_to?: ?string, error?: string}
     */
    public function cotizar(string $cpOrigen, ?string $cpDestino, array $bulto, ?array $cred = null): array
    {
        $cred ??= $this->credenciales();
        if (!$cred) {
            return ['ok' => false, 'error' => 'La API de Correo Argentino no está configurada.'];
        }
        if (CorreoArgentinoService::normalizarCodigoPostal($cpDestino) === null) {
            return ['ok' => false, 'error' => 'Hace falta el código postal de destino.'];
        }

        try {
            $correo = new CorreoArgentinoService($cred);
            $cotizacion = $correo->cotizar($cpOrigen, (string) $cpDestino, $bulto);
            $armar = function (?array $tarifa): ?array {
                return $tarifa === null ? null : [
                    'importe' => round((float) $tarifa['price'], 2),
                    'producto' => (string) ($tarifa['productName'] ?: 'Paq.ar'),
                    'demora' => self::textoDemora($tarifa['deliveryTimeMin'] ?? null, $tarifa['deliveryTimeMax'] ?? null),
                ];
            };
            $domicilio = $armar(CorreoArgentinoService::tarifaPara($cotizacion, CorreoArgentinoService::ENTREGA_DOMICILIO));
            $sucursal = $armar(CorreoArgentinoService::tarifaPara($cotizacion, CorreoArgentinoService::ENTREGA_SUCURSAL));
            if ($domicilio === null && $sucursal === null) {
                return ['ok' => false, 'error' => 'Correo Argentino no devolvió tarifas para ese código postal.'];
            }

            return ['ok' => true, 'domicilio' => $domicilio, 'sucursal' => $sucursal, 'valid_to' => $cotizacion['valid_to'] ?? null];
        } catch (CorreoArgentinoException $e) {
            Log::warning('TiendaCorreo: cotización falló', ['prefijo' => $this->prefijo, 'cp_destino' => $cpDestino, 'error' => $e->getMessage()]);

            return ['ok' => false, 'error' => $e->getMessage()];
        } catch (\Throwable $e) {
            Log::warning('TiendaCorreo: ' . $e->getMessage(), ['prefijo' => $this->prefijo]);

            return ['ok' => false, 'error' => 'No se pudo cotizar con Correo Argentino.'];
        }
    }

    /**
     * Prueba desde el panel con credenciales todavia no guardadas: token y una
     * cotizacion de un bulto chico al CP indicado.
     *
     * @return array{ok: bool, lineas?: list<string>, error?: string}
     */
    public function probar(array $cred, string $cpOrigen, string $cpDestino): array
    {
        try {
            $correo = new CorreoArgentinoService($cred);
            if (!$correo->tieneCredenciales()) {
                return ['ok' => false, 'error' => 'Faltan el usuario y la contraseña de la API.'];
            }
            $correo->olvidarToken();
            $token = $correo->token();
            $lineas = ['Credenciales de API aceptadas' . ($token['expires'] ? " (token válido hasta {$token['expires']})" : '') . '.'];

            if (!$correo->configurado()) {
                return ['ok' => false, 'error' => 'Las credenciales funcionan, pero falta el Customer ID: obtenelo con el email y la contraseña de MiCorreo.'];
            }

            $cotizacion = $correo->cotizar($cpOrigen, $cpDestino, ['peso_gramos' => 500, 'ancho_cm' => 20, 'alto_cm' => 10, 'largo_cm' => 30]);
            $origen = CorreoArgentinoService::normalizarCodigoPostal($cpOrigen);
            $destino = CorreoArgentinoService::normalizarCodigoPostal($cpDestino);
            $lineas[] = "Cotización de un bulto de 500 g del CP {$origen} al CP {$destino}:";
            foreach ($cotizacion['rates'] as $rate) {
                $entrega = $rate['deliveredType'] === CorreoArgentinoService::ENTREGA_SUCURSAL ? 'a sucursal' : 'a domicilio';
                $demora = self::textoDemora($rate['deliveryTimeMin'], $rate['deliveryTimeMax']);
                $lineas[] = '· ' . ($rate['productName'] ?: 'Paq.ar') . " {$entrega}: $ " . number_format($rate['price'], 2, ',', '.') . ($demora ? " ({$demora})" : '');
            }

            return ['ok' => true, 'lineas' => $lineas];
        } catch (CorreoArgentinoException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        } catch (\Throwable $e) {
            Log::warning('TiendaCorreo probar: ' . $e->getMessage(), ['prefijo' => $this->prefijo]);

            return ['ok' => false, 'error' => 'No se pudo probar la conexión con Correo Argentino.'];
        }
    }

    /**
     * Customer ID de la cuenta MiCorreo del comercio a partir de su email y
     * contraseña de la web. La contraseña no se guarda.
     *
     * @return array{ok: bool, customer_id?: string, error?: string}
     */
    public function obtenerCustomerId(array $cred, string $email, string $password): array
    {
        try {
            $datos = (new CorreoArgentinoService($cred))->validarUsuario($email, $password);

            return ['ok' => true, 'customer_id' => $datos['customerId']];
        } catch (CorreoArgentinoException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        } catch (\Throwable $e) {
            Log::warning('TiendaCorreo customerId: ' . $e->getMessage(), ['prefijo' => $this->prefijo]);

            return ['ok' => false, 'error' => 'No se pudo consultar la cuenta MiCorreo.'];
        }
    }

    /**
     * Importa un pedido de la tienda a la cuenta MiCorreo del comercio (API
     * /shipping/import). Queda cargado ahi para pagarlo e imprimir el rotulo; el
     * numero de seguimiento lo da MiCorreo al generar el rotulo y el comercio lo
     * carga despues al marcar "despachado". Deja envio_importado y un evento.
     *
     * @return array{ok: bool, mensaje: string}
     */
    public function importarPedido(int $idpedido, string $usuario = 'panel'): array
    {
        $db = DB::connection('client_db');
        $sat = $db->table($this->prefijo . 'tienda_pedidos')->where('idpedido', $idpedido)->first();
        if (!$sat) {
            return ['ok' => false, 'mensaje' => 'El pedido no vino de la tienda web.'];
        }
        if ($sat->entrega_modo !== TiendaPedidoService::ENTREGA_CORREO) {
            return ['ok' => false, 'mensaje' => 'Este pedido no se entrega por correo.'];
        }
        if ($sat->envio_importado) {
            return ['ok' => false, 'mensaje' => 'El envío de este pedido ya fue importado a MiCorreo el ' . date('d/m/Y H:i', strtotime($sat->envio_importado)) . '.'];
        }
        if (in_array($sat->estado_web, [TiendaSeguimientoService::CANCELADO, TiendaSeguimientoService::ENTREGADO], true)) {
            return ['ok' => false, 'mensaje' => 'El pedido está ' . mb_strtolower(TiendaSeguimientoService::etiqueta($sat->estado_web, $sat->entrega_modo)) . '; no corresponde generar el envío.'];
        }
        if (in_array($sat->pago_estado, [TiendaPagoService::ESTADO_PENDIENTE, TiendaPagoService::ESTADO_RECHAZADO], true)) {
            return ['ok' => false, 'mensaje' => 'El pago del pedido todavía no está confirmado. Confirmalo antes de generar el envío.'];
        }

        $cred = $this->credenciales();
        if (!$cred || !(new CorreoArgentinoService($cred))->configurado()) {
            return ['ok' => false, 'mensaje' => 'Falta la cuenta MiCorreo del comercio (Catálogo → Integraciones → Correo Argentino).'];
        }

        $aSucursal = !empty($sat->entrega_sucursal_codigo);
        $provincia = CorreoArgentinoService::codigoProvincia($sat->entrega_provincia);
        if (!$aSucursal && $provincia === null) {
            return ['ok' => false, 'mensaje' => 'El pedido no tiene una provincia de entrega reconocible' . ($sat->entrega_provincia ? " ({$sat->entrega_provincia})" : '') . '. Cargá el envío a mano en MiCorreo.'];
        }
        if (!$aSucursal && (!$sat->entrega_calle || !$sat->entrega_numero || !$sat->entrega_localidad || CorreoArgentinoService::normalizarCodigoPostal($sat->entrega_cp) === null)) {
            return ['ok' => false, 'mensaje' => 'Al pedido le falta calle, número, localidad o código postal de entrega. Cargá el envío a mano en MiCorreo.'];
        }

        $encab = $db->table($this->prefijo . 'pedidos_encab')->where('id', $idpedido)->first();
        $comprador = $sat->comprador_id ? $db->table($this->prefijo . 'tienda_compradores')->where('id', $sat->comprador_id)->first() : null;
        $det = $db->table($this->prefijo . 'pedidos_det')->where('idpedido', $idpedido)->get();
        if ($det->isEmpty()) {
            return ['ok' => false, 'mensaje' => 'El pedido no tiene artículos.'];
        }

        $items = $det->map(fn ($d) => ['codigo' => $d->codart, 'cantidad' => (float) $d->cantidad])->all();
        $paquete = (new TiendaEnvioService($this->prefijo))->pesoCarrito($items);
        $subtotal = $det->sum(fn ($d) => (float) $d->cantidad * ((float) $d->punitario - (float) ($d->descu ?? 0))) - (float) ($sat->descuento_promos ?? 0);

        [$piso, $depto] = self::separarPisoDepto($sat->entrega_piso);
        $nombre = trim((string) ($comprador->nombre ?? '')) ?: trim((string) ($encab->cliente ?? '')) ?: 'Comprador';

        try {
            $this->cliente($cred)->importarEnvio([
                'ext_order_id' => 'T' . $idpedido,
                'order_number' => (string) $idpedido,
                'destinatario' => [
                    'nombre' => $nombre,
                    'telefono' => $comprador->telefono ?? null,
                    'celular' => $comprador->telefono ?? null,
                    'email' => $comprador->email ?? null,
                ],
                'tipo_entrega' => $aSucursal ? CorreoArgentinoService::ENTREGA_SUCURSAL : CorreoArgentinoService::ENTREGA_DOMICILIO,
                'sucursal' => $aSucursal ? $sat->entrega_sucursal_codigo : null,
                'direccion' => [
                    'calle' => $sat->entrega_calle,
                    'numero' => $sat->entrega_numero,
                    'piso' => $piso,
                    'depto' => $depto,
                    'localidad' => $sat->entrega_localidad,
                    'provincia' => $provincia ?? '',
                    'codigo_postal' => $sat->entrega_cp,
                ],
                'bulto' => [
                    'peso_gramos' => $paquete['peso_gramos'], 'ancho_cm' => $paquete['ancho_cm'],
                    'alto_cm' => $paquete['alto_cm'], 'largo_cm' => $paquete['largo_cm'],
                ],
                'valor_declarado' => max(0, round($subtotal, 2)),
            ]);
        } catch (CorreoArgentinoException $e) {
            Log::warning('TiendaCorreo importar: ' . $e->getMessage(), ['prefijo' => $this->prefijo, 'idpedido' => $idpedido]);

            return ['ok' => false, 'mensaje' => $e->getMessage()];
        } catch (\Throwable $e) {
            Log::warning('TiendaCorreo importar: ' . $e->getMessage(), ['prefijo' => $this->prefijo, 'idpedido' => $idpedido]);

            return ['ok' => false, 'mensaje' => 'No se pudo importar el envío a MiCorreo.'];
        }

        $db->table($this->prefijo . 'tienda_pedidos')->where('idpedido', $idpedido)->update(['envio_importado' => now()]);
        (new TiendaPedidoService($this->prefijo))->registrarEvento(
            $idpedido,
            $sat->estado_web,
            'Envío ' . ($aSucursal ? 'a sucursal ' . $sat->entrega_sucursal_codigo . ' ' : 'a domicilio ') . 'importado a MiCorreo (' . $paquete['peso_gramos'] . ' g, ' . $paquete['ancho_cm'] . 'x' . $paquete['alto_cm'] . 'x' . $paquete['largo_cm'] . ' cm). Falta pagarlo e imprimir el rótulo en la web de MiCorreo.',
            'comercio',
            $usuario
        );

        return ['ok' => true, 'mensaje' => "Envío del pedido #{$idpedido} importado a MiCorreo. Entrá a la web de MiCorreo para pagarlo e imprimir el rótulo; después cargá acá el número de seguimiento al marcarlo despachado."];
    }

    /**
     * El checkout guarda "Piso/Depto" en un solo campo; MiCorreo lo quiere separado
     * y de hasta 3 caracteres cada uno. "3 B", "3º B", "PB 2", "piso 3 dto 4".
     * @return array{0: string, 1: string}
     */
    public static function separarPisoDepto(?string $texto): array
    {
        $t = trim((string) $texto);
        if ($t === '') {
            return ['', ''];
        }
        $t = preg_replace('/\b(piso|dpto\.?|depto\.?|dto\.?|departamento)\b/iu', ' ', $t);
        $t = str_replace(['º', '°', '/', '-', ','], ' ', $t);
        $partes = preg_split('/\s+/', trim($t), -1, PREG_SPLIT_NO_EMPTY);
        if (count($partes) === 1 && preg_match('/^(\d+)([A-Za-z]+)$/', $partes[0], $m)) {
            $partes = [$m[1], $m[2]];
        }

        return [mb_substr($partes[0] ?? '', 0, 3), mb_substr($partes[1] ?? '', 0, 3)];
    }

    /**
     * Sucursales del Correo con retiro habilitado en una provincia (codigo de una
     * letra o nombre como lo escribe el comprador). Cacheadas 12 h por cuenta.
     *
     * @return array{ok: bool, lista?: list<array<string, mixed>>, error?: string}
     */
    public function sucursales(?string $provincia, ?array $cred = null): array
    {
        $cred ??= $this->credenciales();
        if (!$cred) {
            return ['ok' => false, 'error' => 'La API de Correo Argentino no está configurada.'];
        }
        $codigo = CorreoArgentinoService::codigoProvincia($provincia);
        if ($codigo === null) {
            return ['ok' => false, 'error' => 'No reconocemos la provincia para buscar sucursales.'];
        }

        try {
            $lista = array_values(array_filter((new CorreoArgentinoService($cred))->sucursales($codigo), fn ($s) => $s['pickupAvailability']));

            return ['ok' => true, 'lista' => $lista];
        } catch (CorreoArgentinoException $e) {
            Log::warning('TiendaCorreo sucursales: ' . $e->getMessage(), ['prefijo' => $this->prefijo, 'provincia' => $codigo]);

            return ['ok' => false, 'error' => 'No pudimos cargar las sucursales del Correo en este momento.'];
        } catch (\Throwable $e) {
            Log::warning('TiendaCorreo sucursales: ' . $e->getMessage(), ['prefijo' => $this->prefijo]);

            return ['ok' => false, 'error' => 'No pudimos cargar las sucursales del Correo en este momento.'];
        }
    }

    /** "Sucursal Villa Crespo · Av. Corrientes 5000, CABA (1414)" para mostrar y guardar en el pedido. */
    public static function textoSucursal(array $s): string
    {
        $dir = trim(($s['streetName'] ?? '') . ' ' . ($s['streetNumber'] ?? ''));
        $loc = $s['locality'] ?? $s['city'] ?? '';
        $texto = trim((string) $s['name']);
        if ($dir !== '' || $loc !== '') {
            $texto .= ' · ' . trim($dir . ($dir !== '' && $loc !== '' ? ', ' : '') . $loc);
        }
        if (!empty($s['postalCode'])) {
            $texto .= ' (' . $s['postalCode'] . ')';
        }

        return $texto;
    }

    /** "3 a 6 días hábiles" a partir de los tiempos que informa la API; null si no vinieron. */
    public static function textoDemora($min, $max): ?string
    {
        $min = is_numeric($min) ? (int) $min : null;
        $max = is_numeric($max) ? (int) $max : null;
        if ($min === null && $max === null) {
            return null;
        }
        if ($min !== null && $max !== null && $min !== $max) {
            return "{$min} a {$max} días hábiles";
        }
        $d = $max ?? $min;

        return $d === 1 ? '1 día hábil' : "{$d} días hábiles";
    }
}
