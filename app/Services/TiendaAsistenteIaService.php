<?php

namespace App\Services;

use Anthropic\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Asistente de IA de la tienda (F7), con la clave del COMERCIO (tienda_integraciones,
 * servicio 'ia'). Dos cosas, las dos opcionales y las dos sin escribir nada solas:
 *
 *  - Busqueda que entiende: cuando el buscador no encuentra nada, la IA traduce lo
 *    que escribio el comprador a palabras del catalogo y, si corresponde, a un
 *    rubro / marca / departamento. La busqueda se vuelve a correr con eso.
 *  - Pedido desde un mensaje: el comprador escribe "2 kg de arroz, 3 gaseosas cola y
 *    una yerba" y la IA lo separa en renglones con cantidad; despues elige, entre los
 *    candidatos que da la base, cual es cada uno. El comprador revisa y agrega.
 *
 * Distinto de TiendaIaService (descripciones al dar de alta, con la clave de BCN).
 */
class TiendaAsistenteIaService
{
    public const MODELO_DEFAULT = 'claude-haiku-4-5-20251001';   // rapido y barato: es para el comprador en vivo
    public const MAX_CANDIDATOS = 8;

    private string $prefijo;

    public function __construct(string $prefijo)
    {
        $this->prefijo = $prefijo;
    }

    private function tabla(string $nombre)
    {
        return DB::connection('client_db')->table($this->prefijo . $nombre);
    }

    /** Hay clave del comercio cargada y activa. */
    public function disponible(): bool
    {
        return (new TiendaIntegracionService($this->prefijo))->claveIa() !== null;
    }

    private function cliente(): ?Client
    {
        $clave = (new TiendaIntegracionService($this->prefijo))->claveIa();

        return $clave ? new Client(apiKey: $clave) : null;
    }

    private function modelo(): string
    {
        $fila = (new TiendaIntegracionService($this->prefijo))->obtener(TiendaIntegracionService::IA);

        return trim((string) ($fila->modelo ?? '')) ?: self::MODELO_DEFAULT;
    }

