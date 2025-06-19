<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

class Inicio extends Component
{
    public $ventasDelDia = 0;
    public $ventasDelMes = 0;
    public $ventasDelAno = 0;
    public $deudasACobrar = 0;
    public $deudasAPagar = 0;
    public $productosMasVendidos = [];
    public $ventasPorDepartamento = [];
    
    private $tablePrefix;

    public function mount()
    {
        $this->setupClientDatabase();
        $this->tablePrefix = session('client_table_prefix', 'ge_000001');
        $this->cargarDatos();
    }

    public function cargarDatos()
    {
        $this->setupClientDatabase(); 
        $this->cargarVentas();
        $this->cargarDeudas();
        $this->cargarProductosMasVendidos();
        $this->cargarVentasPorDepartamento();
    }

    private function cargarVentas()
    {
        $hoy = Carbon::now()->format('Y-m-d');
        $inicioMes = Carbon::now()->startOfMonth()->format('Y-m-d');
        $inicioAno = Carbon::now()->startOfYear()->format('Y-m-d');

        // Ventas del día
        $ventasDia = DB::connection('client_db')->select("
            SELECT COALESCE(SUM(importe * IF(ticomp='NC',-1,1)), 0) as total
            FROM {$this->tablePrefix}ventas_encab 
            WHERE DATE(fecha) = ? 
        ", [$hoy]);
        
        $this->ventasDelDia = $ventasDia[0]->total ?? 0;

        // Ventas del mes
        $ventasMes = DB::connection('client_db')->select("
            SELECT COALESCE(SUM(importe * IF(ticomp='NC',-1,1)), 0) as total
            FROM {$this->tablePrefix}ventas_encab 
            WHERE DATE(fecha) >= ? 
            AND DATE(fecha) <= ?
        ", [$inicioMes, $hoy]);
        
        $this->ventasDelMes = $ventasMes[0]->total ?? 0;

        // Ventas del año
        $ventasAno = DB::connection('client_db')->select("
            SELECT COALESCE(SUM(importe * IF(ticomp='NC',-1,1)), 0) as total
            FROM {$this->tablePrefix}ventas_encab 
            WHERE DATE(fecha) >= ? 
            AND DATE(fecha) <= ?
        ", [$inicioAno, $hoy]);
        
        $this->ventasDelAno = $ventasAno[0]->total ?? 0;
    }

    private function cargarDeudas()
    {
        // Deudas a cobrar (cuotas pendientes de clientes)
        $deudasCobrar = DB::connection('client_db')->select("
            SELECT COALESCE(SUM(saldo * IF(tipo='NC',-1,1)), 0) as total
            FROM {$this->tablePrefix}ventas_cuota 
            WHERE saldo > 0
        ");
        
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
        $inicioMes = Carbon::now()->startOfMonth()->format('Y-m-d');
        $hoy = Carbon::now()->format('Y-m-d');

        $productos = DB::connection('client_db')->select("
            SELECT 
                d.codart,
                d.detart,
                SUM(d.cantidad * IF(LEFT(d.nrofac,2) = 'NC',-1,1)) as cantidad_vendida,
                SUM(d.importe * IF(LEFT(d.nrofac,2) = 'NC',-1,1)) as total_vendido
            FROM {$this->tablePrefix}ventas_det d
            WHERE DATE(d.fecha) >= ? 
            AND DATE(d.fecha) <= ?
            GROUP BY d.codart, d.detart
            ORDER BY cantidad_vendida DESC
            LIMIT 10
        ", [$inicioMes, $hoy]);

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
        $inicioMes = Carbon::now()->startOfMonth()->format('Y-m-d');
        $hoy = Carbon::now()->format('Y-m-d');

        // Asumo que tienes un campo departamento en ventas_det o en productos
        try {
            $departamentos = DB::connection('client_db')->select("
                SELECT 
                    COALESCE(de.nombre, 'Sin Departamento') as departamento,
                    COUNT(DISTINCT a.depto) as cantidad_ventas,
                    SUM(d.importe) as total_vendido
                FROM {$this->tablePrefix}ventas_det d
                INNER JOIN {$this->tablePrefix}articu a ON d.codart = a.codigo
                INNER JOIN {$this->tablePrefix}deptos de ON de.codigo = a.depto
                WHERE DATE(d.fecha) >= ? 
                AND DATE(d.fecha) <= ?                
                GROUP BY de.nombre
                ORDER BY total_vendido DESC
                LIMIT 8
            ", [$inicioMes, $hoy]);

            $this->ventasPorDepartamento = collect($departamentos)->map(function ($item) {
                return [
                    'departamento' => $item->departamento,
                    'cantidad_ventas' => $item->cantidad_ventas,
                    'total' => $item->total_vendido
                ];
            })->toArray();
        } catch (\Exception $e) {
            // Si no existe el campo departamento, creamos datos de ejemplo
            $this->ventasPorDepartamento = [
                ['departamento' => 'Electrónicos', 'cantidad_ventas' => 0, 'total' => 0],
                ['departamento' => 'Ropa', 'cantidad_ventas' => 0, 'total' => 0],
                ['departamento' => 'Hogar', 'cantidad_ventas' => 0, 'total' => 0]
            ];
        }
    }

    public function actualizarDatos()
    {
        $this->cargarDatos();
        $this->dispatch('datos-actualizados');
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('panel.inicio');
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