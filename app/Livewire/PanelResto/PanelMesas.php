<?php

namespace App\Livewire\PanelResto;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class PanelMesas extends Component
{
    // Control de salones
    public $salones = [];
    public $salonActivo = null;
    
    // Mesas del salón activo
    public $mesas = [];
    
    // Modal de detalle de mesa
    public $mostrarDetalle = false;
    public $mesaSeleccionada = null;
    public $detalleMesa = [];
    public $totalMesa = 0;
    
    protected $tablePrefix;

    public function mount()
    {
        $this->setupClientDatabase();
        $this->cargarSalones();
        
        // Seleccionar el primer salón por defecto
        if (!empty($this->salones)) {
            $this->salonActivo = $this->salones[0]->codigo;
            $this->cargarMesasSalon();
        }
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

    public function cargarSalones()
    {
        $this->setupClientDatabase();
        
        try {
            $this->salones = DB::connection('client_db')
                ->table($this->tablePrefix . 'salones')
                ->select('codigo', 'nombre')
                ->orderBy('codigo')
                ->get();
        } catch (\Exception $e) {
            $this->salones = [];
            \Log::error('Error cargando salones: ' . $e->getMessage());
        }
    }

    public function seleccionarSalon($codigoSalon)
    {
        $this->salonActivo = $codigoSalon;
        $this->cargarMesasSalon();
        $this->cerrarDetalle();
    }

    public function cargarMesasSalon()
    {
        if (!$this->salonActivo) {
            $this->mesas = [];
            return;
        }

        $this->setupClientDatabase();
        
        try {
            $this->mesas = DB::connection('client_db')
                ->table($this->tablePrefix . 'mesas')
                ->select('NUMERO', 'RECURSO', 'COMENSALES', 'OCUPADA', 'fechaaper', 'horaaper', 'salon')
                ->where('salon', $this->salonActivo)
                ->orderBy('NUMERO')
                ->get();
        } catch (\Exception $e) {
            $this->mesas = [];
            \Log::error('Error cargando mesas: ' . $e->getMessage());
        }
    }

    public function seleccionarMesa($numeroMesa)
    {
        $this->mesaSeleccionada = $this->mesas->firstWhere('NUMERO', $numeroMesa);
        
        if ($this->mesaSeleccionada) {
            $this->cargarDetalleMesa($numeroMesa);
            $this->mostrarDetalle = true;
        }
    }

    private function cargarDetalleMesa($numeroMesa)
    {
        $this->setupClientDatabase();
        
        try {
            $this->detalleMesa = DB::connection('client_db')
                ->table($this->tablePrefix . 'detalle')
                ->select('MESA', 'CODART', 'NOMART', 'PUNITARIO', 'CANTIDAD', 'TOTAL', 'caracteristicas', 'IMPRESA', 'seleccion', 'observa')
                ->where('MESA', $numeroMesa)
                ->get();
            
            $this->totalMesa = $this->detalleMesa->sum('TOTAL');
            
        } catch (\Exception $e) {
            $this->detalleMesa = [];
            $this->totalMesa = 0;
            \Log::error('Error cargando detalle de mesa: ' . $e->getMessage());
        }
    }

    public function cerrarDetalle()
    {
        $this->mostrarDetalle = false;
        $this->mesaSeleccionada = null;
        $this->detalleMesa = [];
        $this->totalMesa = 0;
    }

    public function actualizarMesas()
    {
        $this->cargarMesasSalon();
    }

    // Métodos auxiliares para la vista
    public function getColorMesa($recurso)
    {
        $tipo = strtoupper($recurso);
        
        // Libre (Verde)
        if (in_array($tipo, ['MESA2', 'MESA4', 'BANC2'])) {
            return 'bg-green-500 border-green-600';
        }
        
        // Ocupada (Rojo)
        if (in_array($tipo, ['MESA20', 'MESA40', 'BANC20'])) {
            return 'bg-red-500 border-red-600';
        }
        
        // Pronto cierre (Celeste)
        if (in_array($tipo, ['MESA21', 'MESA41', 'BANC21'])) {
            return 'bg-blue-500 border-blue-600';
        }
        
        // Reservada (Amarillo)
        if (in_array($tipo, ['MESA22', 'MESA42', 'BANC22'])) {
            return 'bg-yellow-500 border-yellow-600';
        }
        
        return 'bg-gray-500 border-gray-600';
    }

    public function getIconoMesa($recurso)
    {
        $tipo = strtoupper($recurso);
        
        // Mesa redonda
        if (str_contains($tipo, 'MESA2')) {
            return 'fas fa-circle';
        }
        
        // Mesa cuadrada
        if (str_contains($tipo, 'MESA4')) {
            return 'fas fa-square';
        }
        
        // Barra
        if (str_contains($tipo, 'BANC')) {
            return 'fas fa-minus';
        }
        
        return 'fas fa-question';
    }

    public function getEstadoMesa($recurso)
    {
        $tipo = strtoupper($recurso);
        
        if (in_array($tipo, ['MESA2', 'MESA4', 'BANC2'])) {
            return 'Libre';
        }
        
        if (in_array($tipo, ['MESA20', 'MESA40', 'BANC20'])) {
            return 'Ocupada';
        }
        
        if (in_array($tipo, ['MESA21', 'MESA41', 'BANC21'])) {
            return 'Pronto Cierre';
        }
        
        if (in_array($tipo, ['MESA22', 'MESA42', 'BANC22'])) {
            return 'Reservada';
        }
        
        return 'Desconocido';
    }

    public function getTipoMesa($recurso)
    {
        $tipo = strtoupper($recurso);
        
        if (str_contains($tipo, 'MESA2')) {
            return 'Mesa Redonda';
        }
        
        if (str_contains($tipo, 'MESA4')) {
            return 'Mesa Cuadrada';
        }
        
        if (str_contains($tipo, 'BANC')) {
            return 'Barra';
        }
        
        return 'Mesa';
    }

    public function getColorTextoMesa($recurso)
    {
        $tipo = strtoupper($recurso);
        
        // Para amarillo usar texto negro
        if (in_array($tipo, ['MESA22', 'MESA42', 'BANC22'])) {
            return 'text-black';
        }
        
        return 'text-white';
    }

    public function getSalonNombre($codigo)
    {
        $salon = collect($this->salones)->firstWhere('codigo', $codigo);
        return $salon ? $salon->nombre : "Salón $codigo";
    }

    public function getContadorMesasPorEstado($estado)
    {
        $recursos = [];
        
        switch ($estado) {
            case 'libre':
                $recursos = ['MESA2', 'MESA4', 'BANC2'];
                break;
            case 'ocupada':
                $recursos = ['MESA20', 'MESA40', 'BANC20'];
                break;
            case 'pronto_cierre':
                $recursos = ['MESA21', 'MESA41', 'BANC21'];
                break;
            case 'reservada':
                $recursos = ['MESA22', 'MESA42', 'BANC22'];
                break;
        }
        
        return $this->mesas->whereIn('RECURSO', $recursos)->count();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('panelresto.panel-mesas');
    }
}