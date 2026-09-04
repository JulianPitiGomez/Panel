<?php

namespace App\Livewire\Panelresto;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class VerVentas extends Component
{
    public $fechaDesde;
    public $fechaHasta;
    public $origenFiltro = ''; // '', 'SALON', 'MOSTRADOR', 'DELIVERY', 'POS', 'OTRAS'
    public $ventaExpandida = null; // NROFAC de la venta expandida
    public $currentPage = 1;
    public $perPage = 20;

    private $tablePrefix;

    public function mount()
    {
        $this->setupClientDatabase();

        // Fechas por defecto: último mes
        $this->fechaHasta = date('Y-m-d');
        $this->fechaDesde = date('Y-m-d', strtotime('-1 month'));
    }

    public function updatedFechaDesde()
    {
        $this->currentPage = 1;
    }

    public function updatedFechaHasta()
    {
        $this->currentPage = 1;
    }

    public function updatedOrigenFiltro()
    {
        $this->currentPage = 1;
    }

    public function aplicarFiltros()
    {
        $this->currentPage = 1;
    }

    public function limpiarFiltros()
    {
        $this->fechaDesde = date('Y-m-d', strtotime('-1 month'));
        $this->fechaHasta = date('Y-m-d');
        $this->origenFiltro = '';
        $this->currentPage = 1;
    }

    public function toggleDetalle($nrofac)
    {
        $this->ventaExpandida = $this->ventaExpandida === $nrofac ? null : $nrofac;
    }

