<?php

namespace App\Livewire\PanelResto;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

class Inicio extends Component
{
    // Filtros de fecha
    public $fechaDesde;
    public $fechaHasta;

    // KPIs
    public $ventasDelPeriodo = 0;
    public $comprasDelPeriodo = 0;
    public $sueldosDelPeriodo = 0;
    public $gananciaNeta = 0;
    public $utilidadGlobal = 0;
    public $deudasACobrar = 0;
    public $deudasAPagar = 0;

    // Listas
    public $productosMasVendidos = [];
    public $ventasPorDepartamento = [];

    // Datos para gráfico
    public $ventasPorDia = [];
    public $comprasPorDia = [];

    // Tienda Web
    public $tieneTiendaWeb = false;
    public $tiendaAbierta = false;
    public $nombreTienda = '';
    public $moneda = '$';

    // Datos del abono/suscripción
    public $precioAbono = 0;
    public $fechaVencimiento = '';
    public $diasDemora = 0;
    public $discIva = 0;

    // Datos de comisiones
    public $tieneComisiones = false;
    public $saldoComision = 0;
    public $facturadoComision = 0;
    public $cantidadPedidos = 0;
    public $promedioTicket = 0;
    public $fechaIniComision = '';
    public $fechaLimComision = '';
    public $diasLimComision = 0;

    // Datos de perfil (para pago)
    public $cbu = '';
    public $alias = '';

    // Modal de comisiones
    public $mostrarModalComisiones = false;

    private $tablePrefix;

    public function mount()
    {
        $this->setupClientDatabase();
        $this->tablePrefix = session('client_table_prefix', 're_000001');

        // Inicializar fechas: mes actual
        $this->fechaDesde = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = Carbon::now()->format('Y-m-d');

        $this->cargarDatosTiendaYSuscripcion();
        $this->cargarDatos();
    }

    public function cargarDatos()
    {
        $this->setupClientDatabase();
        $this->cargarVentas();
        $this->cargarCompras();
        $this->cargarSueldos();
        $this->cargarDeudas();
        $this->cargarProductosMasVendidos();
        $this->cargarVentasPorDepartamento();
        $this->cargarGraficoDiario();
        $this->calcularUtilidad();
        $this->dispatch('datos-actualizados');
    }

    public function updatedFechaDesde()
    {
        $this->cargarDatos();
    }

    public function updatedFechaHasta()
    {
        $this->cargarDatos();
    }

