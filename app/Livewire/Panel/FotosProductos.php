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
    public $filtroVisible = ''; // '', 'si', 'no' (visible en la tienda)

    // Upload producto
    public $foto;
    public $productoSeleccionado = null;
    public $subiendo = false;

    // Upload logo y portada
    public $fotoLogo;
    public $fotoPortada;
    public $fotoDefault;       // foto que se muestra cuando el producto no tiene

    // URLs actuales de logo y portada
    public $logoUrl = '';
    public $portadaUrl = '';
    public $fotoDefaultUrl = '';

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
        'tienda_meta_titulo' => '',
        'tienda_meta_descripcion' => '',
        'tienda_registro_aprobacion' => false,   // F5: los registros web esperan aprobacion del comercio
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
    public $totalOcultos = 0;   // no se muestran en la tienda

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
        $this->calcularChequeo();
        $this->calcularAsistente();
        $this->cargarIa();
        $this->cargarCompradoresPendientes();
        $this->cargarMetricas();
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
        $this->fotoDefaultUrl = \App\Services\TiendaService::fotoDefaultUrl($clientId);
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
        $this->invalidarCatalogo();

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

    /** Foto por defecto: la ve el comprador en los productos que no tienen foto propia. */
    public function subirFotoDefault()
    {
        $this->validate([
            'fotoDefault' => 'required|image|max:2048',
        ], [
            'fotoDefault.required' => 'Selecciona una imagen.',
            'fotoDefault.image' => 'El archivo debe ser una imagen.',
            'fotoDefault.max' => 'La imagen no puede superar los 2MB.',
        ]);

        try {
            $directory = public_path('img/logos/' . session('client_id'));
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            copy($this->fotoDefault->getRealPath(), $directory . '/' . \App\Services\TiendaService::FOTO_DEFAULT_ARCHIVO);

            $this->fotoDefault = null;
            $this->cargarImagenesTienda();
            $this->mensaje = 'Foto por defecto guardada. Se muestra en los productos que no tienen foto.';
            $this->tipoMensaje = 'success';
        } catch (\Exception $e) {
            Log::error('Error subiendo foto por defecto: ' . $e->getMessage());
            $this->mensaje = 'Error al subir la foto por defecto.';
            $this->tipoMensaje = 'error';
        }
    }

    public function quitarFotoDefault()
    {
        $ruta = public_path('img/logos/' . session('client_id') . '/' . \App\Services\TiendaService::FOTO_DEFAULT_ARCHIVO);
        if (file_exists($ruta)) {
            @unlink($ruta);
        }
        $this->cargarImagenesTienda();
        $this->mensaje = 'Se quitó la foto por defecto. Los productos sin foto vuelven a mostrar el ícono gris.';
        $this->tipoMensaje = 'success';
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
                'ta.destacado as destacado',
                DB::raw('COALESCE(ta.visible_web, 1) as visible_web')
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

        if ($this->filtroVisible === 'si') {
            $query->whereRaw('COALESCE(ta.visible_web, 1) = 1');
        } elseif ($this->filtroVisible === 'no') {
            $query->where('ta.visible_web', 0);
        }

        // Filtros del chequeo del catalogo
        if ($this->filtroChequeo === 'sin_precio') {
            $query->where('a.PRECIOVEN', '<=', 0);
        } elseif ($this->filtroChequeo === 'sin_descripcion') {
            $query->where(fn ($q) => $q->whereNull('ta.descripcion_larga')->orWhere('ta.descripcion_larga', ''));
        } elseif ($this->filtroChequeo === 'sin_peso') {
            $query->whereNull('ta.peso');
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

        $this->totalOcultos = DB::connection('client_db')
            ->table($this->tablePrefix . 'articu as a')
            ->join($this->tablePrefix . 'tienda_articu as ta', 'a.codigo', '=', 'ta.codigo')
            ->where('a.NOSALE', 0)->where('ta.visible_web', 0)
            ->count();
    }

    // ------------------------------------------------ metricas y cache (F5)

    public $metricas = [];

    private function cargarMetricas()
    {
        $this->setupClientDatabase();
        try {
            $this->metricas = (new \App\Services\TiendaMetricaService($this->tablePrefix))->resumen(30);
        } catch (\Throwable $e) {
            $this->metricas = [];
        }
    }

    /** Algo del catalogo cambio desde el panel: las paginas cacheadas para visitantes dejan de valer. */
    private function invalidarCatalogo(): void
    {
        if ($this->tablePrefix) {
            \App\Services\TiendaMetricaService::invalidarCatalogo($this->tablePrefix);
        }
    }

    // ------------------------------------------------ compradores pendientes (F5)

    public $compradoresPendientes = [];

    private function cargarCompradoresPendientes()
    {
        $this->setupClientDatabase();
        try {
            $this->compradoresPendientes = array_map(fn ($c) => (array) $c, (new \App\Services\TiendaCompradorService($this->tablePrefix))->pendientes());
        } catch (\Throwable $e) {
            $this->compradoresPendientes = [];
        }
    }

    public function aprobarComprador($id)
    {
        $this->setupClientDatabase();
        $srv = new \App\Services\TiendaCompradorService($this->tablePrefix);
        $srv->cambiarEstado((int) $id, \App\Services\TiendaCompradorService::ESTADO_APROBADO);
        $c = DB::connection('client_db')->table($this->tablePrefix . 'tienda_compradores')->where('id', (int) $id)->first();
        if ($c) {
            $this->avisarComprador($c, 'Tu cuenta fue aprobada', 'Ya podés ingresar a la tienda de ' . ($this->datosComercio['nombre_fantasia'] ?? session('cliente_nombre')) . ' y hacer tu pedido.');
        }
        $this->cargarCompradoresPendientes();
    }

    public function rechazarComprador($id)
    {
        $this->setupClientDatabase();
        (new \App\Services\TiendaCompradorService($this->tablePrefix))->cambiarEstado((int) $id, \App\Services\TiendaCompradorService::ESTADO_RECHAZADO, 'Rechazado desde el panel');
        $this->cargarCompradoresPendientes();
    }

    private function avisarComprador(object $c, string $titulo, string $texto): void
    {
        try {
            \Illuminate\Support\Facades\Mail::send('emails.tienda-cuenta', [
                'titulo' => $titulo, 'texto' => $texto, 'boton' => 'Ir a la tienda',
                'url' => url('/web/' . $this->tiendaSlug),
                'nombreTienda' => $this->datosComercio['nombre_fantasia'] ?? session('cliente_nombre') ?? 'Tienda',
                'logoUrl' => $this->logoUrl, 'colorBoton' => $this->datosComercio['color_boton'] ?? '#FFAF22',
            ], fn ($m) => $m->to($c->email)->subject($titulo));
        } catch (\Throwable $e) {
            Log::warning('Tienda: no se pudo avisar al comprador: ' . $e->getMessage());
        }
    }

    // ------------------------------------------------ chequeo del catalogo y asistente (F4)

    public $chequeo = [];       // sin_foto, sin_precio, sin_descripcion, sin_peso, total
    public $asistente = [];     // pasos del alta de la tienda con su estado
    public $filtroChequeo = ''; // '' | sin_precio | sin_descripcion | sin_peso (filtro extra de la tabla)

    public function calcularChequeo()
    {
        $this->setupClientDatabase();
        $p = $this->tablePrefix;
        $base = fn () => DB::connection('client_db')->table("{$p}articu as a")
            ->leftJoin("{$p}articu_fotos as f", 'a.codigo', '=', 'f.codigo')
            ->leftJoin("{$p}tienda_articu as ta", 'a.codigo', '=', 'ta.codigo')
            ->where('a.NOSALE', 0);

        $correoActivo = false;
        try {
            $correoActivo = (bool) ((new \App\Services\TiendaEnvioService($p))->config()['correo_activo'] ?? false);
        } catch (\Throwable $e) {
        }

        $this->chequeo = [
            'total' => $base()->count(),
            'sin_foto' => $base()->whereNull('f.url')->count(),
            'sin_precio' => $base()->where('a.PRECIOVEN', '<=', 0)->count(),
            'sin_descripcion' => $base()->where(fn ($q) => $q->whereNull('ta.descripcion_larga')->orWhere('ta.descripcion_larga', ''))->count(),
            'sin_peso' => $correoActivo ? $base()->whereNull('ta.peso')->count() : null,
        ];
    }

    /** Estado de cada paso del alta de la tienda: lo que falta para salir a vender. */
    public function calcularAsistente()
    {
        $this->setupClientDatabase();
        $p = $this->tablePrefix;
        $clientId = (int) session('client_id');
        $d = $this->datosComercio;

        $pasos = [];
        $pasos[] = ['clave' => 'slug', 'titulo' => 'Nombre de la tienda', 'ok' => trim((string) $this->tiendaSlug) !== '', 'detalle' => trim((string) $this->tiendaSlug) !== '' ? url('/web/' . $this->tiendaSlug) : 'Elegí el nombre que va en la dirección web', 'tab' => 'configuracion'];
        $datosOk = !empty($d['direccion']) && !empty($d['telefono']) && !empty($d['email_comercio']);
        $pasos[] = ['clave' => 'datos', 'titulo' => 'Datos del comercio', 'ok' => $datosOk, 'detalle' => $datosOk ? 'Dirección, teléfono y mail cargados' : 'Faltan dirección, teléfono o mail (a ese mail llegan los pedidos)', 'tab' => 'configuracion'];
        $pasos[] = ['clave' => 'logo', 'titulo' => 'Logo', 'ok' => !empty($this->logoUrl), 'detalle' => !empty($this->logoUrl) ? 'Cargado' : 'Subí el logo para que la tienda se vea tuya', 'tab' => 'configuracion'];

        $total = (int) ($this->chequeo['total'] ?? 0);
        $conFoto = $total - (int) ($this->chequeo['sin_foto'] ?? 0);
        $pct = $total ? (int) round($conFoto * 100 / $total) : 0;
        $pasos[] = ['clave' => 'fotos', 'titulo' => 'Fotos de los artículos', 'ok' => $pct >= 80, 'detalle' => "{$conFoto} de {$total} con foto ({$pct}%)" . ($pct < 80 ? '. Apuntá a más del 80%' : ''), 'tab' => 'productos'];

        $envio = null;
        try { $envio = (new \App\Services\TiendaEnvioService($p))->config(); } catch (\Throwable $e) {}
        $envioOk = $envio && ($envio['retiro_local'] || $envio['flota_propia'] || $envio['correo_activo'] || $envio['a_convenir']);
        $pasos[] = ['clave' => 'envios', 'titulo' => 'Envíos', 'ok' => (bool) $envioOk, 'detalle' => $envioOk ? 'Al menos una forma de entrega activa' : 'Definí cómo entregás', 'tab' => 'envios'];

        $pagos = null;
        try { $pagos = (new \App\Services\TiendaPagoService($p))->config(); } catch (\Throwable $e) {}
        $pagosOk = $pagos && (
            ($pagos['transferencia_activa'] && ($pagos['transferencia_cbu'] || $pagos['transferencia_alias']))
            || $pagos['mercadopago_activa'] || $pagos['cuenta_corriente_activa'] || $pagos['a_convenir_activa']
        );
        $pasos[] = ['clave' => 'pagos', 'titulo' => 'Medios de pago', 'ok' => (bool) $pagosOk, 'detalle' => $pagosOk ? 'Al menos un medio listo' : 'Cargá los datos de transferencia o activá otro medio', 'tab' => 'pagos'];

        $agenda = null;
        try { $agenda = (new \App\Services\TiendaAgendaService($p))->config(); } catch (\Throwable $e) {}
        $pasos[] = ['clave' => 'agenda', 'titulo' => 'Agenda de entregas', 'ok' => true, 'opcional' => true, 'detalle' => $agenda && $agenda['activa'] ? 'Activa' : 'Opcional: solo si fabricás a pedido o entregás en franjas', 'tab' => 'agenda'];

        $habilitada = \App\Services\TiendaService::habilitada($clientId);
        $pasos[] = ['clave' => 'habilitada', 'titulo' => 'Tienda habilitada', 'ok' => $habilitada, 'detalle' => $habilitada ? 'Tu tienda está activa' : 'Cuando esté todo listo, BCN la habilita', 'tab' => null];

        $this->asistente = $pasos;
    }

    public function irAChequeo($filtro)
    {
        $this->filtroChequeo = $filtro === 'sin_foto' ? '' : $filtro;
        $this->filtroFoto = $filtro === 'sin_foto' ? 'sin' : '';
        $this->busqueda = '';
        $this->tabActivo = 'productos';
        $this->resetPage();
    }

    public function updatedFiltroChequeo()
    {
        $this->resetPage();
    }

    public function updatedTabActivo($valor)
    {
        if ($valor === 'configuracion') {
            $this->calcularChequeo();
            $this->calcularAsistente();
        }
    }

    // ------------------------------------------------ descripciones con IA (F4)

    public $iaPendientes = [];     // propuestas a revisar
    public $iaCandidatos = 0;      // articulos sin descripcion ni propuesta
    public $iaMensaje = '';
    public $iaEdicion = [];        // codigo => ['descripcion' => ...] cuando el comercio retoca antes de aceptar

    private function cargarIa()
    {
        $this->setupClientDatabase();
        $srv = new \App\Services\TiendaIaService($this->tablePrefix);
        $this->iaPendientes = $srv->pendientes();
        $this->iaCandidatos = count($srv->candidatos(500));
    }

    public function iaGenerarLote()
    {
        $this->setupClientDatabase();
        $srv = new \App\Services\TiendaIaService($this->tablePrefix);
        $codigos = $srv->candidatos();
        if (empty($codigos)) {
            $this->iaMensaje = 'No quedan artículos sin descripción.';
            return;
        }
        try {
            $n = $srv->generar($codigos, (string) ($this->datosComercio['nombre_fantasia'] ?? session('cliente_nombre') ?? ''), (string) ($this->datosComercio['rubro'] ?? ''));
            $this->iaMensaje = $n ? "La IA propuso descripciones para {$n} artículos. Revisalas abajo." : 'La IA no devolvió propuestas para este lote.';
        } catch (\Throwable $e) {
            Log::error('TiendaIa generar: ' . $e->getMessage());
            $this->iaMensaje = 'No se pudo generar: ' . $e->getMessage();
        }
        $this->cargarIa();
        $this->calcularChequeo();
    }

    public function iaAceptar($codigo)
    {
        $this->setupClientDatabase();
        $editada = $this->iaEdicion[(string) $codigo] ?? null;
        (new \App\Services\TiendaIaService($this->tablePrefix))->aceptar($codigo, $editada ? array_merge($this->sugerenciaDe($codigo), $editada) : null);
        unset($this->iaEdicion[(string) $codigo]);
        $this->cargarIa();
        $this->calcularChequeo();
    }

    public function iaAceptarTodas()
    {
        $this->setupClientDatabase();
        $srv = new \App\Services\TiendaIaService($this->tablePrefix);
        foreach ($this->iaPendientes as $p) {
            $editada = $this->iaEdicion[(string) $p['codigo']] ?? null;
            $srv->aceptar($p['codigo'], $editada ? array_merge($p['sugerencia'], $editada) : null);
        }
        $this->iaEdicion = [];
        $this->iaMensaje = 'Descripciones aceptadas.';
        $this->cargarIa();
        $this->calcularChequeo();
    }

    public function iaDescartar($codigo)
    {
        $this->setupClientDatabase();
        (new \App\Services\TiendaIaService($this->tablePrefix))->descartar($codigo);
        unset($this->iaEdicion[(string) $codigo]);
        $this->cargarIa();
    }

    private function sugerenciaDe($codigo): array
    {
        foreach ($this->iaPendientes as $p) {
            if ((string) $p['codigo'] === (string) $codigo) {
                return $p['sugerencia'];
            }
        }
        return [];
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
            ->where('ta.destacado', 1)->where('a.NOSALE', 0)
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
        $this->invalidarCatalogo();
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

    // ------------------------------------------------ visibilidad en la tienda (tienda_articu.visible_web)

    /** Muestra u oculta un producto en la tienda. */
    public function toggleVisible($codigo)
    {
        $this->setupClientDatabase();
        $actual = DB::connection('client_db')->table($this->tablePrefix . 'tienda_articu')->where('codigo', $codigo)->value('visible_web');
        $this->upsertTiendaArticu((string) $codigo, ['visible_web' => ($actual === null || (int) $actual === 1) ? 0 : 1]);
        $this->invalidarCatalogo();
        $this->calcularContadores();
    }

    /** Cambio masivo: 'todos' muestra todo, 'ninguno' oculta todo, 'con_foto' muestra solo los que tienen foto. */
    public function visibilidadMasiva($modo)
    {
        $this->setupClientDatabase();
        $p = $this->tablePrefix;
        $db = DB::connection('client_db');

        if ($modo === 'todos') {
            $db->table("{$p}tienda_articu")->where('visible_web', 0)->update(['visible_web' => 1]);
            $this->mensaje = 'Ahora se muestran todos los productos en la tienda.';
        } else {
            // Todos los articulos necesitan fila en tienda_articu para poder quedar ocultos
            $db->statement("INSERT IGNORE INTO `{$p}tienda_articu` (codigo) SELECT CODIGO FROM `{$p}articu` WHERE NOSALE = 0");
            if ($modo === 'ninguno') {
                $db->table("{$p}tienda_articu")->update(['visible_web' => 0]);
                $this->mensaje = 'Se ocultaron todos los productos. Podés ir mostrando los que quieras con el ojito.';
            } elseif ($modo === 'con_foto') {
                $db->statement("UPDATE `{$p}tienda_articu` ta SET ta.visible_web = EXISTS (SELECT 1 FROM `{$p}articu_fotos` f WHERE f.codigo = ta.codigo AND f.url IS NOT NULL AND f.url <> '')");
                $this->mensaje = 'Ahora se muestran solo los productos que tienen foto.';
            } else {
                return;
            }
        }

        $this->invalidarCatalogo();
        $this->calcularContadores();
        $this->resetPage();
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
        $this->invalidarCatalogo();
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

        $codigosValidos = DB::connection('client_db')->table($this->tablePrefix . 'articu')->where('NOSALE', 0)->pluck('codigo')->map(fn ($c) => (string) $c)->flip();
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
        $this->setupClientDatabase();
        $this->ficha = (new \App\Services\TiendaSeoService($this->tablePrefix))->fichaDe($codigo);
        $this->mensajeFicha = '';
        $this->foto = null;
        $this->fotosNuevas = [];
        $this->mensajeGaleria = '';
        $this->resetErrorBag();
        $this->cargarGaleria();
    }

    // ------------------------------------------------- ficha: descripcion y SEO por producto (F6)
    public $ficha = ['descripcion' => '', 'meta_titulo' => '', 'meta_descripcion' => ''];
    public $mensajeFicha = '';

    public function guardarFicha()
    {
        if (!$this->productoSeleccionado) {
            return;
        }
        $this->setupClientDatabase();
        $this->upsertTiendaArticu((string) $this->productoSeleccionado, [
            'descripcion_larga' => trim((string) $this->ficha['descripcion']) ?: null,
            'meta_titulo'       => mb_substr(trim((string) $this->ficha['meta_titulo']), 0, 70) ?: null,
            'meta_descripcion'  => mb_substr(trim((string) $this->ficha['meta_descripcion']), 0, 160) ?: null,
        ]);
        $this->invalidarCatalogo();
        $this->mensajeFicha = 'Descripción y datos para Google guardados.';
        $this->calcularChequeo();
    }

    public function cerrarModal()
    {
        $this->productoSeleccionado = null;
        $this->mensajeFicha = '';
        $this->foto = null;
        $this->fotosNuevas = [];
        $this->galeria = [];
        $this->mensajeGaleria = '';
        $this->resetErrorBag();
    }


    // ------------------------------------------------------- galeria (F4)

    public $fotosNuevas = [];      // archivos a subir (varios)
    public $galeria = [];          // fotos del producto seleccionado
    public $mensajeGaleria = '';

    private function cargarGaleria()
    {
        $this->setupClientDatabase();
        $this->galeria = $this->productoSeleccionado
            ? array_map(fn ($f) => (array) $f, (new \App\Services\TiendaFotoService($this->tablePrefix))->de($this->productoSeleccionado))
            : [];
    }

    /** Sube una o varias fotos al producto seleccionado. La primera del articulo queda como principal. */
    public function subirFoto()
    {
        $this->validate([
            'fotosNuevas' => 'required|array|min:1|max:10',
            'fotosNuevas.*' => 'image|max:5120',
        ], [
            'fotosNuevas.required' => 'Seleccioná al menos una imagen.',
            'fotosNuevas.max' => 'Hasta 10 fotos por vez.',
            'fotosNuevas.*.image' => 'Todos los archivos deben ser imágenes (jpg, png, gif, webp).',
            'fotosNuevas.*.max' => 'Cada imagen puede pesar hasta 5MB.',
        ]);

        $this->setupClientDatabase();
        $srv = new \App\Services\TiendaFotoService($this->tablePrefix);
        $ok = 0;

        try {
            $this->subiendo = true;
            foreach ($this->fotosNuevas as $archivo) {
                $srv->agregar($this->productoSeleccionado, $archivo->getRealPath());
                $ok++;
            }
            $this->invalidarCatalogo();
            $this->mensajeGaleria = $ok === 1 ? 'Foto subida.' : "{$ok} fotos subidas.";
            $this->fotosNuevas = [];
            $this->cargarGaleria();
            $this->calcularContadores();
        } catch (\Exception $e) {
            Log::error('Error subiendo foto de producto: ' . $e->getMessage());
            $this->mensajeGaleria = ($ok ? "Se subieron {$ok}. " : '') . 'Error al subir una foto. Intentá de nuevo.';
        } finally {
            $this->subiendo = false;
        }
    }

    public function eliminarFotoGaleria($id)
    {
        $this->setupClientDatabase();
        (new \App\Services\TiendaFotoService($this->tablePrefix))->eliminar((int) $id);
        $this->invalidarCatalogo();
        $this->cargarGaleria();
        $this->calcularContadores();
    }

    public function principalGaleria($id)
    {
        $this->setupClientDatabase();
        (new \App\Services\TiendaFotoService($this->tablePrefix))->principal((int) $id);
        $this->invalidarCatalogo();
        $this->cargarGaleria();
    }

    public function moverGaleria($id, $direccion)
    {
        $this->setupClientDatabase();
        (new \App\Services\TiendaFotoService($this->tablePrefix))->mover((int) $id, $direccion);
        $this->invalidarCatalogo();
        $this->cargarGaleria();
    }

    /** Borra todas las fotos del articulo (boton de la tabla). */
    public function eliminarFoto($codigo)
    {
        $this->setupClientDatabase();

        try {
            (new \App\Services\TiendaFotoService($this->tablePrefix))->eliminarTodas($codigo);

            $this->mensaje = 'Fotos eliminadas correctamente.';
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
        $this->filtroVisible = '';
        $this->resetPage();
        $this->calcularContadores();
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['busqueda', 'filtroFoto', 'filtroVisible'])) {
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
