<?php

namespace App\Livewire\Mozos;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\MozosService;
use Illuminate\Support\Facades\DB;

class ModificarProducto extends Component
{
    public $numeroMesa;
    public $renglon;
    public $codigoProducto;
    public $producto;
    public $detalle;
    public $cantidad;
    public $observaciones = '';
    public $total = 0;

    protected $mozosService;

    public function boot(MozosService $mozosService)
    {
        $this->mozosService = $mozosService;
    }

    public function mount($mesa, $renglon, $codigo)
    {
        $this->numeroMesa = $mesa;
        $this->renglon = $renglon;
        $this->codigoProducto = $codigo;

        $this->producto = $this->mozosService->obtenerArticulo($codigo);

        $tablePrefix = session('client_table_prefix');
        $this->detalle = DB::connection('client_db')
                           ->table($tablePrefix . 'detalle')
                           ->where('mesa', $mesa)
                           ->where('renglon', $renglon)
                           ->first();

        $this->cantidad = $this->detalle->CANTIDAD;
        $this->observaciones = $this->detalle->OBSERVA ?? '';
        $this->calcularTotal();
    }

    public function incrementarCantidad()
    {
        $this->cantidad++;
        $this->calcularTotal();
    }

    public function decrementarCantidad()
    {
        if ($this->cantidad > 0.01) {
            $this->cantidad--;
            $this->calcularTotal();
        }
    }

    public function calcularTotal()
    {
        $this->total = $this->producto->precio * $this->cantidad;
    }

    public function guardar()
    {
        $tablePrefix = session('client_table_prefix');

        DB::connection('client_db')->beginTransaction();
        try {
            // Obtener datos del artículo
            $articu = DB::connection('client_db')
                        ->table($tablePrefix . 'articu as a')
                        ->select('a.IVA as codiva', 'i.tasa as tasa_iva')
                        ->leftJoin($tablePrefix . 'ivas as i', 'i.codigo', '=', 'a.iva')
                        ->where('a.CODIGO', $this->codigoProducto)
                        ->first();

            $neto = $this->total / (1 + $articu->tasa_iva / 100);
            $iva = $this->total - $neto;

            DB::connection('client_db')
              ->table($tablePrefix . 'detalle')
              ->where('mesa', $this->numeroMesa)
              ->where('renglon', $this->renglon)
              ->update([
                  'CANTIDAD' => $this->cantidad,
                  'NETO' => $neto,
                  'IVA' => $iva,
                  'TOTAL' => $this->total,
                  'estado' => 1,
                  'IMPRESA' => false,
                  'OBSERVA' => $this->observaciones
              ]);

            // Auditoría si cambió la cantidad
            if ($this->cantidad != $this->detalle->CANTIDAD) {
                $desc = 'CAMBIO CANT. (W) ';
                $desc .= ($this->detalle->IMPRESA ? 'CC' : 'SC');
                $desc .= ': ' . $this->detalle->CODART . ' ' . $this->detalle->NOMART;
                $desc .= ' DE ' . $this->detalle->CANTIDAD . ' A ' . $this->cantidad;

                DB::connection('client_db')
                  ->table($tablePrefix . 'auditoria')
                  ->insert([
                      'TIPO' => 17,
                      'DESCRIPCION' => $desc,
                      'FECHA' => DB::raw('CURDATE()'),
                      'HORA' => DB::raw('CURTIME()'),
                      'USUARIO' => session('mozo_user'),
                      'MESA' => $this->numeroMesa,
                  ]);
            }

            DB::connection('client_db')->commit();

            $this->redirectRoute('mozos.mesa', ['mesa' => $this->numeroMesa], navigate: true);

        } catch (\Exception $e) {
            DB::connection('client_db')->rollBack();
            session()->flash('error', $e->getMessage());
        }
    }

    #[Layout('layouts.mozos')]
    public function render()
    {
        return view('mozos.modificar-producto');
    }
}
