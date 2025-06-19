<?php
namespace App\Livewire\Panel;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Attributes\Layout;

class CobranzasClientes extends Component
{
    use WithPagination;
    
    public $fechaDesde;
    public $fechaHasta;
    public $clienteSeleccionado = '';
    public $cajaSeleccionada = '';
    public $soloSinCerrar = false;
    
    // Datos
    public $resumenFormasPago = [];
    public $clientes = [];
    public $cajas = [];
    public $totalPagos = 0;
    
    // Panel lateral
    public $mostrarDetallePago = false;
    public $pagoSeleccionado = null;
    public $detallePagoCon = [];
    
    // Configuración
    public $mostrarDetalle = true;
    public $mostrarResumen = true;
    public $paginacionSize = 20; // Elementos por página
    
    protected $tablePrefix;
    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->setupClientDatabase();
        
        // Fechas por defecto (último mes)
        $this->fechaDesde = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = Carbon::now()->format('Y-m-d');
        
        $this->cargarDatosIniciales();
        $this->calcularTotalPagos();
        $this->calcularResumenFormasPago();
    }

    private function setupClientDatabase()
    {
        $clientId = session('client_id');
        if ($clientId) {
            $cliente = \App\Models\Cliente::find($clientId);
            
            if ($cliente) {                
                \Illuminate\Support\Facades\Config::set('database.connections.client_db.database', $cliente->base);
                DB::purge('client_db');
                session(['client_table_prefix' => $cliente->getTablePrefix()]);
                $this->tablePrefix = $cliente->getTablePrefix();
            }
        }
    }

    public function cargarDatosIniciales()
    {
        // Cargar clientes
        $this->clientes = DB::connection('client_db')
            ->table($this->tablePrefix . 'clientes')
            ->select('codigo', 'nombre')
            ->orderBy('nombre')
            ->get();

        // Cargar cajas (obtener las distintas cajas de la tabla pagos)
        $this->cajas = DB::connection('client_db')
            ->table($this->tablePrefix . 'pagos')
            ->select('caja')
            ->distinct()
            ->whereNotNull('caja')
            ->orderBy('caja')
            ->get();
    }

    public function getPagosProperty()
    {
        $this->setupClientDatabase();
        
        $query = DB::connection('client_db')
            ->table($this->tablePrefix . 'pagos as p')
            ->leftJoin($this->tablePrefix . 'clientes as c', 'p.CLIENTE', '=', 'c.codigo')
            ->select(
                'p.NUMERO',
                'p.CLIENTE',
                'c.nombre as cliente_nombre',
                'p.TOTAL',
                'p.FECHA',
                'p.USUARIO',
                'p.OBSERVA',
                'p.caja',
                'p.id_cierre',
                'p.checkeado'
            )
            ->whereBetween('p.FECHA', [$this->fechaDesde, $this->fechaHasta])
            ->orderBy('p.FECHA', 'desc')
            ->orderBy('p.NUMERO', 'desc');

        // Filtros adicionales
        if ($this->clienteSeleccionado) {
            $query->where('p.CLIENTE', $this->clienteSeleccionado);
        }

        if ($this->cajaSeleccionada) {
            $query->where('p.caja', $this->cajaSeleccionada);
        }

        if ($this->soloSinCerrar) {
            $query->where('p.checkeado', true);
        }

        return $query->paginate($this->paginacionSize);
    }

    public function calcularTotalPagos()
    {
        $this->setupClientDatabase();
        
        $query = DB::connection('client_db')
            ->table($this->tablePrefix . 'pagos as p')
            ->whereBetween('p.FECHA', [$this->fechaDesde, $this->fechaHasta]);

        // Aplicar los mismos filtros
        if ($this->clienteSeleccionado) {
            $query->where('p.CLIENTE', $this->clienteSeleccionado);
        }

        if ($this->cajaSeleccionada) {
            $query->where('p.caja', $this->cajaSeleccionada);
        }

        if ($this->soloSinCerrar) {
            $query->where('p.checkeado', true);
        }

        $this->totalPagos = $query->sum('TOTAL');
    }

    public function calcularResumenFormasPago()
    {
        $this->setupClientDatabase();
        
        // Obtener todos los números de pagos que coinciden con los filtros (sin paginación)
        $query = DB::connection('client_db')
            ->table($this->tablePrefix . 'pagos as p')
            ->select('p.NUMERO')
            ->whereBetween('p.FECHA', [$this->fechaDesde, $this->fechaHasta]);

        // Aplicar filtros
        if ($this->clienteSeleccionado) {
            $query->where('p.CLIENTE', $this->clienteSeleccionado);
        }

        if ($this->cajaSeleccionada) {
            $query->where('p.caja', $this->cajaSeleccionada);
        }

        if ($this->soloSinCerrar) {
            $query->where('p.checkeado', true);
        }

        $numerosPagos = $query->pluck('NUMERO')->toArray();
        
        if (empty($numerosPagos)) {
            $this->resumenFormasPago = [];
            return;
        }

        $this->resumenFormasPago = DB::connection('client_db')
            ->table($this->tablePrefix . 'pagcon as pc')
            ->leftJoin($this->tablePrefix . 'forpag as fp', 'pc.CODCON', '=', 'fp.codigo')
            ->select(
                'fp.codigo',
                'fp.nombre as forma_pago',
                DB::raw('SUM(pc.IMPORTE) as total_importe'),
                DB::raw('COUNT(*) as cantidad_pagos')
            )
            ->whereIn('pc.NUMERO', $numerosPagos)
            ->groupBy('fp.codigo', 'fp.nombre')
            ->orderBy('total_importe', 'desc')
            ->get();
    }

    public function limpiarFiltros()
    {
        $this->clienteSeleccionado = '';
        $this->cajaSeleccionada = '';
        $this->soloSinCerrar = false;
        $this->fechaDesde = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = Carbon::now()->format('Y-m-d');
        
        $this->resetPage(); // Resetear paginación
        $this->actualizarDatos();
    }

    public function toggleDetalle()
    {
        $this->mostrarDetalle = !$this->mostrarDetalle;
    }

    public function toggleResumen()
    {
        $this->mostrarResumen = !$this->mostrarResumen;
    }

    public function verDetallePago($numeroPago)
    {
        $this->setupClientDatabase();
        
        // Obtener información del pago
        $this->pagoSeleccionado = DB::connection('client_db')
            ->table($this->tablePrefix . 'pagos as p')
            ->leftJoin($this->tablePrefix . 'clientes as c', 'p.CLIENTE', '=', 'c.codigo')
            ->select(
                'p.NUMERO',
                'p.CLIENTE',
                'c.nombre as cliente_nombre',
                'p.TOTAL',
                'p.FECHA',
                'p.USUARIO',
                'p.OBSERVA',
                'p.caja',
                'p.id_cierre',
                'p.checkeado'
            )
            ->where('p.NUMERO', $numeroPago)
            ->first();

        // Obtener detalle de formas de pago
        $this->detallePagoCon = DB::connection('client_db')
            ->table($this->tablePrefix . 'pagcon as pc')
            ->leftJoin($this->tablePrefix . 'forpag as fp', 'pc.CODCON', '=', 'fp.codigo')
            ->select(
                'pc.CODCON',
                'fp.nombre as forma_pago',
                'pc.IMPORTE',
                'pc.OBSERVA',
                'pc.tipocon'
            )
            ->where('pc.NUMERO', $numeroPago)
            ->orderBy('pc.IMPORTE', 'desc')
            ->get();

        $this->mostrarDetallePago = true;
    }

    public function cerrarDetallePago()
    {
        $this->mostrarDetallePago = false;
        $this->pagoSeleccionado = null;
        $this->detallePagoCon = [];
    }

    public function cambiarTamanoPagina($size)
    {
        $this->paginacionSize = $size;
        $this->resetPage();
    }

    private function actualizarDatos()
    {
        $this->calcularTotalPagos();
        $this->calcularResumenFormasPago();
    }

    #[Layout('layouts.app')]    
    public function render()
    {
        return view('panel.cobranzas-clientes', [
            'pagos' => $this->pagos
        ]);
    }

    // Watchers para actualizar automáticamente
    public function updated($propertyName)
    {
        if (in_array($propertyName, ['fechaDesde', 'fechaHasta', 'clienteSeleccionado', 'cajaSeleccionada', 'soloSinCerrar'])) {
            $this->resetPage(); // Resetear a la primera página cuando cambian los filtros
            $this->actualizarDatos();
        }
    }
}