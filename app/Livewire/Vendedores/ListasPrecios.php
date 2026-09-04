<?php

namespace App\Livewire\Vendedores;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Livewire\Attributes\Layout;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

class ListasPrecios extends Component
{
    use WithPagination;

    public $tablePrefix = '';
    
    // Filtros
    public $busqueda = '';
    public $rubro = '';
    public $marca = '';
    public $depto = '';
    public $proveedor = '';
    public $listaEspecial = '';
    public $fechaDesde;
    public $fechaHasta;

    // Personalización para compartir con cliente (afecta sólo al PDF/impresión)
    public $mostrarLista1 = true;
    public $mostrarLista2 = true;
    public $mostrarListaEspecial = true;
    public $soloEnListaEspecial = false;
    public $mostrarTotales = true;
    public $clienteNombre = '';
    public $fechaEmision = '';
    public $fechaValidez = '';

    // Cliente asignado al vendedor (para auto-rellenar nombre y enviar por mail)
    public $vendedorCodigo;
    public $clienteSeleccionado = ''; // CODIGO del cliente elegido (vacío = manual)
    public $clienteMail = '';
    public $mensajeMail = '';
    public $clientesVendedor = [];

    // Opciones para selects
    public $rubros = [];
    public $marcas = [];
    public $deptos = [];
    public $proveedores = [];
    public $listasEspeciales = [];
    public $perPage = 25;

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        //$this->fechaDesde = Carbon::now()->startOfYear()->format('Y-m-d');
        //$this->fechaHasta = Carbon::now()->format('Y-m-d');
        $this->fechaEmision = Carbon::now()->format('Y-m-d');
        $this->fechaValidez = Carbon::now()->addWeek()->format('Y-m-d');
        $this->vendedorCodigo = session('vendedor_user_id');
        $this->setupClientDatabase();
        $this->cargarOpcionesFiltros();
        $this->cargarClientesVendedor();
    }

    public function cargarClientesVendedor()
    {
        if (empty($this->vendedorCodigo)) {
            return;
        }
        $this->clientesVendedor = DB::connection('client_db')
            ->table("{$this->tablePrefix}clientes")
            ->where('VENDEDOR', $this->vendedorCodigo)
            ->select('CODIGO', 'NOMBRE', 'mail')
            ->orderBy('NOMBRE')
            ->get()
            ->toArray();
    }

    public function updatedClienteSeleccionado($codigo)
    {
        if (empty($codigo)) {
            $this->clienteMail = '';
            return;
        }
        foreach ($this->clientesVendedor as $c) {
            if ((string) $c->CODIGO === (string) $codigo) {
                $this->clienteNombre = $c->NOMBRE;
                $this->clienteMail = trim((string) ($c->mail ?? ''));
                break;
            }
        }
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

    public function cargarOpcionesFiltros()
    {
        // Cargar rubros
        $this->rubros = DB::connection('client_db')
            ->table("{$this->tablePrefix}rubros")
            ->select('CODIGO', 'NOMBRE')
            ->orderBy('NOMBRE')
            ->get();

        // Cargar marcas
        $this->marcas = DB::connection('client_db')
            ->table("{$this->tablePrefix}marcas")
            ->select('CODIGO', 'NOMBRE')
            ->orderBy('NOMBRE')
            ->get();

        // Cargar departamentos
        $this->deptos = DB::connection('client_db')
            ->table("{$this->tablePrefix}deptos")
            ->select('CODIGO', 'NOMBRE')
            ->orderBy('NOMBRE')
            ->get();

        // Cargar proveedores
        $this->proveedores = DB::connection('client_db')
            ->table("{$this->tablePrefix}provee")
            ->select('CODIGO', 'NOMBRE')
            ->orderBy('NOMBRE')
            ->get();

        // Cargar listas especiales
        $this->listasEspeciales = DB::connection('client_db')
            ->table("{$this->tablePrefix}lispre")
            ->select('CODIGO', 'NOMBRE')
            ->orderBy('NOMBRE')
            ->get();
    }

    public function updatedBusqueda()
    {
        $this->resetPage();
    }

    public function updatedRubro()
    {
        $this->resetPage();
    }

    public function updatedMarca()
    {
        $this->resetPage();
    }

    public function updatedDepto()
    {
        $this->resetPage();
    }

    public function updatedProveedor()
    {
        $this->resetPage();
    }

    public function updatedListaEspecial()
    {
        $this->resetPage();
    }

    public function updatedFechaDesde()
    {
        $this->resetPage();
    }

    public function updatedFechaHasta()
    {
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->busqueda = '';
        $this->rubro = '';
        $this->marca = '';
        $this->depto = '';
        $this->proveedor = '';
        $this->listaEspecial = '';
        $this->fechaDesde = '';
        $this->fechaHasta = '';
    }

    public function enviarListaPorMail()
    {
        if (empty($this->clienteMail)) {
            $this->mensajeMail = 'error:El cliente seleccionado no tiene email cargado.';
            return;
        }
        if (!filter_var($this->clienteMail, FILTER_VALIDATE_EMAIL)) {
            $this->mensajeMail = 'error:El email del cliente no es válido.';
            return;
        }

        try {
            @ini_set('memory_limit', '1024M');
            @set_time_limit(300);
            $this->setupClientDatabase();

            $datos = $this->construirDatosImpresion('mail', [
                'mostrarLista1'        => $this->mostrarLista1,
                'mostrarLista2'        => $this->mostrarLista2,
                'mostrarListaEspecial' => $this->mostrarListaEspecial,
                'soloEnListaEspecial'  => $this->soloEnListaEspecial,
                'mostrarTotales'       => $this->mostrarTotales,
                'clienteNombre'        => $this->clienteNombre,
                'fechaEmision'         => $this->fechaEmision,
                'fechaValidez'         => $this->fechaValidez,
            ]);

            $pdf = Pdf::loadView('vendedores.precios-imprimir', $datos)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => false,
                    'isPhpEnabled'         => false,
                    'isRemoteEnabled'      => false,
                    'defaultFont'          => 'DejaVu Sans',
                ]);

            $contenidoPdf = $pdf->output();
            $nombreArchivo = 'lista-precios-' . date('Ymd-His') . '.pdf';
            $destinatario = $this->clienteMail;
            $clienteNombre = $this->clienteNombre ?: 'Cliente';
            $vendedor = session('vendedor_nombre') ?? 'Su vendedor';
            $fechaValidez = $this->fechaValidez
                ? Carbon::parse($this->fechaValidez)->format('d/m/Y')
                : '';

            // Datos de la empresa (tenant del sistema) para From/Reply-To.
            // El cliente final NO debería ver el mail técnico del sistema:
            // el From address es el SMTP configurado, pero el display name
            // es "{Empresa} - {Vendedor}" y el Reply-To es el mail de la empresa.
            $empresa = \App\Models\Cliente::find(session('client_id'));
            $empresaNombre = $empresa->nomemp ?? $empresa->nombre ?? config('app.name', 'Sistema');
            $empresaMail = trim((string) ($empresa->mail ?? ''));

            Mail::send([], [], function ($message) use (
                $destinatario, $clienteNombre, $vendedor, $fechaValidez,
                $contenidoPdf, $nombreArchivo, $empresaNombre, $empresaMail
            ) {
                $cuerpo = "Hola {$clienteNombre},\n\n"
                        . "Le enviamos la lista de precios actualizada adjunta.\n"
                        . ($fechaValidez ? "Válida hasta el {$fechaValidez}.\n\n" : "\n")
                        . "Saludos,\n{$vendedor}\n{$empresaNombre}";

                $message->from(config('mail.from.address'), "{$empresaNombre} - {$vendedor}")
                        ->to($destinatario);

                // Reply-To al mail de la empresa (si lo tiene) para que las
                // respuestas lleguen a quien corresponde, no al SMTP técnico.
                if (!empty($empresaMail) && filter_var($empresaMail, FILTER_VALIDATE_EMAIL)) {
                    $message->replyTo($empresaMail, $empresaNombre);
                }

                $message->subject('Lista de precios - ' . $empresaNombre . ' - ' . date('d/m/Y'))
                        ->text($cuerpo)
                        ->attachData($contenidoPdf, $nombreArchivo, [
                            'mime' => 'application/pdf',
                        ]);
            });

            $this->mensajeMail = 'ok:Lista enviada a ' . $destinatario;
        } catch (\Throwable $e) {
            \Log::error('Error enviando lista por mail: ' . $e->getMessage());
            $this->mensajeMail = 'error:No se pudo enviar el mail. ' . $e->getMessage();
        }
    }

    public function imprimirLista($modo = 'ver')
    {
        // modo: 'ver' | 'descargar' | 'imprimir' | 'compartir'
        // Para "compartir" el backend genera el PDF igual que en "ver"
        // (stream inline); el JS de la página lo descarga como blob y lo
        // pasa a navigator.share() → bandeja nativa (WhatsApp, Mail, etc).
        $modoBackend = $modo === 'compartir' ? 'ver' : $modo;

        $params = [
            'busqueda' => $this->busqueda,
            'rubro' => $this->rubro,
            'marca' => $this->marca,
            'depto' => $this->depto,
            'proveedor' => $this->proveedor,
            'listaEspecial' => $this->listaEspecial,
            'fechaDesde' => $this->fechaDesde,
            'fechaHasta' => $this->fechaHasta,
            // Personalización para cliente
            'mostrarLista1' => $this->mostrarLista1 ? 1 : 0,
            'mostrarLista2' => $this->mostrarLista2 ? 1 : 0,
            'mostrarListaEspecial' => $this->mostrarListaEspecial ? 1 : 0,
            'soloEnListaEspecial' => $this->soloEnListaEspecial ? 1 : 0,
            'mostrarTotales' => $this->mostrarTotales ? 1 : 0,
            'clienteNombre' => $this->clienteNombre,
            'fechaEmision' => $this->fechaEmision,
            'fechaValidez' => $this->fechaValidez,
            'modo' => $modoBackend,
        ];

        $this->dispatch('abrir-impresion-lista', [
            'url' => route('imprimir-lista', $params),
            'modo' => $modo,
            'clienteNombre' => $this->clienteNombre,
        ]);
    }

    private function construirQuery()
    {
        $query = "
            SELECT
                a.CODIGO,
                a.NOMBRE,
                a.PRECIOVEN,
                a.REVENTA,
                a.STOCKACT,
                a.FECMOD,
                r.NOMBRE as rubro_nombre,
                m.NOMBRE as marca_nombre,
                d.NOMBRE as depto_nombre,
                p.NOMBRE as proveedor_nombre
            FROM {$this->tablePrefix}articu a
            LEFT JOIN {$this->tablePrefix}rubros r ON a.RUBRO = r.CODIGO
            LEFT JOIN {$this->tablePrefix}marcas m ON a.MARCA = m.CODIGO
            LEFT JOIN {$this->tablePrefix}deptos d ON a.DEPTO = d.CODIGO
            LEFT JOIN {$this->tablePrefix}provee p ON a.PROV = p.CODIGO
            WHERE (a.NOSALE != 1 OR a.NOSALE IS NULL)
        ";

        $params = [];

        // Aplicar filtros
        if (!empty($this->busqueda)) {
            $query .= " AND (a.NOMBRE LIKE ? OR a.CODIGO LIKE ?)";
            $params[] = '%' . $this->busqueda . '%';
            $params[] = '%' . $this->busqueda . '%';
        }

        if (!empty($this->rubro)) {
            $query .= " AND a.RUBRO = ?";
            $params[] = $this->rubro;
        }

        if (!empty($this->marca)) {
            $query .= " AND a.MARCA = ?";
            $params[] = $this->marca;
        }

        if (!empty($this->depto)) {
            $query .= " AND a.DEPTO = ?";
            $params[] = $this->depto;
        }

        if (!empty($this->proveedor)) {
            $query .= " AND a.PROV = ?";
            $params[] = $this->proveedor;
        }

        if (!empty($this->fechaDesde)) {
            $query .= " AND DATE(a.FECMOD) >= ?";
            $params[] = $this->fechaDesde;
        }

        if (!empty($this->fechaHasta)) {
            $query .= " AND DATE(a.FECMOD) <= ?";
            $params[] = $this->fechaHasta;
        }

        $query .= " ORDER BY a.FECMOD DESC";

        return ['query' => $query, 'params' => $params];
    }

    /**
     * Arma todos los datos para la vista del PDF/HTML.
     * Se usa desde imprimirListaRuta (lee de request) y desde
     * enviarListaPorMail (lee de $this directamente).
     */
    private function construirDatosImpresion($modo, array $o)
    {
        $limiteMaximo = 1000;

        // Precios especiales primero (se usan para filtrar y decorar)
        $preciosEspeciales = [];
        if (!empty($this->listaEspecial)) {
            $preciosEspeciales = DB::connection('client_db')
                ->table("{$this->tablePrefix}lispredet")
                ->where('CODLIS', $this->listaEspecial)
                ->pluck('PRECIO', 'CODART')
                ->toArray();
        }

        $queryData = $this->construirQuery();
        $query = $queryData['query'];
        $queryParams = $queryData['params'];

        // Filtro "solo artículos en lista especial"
        if (!empty($o['soloEnListaEspecial']) && !empty($this->listaEspecial) && !empty($preciosEspeciales)) {
            $codigos = array_keys($preciosEspeciales);
            $placeholders = implode(',', array_fill(0, count($codigos), '?'));
            $query = preg_replace(
                '/ORDER BY/i',
                "AND a.CODIGO IN ($placeholders) ORDER BY",
                $query,
                1
            );
            $queryParams = array_merge($queryParams, $codigos);
        }

        $query .= " LIMIT {$limiteMaximo}";
        $productos = DB::connection('client_db')->select($query, $queryParams);

        // Nombres de filtros para el encabezado
        $rubroNombre = $marcaNombre = $deptoNombre = $proveedorNombre = $listaNombre = '';
        if (!empty($this->rubro)) {
            $rubroNombre = DB::connection('client_db')->table("{$this->tablePrefix}rubros")
                ->where('CODIGO', $this->rubro)->value('NOMBRE') ?? '';
        }
        if (!empty($this->marca)) {
            $marcaNombre = DB::connection('client_db')->table("{$this->tablePrefix}marcas")
                ->where('CODIGO', $this->marca)->value('NOMBRE') ?? '';
        }
        if (!empty($this->depto)) {
            $deptoNombre = DB::connection('client_db')->table("{$this->tablePrefix}deptos")
                ->where('CODIGO', $this->depto)->value('NOMBRE') ?? '';
        }
        if (!empty($this->proveedor)) {
            $proveedorNombre = DB::connection('client_db')->table("{$this->tablePrefix}provee")
                ->where('CODIGO', $this->proveedor)->value('NOMBRE') ?? '';
        }
        if (!empty($this->listaEspecial)) {
            $listaNombre = DB::connection('client_db')->table("{$this->tablePrefix}lispre")
                ->where('CODIGO', $this->listaEspecial)->value('NOMBRE') ?? '';
        }

        return [
            'productos' => collect($productos),
            'preciosEspeciales' => $preciosEspeciales,
            'busqueda' => $this->busqueda,
            'rubro' => $this->rubro,
            'rubroNombre' => $rubroNombre,
            'marca' => $this->marca,
            'marcaNombre' => $marcaNombre,
            'depto' => $this->depto,
            'deptoNombre' => $deptoNombre,
            'proveedor' => $this->proveedor,
            'proveedorNombre' => $proveedorNombre,
            'listaEspecial' => $this->listaEspecial,
            'listaNombre' => $listaNombre,
            'fechaDesde' => $this->fechaDesde,
            'fechaHasta' => $this->fechaHasta,
            'autoImprimir' => $modo === 'imprimir',
            // Personalización para cliente
            'mostrarLista1' => (bool) ($o['mostrarLista1'] ?? true),
            'mostrarLista2' => (bool) ($o['mostrarLista2'] ?? true),
            'mostrarListaEspecial' => (bool) ($o['mostrarListaEspecial'] ?? true),
            'mostrarTotales' => (bool) ($o['mostrarTotales'] ?? true),
            'clienteNombre' => trim((string) ($o['clienteNombre'] ?? '')),
            'fechaEmision' => $o['fechaEmision'] ?? Carbon::now()->format('Y-m-d'),
            'fechaValidez' => $o['fechaValidez'] ?? Carbon::now()->addWeek()->format('Y-m-d'),
        ];
    }

    public function imprimirListaRuta()
    {
        // Subir límites para PDFs grandes
        @ini_set('memory_limit', '1024M');
        @set_time_limit(300);

        $this->setupClientDatabase();

        // Leer filtros desde la request
        $request = request();
        $this->busqueda = $request->get('busqueda', '');
        $this->rubro = $request->get('rubro', '');
        $this->marca = $request->get('marca', '');
        $this->depto = $request->get('depto', '');
        $this->proveedor = $request->get('proveedor', '');
        $this->listaEspecial = $request->get('listaEspecial', '');
        $this->fechaDesde = $request->get('fechaDesde', '');
        $this->fechaHasta = $request->get('fechaHasta', '');

        $modo = $request->get('modo', 'ver'); // 'ver' | 'descargar' | 'imprimir'

        $datos = $this->construirDatosImpresion($modo, [
            'mostrarLista1'        => (bool) $request->get('mostrarLista1', 1),
            'mostrarLista2'        => (bool) $request->get('mostrarLista2', 1),
            'mostrarListaEspecial' => (bool) $request->get('mostrarListaEspecial', 1),
            'soloEnListaEspecial'  => (bool) $request->get('soloEnListaEspecial', 0),
            'mostrarTotales'       => (bool) $request->get('mostrarTotales', 1),
            'clienteNombre'        => $request->get('clienteNombre', ''),
            'fechaEmision'         => $request->get('fechaEmision', Carbon::now()->format('Y-m-d')),
            'fechaValidez'         => $request->get('fechaValidez', Carbon::now()->addWeek()->format('Y-m-d')),
        ]);

        // Modo "imprimir": devolvemos HTML (no PDF) y la vista llama
        // window.print() al cargar. En móvil abre la bandeja nativa con
        // "Guardar como PDF" + impresoras. En desktop abre el diálogo
        // de impresión del navegador.
        if ($modo === 'imprimir') {
            return response()->view('vendedores.precios-imprimir', $datos);
        }

        $pdf = Pdf::loadView('vendedores.precios-imprimir', $datos)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => false,
                'isPhpEnabled'         => false,
                'isRemoteEnabled'      => false,
                'defaultFont'          => 'DejaVu Sans',
            ]);

        $nombreArchivo = 'lista-precios-' . date('Ymd-His') . '.pdf';

        if ($modo === 'descargar') {
            return $pdf->download($nombreArchivo);
        }

        return $pdf->stream($nombreArchivo);
    }


    #[Layout('layouts.vendedores')]
    public function render()
    {
        $this->setupClientDatabase();

        // Obtener todos los productos con una sola consulta
        $queryData = $this->construirQuery();
        $todosProductos = DB::connection('client_db')->select($queryData['query'], $queryData['params']);
        $productos = collect($todosProductos);

        // Implementar paginación manual
        $currentPage = $this->getPage();
        $offset = ($currentPage - 1) * $this->perPage;
        $productosPaginados = $productos->slice($offset, $this->perPage)->values();

        // Crear objeto de paginación
        $totalItems = $productos->count();
        $lastPage = ceil($totalItems / $this->perPage);

        $paginationInfo = (object)[
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'per_page' => $this->perPage,
            'total' => $totalItems,
            'from' => $totalItems > 0 ? $offset + 1 : 0,
            'to' => min($offset + $this->perPage, $totalItems)
        ];

        // Si hay lista especial seleccionada, obtener precios especiales
        $preciosEspeciales = [];
        if (!empty($this->listaEspecial)) {
            $preciosEspeciales = DB::connection('client_db')
                ->table("{$this->tablePrefix}lispredet")
                ->where('CODLIS', $this->listaEspecial)
                ->pluck('PRECIO', 'CODART')
                ->toArray();
        }

        return view('vendedores.listas-precios', [
            'productos' => $productosPaginados,
            'paginationInfo' => $paginationInfo,
            'preciosEspeciales' => $preciosEspeciales
        ]);
    }
}