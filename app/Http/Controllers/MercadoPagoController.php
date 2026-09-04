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

        // Obtener access_token del comercio
        $configWeb = DB::connection('config')
            ->table('config_clientes_web')
            ->where('cliente_id', $clientId)
            ->first();

        if (!$configWeb || !$configWeb->configuracion) {
            return response()->json(['error' => 'Config no encontrada'], 404);
        }

        $config = json_decode($configWeb->configuracion, true);
        $accessToken = $config['mp_access_token'] ?? null;

        if (!$accessToken) {
            return response()->json(['error' => 'Token MP no configurado'], 400);
        }

        // Consultar el pago en MP para obtener la external_reference
        $mp = new MercadoPagoService($accessToken);

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

            if ($response->successful()) {
                $pago = $response->json();
                $externalRef = $pago['external_reference'] ?? null;
                $status = $pago['status'] ?? 'pending';

                if ($externalRef) {
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
     * Consultar estado del pago (para polling desde JS)
     */
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
