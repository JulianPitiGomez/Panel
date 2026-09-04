<?php

namespace App\Livewire\Panelresto;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

class EstadoResultado extends Component
{
    public $fechaDesde;
    public $fechaHasta;
    public $tabActiva = 'devengado';

    public $ventas = [
        'fiscal' => 0, 'no_fiscal' => 0,
        'neto' => 0, 'iva' => 0, 'iibb' => 0, 'sobretasa' => 0, 'importe' => 0,
    ];
    public $compras = [
        'fiscal' => 0, 'no_fiscal' => 0,
        'nograbado' => 0, 'neto' => 0, 'iva' => 0, 'importe' => 0,
    ];
    public $gastos = [
        'fiscal' => 0, 'no_fiscal' => 0,
        'nograbado' => 0, 'neto' => 0, 'iva' => 0, 'importe' => 0,
    ];
    public $sueldosLiq = 0;
    public $comprasPorCuenta = [];
    public $gastosPorCuenta = [];

    // La separación compra/gasto sale de plancont.TIPO ('C' = compra, 'G' = gasto).
    // Si el plan de cuentas del cliente no tiene ese campo, todo se imputa como compra.
    public $separaGastos = false;

    public $cobros = 0;
    public $pagosProv = 0;
    public $sueldosPag = 0;
    public $cobrosPorForma = [];
    public $pagosPorForma = [];

    public $depositosIngresos = 0;
    public $depositosRetiros = 0;
    public $retirosIngresos = 0;
    public $retirosSalidas = 0;

    private $tablePrefix;
    private $plancontTipo = null;

    public function mount()
    {
        $this->setupClientDatabase();
        $this->tablePrefix = session('client_table_prefix', 're_000001');
        $this->fechaDesde = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = Carbon::now()->format('Y-m-d');
        $this->cargarDatos();
    }

    public function cargarDatos()
    {
        $this->setupClientDatabase();
        $this->cargarVentas();
        $this->cargarCompras();
        $this->cargarComprasPorCuenta();
        $this->cargarSueldosLiq();
        $this->cargarCobros();
        $this->cargarPagosProv();
        $this->cargarSueldosPag();
        $this->cargarFormasPago();
        $this->cargarMovimientosBancarios();
    }

    public function updatedFechaDesde() { $this->cargarDatos(); }
    public function updatedFechaHasta() { $this->cargarDatos(); }
    public function setTab($tab) { $this->tabActiva = $tab; }

    public function volverInicio()
    {
        $this->dispatch('panel-navigate', page: 'inicio');
    }

