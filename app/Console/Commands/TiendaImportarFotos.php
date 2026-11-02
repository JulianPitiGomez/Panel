<?php

namespace App\Console\Commands;

use App\Services\TiendaFotoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * Carga masiva de fotos de productos desde un Excel (.xlsx) que trae, por fila,
 * el codigo del articulo y un link a la foto (Google Drive o URL directa).
 *
 *   php artisan tienda:importar-fotos 2409 "General productos.xlsx" --dry-run
 *   php artisan tienda:importar-fotos 2409 "General productos.xlsx" --dry-run --verificar-links
 *   php artisan tienda:importar-fotos 2409 "General productos.xlsx" --base=test   (pruebas locales)
 *   php artisan tienda:importar-fotos 2409 "General productos.xlsx"
 *
 * Cada foto entra por TiendaFotoService::agregar, igual que el boton del panel:
 * tres variantes en R2, fila en tienda_fotos y articu_fotos sincronizado.
 * Los articulos que ya tienen foto se saltean salvo --pisar.
 */
class TiendaImportarFotos extends Command
{
    protected $signature = 'tienda:importar-fotos
                            {cliente_id : id en config_clientes}
                            {archivo : ruta al .xlsx}
                            {--col-codigo=A : columna con el codigo del articulo}
                            {--col-foto=J : columna con el link de la foto}
                            {--base= : base de datos a usar en lugar de la del cliente (pruebas)}
                            {--limite= : procesar solo las primeras N fotos (para probar)}
                            {--pisar : reemplazar las fotos de los articulos que ya tienen}
                            {--verificar-links : en --dry-run, bajar cada foto para comprobar que es una imagen}
                            {--dry-run : mostrar que haria sin subir ni escribir nada}';

    protected $description = 'Carga fotos de productos a la tienda a partir de un Excel con codigo y link';

