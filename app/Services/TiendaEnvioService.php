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
 *  - correo     : F2b (tarifario manual, luego API). Todavia no.
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
    public function opciones(float $subtotal, ?float $lat, ?float $lng, ?string $cp): array
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
        $key = config('app.google_maps_api_key');
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
