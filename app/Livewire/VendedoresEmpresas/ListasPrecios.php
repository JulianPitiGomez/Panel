<?php

namespace App\Livewire\VendedoresEmpresas;

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
    public $depto = '';
    public $listaEspecial = '';

    // Personalización para compartir con cliente
    public $mostrarLista1 = true;
    public $mostrarListaEspecial = true;
    public $soloEnListaEspecial = false;
    public $mostrarTotales = true;
    public $clienteNombre = '';
    public $fechaEmision = '';
    public $fechaValidez = '';

    // Cliente asignado al vendedor
    public $vendedorCodigo;
    public $clienteSeleccionado = '';
    public $clienteMail = '';
    public $mensajeMail = '';
    public $clientesVendedor = [];

    // Opciones para selects
    public $deptos = [];
    public $listasEspeciales = [];
    public $perPage = 25;

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        $this->fechaEmision = Carbon::now()->format('Y-m-d');
        $this->fechaValidez = Carbon::now()->addWeek()->format('Y-m-d');
        $this->vendedorCodigo = session('vendedor_empresa_user_id');
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

        // Cargar departamentos
        $this->deptos = DB::connection('client_db')
            ->table("{$this->tablePrefix}deptos")
            ->select('CODIGO', 'NOMBRE')
            ->orderBy('NOMBRE')
            ->get();


        // Cargar listas especiales - USAR ma_lispre
        $this->listasEspeciales = DB::connection('client_db')
            ->table("ma_lispre")
            ->select('CODIGO', 'NOMBRE')
            ->orderBy('NOMBRE')
            ->get();
    }

    public function updatedBusqueda()
    {
        $this->resetPage();
    }


    public function updatedDepto()
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
        $this->depto = '';
        $this->listaEspecial = '';
    }

    public function imprimirLista($modo = 'ver')
    {
        // modo: 'ver' | 'descargar' | 'imprimir' | 'compartir'
        $modoBackend = $modo === 'compartir' ? 'ver' : $modo;

        $params = [
            'busqueda' => $this->busqueda,
            'depto' => $this->depto,
            'listaEspecial' => $this->listaEspecial,
            // Personalización para cliente
            'mostrarLista1' => $this->mostrarLista1 ? 1 : 0,
            'mostrarListaEspecial' => $this->mostrarListaEspecial ? 1 : 0,
            'soloEnListaEspecial' => $this->soloEnListaEspecial ? 1 : 0,
            'mostrarTotales' => $this->mostrarTotales ? 1 : 0,
            'clienteNombre' => $this->clienteNombre,
            'fechaEmision' => $this->fechaEmision,
            'fechaValidez' => $this->fechaValidez,
            'modo' => $modoBackend,
        ];

        $this->dispatch('abrir-impresion-lista', [
            'url' => route('imprimir-lista-empresa', $params),
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
                a.PRECIO,
                a.STOCK,
                d.NOMBRE as depto_nombre
            FROM {$this->tablePrefix}articu a
            LEFT JOIN {$this->tablePrefix}deptos d ON a.DEPTO = d.CODIGO
            WHERE (a.INACTIVO = false OR a.INACTIVO IS NULL)
        ";

        $params = [];

        // Aplicar filtros
        if (!empty($this->busqueda)) {
            $query .= " AND (a.NOMBRE LIKE ? OR a.CODIGO LIKE ?)";
            $params[] = '%' . $this->busqueda . '%';
            $params[] = '%' . $this->busqueda . '%';
        }

        if (!empty($this->depto)) {
            $query .= " AND a.DEPTO = ?";
            $params[] = $this->depto;
        }

        $query .= " ORDER BY a.NOMBRE ASC";

        return ['query' => $query, 'params' => $params];
    }

    private function construirDatosImpresion($modo, array $o)
    {
        $limiteMaximo = 1000;

        $preciosEspeciales = [];
        if (!empty($this->listaEspecial)) {
            $preciosEspeciales = DB::connection('client_db')
                ->table("ma_lispredet")
                ->where('CODLIS', $this->listaEspecial)
                ->pluck('PRECIO', 'CODART')
                ->toArray();
        }

        $queryData = $this->construirQuery();
        $query = $queryData['query'];
        $queryParams = $queryData['params'];

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

        $deptoNombre = '';
        $listaNombre = '';
        if (!empty($this->depto)) {
            $deptoNombre = DB::connection('client_db')->table("{$this->tablePrefix}deptos")
                ->where('CODIGO', $this->depto)->value('NOMBRE') ?? '';
        }
        if (!empty($this->listaEspecial)) {
            $listaNombre = DB::connection('client_db')->table("ma_lispre")
                ->where('CODIGO', $this->listaEspecial)->value('NOMBRE') ?? '';
        }

        return [
            'productos' => collect($productos),
            'preciosEspeciales' => $preciosEspeciales,
            'busqueda' => $this->busqueda,
            'depto' => $this->depto,
            'deptoNombre' => $deptoNombre,
            'listaEspecial' => $this->listaEspecial,
            'listaNombre' => $listaNombre,
            'autoImprimir' => $modo === 'imprimir',
            'mostrarLista1' => (bool) ($o['mostrarLista1'] ?? true),
            'mostrarListaEspecial' => (bool) ($o['mostrarListaEspecial'] ?? true),
            'mostrarTotales' => (bool) ($o['mostrarTotales'] ?? true),
            'clienteNombre' => trim((string) ($o['clienteNombre'] ?? '')),
            'fechaEmision' => $o['fechaEmision'] ?? Carbon::now()->format('Y-m-d'),
            'fechaValidez' => $o['fechaValidez'] ?? Carbon::now()->addWeek()->format('Y-m-d'),
        ];
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
                'mostrarListaEspecial' => $this->mostrarListaEspecial,
                'soloEnListaEspecial'  => $this->soloEnListaEspecial,
                'mostrarTotales'       => $this->mostrarTotales,
                'clienteNombre'        => $this->clienteNombre,
                'fechaEmision'         => $this->fechaEmision,
                'fechaValidez'         => $this->fechaValidez,
            ]);

            $pdf = Pdf::loadView('vendedores-empresas.precios-imprimir', $datos)
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
            $vendedor = session('vendedor_empresa_nombre') ?? 'Su vendedor';
            $fechaValidez = $this->fechaValidez
                ? Carbon::parse($this->fechaValidez)->format('d/m/Y')
                : '';

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
            \Log::error('Error enviando lista por mail (Empresas): ' . $e->getMessage());
            $this->mensajeMail = 'error:No se pudo enviar el mail. ' . $e->getMessage();
        }
    }

    public function imprimirListaRuta()
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(300);

        $this->setupClientDatabase();

        $request = request();
        $this->busqueda = $request->get('busqueda', '');
        $this->depto = $request->get('depto', '');
        $this->listaEspecial = $request->get('listaEspecial', '');

        $modo = $request->get('modo', 'ver');

        $datos = $this->construirDatosImpresion($modo, [
            'mostrarLista1'        => (bool) $request->get('mostrarLista1', 1),
            'mostrarListaEspecial' => (bool) $request->get('mostrarListaEspecial', 1),
            'soloEnListaEspecial'  => (bool) $request->get('soloEnListaEspecial', 0),
            'mostrarTotales'       => (bool) $request->get('mostrarTotales', 1),
            'clienteNombre'        => $request->get('clienteNombre', ''),
            'fechaEmision'         => $request->get('fechaEmision', Carbon::now()->format('Y-m-d')),
            'fechaValidez'         => $request->get('fechaValidez', Carbon::now()->addWeek()->format('Y-m-d')),
        ]);

        // Modo "imprimir": HTML con window.print() auto (bandeja nativa en móvil).
        if ($modo === 'imprimir') {
            return response()->view('vendedores-empresas.precios-imprimir', $datos);
        }

        $pdf = Pdf::loadView('vendedores-empresas.precios-imprimir', $datos)
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


    #[Layout('layouts.vendedores-empresas')]
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

        // Si hay lista especial seleccionada, obtener precios especiales - USAR ma_lispredet
        $preciosEspeciales = [];
        if (!empty($this->listaEspecial)) {
            $preciosEspeciales = DB::connection('client_db')
                ->table("ma_lispredet")
                ->where('CODLIS', $this->listaEspecial)
                ->pluck('PRECIO', 'CODART')
                ->toArray();
        }

        return view('vendedores-empresas.listas-precios', [
            'productos' => $productosPaginados,
            'paginationInfo' => $paginationInfo,
            'preciosEspeciales' => $preciosEspeciales
        ]);
    }
}
