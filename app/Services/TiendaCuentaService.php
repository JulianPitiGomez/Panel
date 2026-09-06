<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Portal de cuenta del mayorista (F6): saldo, comprobantes, cuenta corriente y
 * pago de la cuenta desde la tienda.
 *
 * Lee de gestion (clientes.SALDO / LIMITE, ventas_encab + ventas_det, ventas_cuota,
 * pagos) y NO escribe nada ahi: el pago que entra por la tienda queda en
 * tienda_pagos_cuenta hasta que el comercio lo confirma, y despues lo carga en
 * gestion como cualquier cobro. Asi la caja y los cierres de Fivewin siguen
 * siendo de Fivewin.
 *
 * ventas_det se enlaza a ventas_encab por NROFAC = TICOMP . LETRA . NUMCOMP.
 */
class TiendaCuentaService
{
    public const TIPOS = ['FC' => 'Factura', 'FR' => 'Factura', 'NC' => 'Nota de crédito', 'ND' => 'Nota de débito'];

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_APROBADO  = 'aprobado';
    public const ESTADO_RECHAZADO = 'rechazado';

    private string $prefijo;

    public function __construct(string $prefijo)
    {
        $this->prefijo = $prefijo;
    }

    private function tabla(string $nombre)
    {
        return DB::connection('client_db')->table($this->prefijo . $nombre);
    }

    // ------------------------------------------------------------ resumen

    /** Saldo, limite y cuotas pendientes del cliente de gestion. */
    public function resumen(int $codcli): array
    {
        $c = $this->tabla('clientes')->where('CODIGO', $codcli)->first(['CODIGO', 'NOMBRE', 'SALDO', 'LIMITE', 'CUIT', 'DIRECCION', 'LOCALIDAD']);
        if (!$c) {
            return ['existe' => false];
        }
        $cuotas = $this->tabla('ventas_cuota')->where('CLIENTE', $codcli)->where('SALDO', '>', 0)
            ->orderBy('FECVTO')->get(['TIPO', 'LETRA', 'NUMERO', 'CUOTA', 'CANTCUO', 'FECHA', 'FECVTO', 'IMPORTE', 'SALDO']);
        $hoy = now()->toDateString();
        $vencidas = $cuotas->filter(fn ($q) => $q->FECVTO && $q->FECVTO < $hoy);

        $saldo = (float) $c->SALDO;
        $limite = (float) $c->LIMITE;

        return [
            'existe'          => true,
            'nombre'          => trim($c->NOMBRE),
            'cuit'            => $c->CUIT,
            'saldo'           => $saldo,
            'limite'          => $limite,
            'disponible'      => $limite > 0 ? max(0, $limite - $saldo) : null,
            'cuotas'          => $cuotas->map(fn ($q) => [
                'comprobante' => $this->etiquetaComprobante($q->TIPO, $q->LETRA, $q->NUMERO),
                'cuota'       => (int) $q->CANTCUO > 1 ? ($q->CUOTA . '/' . $q->CANTCUO) : null,
                'fecha'       => $q->FECHA,
                'vence'       => $q->FECVTO,
                'importe'     => (float) $q->IMPORTE,
                'saldo'       => (float) $q->SALDO,
                'vencida'     => $q->FECVTO && $q->FECVTO < $hoy,
            ])->values()->all(),
            'cuotas_total'    => (float) $cuotas->sum('SALDO'),
            'vencido_total'   => (float) $vencidas->sum('SALDO'),
            'vencidas'        => $vencidas->count(),
            'pendientes_web'  => (float) $this->tabla('tienda_pagos_cuenta')->where('codcli', $codcli)->where('estado', self::ESTADO_PENDIENTE)->sum('importe'),
        ];
    }

    public function etiquetaComprobante(?string $tipo, ?string $letra, ?string $numero): string
    {
        return (self::TIPOS[$tipo] ?? $tipo) . ' ' . $letra . ' ' . $numero;
    }

