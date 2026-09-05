<?php

namespace App\Livewire\Tienda;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Cliente;

class Catalogo extends Component
{
    public $tiendaSlug;
    public $tablePrefix;
    public $nombreTienda;
    public $modo; // TiendaService::MODO_PUBLICA | MODO_PRIVADA

    public $busqueda = '';
    public $rubrosSeleccionados = [];
    public $marcasSeleccionadas = [];
    public $deptosSeleccionados = [];

    public $rubros = [];
    public $marcas = [];
    public $deptos = [];

    public $carrito = [];
    public $cantidadCarrito = 0;

    public $paginaActual = 1;
    public $porPagina = 18;
    public $totalProductos = 0;

    // Configuración visual
    public $colores = [];
    public $filtrosVisibles = [];
    public $layoutCatalogo = 'top';
    public $estiloTarjeta = 'estandar';
    public $columnasDesktop = 4;
    public $ordenDefecto = 'nombre_asc';
    public $estiloHeader = 'completo';
    public $modoCantidad = 'rapido';
    public $permiteDecimal = false;
    public $vendeSinStock = true;      // config web del comercio (TiendaStockService)
    public $mensajeCerrada = null;     // aviso de tienda cerrada (TiendaAgendaService)
    public $habituales = [];           // lo que este comprador compra seguido (ultimos 12 meses)
    public $disponibles = [];          // codigo => disponible, de la pagina visible
    public $avisoStock = '';           // cuando se recorta una cantidad por falta de stock

    // Modal cantidad
    public $modalCantidadAbierto = false;
    public $productoModal = null;
    public $cantidadModal = 1;

    public function mount($tienda)
    {
        $this->tiendaSlug = $tienda;
        $this->setupClientDatabase();

        $this->nombreTienda = session('tienda_nombre');
        $this->modo = \App\Services\TiendaService::normalizarModo(session('tienda_modo'));
        $this->carrito = session('tienda_carrito', []);
        $this->cantidadCarrito = count($this->carrito);

        $datos = session('tienda_datos', []);
        $this->colores = [
            'fondo' => $datos['color_fondo'] ?? '#f9fafb',
            'texto' => $datos['color_texto'] ?? '#1f2937',
            'precio' => $datos['color_precio'] ?? '#1E1B35',
            'boton' => $datos['color_boton'] ?? '#FFAF22',
        ];
        $this->filtrosVisibles = [
            'depto' => $datos['filtro_depto'] ?? true,
            'rubro' => $datos['filtro_rubro'] ?? true,
            'marca' => $datos['filtro_marca'] ?? true,
        ];

        $this->layoutCatalogo = $datos['layout_catalogo'] ?? 'top';
        $this->estiloTarjeta = $datos['estilo_tarjeta'] ?? 'estandar';
        $this->columnasDesktop = $datos['columnas_desktop'] ?? 4;
        $this->ordenDefecto = $datos['orden_defecto'] ?? 'nombre_asc';
        $this->estiloHeader = $datos['estilo_header'] ?? 'completo';
        $this->modoCantidad = $datos['modo_cantidad'] ?? 'rapido';
        $this->permiteDecimal = (bool) ($datos['permite_decimal'] ?? false);
        $this->vendeSinStock = \App\Services\TiendaStockService::vendeSinStock($datos);

        try {
            $ag = (new \App\Services\TiendaAgendaService($this->tablePrefix))->config();
            if (!$ag['abierta'] && $ag['muestra_cerrada']) {
                $this->mensajeCerrada = $ag['recibe_cerrada']
                    ? 'La tienda está cerrada por el momento. Podés dejar tu pedido y lo preparamos cuando volvamos a abrir.'
                    : 'La tienda está cerrada por el momento. No estamos tomando pedidos.';
            }
        } catch (\Throwable $e) {
            // sin tablas de agenda: se ignora
        }

        $this->cargarFiltros();
    }

    private function setupClientDatabase()
    {
        $configId = session('tienda_config_id');
        if (!$configId) {
            return redirect()->route('tienda.index', $this->tiendaSlug);
        }

        $cliente = Cliente::find($configId);
        if ($cliente) {
            Config::set('database.connections.client_db.database', $cliente->base);
            DB::purge('client_db');
            $this->tablePrefix = $cliente->getTablePrefix();
        }
    }