    public function handle(): int
    {
        $clienteId = (int) $this->argument('cliente_id');
        $dryRun = (bool) $this->option('dry-run');
        $verificar = !$dryRun || (bool) $this->option('verificar-links');

        $cliente = \App\Models\Cliente::find($clienteId);
        $prefijo = $cliente ? $cliente->getTablePrefix() : 'ge_' . str_pad((string) $clienteId, 6, '0', STR_PAD_LEFT);
        $base = $this->option('base') ?: $cliente?->base;
        if (!$base) {
            $this->error("No encuentro la base del cliente {$clienteId}; indicala con --base.");
            return self::FAILURE;
        }
        Config::set('database.connections.client_db.database', $base);
        DB::purge('client_db');

        $this->info(($dryRun ? '[SIMULACION] ' : '') . "Cliente {$clienteId} (base {$base}, prefijo {$prefijo})");

        try {
            $filas = $this->leerXlsx($this->argument('archivo'), strtoupper($this->option('col-codigo')), strtoupper($this->option('col-foto')));
        } catch (\Throwable $e) {
            $this->error('No pude leer el Excel: ' . $e->getMessage());
            return self::FAILURE;
        }

        $schema = Schema::connection('client_db');
        if (!$schema->hasTable("{$prefijo}articu")) {
            $this->error("No existe la tabla {$prefijo}articu en la base {$base}.");
            return self::FAILURE;
        }
        $hayTablasFotos = $schema->hasTable("{$prefijo}articu_fotos") && $schema->hasTable("{$prefijo}tienda_fotos");
        if (!$hayTablasFotos && !$dryRun) {
            $this->error("Faltan las tablas {$prefijo}articu_fotos / {$prefijo}tienda_fotos (esquema de tienda sin aplicar).");
            return self::FAILURE;
        }
        if (!$hayTablasFotos) {
            $this->warn("Faltan las tablas de fotos: no puedo saber que articulos ya tienen foto, los cuento como sin foto.");
        }

        $articulos = DB::connection('client_db')->table("{$prefijo}articu")
            ->pluck('NOSALE', 'CODIGO')->mapWithKeys(fn ($v, $k) => [(string) $k => (int) $v])->all();
        $conFoto = $hayTablasFotos
            ? DB::connection('client_db')->table("{$prefijo}articu_fotos")->where('url', '<>', '')->pluck('codigo')->map(fn ($c) => (string) $c)->flip()->all()
            : [];

        // Clasificar filas
        $resultado = [];
        $aProcesar = [];
        foreach ($filas as $f) {
            $codigo = $f['codigo'];
            $url = $this->urlDescarga($f['link']);

            $estado = match (true) {
                $codigo === '' => null,
                $url === null => 'sin link',
                !isset($articulos[$codigo]) => 'codigo inexistente',
                isset($conFoto[$codigo]) && !$this->option('pisar') => 'ya tiene foto',
                default => 'a cargar',
            };
            if ($estado === null) {
                continue;
            }
            $f['url'] = $url;
            $f['estado'] = $estado;
            $f['nosale'] = $articulos[$codigo] ?? 0;
            $resultado[] = $f;
            if ($estado === 'a cargar') {
                $aProcesar[] = array_key_last($resultado);
            }
        }

        if ($limite = (int) $this->option('limite')) {
            foreach (array_slice($aProcesar, $limite) as $i) {
                $resultado[$i]['estado'] = 'fuera del limite';
            }
            $aProcesar = array_slice($aProcesar, 0, $limite);
        }

        // Descargar (y en modo real, subir)
        $srv = $dryRun ? null : new TiendaFotoService($prefijo);
        $tmpDir = storage_path('app/tmp-importar-fotos');
        if ($verificar && !is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        if ($verificar && $aProcesar) {
            $bar = $this->output->createProgressBar(count($aProcesar));
            foreach (array_chunk($aProcesar, 8) as $lote) {
                $respuestas = Http::pool(fn ($pool) => array_map(
                    fn ($i) => $pool->as((string) $i)->timeout(60)->retry(2, 2000, throw: false)->withOptions(['allow_redirects' => true])->get($resultado[$i]['url']),
                    $lote
                ));
                foreach ($lote as $i) {
                    $resultado[$i]['estado'] = $this->procesar($resultado[$i], $respuestas[(string) $i] ?? null, $srv, $tmpDir, $dryRun);
                    $bar->advance();
                }
            }
            $bar->finish();
            $this->newLine(2);
        }

        if ($srv) {
            \App\Services\TiendaMetricaService::invalidarCatalogo($prefijo);
        }

        // Resumen
        $cuenta = array_count_values(array_column($resultado, 'estado'));
        arsort($cuenta);
        $this->table(['resultado', 'articulos'], array_map(fn ($k, $v) => [$k, $v], array_keys($cuenta), $cuenta));

        $ocultos = count(array_filter($resultado, fn ($r) => $r['nosale'] && in_array($r['estado'], ['a cargar', 'cargada', 'link ok'])));
        if ($ocultos) {
            $this->line("De esos, {$ocultos} articulos estan marcados NOSALE (no aparecen en el catalogo).");
        }

        $problemas = array_values(array_filter($resultado, fn ($r) => !in_array($r['estado'], ['a cargar', 'cargada', 'link ok', 'ya tiene foto', 'fuera del limite'])));
        if ($problemas) {
            $this->newLine();
            $this->table(['fila', 'codigo', 'nombre (excel)', 'resultado', 'link'], array_map(fn ($r) => [
                $r['fila'], $r['codigo'], mb_substr($r['nombre'], 0, 35), $r['estado'], $r['link'],
            ], $problemas));
        }

        return self::SUCCESS;
    }

    /** Valida la respuesta, y si no es simulacion sube la foto. Devuelve el estado final. */
    private function procesar(array $fila, $respuesta, ?TiendaFotoService $srv, string $tmpDir, bool $dryRun): string
    {
        if (!$respuesta instanceof \Illuminate\Http\Client\Response) {
            return 'error de descarga';
        }
        if (!$respuesta->successful()) {
            return 'link falla (HTTP ' . $respuesta->status() . ')';
        }

        $tmp = $tmpDir . '/' . preg_replace('/\W/', '_', $fila['codigo']) . '_' . uniqid();
        file_put_contents($tmp, $respuesta->body());

        try {
            if (!@getimagesize($tmp)) {
                // Drive devuelve una pagina HTML cuando el archivo no esta compartido publicamente
                return str_contains((string) $respuesta->header('Content-Type'), 'html') ? 'link sin acceso (no publico)' : 'no es imagen';
            }
            if ($dryRun) {
                return 'link ok';
            }
            $srv->agregar($fila['codigo'], $tmp);
            return 'cargada';
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("importar-fotos codigo {$fila['codigo']}: " . $e->getMessage());
            return 'error al subir';
        } finally {
            @unlink($tmp);
        }
    }

    /** Link de Drive (file/d/ID, open?id=ID, uc?id=ID) -> descarga directa. Otra URL http se usa tal cual. */
    private function urlDescarga(?string $link): ?string
    {
        $link = trim((string) $link);
        if (preg_match('~drive\.google\.com/.*(?:/d/|[?&]id=)([\w-]{20,})~', $link, $m)) {
            return 'https://drive.google.com/uc?export=download&id=' . $m[1];
        }
        return preg_match('~^https?://~i', $link) ? $link : null;
    }

    /**
     * Lee la primera hoja de un .xlsx sin dependencias (zip + xml). Toma el link
     * del hipervinculo de la celda si lo tiene, si no el texto.
     * @return array<int, array{fila:int, codigo:string, nombre:string, link:?string}>
     */
    private function leerXlsx(string $archivo, string $colCodigo, string $colFoto): array
    {
        if (!is_file($archivo)) {
            throw new \RuntimeException("no existe {$archivo}");
        }
        $zip = new \ZipArchive();
        if ($zip->open($archivo) !== true) {
            throw new \RuntimeException('no es un .xlsx valido (si es .xls viejo, guardalo como .xlsx)');
        }

        $compartidos = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            foreach (simplexml_load_string($xml)->si as $si) {
                $t = isset($si->t) ? (string) $si->t : '';
                foreach ($si->r as $r) {
                    $t .= (string) $r->t;
                }
                $compartidos[] = $t;
            }
        }

        $hoja = simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml'));
        $rels = [];
        if (($xml = $zip->getFromName('xl/worksheets/_rels/sheet1.xml.rels')) !== false) {
            foreach (simplexml_load_string($xml)->Relationship as $r) {
                $rels[(string) $r['Id']] = (string) $r['Target'];
            }
        }
        $zip->close();

        $links = [];
        foreach ($hoja->hyperlinks->hyperlink ?? [] as $h) {
            $id = (string) $h->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            if (isset($rels[$id])) {
                $links[(string) $h['ref']] = $rels[$id];
            }
        }

        $filas = [];
        foreach ($hoja->sheetData->row as $row) {
            $n = (int) $row['r'];
            if ($n === 1) {
                continue; // encabezado
            }
            $celdas = [];
            foreach ($row->c as $c) {
                preg_match('/^([A-Z]+)/', (string) $c['r'], $m);
                $v = isset($c->v) ? (string) $c->v : (isset($c->is->t) ? (string) $c->is->t : '');
                $celdas[$m[1]] = (string) $c['t'] === 's' ? ($compartidos[(int) $v] ?? '') : $v;
            }
            $codigo = trim($celdas[$colCodigo] ?? '');
            if (preg_match('/^\d+\.0+$/', $codigo)) {
                $codigo = (string) (int) $codigo;
            }
            $filas[] = [
                'fila' => $n,
                'codigo' => $codigo,
                'nombre' => (string) ($celdas['B'] ?? ''),
                'link' => $links["{$colFoto}{$n}"] ?? ($celdas[$colFoto] ?? null),
            ];
        }

        return $filas;
    }
}
