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
    ];

    // Tienda config
    public $tiendaSlug = '';
    public $tiendaModo = TiendaService::MODO_PUBLICA; // ver config/tienda.php

    // Tab activo
    public $tabActivo = 'productos'; // 'productos', 'configuracion'

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
            ->select(
                'a.codigo',
                'a.nombre',
                'a.precioven',
                'f.url as foto_url'
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