    private function cargarFiltros()
    {
        $this->setupClientDatabase();

        $this->rubros = DB::connection('client_db')
            ->table("{$this->tablePrefix}rubros")
            ->orderBy('NOMBRE')
            ->get(['CODIGO', 'NOMBRE'])
            ->map(fn($r) => ['cod' => $r->CODIGO, 'nom' => $r->NOMBRE])
            ->toArray();

        $this->marcas = DB::connection('client_db')
            ->table("{$this->tablePrefix}marcas")
            ->orderBy('NOMBRE')
            ->get(['CODIGO', 'NOMBRE'])
            ->map(fn($m) => ['cod' => $m->CODIGO, 'nom' => $m->NOMBRE])
            ->toArray();

        $this->deptos = DB::connection('client_db')
            ->table("{$this->tablePrefix}deptos")
            ->orderBy('NOMBRE')
            ->get(['CODIGO', 'NOMBRE'])
            ->map(fn($d) => ['cod' => $d->CODIGO, 'nom' => $d->NOMBRE])
            ->toArray();
    }

    public function updatedBusqueda()
    {
        $this->paginaActual = 1;
    }

    public function toggleFiltro($tipo, $valor)
    {
        $prop = match($tipo) {
            'depto' => 'deptosSeleccionados',
            'rubro' => 'rubrosSeleccionados',
            'marca' => 'marcasSeleccionadas',
        };

        if (in_array($valor, $this->{$prop})) {
            $this->{$prop} = array_values(array_diff($this->{$prop}, [$valor]));
        } else {
            $this->{$prop}[] = $valor;
        }

        $this->paginaActual = 1;
    }

    public function quitarFiltro($tipo, $valor)
    {
        $this->toggleFiltro($tipo, $valor);
    }

    public function limpiarFiltros()
    {
        $this->busqueda = '';
        $this->rubrosSeleccionados = [];
        $this->marcasSeleccionadas = [];
        $this->deptosSeleccionados = [];
        $this->paginaActual = 1;
    }

    public function paginaAnterior()
    {
        if ($this->paginaActual > 1) {
            $this->paginaActual--;
        }
    }

    public function paginaSiguiente()
    {
        $totalPaginas = ceil($this->totalProductos / $this->porPagina);
        if ($this->paginaActual < $totalPaginas) {
            $this->paginaActual++;
        }
    }

    public function irAPagina($pagina)
    {
        $this->paginaActual = $pagina;
    }

    private function obtenerProductos()
    {
        $this->setupClientDatabase();

        $query = DB::connection('client_db')
            ->table("{$this->tablePrefix}articu as a")
            ->leftJoin("{$this->tablePrefix}articu_fotos as f", 'a.CODIGO', '=', 'f.codigo')
            ->leftJoin("{$this->tablePrefix}ivas as i", 'a.IVA', '=', 'i.codigo')
            ->where('a.NOSALE', 0)
            ->select('a.CODIGO', 'a.NOMBRE', 'a.PRECIOVEN', 'a.REVENTA', 'a.STOCKACT', 'a.DEPTO', 'a.RUBRO', 'a.MARCA', 'f.url as foto_url', 'i.tasa as tasa_iva');

        if ($this->busqueda) {
            $palabras = array_filter(explode(' ', trim($this->busqueda)));
            $query->where(function ($q) use ($palabras) {
                // Buscar por código exacto parcial
                $q->where('a.CODIGO', 'like', "%{$this->busqueda}%")
                  // O que el nombre contenga TODAS las palabras (en cualquier orden)
                  ->orWhere(function ($q2) use ($palabras) {
                      foreach ($palabras as $palabra) {
                          $q2->where('a.NOMBRE', 'like', "%{$palabra}%");
                      }
                  });
            });
        }

        if (!empty($this->rubrosSeleccionados)) {
            $query->whereIn('a.RUBRO', $this->rubrosSeleccionados);
        }

        if (!empty($this->marcasSeleccionadas)) {
            $query->whereIn('a.MARCA', $this->marcasSeleccionadas);
        }

        if (!empty($this->deptosSeleccionados)) {
            $query->whereIn('a.DEPTO', $this->deptosSeleccionados);
        }

        $this->totalProductos = $query->count();

        $ordenColumna = match($this->ordenDefecto) {
            'nombre_desc' => ['a.NOMBRE', 'desc'],
            'precio_asc' => ['a.PRECIOVEN', 'asc'],
            'precio_desc' => ['a.PRECIOVEN', 'desc'],
            default => ['a.NOMBRE', 'asc'],
        };

        $productos = $query
            ->orderBy($ordenColumna[0], $ordenColumna[1])
            ->skip(($this->paginaActual - 1) * $this->porPagina)
            ->take($this->porPagina)
            ->get();

        // Disponible real (STOCKACT - comprometido en pedidos abiertos): una sola consulta para la pagina
        $this->disponibles = (new \App\Services\TiendaStockService($this->tablePrefix))->disponibleDe($productos);

        return $productos;
    }

