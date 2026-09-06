<?php

namespace App\Services;

use App\Models\Cliente;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Dominio propio de la tienda (F6): la tienda se publica en el dominio del
 * cliente (tienda.sucomercio.com.ar o su dominio raiz), apuntado por CNAME o A
 * a nuestro servidor. La URL interna /web/{slug} sigue funcionando.
 *
 * Todo vive en el JSON de config_clientes_web (sin tablas nuevas en bcn_config):
 *   tienda_dominio, tienda_dominio_verificado, tienda_dominio_verificado_fecha,
 *   tienda_dominio_ultimo_chequeo, tienda_dominio_ultimo_error
 *
 * Verificacion de propiedad: el dominio tiene que resolver a nosotros (CNAME al
 * host de config tienda.dominio_cname, o A a una IP de tienda.dominio_ips).
 * Quien controla la zona DNS controla el dominio: no hace falta otro token.
 *
 * Certificado: Caddy con on-demand TLS pregunta a /tienda/dominio-permitido antes
 * de emitirlo con Let's Encrypt (ver docs/tienda/dominio-propio.md).
 */
class TiendaDominioService
{
    public const CACHE_TTL = 600;

    // ------------------------------------------------------------ hosts

    /** Nuestros hosts: el de APP_URL, los extra del .env, el destino del CNAME y los locales. */
    public static function hostsPropios(): array
    {
        $hosts = [parse_url((string) config('app.url'), PHP_URL_HOST), config('tienda.dominio_cname'), 'localhost', '127.0.0.1'];
        foreach (config('tienda.dominio_hosts_propios', []) as $h) {
            $hosts[] = $h;
        }

        return array_values(array_unique(array_filter(array_map(fn ($h) => strtolower(trim((string) $h, ". \t")), $hosts))));
    }

    public static function esHostPropio(string $host): bool
    {
        $host = strtolower(trim($host, '. '));
        $host = preg_replace('/:\d+$/', '', $host);

        return in_array($host, self::hostsPropios(), true);
    }

