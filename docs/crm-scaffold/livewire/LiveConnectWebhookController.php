<?php
// ================================================================
// app/Http/Controllers/Api/LiveConnectWebhookController.php
// REEMPLAZA: Los 6 métodos API_* de Web.php
// ================================================================

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LiveConnectService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class LiveConnectWebhookController extends Controller
{
    public function __construct(
        private LiveConnectService $liveConnect
    ) {}

    /**
     * Webhook: Contacto creado en LiveConnect
     * REEMPLAZA: API_contacto_crear()
     */
    public function contactoCrear(Request $request): JsonResponse
    {
        $data = $this->decodeWebhookData($request);

        try {
            $this->liveConnect->handleContactCreated($data);
        } catch (\Exception $e) {
            Log::error('Webhook contacto-crear error', ['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Webhook: Contacto descartado en LiveConnect
     * REEMPLAZA: API_contacto_descartado()
     */
    public function contactoDescartado(Request $request): JsonResponse
    {
        $data = $this->decodeWebhookData($request);

        try {
            $this->liveConnect->handleContactDiscarded($data);
        } catch (\Exception $e) {
            Log::error('Webhook contacto-descartado error', ['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Webhook: Alta de cliente desde LiveConnect
     * REEMPLAZA: API_contacto_altaCliente()
     */
    public function altaCliente(Request $request): JsonResponse
    {
        $data = $this->decodeWebhookData($request);

        try {
            $clienteId = $this->liveConnect->handleClientCreation($data);

            if ($clienteId) {
                Log::info("Cliente creado desde LiveConnect: ID {$clienteId}");
            }
        } catch (\Exception $e) {
            Log::error('Webhook alta-cliente error', ['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Webhook: Identificar contacto existente
     * REEMPLAZA: API_identifica_contacto()
     * Este devuelve respuesta para LiveConnect (sendText, input, etc.)
     */
    public function identificaContacto(Request $request): JsonResponse
    {
        $data = $this->decodeWebhookData($request);

        try {
            $result = $this->liveConnect->handleIdentifyClient($data);
            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('Webhook identifica-contacto error', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 0,
                'status_message' => 'Error interno',
            ], 200); // 200 para que LC no reintente
        }
    }

    /**
     * Webhook: Contacto ya es cliente, actualizar datos
     * REEMPLAZA: API_contacto_cliente()
     */
    public function contactoCliente(Request $request): JsonResponse
    {
        $data = $this->decodeWebhookData($request);

        try {
            $result = $this->liveConnect->handleIdentifyClient($data);
            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('Webhook contacto-cliente error', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'ok'], 200);
        }
    }

    /**
     * Webhook: Contacto NO es cliente
     * REEMPLAZA: API_noContacto_cliente()
     */
    public function noContactoCliente(Request $request): JsonResponse
    {
        $data = $this->decodeWebhookData($request);
        // Lógica mínima - solo registrar
        Log::info('Webhook no-contacto-cliente recibido', ['chat_id' => $data['chat']['id'] ?? null]);
        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Decodifica Unicode escapes del webhook
     */
    private function decodeWebhookData(Request $request): array
    {
        $data = $request->json()->all();
        return $this->decodeArray($data);
    }

    private function decodeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->decodeArray($value);
            } elseif (is_string($value)) {
                $data[$key] = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($matches) {
                    return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UCS-2BE');
                }, $value);
            }
        }
        return $data;
    }
}