    /**
     * "Lo que comprás habitualmente": los articulos que mas veces pidio este cliente
     * en el ultimo año, con precio y disponibilidad de hoy. Generaliza lo que ya hace
     * el historial de vendedores. Se muestra sin busqueda ni filtros activos.
     */
    private function cargarHabituales()
    {
        $codcli = session('tienda_comprador_id');
        if (!$codcli || !session('tienda_comprador_web_id')) {
            $this->habituales = [];
            return;
        }

        $frecuentes = DB::connection('client_db')
            ->table("{$this->tablePrefix}pedidos_det as d")
            ->join("{$this->tablePrefix}pedidos_encab as e", 'e.id', '=', 'd.idpedido')
            ->where('e.codcli', $codcli)
            ->where('e.fecha', '>=', now()->subYear()->toDateString())
            ->groupBy('d.codart')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(8)
            ->pluck('d.codart')
            ->all();

        if (empty($frecuentes)) {
            $this->habituales = [];
            return;
        }

        $productos = DB::connection('client_db')
            ->table("{$this->tablePrefix}articu as a")
            ->leftJoin("{$this->tablePrefix}articu_fotos as f", 'a.CODIGO', '=', 'f.codigo')
            ->leftJoin("{$this->tablePrefix}ivas as i", 'a.IVA', '=', 'i.codigo')
            ->whereIn('a.CODIGO', $frecuentes)
            ->where('a.NOSALE', 0)
            ->get(['a.CODIGO', 'a.NOMBRE', 'a.PRECIOVEN', 'a.REVENTA', 'a.STOCKACT', 'f.url as foto_url', 'i.tasa as tasa_iva'])
            ->keyBy('CODIGO');

        // Mantener el orden por frecuencia
        $this->habituales = collect($frecuentes)->map(fn ($c) => $productos[$c] ?? null)->filter()->values()->all();
    }

    /** Disponible de un producto de la pagina actual. */
    public function disponibleDe($codigo): float
    {
        return (float) ($this->disponibles[$codigo] ?? 0);
    }

    /** IVA incluido en el precio unitario que se muestra (0 si el articulo es exento / tasa cero). */
    public function ivaIncluido($producto): float
    {
        return \App\Services\TiendaPrecioService::desglosar((float) $this->obtenerPrecio($producto), $producto->tasa_iva ?? null)['iva'];
    }

    /** Cantidad ya cargada en el carrito para un articulo. */
    private function enCarrito($codigo): float
    {
        foreach ($this->carrito as $item) {
            if ($item['codigo'] == $codigo) {
                return (float) $item['cantidad'];
            }
        }
        return 0.0;
    }

    public function obtenerPrecio($producto)
    {
        // Si es tienda privada y hay comprador logueado, usar su lista de precios
        if (\App\Services\TiendaService::esPrivada($this->modo) && session('tienda_comprador_id')) {
            return $this->obtenerPrecioCliente($producto);
        }

        // Tienda pública: mostrar PRECIOVEN por defecto
        return $producto->PRECIOVEN;
    }

    private function obtenerPrecioCliente($producto)
    {
        $lispreesp = session('tienda_comprador_lispreesp');
        $lispre = session('tienda_comprador_lispre');

        if ($lispreesp) {
            $this->setupClientDatabase();
            $especial = DB::connection('client_db')
                ->table("{$this->tablePrefix}lispredet")
                ->where('CODLIS', $lispreesp)
                ->where('CODART', $producto->CODIGO)
                ->value('PRECIO');

            if ($especial !== null) return $especial;
        }

        return $lispre == 2 ? $producto->REVENTA : $producto->PRECIOVEN;
    }

    public function abrirModalCantidad($codigo)
    {
        $this->setupClientDatabase();
        $producto = DB::connection('client_db')
            ->table("{$this->tablePrefix}articu as a")
            ->leftJoin("{$this->tablePrefix}articu_fotos as f", 'a.CODIGO', '=', 'f.codigo')
            ->leftJoin("{$this->tablePrefix}ivas as i", 'a.IVA', '=', 'i.codigo')
            ->where('a.CODIGO', $codigo)
            ->select('a.CODIGO', 'a.NOMBRE', 'a.PRECIOVEN', 'a.REVENTA', 'a.STOCKACT', 'f.url as foto_url', 'i.tasa as tasa_iva')
            ->first();

        if (!$producto) return;

        $disponible = (new \App\Services\TiendaStockService($this->tablePrefix))->disponible($codigo) - $this->enCarrito($codigo);

        $this->productoModal = [
            'codigo' => $producto->CODIGO,
            'nombre' => $producto->NOMBRE,
            'precio' => $this->obtenerPrecio($producto),
            'foto_url' => $producto->foto_url,
            'tasa' => (float) ($producto->tasa_iva ?? 0),
            'iva' => $this->ivaIncluido($producto),
            'disponible' => max($disponible, 0),
        ];
        $this->cantidadModal = $this->permiteDecimal ? 1 : 1;
        $this->modalCantidadAbierto = true;
    }

