<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Services\ImagenProductoService;
use App\Services\TiendaService;

class FotosProductos extends Component
{
    use WithPagination, WithFileUploads;

    // Filtros
    public $busqueda = '';
    public $filtroFoto = ''; // '', 'con', 'sin'

    // Upload producto
    public $foto;
    public $productoSeleccionado = null;
    public $subiendo = false;

    // Upload logo y portada
    public $fotoLogo;
    public $fotoPortada;

    // URLs actuales de logo y portada
    public $logoUrl = '';
    public $portadaUrl = '';

    // Datos del comercio (config_clientes_web)
    public $datosComercio = [
        'direccion' => '',
        'localidad' => '',
        'telefono' => '',
        'instagram' => '',
        'facebook' => '',
        'horario' => '',
        'slogan' => '',
        'color_fondo' => '#f9fafb',
        'color_texto' => '#1f2937',
        'color_precio' => '#1E1B35',
        'color_boton' => '#FFAF22',
        'filtro_rubro' => true,
        'filtro_marca' => true,
        'filtro_depto' => true,
        'email_comercio' => '',
        'layout_catalogo' => 'top',
        'estilo_tarjeta' => 'estandar',
        'columnas_desktop' => 4,
        'orden_defecto' => 'nombre_asc',
        'estilo_header' => 'completo',
        'modo_cantidad' => 'rapido',
        'permite_decimal' => false,
        'tienda_vende_sin_stock' => true,   // ver TiendaStockService
    ];

    // Tienda config
    public $tiendaSlug = '';
    public $tiendaModo = TiendaService::MODO_PUBLICA; // ver config/tienda.php

    // Tab activo
    public $tabActivo = 'productos'; // 'productos', 'configuracion', 'envios' (todo lo de la tienda vive aca, no en el menu)

    // Contadores
    public $totalProductos = 0;
    public $totalConFoto = 0;
    public $totalSinFoto = 0;

    // Mensajes
    public $mensaje = '';
    public $tipoMensaje = '';