    public function previousPage()
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
        }
    }

    public function nextPage()
    {
        $paginationInfo = $this->getPaginationInfo();
        if ($this->currentPage < $paginationInfo->last_page) {
            $this->currentPage++;
        }
    }

    public function gotoPage($page)
    {
        $this->currentPage = $page;
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

    private function getOrigenVenta($observa)
    {
        if (empty($observa)) {
            return 'OTRAS';
        }

        if (str_starts_with($observa, 'VENTA MESAS')) {
            return 'SALON';
        } elseif (str_starts_with($observa, 'MOSTRADOR')) {
            return 'MOSTRADOR';
        } elseif (str_starts_with($observa, 'DELIVERY')) {
            return 'DELIVERY';
        } elseif (str_starts_with($observa, 'PUNTO DE VENTA')) {
            return 'POS';
        }

        return 'OTRAS';
    }

    private function aplicarFiltroOrigen($query)
    {
        if ($this->origenFiltro === '') {
            return $query;
        }

        switch ($this->origenFiltro) {
            case 'SALON':
                $query->where('OBSERVA', 'LIKE', 'VENTA MESAS%');
                break;
            case 'MOSTRADOR':
                $query->where('OBSERVA', 'LIKE', 'MOSTRADOR%');
                break;
            case 'DELIVERY':
                $query->where('OBSERVA', 'LIKE', 'DELIVERY%');
                break;
            case 'POS':
                $query->where('OBSERVA', 'LIKE', 'PUNTO DE VENTA%');
                break;
            case 'OTRAS':
                $query->where(function($q) {
                    $q->whereNull('OBSERVA')
                      ->orWhere('OBSERVA', '')
                      ->orWhere(function($q2) {
                          $q2->where('OBSERVA', 'NOT LIKE', 'VENTA MESAS%')
                             ->where('OBSERVA', 'NOT LIKE', 'MOSTRADOR%')
                             ->where('OBSERVA', 'NOT LIKE', 'DELIVERY%')
                             ->where('OBSERVA', 'NOT LIKE', 'PUNTO DE VENTA%');
                      });
                });
                break;
        }

        return $query;
    }

    private function getTotalesPorOrigen()
    {
        $this->setupClientDatabase();

        if (!$this->tablePrefix) {
            return [
                'SALON' => 0,
                'MOSTRADOR' => 0,
                'DELIVERY' => 0,
                'POS' => 0,
                'OTRAS' => 0,
            ];
        }

        $query = DB::connection('client_db')->table($this->tablePrefix . 'ventas_encab')
            ->whereBetween('FECHA', [$this->fechaDesde, $this->fechaHasta]);

        // Aplicar filtro de origen si está seleccionado
        $query = $this->aplicarFiltroOrigen($query);

        $ventas = $query->select('OBSERVA', 'IMPORTE', 'TICOMP')->get();

        $totales = [
            'SALON' => 0,
            'MOSTRADOR' => 0,
            'DELIVERY' => 0,
            'POS' => 0,
            'OTRAS' => 0,
        ];

        foreach ($ventas as $venta) {
            $origen = $this->getOrigenVenta($venta->OBSERVA);
            $importe = $venta->TICOMP === 'NC' ? -$venta->IMPORTE : $venta->IMPORTE;
            $totales[$origen] += $importe;
        }

        return $totales;
    }

    private function getTotalGeneral()
    {
        $totalesPorOrigen = $this->getTotalesPorOrigen();
        return array_sum($totalesPorOrigen);
    }

    private function getFormasPagoTotales()
    {
        $this->setupClientDatabase();

        if (!$this->tablePrefix) {
            return [];
        }

        // Formas de facturación (con qué forma de pago se cerraron las ventas)
        // Agrupado por el rango de fechas seleccionado
        $formas = DB::connection('client_db')->select("
            SELECT
                COALESCE(NULLIF(TRIM(observa), ''), 'Sin especificar') as forma,
                SUM(importe * IF(ticomp='NC',-1,1)) as total,
                COUNT(*) as cantidad
            FROM {$this->tablePrefix}concfact
            WHERE fecha BETWEEN ? AND ?
            GROUP BY forma
            ORDER BY total DESC
        ", [$this->fechaDesde, $this->fechaHasta]);

        return collect($formas)->map(function ($item) {
            return [
                'forma' => $item->forma,
                'total' => round($item->total, 2),
                'cantidad' => $item->cantidad,
            ];
        })->toArray();
    }

    private function getPaginationInfo()
    {
        $this->setupClientDatabase();

        if (!$this->tablePrefix) {
            return (object)[
                'total' => 0,
                'per_page' => $this->perPage,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 0,
                'to' => 0,
            ];
        }

        $query = DB::connection('client_db')->table($this->tablePrefix . 'ventas_encab')
            ->whereBetween('FECHA', [$this->fechaDesde, $this->fechaHasta]);

        $query = $this->aplicarFiltroOrigen($query);

        $total = $query->count();
        $lastPage = ceil($total / $this->perPage);
        $from = $total > 0 ? (($this->currentPage - 1) * $this->perPage) + 1 : 0;
        $to = min($this->currentPage * $this->perPage, $total);

        return (object)[
            'total' => $total,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'last_page' => $lastPage,
            'from' => $from,
            'to' => $to,
        ];
    }

    public function getDetalleVenta($nrofac)
    {
        $this->setupClientDatabase();

        if (!$this->tablePrefix) {
            return collect();
        }

        return DB::connection('client_db')->table($this->tablePrefix . 'ventas_det')
            ->where('NROFAC', $nrofac)
            ->select('CODART', 'DETART', 'CANTIDAD', 'PUNIT', 'IMPORTE')
            ->get();
    }

    public function getFormasPago($ticomp, $letra, $numcomp)
    {
        $this->setupClientDatabase();

        if (!$this->tablePrefix) {
            return collect();
        }

        return DB::connection('client_db')->table($this->tablePrefix . 'concfact')
            ->where('ticomp', $ticomp)
            ->where('letra', $letra)
            ->where('numcomp', $numcomp)
            ->select('observa', 'importe')
            ->get();
    }

    public function render()
    {
        $this->setupClientDatabase();
        $ventas = collect();
        $totalesPorOrigen = $this->getTotalesPorOrigen();

        if ($this->tablePrefix) {
            $query = DB::connection('client_db')->table($this->tablePrefix . 'ventas_encab')
                ->whereBetween('FECHA', [$this->fechaDesde, $this->fechaHasta]);

            $query = $this->aplicarFiltroOrigen($query);

            $ventas = $query
                ->orderBy('FECHA', 'DESC')
                ->orderBy('HORA', 'DESC')
                ->skip(($this->currentPage - 1) * $this->perPage)
                ->take($this->perPage)
                ->get()
                ->map(function ($venta) {
                    $venta->fecha_formateada = date('d/m/Y', strtotime($venta->FECHA));
                    $venta->hora_formateada = date('H:i:s', strtotime($venta->HORA));
                    $ticompLabels = [
                        'FC' => 'Factura',
                        'FR' => 'Fact. Remito',
                        'ND' => 'Nota Débito',
                        'NC' => 'Nota Crédito',
                    ];
                    $ticompLabel = $ticompLabels[$venta->TICOMP] ?? $venta->TICOMP;
                    $venta->comprobante = $ticompLabel . ' ' . $venta->LETRA . $venta->NUMCOMP;
                    $venta->nrofac = $venta->TICOMP . $venta->LETRA . $venta->NUMCOMP;
                    $venta->importe_calculado = $venta->TICOMP === 'NC' ? -$venta->IMPORTE : $venta->IMPORTE;
                    $venta->factura_aplicada = $venta->TICOMP === 'NC' && !empty($venta->OBSERVA) ? $venta->OBSERVA : null;
                    $venta->origen = $this->getOrigenVenta($venta->OBSERVA);

                    // Resolver nombre del mozo para ventas de Salón
                    $venta->nombre_mozo = null;
                    if ($venta->origen === 'SALON' && !empty($venta->VENDEDOR)) {
                        $venta->nombre_mozo = DB::connection('client_db')
                            ->table($this->tablePrefix . 'mozos')
                            ->where('codigo', $venta->VENDEDOR)
                            ->value('nombre');
                    }

                    return $venta;
                });
        }

        return view('panelresto.ver-ventas', [
            'ventas' => $ventas,
            'totalesPorOrigen' => $totalesPorOrigen,
            'totalGeneral' => $this->getTotalGeneral(),
            'formasPago' => $this->getFormasPagoTotales(),
            'paginationInfo' => $this->getPaginationInfo(),
        ]);
    }
}