    private function cargarVentas()
    {
        // Ventas del período seleccionado
        $ventas = DB::connection('client_db')->select("
            SELECT COALESCE(SUM(importe * IF(ticomp='NC',-1,1)), 0) as total
            FROM {$this->tablePrefix}ventas_encab
            WHERE fecha >= ?
            AND fecha < ? + INTERVAL 1 DAY
        ", [$this->fechaDesde, $this->fechaHasta]);

        $this->ventasDelPeriodo = $ventas[0]->total ?? 0;
    }

    private function cargarCompras()
    {
        try {
            // Compras del período seleccionado
            $compras = DB::connection('client_db')->select("
                SELECT COALESCE(SUM(importe * IF(tipocomp='NC',-1,1)), 0) as total
                FROM {$this->tablePrefix}compras
                WHERE fecfac >= ?
                AND fecfac < ? + INTERVAL 1 DAY
            ", [$this->fechaDesde, $this->fechaHasta]);

            $this->comprasDelPeriodo = $compras[0]->total ?? 0;
        } catch (\Exception $e) {
            $this->comprasDelPeriodo = 0;
        }
    }

    private function cargarSueldos()
    {
        try {
            $sueldos = DB::connection('client_db')->select("
                SELECT COALESCE(SUM(importe), 0) as total
                FROM {$this->tablePrefix}emp_pagsueldo
                WHERE fecha >= ?
                AND fecha < ? + INTERVAL 1 DAY
            ", [$this->fechaDesde, $this->fechaHasta]);

            $this->sueldosDelPeriodo = $sueldos[0]->total ?? 0;
        } catch (\Exception $e) {
            $this->sueldosDelPeriodo = 0;
        }
    }

    private function calcularUtilidad()
    {
        $this->gananciaNeta = $this->ventasDelPeriodo - $this->comprasDelPeriodo - $this->sueldosDelPeriodo;
        $this->utilidadGlobal = $this->ventasDelPeriodo > 0
            ? round(($this->gananciaNeta / $this->ventasDelPeriodo) * 100, 2)
            : 0;
    }

    private function cargarDeudas()
    {
        // Deudas a cobrar (cuotas pendientes de clientes).
        // Solo último semestre: deuda más vieja se considera incobrable/pérdida.
        $seisMesesAtras = Carbon::now()->subMonths(6)->format('Y-m-d');
        $deudasCobrar = DB::connection('client_db')->select("
            SELECT COALESCE(SUM(saldo * IF(tipo='NC',-1,1)), 0) as total
            FROM {$this->tablePrefix}ventas_cuota
            WHERE saldo > 0
            AND FECHA >= ?
        ", [$seisMesesAtras]);
        
        $this->deudasACobrar = $deudasCobrar[0]->total ?? 0;

        // Deudas a pagar (esto depende de tu estructura, asumo que tienes compras_cuota)
        try {
            $deudasPagar = DB::connection('client_db')->select("
                SELECT COALESCE(SUM(saldo *  IF(tipocomp='NC',-1,1)), 0) as total
                FROM {$this->tablePrefix}compras
                WHERE saldo > 0
            ");
            
            $this->deudasAPagar = $deudasPagar[0]->total ?? 0;
        } catch (\Exception $e) {
            // Si no existe la tabla compras_cuota, dejamos en 0
            $this->deudasAPagar = 0;
        }
    }

    private function cargarProductosMasVendidos()
    {
        $version = DB::connection('client_db')->selectOne("SELECT VERSION() as v")->v;
        $isMariaDB = stripos($version, 'MariaDB') !== false;
        $supportsAnyValue = !$isMariaDB && version_compare($version, '5.7.0', '>=');
        $detartFn = $supportsAnyValue ? 'ANY_VALUE(d.detart)' : 'MAX(d.detart)';

        $productos = DB::connection('client_db')->select("
            SELECT
                d.codart,
                {$detartFn} as detart,
                SUM(d.cantidad * IF(LEFT(d.nrofac,2) = 'NC',-1,1)) as cantidad_vendida,
                SUM(d.importe * IF(LEFT(d.nrofac,2) = 'NC',-1,1)) as total_vendido
            FROM {$this->tablePrefix}ventas_det d
            WHERE d.fecha >= ?
            AND d.fecha < ? + INTERVAL 1 DAY
            GROUP BY d.codart
            ORDER BY cantidad_vendida DESC
        ", [$this->fechaDesde, $this->fechaHasta]);

        $this->productosMasVendidos = collect($productos)->map(function ($item) {
            return [
                'producto_id' => $item->codart,
                'nombre' => $item->detart,
                'cantidad' => $item->cantidad_vendida,
                'total' => $item->total_vendido
            ];
        })->toArray();
    }

    private function cargarVentasPorDepartamento()
    {
        try {
            // Se agrega ventas_det por artículo PRIMERO (usa índice de fecha) y
            // recién después se joinea el resultado chico a articu/deptos.
            // Evita que el optimizador escanee toda ventas_det (era ~79s → ~65ms).
            $departamentos = DB::connection('client_db')->select("
                SELECT
                    COALESCE(de.nombre, 'Sin Departamento') as departamento,
                    COUNT(DISTINCT x.codart) as cantidad_ventas,
                    SUM(x.total_vendido) as total_vendido
                FROM (
                    SELECT d.codart, SUM(d.importe) as total_vendido
                    FROM {$this->tablePrefix}ventas_det d
                    WHERE d.fecha >= ?
                    AND d.fecha < ? + INTERVAL 1 DAY
                    GROUP BY d.codart
                ) x
                INNER JOIN {$this->tablePrefix}articu a ON a.codigo = x.codart
                INNER JOIN {$this->tablePrefix}deptos de ON de.codigo = a.depto
                GROUP BY de.nombre
                ORDER BY total_vendido DESC
            ", [$this->fechaDesde, $this->fechaHasta]);

            $this->ventasPorDepartamento = collect($departamentos)->map(function ($item) {
                return [
                    'departamento' => $item->departamento,
                    'cantidad_ventas' => $item->cantidad_ventas,
                    'total' => $item->total_vendido
                ];
            })->toArray();
        } catch (\Exception $e) {
            $this->ventasPorDepartamento = [];
        }
    }

    private function cargarGraficoDiario()
    {
        // Cargar ventas por día
        try {
            $ventasDiarias = DB::connection('client_db')->select("
                SELECT
                    DATE(fecha) as fecha,
                    SUM(importe * IF(ticomp='NC',-1,1)) as total
                FROM {$this->tablePrefix}ventas_encab
                WHERE fecha >= ?
                AND fecha < ? + INTERVAL 1 DAY
                GROUP BY DATE(fecha)
                ORDER BY fecha
            ", [$this->fechaDesde, $this->fechaHasta]);

            $this->ventasPorDia = collect($ventasDiarias)->map(function ($item) {
                return [
                    'fecha' => Carbon::parse($item->fecha)->format('d/m'),
                    'total' => round($item->total, 2)
                ];
            })->toArray();
        } catch (\Exception $e) {
            $this->ventasPorDia = [];
        }

        // Cargar compras por día
        try {
            $comprasDiarias = DB::connection('client_db')->select("
                SELECT
                    DATE(fecfac) as fecha,
                    SUM(importe * IF(tipocomp='NC',-1,1)) as total
                FROM {$this->tablePrefix}compras
                WHERE fecfac >= ?
                AND fecfac < ? + INTERVAL 1 DAY
                GROUP BY DATE(fecfac)
            ", [$this->fechaDesde, $this->fechaHasta]);

            $this->comprasPorDia = collect($comprasDiarias)->map(function ($item) {
                return [
                    'fecha' => Carbon::parse($item->fecha)->format('d/m'),
                    'total' => round($item->total, 2)
                ];
            })->toArray();
        } catch (\Exception $e) {
            $this->comprasPorDia = [];
        }
    }

    public function actualizarDatos()
    {
        $this->cargarDatos();
        $this->dispatch('datos-actualizados');
    }

    public function verEstadoResultado()
    {
        $this->dispatch('panel-navigate', page: 'estado-resultado');
    }

    private function cargarDatosTiendaYSuscripcion()
    {
        $clientId = session('client_id');
        if (!$clientId) {
            return;
        }

        // Cargar datos del cliente (abono, vencimiento, demora, etc.)
        $datosCliente = DB::connection('config')->table('config_clientes as c')
            ->leftJoin('config_packs as v', 'v.codigo', '=', 'c.pack')
            ->select(
                'c.mail', 'c.nombre', 'c.vto', 'c.demo', 'c.pack', 'c.version',
                'c.id_perfil', 'c.abono as precio',
                DB::raw('CURDATE() AS hoy'),
                DB::raw('DATEDIFF(CURDATE(), c.fecha_comision) as demora'),
                'c.disc_iva', 'c.lComision', 'c.tienda',
                'v.nombre as packnom', 'v.version_actual', 'c.id'
            )
            ->where('c.id', $clientId)
            ->first();

        if (!$datosCliente) {
            return;
        }

        // Datos del abono
        $this->precioAbono = $datosCliente->precio ?? 0;
        $this->fechaVencimiento = $datosCliente->vto ?? '';
        $this->diasDemora = $datosCliente->demora ?? 0;
        $this->discIva = $datosCliente->disc_iva ?? 0;
        $this->tieneTiendaWeb = (bool) $datosCliente->lComision;
        $this->tieneComisiones = (bool) $datosCliente->lComision;
        $this->nombreTienda = $datosCliente->tienda ?? '';

        // Cargar perfil (CBU, Alias)
        if ($datosCliente->id_perfil) {
            $perfil = DB::table('perfiles')
                ->select('cbu', 'alias')
                ->where('id', $datosCliente->id_perfil)
                ->first();

            if ($perfil) {
                $this->cbu = $perfil->cbu ?? '';
                $this->alias = $perfil->alias ?? '';
            }
        }

        // Si tiene tienda web, cargar datos del comercio y comisiones
        if ($this->tieneTiendaWeb) {
            $this->cargarDatosComercioWeb();
            $this->cargarDatosComisiones();
        }
    }

    private function cargarDatosComercioWeb()
    {
        try {
            $comercio = DB::connection('client_db')
                ->table($this->tablePrefix . 'comercio_web')
                ->select('*')
                ->first();

            if ($comercio) {
                $this->tiendaAbierta = (bool) ($comercio->lAbierto ?? false);
                $this->moneda = $comercio->moneda ?? '$';
            }
        } catch (\Exception $e) {
            // La tabla puede no existir
            $this->tieneTiendaWeb = false;
        }
    }

    private function cargarDatosComisiones()
    {
        try {
            $sumas = DB::connection('client_db')
                ->table($this->tablePrefix . 'pedidos')
                ->select(
                    DB::raw('COALESCE(SUM(saldo), 0) as saldo'),
                    DB::raw('COALESCE(SUM(importe), 0) as facturado'),
                    DB::raw('COUNT(id) as cantidad'),
                    DB::raw('COALESCE(SUM(importe)/NULLIF(COUNT(id), 0), 0) as promedio'),
                    DB::raw('MIN(fecha) as fecha_ini'),
                    DB::raw('ADDDATE(MIN(fecha), INTERVAL 31 DAY) as fecha_lim'),
                    DB::raw('DATEDIFF(ADDDATE(MIN(fecha), INTERVAL 31 DAY), CURDATE()) as dias_lim')
                )
                ->where('saldo', '>', 0)
                ->where('estado', '<', 5)
                ->first();

            if ($sumas) {
                $this->saldoComision = $sumas->saldo ?? 0;
                $this->facturadoComision = $sumas->facturado ?? 0;
                $this->cantidadPedidos = $sumas->cantidad ?? 0;
                $this->promedioTicket = $sumas->promedio ?? 0;
                $this->fechaIniComision = $sumas->fecha_ini ?? '';
                $this->fechaLimComision = $sumas->fecha_lim ?? '';
                $this->diasLimComision = $sumas->dias_lim ?? 0;
            }
        } catch (\Exception $e) {
            // La tabla puede no existir
        }
    }

    public function toggleTiendaAbierta()
    {
        try {
            // Asegurar conexión a la base de datos del cliente
            $this->setupClientDatabase();

            $nuevoEstado = !$this->tiendaAbierta;

            DB::connection('client_db')
                ->table($this->tablePrefix . 'comercio_web')
                ->update(['lAbierto' => $nuevoEstado ? 1 : 0]);

            $this->tiendaAbierta = $nuevoEstado;

            $mensaje = $nuevoEstado ? 'Tienda abierta correctamente' : 'Tienda cerrada correctamente';
            $this->dispatch('notify', type: 'success', message: $mensaje);

        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Error al cambiar el estado de la tienda: ' . $e->getMessage());
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('panelresto.inicio');
    }

    private function setupClientDatabase()
    {
        $clientId = session('client_id');
        if ($clientId) {
            $cliente = \App\Models\Cliente::find($clientId);
            
            if ($cliente) {                
                // Configurar la conexión client_db
                \Illuminate\Support\Facades\Config::set('database.connections.client_db.database', $cliente->base);

                // Purgar la conexión para forzar reconexión
                DB::purge('client_db');

                // Establecer el prefijo de tabla en la sesión
                session(['client_table_prefix' => $cliente->getTablePrefix()]);
                $this->tablePrefix = $cliente->getTablePrefix();
            }
        }
    }
}