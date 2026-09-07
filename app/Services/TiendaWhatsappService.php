<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp por la API de Meta (F7), a nombre del COMERCIO: su numero, su
 * verificacion en Meta, sus plantillas aprobadas y su costo por mensaje.
 * Nosotros solo mandamos.
 *
 * Credenciales en tienda_integraciones (servicio 'whatsapp'):
 *   token (permanente, del System User), phone_number_id, idioma (es_AR),
 *   plantilla_pedido, plantilla_estado, plantilla_carrito (nombres de plantillas
 *   aprobadas), telefono_comercio (a donde avisar los pedidos nuevos) y los
 *   interruptores avisa_pedido / avisa_estado / avisa_carrito.
 *
 * Las plantillas se mandan con parametros de texto {{1}}, {{2}}... en el cuerpo:
 *   pedido:  1 nombre del comprador, 2 nro de pedido, 3 total, 4 tienda
 *   estado:  1 nombre, 2 nro de pedido, 3 estado, 4 tienda
 *   carrito: 1 nombre, 2 cantidad de productos, 3 tienda, 4 link
 */
class TiendaWhatsappService
{
    public const API = 'https://graph.facebook.com/v20.0';

    private string $prefijo;

    public function __construct(string $prefijo)
    {
        $this->prefijo = $prefijo;
    }

    private function tabla(string $nombre)
    {
        return DB::connection('client_db')->table($this->prefijo . $nombre);
    }

    /** Configuracion del comercio (credenciales descifradas) o null si no esta activa. */
    public function config(): ?array
    {
        return (new TiendaIntegracionService($this->prefijo))->whatsapp();
    }

    public function activo(): bool
    {
        $c = $this->config();

        return $c !== null && !empty($c['token']) && !empty($c['phone_number_id']);
    }

    /** Telefono en formato internacional sin '+': argentino de 10 digitos -> 549XXXXXXXXXX. */
    public static function normalizar(?string $telefono): ?string
    {
        $t = preg_replace('/\D/', '', (string) $telefono);
        if ($t === '') {
            return null;
        }
        if (str_starts_with($t, '0')) {
            $t = substr($t, 1);
        }
        if (strlen($t) === 10) {
            $t = '549' . $t;
        } elseif (strlen($t) === 12 && str_starts_with($t, '54') && !str_starts_with($t, '549')) {
            $t = '549' . substr($t, 2);
        }

        return strlen($t) >= 10 ? $t : null;
    }

