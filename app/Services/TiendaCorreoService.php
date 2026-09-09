<?php

namespace App\Services;

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
     * Cotiza el envio a domicilio de un bulto. Nunca lanza: si algo falla devuelve
     * ok=false con el motivo, y el que llama decide si cae al tarifario manual.
     *
     * @param  array{peso_gramos: int|float, ancho_cm: int|float, alto_cm: int|float, largo_cm: int|float}  $bulto
     * @return array{ok: bool, importe?: float, producto?: string, demora?: ?string, valid_to?: ?string, error?: string}
     */
    public function cotizarDomicilio(string $cpOrigen, ?string $cpDestino, array $bulto, ?array $cred = null): array
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
            $cotizacion = $correo->cotizar($cpOrigen, (string) $cpDestino, $bulto, CorreoArgentinoService::ENTREGA_DOMICILIO);
            $tarifa = CorreoArgentinoService::tarifaPara($cotizacion, CorreoArgentinoService::ENTREGA_DOMICILIO);
            if ($tarifa === null) {
                return ['ok' => false, 'error' => 'Correo Argentino no devolvió tarifa a domicilio para ese código postal.'];
            }

            return [
                'ok' => true,
                'importe' => round((float) $tarifa['price'], 2),
                'producto' => (string) ($tarifa['productName'] ?: 'Paq.ar'),
                'demora' => self::textoDemora($tarifa['deliveryTimeMin'] ?? null, $tarifa['deliveryTimeMax'] ?? null),
                'valid_to' => $cotizacion['valid_to'] ?? null,
            ];
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