    // ------------------------------------------------------------ comprobantes

    /** Ultimos comprobantes emitidos al cliente, con lo que les falta pagar. */
    public function comprobantes(int $codcli, int $limite = 60): array
    {
        $encabs = $this->tabla('ventas_encab')->where('CODCLI', $codcli)
            ->orderByDesc('FECHA')->orderByDesc('NUMCOMP')->limit($limite)
            ->get(['TICOMP', 'LETRA', 'NUMCOMP', 'FECHA', 'NETO', 'IVA', 'IMPORTE', 'cae', 'fecvto', 'condven']);

        $pendientes = $this->tabla('ventas_cuota')->where('CLIENTE', $codcli)->where('SALDO', '>', 0)
            ->selectRaw('TIPO, LETRA, NUMERO, SUM(SALDO) AS saldo')->groupBy('TIPO', 'LETRA', 'NUMERO')->get()
            ->keyBy(fn ($q) => $q->TIPO . $q->LETRA . $q->NUMERO);

        return $encabs->map(function ($e) use ($pendientes) {
            $clave = $e->TICOMP . $e->LETRA . $e->NUMCOMP;
            $esCredito = $e->TICOMP === 'NC';

            return [
                'clave'     => $clave,
                'ticomp'    => $e->TICOMP,
                'letra'     => $e->LETRA,
                'numero'    => $e->NUMCOMP,
                'etiqueta'  => $this->etiquetaComprobante($e->TICOMP, $e->LETRA, $e->NUMCOMP),
                'fecha'     => $e->FECHA,
                'importe'   => (float) $e->IMPORTE,
                'credito'   => $esCredito,
                'cae'       => $e->cae ?: null,
                'pendiente' => (float) ($pendientes[$clave]->saldo ?? 0),
            ];
        })->all();
    }

    /** Encabezado + renglones de un comprobante del cliente (null si no es suyo). */
    public function detalleComprobante(int $codcli, string $ticomp, string $letra, string $numero): ?array
    {
        $e = $this->tabla('ventas_encab')->where('CODCLI', $codcli)
            ->where('TICOMP', $ticomp)->where('LETRA', $letra)->where('NUMCOMP', $numero)->first();
        if (!$e) {
            return null;
        }
        $det = $this->tabla('ventas_det')->where('CODCLI', $codcli)->where('NROFAC', $ticomp . $letra . $numero)
            ->orderBy('ID')->get(['CODART', 'DETART', 'CANTIDAD', 'PUNIT', 'IMPORTE', 'DESCU', 'IVA', 'NETO']);

        return [
            'encab' => [
                'etiqueta'  => $this->etiquetaComprobante($e->TICOMP, $e->LETRA, $e->NUMCOMP),
                'ticomp'    => $e->TICOMP,
                'letra'     => $e->LETRA,
                'numero'    => $e->NUMCOMP,
                'fecha'     => $e->FECHA,
                'neto'      => (float) $e->NETO,
                'iva'       => (float) $e->IVA,
                'iibb'      => (float) ($e->IIBB ?? 0),
                'percep'    => (float) ($e->PERCEP ?? 0),
                'importe'   => (float) $e->IMPORTE,
                'cae'       => $e->cae ?: null,
                'cae_vto'   => $e->fecvto ?: null,
                'nombre'    => trim((string) $e->NOMBRE),
                'cuit'      => $e->CUIT,
                'direccion' => trim((string) $e->DIRECCION) . ($e->LOCALIDAD ? ', ' . trim($e->LOCALIDAD) : ''),
                'coniva'    => $e->CONIVA,
                'observa'   => trim((string) $e->OBSERVA),
            ],
            'renglones' => $det->map(fn ($d) => [
                'codigo'   => $d->CODART,
                'detalle'  => trim((string) $d->DETART),
                'cantidad' => (float) $d->CANTIDAD,
                'punit'    => (float) $d->PUNIT,
                'descu'    => (float) ($d->DESCU ?? 0),
                'importe'  => (float) $d->IMPORTE,
            ])->all(),
        ];
    }