    public function incrementarCantidadModal()
    {
        $step = $this->permiteDecimal ? 0.5 : 1;
        $this->cantidadModal = (float) $this->cantidadModal + $step;
    }

    public function decrementarCantidadModal()
    {
        $step = $this->permiteDecimal ? 0.5 : 1;
        $min = $this->permiteDecimal ? 0.01 : 1;
        $nuevo = (float) $this->cantidadModal - $step;
        $this->cantidadModal = $nuevo < $min ? $min : $nuevo;
    }

    public function cerrarModalCantidad()
    {
        $this->modalCantidadAbierto = false;
        $this->productoModal = null;
        $this->cantidadModal = 1;
    }

    public function confirmarCantidadModal()
    {
        if (!$this->productoModal) return;
        $cantidad = $this->permiteDecimal ? (float) $this->cantidadModal : (int) $this->cantidadModal;
        if ($cantidad <= 0) return;
        $this->agregarAlCarrito($this->productoModal['codigo'], $cantidad);
        $this->cerrarModalCantidad();
    }

    public function agregarAlCarrito($codigo, $cantidadAgregar = 1)
    {
        $this->setupClientDatabase();

        $cantidadAgregar = $this->permiteDecimal ? (float) $cantidadAgregar : (int) $cantidadAgregar;
        if ($cantidadAgregar <= 0) $cantidadAgregar = 1;

        $producto = DB::connection('client_db')
            ->table("{$this->tablePrefix}articu as a")
            ->leftJoin("{$this->tablePrefix}articu_fotos as f", 'a.CODIGO', '=', 'f.codigo')
            ->leftJoin("{$this->tablePrefix}ivas as i", 'a.IVA', '=', 'i.codigo')
            ->where('a.CODIGO', $codigo)
            ->select('a.CODIGO', 'a.NOMBRE', 'a.PRECIOVEN', 'a.REVENTA', 'a.STOCKACT', 'f.url as foto_url', 'i.tasa as tasa_iva')
            ->first();

        if (!$producto) return;

        $this->avisoStock = '';

        // Si el comercio no vende sin stock, la cantidad se limita al disponible (descontando lo que ya esta en el carrito)
        if (!$this->vendeSinStock) {
            $disponible = (new \App\Services\TiendaStockService($this->tablePrefix))->disponible($codigo) - $this->enCarrito($codigo);
            if ($disponible <= 0) {
                $this->avisoStock = "No hay más stock disponible de {$producto->NOMBRE}.";
                $this->dispatch('stock-insuficiente');
                return;
            }
            if ($cantidadAgregar > $disponible) {
                $cantidadAgregar = $this->permiteDecimal ? round($disponible, 2) : (int) floor($disponible);
                $this->avisoStock = "De {$producto->NOMBRE} solo quedan " . \App\Services\TiendaStockService::formato($disponible) . ". Se agregó esa cantidad.";
            }
        }

        $precio = $this->obtenerPrecio($producto);

        // Buscar si ya existe en el carrito
        $existente = false;
        foreach ($this->carrito as $key => $item) {
            if ($item['codigo'] == $codigo) {
                $this->carrito[$key]['cantidad'] += $cantidadAgregar;
                $this->carrito[$key]['subtotal'] = $this->carrito[$key]['cantidad'] * $this->carrito[$key]['precio'];
                $this->carrito[$key]['tasa'] = (float) ($producto->tasa_iva ?? 0);
                $existente = true;
                break;
            }
        }

        if (!$existente) {
            $this->carrito[] = [
                'codigo' => $producto->CODIGO,
                'nombre' => $producto->NOMBRE,
                'precio' => $precio,
                'precio_ven' => $producto->PRECIOVEN,
                'precio_rev' => $producto->REVENTA,
                'cantidad' => $cantidadAgregar,
                'subtotal' => $precio * $cantidadAgregar,
                'foto_url' => $producto->foto_url,
                'tasa' => (float) ($producto->tasa_iva ?? 0),
            ];
        }

        session(['tienda_carrito' => $this->carrito]);
        $this->cantidadCarrito = count($this->carrito);

        $this->dispatch('producto-agregado');
    }

    public function irAlCarrito()
    {
        return redirect()->route('tienda.carrito', $this->tiendaSlug);
    }

    #[Layout('layouts.tienda')]
    public function render()
    {
        $productos = $this->obtenerProductos();
        $totalPaginas = ceil($this->totalProductos / $this->porPagina);

        $sinFiltros = !$this->busqueda && empty($this->rubrosSeleccionados) && empty($this->marcasSeleccionadas) && empty($this->deptosSeleccionados) && $this->paginaActual == 1;
        if ($sinFiltros) {
            $this->cargarHabituales();
        } else {
            $this->habituales = [];
        }

        return view('tienda.catalogo', [
            'productos' => $productos,
            'totalPaginas' => $totalPaginas,
        ]);
    }
}