    protected $tablePrefix;
    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        $this->setupClientDatabase();
        $this->calcularContadores();
        $this->cargarImagenesTienda();
        $this->cargarDatosComercio();
        $this->cargarDatosTienda();
        $this->cargarDestacados();
    }

    private function setupClientDatabase()
    {
        $clientId = session('client_id');
        if ($clientId) {
            $cliente = \App\Models\Cliente::find($clientId);

            if ($cliente) {
                Config::set('database.connections.client_db.database', $cliente->base);
                DB::purge('client_db');
                session(['client_table_prefix' => $cliente->getTablePrefix()]);
                $this->tablePrefix = $cliente->getTablePrefix();
            }
        }
    }

    private function cargarImagenesTienda()
    {
        $clientId = session('client_id');
        $logoPath = "img/logos/{$clientId}/logo.png";
        $portadaPath = "img/logos/{$clientId}/portada.png";

        $this->logoUrl = file_exists(public_path($logoPath)) ? asset($logoPath) . '?v=' . filemtime(public_path($logoPath)) : '';
        $this->portadaUrl = file_exists(public_path($portadaPath)) ? asset($portadaPath) . '?v=' . filemtime(public_path($portadaPath)) : '';
    }

    private function cargarDatosComercio()
    {
        $clientId = session('client_id');

        $registro = DB::connection('config')
            ->table('config_clientes_web')
            ->where('cliente_id', $clientId)
            ->first();

        if ($registro && $registro->configuracion) {
            $datos = json_decode($registro->configuracion, true);
            if (is_array($datos)) {
                $this->datosComercio = array_merge($this->datosComercio, $datos);
            }
        }
    }

    private function cargarDatosTienda()
    {
        $clientId = session('client_id');
        $cliente = DB::connection('config')
            ->table('config_clientes')
            ->where('id', $clientId)
            ->first();

        if ($cliente) {
            $this->tiendaSlug = $cliente->tienda ?? '';
        }

        $this->tiendaModo = TiendaService::modo((int) $clientId);
    }

    public function setConfigCatalogo($key, $value)
    {
        $this->datosComercio[$key] = $value;
    }

    public function guardarTiendaSlug()
    {
        $slug = trim($this->tiendaSlug);

        if (empty($slug)) {
            $this->mensaje = 'El nombre de la tienda no puede estar vacío.';
            $this->tipoMensaje = 'error';
            return;
        }

        // Validar caracteres: solo letras, números, guiones
        if (!preg_match('/^[a-zA-Z0-9\-]+$/', $slug)) {
            $this->mensaje = 'El nombre de la tienda solo puede contener letras, números y guiones.';
            $this->tipoMensaje = 'error';
            return;
        }

        $clientId = session('client_id');

        // Verificar unicidad (case insensitive, excluyendo el cliente actual)
        $existe = DB::connection('config')
            ->table('config_clientes')
            ->whereRaw('LOWER(tienda) = ?', [strtolower($slug)])
            ->where('id', '!=', $clientId)
            ->exists();

        if ($existe) {
            $this->mensaje = 'Ese nombre de tienda ya está en uso. Elegí otro.';
            $this->tipoMensaje = 'error';
            return;
        }

        DB::connection('config')
            ->table('config_clientes')
            ->where('id', $clientId)
            ->update(['tienda' => $slug]);

        $this->tiendaSlug = $slug;
        $this->mensaje = 'Nombre de tienda actualizado correctamente.';
        $this->tipoMensaje = 'success';
    }

    public function guardarTiendaModo()
    {
        $clientId = (int) session('client_id');

        $this->tiendaModo = TiendaService::normalizarModo($this->tiendaModo);
        TiendaService::guardarModo($clientId, $this->tiendaModo);

        // Mantener sincronizada la copia en memoria del JSON, que guardarDatosComercio() reescribe entero
        $this->datosComercio[TiendaService::CLAVE_MODO] = $this->tiendaModo;

        $this->mensaje = 'Modo de acceso actualizado correctamente.';
        $this->tipoMensaje = 'success';
    }

    public function guardarDatosComercio()
    {
        $clientId = session('client_id');

        $json = json_encode($this->datosComercio, JSON_UNESCAPED_UNICODE);

        $existe = DB::connection('config')
            ->table('config_clientes_web')
            ->where('cliente_id', $clientId)
            ->exists();

        if ($existe) {
            DB::connection('config')
                ->table('config_clientes_web')
                ->where('cliente_id', $clientId)
                ->update(['configuracion' => $json]);
        } else {
            DB::connection('config')
                ->table('config_clientes_web')
                ->insert([
                    'cliente_id' => $clientId,
                    'configuracion' => $json,
                ]);
        }

        $this->mensaje = 'Datos del comercio guardados correctamente.';
        $this->tipoMensaje = 'success';
    }

    public function subirLogo()
    {
        $this->validate([
            'fotoLogo' => 'required|image|max:2048',
        ], [
            'fotoLogo.required' => 'Selecciona una imagen para el logo.',
            'fotoLogo.image' => 'El archivo debe ser una imagen.',
            'fotoLogo.max' => 'La imagen no puede superar los 2MB.',
        ]);

        try {
            $clientId = session('client_id');
            $directory = public_path("img/logos/{$clientId}");

            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            copy($this->fotoLogo->getRealPath(), "{$directory}/logo.png");

            $this->fotoLogo = null;
            $this->cargarImagenesTienda();
            $this->mensaje = 'Logo subido correctamente.';
            $this->tipoMensaje = 'success';
        } catch (\Exception $e) {
            Log::error('Error subiendo logo: ' . $e->getMessage());
            $this->mensaje = 'Error al subir el logo.';
            $this->tipoMensaje = 'error';
        }
    }

    public function subirPortada()
    {
        $this->validate([
            'fotoPortada' => 'required|image|max:5120',
        ], [
            'fotoPortada.required' => 'Selecciona una imagen de portada.',
            'fotoPortada.image' => 'El archivo debe ser una imagen.',
            'fotoPortada.max' => 'La imagen no puede superar los 5MB.',
        ]);

        try {
            $clientId = session('client_id');
            $directory = public_path("img/logos/{$clientId}");

            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            copy($this->fotoPortada->getRealPath(), "{$directory}/portada.png");

            $this->fotoPortada = null;
            $this->cargarImagenesTienda();
            $this->mensaje = 'Imagen de portada subida correctamente.';
            $this->tipoMensaje = 'success';
        } catch (\Exception $e) {
            Log::error('Error subiendo portada: ' . $e->getMessage());
            $this->mensaje = 'Error al subir la portada.';
            $this->tipoMensaje = 'error';
        }
    }

    // ========== PRODUCTOS (funcionalidad existente) ==========

    public function getProductosProperty()
    {
        $this->setupClientDatabase();

        $query = DB::connection('client_db')
            ->table($this->tablePrefix . 'articu as a')
            ->leftJoin($this->tablePrefix . 'articu_fotos as f', 'a.codigo', '=', 'f.codigo')
            ->leftJoin($this->tablePrefix . 'tienda_articu as ta', 'a.codigo', '=', 'ta.codigo')
            ->select(
                'a.codigo',
                'a.nombre',
                'a.precioven',
                'f.url as foto_url',
                'ta.peso as peso',
                'ta.destacado as destacado'
            )
            ->where('a.NOSALE', 0);

        if ($this->busqueda) {
            $query->where(function ($q) {
                $q->where('a.codigo', 'like', '%' . $this->busqueda . '%')
                  ->orWhere('a.nombre', 'like', '%' . $this->busqueda . '%');
            });
        }

        if ($this->filtroFoto === 'con') {
            $query->whereNotNull('f.url');
        } elseif ($this->filtroFoto === 'sin') {
            $query->whereNull('f.url');
        }

        $query->orderBy('a.nombre', 'asc');

        return $query->paginate(20);
    }

    public function calcularContadores()
    {
        $this->setupClientDatabase();

        $this->totalProductos = DB::connection('client_db')
            ->table($this->tablePrefix . 'articu')
            ->where('NOSALE', 0)
            ->count();

        $this->totalConFoto = DB::connection('client_db')
            ->table($this->tablePrefix . 'articu as a')
            ->join($this->tablePrefix . 'articu_fotos as f', 'a.codigo', '=', 'f.codigo')
            ->where('a.NOSALE', 0)
            ->whereNotNull('f.url')
            ->count();

        $this->totalSinFoto = $this->totalProductos - $this->totalConFoto;
    }

    // ------------------------------------------------ destacados (tienda_articu)

    public $destacados = [];   // [codigo, nombre, foto_url, orden], en orden

    private function cargarDestacados()
    {
        $this->setupClientDatabase();
        $this->destacados = DB::connection('client_db')
            ->table($this->tablePrefix . 'tienda_articu as ta')
            ->join($this->tablePrefix . 'articu as a', 'a.codigo', '=', 'ta.codigo')
            ->leftJoin($this->tablePrefix . 'articu_fotos as f', 'a.codigo', '=', 'f.codigo')
            ->where('ta.destacado', 1)
            ->orderBy('ta.orden')->orderBy('a.nombre')
            ->get(['a.codigo', 'a.nombre', 'f.url as foto_url', 'ta.orden'])
            ->map(fn ($d) => (array) $d)->all();
    }

    /** Marca o desmarca un articulo como destacado (los nuevos van al final). */
    public function toggleDestacado($codigo)
    {
        $this->setupClientDatabase();
        $actual = DB::connection('client_db')->table($this->tablePrefix . 'tienda_articu')->where('codigo', $codigo)->value('destacado');
        if ($actual) {
            $this->upsertTiendaArticu((string) $codigo, ['destacado' => 0]);
        } else {
            $max = (int) DB::connection('client_db')->table($this->tablePrefix . 'tienda_articu')->where('destacado', 1)->max('orden');
            $this->upsertTiendaArticu((string) $codigo, ['destacado' => 1, 'orden' => $max + 1]);
        }
        $this->cargarDestacados();
    }

    /** Sube o baja un destacado en el orden de la portada. */
    public function moverDestacado($codigo, $direccion)
    {
        $this->cargarDestacados();
        $codigos = array_map(fn ($d) => (string) $d['codigo'], $this->destacados);
        $i = array_search((string) $codigo, $codigos, true);
        $j = $direccion === 'arriba' ? $i - 1 : $i + 1;
        if ($i === false || $j < 0 || $j >= count($codigos)) {
            return;
        }
        [$codigos[$i], $codigos[$j]] = [$codigos[$j], $codigos[$i]];
        foreach ($codigos as $orden => $c) {
            DB::connection('client_db')->table($this->tablePrefix . 'tienda_articu')->where('codigo', $c)->update(['orden' => $orden + 1]);
        }
        $this->cargarDestacados();
    }

    // ------------------------------------------------ peso y medidas (tienda_articu)

    public $planillaPesos = null;    // CSV subido
    public $mensajePesos = '';

    /** Guarda el peso (kg) de un articulo desde la tabla. Vacio = sin dato. */
    public function guardarPeso($codigo, $valor)
    {
        $this->setupClientDatabase();
        $peso = is_numeric($valor) && (float) $valor >= 0 ? round((float) $valor, 3) : null;
        $this->upsertTiendaArticu((string) $codigo, ['peso' => $peso]);
    }

    private function upsertTiendaArticu(string $codigo, array $datos): void
    {
        $tabla = DB::connection('client_db')->table($this->tablePrefix . 'tienda_articu');
        if ($tabla->where('codigo', $codigo)->exists()) {
            DB::connection('client_db')->table($this->tablePrefix . 'tienda_articu')->where('codigo', $codigo)->update($datos);
        } else {
            DB::connection('client_db')->table($this->tablePrefix . 'tienda_articu')->insert(array_merge(['codigo' => $codigo], $datos));
        }
    }

    /** Planilla CSV con todos los articulos y sus pesos/medidas actuales, para completar en Excel. */
    public function descargarPlanillaPesos()
    {
        $this->setupClientDatabase();
        $filas = DB::connection('client_db')
            ->table($this->tablePrefix . 'articu as a')
            ->leftJoin($this->tablePrefix . 'tienda_articu as ta', 'a.codigo', '=', 'ta.codigo')
            ->where('a.NOSALE', 0)
            ->orderBy('a.nombre')
            ->get(['a.codigo', 'a.nombre', 'ta.peso', 'ta.alto', 'ta.ancho', 'ta.largo', 'ta.bultos', 'ta.anticipacion_horas']);

        return response()->streamDownload(function () use ($filas) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // BOM: Excel abre bien los acentos
            fputcsv($out, ['codigo', 'nombre', 'peso_kg', 'alto_cm', 'ancho_cm', 'largo_cm', 'bultos', 'anticipacion_horas'], ';');
            foreach ($filas as $f) {
                fputcsv($out, [$f->codigo, $f->nombre, $f->peso, $f->alto, $f->ancho, $f->largo, $f->bultos, $f->anticipacion_horas], ';');
            }
            fclose($out);
        }, 'pesos-articulos.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Importa la planilla: columnas codigo, peso_kg (y opcionales alto_cm, ancho_cm, largo_cm, bultos).
     * Acepta ; o , como separador y coma o punto decimal. Solo toca las filas con codigo valido.
     */
    public function importarPlanillaPesos()
    {
        $this->validate(['planillaPesos' => 'required|file|max:4096'], ['planillaPesos.required' => 'Elegí el archivo CSV.']);
        $this->setupClientDatabase();

        $contenido = file_get_contents($this->planillaPesos->getRealPath());
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
        $lineas = preg_split('/\r\n|\r|\n/', trim($contenido));
        if (count($lineas) < 2) {
            $this->mensajePesos = 'La planilla está vacía.';
            return;
        }

        $sep = substr_count($lineas[0], ';') >= substr_count($lineas[0], ',') ? ';' : ',';
        $cab = array_map(fn ($c) => strtolower(trim($c)), str_getcsv($lineas[0], $sep));
        $col = fn ($nombre) => array_search($nombre, $cab, true);
        $iCod = $col('codigo');
        if ($iCod === false) {
            $this->mensajePesos = 'La planilla tiene que tener una columna "codigo".';
            return;
        }
        $iPeso = $col('peso_kg'); $iAlto = $col('alto_cm'); $iAncho = $col('ancho_cm'); $iLargo = $col('largo_cm'); $iBultos = $col('bultos'); $iAntic = $col('anticipacion_horas');
        $num = fn ($v) => ($v === null || trim((string) $v) === '') ? null : (is_numeric(str_replace(',', '.', trim($v))) ? (float) str_replace(',', '.', trim($v)) : null);

        $codigosValidos = DB::connection('client_db')->table($this->tablePrefix . 'articu')->pluck('codigo')->map(fn ($c) => (string) $c)->flip();
        $ok = 0; $saltadas = 0;

        foreach (array_slice($lineas, 1) as $linea) {
            if (trim($linea) === '') continue;
            $c = str_getcsv($linea, $sep);
            $codigo = trim((string) ($c[$iCod] ?? ''));
            if ($codigo === '' || !isset($codigosValidos[$codigo])) { $saltadas++; continue; }

            $datos = [];
            if ($iPeso !== false) $datos['peso'] = $num($c[$iPeso] ?? null);
            if ($iAlto !== false) $datos['alto'] = $num($c[$iAlto] ?? null);
            if ($iAncho !== false) $datos['ancho'] = $num($c[$iAncho] ?? null);
            if ($iLargo !== false) $datos['largo'] = $num($c[$iLargo] ?? null);
            if ($iBultos !== false) { $b = $num($c[$iBultos] ?? null); $datos['bultos'] = $b !== null ? (int) $b : null; }
            if ($iAntic !== false) { $h = $num($c[$iAntic] ?? null); $datos['anticipacion_horas'] = $h !== null ? (int) $h : null; }
            if (empty($datos)) { $saltadas++; continue; }

            $this->upsertTiendaArticu($codigo, $datos);
            $ok++;
        }

        $this->planillaPesos = null;
        $this->mensajePesos = "Planilla importada: {$ok} artículos actualizados" . ($saltadas ? ", {$saltadas} filas salteadas (código inexistente o sin datos)" : '') . '.';
    }

    public function seleccionarProducto($codigo)
    {
        $this->productoSeleccionado = $codigo;
        $this->foto = null;
        $this->resetErrorBag();
    }

    public function cerrarModal()
    {
        $this->productoSeleccionado = null;
        $this->foto = null;
        $this->resetErrorBag();
    }


    public function subirFoto()
    {
        $this->validate([
            'foto' => 'required|image|max:5120',
        ], [
            'foto.required' => 'Seleccioná una imagen.',
            'foto.image' => 'El archivo debe ser una imagen (jpg, png, gif, webp).',
            'foto.max' => 'La imagen no puede superar los 5MB.',
        ]);

        $this->setupClientDatabase();

        try {
            $this->subiendo = true;

            // El servicio redimensiona, genera las variantes y sube a R2
            $url = app(ImagenProductoService::class)->subir(
                $this->tablePrefix,
                (string) $this->productoSeleccionado,
                $this->foto->getRealPath()
            );

            $existe = DB::connection('client_db')
                ->table($this->tablePrefix . 'articu_fotos')
                ->where('codigo', $this->productoSeleccionado)
                ->exists();

            if ($existe) {
                DB::connection('client_db')
                    ->table($this->tablePrefix . 'articu_fotos')
                    ->where('codigo', $this->productoSeleccionado)
                    ->update(['url' => $url]);
            } else {
                DB::connection('client_db')
                    ->table($this->tablePrefix . 'articu_fotos')
                    ->insert([
                        'codigo' => $this->productoSeleccionado,
                        'url' => $url,
                    ]);
            }

            $this->mensaje = 'Foto subida correctamente.';
            $this->tipoMensaje = 'success';
            $this->cerrarModal();
            $this->calcularContadores();

        } catch (\Exception $e) {
            Log::error('Error subiendo foto de producto: ' . $e->getMessage());
            $this->mensaje = 'Error al subir la foto. Intentá de nuevo.';
            $this->tipoMensaje = 'error';
        } finally {
            $this->subiendo = false;
        }
    }

    public function eliminarFoto($codigo)
    {
        $this->setupClientDatabase();

        try {
            app(ImagenProductoService::class)->eliminar($this->tablePrefix, (string) $codigo);

            DB::connection('client_db')
                ->table($this->tablePrefix . 'articu_fotos')
                ->where('codigo', $codigo)
                ->delete();

            $this->mensaje = 'Foto eliminada correctamente.';
            $this->tipoMensaje = 'success';
            $this->calcularContadores();

        } catch (\Exception $e) {
            Log::error('Error eliminando foto de producto: ' . $e->getMessage());
            $this->mensaje = 'Error al eliminar la foto.';
            $this->tipoMensaje = 'error';
        }
    }

    public function limpiarFiltros()
    {
        $this->busqueda = '';
        $this->filtroFoto = '';
        $this->resetPage();
        $this->calcularContadores();
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['busqueda', 'filtroFoto'])) {
            $this->resetPage();
        }
    }

    public function updatedFoto()
    {
        $this->validateOnly('foto', [
            'foto' => 'image|max:5120',
        ], [
            'foto.image' => 'El archivo debe ser una imagen.',
            'foto.max' => 'La imagen no puede superar los 5MB.',
        ]);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('panel.fotos-productos', [
            'productos' => $this->productos,
        ]);
    }
}
