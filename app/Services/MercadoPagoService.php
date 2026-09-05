<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MercadoPagoService
{
    private string $accessToken;
    private string $baseUrl = 'https://api.mercadopago.com';

    public function __construct(string $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    // =============================================
    // Checkout Pro (Preferencias)
    // =============================================

    /**
     * Crear una preferencia de pago en Mercado Pago
     */
    public function crearPreferencia(array $items, string $externalReference, ?string $notificationUrl = null): ?array
    {
        try {
            $body = [
                'items' => $items,
                'external_reference' => $externalReference,
                'expires' => true,
                'expiration_date_to' => now()->addMinutes(10)->toIso8601String(),
                'payment_methods' => [
                    'excluded_payment_types' => [
                        ['id' => 'credit_card'],
                        ['id' => 'debit_card'],
                        ['id' => 'ticket'],
                        ['id' => 'atm'],
                        ['id' => 'prepaid_card'],
                    ],
                ],
            ];

            if ($notificationUrl) {
                $body['notification_url'] = $notificationUrl;
            }

            $response = Http::withToken($this->accessToken)
                ->timeout(15)
                ->post("{$this->baseUrl}/checkout/preferences", $body);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'id' => $data['id'],
                    'init_point' => $data['init_point'],
                    'sandbox_init_point' => $data['sandbox_init_point'] ?? $data['init_point'],
                ];
            }

            Log::error('MP crearPreferencia error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('MP crearPreferencia exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Preferencia de Checkout Pro para la TIENDA WEB (PyMEs): a diferencia del kiosco,
     * acepta todos los medios (tarjetas incluidas), no vence a los 10 minutos y vuelve
     * a la tienda con back_urls. Devuelve ['id', 'init_point'] o null.
     */
    public function crearPreferenciaTienda(array $items, string $externalReference, ?string $notificationUrl, array $backUrls): ?array
    {
        try {
            $body = [
                'items' => $items,
                'external_reference' => $externalReference,
                'back_urls' => $backUrls,
                'auto_return' => 'approved',
                'statement_descriptor' => 'TIENDA WEB',
            ];

            if ($notificationUrl) {
                $body['notification_url'] = $notificationUrl;
            }

            $response = Http::withToken($this->accessToken)
                ->timeout(15)
                ->post("{$this->baseUrl}/checkout/preferences", $body);

            if ($response->successful()) {
                $data = $response->json();
                return ['id' => $data['id'], 'init_point' => $data['init_point']];
            }

            Log::error('MP crearPreferenciaTienda error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('MP crearPreferenciaTienda exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Consultar pagos por external_reference
     */
    public function consultarPago(string $externalReference): ?array
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(10)
                ->get("{$this->baseUrl}/v1/payments/search", [
                    'external_reference' => $externalReference,
                    'sort' => 'date_created',
                    'criteria' => 'desc',
                    'limit' => 1,
                ]);

            if ($response->successful()) {
                $results = $response->json('results', []);

                if (empty($results)) {
                    return ['status' => 'pending', 'payment_id' => null];
                }

                $pago = $results[0];
                return [
                    'status' => $pago['status'],
                    'payment_id' => $pago['id'],
                    'status_detail' => $pago['status_detail'] ?? null,
                ];
            }

            Log::error('MP consultarPago error: ' . $response->body());
            return ['status' => 'pending', 'payment_id' => null];
        } catch (\Exception $e) {
            Log::error('MP consultarPago exception: ' . $e->getMessage());
            return ['status' => 'pending', 'payment_id' => null];
        }
    }

    // =============================================
    // Común
    // =============================================

    /**
     * Obtener datos del usuario autenticado (collector_id, nombre, etc.)
     */
    public function obtenerUsuario(): ?array
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(10)
                ->get("{$this->baseUrl}/users/me");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'id' => $data['id'],
                    'nickname' => $data['nickname'] ?? null,
                    'first_name' => $data['first_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'email' => $data['email'] ?? null,
                    'site_id' => $data['site_id'] ?? null,
                ];
            }

            Log::error('MP obtenerUsuario error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('MP obtenerUsuario exception: ' . $e->getMessage());
            return null;
        }
    }

    // =============================================
    // QR Presencial (Modelo Atendido)
    // =============================================

    /**
     * Crear sucursal (store)
     * Devuelve ['success' => true, 'data' => [...]] o ['success' => false, 'error' => '...']
     */
    public function crearSucursal(int $userId, array $data): array
    {
        try {
            $body = [
                'name' => $data['name'],
                'external_id' => $data['external_id'],
            ];

            // Location: solo agregar si se proveen datos reales
            if (!empty($data['street_name'])) {
                $body['location'] = [
                    'street_number' => $data['street_number'] ?? '0',
                    'street_name' => $data['street_name'],
                    'city_name' => $data['city_name'] ?? '',
                    'state_name' => $data['state_name'] ?? '',
                    'reference' => $data['reference'] ?? '',
                ];

                if (!empty($data['latitude']) && !empty($data['longitude'])) {
                    $body['location']['latitude'] = (float) $data['latitude'];
                    $body['location']['longitude'] = (float) $data['longitude'];
                }
            }

            if (!empty($data['business_hours'])) {
                $body['business_hours'] = $data['business_hours'];
            }

            $response = Http::withToken($this->accessToken)
                ->timeout(15)
                ->post("{$this->baseUrl}/users/{$userId}/stores", $body);

            if ($response->successful()) {
                $store = $response->json();
                return [
                    'success' => true,
                    'data' => [
                        'id' => $store['id'],
                        'name' => $store['name'],
                        'external_id' => $store['external_id'] ?? $body['external_id'],
                    ],
                ];
            }

            $errorBody = $response->json();
            $errorMsg = $errorBody['message'] ?? ($errorBody['error'] ?? 'Error desconocido');

            // Capturar detalle de validación (cause o causes)
            $causeList = $errorBody['causes'] ?? $errorBody['cause'] ?? [];
            if (!empty($causeList)) {
                $causes = collect($causeList)->map(function ($c) {
                    return ($c['code'] ?? '') . ': ' . ($c['description'] ?? $c['message'] ?? json_encode($c));
                })->implode(' | ');
                $errorMsg .= ' - ' . $causes;
            }

            Log::error('MP crearSucursal error', ['status' => $response->status(), 'body' => $errorBody, 'request' => $body]);
            return ['success' => false, 'error' => $errorMsg, 'status' => $response->status()];
        } catch (\Exception $e) {
            Log::error('MP crearSucursal exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crear caja/punto de venta (POS) - devuelve QR
     * Devuelve ['success' => true, 'data' => [...]] o ['success' => false, 'error' => '...']
     */
    public function crearCaja(array $data): array
    {
        try {
            $body = [
                'name' => $data['name'] ?? 'Caja Kiosco',
                'external_id' => $data['external_id'],
                'fixed_amount' => false,
                'category' => $data['category'] ?? 621102, // Comida rápida
            ];

            // Preferir store_id numérico (más confiable) sobre external_store_id
            if (!empty($data['store_id'])) {
                $body['store_id'] = (int) $data['store_id'];
            } elseif (!empty($data['external_store_id'])) {
                $body['external_store_id'] = $data['external_store_id'];
            }

            $response = Http::withToken($this->accessToken)
                ->timeout(15)
                ->post("{$this->baseUrl}/pos", $body);

            if ($response->successful()) {
                $pos = $response->json();
                return [
                    'success' => true,
                    'data' => [
                        'id' => $pos['id'],
                        'name' => $pos['name'],
                        'external_id' => $pos['external_id'] ?? $body['external_id'],
                        'qr' => $pos['qr']['image'] ?? null,
                        'qr_template' => $pos['qr']['template_document'] ?? null,
                        'qr_template_image' => $pos['qr']['template_image'] ?? null,
                    ],
                ];
            }

            $errorBody = $response->json();
            $errorMsg = $errorBody['message'] ?? ($errorBody['error'] ?? 'Error desconocido');

            if (!empty($errorBody['cause'])) {
                $causes = collect($errorBody['cause'])->map(function ($c) {
                    return ($c['code'] ?? '') . ': ' . ($c['description'] ?? $c['message'] ?? json_encode($c));
                })->implode(' | ');
                $errorMsg .= ' - ' . $causes;
            }

            Log::error('MP crearCaja error', ['status' => $response->status(), 'body' => $errorBody, 'request' => $body]);
            return ['success' => false, 'error' => $errorMsg, 'status' => $response->status()];
        } catch (\Exception $e) {
            Log::error('MP crearCaja exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crear orden en QR presencial (push order to POS)
     */
    public function crearOrdenQR(int $userId, string $externalStoreId, string $externalPosId, array $orderData): bool
    {
        try {
            $body = [
                'external_reference' => $orderData['external_reference'],
                'title' => $orderData['title'] ?? 'Pedido Kiosco',
                'description' => $orderData['description'] ?? 'Pedido desde kiosco autoservicio',
                'total_amount' => $orderData['total_amount'],
                'items' => $orderData['items'],
            ];

            if (!empty($orderData['notification_url'])) {
                $body['notification_url'] = $orderData['notification_url'];
            }

            $url = "{$this->baseUrl}/instore/qr/seller/collectors/{$userId}/stores/{$externalStoreId}/pos/{$externalPosId}/orders";

            $response = Http::withToken($this->accessToken)
                ->timeout(15)
                ->put($url, $body);

            if ($response->successful() || $response->status() === 204) {
                return true;
            }

            Log::error('MP crearOrdenQR error: ' . $response->status() . ' - ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('MP crearOrdenQR exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar orden del QR (cancelar pedido pendiente en el POS)
     */
    public function eliminarOrdenQR(int $userId, string $externalStoreId, string $externalPosId): bool
    {
        try {
            $url = "{$this->baseUrl}/instore/qr/seller/collectors/{$userId}/stores/{$externalStoreId}/pos/{$externalPosId}/orders";

            $response = Http::withToken($this->accessToken)
                ->timeout(10)
                ->delete($url);

            return $response->successful() || $response->status() === 204;
        } catch (\Exception $e) {
            Log::error('MP eliminarOrdenQR exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener sucursales existentes
     */
    public function obtenerSucursales(int $userId): array
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(10)
                ->get("{$this->baseUrl}/users/{$userId}/stores/search");

            if ($response->successful()) {
                return $response->json('results', []);
            }

            return [];
        } catch (\Exception $e) {
            Log::error('MP obtenerSucursales exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener cajas/POS existentes
     */
    public function obtenerCajas(?string $externalStoreId = null): array
    {
        try {
            $params = [];
            if ($externalStoreId) {
                $params['external_store_id'] = $externalStoreId;
            }

            $response = Http::withToken($this->accessToken)
                ->timeout(10)
                ->get("{$this->baseUrl}/pos", $params);

            if ($response->successful()) {
                return $response->json('results', []);
            }

            return [];
        } catch (\Exception $e) {
            Log::error('MP obtenerCajas exception: ' . $e->getMessage());
            return [];
        }
    }

    // =============================================
    // Point (Lectores)
    // =============================================

    /**
     * Obtener dispositivos Point vinculados a la cuenta
     */
    public function obtenerDispositivos(): array
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(10)
                ->get("{$this->baseUrl}/point/integration-api/devices");

            if ($response->successful()) {
                return $response->json('devices', []);
            }

            Log::error('MP obtenerDispositivos error: ' . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error('MP obtenerDispositivos exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear intención de pago en dispositivo Point
     */
    public function crearIntencionPago(string $deviceId, array $data): ?array
    {
        try {
            $body = [
                'amount' => $data['amount'],
                'description' => $data['description'] ?? 'Pedido Kiosco',
                'payment' => [
                    'installments' => 1,
                    'type' => 'credit_card',
                ],
                'additional_info' => [
                    'external_reference' => $data['external_reference'],
                    'print_on_terminal' => $data['print_on_terminal'] ?? false,
                ],
            ];

            $response = Http::withToken($this->accessToken)
                ->timeout(15)
                ->post("{$this->baseUrl}/point/integration-api/devices/{$deviceId}/payment-intents", $body);

            if ($response->successful()) {
                $intent = $response->json();
                return [
                    'id' => $intent['id'],
                    'device_id' => $intent['device_id'] ?? $deviceId,
                    'amount' => $intent['amount'] ?? $data['amount'],
                    'state' => $intent['state'] ?? null,
                ];
            }

            Log::error('MP crearIntencionPago error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('MP crearIntencionPago exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Consultar estado de intención de pago en Point
     */
    public function consultarIntencionPago(string $deviceId, string $intentId): ?array
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(10)
                ->get("{$this->baseUrl}/point/integration-api/devices/{$deviceId}/payment-intents/{$intentId}");

            if ($response->successful()) {
                $intent = $response->json();
                return [
                    'id' => $intent['id'] ?? $intentId,
                    'state' => $intent['state'] ?? 'OPEN',
                    'payment_id' => $intent['payment']['id'] ?? null,
                    'payment_status' => $intent['payment']['status'] ?? null,
                ];
            }

            // 404 puede significar que expiró
            if ($response->status() === 404) {
                return ['id' => $intentId, 'state' => 'EXPIRED', 'payment_id' => null, 'payment_status' => null];
            }

            Log::error('MP consultarIntencionPago error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('MP consultarIntencionPago exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cancelar intención de pago en Point
     */
    public function cancelarIntencionPago(string $deviceId, string $intentId): bool
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(10)
                ->delete("{$this->baseUrl}/point/integration-api/devices/{$deviceId}/payment-intents/{$intentId}");

            return $response->successful() || $response->status() === 204;
        } catch (\Exception $e) {
            Log::error('MP cancelarIntencionPago exception: ' . $e->getMessage());
            return false;
        }
    }
}
