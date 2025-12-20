<?php

namespace App\Livewire\PanelResto;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class Auditoria extends Component
{
    public $fechaDesde;
    public $fechaHasta;
    public $tipoFiltro = '';
    public $usuarioFiltro = '';
    public $mesaFiltro = '';
    public $pedidoFiltro = '';
    public $comandadoFiltro = ''; // 'CC' para comandado, 'SC' para sin comandar, '' para todos
    public $currentPage = 1;
    public $perPage = 20;

    public $tiposAuditoria = [
        1 => 'APERTURA DE MESA',
        2 => 'CIERRE DE MESA',
        3 => 'INVITACION A MESA',
        4 => 'BORRADO DET. EN MESA',
        5 => 'CANCELACION DE MESA',
        6 => 'APERTURA DE PEDIDO',
        7 => 'CIERRE DE PEDIDO',
        8 => 'BORRADO DET. EN PEDIDO',
        9 => 'CANCELACION DE PEDIDO',
        10 => 'ANULACION DE TICKET DE CAJA',
        11 => 'CIERRE DE TURNO',
        12 => 'EMISION DE PRE-CUENTA',
        13 => 'DESCUENTO APLICADO',
        14 => 'BORRADO C.INTERNO',
        15 => 'INVITACION PEDIDO',
        16 => 'TRANS. MESA',
        17 => 'CAMBIO CANT.',
        18 => 'CANCELACION RECIBO',
    ];

    public function mount()
    {
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
        $this->mesaFiltro = '';
        $this->pedidoFiltro = '';
        $this->comandadoFiltro = '';
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

    private function getConnection()
    {
        $clienteId = session('cliente_id_resto');
        $prefijo = session('prefijo_base_resto');

        if (!$clienteId || !$prefijo) {
            return null;
        }

        return DB::connection($prefijo);
    }

    private function getTableName()
    {
        $clienteId = session('cliente_id_resto');
        $prefijo = session('prefijo_base_resto');

        if (!$clienteId || !$prefijo) {
            return null;
        }

        return $prefijo . '_' . str_pad($clienteId, 6, '0', STR_PAD_LEFT) . 'auditoria';
    }

    private function getPaginationInfo()
    {
        $conn = $this->getConnection();
        $tableName = $this->getTableName();

        if (!$conn || !$tableName) {
            return (object)[
                'total' => 0,
                'per_page' => $this->perPage,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 0,
                'to' => 0,
            ];
        }

        $query = $conn->table($tableName)
            ->whereBetween('FECHA', [$this->fechaDesde, $this->fechaHasta]);

        if ($this->tipoFiltro !== '') {
            $query->where('TIPO', $this->tipoFiltro);
        }

        if ($this->usuarioFiltro !== '') {
            $query->where('USUARIO', 'LIKE', '%' . $this->usuarioFiltro . '%');
        }

        if ($this->mesaFiltro !== '') {
            $query->where('MESA', $this->mesaFiltro);
        }

        if ($this->pedidoFiltro !== '') {
            $query->where('PEDIDO', $this->pedidoFiltro);
        }

        // Filtro de comandado solo para tipos 4 y 8
        if ($this->comandadoFiltro !== '' && in_array($this->tipoFiltro, [4, 8])) {
            if ($this->comandadoFiltro === 'CC') {
                $query->where('DESCRIPCION', 'LIKE', '%CC:%');
            } elseif ($this->comandadoFiltro === 'SC') {
                $query->where('DESCRIPCION', 'LIKE', '%SC:%');
            }
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
        $conn = $this->getConnection();
        $tableName = $this->getTableName();
        $registros = collect();

        if ($conn && $tableName) {
            $query = $conn->table($tableName)
                ->whereBetween('FECHA', [$this->fechaDesde, $this->fechaHasta]);

            if ($this->tipoFiltro !== '') {
                $query->where('TIPO', $this->tipoFiltro);
            }

            if ($this->usuarioFiltro !== '') {
                $query->where('USUARIO', 'LIKE', '%' . $this->usuarioFiltro . '%');
            }

            if ($this->mesaFiltro !== '') {
                $query->where('MESA', $this->mesaFiltro);
            }

            if ($this->pedidoFiltro !== '') {
                $query->where('PEDIDO', $this->pedidoFiltro);
            }

            // Filtro de comandado solo para tipos 4 y 8
            if ($this->comandadoFiltro !== '' && in_array($this->tipoFiltro, [4, 8])) {
                if ($this->comandadoFiltro === 'CC') {
                    $query->where('DESCRIPCION', 'LIKE', '%CC:%');
                } elseif ($this->comandadoFiltro === 'SC') {
                    $query->where('DESCRIPCION', 'LIKE', '%SC:%');
                }
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

        return view('panelresto.auditoria', [
            'registros' => $registros,
            'paginationInfo' => $this->getPaginationInfo(),
        ]);
    }
}