    // ------------------------------------------------------------ cuenta corriente

    /**
     * Movimientos del cliente (comprobantes al debe, notas de credito y pagos al haber),
     * del mas nuevo al mas viejo, con el saldo que quedaba despues de cada uno
     * (arrancando del SALDO actual de clientes hacia atras).
     */
    public function movimientos(int $codcli, int $meses = 12): array
    {
        $desde = now()->subMonths($meses)->toDateString();
        $saldoActual = (float) $this->tabla('clientes')->where('CODIGO', $codcli)->value('SALDO');

        $ventas = $this->tabla('ventas_encab')->where('CODCLI', $codcli)->where('FECHA', '>=', $desde)
            ->get(['TICOMP', 'LETRA', 'NUMCOMP', 'FECHA', 'IMPORTE', 'hora'])
            ->map(fn ($v) => [
                'fecha'    => $v->FECHA,
                'hora'     => $v->hora ?? '',
                'concepto' => $this->etiquetaComprobante($v->TICOMP, $v->LETRA, $v->NUMCOMP),
                'debe'     => $v->TICOMP === 'NC' ? 0 : (float) $v->IMPORTE,
                'haber'    => $v->TICOMP === 'NC' ? (float) $v->IMPORTE : 0,
                'tipo'     => $v->TICOMP === 'NC' ? 'credito' : 'comprobante',
                'clave'    => [$v->TICOMP, $v->LETRA, $v->NUMCOMP],
            ]);

        $pagos = $this->tabla('pagos')->where('CLIENTE', $codcli)->where('FECHA', '>=', $desde)
            ->get(['NUMERO', 'TOTAL', 'FECHA', 'OBSERVA'])
            ->map(fn ($p) => [
                'fecha'    => $p->FECHA,
                'hora'     => '',
                'concepto' => 'Pago recibido' . ($p->NUMERO ? " #{$p->NUMERO}" : ''),
                'debe'     => 0,
                'haber'    => (float) $p->TOTAL,
                'tipo'     => 'pago',
                'clave'    => null,
            ]);

        $movs = $ventas->concat($pagos)->sortByDesc(fn ($m) => $m['fecha'] . ' ' . $m['hora'])->values();

        // Saldo despues de cada movimiento, de hoy hacia atras
        $saldo = $saldoActual;
        $out = [];
        foreach ($movs as $m) {
            $m['saldo'] = $saldo;
            $out[] = $m;
            $saldo = $saldo - $m['debe'] + $m['haber'];
        }

        return ['saldo_actual' => $saldoActual, 'movimientos' => $out, 'desde' => $desde];
    }

    // ------------------------------------------------------------ pago de la cuenta

    /** Pagos a cuenta hechos desde la tienda por este cliente. */
    public function pagosDe(int $codcli, int $limite = 20): array
    {
        return $this->tabla('tienda_pagos_cuenta')->where('codcli', $codcli)->orderByDesc('id')->limit($limite)->get()
            ->map(fn ($p) => (array) $p)->all();
    }

