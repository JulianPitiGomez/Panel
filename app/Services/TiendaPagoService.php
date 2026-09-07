<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Medios de pago de la tienda web y estado del pago de cada pedido.
 *
 * Medios (pago_medio en tienda_pedidos):
 *  - mercadopago      : Checkout Pro con la cuenta del comercio; el estado llega por webhook / consulta.
 *  - transferencia    : con importe unico (centavos que identifican el pedido) y comprobante que
 *                       sube el comprador; el comercio lo confirma en un clic desde el panel.
 *  - cuenta_corriente : solo compradores enlazados a un cliente de gestion con LIMITE; se cobra
 *                       despues desde gestion. La tienda solo respeta el limite.
 *  - a_convenir       : pago al recibir o lo que arreglen; no hay cobro online.
 *
 * Estados (pago_estado): pendiente | aprobado | rechazado | no_aplica.
 * El pedido en pedidos_encab no cambia por el pago: gestion sigue viendo estado P.
 */
class TiendaPagoService
{
    public const MEDIO_MERCADOPAGO      = 'mercadopago';
    public const MEDIO_TRANSFERENCIA    = 'transferencia';
    public const MEDIO_CUENTA_CORRIENTE = 'cuenta_corriente';
    public const MEDIO_A_CONVENIR       = 'a_convenir';
    public const MEDIO_DEBIN            = 'debin';   // F7: debito inmediato (lo pide el comercio desde su banco)

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_APROBADO  = 'aprobado';
    public const ESTADO_RECHAZADO = 'rechazado';
    public const ESTADO_NO_APLICA = 'no_aplica';

    private string $prefijo;

    public function __construct(string $prefijo)
    {
        $this->prefijo = $prefijo;
    }

    private function tabla(string $nombre)
    {
        return DB::connection('client_db')->table($this->prefijo . $nombre);
    }

    // ------------------------------------------------------------------ config

    public static function configDefault(): array
    {
        return [
            'transferencia_activa' => 1, 'transferencia_titular' => null, 'transferencia_banco' => null,
            'transferencia_cbu' => null, 'transferencia_alias' => null, 'transferencia_texto' => null,
            'cuenta_corriente_activa' => 0, 'mercadopago_activa' => 0, 'a_convenir_activa' => 1,
            'debin_activa' => 0, 'debin_texto' => null,
            'forpag_mercadopago' => null, 'forpag_transferencia' => null, 'forpag_cuenta_corriente' => null, 'forpag_a_convenir' => null, 'forpag_debin' => null,
        ];
    }