    /** Manda una plantilla aprobada con parametros de cuerpo. Devuelve ['ok', 'id' | 'error']. */
    public function enviarPlantilla(?string $telefono, ?string $plantilla, array $parametros, ?array $cfg = null, ?string $tokenPrueba = null): array
    {
        $cfg = $cfg ?? $this->config();
        $to = self::normalizar($telefono);
        if (!$cfg || !$to || empty($plantilla)) {
            return ['ok' => false, 'error' => 'Falta el número, la plantilla o la configuración de WhatsApp.'];
        }
        $cuerpo = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $plantilla,
                'language' => ['code' => $cfg['idioma'] ?: 'es_AR'],
            ],
        ];
        if (!empty($parametros)) {
            $cuerpo['template']['components'] = [[
                'type' => 'body',
                'parameters' => array_map(fn ($p) => ['type' => 'text', 'text' => mb_substr(trim((string) $p), 0, 200) ?: '-'], array_values($parametros)),
            ]];
        }
        try {
            $r = Http::withToken($tokenPrueba ?: $cfg['token'])->timeout(15)
                ->post(self::API . '/' . $cfg['phone_number_id'] . '/messages', $cuerpo);
            if ($r->successful()) {
                return ['ok' => true, 'id' => $r->json('messages.0.id')];
            }
            $error = $r->json('error.message') ?: ('HTTP ' . $r->status());

            return ['ok' => false, 'error' => mb_substr((string) $error, 0, 250)];
        } catch (\Throwable $e) {
            Log::warning('TiendaWhatsapp: ' . $e->getMessage(), ['prefijo' => $this->prefijo]);

            return ['ok' => false, 'error' => 'No se pudo conectar con WhatsApp.'];
        }
    }

    /** Prueba desde el panel: manda hello_world (la plantilla que Meta da por defecto) al numero indicado. */
    public function probar(array $cfg, string $telefono): array
    {
        $cfgPrueba = array_merge($cfg, ['idioma' => 'en_US']);

        return $this->enviarPlantilla($telefono, 'hello_world', [], $cfgPrueba);
    }

    // ------------------------------------------------------------ avisos

    private function anotar(int $idpedido, array $r, string $que): void
    {
        try {
            (new TiendaPedidoService($this->prefijo))->registrarEvento($idpedido, $r['ok'] ? 'whatsapp_enviado' : 'whatsapp_error',
                $r['ok'] ? "WhatsApp: {$que}" : "WhatsApp ({$que}) no se pudo mandar: " . ($r['error'] ?? ''), 'sistema');
        } catch (\Throwable $e) {
            // sin tabla de eventos: se ignora
        }
    }

    /** Pedido nuevo: al comprador (si tiene telefono) y al comercio. */
    public function avisarPedidoNuevo(int $idpedido, ?string $telefonoComprador, string $nombreComprador, float $total, string $nombreTienda): void
    {
        $cfg = $this->config();
        if (!$cfg || empty($cfg['avisa_pedido']) || empty($cfg['plantilla_pedido'])) {
            return;
        }
        $params = [$nombreComprador ?: 'Cliente', (string) $idpedido, '$' . number_format($total, 2, ',', '.'), $nombreTienda];
        if ($telefonoComprador) {
            $this->anotar($idpedido, $this->enviarPlantilla($telefonoComprador, $cfg['plantilla_pedido'], $params, $cfg), 'aviso de pedido al comprador');
        }
        if (!empty($cfg['telefono_comercio'])) {
            $this->anotar($idpedido, $this->enviarPlantilla($cfg['telefono_comercio'], $cfg['plantilla_pedido'], $params, $cfg), 'aviso de pedido al comercio');
        }
    }

    /** Cambio de estado: al comprador. */
    public function avisarEstado(int $idpedido, ?string $telefonoComprador, string $nombreComprador, string $etiquetaEstado, string $nombreTienda): void
    {
        $cfg = $this->config();
        if (!$cfg || empty($cfg['avisa_estado']) || empty($cfg['plantilla_estado']) || !$telefonoComprador) {
            return;
        }
        $this->anotar($idpedido, $this->enviarPlantilla($telefonoComprador, $cfg['plantilla_estado'], [$nombreComprador ?: 'Cliente', (string) $idpedido, $etiquetaEstado, $nombreTienda], $cfg), "estado {$etiquetaEstado}");
    }

    /** Carrito abandonado: al comprador. Devuelve el resultado crudo (lo anota el comando). */
    public function avisarCarritoAbandonado(?string $telefonoComprador, string $nombreComprador, int $cantidad, string $nombreTienda, string $link): array
    {
        $cfg = $this->config();
        if (!$cfg || empty($cfg['avisa_carrito']) || empty($cfg['plantilla_carrito']) || !$telefonoComprador) {
            return ['ok' => false, 'error' => 'sin configurar'];
        }

        return $this->enviarPlantilla($telefonoComprador, $cfg['plantilla_carrito'], [$nombreComprador ?: 'Cliente', (string) $cantidad, $nombreTienda, $link], $cfg);
    }

    /** Telefono del comprador web (tienda_compradores) o, si no, el del cliente de gestion. */
    public function telefonoComprador(?int $compradorId, ?int $codcli): ?string
    {
        $tel = $compradorId ? $this->tabla('tienda_compradores')->where('id', $compradorId)->value('telefono') : null;
        if (!$tel && $codcli) {
            $tel = $this->tabla('clientes')->where('CODIGO', $codcli)->value('TELEFONO');
        }

        return $tel ? trim((string) $tel) : null;
    }
}