    /**
     * Crea un pago a cuenta pendiente. $medio: transferencia | mercadopago.
     * Devuelve ['ok', 'mensaje', 'id', 'importe_unico', 'init_point'].
     */
    public function crearPago(int $compradorId, int $codcli, float $importe, string $medio, int $clienteId, string $slug): array
    {
        $importe = round($importe, 2);
        if ($importe <= 0) {
            return ['ok' => false, 'mensaje' => 'Ingresá el importe que querés pagar.'];
        }
        $cfg = (new TiendaPagoService($this->prefijo))->config();
        if ($medio === TiendaPagoService::MEDIO_TRANSFERENCIA && empty($cfg['transferencia_activa'])) {
            return ['ok' => false, 'mensaje' => 'El comercio no tiene habilitado el pago por transferencia.'];
        }
        if ($medio === TiendaPagoService::MEDIO_MERCADOPAGO && empty($cfg['mercadopago_activa'])) {
            return ['ok' => false, 'mensaje' => 'El comercio no tiene habilitado Mercado Pago.'];
        }
        if (!in_array($medio, [TiendaPagoService::MEDIO_TRANSFERENCIA, TiendaPagoService::MEDIO_MERCADOPAGO], true)) {
            return ['ok' => false, 'mensaje' => 'Elegí cómo querés pagar.'];
        }

        $id = $this->tabla('tienda_pagos_cuenta')->insertGetId([
            'comprador_id' => $compradorId,
            'codcli'       => $codcli,
            'importe'      => $importe,
            'medio'        => $medio,
            'estado'       => self::ESTADO_PENDIENTE,
            'creado'       => now(),
        ]);

        $r = ['ok' => true, 'id' => $id, 'importe_unico' => null, 'init_point' => null, 'mensaje' => ''];

        if ($medio === TiendaPagoService::MEDIO_TRANSFERENCIA) {
            $r['importe_unico'] = TiendaPagoService::importeUnico($importe, $id);
            $this->tabla('tienda_pagos_cuenta')->where('id', $id)->update(['importe_unico' => $r['importe_unico']]);
            $r['mensaje'] = 'Transferí exactamente $' . number_format($r['importe_unico'], 2, ',', '.') . ' y subí el comprobante. El comercio lo confirma y lo aplica a tu cuenta.';
        } else {
            $r['init_point'] = $this->iniciarMercadoPago($id, $clienteId, $slug, $importe);
            if (!$r['init_point']) {
                $this->tabla('tienda_pagos_cuenta')->where('id', $id)->update(['estado' => self::ESTADO_RECHAZADO, 'observa' => 'No se pudo crear la preferencia de Mercado Pago']);

                return ['ok' => false, 'mensaje' => 'No pudimos conectar con Mercado Pago. Probá de nuevo o pagá por transferencia.'];
            }
            $r['mensaje'] = 'Te llevamos a Mercado Pago para pagar.';
        }

        return $r;
    }

    public static function referenciaMp(int $clienteId, int $pagoId): string
    {
        return "tienda-cuenta-{$clienteId}-{$pagoId}";
    }

    /** @return array{cliente_id: int, pago_id: int}|null */
    public static function parsearReferenciaMp(?string $ref): ?array
    {
        if ($ref && preg_match('/^tienda-cuenta-(\d+)-(\d+)$/', $ref, $m)) {
            return ['cliente_id' => (int) $m[1], 'pago_id' => (int) $m[2]];
        }

        return null;
    }

    private function iniciarMercadoPago(int $pagoId, int $clienteId, string $slug, float $importe): ?string
    {
        $token = (new TiendaIntegracionService($this->prefijo))->mercadoPagoToken();
        if (!$token) {
            return null;
        }
        $retorno = route('tienda.mi-cuenta', ['tienda' => $slug, 'pago' => $pagoId]);
        $notif = route('mp.webhook', ['clientId' => $clienteId]);
        if (!str_starts_with($notif, 'https://')) {
            $notif = null;
        }
        $pref = (new MercadoPagoService($token))->crearPreferenciaTienda(
            [['title' => 'Pago a cuenta', 'quantity' => 1, 'unit_price' => round($importe, 2), 'currency_id' => 'ARS']],
            self::referenciaMp($clienteId, $pagoId), $notif, ['success' => $retorno, 'pending' => $retorno, 'failure' => $retorno]
        );
        if (!$pref) {
            return null;
        }
        $this->tabla('tienda_pagos_cuenta')->where('id', $pagoId)->update(['referencia' => 'pref:' . $pref['id']]);

        return $pref['init_point'];
    }

