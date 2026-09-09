<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * Envios de la tienda web: como entrega el comercio y cuanto cuesta.
 *
 * Modalidades (ver plan, "Envios y zonas"):
 *  - retiro     : en el local, sin costo.
 *  - flota      : reparto propio por zonas (tienda_zonas): radio, codigos postales o poligono.
 *  - correo     : tarifario manual por destino y peso; si el comercio cargo su cuenta
 *                 MiCorreo (Integraciones) y el transportista es Correo Argentino, se
 *                 cotiza con la API y el tarifario queda de respaldo (TiendaCorreoService).
 *  - a_convenir : entra sin costo de envio y el comercio lo cierra por WhatsApp.
 *                 Es la red de contencion cuando no hay cotizacion.
 *
 * Resolucion de zona: gana la mas especifica (poligono > cp > radio mas chico).
 * Si la direccion no cae en ninguna, el checkout ofrece retiro / a convenir, nunca
 * deja al comprador sin salida.
 */
class TiendaEnvioService
{
    public const ZONA_RADIO    = 'radio';
    public const ZONA_CP       = 'cp';
    public const ZONA_POLIGONO = 'poligono';

    public const TARIFA_PROVINCIA = 'provincia';
    public const TARIFA_CP        = 'cp';

    public const PROVINCIAS = [
        'Buenos Aires', 'CABA', 'Catamarca', 'Chaco', 'Chubut', 'Córdoba', 'Corrientes', 'Entre Ríos',
        'Formosa', 'Jujuy', 'La Pampa', 'La Rioja', 'Mendoza', 'Misiones', 'Neuquén', 'Río Negro',
        'Salta', 'San Juan', 'San Luis', 'Santa Cruz', 'Santa Fe', 'Santiago del Estero',
        'Tierra del Fuego', 'Tucumán',
    ];

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
            'retiro_local' => 1, 'retiro_texto' => null,
            'flota_propia' => 0, 'correo_activo' => 0, 'correo_proveedor' => null,
            'a_convenir' => 1,
            'origen_direccion' => null, 'origen_cp' => null, 'origen_lat' => null, 'origen_lng' => null,
            'peso_default' => null, 'bulto_default' => null,
        ];
    }

    /** Configuracion de envios del comercio (defaults si todavia no guardo nada). */
    public function config(): array
    {
        $fila = $this->tabla('tienda_envios_config')->first();

        return $fila ? array_merge(self::configDefault(), (array) $fila) : self::configDefault();
    }

    public function guardarConfig(array $datos): void
    {
        $permitidas = array_keys(self::configDefault());
        $datos = array_intersect_key($datos, array_flip($permitidas));

        foreach (['retiro_local', 'flota_propia', 'correo_activo', 'a_convenir'] as $b) {
            if (array_key_exists($b, $datos)) {
                $datos[$b] = $datos[$b] ? 1 : 0;
            }
        }
        foreach (['origen_lat', 'origen_lng'] as $c) {
            if (array_key_exists($c, $datos)) {
                $datos[$c] = is_numeric($datos[$c]) ? round((float) $datos[$c], 7) : null;
            }
        }
        if (array_key_exists('bulto_default', $datos)) {
            // se guarda normalizado "ancho x alto x largo" en cm, o null si no se entiende
            $b = self::parsearBulto($datos['bulto_default']);
            $datos['bulto_default'] = $b ? "{$b['ancho_cm']}x{$b['alto_cm']}x{$b['largo_cm']}" : null;
        }

        $existente = $this->tabla('tienda_envios_config')->first();
        if ($existente) {
            $this->tabla('tienda_envios_config')->where('id', $existente->id)->update($datos);
        } else {
            $this->tabla('tienda_envios_config')->insert(array_merge(self::configDefault(), $datos));
        }
    }

    // ------------------------------------------------------------------- zonas

    public function zonas(bool $soloActivas = false): array
    {
        $q = $this->tabla('tienda_zonas')->orderBy('orden')->orderBy('id');
        if ($soloActivas) {
            $q->where('activa', 1);
        }

        return $q->get()->map(function ($z) {
            $z->poligono_puntos = self::parsearPoligono($z->poligono);
            $z->cp_array = self::parsearCps($z->cp_lista);
            return $z;
        })->all();
    }

    /**
     * Guarda la lista completa de zonas (alta, modificacion y baja de las que no vienen).
     * Cada zona: id (0 = nueva), nombre, tipo, poligono (JSON o "(lat,lng),..."), cp_lista,
     * radio_km, tarifa, tarifa_por_km, minimo_gratis, demora_estimada, activa.
     */
    public function guardarZonas(array $zonas): void
    {
        $existentes = $this->tabla('tienda_zonas')->pluck('id')->all();
        $vistas = [];

        foreach (array_values($zonas) as $orden => $z) {
            $tipo = in_array($z['tipo'] ?? '', [self::ZONA_RADIO, self::ZONA_CP, self::ZONA_POLIGONO], true) ? $z['tipo'] : self::ZONA_RADIO;
            $puntos = $tipo === self::ZONA_POLIGONO ? self::parsearPoligono($z['poligono'] ?? null) : [];
            $cps = $tipo === self::ZONA_CP ? self::parsearCps($z['cp_lista'] ?? null) : [];

            // Una zona sin definicion geografica no sirve: se descarta
            if (($tipo === self::ZONA_POLIGONO && count($puntos) < 3)
                || ($tipo === self::ZONA_CP && empty($cps))
                || ($tipo === self::ZONA_RADIO && (float) ($z['radio_km'] ?? 0) <= 0)) {
                continue;
            }

            $fila = [
                'nombre'          => mb_substr(trim($z['nombre'] ?? '') ?: 'Zona ' . ($orden + 1), 0, 60),
                'tipo'            => $tipo,
                'poligono'        => $tipo === self::ZONA_POLIGONO ? json_encode($puntos) : null,
                'cp_lista'        => $tipo === self::ZONA_CP ? implode(',', $cps) : null,
                'radio_km'        => $tipo === self::ZONA_RADIO ? round((float) $z['radio_km'], 2) : null,
                'tarifa'          => round((float) ($z['tarifa'] ?? 0), 2),
                'tarifa_por_km'   => $tipo === self::ZONA_RADIO ? round((float) ($z['tarifa_por_km'] ?? 0), 2) : 0,
                'minimo_gratis'   => is_numeric($z['minimo_gratis'] ?? null) && (float) $z['minimo_gratis'] > 0 ? round((float) $z['minimo_gratis'], 2) : null,
                'demora_estimada' => mb_substr(trim((string) ($z['demora_estimada'] ?? '')), 0, 60) ?: null,
                'activa'          => !empty($z['activa']) ? 1 : 0,
                'orden'           => $orden,
            ];

            $id = (int) ($z['id'] ?? 0);
            if ($id > 0 && in_array($id, $existentes, true)) {
                $this->tabla('tienda_zonas')->where('id', $id)->update($fila);
                $vistas[] = $id;
            } else {
                $vistas[] = $this->tabla('tienda_zonas')->insertGetId($fila);
            }
        }

        $borrar = array_diff($existentes, $vistas);
        if (!empty($borrar)) {
            $this->tabla('tienda_zonas')->whereIn('id', $borrar)->delete();
        }
    }

    // ------------------------------------------------------ correo (tarifario)

    public function tarifasCorreo(bool $soloActivas = false): array
    {
        $q = $this->tabla('tienda_correo_tarifas')->orderBy('orden')->orderBy('id');
        if ($soloActivas) {
            $q->where('activa', 1);
        }

        return $q->get()->map(function ($t) {
            $t->provincias_array = self::parsearProvincias($t->provincias);
            return $t;
        })->all();
    }

    /** Guarda la lista completa del tarifario (alta, modificacion y baja de las que no vienen). */
    public function guardarTarifasCorreo(array $tarifas): void
    {
        $existentes = $this->tabla('tienda_correo_tarifas')->pluck('id')->all();
        $vistas = [];

        foreach (array_values($tarifas) as $orden => $t) {
            $tipo = ($t['tipo'] ?? '') === self::TARIFA_CP ? self::TARIFA_CP : self::TARIFA_PROVINCIA;
            $provincias = $tipo === self::TARIFA_PROVINCIA ? self::parsearProvincias($t['provincias'] ?? null) : [];
            $cpDesde = $tipo === self::TARIFA_CP && is_numeric($t['cp_desde'] ?? null) ? (int) $t['cp_desde'] : null;
            $cpHasta = $tipo === self::TARIFA_CP && is_numeric($t['cp_hasta'] ?? null) ? (int) $t['cp_hasta'] : null;

            if (($tipo === self::TARIFA_PROVINCIA && empty($provincias)) || ($tipo === self::TARIFA_CP && ($cpDesde === null || $cpHasta === null))) {
                continue;   // sin destino no sirve
            }

            $fila = [
                'nombre'          => mb_substr(trim($t['nombre'] ?? '') ?: 'Destino ' . ($orden + 1), 0, 60),
                'tipo'            => $tipo,
                'provincias'      => $tipo === self::TARIFA_PROVINCIA ? implode(',', $provincias) : null,
                'cp_desde'        => $cpDesde,
                'cp_hasta'        => $cpHasta,
                'peso_desde'      => max(0, round((float) ($t['peso_desde'] ?? 0), 3)),
                'peso_hasta'      => is_numeric($t['peso_hasta'] ?? null) && (float) $t['peso_hasta'] > 0 ? round((float) $t['peso_hasta'], 3) : null,
                'precio'          => round((float) ($t['precio'] ?? 0), 2),
                'demora_estimada' => mb_substr(trim((string) ($t['demora_estimada'] ?? '')), 0, 60) ?: null,
                'activa'          => !empty($t['activa']) ? 1 : 0,
                'orden'           => $orden,
            ];

            $id = (int) ($t['id'] ?? 0);
            if ($id > 0 && in_array($id, $existentes, true)) {
                $this->tabla('tienda_correo_tarifas')->where('id', $id)->update($fila);
                $vistas[] = $id;
            } else {
                $vistas[] = $this->tabla('tienda_correo_tarifas')->insertGetId($fila);
            }
        }

        $borrar = array_diff($existentes, $vistas);
        if (!empty($borrar)) {
            $this->tabla('tienda_correo_tarifas')->whereIn('id', $borrar)->delete();
        }
    }

    /**
     * Peso total del carrito en kg, cantidad de bultos y las medidas del paquete
     * que se le manda a la API del correo, con tienda_articu.peso/alto/ancho/largo
     * (o los valores por defecto del comercio para los que no los tienen cargados).
     *
     * El paquete es uno solo para todo el carrito: se suman los pesos, se toman el
     * ancho y el alto mayores y se apilan los largos. Es una aproximacion pensada
     * para cotizar; el rotulo real lo arma el comercio en MiCorreo.
     *
     * @param array $carrito items con 'codigo' y 'cantidad'
     * @return array{peso: float, bultos: int, sin_peso: int, sin_medidas: int, peso_gramos: int, ancho_cm: int, alto_cm: int, largo_cm: int}
     */
    public function pesoCarrito(array $carrito, ?array $config = null): array
    {
        $config ??= $this->config();
        $default = (float) ($config['peso_default'] ?? 0);
        $bultoDef = self::bultoDefault($config);
        $codigos = array_map(fn ($i) => $i['codigo'], $carrito);

        $datos = empty($codigos) ? collect() : $this->tabla('tienda_articu')->whereIn('codigo', $codigos)->get()->keyBy('codigo');

        $peso = 0.0;
        $bultos = 0;
        $sinPeso = 0;
        $sinMedidas = 0;
        $ancho = 0.0;
        $alto = 0.0;
        $largo = 0.0;
        foreach ($carrito as $i) {
            $a = $datos[$i['codigo']] ?? null;
            $cant = (float) $i['cantidad'];
            $unidades = max(1, (int) ceil($cant));

            $p = $a && $a->peso !== null ? (float) $a->peso : null;
            if ($p === null) {
                $sinPeso++;
                $p = $default;
            }
            $peso += $p * $cant;
            $bultos += max(1, (int) ($a->bultos ?? 1)) * $unidades;

            $conMedidas = $a && (float) ($a->ancho ?? 0) > 0 && (float) ($a->alto ?? 0) > 0 && (float) ($a->largo ?? 0) > 0;
            if (!$conMedidas) {
                $sinMedidas++;
            }
            $ancho = max($ancho, $conMedidas ? (float) $a->ancho : $bultoDef['ancho_cm']);
            $alto = max($alto, $conMedidas ? (float) $a->alto : $bultoDef['alto_cm']);
            $largo += ($conMedidas ? (float) $a->largo : $bultoDef['largo_cm']) * $unidades;
        }

        $bulto = CorreoArgentinoService::normalizarBulto([
            'peso_gramos' => $peso * 1000,
            'ancho_cm' => $ancho,
            'alto_cm' => $alto,
            'largo_cm' => $largo,
        ]);

        return [
            'peso' => round($peso, 3), 'bultos' => $bultos, 'sin_peso' => $sinPeso, 'sin_medidas' => $sinMedidas,
            'peso_gramos' => $bulto['peso_gramos'], 'ancho_cm' => $bulto['ancho_cm'], 'alto_cm' => $bulto['alto_cm'], 'largo_cm' => $bulto['largo_cm'],
        ];
    }

    /**
     * Medidas por defecto de un articulo sin medidas: las del comercio
     * (bulto_default, "ancho x alto x largo" en cm) o las de config/correo.php.
     * @return array{ancho_cm: float, alto_cm: float, largo_cm: float}
     */
    public static function bultoDefault(array $config): array
    {
        $propio = self::parsearBulto($config['bulto_default'] ?? null);
        if ($propio) {
            return $propio;
        }
        $global = (array) config('correo.bulto_por_defecto');

        return [
            'ancho_cm' => (float) ($global['ancho_cm'] ?? 25),
            'alto_cm' => (float) ($global['alto_cm'] ?? 10),
            'largo_cm' => (float) ($global['largo_cm'] ?? 30),
        ];
    }

    /** "25x10x30", "25 x 10 x 30 cm", "25*10*30" -> medidas en cm; null si no hay tres numeros positivos. */
    public static function parsearBulto($valor): ?array
    {
        if (!is_string($valor) && !is_numeric($valor)) {
            return null;
        }
        $partes = preg_split('/[^\d.,]+/', str_replace(',', '.', (string) $valor), -1, PREG_SPLIT_NO_EMPTY);
        if (count($partes) < 3) {
            return null;
        }
        [$an, $al, $la] = array_map('floatval', array_slice($partes, 0, 3));
        if ($an <= 0 || $al <= 0 || $la <= 0) {
            return null;
        }

        return ['ancho_cm' => (float) ceil($an), 'alto_cm' => (float) ceil($al), 'largo_cm' => (float) ceil($la)];
    }

    /**
     * Cotiza el correo con el tarifario manual. Devuelve la tarifa mas barata que
     * aplique al destino (provincia o cp) y al peso, o null si ninguna cubre.
     * @return array{tarifa: object, importe: float}|null
     */
    public function cotizarCorreo(?string $provincia, ?string $cp, float $pesoKg): ?array
    {
        $prov = self::normalizarProvincia($provincia);
        $cpNum = (int) self::normalizarCp($cp);
        $mejor = null;

        foreach ($this->tarifasCorreo(true) as $t) {
            if ($pesoKg < (float) $t->peso_desde || ($t->peso_hasta !== null && $pesoKg > (float) $t->peso_hasta)) {
                continue;
            }
            $cubre = $t->tipo === self::TARIFA_CP
                ? ($cpNum > 0 && $cpNum >= (int) $t->cp_desde && $cpNum <= (int) $t->cp_hasta)
                : ($prov !== '' && in_array($prov, array_map([self::class, 'normalizarProvincia'], $t->provincias_array), true));
            if (!$cubre) {
                continue;
            }
            if ($mejor === null || (float) $t->precio < (float) $mejor->precio) {
                $mejor = $t;
            }
        }

        return $mejor ? ['tarifa' => $mejor, 'importe' => round((float) $mejor->precio, 2)] : null;
    }

    // ------------------------------------------------------------ correo (API)

    /**
     * El comercio cotiza con la API de MiCorreo: eligio Correo Argentino como
     * transportista y tiene sus credenciales cargadas y activas en Integraciones.
     */
    public function usaCorreoApi(?array $config = null): bool
    {
        $config ??= $this->config();

        return ($config['correo_proveedor'] ?? null) === TiendaCorreoService::PROVEEDOR
            && (new TiendaCorreoService($this->prefijo))->activo();
    }

    /**
     * Cotizacion a domicilio por la API de MiCorreo para el paquete que devuelve
     * pesoCarrito(). Null si el comercio no usa la API, falta el CP de destino o
     * del local, o el Correo no respondio (queda en el log y se cae al tarifario).
     *
     * @return array{importe: float, producto: string, demora: ?string}|null
     */
    private function cotizarCorreoApi(array $cfg, ?string $cp, array $paquete): ?array
    {
        if (($cfg['correo_proveedor'] ?? null) !== TiendaCorreoService::PROVEEDOR) {
            return null;
        }
        if (CorreoArgentinoService::normalizarCodigoPostal($cp) === null || CorreoArgentinoService::normalizarCodigoPostal($cfg['origen_cp'] ?? null) === null) {
            return null;
        }

        $correo = new TiendaCorreoService($this->prefijo);
        $cred = $correo->credenciales();
        if (!$cred) {
            return null;
        }

        $r = $correo->cotizarDomicilio((string) $cfg['origen_cp'], $cp, [
            'peso_gramos' => $paquete['peso_gramos'], 'ancho_cm' => $paquete['ancho_cm'],
            'alto_cm' => $paquete['alto_cm'], 'largo_cm' => $paquete['largo_cm'],
        ], $cred);

        return $r['ok'] ? ['importe' => (float) $r['importe'], 'producto' => $r['producto'], 'demora' => $r['demora']] : null;
    }

    public static function parsearProvincias($valor): array
    {
        $lista = is_array($valor) ? $valor : (is_string($valor) ? explode(',', $valor) : []);
        $out = [];
        foreach ($lista as $p) {
            $n = self::normalizarProvincia($p);
            foreach (self::PROVINCIAS as $oficial) {
                if (self::normalizarProvincia($oficial) === $n) {
                    $out[] = $oficial;
                    break;
                }
            }
        }
        return array_values(array_unique($out));
    }

    /** "Ciudad Autónoma de Buenos Aires" / "capital federal" -> "caba"; sin tildes ni mayusculas. */
    public static function normalizarProvincia(?string $p): string
    {
        $s = mb_strtolower(trim((string) $p));
        $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        $s = preg_replace('/\s+/', ' ', $s);
        if (in_array($s, ['ciudad autonoma de buenos aires', 'capital federal', 'ciudad de buenos aires', 'buenos aires capital', 'c.a.b.a.', 'caba'], true)) {
            return 'caba';
        }
        return str_replace('provincia de ', '', $s);
    }

    // --------------------------------------------------------------- resolver

    /**
     * Zona que cubre una direccion. Devuelve ['zona' => obj, 'distancia_km' => float|null] o null.
     * Prioridad: poligono que la contiene > cp en la lista > radio mas chico que la alcanza.
     */
    public function resolverZona(?float $lat, ?float $lng, ?string $cp, ?array $config = null): ?array
    {
        $config ??= $this->config();
        $zonas = $this->zonas(true);
        if (empty($zonas)) {
            return null;
        }

        $cpNorm = self::normalizarCp($cp);
        $tieneCoords = $lat !== null && $lng !== null;
        $origenOk = is_numeric($config['origen_lat'] ?? null) && is_numeric($config['origen_lng'] ?? null);
        $distancia = ($tieneCoords && $origenOk)
            ? self::distanciaKm($lat, $lng, (float) $config['origen_lat'], (float) $config['origen_lng'])
            : null;

        // 1. poligono
        if ($tieneCoords) {
            foreach ($zonas as $z) {
                if ($z->tipo === self::ZONA_POLIGONO && self::dentroDePoligono($lat, $lng, $z->poligono_puntos)) {
                    return ['zona' => $z, 'distancia_km' => $distancia];
                }
            }
        }

        // 2. codigo postal
        if ($cpNorm !== '') {
            foreach ($zonas as $z) {
                if ($z->tipo === self::ZONA_CP && in_array($cpNorm, $z->cp_array, true)) {
                    return ['zona' => $z, 'distancia_km' => $distancia];
                }
            }
        }

        // 3. radio (el mas chico que alcanza)
        if ($distancia !== null) {
            $mejor = null;
            foreach ($zonas as $z) {
                if ($z->tipo === self::ZONA_RADIO && (float) $z->radio_km >= $distancia) {
                    if ($mejor === null || (float) $z->radio_km < (float) $mejor->radio_km) {
                        $mejor = $z;
                    }
                }
            }
            if ($mejor) {
                return ['zona' => $mejor, 'distancia_km' => $distancia];
            }
        }

        return null;
    }

    /** Costo de envio de una zona para un subtotal dado. */
    public static function cotizar(object $zona, float $subtotal, ?float $distanciaKm): float
    {
        if ($zona->minimo_gratis !== null && (float) $zona->minimo_gratis > 0 && $subtotal >= (float) $zona->minimo_gratis) {
            return 0.0;
        }

        $importe = (float) $zona->tarifa;
        if ($zona->tipo === self::ZONA_RADIO && (float) $zona->tarifa_por_km > 0 && $distanciaKm !== null) {
            $importe += (float) $zona->tarifa_por_km * $distanciaKm;
        }

        return round($importe, 2);
    }

    /**
     * Opciones de entrega para el checkout, con costo, para una direccion.
     * @return array<string, array{modo: string, etiqueta: string, importe: float, disponible: bool, detalle: string, zona_id: ?int}>
     */
    public function opciones(float $subtotal, ?float $lat, ?float $lng, ?string $cp, ?string $provincia = null, ?array $carrito = null): array
    {
        $cfg = $this->config();
        $out = [];

        if ($cfg['retiro_local']) {
            $out[TiendaPedidoService::ENTREGA_RETIRO] = [
                'modo' => TiendaPedidoService::ENTREGA_RETIRO, 'etiqueta' => 'Retiro en el local',
                'importe' => 0.0, 'disponible' => true,
                'detalle' => $cfg['retiro_texto'] ?: ($cfg['origen_direccion'] ?: 'Sin costo'), 'zona_id' => null,
            ];
        }

        if ($cfg['flota_propia']) {
            $r = $this->resolverZona($lat, $lng, $cp, $cfg);
            if ($r) {
                $importe = self::cotizar($r['zona'], $subtotal, $r['distancia_km']);
                $detalle = $r['zona']->nombre . ($r['zona']->demora_estimada ? ' · ' . $r['zona']->demora_estimada : '');
                if ($importe == 0 && $r['zona']->minimo_gratis) {
                    $detalle .= ' · envío bonificado';
                }
                $out[TiendaPedidoService::ENTREGA_FLOTA] = [
                    'modo' => TiendaPedidoService::ENTREGA_FLOTA, 'etiqueta' => 'Envío a domicilio',
                    'importe' => $importe, 'disponible' => true, 'detalle' => $detalle, 'zona_id' => (int) $r['zona']->id,
                ];
            } else {
                $sinDatos = ($lat === null || $lng === null) && self::normalizarCp($cp) === '';
                $out[TiendaPedidoService::ENTREGA_FLOTA] = [
                    'modo' => TiendaPedidoService::ENTREGA_FLOTA, 'etiqueta' => 'Envío a domicilio',
                    'importe' => 0.0, 'disponible' => false,
                    'detalle' => $sinDatos ? 'Ingresá tu dirección para cotizar' : 'No llegamos a esa dirección con reparto propio',
                    'zona_id' => null,
                ];
            }
        }

        if ($cfg['correo_activo']) {
            $tieneDestino = self::normalizarProvincia($provincia) !== '' || self::normalizarCp($cp) !== '';
            $peso = $this->pesoCarrito($carrito ?? [], $cfg);
            $proveedor = $cfg['correo_proveedor'] ? ucwords(str_replace('_', ' ', $cfg['correo_proveedor'])) : 'Correo';

            // 1. API de MiCorreo, si el comercio la tiene: precio real del Correo para ese CP y ese paquete.
            // 2. Si no la tiene o fallo, el tarifario manual del comercio.
            $api = $this->cotizarCorreoApi($cfg, $cp, $peso);
            $cot = !$api && $tieneDestino ? $this->cotizarCorreo($provincia, $cp, $peso['peso']) : null;

            if ($api) {
                $out[TiendaPedidoService::ENTREGA_CORREO] = [
                    'modo' => TiendaPedidoService::ENTREGA_CORREO, 'etiqueta' => 'Envío por Correo Argentino',
                    'importe' => $api['importe'], 'disponible' => true,
                    'detalle' => $api['producto'] . ' a domicilio' . ($api['demora'] ? ' · ' . $api['demora'] : '') . ' · ' . self::formatoPeso($peso['peso']),
                    'zona_id' => null, 'transportista' => TiendaCorreoService::PROVEEDOR, 'cotizacion' => 'api',
                ];
            } elseif ($cot) {
                $out[TiendaPedidoService::ENTREGA_CORREO] = [
                    'modo' => TiendaPedidoService::ENTREGA_CORREO, 'etiqueta' => 'Envío por ' . $proveedor,
                    'importe' => $cot['importe'], 'disponible' => true,
                    'detalle' => $cot['tarifa']->nombre . ($cot['tarifa']->demora_estimada ? ' · ' . $cot['tarifa']->demora_estimada : '') . ' · ' . self::formatoPeso($peso['peso']),
                    'zona_id' => null, 'transportista' => $cfg['correo_proveedor'] ?: 'correo',
                ];
            } else {
                $out[TiendaPedidoService::ENTREGA_CORREO] = [
                    'modo' => TiendaPedidoService::ENTREGA_CORREO, 'etiqueta' => 'Envío por ' . $proveedor,
                    'importe' => 0.0, 'disponible' => false,
                    'detalle' => !$tieneDestino
                        ? ($this->usaCorreoApi($cfg) ? 'Ingresá tu código postal para cotizar' : 'Ingresá provincia o código postal para cotizar')
                        : 'No tenemos tarifa de correo para ese destino o peso (' . self::formatoPeso($peso['peso']) . ')',
                    'zona_id' => null, 'transportista' => $cfg['correo_proveedor'] ?: 'correo',
                ];
            }
        }

        if ($cfg['a_convenir']) {
            $out[TiendaPedidoService::ENTREGA_A_CONVENIR] = [
                'modo' => TiendaPedidoService::ENTREGA_A_CONVENIR, 'etiqueta' => 'Envío a convenir',
                'importe' => 0.0, 'disponible' => true,
                'detalle' => 'El comercio te contacta para coordinar el envío y su costo', 'zona_id' => null,
            ];
        }

        return $out;
    }

    // ------------------------------------------------------------- geocoding

    /**
     * Coordenadas de una direccion via Google Geocoding (misma clave que el mapa).
     * Devuelve [lat, lng] o null. Se usa cuando el navegador no pudo resolverla
     * (sin Places, bloqueadores) y hay zonas por radio o poligono.
     */
    public static function geocodificar(string $direccion): ?array
    {
        $key = config('app.google_maps_server_key') ?: config('app.google_maps_api_key');
        $direccion = trim($direccion);
        if (!$key || $direccion === '') {
            return null;
        }

        // Se cachea tambien el "no encontrado" (false) para no pegarle a Google en cada render
        $resultado = Cache::remember('tienda_geo_' . md5($direccion), now()->addDays(30), function () use ($direccion, $key) {
            try {
                $r = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $direccion, 'region' => 'ar', 'key' => $key,
                ]);
                $loc = $r->json('results.0.geometry.location');
                return ($loc && isset($loc['lat'], $loc['lng'])) ? [(float) $loc['lat'], (float) $loc['lng']] : false;
            } catch (\Throwable $e) {
                return false;
            }
        });

        return $resultado ?: null;
    }

    public static function formatoPeso(float $kg): string
    {
        return $kg >= 1 ? rtrim(rtrim(number_format($kg, 2, ',', '.'), '0'), ',') . ' kg' : (int) round($kg * 1000) . ' g';
    }

    // -------------------------------------------------------------- geometria

    public static function distanciaKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return round(2 * $r * asin(min(1, sqrt($a))), 3);
    }

    /** Ray casting. $puntos = [[lat,lng], ...] */
    public static function dentroDePoligono(float $lat, float $lng, array $puntos): bool
    {
        $n = count($puntos);
        if ($n < 3) {
            return false;
        }

        $dentro = false;
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$yi, $xi] = [(float) $puntos[$i][0], (float) $puntos[$i][1]];
            [$yj, $xj] = [(float) $puntos[$j][0], (float) $puntos[$j][1]];
            $cruza = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi);
            if ($cruza) {
                $dentro = !$dentro;
            }
        }

        return $dentro;
    }

    /** Acepta JSON [[lat,lng],...] o el formato de resto "(lat,lng),(lat,lng)". */
    public static function parsearPoligono($valor): array
    {
        if (is_array($valor)) {
            $puntos = $valor;
        } elseif (is_string($valor) && $valor !== '') {
            $puntos = json_decode($valor, true);
            if (!is_array($puntos)) {
                $puntos = [];
                if (preg_match_all('/\(\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*\)/', $valor, $m, PREG_SET_ORDER)) {
                    foreach ($m as $p) {
                        $puntos[] = [(float) $p[1], (float) $p[2]];
                    }
                }
            }
        } else {
            return [];
        }

        $out = [];
        foreach ($puntos as $p) {
            $lat = $p[0] ?? $p['lat'] ?? null;
            $lng = $p[1] ?? $p['lng'] ?? null;
            if (is_numeric($lat) && is_numeric($lng)) {
                $out[] = [round((float) $lat, 7), round((float) $lng, 7)];
            }
        }

        return $out;
    }

    public static function parsearCps($valor): array
    {
        if (!is_string($valor) || trim($valor) === '') {
            return [];
        }

        $cps = array_map([self::class, 'normalizarCp'], preg_split('/[\s,;]+/', $valor));

        return array_values(array_unique(array_filter($cps, fn ($c) => $c !== '')));
    }

    /** "B6600ABC" -> "6600"; " 1425 " -> "1425". Se queda con los digitos. */
    public static function normalizarCp(?string $cp): string
    {
        return preg_replace('/\D+/', '', (string) $cp) ?? '';
    }
}
