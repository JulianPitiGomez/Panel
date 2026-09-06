<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Models\Cliente;
use App\Services\MercadoPagoService;

class MercadoPagoController extends Controller
{
    /**
     * Webhook que recibe notificaciones de Mercado Pago
     */
    public function webhook(Request $request, $clientId)
    {
        Log::info('MP Webhook recibido', [
            'client_id' => $clientId,
            'type' => $request->input('type'),
            'data' => $request->all(),
        ]);

        $type = $request->input('type');

        if ($type !== 'payment') {
            return response()->json(['ok' => true]);
        }

        $paymentId = $request->input('data.id');
        if (!$paymentId) {
            return response()->json(['ok' => true]);
        }

        // Configurar DB del cliente
        $cliente = Cliente::find($clientId);
        if (!$cliente) {
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }

        Config::set('database.connections.client_db.database', $cliente->base);
        DB::purge('client_db');

        // Obtener access_token del comercio: kiosco resto (JSON de config web) o tienda PyMEs (tienda_integraciones)
        $accessToken = null;
        $configWeb = DB::connection('config')
            ->table('config_clientes_web')
            ->where('cliente_id', $clientId)
            ->first();
        if ($configWeb && $configWeb->configuracion) {
            $config = json_decode($configWeb->configuracion, true);
            $accessToken = $config['mp_access_token'] ?? null;
        }

        $prefijoTienda = $cliente->getTablePrefix();
        $tokenTienda = null;
        try {
            $tokenTienda = (new \App\Services\TiendaIntegracionService($prefijoTienda))->mercadoPagoToken();
        } catch (\Throwable $e) {
            // el cliente puede no tener las tablas de la tienda
        }

        $accessToken = $accessToken ?: $tokenTienda;
        if (!$accessToken) {
            return response()->json(['error' => 'Token MP no configurado'], 400);
        }

        // Consultar el pago en MP para obtener la external_reference
        $mp = new MercadoPagoService($accessToken);

        try {
            // Un pago de la tienda PyMEs se consulta con el token de la tienda
            $refTienda = null;
            $tokenConsulta = $accessToken;
            $response = \Illuminate\Support\Facades\Http::withToken($tokenConsulta)
                ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");
            if (!$response->successful() && $tokenTienda && $tokenTienda !== $accessToken) {
                $tokenConsulta = $tokenTienda;
                $response = \Illuminate\Support\Facades\Http::withToken($tokenConsulta)
                    ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");
            }

            if ($response->successful()) {
                $pago = $response->json();
                $externalRef = $pago['external_reference'] ?? null;
                $status = $pago['status'] ?? 'pending';

                $refCuenta = \App\Services\TiendaCuentaService::parsearReferenciaMp($externalRef);
                $refTienda = \App\Services\TiendaPagoService::parsearReferenciaMp($externalRef);
                if ($refCuenta) {
                    (new \App\Services\TiendaCuentaService($prefijoTienda))
                        ->actualizarDesdeMercadoPago($refCuenta['pago_id'], $status, (string) $paymentId);
                } elseif ($refTienda) {
                    (new \App\Services\TiendaPagoService($prefijoTienda))
                        ->actualizarDesdeMercadoPago($refTienda['pedido_id'], $status, (string) $paymentId);
                } elseif ($externalRef) {
                    DB::connection('client_db')
                        ->table('pedidosm_temp')
                        ->where('mp_external_reference', $externalRef)
                        ->update([
                            'mp_status' => $status,
                            'mp_payment_id' => (string) $paymentId,
                            'updated_at' => now(),
                        ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('MP Webhook error procesando pago: ' . $e->getMessage());
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Estado del pago de un pedido de la TIENDA PyMEs (polling desde la pagina de retorno).
     * Solo responde para el comprador dueño del pedido (sesion de la tienda).
     */
    public function consultarEstadoTienda($clientId, $pedido)
    {
        $cliente = Cliente::find($clientId);
        if (!$cliente || (int) session('tienda_config_id') !== (int) $clientId) {
            return response()->json(['status' => 'error'], 403);
        }

        Config::set('database.connections.client_db.database', $cliente->base);
        DB::purge('client_db');
        $prefijo = $cliente->getTablePrefix();

        $sat = DB::connection('client_db')->table($prefijo . 'tienda_pedidos')
            ->where('idpedido', (int) $pedido)->first(['comprador_id', 'pago_estado']);
        if (!$sat || (int) $sat->comprador_id !== (int) session('tienda_comprador_web_id')) {
            return response()->json(['status' => 'not_found'], 404);
        }

        $estado = (new \App\Services\TiendaPagoService($prefijo))->consultarMercadoPago((int) $pedido, (int) $clientId);

        return response()->json(['status' => $estado]);
    }

    /**
     * Consultar estado del pago (para polling desde JS)
     */
    /** Estado de un pago a cuenta de la tienda (F6), solo para el comprador dueño. */
    public function consultarEstadoCuenta($clientId, $pago)
    {
        $cliente = Cliente::find($clientId);
        if (!$cliente || (int) session('tienda_config_id') !== (int) $clientId) {
            return response()->json(['status' => 'error'], 403);
        }
        Config::set('database.connections.client_db.database', $cliente->base);
        DB::purge('client_db');
        $prefijo = $cliente->getTablePrefix();

        $fila = DB::connection('client_db')->table($prefijo . 'tienda_pagos_cuenta')->where('id', (int) $pago)->first(['comprador_id']);
        if (!$fila || (int) $fila->comprador_id !== (int) session('tienda_comprador_web_id')) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json(['status' => (new \App\Services\TiendaCuentaService($prefijo))->consultarMercadoPago((int) $pago, (int) $clientId)]);
    }

    public function consultarEstado($clientId, $reference)
    {
        $cliente = Cliente::find($clientId);
        if (!$cliente) {
            return response()->json(['status' => 'error', 'message' => 'Cliente no encontrado']);
        }

        Config::set('database.connections.client_db.database', $cliente->base);
        DB::purge('client_db');

        // Primero verificar en nuestra DB (por si el webhook ya actualizó)
        $pedido = DB::connection('client_db')
            ->table('pedidosm_temp')
            ->where('mp_external_reference', $reference)
            ->first();

        if (!$pedido) {
            return response()->json(['status' => 'not_found']);
        }

        // Si ya tenemos status del webhook, devolverlo
        if ($pedido->mp_status && $pedido->mp_status !== 'pending') {
            return response()->json([
                'status' => $pedido->mp_status,
                'payment_id' => $pedido->mp_payment_id,
            ]);
        }

        // Si no, consultar directamente a MP
        $configWeb = DB::connection('config')
            ->table('config_clientes_web')
            ->where('cliente_id', $clientId)
            ->first();

        if (!$configWeb || !$configWeb->configuracion) {
            return response()->json(['status' => 'pending']);
        }

        $config = json_decode($configWeb->configuracion, true);
        $accessToken = $config['mp_access_token'] ?? null;

        if (!$accessToken) {
            return response()->json(['status' => 'pending']);
        }

        $mp = new MercadoPagoService($accessToken);
        $resultado = $mp->consultarPago($reference);

        // Actualizar en DB si cambió
        if ($resultado && $resultado['status'] !== 'pending') {
            DB::connection('client_db')
                ->table('pedidosm_temp')
                ->where('mp_external_reference', $reference)
                ->update([
                    'mp_status' => $resultado['status'],
                    'mp_payment_id' => $resultado['payment_id'] ? (string) $resultado['payment_id'] : null,
                    'updated_at' => now(),
                ]);
        }

        return response()->json($resultado);
    }

    /**
     * Consultar estado de intención de pago Point (para polling desde JS)
     */
    public function consultarEstadoPoint($clientId, $deviceId, $intentId)
    {
        $cliente = Cliente::find($clientId);
        if (!$cliente) {
            return response()->json(['state' => 'ERROR', 'message' => 'Cliente no encontrado']);
        }

        $configWeb = DB::connection('config')
            ->table('config_clientes_web')
            ->where('cliente_id', $clientId)
            ->first();

        if (!$configWeb || !$configWeb->configuracion) {
            return response()->json(['state' => 'OPEN']);
        }

        $config = json_decode($configWeb->configuracion, true);
        $accessToken = $config['mp_access_token'] ?? null;

        if (!$accessToken) {
            return response()->json(['state' => 'OPEN']);
        }

        $mp = new MercadoPagoService($accessToken);
        $resultado = $mp->consultarIntencionPago($deviceId, $intentId);

        if (!$resultado) {
            return response()->json(['state' => 'OPEN']);
        }

        // Si el pago terminó, actualizar la DB
        if (in_array($resultado['state'], ['FINISHED']) && $resultado['payment_id']) {
            Config::set('database.connections.client_db.database', $cliente->base);
            DB::purge('client_db');

            // Buscar el pedido por el intent ID (guardado en mp_preference_id)
            DB::connection('client_db')
                ->table('pedidosm_temp')
                ->where('mp_preference_id', $intentId)
                ->update([
                    'mp_status' => 'approved',
                    'mp_payment_id' => (string) $resultado['payment_id'],
                    'updated_at' => now(),
                ]);
        }

        return response()->json($resultado);
    }
}