    /** Llamada con salida JSON validada por schema. Null si falla (nunca rompe la tienda). */
    private function pedirJson(string $system, string $user, array $schema, int $maxTokens = 1500): ?array
    {
        $cliente = $this->cliente();
        if (!$cliente) {
            return null;
        }
        try {
            $msg = $cliente->messages->create(
                model: $this->modelo(),
                maxTokens: $maxTokens,
                system: $system,
                messages: [['role' => 'user', 'content' => $user]],
                outputConfig: ['format' => ['type' => 'json_schema', 'schema' => $schema]],
            );
            if ($msg->stopReason === 'refusal') {
                return null;
            }
            foreach ($msg->content as $block) {
                if ($block->type === 'text') {
                    $json = json_decode($block->text, true);

                    return is_array($json) ? $json : null;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('TiendaAsistenteIa: ' . $e->getMessage(), ['prefijo' => $this->prefijo]);
        }

        return null;
    }

    /** Prueba de la clave desde el panel: devuelve el nombre del modelo o lanza. */
    public function probar(string $clave, ?string $modelo = null): string
    {
        $cliente = new Client(apiKey: $clave);
        $msg = $cliente->messages->create(
            model: trim((string) $modelo) ?: self::MODELO_DEFAULT,
            maxTokens: 20,
            messages: [['role' => 'user', 'content' => 'Respondé solo la palabra OK.']],
        );

        return $msg->model ?? (trim((string) $modelo) ?: self::MODELO_DEFAULT);
    }

    // ------------------------------------------------------------ busqueda que entiende

    /**
     * Traduce la busqueda a palabras del catalogo y filtros.
     * @return array{palabras: string[], rubro: ?string, marca: ?string, depto: ?string}|null
     */
    public function interpretarBusqueda(string $texto, array $rubros, array $marcas, array $deptos): ?array
    {
        $texto = mb_substr(trim($texto), 0, 200);
        if ($texto === '') {
            return null;
        }
        $lista = fn (array $items) => implode(' | ', array_map(fn ($i) => $i['cod'] . '=' . $i['nom'], array_slice($items, 0, 150)));
        $system = 'Sos el buscador de una tienda online argentina de un comercio. El comprador escribió algo que no coincidió con ningún nombre de producto. '
            . 'Devolvé palabras clave cortas y concretas (sustantivos en singular, sin artículos, sinónimos y variantes que aparecerían en el nombre de un artículo de gestión, en mayúsculas o minúsculas indistinto) '
            . 'y, solo si es claro, el código de rubro, marca o departamento de las listas. No inventes códigos.';
        $user = "Búsqueda: \"{$texto}\"\nRubros: " . $lista($rubros) . "\nMarcas: " . $lista($marcas) . "\nDepartamentos: " . $lista($deptos);
        $schema = [
            'type' => 'object',
            'properties' => [
                'palabras' => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 8],
                'rubro' => ['type' => ['string', 'null']],
                'marca' => ['type' => ['string', 'null']],
                'depto' => ['type' => ['string', 'null']],
            ],
            'required' => ['palabras', 'rubro', 'marca', 'depto'],
            'additionalProperties' => false,
        ];
        $r = $this->pedirJson($system, $user, $schema, 300);
        if (!$r) {
            return null;
        }
        $valido = fn (array $items, $cod) => $cod !== null && collect($items)->contains(fn ($i) => (string) $i['cod'] === (string) $cod) ? (string) $cod : null;
        $palabras = array_values(array_unique(array_filter(array_map(fn ($p) => mb_substr(trim((string) $p), 0, 40), $r['palabras'] ?? []))));
        (new TiendaMetricaService($this->prefijo))->registrar('ia_busqueda', mb_substr($texto, 0, 120));

        return [
            'palabras' => array_slice($palabras, 0, 8),
            'rubro' => $valido($rubros, $r['rubro'] ?? null),
            'marca' => $valido($marcas, $r['marca'] ?? null),
            'depto' => $valido($deptos, $r['depto'] ?? null),
        ];
    }

    // ------------------------------------------------------------ pedido desde un mensaje

    /**
     * Separa el mensaje en renglones y elige un articulo del catalogo para cada uno.
     * @return array<int, array{texto: string, cantidad: float, codigo: ?string, nombre: ?string, precio: ?float, foto: ?string, alternativas: array}>
     */
    public function armarPedido(string $mensaje, callable $precioDe): array
    {
        $mensaje = mb_substr(trim($mensaje), 0, 1500);
        if ($mensaje === '') {
            return [];
        }

        // 1) renglones con cantidad
        $r = $this->pedirJson(
            'Sos el asistente de pedidos de una tienda online argentina. Separá el mensaje del comprador en renglones: qué pide y cuánto. '
            . 'La cantidad es numérica (una docena = 12, medio kilo = 0.5 si el artículo se vende por kilo, "un par" = 2). Si no dice cantidad, 1. '
            . 'En "texto" poné el producto con sus características (marca, tamaño, sabor) tal como lo diría el comprador, sin la cantidad.',
            "Mensaje: \"{$mensaje}\"",
            ['type' => 'object', 'properties' => ['items' => ['type' => 'array', 'maxItems' => 30, 'items' => ['type' => 'object', 'properties' => ['texto' => ['type' => 'string'], 'cantidad' => ['type' => 'number']], 'required' => ['texto', 'cantidad'], 'additionalProperties' => false]]], 'required' => ['items'], 'additionalProperties' => false],
            1500
        );
        $items = array_values(array_filter($r['items'] ?? [], fn ($i) => trim((string) ($i['texto'] ?? '')) !== ''));
        if (empty($items)) {
            return [];
        }

        // 2) candidatos de la base para cada renglon
        $candidatos = [];
        foreach ($items as $k => $it) {
            $candidatos[$k] = $this->candidatos((string) $it['texto']);
        }

        // 3) la IA elige entre los candidatos (o ninguno)
        $paraElegir = [];
        foreach ($items as $k => $it) {
            $paraElegir[] = ['n' => $k, 'pedido' => $it['texto'], 'candidatos' => array_map(fn ($c) => ['codigo' => (string) $c->CODIGO, 'nombre' => $c->NOMBRE], $candidatos[$k])];
        }
        $e = $this->pedirJson(
            'Para cada renglón pedido, elegí el código del candidato que mejor corresponde. Si ninguno corresponde de verdad (otro producto, otra cosa), devolvé null: es mejor no elegir que elegir mal.',
            json_encode($paraElegir, JSON_UNESCAPED_UNICODE),
            ['type' => 'object', 'properties' => ['elecciones' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['n' => ['type' => 'integer'], 'codigo' => ['type' => ['string', 'null']]], 'required' => ['n', 'codigo'], 'additionalProperties' => false]]], 'required' => ['elecciones'], 'additionalProperties' => false],
            1000
        );
        $elegidos = [];
        foreach ($e['elecciones'] ?? [] as $el) {
            $elegidos[(int) $el['n']] = $el['codigo'] !== null ? (string) $el['codigo'] : null;
        }