    /** Formas de pago de gestion (forpag), para mapear cada medio de la tienda. */
    public function formasPagoGestion(): array
    {
        try {
            return $this->tabla('forpag')->orderBy('CODIGO')->get(['CODIGO', 'NOMBRE', 'TIPO'])->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** forpag de gestion equivalente a un medio de la tienda ({CODIGO, NOMBRE, TIPO}) o null si no esta mapeado. */
    public function forpagDe(?string $medio): ?object
    {
        if (!$medio) {
            return null;
        }
        $cfg = $this->config();
        $codigo = $cfg['forpag_' . $medio] ?? null;
        if (!$codigo) {
            return null;
        }
        return $this->tabla('forpag')->where('CODIGO', (int) $codigo)->first(['CODIGO', 'NOMBRE', 'TIPO']);
    }

    /** Tipos forpag de todos los medios activos y mapeados: define que promos "valen para cualquier forma de pago". */
    public function tiposFpActivos(): array
    {
        $cfg = $this->config();
        $tipos = [];
        foreach (['mercadopago', 'transferencia', 'cuenta_corriente', 'a_convenir', 'debin'] as $m) {
            if (!empty($cfg[$m . '_activa']) && !empty($cfg['forpag_' . $m])) {
                $t = $this->tabla('forpag')->where('CODIGO', (int) $cfg['forpag_' . $m])->value('TIPO');
                if ($t !== null) {
                    $tipos[] = (int) $t;
                }
            }
        }
        return array_values(array_unique($tipos));
    }

    public function config(): array
    {
        $fila = $this->tabla('tienda_pagos_config')->first();

        return $fila ? array_merge(self::configDefault(), (array) $fila) : self::configDefault();
    }

    public function guardarConfig(array $datos): void
    {
        $datos = array_intersect_key($datos, self::configDefault());
        foreach (['transferencia_activa', 'cuenta_corriente_activa', 'mercadopago_activa', 'a_convenir_activa', 'debin_activa'] as $b) {
            if (array_key_exists($b, $datos)) {
                $datos[$b] = $datos[$b] ? 1 : 0;
            }
        }
        foreach (['transferencia_titular', 'transferencia_banco', 'transferencia_cbu', 'transferencia_alias', 'transferencia_texto'] as $t) {
            if (array_key_exists($t, $datos)) {
                $datos[$t] = trim((string) $datos[$t]) ?: null;
            }
        }
        foreach (['forpag_mercadopago', 'forpag_transferencia', 'forpag_cuenta_corriente', 'forpag_a_convenir'] as $f) {
            if (array_key_exists($f, $datos)) {
                $datos[$f] = is_numeric($datos[$f]) && (int) $datos[$f] > 0 ? (int) $datos[$f] : null;
            }
        }

        $existente = $this->tabla('tienda_pagos_config')->first();
        if ($existente) {
            $this->tabla('tienda_pagos_config')->where('id', $existente->id)->update($datos);
        } else {
            $this->tabla('tienda_pagos_config')->insert(array_merge(self::configDefault(), $datos));
        }
    }

    public static function etiquetaMedio(?string $medio): string
    {
        return match ($medio) {
            self::MEDIO_MERCADOPAGO => 'Mercado Pago',
            self::MEDIO_TRANSFERENCIA => 'Transferencia',
            self::MEDIO_CUENTA_CORRIENTE => 'Cuenta corriente',
            self::MEDIO_A_CONVENIR => 'A convenir',
            self::MEDIO_DEBIN => 'Débito inmediato (DEBIN)',
            default => 'Sin definir',
        };
    }

    public static function etiquetaEstado(?string $estado): string
    {
        return match ($estado) {
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_APROBADO => 'Pagado',
            self::ESTADO_RECHAZADO => 'Rechazado',
            self::ESTADO_NO_APLICA => 'Sin cobro online',
            default => '-',
        };
    }

    // --------------------------------------------------------------- checkout

    /**
     * Medios disponibles para el checkout, con detalle. $total incluye envio.
     * @return array<string, array{medio: string, etiqueta: string, detalle: string, disponible: bool}>
     */
    public function opciones(float $total, ?int $codcli): array
    {
        $cfg = $this->config();
        $out = [];

        if ($cfg['mercadopago_activa'] && (new TiendaIntegracionService($this->prefijo))->mercadoPagoToken()) {
            $out[self::MEDIO_MERCADOPAGO] = [
                'medio' => self::MEDIO_MERCADOPAGO, 'etiqueta' => 'Mercado Pago',
                'detalle' => 'Tarjeta, dinero en cuenta o transferencia. Te llevamos a Mercado Pago para pagar.', 'disponible' => true,
            ];
        }

        if ($cfg['transferencia_activa']) {
            $out[self::MEDIO_TRANSFERENCIA] = [
                'medio' => self::MEDIO_TRANSFERENCIA, 'etiqueta' => 'Transferencia bancaria',
                'detalle' => 'Al confirmar te mostramos los datos y el importe exacto a transferir.', 'disponible' => true,
            ];
        }

        if (!empty($cfg['debin_activa'])) {
            $out[self::MEDIO_DEBIN] = [
                'medio' => self::MEDIO_DEBIN, 'etiqueta' => 'Débito inmediato (DEBIN)',
                'detalle' => trim((string) ($cfg['debin_texto'] ?? '')) ?: 'Nos dejás tu CUIT/CUIL y tu CBU o alias. El comercio te pide el débito y lo aprobás desde tu home banking.', 'disponible' => true,
            ];
        }

        if ($cfg['cuenta_corriente_activa']) {
            $cc = $this->cuentaCorriente($codcli, $total);
            $out[self::MEDIO_CUENTA_CORRIENTE] = [
                'medio' => self::MEDIO_CUENTA_CORRIENTE, 'etiqueta' => 'Cuenta corriente',
                'detalle' => $cc['detalle'], 'disponible' => $cc['disponible'],
            ];
        }

        if ($cfg['a_convenir_activa']) {
            $out[self::MEDIO_A_CONVENIR] = [
                'medio' => self::MEDIO_A_CONVENIR, 'etiqueta' => 'Pago al recibir / a convenir',
                'detalle' => 'Lo arreglás con el comercio al momento de la entrega.', 'disponible' => true,
            ];
        }

        return $out;
    }

    /** Puede este cliente de gestion cargar $total a su cuenta corriente? Respeta clientes.LIMITE. */
    public function cuentaCorriente(?int $codcli, float $total): array
    {
        if (!$codcli) {
            return ['disponible' => false, 'detalle' => 'Solo para clientes registrados en el comercio.'];
        }

        $c = $this->tabla('clientes')->where('CODIGO', $codcli)->first(['SALDO', 'LIMITE']);
        if (!$c || (float) $c->LIMITE <= 0) {
            return ['disponible' => false, 'detalle' => 'Tu cuenta no tiene crédito habilitado. Consultá con el comercio.'];
        }

        $disponible = round((float) $c->LIMITE - (float) $c->SALDO, 2);
        if ($total > $disponible) {
            return ['disponible' => false, 'detalle' => 'Este pedido supera tu crédito disponible ($' . number_format(max($disponible, 0), 2, ',', '.') . ').'];
        }

        return ['disponible' => true, 'detalle' => 'Se suma a tu cuenta. Crédito disponible: $' . number_format($disponible, 2, ',', '.') . '.'];
    }

    /**
     * Importe con centavos unicos para identificar la transferencia de este pedido
     * en el resumen bancario. Los centavos salen del numero de pedido, asi dos
     * pedidos cercanos nunca comparten importe.
     */
    public static function importeUnico(float $total, int $pedidoId): float
    {
        $centavos = (($pedidoId * 7) % 89) + 1;   // 1..89

        return round(floor($total) + $centavos / 100, 2);
    }

    /**
     * Deja el medio elegido en el satelite recien creado (dentro de la transaccion del alta).
     * Devuelve los datos que la pantalla de exito necesita mostrar.
     */
    public function registrarPagoInicial(int $pedidoId, string $medio, float $total, ?string $usuario = null): array
    {
        $datos = ['pago_medio' => $medio];

        switch ($medio) {
            case self::MEDIO_TRANSFERENCIA:
                $datos['pago_estado'] = self::ESTADO_PENDIENTE;
                $datos['pago_importe_unico'] = self::importeUnico($total, $pedidoId);
                break;
            case self::MEDIO_MERCADOPAGO:
            case self::MEDIO_DEBIN:
                $datos['pago_estado'] = self::ESTADO_PENDIENTE;
                break;
            default:
                $datos['pago_estado'] = self::ESTADO_NO_APLICA;
        }

        $this->tabla('tienda_pedidos')->where('idpedido', $pedidoId)->update($datos);

        (new TiendaPedidoService($this->prefijo))->registrarEvento(
            $pedidoId, 'pago_' . $datos['pago_estado'],
            'Medio de pago: ' . self::etiquetaMedio($medio) . (isset($datos['pago_importe_unico']) ? ' · importe a transferir $' . number_format($datos['pago_importe_unico'], 2, ',', '.') : ''),
            'comprador', $usuario
        );

        return $datos;
    }

    // ------------------------------------------------------------ Mercado Pago

    public static function referenciaMp(int $clienteId, int $pedidoId): string
    {
        return "tienda-{$clienteId}-{$pedidoId}";
    }

    /** @return array{cliente_id: int, pedido_id: int}|null */
    public static function parsearReferenciaMp(?string $ref): ?array
    {
        if ($ref && preg_match('/^tienda-(\d+)-(\d+)$/', $ref, $m)) {
            return ['cliente_id' => (int) $m[1], 'pedido_id' => (int) $m[2]];
        }
        return null;
    }

    /**
     * Crea la preferencia de Checkout Pro para un pedido y devuelve la URL de pago (o null).
     * $items: [['nombre','cantidad','precio']]; $envio se agrega como un item mas.
     */
    public function iniciarMercadoPago(int $pedidoId, int $clienteId, string $slug, array $items, float $envio, float $descuento = 0): ?string
    {
        $token = (new TiendaIntegracionService($this->prefijo))->mercadoPagoToken();
        if (!$token) {
            return null;
        }

        // MP no acepta items negativos: las promos se prorratean bajando el precio de cada item
        if ($descuento > 0) {
            $bruto = array_sum(array_map(fn ($i) => (float) $i['precio'] * (float) $i['cantidad'], $items));
            if ($bruto > 0) {
                $factor = max(0, ($bruto - $descuento) / $bruto);
                $items = array_map(function ($i) use ($factor) {
                    $i['precio'] = round((float) $i['precio'] * $factor, 2);
                    return $i;
                }, $items);
            }
        }

        $mpItems = array_map(fn ($i) => [
            'title' => mb_substr($i['nombre'], 0, 120),
            'quantity' => (int) max(1, round($i['cantidad'])),
            'unit_price' => round((float) $i['precio'], 2),
            'currency_id' => 'ARS',
        ], $items);

        // Cantidades decimales (pesables): MP exige enteros; se manda 1 x subtotal
        foreach ($items as $k => $i) {
            if ((float) $i['cantidad'] != round($i['cantidad'])) {
                $mpItems[$k]['quantity'] = 1;
                $mpItems[$k]['unit_price'] = round((float) $i['precio'] * (float) $i['cantidad'], 2);
            }
        }

        if ($envio > 0) {
            $mpItems[] = ['title' => 'Envío', 'quantity' => 1, 'unit_price' => round($envio, 2), 'currency_id' => 'ARS'];
        }

        $ref = self::referenciaMp($clienteId, $pedidoId);
        $retorno = route('tienda.pago', ['tienda' => $slug, 'pedido' => $pedidoId]);
        $notif = route('mp.webhook', ['clientId' => $clienteId]);
        if (!str_starts_with($notif, 'https://')) {
            $notif = null;   // MP rechaza webhooks sin https (local); queda la consulta por polling
        }

        $pref = (new MercadoPagoService($token))->crearPreferenciaTienda($mpItems, $ref, $notif, [
            'success' => $retorno, 'pending' => $retorno, 'failure' => $retorno,
        ]);

        if (!$pref) {
            (new TiendaPedidoService($this->prefijo))->registrarEvento($pedidoId, 'pago_error', 'No se pudo crear la preferencia de Mercado Pago', 'sistema');
            return null;
        }

        $this->tabla('tienda_pedidos')->where('idpedido', $pedidoId)->update(['pago_referencia' => 'pref:' . $pref['id']]);

        return $pref['init_point'];
    }

    /** Aplica un estado de MP (approved / rejected / pending...) al pedido. Devuelve el estado propio. */
    public function actualizarDesdeMercadoPago(int $pedidoId, string $statusMp, ?string $paymentId = null): string
    {
        $estado = match ($statusMp) {
            'approved' => self::ESTADO_APROBADO,
            'rejected', 'cancelled', 'refunded', 'charged_back' => self::ESTADO_RECHAZADO,
            default => self::ESTADO_PENDIENTE,
        };

        $actual = $this->tabla('tienda_pedidos')->where('idpedido', $pedidoId)->first(['pago_estado', 'pago_referencia']);
        if (!$actual) {
            return $estado;
        }

        $cambios = ['pago_estado' => $estado];
        if ($paymentId) {
            $cambios['pago_referencia'] = 'mp:' . $paymentId;
        }

        if ($actual->pago_estado !== $estado || ($paymentId && $actual->pago_referencia !== 'mp:' . $paymentId)) {
            $this->tabla('tienda_pedidos')->where('idpedido', $pedidoId)->update($cambios);
            if ($actual->pago_estado !== $estado) {
                (new TiendaPedidoService($this->prefijo))->registrarEvento(
                    $pedidoId, 'pago_' . $estado,
                    'Mercado Pago informó: ' . $statusMp . ($paymentId ? " (pago {$paymentId})" : ''),
                    'sistema'
                );
            }
        }

        return $estado;
    }

    /** Consulta a MP el pago de un pedido pendiente y actualiza. Devuelve el estado propio. */
    public function consultarMercadoPago(int $pedidoId, int $clienteId): string
    {
        $sat = $this->tabla('tienda_pedidos')->where('idpedido', $pedidoId)->first(['pago_estado', 'pago_medio']);
        if (!$sat || $sat->pago_medio !== self::MEDIO_MERCADOPAGO) {
            return $sat->pago_estado ?? self::ESTADO_NO_APLICA;
        }
        if ($sat->pago_estado !== self::ESTADO_PENDIENTE) {
            return $sat->pago_estado;
        }

        $token = (new TiendaIntegracionService($this->prefijo))->mercadoPagoToken();
        if (!$token) {
            return self::ESTADO_PENDIENTE;
        }

        $r = (new MercadoPagoService($token))->consultarPago(self::referenciaMp($clienteId, $pedidoId));
        if (!$r || ($r['status'] ?? 'pending') === 'pending') {
            return self::ESTADO_PENDIENTE;
        }

        return $this->actualizarDesdeMercadoPago($pedidoId, $r['status'], $r['payment_id'] ? (string) $r['payment_id'] : null);
    }

    // ---------------------------------------------------------- transferencia

    /** El comercio confirma que la transferencia llego (un clic en el panel). */
    public function confirmarPago(int $pedidoId, ?string $usuario = null): void
    {
        $this->tabla('tienda_pedidos')->where('idpedido', $pedidoId)->update(['pago_estado' => self::ESTADO_APROBADO]);
        (new TiendaPedidoService($this->prefijo))->registrarEvento($pedidoId, 'pago_aprobado', 'Pago confirmado por el comercio', 'comercio', $usuario);
    }

    public function rechazarPago(int $pedidoId, ?string $motivo = null, ?string $usuario = null): void
    {
        $this->tabla('tienda_pedidos')->where('idpedido', $pedidoId)->update(['pago_estado' => self::ESTADO_RECHAZADO]);
        (new TiendaPedidoService($this->prefijo))->registrarEvento($pedidoId, 'pago_rechazado', 'Pago rechazado por el comercio' . ($motivo ? ": {$motivo}" : ''), 'comercio', $usuario);
    }

    /**
     * Guarda el comprobante que sube el comprador en el disco de imagenes de la tienda
     * (mismo bucket que las fotos, carpeta comprobantes/). Devuelve la URL.
     */
    public function guardarComprobante(int $pedidoId, \Illuminate\Http\UploadedFile $archivo): string
    {
        $disco = Storage::disk(config('filesystems.imagenes_productos', 'r2'));
        $ext = strtolower($archivo->getClientOriginalExtension() ?: 'jpg');
        $clave = "comprobantes/{$this->prefijo}/{$pedidoId}." . preg_replace('/[^a-z0-9]/', '', $ext);

        $disco->put($clave, file_get_contents($archivo->getRealPath()), [
            'ContentType' => $archivo->getMimeType() ?: 'application/octet-stream',
        ]);
        $url = $disco->url($clave) . '?v=' . time();

        $this->tabla('tienda_pedidos')->where('idpedido', $pedidoId)->update(['pago_comprobante' => $url]);
        (new TiendaPedidoService($this->prefijo))->registrarEvento($pedidoId, 'comprobante_subido', 'El comprador subió el comprobante de la transferencia', 'comprador');

        return $url;
    }

    /** Datos de transferencia para mostrar al comprador (pantalla de exito, mail, mis pedidos). */
    public function datosTransferencia(): array
    {
        $cfg = $this->config();

        return [
            'titular' => $cfg['transferencia_titular'],
            'banco' => $cfg['transferencia_banco'],
            'cbu' => $cfg['transferencia_cbu'],
            'alias' => $cfg['transferencia_alias'],
            'texto' => $cfg['transferencia_texto'],
        ];
    }
}
