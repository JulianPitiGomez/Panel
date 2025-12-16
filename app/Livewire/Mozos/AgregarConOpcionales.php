<?php

namespace App\Livewire\Mozos;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\MozosService;
use Illuminate\Support\Facades\DB;

class AgregarConOpcionales extends Component
{
    public $numeroMesa;
    public $codigoProducto;
    public $producto;
    public $opcionales = [];
    public $cantidad = 1;
    public $observaciones = '';
    public $seleccionados = []; // Para opcionales checkbox
    public $cantidades = []; // Para opcionales por cantidad
    public $total = 0;
    public $cambiar = false; // Si es solo_unitario
    public $erroresGrupos = []; // Errores de validación por grupo
    public $comensales = 0; // Comensales de la mesa

    protected $mozosService;

    public function boot(MozosService $mozosService)
    {
        $this->mozosService = $mozosService;
    }

    public function mount($mesa, $codigo)
    {
        $this->numeroMesa = $mesa;
        $this->codigoProducto = $codigo;

        $this->producto = $this->mozosService->obtenerArticulo($codigo);
        $this->opcionales = $this->mozosService->obtenerOpcionalesArticulo($codigo);

        // Obtener comensales actuales de la mesa
        $infoMesa = $this->mozosService->obtenerInfoMesa($mesa);
        $this->comensales = $infoMesa->comensales ?? 0;

        // Agrupar opcionales
        $this->opcionales = collect($this->opcionales)->groupBy('idgrupo');

        // Inicializar arrays de seleccionados para cada grupo
        foreach ($this->opcionales as $grupo => $items) {
            $this->seleccionados[$grupo] = [];
        }

        $this->cambiar = $this->producto->solo_unitario ?? false;
        $this->calcularTotal();
    }

    public function incrementarCantidad()
    {
        $this->cantidad++;
        $this->calcularTotal();
    }

    public function decrementarCantidad()
    {
        if ($this->cantidad > 1) {
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

        // Validar grupos en tiempo real
        $this->validarGrupos();
        $this->calcularTotal();
    }

    private function validarGrupos()
    {
        $this->erroresGrupos = [];

        foreach ($this->opcionales as $grupo => $items) {
            $primerItem = $items->first();

            if ($primerItem->obligatorio) {
                if ($primerItem->por_cantidad) {
                    $suma = $this->getSumaCantidadesGrupo($grupo);
                    if ($suma < $primerItem->minimo) {
                        $this->erroresGrupos[$grupo] = "Selecciona mínimo {$primerItem->minimo}";
                    }
                } else {
                    $seleccionados = count($this->seleccionados[$grupo] ?? []);
                    if ($seleccionados < $primerItem->minimo) {
                        $this->erroresGrupos[$grupo] = "Selecciona mínimo {$primerItem->minimo}";
                    }
                }
            }
        }
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

    public function validarYGuardar()
    {
        // Validar opcionales obligatorios
        foreach ($this->opcionales as $grupo => $items) {
            $primerItem = $items->first();

            if ($primerItem->obligatorio) {
                if ($primerItem->por_cantidad) {
                    $suma = $this->getSumaCantidadesGrupo($grupo);
                    if ($suma < $primerItem->minimo) {
                        session()->flash('error', "El grupo '{$primerItem->nomgru}' requiere mínimo {$primerItem->minimo} opciones");
                        return;
                    }
                } else {
                    $seleccionados = count($this->seleccionados[$grupo] ?? []);
                    if ($seleccionados < $primerItem->minimo) {
                        session()->flash('error', "El grupo '{$primerItem->nomgru}' requiere mínimo {$primerItem->minimo} opciones");
                        return;
                    }
                }
            }
        }

        $this->guardar();
    }

    private function guardar()
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

            // Calcular totales
            $neto = $this->total / (1 + $articu->tasa_iva / 100);
            $iva = $this->total - $neto;
            $punitario = $this->total / $this->cantidad;

            // Obtener próximo renglón
            $maxRenglon = DB::connection('client_db')
                            ->table($tablePrefix . 'detalle')
                            ->where('mesa', $this->numeroMesa)
                            ->max('renglon');
            $renglon = $maxRenglon ? $maxRenglon + 1 : 1;
            $esApertura = ($renglon == 1);

            // Si es apertura de mesa, abrir primero
            if ($esApertura) {
                $this->mozosService->abrirMesa($this->numeroMesa, $this->comensales);
                // Recalcular renglón después de abrir (puede haber agregado servicio de mesa)
                $maxRenglon = DB::connection('client_db')
                                ->table($tablePrefix . 'detalle')
                                ->where('mesa', $this->numeroMesa)
                                ->max('renglon');
                $renglon = $maxRenglon ? $maxRenglon + 1 : 1;
            }

            // Construir selección
            $seleccion = $this->construirSeleccion();

            // Truncar nombre si es muy largo (máximo 27 caracteres para dejar espacio a "(w)")
            $nombreTruncado = mb_strlen($this->producto->nombre) > 27
                ? mb_substr($this->producto->nombre, 0, 27)
                : $this->producto->nombre;

            // Insertar en detalle
            DB::connection('client_db')
              ->table($tablePrefix . 'detalle')
              ->insert([
                  'MESA' => $this->numeroMesa,
                  'RENGLON' => $renglon,
                  'CODART' => $this->codigoProducto,
                  'NOMART' => $nombreTruncado . '(w)',
                  'CANTIDAD' => $this->cantidad,
                  'PUNITARIO' => $punitario,
                  'NETO' => $neto,
                  'IVA' => $iva,
                  'TOTAL' => $this->total,
                  'guarnicion' => '',
                  'caracteristicas' => '',
                  'CODIVA' => $articu->codiva,
                  'sabores' => ' ',
                  'estado' => 1,
                  'hora' => DB::raw('CURTIME()'),
                  'seleccion' => $seleccion,
                  'IMPRESA' => false,
                  'OBSERVA' => $this->observaciones
              ]);

            // Insertar opcionales en detalle_opc
            $this->insertarOpcionales($renglon);

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

    private function insertarOpcionales($renglon)
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
                              'orden' => $renglon,
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
                              'orden' => $renglon,
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
        return view('mozos.agregar-con-opcionales');
    }
}