        $out = [];
        foreach ($items as $k => $it) {
            $codigo = $elegidos[$k] ?? null;
            $cand = collect($candidatos[$k]);
            $prod = $codigo !== null ? $cand->first(fn ($c) => (string) $c->CODIGO === $codigo) : null;
            $out[] = [
                'texto' => mb_substr(trim($it['texto']), 0, 120),
                'cantidad' => max(0.01, (float) ($it['cantidad'] ?? 1)),
                'codigo' => $prod ? (string) $prod->CODIGO : null,
                'nombre' => $prod ? TiendaEstiloService::nombreBonito($prod->NOMBRE, $prod->CODIGO) : null,
                'precio' => $prod ? (float) $precioDe($prod) : null,
                'foto' => $prod ? ($prod->foto_url ?: null) : null,
                'alternativas' => $cand->filter(fn ($c) => !$prod || (string) $c->CODIGO !== (string) $prod->CODIGO)->take(4)
                    ->map(fn ($c) => ['codigo' => (string) $c->CODIGO, 'nombre' => TiendaEstiloService::nombreBonito($c->NOMBRE, $c->CODIGO), 'precio' => (float) $precioDe($c)])->values()->all(),
            ];
        }
        (new TiendaMetricaService($this->prefijo))->registrar('ia_pedido', (string) count($out));

        return $out;
    }

    /** Articulos vendibles cuyo nombre contiene alguna de las palabras del texto (las mas largas primero). */
    private function candidatos(string $texto)
    {
        $palabras = array_values(array_filter(array_map(fn ($p) => trim($p, " ,.;:()"), preg_split('/\s+/u', mb_strtolower($texto))), fn ($p) => mb_strlen($p) >= 3));
        usort($palabras, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        if (empty($palabras)) {
            return collect();
        }
        $q = $this->tabla('articu as a')
            ->leftJoin($this->prefijo . 'articu_fotos as f', 'a.CODIGO', '=', 'f.codigo')
            ->leftJoin($this->prefijo . 'ivas as i', 'a.IVA', '=', 'i.codigo')
            ->leftJoin($this->prefijo . 'tienda_articu as ta', 'a.CODIGO', '=', 'ta.codigo')
            ->where('a.NOSALE', 0)->whereRaw('COALESCE(ta.visible_web, 1) = 1')
            ->where(function ($w) use ($palabras) {
                foreach (array_slice($palabras, 0, 4) as $p) {
                    $w->orWhere('a.NOMBRE', 'like', '%' . $p . '%');
                }
            });
        // Los que contienen mas palabras, primero
        $orden = implode(' + ', array_map(fn ($p) => "(a.NOMBRE LIKE '%" . str_replace("'", "''", $p) . "%')", array_slice($palabras, 0, 4)));

        return $q->orderByRaw("({$orden}) DESC")->orderBy('a.NOMBRE')->limit(self::MAX_CANDIDATOS)
            ->get(['a.CODIGO', 'a.NOMBRE', 'a.PRECIOVEN', 'a.REVENTA', 'a.STOCKACT', 'f.url as foto_url', 'i.tasa as tasa_iva']);
    }
}