    /**
     * Indica si el plan de cuentas tiene el campo TIPO ('C' compra / 'G' gasto).
     * Los planes viejos no lo tienen: en ese caso no se separa nada.
     */
    private function tienePlancontTipo(): bool
    {
        if ($this->plancontTipo !== null) {
            return $this->plancontTipo;
        }

        try {
            $cols = DB::connection('client_db')->select("
                SHOW COLUMNS FROM {$this->tablePrefix}plancont LIKE 'tipo'
            ");
            $this->plancontTipo = count($cols) > 0;
        } catch (\Exception $e) {
            $this->plancontTipo = false;
        }

        return $this->plancontTipo;
    }

    /** Expresión SQL que clasifica cada comprobante de compra en 'C' o 'G'. */
    private function expresionTipoCuenta(): string
    {
        return $this->tienePlancontTipo()
            ? "CASE WHEN UPPER(COALESCE(p.TIPO, 'C')) = 'G' THEN 'G' ELSE 'C' END"
            : "'C'";
    }

    private function cargarVentas()
    {
        try {
            // Totales y otros impuestos desde ventas_encab (todas las letras, NCs con signo)
            $r = DB::connection('client_db')->selectOne("
                SELECT
                    COALESCE(SUM(IF(cae IS NOT NULL, IIBB * IF(TICOMP='NC',-1,1), 0)), 0) as iibb,
                    COALESCE(SUM(IF(cae IS NOT NULL, SOBRETASA * IF(TICOMP='NC',-1,1), 0)), 0) as sobretasa,
                    COALESCE(SUM(IMPORTE * IF(TICOMP='NC',-1,1)), 0) as importe,
                    COALESCE(SUM(IF(cae IS NULL, IMPORTE * IF(TICOMP='NC',-1,1), 0)), 0) as no_fiscal,
                    COALESCE(SUM(IF(cae IS NOT NULL, IMPORTE * IF(TICOMP='NC',-1,1), 0)), 0) as fiscal
                FROM {$this->tablePrefix}ventas_encab
                WHERE FECHA BETWEEN ? AND ?
            ", [$this->fechaDesde, $this->fechaHasta]);

            // NETO e IVA fiscales tomados del libro IVA ventas (ventivadet)
            // Filtra por FECMOD (fecha de imputación contable) y excluye letra X
            $iva = DB::connection('client_db')->selectOne("
                SELECT
                    COALESCE(SUM(vd.NETO * IF(ve.TICOMP='NC',-1,1)), 0) as neto,
                    COALESCE(SUM(vd.IVA * IF(ve.TICOMP='NC',-1,1)), 0) as iva
                FROM {$this->tablePrefix}ventas_encab ve
                INNER JOIN {$this->tablePrefix}ventivadet vd
                    ON vd.TIPOCOMP = ve.TICOMP AND vd.LETRA = ve.LETRA AND vd.NUMFAC = ve.NUMCOMP
                WHERE ve.cae IS NOT NULL
                  AND ve.LETRA <> 'X'
                  AND ve.FECMOD BETWEEN ? AND ?
            ", [$this->fechaDesde, $this->fechaHasta]);

            $this->ventas = [
                'neto' => (float)$iva->neto, 'iva' => (float)$iva->iva,
                'iibb' => (float)$r->iibb, 'sobretasa' => (float)$r->sobretasa,
                'importe' => (float)$r->importe,
                'fiscal' => (float)$r->fiscal, 'no_fiscal' => (float)$r->no_fiscal,
            ];
        } catch (\Exception $e) {
            $this->ventas = array_fill_keys(array_keys($this->ventas), 0);
        }
    }

    private function cargarCompras()
    {
        $vacio = [
            'fiscal' => 0, 'no_fiscal' => 0,
            'nograbado' => 0, 'neto' => 0, 'iva' => 0, 'importe' => 0,
        ];
        $this->compras = $vacio;
        $this->gastos = $vacio;
        $this->separaGastos = false;

        try {
            $tipo = $this->expresionTipoCuenta();
            $join = $this->tienePlancontTipo()
                ? "LEFT JOIN {$this->tablePrefix}plancont p ON p.CODIGO = c.CODCUE"
                : "";

            $rows = DB::connection('client_db')->select("
                SELECT
                    {$tipo} as tipo,
                    COALESCE(SUM(c.NOGRABADO * IF(c.TIPOCOMP='NC',-1,1)), 0) as nograbado,
                    COALESCE(SUM(c.NETO * IF(c.TIPOCOMP='NC',-1,1)), 0) as neto,
                    COALESCE(SUM(IF(c.IMPUTAIVA=1, c.IVA * IF(c.TIPOCOMP='NC',-1,1), 0)), 0) as iva,
                    COALESCE(SUM(c.IMPORTE * IF(c.TIPOCOMP='NC',-1,1)), 0) as importe,
                    COALESCE(SUM(IF(c.IMPUTAIVA=1, c.IMPORTE * IF(c.TIPOCOMP='NC',-1,1), 0)), 0) as fiscal,
                    COALESCE(SUM(IF(c.IMPUTAIVA=0, c.IMPORTE * IF(c.TIPOCOMP='NC',-1,1), 0)), 0) as no_fiscal
                FROM {$this->tablePrefix}compras c
                {$join}
                WHERE c.FECFAC BETWEEN ? AND ?
                GROUP BY tipo
            ", [$this->fechaDesde, $this->fechaHasta]);

            foreach ($rows as $r) {
                $bloque = [
                    'nograbado' => (float)$r->nograbado, 'neto' => (float)$r->neto,
                    'iva' => (float)$r->iva,
                    'importe' => (float)$r->importe,
                    'fiscal' => (float)$r->fiscal, 'no_fiscal' => (float)$r->no_fiscal,
                ];

                if ($r->tipo === 'G') {
                    $this->gastos = $bloque;
                } else {
                    $this->compras = $bloque;
                }
            }

            $this->separaGastos = $this->tienePlancontTipo();
        } catch (\Exception $e) {
            $this->compras = $vacio;
            $this->gastos = $vacio;
            $this->separaGastos = false;
        }
    }

    private function cargarComprasPorCuenta()
    {
        $this->comprasPorCuenta = [];
        $this->gastosPorCuenta = [];

        try {
            $tipo = $this->expresionTipoCuenta();

            $rows = DB::connection('client_db')->select("
                SELECT
                    c.CODCUE as codigo,
                    COALESCE(p.NOMBRE, 'Sin clasificar') as nombre,
                    {$tipo} as tipo,
                    COALESCE(SUM(c.IMPORTE * IF(c.TIPOCOMP='NC',-1,1)), 0) as importe,
                    COALESCE(SUM(c.NETO * IF(c.TIPOCOMP='NC',-1,1)), 0) as neto
                FROM {$this->tablePrefix}compras c
                LEFT JOIN {$this->tablePrefix}plancont p ON p.CODIGO = c.CODCUE
                WHERE c.FECFAC BETWEEN ? AND ?
                GROUP BY c.CODCUE, p.NOMBRE, tipo
                ORDER BY importe DESC
            ", [$this->fechaDesde, $this->fechaHasta]);

            foreach ($rows as $r) {
                $fila = [
                    'codigo' => $r->codigo,
                    'nombre' => $r->nombre,
                    'importe' => (float)$r->importe,
                    'neto' => (float)$r->neto,
                ];

                if ($r->tipo === 'G') {
                    $this->gastosPorCuenta[] = $fila;
                } else {
                    $this->comprasPorCuenta[] = $fila;
                }
            }
        } catch (\Exception $e) {
            $this->comprasPorCuenta = [];
            $this->gastosPorCuenta = [];
        }
    }

    private function cargarSueldosLiq()
    {
        try {
            $r = DB::connection('client_db')->selectOne("
                SELECT COALESCE(SUM(TOTAL), 0) as total
                FROM {$this->tablePrefix}emp_liqsueldo
                WHERE FECHA BETWEEN ? AND ?
            ", [$this->fechaDesde, $this->fechaHasta]);
            $this->sueldosLiq = (float)$r->total;
        } catch (\Exception $e) {
            $this->sueldosLiq = 0;
        }
    }

    private function cargarCobros()
    {
        try {
            $r = DB::connection('client_db')->selectOne("
                SELECT COALESCE(SUM(TOTAL), 0) as total
                FROM {$this->tablePrefix}pagos
                WHERE FECHA BETWEEN ? AND ?
            ", [$this->fechaDesde, $this->fechaHasta]);
            $this->cobros = (float)$r->total;
        } catch (\Exception $e) {
            $this->cobros = 0;
        }
    }

    private function cargarPagosProv()
    {
        try {
            $r = DB::connection('client_db')->selectOne("
                SELECT COALESCE(SUM(TOTAL), 0) as total
                FROM {$this->tablePrefix}ordpag
                WHERE FECHA BETWEEN ? AND ?
            ", [$this->fechaDesde, $this->fechaHasta]);
            $this->pagosProv = (float)$r->total;
        } catch (\Exception $e) {
            $this->pagosProv = 0;
        }
    }

    private function cargarSueldosPag()
    {
        try {
            $r = DB::connection('client_db')->selectOne("
                SELECT COALESCE(SUM(IMPORTE), 0) as total
                FROM {$this->tablePrefix}emp_pagsueldo
                WHERE FECHA BETWEEN ? AND ?
            ", [$this->fechaDesde, $this->fechaHasta]);
            $this->sueldosPag = (float)$r->total;
        } catch (\Exception $e) {
            $this->sueldosPag = 0;
        }
    }

    private function cargarFormasPago()
    {
        try {
            $rows = DB::connection('client_db')->select("
                SELECT
                    fp.codigo,
                    COALESCE(fp.nombre, 'Sin clasificar') as nombre,
                    COALESCE(SUM(pc.IMPORTE), 0) as total,
                    COUNT(*) as cantidad
                FROM {$this->tablePrefix}pagcon pc
                INNER JOIN {$this->tablePrefix}pagos p ON pc.NUMERO = p.NUMERO
                LEFT JOIN {$this->tablePrefix}forpag fp ON pc.CODCON = fp.codigo
                WHERE p.FECHA BETWEEN ? AND ?
                GROUP BY fp.codigo, fp.nombre
                ORDER BY total DESC
            ", [$this->fechaDesde, $this->fechaHasta]);

            $this->cobrosPorForma = collect($rows)->map(fn($r) => [
                'codigo' => $r->codigo,
                'nombre' => $r->nombre,
                'total' => (float)$r->total,
                'cantidad' => (int)$r->cantidad,
            ])->toArray();
        } catch (\Exception $e) {
            $this->cobrosPorForma = [];
        }

        try {
            $rows = DB::connection('client_db')->select("
                SELECT
                    fp.codigo,
                    COALESCE(fp.nombre, 'Sin clasificar') as nombre,
                    COALESCE(SUM(oc.IMPORTE), 0) as total,
                    COUNT(*) as cantidad
                FROM {$this->tablePrefix}ordcon oc
                INNER JOIN {$this->tablePrefix}ordpag o ON oc.NUMERO = o.NUMERO
                LEFT JOIN {$this->tablePrefix}forpag fp ON oc.CODCON = fp.codigo
                WHERE o.FECHA BETWEEN ? AND ?
                GROUP BY fp.codigo, fp.nombre
                ORDER BY total DESC
            ", [$this->fechaDesde, $this->fechaHasta]);

            $this->pagosPorForma = collect($rows)->map(fn($r) => [
                'codigo' => $r->codigo,
                'nombre' => $r->nombre,
                'total' => (float)$r->total,
                'cantidad' => (int)$r->cantidad,
            ])->toArray();
        } catch (\Exception $e) {
            $this->pagosPorForma = [];
        }
    }

    private function cargarMovimientosBancarios()
    {
        try {
            $rows = DB::connection('client_db')->select("
                SELECT TIPO, COALESCE(SUM(IMPORTE), 0) as total
                FROM {$this->tablePrefix}deposito
                WHERE FECEMI BETWEEN ? AND ?
                GROUP BY TIPO
            ", [$this->fechaDesde, $this->fechaHasta]);

            $this->depositosIngresos = 0;
            $this->depositosRetiros = 0;
            foreach ($rows as $r) {
                if ($r->TIPO === 'D') $this->depositosIngresos = (float)$r->total;
                if ($r->TIPO === 'R') $this->depositosRetiros = (float)$r->total;
            }
        } catch (\Exception $e) {
            $this->depositosIngresos = 0;
            $this->depositosRetiros = 0;
        }

        try {
            $rows = DB::connection('client_db')->select("
                SELECT TIPO, COALESCE(SUM(IMPORTE), 0) as total
                FROM {$this->tablePrefix}retiros
                WHERE FECHA BETWEEN ? AND ?
                GROUP BY TIPO
            ", [$this->fechaDesde, $this->fechaHasta]);

            $this->retirosIngresos = 0;
            $this->retirosSalidas = 0;
            foreach ($rows as $r) {
                if ($r->TIPO === 'I') $this->retirosIngresos = (float)$r->total;
                if ($r->TIPO === 'R') $this->retirosSalidas = (float)$r->total;
            }
        } catch (\Exception $e) {
            $this->retirosIngresos = 0;
            $this->retirosSalidas = 0;
        }
    }

    /** Total facturado por proveedores: compras de mercadería + gastos. */
    public function getEgresosFacturadosProperty()
    {
        return $this->compras['importe'] + $this->gastos['importe'];
    }

    public function getResultadoBrutoProperty()
    {
        return $this->ventas['importe'] - $this->compras['importe'];
    }

    public function getResultadoOperativoProperty()
    {
        return $this->resultadoBruto - $this->gastos['importe'] - $this->sueldosLiq;
    }

    public function getMargenBrutoProperty()
    {
        $base = $this->ventas['importe'];
        return $base > 0 ? round(($this->resultadoBruto / $base) * 100, 2) : 0;
    }

    public function getMargenOperativoProperty()
    {
        $base = $this->ventas['importe'];
        return $base > 0 ? round(($this->resultadoOperativo / $base) * 100, 2) : 0;
    }

    public function getSaldoIvaProperty()
    {
        return $this->ventas['iva'] - $this->compras['iva'] - $this->gastos['iva'];
    }

    public function getFlujoNetoProperty()
    {
        return $this->cobros - $this->pagosProv - $this->sueldosPag;
    }

    private function getExportData(): array
    {
        $this->setupClientDatabase();
        $this->cargarDatos();

        return [
            'fechaDesde' => $this->fechaDesde,
            'fechaHasta' => $this->fechaHasta,
            'cliente' => session('cliente_nombre', ''),
            'ventas' => $this->ventas,
            'compras' => $this->compras,
            'gastos' => $this->gastos,
            'separaGastos' => $this->separaGastos,
            'sueldosLiq' => $this->sueldosLiq,
            'comprasPorCuenta' => $this->comprasPorCuenta,
            'gastosPorCuenta' => $this->gastosPorCuenta,
            'cobros' => $this->cobros,
            'pagosProv' => $this->pagosProv,
            'sueldosPag' => $this->sueldosPag,
            'cobrosPorForma' => $this->cobrosPorForma,
            'pagosPorForma' => $this->pagosPorForma,
            'depositosIngresos' => $this->depositosIngresos,
            'depositosRetiros' => $this->depositosRetiros,
            'retirosIngresos' => $this->retirosIngresos,
            'retirosSalidas' => $this->retirosSalidas,
            'egresosFacturados' => $this->egresosFacturados,
            'resultadoBruto' => $this->resultadoBruto,
            'resultadoOperativo' => $this->resultadoOperativo,
            'margenBruto' => $this->margenBruto,
            'margenOperativo' => $this->margenOperativo,
            'saldoIva' => $this->saldoIva,
            'flujoNeto' => $this->flujoNeto,
        ];
    }

    public function exportarPdf()
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('panel.estado-resultado-pdf', $this->getExportData())
            ->setPaper('a4', 'portrait');

        $filename = 'estado-resultado_' . $this->fechaDesde . '_' . $this->fechaHasta . '.pdf';

        return response()->streamDownload(fn() => print($pdf->output()), $filename);
    }

    public function exportarExcel()
    {
        $html = view('panel.estado-resultado-excel', $this->getExportData())->render();
        $filename = 'estado-resultado_' . $this->fechaDesde . '_' . $this->fechaHasta . '.xls';

        return response()->streamDownload(fn() => print($html), $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('panelresto.estado-resultado');
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
                $this->plancontTipo = null;
            }
        }
    }
}
