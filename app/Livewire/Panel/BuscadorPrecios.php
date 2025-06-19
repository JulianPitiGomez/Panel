<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;
use Livewire\Attributes\On;

class BuscadorPrecios extends Component
{
    // Búsqueda
    public $codigoProducto = '';
    public $ultimoCodigo = '';
    
    // Información del producto
    public $producto = null;
    public $promocion = null;
    public $mostrarInfo = false;
    public $esPromocion = false;
    
    // Control de tiempo
    public $tiempoMostrar = 10; // segundos
    
    protected $tablePrefix;

    protected $listeners = [
        'limpiarPantalla' => 'limpiarPantalla',
        'buscarProductoPorCodigo' => 'buscarProductoPorCodigo'
    ];

    public function mount()
    {
        $this->setupClientDatabase();
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

    public function updatedCodigoProducto()
    {
        if (!empty($this->codigoProducto) && $this->codigoProducto !== $this->ultimoCodigo) {
            $this->buscarProducto();
            $this->ultimoCodigo = $this->codigoProducto;
        }
    }

    public function buscarProducto()
    {
        if (empty($this->codigoProducto)) {
            return;
        }

        $this->setupClientDatabase();
        
        // Buscar el producto en la tabla articu
        $this->producto = DB::connection('client_db')
            ->table($this->tablePrefix . 'articu')
            ->select('nombre', 'precioven', 'codigo')
            ->where('codigo', $this->codigoProducto)
            ->first();

        if (!$this->producto) {
            $this->mostrarProductoNoEncontrado();
            return;
        }

        // Buscar promociones activas para este producto
        $this->promocion = $this->buscarPromocionActiva($this->codigoProducto);
        
        $this->esPromocion = $this->promocion !== null;
        $this->mostrarInfo = true;
        
        // Limpiar la pantalla después de 10 segundos
        $this->dispatch('iniciarTemporizador', ['tiempo' => $this->tiempoMostrar]);
    }

    private function buscarPromocionActiva($codigoProducto)
    {
        $fechaActual = Carbon::now()->format('Y-m-d');
        
        return DB::connection('client_db')
            ->table($this->tablePrefix . 'promociones')
            ->where('codart', $codigoProducto)
            ->where('tipo', 1)
            ->where('fecha_inicio', '<=', $fechaActual)
            ->where('fecha_fin', '>=', $fechaActual)
            ->orderBy('fecha_fin', 'desc') // Si hay múltiples promociones, tomar la que vence más tarde
            ->first();
    }

    private function mostrarProductoNoEncontrado()
    {
        $this->producto = (object) [
            'nombre' => 'PRODUCTO NO ENCONTRADO',
            'precioven' => 0
        ];
        $this->promocion = null;
        $this->esPromocion = false;
        $this->mostrarInfo = true;
        
        // Limpiar más rápido para productos no encontrados (5 segundos)
        $this->dispatch('iniciarTemporizador', ['tiempo' => 5]);
    }

    public function limpiarPantalla()
    {
        $this->producto = null;
        $this->promocion = null;
        $this->mostrarInfo = false;
        $this->esPromocion = false;
        $this->codigoProducto = '';
        $this->ultimoCodigo = '';
    }

    public function buscarProductoPorCodigo($codigo)
    {
        $this->codigoProducto = $codigo;
        $this->buscarProducto();
    }

    public function getPrecioMostrar()
    {
        if ($this->esPromocion && $this->promocion) {
            return number_format($this->promocion->precio_especial, 2);
        }
        
        if ($this->producto) {
            return number_format($this->producto->precioven, 2);
        }
        
        return '0.00';
    }

    public function getPrecioOriginal()
    {
        if ($this->producto) {
            return number_format($this->producto->precioven, 2);
        }
        
        return '0.00';
    }

    public function getFechaVencimientoPromocion()
    {
        if ($this->promocion) {
            return Carbon::parse($this->promocion->fecha_fin)->format('d/m/Y');
        }
        
        return '';
    }
    #[On('enfocarCampo')]
    public function enfocarInput()
    {
        $this->dispatch('enfocarCampo');
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('panel.buscador-precios');
    }
}