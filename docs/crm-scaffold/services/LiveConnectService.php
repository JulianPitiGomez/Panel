<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\BCNSoft\ConfigCliente;
use App\Models\BCNSoft\Contacto;
use App\Models\CRM\Contact;
use App\Enums\TaxCondition;

class LiveConnectService
{
    private string $apiUrl;
    private string $cKey;
    private string $privateKey;
    private array $dynamicFields;
    private array $ivaMap;
    private array $facturacionMap;
    private array $sistemaMap;

    public function __construct()
    {
        $this->apiUrl = config('liveconnect.api_url');
        $this->cKey = config('liveconnect.ckey');
        $this->privateKey = config('liveconnect.private_key');
        $this->dynamicFields = config('liveconnect.dynamic_fields');
        $this->ivaMap = config('liveconnect.condicion_iva_map');
        $this->facturacionMap = config('liveconnect.tipo_facturacion_map');
        $this->sistemaMap = config('liveconnect.tipo_sistema_map');
    }

    // ================================================================
    // AUTENTICACIÓN
    // ================================================================

    /**
     * Obtiene token de LiveConnect con caché
     * REEMPLAZA: Las 6+ llamadas curl a /account/token en Web.php
     */
    public function getToken(): ?string
    {
        return Cache::remember('liveconnect_token', config('liveconnect.token_cache_ttl', 3000), function () {
            try {
                $response = Http::timeout(30)->post("{$this->apiUrl}/account/token", [
                    'cKey' => $this->cKey,
                    'privateKey' => $this->privateKey,
                ]);

                if ($response->successful()) {
                    return $response->json('PageGearToken');
                }

                Log::error('LiveConnect: Error obteniendo token', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } catch (\Exception $e) {
                Log::error('LiveConnect: Excepción obteniendo token', [
                    'error' => $e->getMessage(),
                ]);
            }

            return null;
        });
    }

    /**
     * Invalida el token cacheado (por si expiró)
     */
    public function forgetToken(): void
    {
        Cache::forget('liveconnect_token');
    }

    // ================================================================
    // ACTUALIZACIÓN DE CONTACTOS
    // ================================================================

    /**
     * Actualiza un contacto en LiveConnect
     * REEMPLAZA: Todas las llamadas curl a /contacts/edt en Web.php
     */
    public function updateContact(int $contactId, array $data, ?string $chatId = null): bool
    {
        $token = $this->getToken();
        if (!$token) {
            return false;
        }

        $payload = [
            'id' => $contactId,
            'direccion' => $data['direccion'] ?? '',
            'ciudad' => $data['localidad'] ?? '',
            'dinamicos' => $this->buildDynamicFieldsForUpdate($data),
            'default_target' => '0',
            'default_target_id' => '0',
        ];

        if ($chatId) {
            $payload['id_chat'] = $chatId;
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders(['PageGearToken' => $token])
                ->post("{$this->apiUrl}/contacts/edt", $payload);

            if ($response->successful()) {
                return true;
            }

            Log::error('LiveConnect: Error actualizando contacto', [
                'contact_id' => $contactId,
                'status' => $response->status(),
            ]);
        } catch (\Exception $e) {
            Log::error('LiveConnect: Excepción actualizando contacto', [
                'contact_id' => $contactId,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Actualiza TODOS los contactos de bcnsoft en LiveConnect
     * REEMPLAZA: API_actualizar_contactos() de Web.php
     * Se puede ejecutar como Job: SyncLiveConnectContacts
     */
    public function syncAllBcnsoftContacts(): int
    {
        $clientes = DB::connection('bcnsoft')
            ->table('config_clientes AS c')
            ->select('c.*', 'r.nombre AS nomrub', 'cont.id AS id_live')
            ->leftJoin('config_rubros AS r', 'c.id_rubro', '=', 'r.id')
            ->leftJoin('aacontactos AS cont', 'cont.extra1', '=', 'c.id')
            ->get();

        $count = 0;

        foreach ($clientes as $cliente) {
            if (!$cliente->id_live) {
                continue;
            }

            $responsableNombre = '';
            if ($cliente->responsable > 0) {
                $resp = DB::connection('pedidosbcn')
                    ->table('usuariop')
                    ->where('id', $cliente->responsable)
                    ->first();
                $responsableNombre = $resp->nombre ?? '';
            }

            $data = $this->buildClientDataArray($cliente, $responsableNombre);
            
            if ($this->updateContact($cliente->id_live, $data)) {
                $count++;
            }
        }

        return $count;
    }

    // ================================================================
    // WEBHOOKS ENTRANTES
    // ================================================================

    /**
     * Procesa webhook de contacto creado
     * REEMPLAZA: API_contacto_crear() de Web.php
     */
    public function handleContactCreated(array $webhookData): void
    {
        if (!isset($webhookData['chat']['contacto'])) {
            return;
        }

        $contacto = $webhookData['chat']['contacto'];
        $mapped = $this->mapIncomingFields($contacto);

        // Buscar si ya existe por teléfono
        $existing = Contact::where('phone', $mapped['telefono'])->first();

        if (!$existing) {
            Contact::create([
                'name' => $mapped['nombre'],
                'business_name' => $mapped['nombre_comercial'] ?? 'CONTACTO LIVECONNECT',
                'email' => $mapped['email'] ?? 'CONTACTO LIVECONNECT',
                'phone' => $mapped['telefono'],
                'status' => 1, // Nuevo
                'type' => 0,
                'rubro_id' => 0,
                'assigned_to' => null,
            ]);
        }
    }

    /**
     * Procesa webhook de contacto descartado
     * REEMPLAZA: API_contacto_descartado() de Web.php
     */
    public function handleContactDiscarded(array $webhookData): void
    {
        if (!isset($webhookData['chat']['contacto'])) {
            return;
        }

        $contacto = $webhookData['chat']['contacto'];
        $mapped = $this->mapIncomingFields($contacto);

        $tipoSistema = $this->resolveTipoSistema($mapped['tipo_sistema'] ?? '');
        $responsableId = $this->resolveResponsableId($mapped['responsable'] ?? '');
        $rubroId = $this->resolveRubroId($mapped['rubro'] ?? '');
        $motivoId = $this->resolveMotivoDescarteId($mapped['motivo_descarte'] ?? '');

        $contact = Contact::where('phone', $mapped['telefono'])->first();

        $data = [
            'name' => $mapped['nombre'],
            'business_name' => $mapped['nombre_comercial'] ?? '',
            'email' => $mapped['email'] ?? '',
            'phone' => $mapped['telefono'],
            'rubro_id' => $rubroId,
            'assigned_to' => $responsableId,
            'status' => 7, // Descartado
            'type' => $tipoSistema,
            'discard_reason_id' => $motivoId,
        ];

        if ($contact) {
            $contact->update($data);
        } else {
            Contact::create($data);
        }
    }

    /**
     * Procesa webhook de alta de cliente
     * REEMPLAZA: API_contacto_altaCliente() de Web.php
     * Este es el más complejo - crea el cliente en bcn_config
     */
    public function handleClientCreation(array $webhookData): ?int
    {
        if (!isset($webhookData['chat']['contacto'])) {
            return null;
        }

        $chatId = $webhookData['chat']['id'] ?? null;
        $contacto = $webhookData['chat']['contacto'];
        $mapped = $this->mapIncomingFields($contacto);
        $contactoId = $contacto['id_contacto'] ?? null;

        $tipoSistemaTexto = $mapped['tipo_sistema'] ?? '';

        // Si es PEDIDOS BCN, la lógica es diferente (TODO: implementar)
        if ($tipoSistemaTexto === 'PEDIDOS BCN') {
            return null;
        }

        // --- Crear cliente en BCNSoft ---
        $ivaId = $this->resolveIvaId($mapped['condicion_iva'] ?? '');
        $rubroId = $this->resolveRubroId($mapped['rubro'] ?? '');
        $responsableId = $this->resolveResponsableId($mapped['responsable'] ?? '');
        $facturacionId = $this->resolveFacturacionId($mapped['tipo_facturacion'] ?? '');

        // Determinar pack
        $pack = ($tipoSistemaTexto === 'PYME') ? 'GE' : 'RE';

        // Generar código
        $codigo = $this->generateRandomCode(10);

        // Calcular base
        $lastId = DB::connection('bcnsoft')
            ->table('config_clientes')
            ->max('id') + 1;
        $base = intval($lastId / 30) + 1;

        // Preparar fecha implementación
        $fechaImpl = !empty($mapped['implementacion_fecha'])
            ? $this->convertDate($mapped['implementacion_fecha'])
            : null;

        // Versión actual del pack
        $version = DB::connection('bcnsoft')
            ->table('config_packs')
            ->where('codigo', $pack . '3')
            ->value('version_actual') ?? 0;

        DB::connection('bcnsoft')->beginTransaction();

        try {
            $clienteId = DB::connection('bcnsoft')->table('config_clientes')->insertGetId([
                'mail' => $mapped['email'] ?? '',
                'code' => $codigo,
                'nombre' => $mapped['nombre'],
                'nomemp' => $mapped['nombre_comercial'] ?? '',
                'razon_social' => $mapped['razon_social'] ?? '',
                'direccion' => $mapped['direccion'] ?? '',
                'localidad' => $mapped['localidad'] ?? '',
                'telefono' => $mapped['telefono'],
                'fechadesc' => now()->toDateString(),
                'activo' => false,
                'demo' => true,
                'vto' => now()->addDays(7)->toDateString(),
                'pack' => $pack . '3',
                'version' => $version,
                'modalidad' => 1,
                'codven' => 0,
                'abono' => 0,
                'setup' => 0,
                'saldo_setup' => 0,
                'gan_abono' => 0,
                'gan_setup' => 0,
                'host' => 'app.bcnsoft.com.ar',
                'base' => 'bcn_base' . $base,
                'baselocal' => 0,
                'panelweb' => 1,
                'cuit' => $mapped['cuit'] ?? 0,
                'id_rubro' => $rubroId,
                'id_contacto_app' => $contactoId ?? 0,
                'responsable' => $responsableId,
                'clave_fiscal' => $mapped['clave_fiscal'] ?? '',
                'coniva' => $ivaId,
                'pagina_web' => $mapped['pagina_web'] ?? '',
                'instagram' => $mapped['instagram'] ?? '',
                'facebook' => $mapped['facebook'] ?? '',
                'lic_principal' => $mapped['lic_principal'] ?? 0,
                'lic_adicional' => $mapped['lic_adicional'] ?? 0,
                'lic_bonificada' => $mapped['lic_bonificada'] ?? 0,
                'implementacion_fecha' => $fechaImpl,
                'implementacion_comanderas' => $mapped['implementacion_comanderas'] ?? 0,
                'tipo_facturacion' => $facturacionId,
                'tipo_conexion' => $mapped['tipo_conexion'] ?? '',
                'implementacion_personal' => $mapped['implementacion_personal'] ?? '',
                'implementacion_canales' => $mapped['implementacion_canales'] ?? '',
                'implementacion_paneles' => $mapped['implementacion_paneles'] ?? '',
                'lector_barras' => $mapped['lector_barras'] ?? 0,
            ]);

            // Actualizar o crear en la tabla contacts del CRM
            $tipo = ($pack === 'GE') ? 1 : 2;

            Contact::updateOrCreate(
                ['phone' => $mapped['telefono']],
                [
                    'name' => $mapped['nombre'],
                    'business_name' => $mapped['nombre_comercial'] ?? '',
                    'email' => $mapped['email'] ?? '',
                    'rubro_id' => $rubroId,
                    'assigned_to' => $responsableId > 0 ? $responsableId : null,
                    'status' => 5, // Convertido
                    'type' => $tipo,
                    'client_id' => $clienteId,
                    'system_source' => 'bcnsoft',
                ]
            );

            DB::connection('bcnsoft')->commit();

            // Actualizar LiveConnect con el ID del nuevo cliente
            if ($contactoId) {
                $data = $this->buildClientDataArrayFromMapped($mapped, $clienteId, $tipoSistemaTexto);
                $this->updateContact($contactoId, $data, $chatId);
            }

            return $clienteId;

        } catch (\Exception $e) {
            DB::connection('bcnsoft')->rollBack();
            Log::error('LiveConnect: Error creando cliente', [
                'error' => $e->getMessage(),
                'mapped' => $mapped,
            ]);
            return null;
        }
    }

    /**
     * Identifica un contacto existente y actualiza LiveConnect
     * REEMPLAZA: API_identifica_contacto() y API_contacto_cliente() de Web.php
     */
    public function handleIdentifyClient(array $webhookData): array
    {
        $chat = $webhookData['chat'] ?? [];
        $contacto = $chat['contacto'] ?? [];
        $contactoId = $contacto['id_contacto'] ?? null;
        $chatId = $chat['id'] ?? null;
        $userInput = $webhookData['userInput'] ?? null;

        if (!$contactoId) {
            return ['status' => 0, 'message' => 'Sin contacto'];
        }

        // Buscar cliente por id_contacto_app
        $query = DB::connection('bcnsoft')
            ->table('config_clientes AS c')
            ->select('c.id', 'c.nomemp')
            ->where('c.id_contacto_app', $contactoId);

        // Si hay input del usuario, filtrar por ese ID
        if ($userInput && filter_var($userInput, FILTER_VALIDATE_INT) !== false) {
            $query->where('c.id', $userInput);
        }

        $sucursales = $query->get();

        // Si tiene múltiples sucursales y no se eligió una
        if ($sucursales->count() > 1 && !$userInput) {
            $text = "Veo que tenés varias sucursales a tu nombre:\n\n";
            foreach ($sucursales as $suc) {
                $text .= "*{$suc->id}* -> {$suc->nomemp}\n";
            }
            $text .= "\nRespondeme con el número de cliente de alguna de ellas";

            return [
                'status' => 1,
                'data' => [
                    'actions' => [
                        ['type' => 'sendText', 'text' => $text],
                        ['type' => 'input'],
                    ],
                ],
            ];
        }

        // Obtener cliente específico
        $clienteId = $userInput ?: ($sucursales->first()->id ?? null);
        if (!$clienteId) {
            return ['status' => 0, 'message' => 'Cliente no encontrado'];
        }

        $cliente = DB::connection('bcnsoft')
            ->table('config_clientes AS c')
            ->select('c.*', 'r.nombre AS nomrub')
            ->leftJoin('config_rubros AS r', 'c.id_rubro', '=', 'r.id')
            ->where('c.id', $clienteId)
            ->first();

        if (!$cliente) {
            return ['status' => 0, 'message' => 'Cliente no encontrado'];
        }

        // Actualizar LiveConnect
        $responsableNombre = '';
        if ($cliente->responsable > 0) {
            $resp = DB::connection('pedidosbcn')
                ->table('usuariop')
                ->where('id', $cliente->responsable)
                ->first();
            $responsableNombre = $resp->nombre ?? '';
        }

        $data = $this->buildClientDataArray($cliente, $responsableNombre);
        $this->updateContact($contactoId, $data, $chatId);

        return [
            'status' => 1,
            'client_id' => $cliente->id,
            'client_name' => $cliente->nomemp,
        ];
    }

    // ================================================================
    // HELPERS PRIVADOS
    // ================================================================

    /**
     * Mapea campos dinámicos entrantes a nombres legibles
     */
    private function mapIncomingFields(array $contactData): array
    {
        $dinamicos = $contactData['dinamicos'] ?? [];
        $reverseMap = array_flip($this->dynamicFields);
        $mapped = [];

        foreach ($dinamicos as $key => $value) {
            if (isset($reverseMap[$key])) {
                $mapped[$reverseMap[$key]] = $this->decodeUnicodeEscapes($value);
            }
        }

        // Campos directos
        $nombre = $this->decodeUnicodeEscapes($contactData['nombre'] ?? '');
        $apellidos = $this->decodeUnicodeEscapes($contactData['apellidos'] ?? '');
        $mapped['nombre'] = trim($nombre . ' ' . $apellidos);
        $mapped['email'] = $contactData['email'] ?? '';
        $mapped['telefono'] = $contactData['id_wapi'] ?? '';
        $mapped['id_contacto'] = $contactData['id_contacto'] ?? null;
        $mapped['direccion'] = $this->decodeUnicodeEscapes($contactData['direccion'] ?? '');
        $mapped['localidad'] = $this->decodeUnicodeEscapes($contactData['ciudad'] ?? '');

        return $mapped;
    }

    /**
     * Construye array de campos dinámicos para enviar a LiveConnect
     */
    private function buildDynamicFieldsForUpdate(array $data): array
    {
        $fields = [];
        $mapping = [
            'rubro' => $data['nomrub'] ?? '',
            'tipo_sistema' => $data['tipo_app'] ?? '',
            'nombre_comercial' => $data['nomemp'] ?? '',
            'razon_social' => $data['razon_social'] ?? '',
            'id_cliente' => $data['id'] ?? '',
            'responsable' => $data['responsable_nombre'] ?? '',
            'cuit' => $data['cuit'] ?? '',
            'clave_fiscal' => $data['clave_fiscal'] ?? '',
            'condicion_iva' => $data['coniva_texto'] ?? '',
            'pagina_web' => $data['pagina_web'] ?? '',
            'instagram' => $data['instagram'] ?? '',
            'facebook' => $data['facebook'] ?? '',
            'lic_principal' => $data['lic_principal'] ?? 0,
            'lic_adicional' => $data['lic_adicional'] ?? 0,
            'lic_bonificada' => $data['lic_bonificada'] ?? 0,
            'implementacion_fecha' => $data['implementacion_fecha'] ?? '',
            'implementacion_comanderas' => $data['implementacion_comanderas'] ?? 0,
            'tipo_facturacion' => $data['tipo_facturacion'] ?? '',
            'tipo_conexion' => $data['tipo_conexion'] ?? '',
            'implementacion_personal' => $data['implementacion_personal'] ?? '',
            'implementacion_canales' => $data['implementacion_canales'] ?? '',
            'implementacion_paneles' => $data['implementacion_paneles'] ?? '',
            'lector_barras' => $data['lector_barras'] ?? 0,
        ];

        foreach ($mapping as $key => $value) {
            if (isset($this->dynamicFields[$key])) {
                $fields[$this->dynamicFields[$key]] = $value;
            }
        }

        return $fields;
    }

    /**
     * Construye array de datos del cliente para actualizar LC
     */
    private function buildClientDataArray(object $cliente, string $responsableNombre): array
    {
        $tipoApp = (strtolower(substr($cliente->pack, 0, 2)) === 're') ? 'RESTO' : 'PYME';
        $conivaTexto = TaxCondition::tryFrom($cliente->coniva ?? 5)?->label() ?? '';

        return [
            'nomrub' => $cliente->nomrub ?? '',
            'tipo_app' => $tipoApp,
            'nomemp' => $cliente->nomemp ?? '',
            'razon_social' => $cliente->razon_social ?? '',
            'id' => $cliente->id,
            'responsable_nombre' => $responsableNombre,
            'cuit' => $cliente->cuit ?? '',
            'clave_fiscal' => $cliente->clave_fiscal ?? '',
            'coniva_texto' => $conivaTexto,
            'pagina_web' => $cliente->pagina_web ?? '',
            'instagram' => $cliente->instagram ?? '',
            'facebook' => $cliente->facebook ?? '',
            'lic_principal' => $cliente->lic_principal ?? 0,
            'lic_adicional' => $cliente->lic_adicional ?? 0,
            'lic_bonificada' => $cliente->lic_bonificada ?? 0,
            'implementacion_fecha' => $cliente->implementacion_fecha ?? '',
            'implementacion_comanderas' => $cliente->implementacion_comanderas ?? 0,
            'tipo_facturacion' => $cliente->tipo_facturacion ?? 0,
            'tipo_conexion' => $cliente->tipo_conexion ?? '',
            'implementacion_personal' => $cliente->implementacion_personal ?? '',
            'implementacion_canales' => $cliente->implementacion_canales ?? '',
            'implementacion_paneles' => $cliente->implementacion_paneles ?? '',
            'lector_barras' => $cliente->lector_barras ?? 0,
            'direccion' => $cliente->direccion ?? '',
            'localidad' => $cliente->localidad ?? '',
        ];
    }

    private function buildClientDataArrayFromMapped(array $mapped, int $clienteId, string $tipoSistema): array
    {
        return array_merge($mapped, [
            'id' => $clienteId,
            'tipo_app' => $tipoSistema,
            'responsable_nombre' => $mapped['responsable'] ?? '',
            'coniva_texto' => $mapped['condicion_iva'] ?? '',
            'nomemp' => $mapped['nombre_comercial'] ?? '',
            'nomrub' => $mapped['rubro'] ?? '',
        ]);
    }

    // --- Resolvers ---

    private function resolveIvaId(string $text): int
    {
        return $this->ivaMap[$text] ?? 0;
    }

    private function resolveFacturacionId(string $text): int
    {
        return $this->facturacionMap[$text] ?? 0;
    }

    private function resolveTipoSistema(string $text): int
    {
        return $this->sistemaMap[$text]['tipo'] ?? 0;
    }

    private function resolveRubroId(string $nombre): int
    {
        if (empty($nombre)) return 0;
        return DB::connection('bcnsoft')
            ->table('config_rubros')
            ->where('nombre', $nombre)
            ->value('id') ?? 0;
    }

    private function resolveResponsableId(string $nombre): int
    {
        if (empty($nombre)) return 0;
        return DB::connection('pedidosbcn')
            ->table('usuariop')
            ->where('nombre', $nombre)
            ->value('id') ?? 0;
    }

    private function resolveMotivoDescarteId(string $nombre): int
    {
        if (empty($nombre)) return 0;
        return DB::connection('bcnsoft')
            ->table('contactos_motivos_desc')
            ->where('nombre', $nombre)
            ->value('id') ?? 0;
    }

    // --- Utilidades ---

    private function decodeUnicodeEscapes(mixed $text): string
    {
        if (!is_string($text)) return (string) $text;
        return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($matches) {
            return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UCS-2BE');
        }, $text);
    }

    private function generateRandomCode(int $length): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }

    private function convertDate(string $fecha): ?string
    {
        $formats = ['d/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d', 'Y-m-d', 'd.m.Y'];
        foreach ($formats as $format) {
            $d = \DateTime::createFromFormat($format, $fecha);
            if ($d && $d->format($format) === $fecha) {
                return $d->format('Y-m-d');
            }
        }
        $t = strtotime($fecha);
        return $t ? date('Y-m-d', $t) : null;
    }
}
