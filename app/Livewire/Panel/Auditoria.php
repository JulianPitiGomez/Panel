<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class Auditoria extends Component
{
    public $fechaDesde;
    public $fechaHasta;
    public $tipoFiltro = '';
    public $usuarioFiltro = '';
    public $currentPage = 1;
    public $perPage = 20;

    private $tablePrefix;

    public $tiposAuditoria = [
        1 => 'ELIMINACION',
        2 => 'MODIFICACION',
        3 => 'BORRADO EN VENTA',
        4 => 'CANCELACION DE VENTA',
        5 => 'CIERRE DE TURNO',
        6 => 'DESCUENTO',
    ];

    public function mount()
    {
        $this->setupClientDatabase();

        // Fechas por defecto: último mes
        $this->fechaHasta = date('Y-m-d');
        $this->fechaDesde = date('Y-m-d', strtotime('-1 month'));
    }

    public function aplicarFiltros()
    {
        $this->currentPage = 1;
    }

    public function limpiarFiltros()
    {
        $this->fechaDesde = date('Y-m-d', strtotime('-1 month'));
        $this->fechaHasta = date('Y-m-d');
        $this->tipoFiltro = '';
        $this->usuarioFiltro = '';
        $this->currentPage = 1;
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

        $query = DB::connection('client_db')->table($this->tablePrefix . 'auditoria')
            ->whereBetween('FECHA', [$this->fechaDesde, $this->fechaHasta]);

        if ($this->tipoFiltro !== '') {
            $query->where('TIPO', $this->tipoFiltro);
        }

        if ($this->usuarioFiltro !== '') {
            $query->where('USUARIO', 'LIKE', '%' . $this->usuarioFiltro . '%');
        }

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

    public function render()
    {
        $this->setupClientDatabase();
        $registros = collect();

        if ($this->tablePrefix) {
            $query = DB::connection('client_db')->table($this->tablePrefix . 'auditoria')
                ->whereBetween('FECHA', [$this->fechaDesde, $this->fechaHasta]);

            if ($this->tipoFiltro !== '') {
                $query->where('TIPO', $this->tipoFiltro);
            }

            if ($this->usuarioFiltro !== '') {
                $query->where('USUARIO', 'LIKE', '%' . $this->usuarioFiltro . '%');
            }

            $registros = $query
                ->orderBy('FECHA', 'DESC')
                ->orderBy('HORA', 'DESC')
                ->skip(($this->currentPage - 1) * $this->perPage)
                ->take($this->perPage)
                ->get()
                ->map(function ($registro) {
                    $registro->fecha_formateada = date('d/m/Y', strtotime($registro->FECHA));
                    $registro->hora_formateada = date('H:i:s', strtotime($registro->HORA));
                    $registro->tipo_descripcion = $this->tiposAuditoria[$registro->TIPO] ?? 'DESCONOCIDO';
                    return $registro;
                });
        }

        return view('panel.auditoria', [
            'registros' => $registros,
            'paginationInfo' => $this->getPaginationInfo(),
        ]);
    }
}
