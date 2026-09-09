<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Las cuentas del comercio en servicios de terceros ({prefijo}tienda_integraciones).
 *
 * Regla del proyecto: la cuenta es del comercio (Mercado Pago, correo, WhatsApp, IA);
 * nosotros integramos. Las credenciales se guardan cifradas con la APP_KEY de Laravel
 * y nunca se escriben en logs ni se devuelven a la pantalla una vez cargadas.
 */
class TiendaIntegracionService
{
    public const MERCADOPAGO = 'mercadopago';
    public const CORREO      = 'correo';
    public const WHATSAPP    = 'whatsapp';
    public const IA          = 'ia';

    private string $prefijo;

    public function __construct(string $prefijo)
    {
        $this->prefijo = $prefijo;
    }

    private function tabla()
    {
        return DB::connection('client_db')->table($this->prefijo . 'tienda_integraciones');
    }

    /** Fila del servicio con 'credenciales' ya descifradas (array), o null si no existe. */
    public function obtener(string $servicio): ?object
    {
        $fila = $this->tabla()->where('servicio', $servicio)->first();
        if (!$fila) {
            return null;
        }

        $fila->credenciales = $this->descifrar($fila->credenciales);

        return $fila;
    }

    /** Guarda (o reemplaza) las credenciales de un servicio. Resetea la verificacion. */
    public function guardar(string $servicio, array $credenciales, array $extra = []): void
    {
        $datos = array_merge([
            'proveedor' => null,
            'modelo' => null,
            'activa' => 1,
        ], array_intersect_key($extra, array_flip(['proveedor', 'modelo', 'activa'])), [
            'credenciales' => Crypt::encryptString(json_encode($credenciales)),
            'verificada' => 0,
            'verificada_fecha' => null,
            'verificada_dato' => null,
            'ultimo_error' => null,
        ]);

        if ($this->tabla()->where('servicio', $servicio)->exists()) {
            $this->tabla()->where('servicio', $servicio)->update($datos);
        } else {
            $this->tabla()->insert(array_merge($datos, ['servicio' => $servicio, 'creado' => now()]));
        }
    }

    public function eliminar(string $servicio): void
    {
        $this->tabla()->where('servicio', $servicio)->delete();
    }

    public function marcarVerificada(string $servicio, bool $ok, ?string $dato = null, ?string $error = null): void
    {
        $this->tabla()->where('servicio', $servicio)->update([
            'verificada' => $ok ? 1 : 0,
            'verificada_fecha' => $ok ? now() : null,
            'verificada_dato' => $ok ? mb_substr((string) $dato, 0, 120) : null,
            'ultimo_error' => $ok ? null : mb_substr((string) $error, 0, 255),
        ]);
    }

    /** Access token de Mercado Pago del comercio (null si no esta cargado o esta inactivo). */
    public function mercadoPagoToken(): ?string
    {
        $mp = $this->obtener(self::MERCADOPAGO);
        if (!$mp || !$mp->activa) {
            return null;
        }

        return $mp->credenciales['access_token'] ?? null;
    }

    /** Clave de Anthropic del comercio para el asistente de la tienda (F7), o null. */
    public function claveIa(): ?string
    {
        $ia = $this->obtener(self::IA);
        if (!$ia || !$ia->activa) {
            return null;
        }

        return $ia->credenciales['api_key'] ?? null;
    }

    /**
     * Credenciales de la API MiCorreo (Correo Argentino) del comercio:
     * env, usuario, password, customer_id. Null si no estan cargadas o estan
     * desactivadas (el comercio las guardo pero eligio no cotizar con la API).
     */
    public function correo(): ?array
    {
        $c = $this->obtener(self::CORREO);
        if (!$c || !$c->activa || empty($c->credenciales['usuario']) || empty($c->credenciales['password'])) {
            return null;
        }

        return [
            'env' => $c->credenciales['env'] ?? 'produccion',
            'usuario' => $c->credenciales['usuario'],
            'password' => $c->credenciales['password'],
            'customer_id' => $c->credenciales['customer_id'] ?? '',
        ];
    }

    /** Configuracion de WhatsApp (API de Meta) del comercio, o null si no esta cargada / activa. */
    public function whatsapp(): ?array
    {
        $wa = $this->obtener(self::WHATSAPP);
        if (!$wa || !$wa->activa || empty($wa->credenciales['token']) || empty($wa->credenciales['phone_number_id'])) {
            return null;
        }

        return $wa->credenciales;
    }

    private function descifrar(?string $valor): array
    {
        if (!$valor) {
            return [];
        }
        try {
            $arr = json_decode(Crypt::decryptString($valor), true);
            return is_array($arr) ? $arr : [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
