<?php

namespace App\Services;

use Anthropic\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Enriquecimiento del catalogo con IA (F4): descripcion larga, texto para
 * buscadores y categoria sugerida, por lote, para los articulos que no tienen.
 *
 * Es la unica excepcion deliberada a "la cuenta es del comercio": corre con la
 * clave de BCN (ANTHROPIC_API_KEY) porque cuesta centavos y decide si la tienda
 * vende. Nada se publica solo: las propuestas quedan en tienda_articu.ia_sugerencia
 * hasta que el comercio las acepta (o las edita) desde el panel.
 */
class TiendaIaService
{
    public const LOTE_MAX = 15;

    private string $prefijo;

    public function __construct(string $prefijo)
    {
        $this->prefijo = $prefijo;
    }

    private function tabla(string $nombre)
    {
        return DB::connection('client_db')->table($this->prefijo . $nombre);
    }

    public static function disponible(): bool
    {
        return (bool) config('services.anthropic.key');
    }

    /** Codigos de articulos a la venta sin descripcion ni propuesta pendiente (hasta $limite). */
    public function candidatos(int $limite = self::LOTE_MAX): array
    {
        return $this->tabla('articu as a')
            ->leftJoin($this->prefijo . 'tienda_articu as ta', 'a.CODIGO', '=', 'ta.codigo')
            ->where('a.NOSALE', 0)
            ->where(fn ($q) => $q->whereNull('ta.descripcion_larga')->orWhere('ta.descripcion_larga', ''))
            ->whereNull('ta.ia_sugerencia')
            ->orderBy('a.NOMBRE')
            ->limit($limite)
            ->pluck('a.CODIGO')
            ->all();
    }

    /** Propuestas pendientes de revision: [codigo, nombre, sugerencia (array), fecha]. */
    public function pendientes(int $limite = 50): array
    {
        return $this->tabla('tienda_articu as ta')
            ->join($this->prefijo . 'articu as a', 'a.CODIGO', '=', 'ta.codigo')
            ->whereNotNull('ta.ia_sugerencia')
            ->orderBy('ta.ia_fecha')
            ->limit($limite)
            ->get(['ta.codigo', 'a.NOMBRE as nombre', 'ta.ia_sugerencia', 'ta.ia_fecha'])
            ->map(fn ($r) => ['codigo' => $r->codigo, 'nombre' => $r->nombre, 'sugerencia' => json_decode($r->ia_sugerencia, true) ?: [], 'fecha' => $r->ia_fecha])
            ->all();
    }