    /** Normaliza lo que escribe el comercio: sin esquema, sin ruta, sin puerto, minusculas. Null si no es un host valido. */
    public static function normalizar(?string $entrada): ?string
    {
        $h = strtolower(trim((string) $entrada));
        $h = preg_replace('#^[a-z]+://#', '', $h);
        $h = explode('/', $h)[0];
        $h = explode('?', $h)[0];
        $h = preg_replace('/:\d+$/', '', $h);
        $h = rtrim($h, '.');
        if ($h === '' || strlen($h) > 253 || !str_contains($h, '.')) {
            return null;
        }
        if (!preg_match('/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $h)) {
            return null;
        }

        return $h;
    }

    // ------------------------------------------------------------ resolucion por host

    /** Cliente (config_clientes) cuyo dominio verificado es $host, o null. Cacheado. */
    public static function clientePorHost(string $host): ?Cliente
    {
        $host = strtolower(preg_replace('/:\d+$/', '', trim($host, '. ')));
        if ($host === '' || self::esHostPropio($host)) {
            return null;
        }

        $id = Cache::remember("tienda_dominio_host_{$host}", self::CACHE_TTL, function () use ($host) {
            $fila = self::filaPorDominio($host);
            if (!$fila) {
                return 0;
            }
            $datos = json_decode($fila->configuracion, true) ?: [];
            if (($datos['tienda_dominio'] ?? null) !== $host || empty($datos['tienda_dominio_verificado'])) {
                return 0;
            }

            return (int) $fila->cliente_id;
        });

        if (!$id) {
            return null;
        }
        $cliente = Cliente::find($id);

        return $cliente && $cliente->tienda && TiendaService::habilitada($cliente->id) ? $cliente : null;
    }

    /** Fila de config_clientes_web que tiene ese dominio (MariaDB 10.0: sin funciones JSON, se busca por texto). */
    private static function filaPorDominio(string $host, ?int $excepto = null): ?object
    {
        $q = DB::connection('config')->table('config_clientes_web')
            ->where('configuracion', 'like', '%"tienda_dominio":' . json_encode($host) . '%');
        if ($excepto) {
            $q->where('cliente_id', '!=', $excepto);
        }

        return $q->first(['cliente_id', 'configuracion']);
    }

    public static function olvidarCache(?string $host): void
    {
        if ($host) {
            Cache::forget('tienda_dominio_host_' . strtolower($host));
        }
    }

    // ------------------------------------------------------------ alta, verificacion, baja

    /** Guarda el dominio (sin verificar todavia). Devuelve ['ok', 'mensaje']. */
    public static function guardar(int $clienteId, ?string $entrada): array
    {
        $datos = TiendaService::configuracionWeb($clienteId);
        $anterior = $datos['tienda_dominio'] ?? null;

        $host = self::normalizar($entrada);
        if (!$host) {
            return ['ok' => false, 'mensaje' => 'Escribí el dominio como tienda.tunegocio.com.ar (sin http:// ni barras).'];
        }
        if (self::esHostPropio($host)) {
            return ['ok' => false, 'mensaje' => 'Ese dominio es nuestro. Usá un dominio tuyo o un subdominio tuyo.'];
        }
        if (self::filaPorDominio($host, $clienteId)) {
            return ['ok' => false, 'mensaje' => 'Ese dominio ya está cargado en otra tienda. Si es tuyo, escribinos.'];
        }

        TiendaService::guardarConfiguracionWeb($clienteId, [
            'tienda_dominio'                 => $host,
            'tienda_dominio_verificado'      => $host === $anterior ? (int) !empty($datos['tienda_dominio_verificado']) : 0,
            'tienda_dominio_verificado_fecha'=> $host === $anterior ? ($datos['tienda_dominio_verificado_fecha'] ?? null) : null,
            'tienda_dominio_ultimo_chequeo'  => null,
            'tienda_dominio_ultimo_error'    => null,
        ]);
        self::olvidarCache($anterior);
        self::olvidarCache($host);

        return ['ok' => true, 'mensaje' => "Dominio {$host} guardado. Ahora apuntalo a nuestro servidor y verificalo."];
    }

    public static function quitar(int $clienteId): void
    {
        $datos = TiendaService::configuracionWeb($clienteId);
        TiendaService::guardarConfiguracionWeb($clienteId, [
            'tienda_dominio'                 => null,
            'tienda_dominio_verificado'      => 0,
            'tienda_dominio_verificado_fecha'=> null,
            'tienda_dominio_ultimo_chequeo'  => null,
            'tienda_dominio_ultimo_error'    => null,
        ]);
        self::olvidarCache($datos['tienda_dominio'] ?? null);
    }

    /**
     * Consulta el DNS y marca el dominio como verificado si apunta a nosotros.
     * Devuelve ['ok', 'mensaje', 'cname', 'ips'].
     */
    public static function verificar(int $clienteId): array
    {
        $datos = TiendaService::configuracionWeb($clienteId);
        $host = $datos['tienda_dominio'] ?? null;
        if (!$host) {
            return ['ok' => false, 'mensaje' => 'Primero cargá un dominio.', 'cname' => null, 'ips' => []];
        }

        $objetivo = strtolower(rtrim((string) config('tienda.dominio_cname'), '.'));
        $ipsNuestras = array_map('trim', config('tienda.dominio_ips', []));

        [$cname, $ips] = self::resolver($host);

        $porCname = $objetivo !== '' && $cname !== null && strtolower(rtrim($cname, '.')) === $objetivo;
        $porIp = !empty($ipsNuestras) && count(array_intersect($ips, $ipsNuestras)) > 0;
        $ok = $porCname || $porIp;

        $error = null;
        if (!$ok) {
            if ($cname === null && empty($ips)) {
                $error = 'El dominio todavía no resuelve a ningún lado. Si recién cargaste el registro, esperá unos minutos (la propagación puede tardar hasta 24 h) y volvé a verificar.';
            } elseif ($cname !== null) {
                $error = "El dominio apunta a {$cname}, y tiene que apuntar a " . ($objetivo ?: 'nuestro servidor') . '.';
            } else {
                $error = 'El dominio apunta a ' . implode(', ', $ips) . ', y tiene que apuntar a ' . ($objetivo ? "{$objetivo} (CNAME)" : 'nuestro servidor') . (!empty($ipsNuestras) ? ' o a ' . implode(', ', $ipsNuestras) . ' (A)' : '') . '.';
            }
        }

        TiendaService::guardarConfiguracionWeb($clienteId, [
            'tienda_dominio_verificado'      => $ok ? 1 : 0,
            'tienda_dominio_verificado_fecha'=> $ok ? now()->toDateTimeString() : null,
            'tienda_dominio_ultimo_chequeo'  => now()->toDateTimeString(),
            'tienda_dominio_ultimo_error'    => $error,
        ]);
        self::olvidarCache($host);

        return [
            'ok'      => $ok,
            'mensaje' => $ok ? "Listo: {$host} apunta a nuestro servidor. La tienda ya responde ahí; el certificado se emite solo la primera vez que alguien entra." : $error,
            'cname'   => $cname,
            'ips'     => $ips,
        ];
    }

    /** [cname o null, [ips]] del host, sin romper si no hay DNS. */
    private static function resolver(string $host): array
    {
        $cname = null;
        $ips = [];
        try {
            $rc = @dns_get_record($host, DNS_CNAME) ?: [];
            foreach ($rc as $r) {
                if (!empty($r['target'])) {
                    $cname = $r['target'];
                    break;
                }
            }
            $ra = @dns_get_record($host, DNS_A) ?: [];
            foreach ($ra as $r) {
                if (!empty($r['ip'])) {
                    $ips[] = $r['ip'];
                }
            }
        } catch (\Throwable $e) {
            // sin resolver: se informa como "no apunta"
        }

        return [$cname, $ips];
    }

    // ------------------------------------------------------------ para el panel

    /** Estado para mostrar en Mi Tienda. */
    public static function estado(array $datos): array
    {
        $host = $datos['tienda_dominio'] ?? null;

        return [
            'dominio'    => $host,
            'verificado' => $host && !empty($datos['tienda_dominio_verificado']),
            'fecha'      => $datos['tienda_dominio_verificado_fecha'] ?? null,
            'chequeo'    => $datos['tienda_dominio_ultimo_chequeo'] ?? null,
            'error'      => $datos['tienda_dominio_ultimo_error'] ?? null,
            'cname'      => config('tienda.dominio_cname'),
            'ips'        => config('tienda.dominio_ips', []),
        ];
    }

    /** URL publica de la tienda: el dominio propio si esta verificado, si no la interna /web/{slug}. */
    public static function urlPublica(?string $slug, array $datos): ?string
    {
        if (!$slug) {
            return null;
        }
        $host = $datos['tienda_dominio'] ?? null;
        if ($host && !empty($datos['tienda_dominio_verificado'])) {
            return 'https://' . $host;
        }

        return url('/web/' . $slug);
    }
}
