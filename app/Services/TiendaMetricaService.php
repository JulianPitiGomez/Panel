<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Metricas de la tienda para el comercio (F5): visitas, mas vistos, pedidos por dia
 * y busquedas sin resultado. Contadores diarios agregados, sin datos personales.
 *
 * Tambien maneja la "version" del catalogo para la cache del trafico anonimo:
 * cada cambio desde el panel (fotos, destacados, pesos, config) la sube y las
 * paginas cacheadas dejan de valer. Lo que cambia Fivewin (precios, stock,
 * promos) no avisa: por eso la cache dura poco (TTL corto).
 */
class TiendaMetricaService
{
    public const VISITA = 'visita';
    public const VISTA_PRODUCTO = 'vista_producto';
    public const BUSQUEDA_SIN_RESULTADO = 'busqueda_sin_resultado';

    /** Segundos que vive una pagina de catalogo cacheada para visitantes anonimos. */
    public const CACHE_TTL = 300;

    private string $prefijo;

    public function __construct(string $prefijo)
    {
        $this->prefijo = $prefijo;
    }

    private function tabla()
    {
        return DB::connection('client_db')->table($this->prefijo . 'tienda_metricas');
    }

    /** Suma 1 al contador del dia. Nunca rompe la pagina si falla. */
    public function registrar(string $tipo, string $clave = ''): void
    {
        try {
            $t = $this->prefijo . 'tienda_metricas';
            DB::connection('client_db')->statement(
                "INSERT INTO `{$t}` (fecha, tipo, clave, cantidad) VALUES (CURDATE(), ?, ?, 1) ON DUPLICATE KEY UPDATE cantidad = cantidad + 1",
                [$tipo, mb_substr(trim($clave), 0, 120)]
            );
        } catch (\Throwable $e) {
            // la metrica no es critica
        }
    }

    /** Resumen de los ultimos $dias dias para el panel del comercio. */
    public function resumen(int $dias = 30): array
    {
        $desde = today()->subDays($dias - 1)->toDateString();

        $visitasPorDia = $this->tabla()->where('tipo', self::VISITA)->where('fecha', '>=', $desde)
            ->groupBy('fecha')->orderBy('fecha')->selectRaw('fecha, SUM(cantidad) AS n')->pluck('n', 'fecha')->all();

        $pedidosPorDia = DB::connection('client_db')->table($this->prefijo . 'tienda_pedidos')
            ->where('creado', '>=', $desde . ' 00:00:00')
            ->groupBy(DB::raw('DATE(creado)'))->orderBy(DB::raw('DATE(creado)'))
            ->selectRaw('DATE(creado) AS fecha, COUNT(*) AS n')->pluck('n', 'fecha')->all();

        $masVistos = $this->tabla()->where('tipo', self::VISTA_PRODUCTO)->where('fecha', '>=', $desde)
            ->groupBy('clave')->orderByRaw('SUM(cantidad) DESC')->limit(10)
            ->selectRaw('clave AS codigo, SUM(cantidad) AS n')->get();
        $nombres = $masVistos->isEmpty() ? collect() : DB::connection('client_db')->table($this->prefijo . 'articu')
            ->whereIn('CODIGO', $masVistos->pluck('codigo')->all())->pluck('NOMBRE', 'CODIGO');
        $masVistos = $masVistos->map(fn ($m) => ['codigo' => $m->codigo, 'nombre' => $nombres[$m->codigo] ?? ('#' . $m->codigo), 'n' => (int) $m->n])->all();

        $sinResultado = $this->tabla()->where('tipo', self::BUSQUEDA_SIN_RESULTADO)->where('fecha', '>=', $desde)
            ->groupBy('clave')->orderByRaw('SUM(cantidad) DESC')->limit(10)
            ->selectRaw('clave AS termino, SUM(cantidad) AS n')->get()->map(fn ($m) => ['termino' => $m->termino, 'n' => (int) $m->n])->all();

        // Serie diaria completa (con ceros) para el grafico
        $serie = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $f = today()->subDays($i)->toDateString();
            $serie[] = ['fecha' => $f, 'visitas' => (int) ($visitasPorDia[$f] ?? 0), 'pedidos' => (int) ($pedidosPorDia[$f] ?? 0)];
        }

        return [
            'dias' => $dias,
            'visitas' => array_sum(array_column($serie, 'visitas')),
            'pedidos' => array_sum(array_column($serie, 'pedidos')),
            'serie' => $serie,
            'mas_vistos' => $masVistos,
            'sin_resultado' => $sinResultado,
        ];
    }

    // ---------------------------------------------------- cache del catalogo

    public static function versionCatalogo(string $prefijo): int
    {
        return (int) Cache::get("tienda_cat_ver_{$prefijo}", 1);
    }

    /** El panel cambio algo del catalogo: las paginas cacheadas dejan de valer. */
    public static function invalidarCatalogo(string $prefijo): void
    {
        Cache::put("tienda_cat_ver_{$prefijo}", self::versionCatalogo($prefijo) + 1, now()->addDays(30));
    }

    /** Clave de cache para una consulta del catalogo anonimo. */
    public static function claveCache(string $prefijo, string $que, array $parametros): string
    {
        return 'tienda_cat_' . $prefijo . '_' . self::versionCatalogo($prefijo) . '_' . $que . '_' . md5(json_encode($parametros));
    }
}