    /**
     * Pide a Claude las propuestas para un lote de articulos y las deja pendientes.
     * Devuelve cuantas se generaron. Lanza excepcion si la API falla.
     */
    public function generar(array $codigos, string $nombreComercio = '', string $rubroComercio = ''): int
    {
        if (!self::disponible()) {
            throw new \RuntimeException('Falta configurar la clave de la API (ANTHROPIC_API_KEY).');
        }
        $codigos = array_slice(array_values(array_unique($codigos)), 0, self::LOTE_MAX);
        if (empty($codigos)) {
            return 0;
        }

        $articulos = $this->tabla('articu as a')
            ->leftJoin($this->prefijo . 'rubros as r', 'r.CODIGO', '=', 'a.RUBRO')
            ->leftJoin($this->prefijo . 'marcas as m', 'm.CODIGO', '=', 'a.MARCA')
            ->leftJoin($this->prefijo . 'deptos as d', 'd.CODIGO', '=', 'a.DEPTO')
            ->whereIn('a.CODIGO', $codigos)
            ->get(['a.CODIGO', 'a.NOMBRE', 'a.PRECIOVEN', 'r.NOMBRE as rubro', 'm.NOMBRE as marca', 'd.NOMBRE as depto']);
        if ($articulos->isEmpty()) {
            return 0;
        }

        $lista = $articulos->map(fn ($a) => [
            'codigo' => (string) $a->CODIGO,
            'nombre' => trim($a->NOMBRE),
            'rubro' => trim((string) $a->rubro) ?: null,
            'marca' => trim((string) $a->marca) ?: null,
            'departamento' => trim((string) $a->depto) ?: null,
        ])->values()->all();

        $system = "Sos el redactor del catálogo online de un comercio argentino"
            . ($nombreComercio ? " llamado \"{$nombreComercio}\"" : '')
            . ($rubroComercio ? " del rubro {$rubroComercio}" : '') . ".\n"
            . "Para cada artículo escribí, en español rioplatense (voseo), neutro y sin exagerar:\n"
            . "- descripcion: 2 a 4 oraciones útiles para decidir la compra. Solo lo que se deduce del nombre, rubro y marca; si no sabés un dato (medidas, material, sabor), no lo inventes ni lo insinúes.\n"
            . "- meta_titulo: hasta 60 caracteres, para el título de la página.\n"
            . "- meta_descripcion: hasta 155 caracteres, para el resultado de Google.\n"
            . "- categoria: una categoría corta y comercial (por ejemplo \"Accesorios para bebé\"), sin repetir la marca.\n"
            . "Nombres de artículo en mayúsculas o con códigos: interpretalos, no los copies textuales. Devolvé una entrada por cada código recibido, con el mismo código.";

        $schema = [
            'type' => 'object',
            'properties' => [
                'articulos' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'codigo' => ['type' => 'string'],
                            'descripcion' => ['type' => 'string'],
                            'meta_titulo' => ['type' => 'string'],
                            'meta_descripcion' => ['type' => 'string'],
                            'categoria' => ['type' => 'string'],
                        ],
                        'required' => ['codigo', 'descripcion', 'meta_titulo', 'meta_descripcion', 'categoria'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['articulos'],
            'additionalProperties' => false,
        ];

        $client = new Client(apiKey: config('services.anthropic.key'));
        $mensaje = $client->messages->create(
            model: config('services.anthropic.model', 'claude-opus-5'),
            maxTokens: 16000,
            system: [['type' => 'text', 'text' => $system, 'cacheControl' => ['type' => 'ephemeral']]],
            messages: [['role' => 'user', 'content' => "Artículos:\n" . json_encode($lista, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)]],
            outputConfig: ['format' => ['type' => 'json_schema', 'schema' => $schema]],
        );

        if ($mensaje->stopReason === 'refusal') {
            throw new \RuntimeException('La IA no pudo procesar este lote.');
        }

        $json = null;
        foreach ($mensaje->content as $block) {
            if ($block->type === 'text') {
                $json = json_decode($block->text, true);
                break;
            }
        }
        if (!is_array($json) || empty($json['articulos'])) {
            Log::warning('TiendaIa: respuesta sin articulos', ['prefijo' => $this->prefijo]);
            return 0;
        }

        $validos = array_flip(array_map('strval', $codigos));
        $n = 0;
        foreach ($json['articulos'] as $a) {
            $codigo = (string) ($a['codigo'] ?? '');
            if (!isset($validos[$codigo])) {
                continue;
            }
            $sug = [
                'descripcion' => mb_substr(trim($a['descripcion'] ?? ''), 0, 1500),
                'meta_titulo' => mb_substr(trim($a['meta_titulo'] ?? ''), 0, 120),
                'meta_descripcion' => mb_substr(trim($a['meta_descripcion'] ?? ''), 0, 255),
                'categoria' => mb_substr(trim($a['categoria'] ?? ''), 0, 60),
            ];
            if ($sug['descripcion'] === '') {
                continue;
            }
            $this->upsert($codigo, ['ia_sugerencia' => json_encode($sug, JSON_UNESCAPED_UNICODE), 'ia_fecha' => now()]);
            $n++;
        }

        return $n;
    }

    /** El comercio acepta la propuesta (con o sin retoques): pasa a los campos definitivos. */
    public function aceptar($codigo, ?array $editada = null): void
    {
        $fila = $this->tabla('tienda_articu')->where('codigo', $codigo)->first(['ia_sugerencia']);
        $sug = $editada ?? (json_decode($fila->ia_sugerencia ?? 'null', true) ?: []);
        if (empty($sug['descripcion'])) {
            return;
        }
        $this->upsert((string) $codigo, [
            'descripcion_larga' => mb_substr(trim($sug['descripcion']), 0, 1500),
            'meta_titulo' => mb_substr(trim($sug['meta_titulo'] ?? ''), 0, 120) ?: null,
            'meta_descripcion' => mb_substr(trim($sug['meta_descripcion'] ?? ''), 0, 255) ?: null,
            'ia_sugerencia' => null,
            'ia_fecha' => null,
        ]);
    }

    public function descartar($codigo): void
    {
        $this->tabla('tienda_articu')->where('codigo', $codigo)->update(['ia_sugerencia' => null, 'ia_fecha' => null]);
    }

    private function upsert(string $codigo, array $datos): void
    {
        if ($this->tabla('tienda_articu')->where('codigo', $codigo)->exists()) {
            $this->tabla('tienda_articu')->where('codigo', $codigo)->update($datos);
        } else {
            $this->tabla('tienda_articu')->insert(array_merge(['codigo' => $codigo], $datos));
        }
    }
}
