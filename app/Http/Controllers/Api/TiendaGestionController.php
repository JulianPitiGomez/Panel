<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TiendaCorreoService;
use App\Services\TiendaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Endpoints que usa el sistema de gestion (Fivewin, modulo PEDIDOSWEB.PRG) para las
 * acciones de la tienda que necesitan algo que solo tiene el panel: credenciales
 * cifradas del comercio, mails, etc. Ver docs/tienda/fivewin/README.md.
 *
 * Autenticacion: header X-Gestion-Token igual a config('tienda.gestion_token')
 * (TIENDA_GESTION_TOKEN en .env). Sin token configurado, los endpoints no existen.
 * El {cliente} es el id de config_clientes (gestion lo manda con ceros a la
 * izquierda, "000001"; se toma como entero).
 */
class TiendaGestionController extends Controller
{
    /**
     * POST /api/gestion/tienda/{cliente}/pedidos/{pedido}/envio-micorreo
     * Body opcional: {"usuario": "quien lo pidio en gestion"}
     * Respuesta: {"ok": bool, "mensaje": string}
     */
    public function envioMiCorreo(Request $request, string $cliente, string $pedido): JsonResponse
    {
        if ($falla = $this->autorizar($request)) {
            return $falla;
        }

        $clienteId = (int) ltrim($cliente, '0');
        $pedidoId = (int) $pedido;
        if ($clienteId <= 0 || $pedidoId <= 0) {
            return response()->json(['ok' => false, 'mensaje' => 'Cliente o pedido inválido.'], 422);
        }

        try {
            [, $prefijo] = TiendaService::conectarCliente($clienteId);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'mensaje' => 'Comercio inexistente o sin base asignada.'], 404);
        }

        $usuario = trim((string) $request->input('usuario', ''));
        $usuario = $usuario !== '' ? mb_substr($usuario, 0, 60) : 'gestion';

        try {
            $r = (new TiendaCorreoService($prefijo))->importarPedido($pedidoId, $usuario);
        } catch (\Throwable $e) {
            Log::warning('Gestion envio MiCorreo: ' . $e->getMessage(), ['cliente' => $clienteId, 'pedido' => $pedidoId]);

            return response()->json(['ok' => false, 'mensaje' => 'No se pudo generar el envío. Revisá el log del panel.'], 500);
        }

        return response()->json(['ok' => (bool) $r['ok'], 'mensaje' => (string) $r['mensaje']], $r['ok'] ? 200 : 422);
    }

    /** Null si el token es valido; la respuesta de error si no. */
    private function autorizar(Request $request): ?JsonResponse
    {
        $esperado = (string) config('tienda.gestion_token', '');
        if ($esperado === '') {
            return response()->json(['ok' => false, 'mensaje' => 'La integración con gestión no está habilitada en el panel.'], 404);
        }

        $recibido = (string) $request->header('X-Gestion-Token', '');
        if ($recibido === '' || !hash_equals($esperado, $recibido)) {
            return response()->json(['ok' => false, 'mensaje' => 'Token de gestión inválido.'], 401);
        }

        return null;
    }
}
