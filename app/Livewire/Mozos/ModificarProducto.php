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
    public $opcionales = [];
    public $seleccionados = [];
    public $cantidades = [];
    public $opcionalesOriginales = [];
    public $cambiar = false;
    public $erroresGrupos = [];

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
                           ->select('*')
                           ->where('mesa', $mesa)
                           ->where('renglon', $renglon)
                           ->first();

        if (!$this->detalle) {
            session()->flash('error', 'Producto no encontrado');
            $this->redirectRoute('mozos.mesa', ['mesa' => $mesa], navigate: true);
            return;
        }

        $this->cantidad = $this->detalle->CANTIDAD ?? 1;
        $this->observaciones = $this->detalle->observa ?? ($this->detalle->OBSERVA ?? '');

        // Cargar opcionales del producto
        $this->opcionales = $this->mozosService->obtenerOpcionalesArticulo($codigo);
        $this->opcionales = collect($this->opcionales)->groupBy('idgrupo');

        // Inicializar arrays de seleccionados para cada grupo
        foreach ($this->opcionales as $grupo => $items) {
            $this->seleccionados[$grupo] = [];
        }

        // Cargar opcionales ya seleccionados
        $this->cargarOpcionalesSeleccionados();

        $this->cambiar = $this->producto->solo_unitario ?? false;
        $this->calcularTotal();
    }

    private function cargarOpcionalesSeleccionados()
    {
        $tablePrefix = session('client_table_prefix');

        // Obtener los opcionales seleccionados de detalle_opc
        $this->opcionalesOriginales = DB::connection('client_db')
            ->table($tablePrefix . 'detalle_opc')
            ->where('mesa', $this->numeroMesa)
            ->where('orden', $this->renglon)
            ->get();

        foreach ($this->opcionalesOriginales as $opc) {
            // Buscar el opcional en los grupos
            foreach ($this->opcionales as $grupo => $items) {
                $primerItem = $items->first();

                if ($primerItem->por_cantidad) {
                    // Buscar el índice del opcional
                    $index = $items->search(function($item) use ($opc) {
                        return $item->iddet == $opc->id_opcional;
                    });

                    if ($index !== false) {
                        $key = $grupo . '_' . $index;
                        $this->cantidades[$key] = $opc->cantidad;
                    }
                } else {
                    // Checkbox: agregar al array de seleccionados
                    $opcional = $items->firstWhere('iddet', $opc->id_opcional);
                    if ($opcional) {
                        $this->seleccionados[$grupo][] = $opc->id_opcional;
                    }
                }
            }
        }
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

    public function incrementarOpcional($grupo, $index)
    {
        $key = $grupo . '_' . $index;
        if (!isset($this->cantidades[$key])) {
            $this->cantidades[$key] = 0;
        }

        $grupoData = $this->opcionales[$grupo]->first();
        if ($this->getSumaCantidadesGrupo($grupo) < $grupoData->maximo) {
            $this->cantidades[$key]++;
            $this->calcularTotal();
        }
    }

    public function decrementarOpcional($grupo, $index)
    {
        $key = $grupo . '_' . $index;
        if (isset($this->cantidades[$key]) && $this->cantidades[$key] > 0) {
            $this->cantidades[$key]--;
            $this->calcularTotal();
        }
    }

    private function getSumaCantidadesGrupo($grupo)
    {
        $suma = 0;
        foreach ($this->cantidades as $key => $cantidad) {
            if (str_starts_with($key, $grupo . '_')) {
                $suma += $cantidad;
            }
        }
        return $suma;
    }

    public function updated($property)
    {
        // Validar máximo de opcionales checkbox cuando se actualiza un grupo
        if (str_starts_with($property, 'seleccionados.')) {
            $grupo = str_replace('seleccionados.', '', $property);

            if (isset($this->opcionales[$grupo])) {
                $grupoData = $this->opcionales[$grupo]->first();
                $seleccionadosCount = count($this->seleccionados[$grupo] ?? []);

                // Si excede el máximo, quitar el último seleccionado
                if ($seleccionadosCount > $grupoData->maximo) {
                    array_pop($this->seleccionados[$grupo]);
                }
            }
        }

        $this->calcularTotal();
    }

    public function calcularTotal()
    {
        $total = $this->producto->precio * $this->cantidad;

        // Sumar opcionales checkbox
        foreach ($this->seleccionados as $grupo => $ids) {
            if (is_array($ids)) {
                foreach ($ids as $id) {
                    $opcional = $this->opcionales[$grupo]->firstWhere('iddet', $id);
                    if ($opcional && $opcional->precio_opc > 0) {
                        $total += $opcional->precio_opc * $this->cantidad;
                    }
                }
            }
        }

        // Sumar opcionales por cantidad
        foreach ($this->cantidades as $key => $cantidad) {
            if ($cantidad > 0) {
                list($grupo, $index) = explode('_', $key);
                $opcional = $this->opcionales[$grupo]->values()[$index] ?? null;
                if ($opcional && $opcional->precio_opc > 0) {
                    $total += $opcional->precio_opc * $cantidad;
                }
            }
        }

        $this->total = $total;
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
            $punitario = $this->total / $this->cantidad;

            // Construir selección
            $seleccion = $this->construirSeleccion();

            DB::connection('client_db')
              ->table($tablePrefix . 'detalle')
              ->where('mesa', $this->numeroMesa)
              ->where('renglon', $this->renglon)
              ->update([
                  'CANTIDAD' => $this->cantidad,
                  'PUNITARIO' => $punitario,
                  'NETO' => $neto,
                  'IVA' => $iva,
                  'TOTAL' => $this->total,
                  'estado' => 1,
                  'IMPRESA' => false,
                  'OBSERVA' => $this->observaciones,
                  'seleccion' => $seleccion
              ]);

            // Eliminar opcionales anteriores
            DB::connection('client_db')
              ->table($tablePrefix . 'detalle_opc')
              ->where('mesa', $this->numeroMesa)
              ->where('orden', $this->renglon)
              ->delete();

            // Insertar opcionales nuevos
            $this->insertarOpcionales();

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

            // Actualizar puntos
            DB::connection('client_db')
              ->statement("INSERT INTO {$tablePrefix}actualizar (punto, mesa)
                           (SELECT ip, ? FROM {$tablePrefix}punto)", [$this->numeroMesa]);

            DB::connection('client_db')->commit();

            $this->redirectRoute('mozos.mesa', ['mesa' => $this->numeroMesa], navigate: true);

        } catch (\Exception $e) {
            DB::connection('client_db')->rollBack();
            session()->flash('error', $e->getMessage());
        }
    }

    private function construirSeleccion()
    {
        $sel = '';

        foreach ($this->opcionales as $grupo => $items) {
            $primerItem = $items->first();

            if ($primerItem->por_cantidad) {
                foreach ($items->values() as $index => $item) {
                    $key = $grupo . '_' . $index;
                    $cantidad = $this->cantidades[$key] ?? 0;
                    if ($cantidad > 0) {
                        $sel .= $cantidad . ' ' . $item->nomopc . ',';
                    }
                }
            } else {
                $ids = $this->seleccionados[$grupo] ?? [];
                foreach ($ids as $id) {
                    $opcional = $items->firstWhere('iddet', $id);
                    if ($opcional) {
                        $sel .= $opcional->nomopc . ',';
                    }
                }
                $sel .= ' - ';
            }
        }

        return $sel;
    }

    private function insertarOpcionales()
    {
        $tablePrefix = session('client_table_prefix');

        foreach ($this->opcionales as $grupo => $items) {
            $primerItem = $items->first();

            if ($primerItem->por_cantidad) {
                foreach ($items->values() as $index => $item) {
                    $key = $grupo . '_' . $index;
                    $cantidad = $this->cantidades[$key] ?? 0;
                    if ($cantidad > 0) {
                        DB::connection('client_db')
                          ->table($tablePrefix . 'detalle_opc')
                          ->insert([
                              'mesa' => $this->numeroMesa,
                              'orden' => $this->renglon,
                              'codart' => $this->codigoProducto,
                              'id_opcional' => $item->iddet,
                              'precio' => $item->precio_opc,
                              'cantidad' => $cantidad
                          ]);
                    }
                }
            } else {
                $ids = $this->seleccionados[$grupo] ?? [];
                foreach ($ids as $id) {
                    $opcional = $items->firstWhere('iddet', $id);
                    if ($opcional) {
                        DB::connection('client_db')
                          ->table($tablePrefix . 'detalle_opc')
                          ->insert([
                              'mesa' => $this->numeroMesa,
                              'orden' => $this->renglon,
                              'codart' => $this->codigoProducto,
                              'id_opcional' => $id,
                              'precio' => $opcional->precio_opc,
                              'cantidad' => 1
                          ]);
                    }
                }
            }
        }
    }

    #[Layout('layouts.mozos')]
    public function render()
    {
        return view('mozos.modificar-producto');
    }
}