    public function actualizarDesdeMercadoPago(int $pagoId, string $statusMp, ?string $paymentId = null): string
    {
        $estado = match ($statusMp) {
            'approved' => self::ESTADO_APROBADO,
            'rejected', 'cancelled', 'refunded', 'charged_back' => self::ESTADO_RECHAZADO,
            default => self::ESTADO_PENDIENTE,
        };
        $cambios = ['estado' => $estado];
        if ($paymentId) {
            $cambios['referencia'] = 'mp:' . $paymentId;
        }
        if ($estado === self::ESTADO_APROBADO) {
            $cambios['confirmado'] = now();
            $cambios['usuario'] = 'mercadopago';
        }
        $this->tabla('tienda_pagos_cuenta')->where('id', $pagoId)->update($cambios);

        return $estado;
    }

    /** Consulta a MP un pago a cuenta pendiente (polling al volver). */
    public function consultarMercadoPago(int $pagoId, int $clienteId): string
    {
        $p = $this->tabla('tienda_pagos_cuenta')->where('id', $pagoId)->first();
        if (!$p || $p->medio !== TiendaPagoService::MEDIO_MERCADOPAGO) {
            return $p->estado ?? self::ESTADO_RECHAZADO;
        }
        if ($p->estado !== self::ESTADO_PENDIENTE) {
            return $p->estado;
        }
        $token = (new TiendaIntegracionService($this->prefijo))->mercadoPagoToken();
        if (!$token) {
            return self::ESTADO_PENDIENTE;
        }
        $r = (new MercadoPagoService($token))->consultarPago(self::referenciaMp($clienteId, $pagoId));
        if (!$r || ($r['status'] ?? 'pending') === 'pending') {
            return self::ESTADO_PENDIENTE;
        }

        return $this->actualizarDesdeMercadoPago($pagoId, $r['status'], $r['payment_id'] ? (string) $r['payment_id'] : null);
    }

    public function guardarComprobante(int $pagoId, \Illuminate\Http\UploadedFile $archivo): string
    {
        $disco = Storage::disk(config('filesystems.imagenes_productos', 'r2'));
        $ext = strtolower($archivo->getClientOriginalExtension() ?: 'jpg');
        $clave = "comprobantes/{$this->prefijo}/cuenta-{$pagoId}." . preg_replace('/[^a-z0-9]/', '', $ext);
        $disco->put($clave, file_get_contents($archivo->getRealPath()), ['ContentType' => $archivo->getMimeType() ?: 'application/octet-stream']);
        $url = $disco->url($clave) . '?v=' . time();
        $this->tabla('tienda_pagos_cuenta')->where('id', $pagoId)->update(['comprobante' => $url]);

        return $url;
    }

    /** Para el panel: pagos a cuenta pendientes de confirmar (y los ultimos resueltos). */
    public function pendientesPanel(int $limite = 50): array
    {
        return $this->tabla('tienda_pagos_cuenta as pc')
            ->leftJoin($this->prefijo . 'clientes as c', 'c.CODIGO', '=', 'pc.codcli')
            ->leftJoin($this->prefijo . 'tienda_compradores as tc', 'tc.id', '=', 'pc.comprador_id')
            ->orderByRaw("pc.estado = 'pendiente' DESC")->orderByDesc('pc.id')->limit($limite)
            ->get(['pc.*', 'c.NOMBRE as cliente', 'c.SALDO as saldo_cliente', 'tc.email'])
            ->map(fn ($p) => (array) $p)->all();
    }

    public function confirmar(int $pagoId, ?string $usuario = null): void
    {
        $this->tabla('tienda_pagos_cuenta')->where('id', $pagoId)->update(['estado' => self::ESTADO_APROBADO, 'confirmado' => now(), 'usuario' => $usuario]);
    }

    public function rechazar(int $pagoId, ?string $motivo = null, ?string $usuario = null): void
    {
        $this->tabla('tienda_pagos_cuenta')->where('id', $pagoId)->update(['estado' => self::ESTADO_RECHAZADO, 'confirmado' => now(), 'usuario' => $usuario, 'observa' => $motivo]);
    }
}
