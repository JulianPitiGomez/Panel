<?php

namespace App\Livewire\Mozos;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\MozosService;

class DetalleMesa extends Component
{
    public $numeroMesa;
    public $mesa;
    public $detalle = [];
    public $comensales = 0;
    public $total = 0;

    public $error = false;
    public $mensajeError = '';

    public $busqueda = '';
    public $productos = [];
    public $mostrarBusqueda = false;
    public $requiereComensales = false;
    public $cobraCubierto = false;
    public $soloServicioMesa = false;

    protected $mozosService;

    public function boot(MozosService $mozosService)
    {
        $this->mozosService = $mozosService;
    }

    public function mount($mesa)
    {
        $this->numeroMesa = $mesa;
        $this->cargarDatosMesa();
        $this->validarMesa();
        $this->verificarParametros();
    }

    private function verificarParametros()
    {
        $param = $this->mozosService->obtenerParametros();
        $this->cobraCubierto = ($param && $param->precioxcub > 0);
        $this->requiereComensales = ($param && $param->pedircomen && $param->precioxcub > 0);
    }

    private function cargarDatosMesa()
    {
        $this->mesa = $this->mozosService->obtenerInfoMesa($this->numeroMesa);
        $this->detalle = $this->mozosService->obtenerDetalleMesa($this->numeroMesa);
        $this->comensales = $this->mesa->comensales ?? 0;
        $this->calcularTotal();
        $this->verificarParametros();

        // Verificar si solo hay servicio de mesa
        $this->soloServicioMesa = false;
        if (count($this->detalle) > 0) {
            $soloServicio = true;
            foreach ($this->detalle as $item) {
                if (!str_starts_with(strtoupper($item->NOMART), 'SERVICIO DE MESA')) {
                    $soloServicio = false;
                    break;
                }
            }
            $this->soloServicioMesa = $soloServicio;
        }
    }

    private function validarMesa()
    {
        // Validar turno abierto
        if (!$this->mozosService->hayTurnoAbierto()) {
            $this->error = true;
            $this->mensajeError = 'No hay turno abierto. Abrir el turno desde el sistema!!';
            return;
        }

        // Validar mesa unificada
        if ($this->mesa->unificada > 0) {
            $this->error = true;
            $this->mensajeError = "Esta mesa está unificada, por favor ingrese a la mesa N° " . $this->mesa->unificada;
            return;
        }

        // Validar mesa en uso
        if ($this->mesa->usando > 0) {
            $this->error = true;
            $this->mensajeError = "La mesa está siendo utilizada por otra terminal!";
            return;
        }
    }

    private function calcularTotal()
    {
        $this->total = collect($this->detalle)->sum('TOTAL');
    }

    public function updatedBusqueda()
    {
        if (strlen($this->busqueda) >= 2) {
            $this->productos = $this->mozosService->buscarArticulos($this->busqueda);
            $this->mostrarBusqueda = true;
        } else {
            $this->productos = [];
            $this->mostrarBusqueda = false;
        }
    }

    public function seleccionarProducto($codigo)
    {
        // Verificar si tiene opcionales
        if ($this->mozosService->tieneOpcionales($codigo)) {
            $this->redirectRoute('mozos.agregar-opcionales', [
                'mesa' => $this->numeroMesa,
                'codigo' => $codigo
            ], navigate: true);
            return;
        }

        // Si no tiene opcionales, agregar directamente
        try {
            $this->mozosService->agregarProducto([
                'mesa' => $this->numeroMesa,
                'codigo' => $codigo,
                'cantidad' => 1,
                'comensales' => $this->comensales,
                'caracteristicas' => ''
            ]);

            $this->busqueda = '';
            $this->productos = [];
            $this->mostrarBusqueda = false;
            $this->cargarDatosMesa();

            session()->flash('success', 'Producto agregado correctamente');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function eliminarProducto($renglon)
    {
        try {
            $this->mozosService->eliminarProducto($this->numeroMesa, $renglon);
            $this->cargarDatosMesa();
            session()->flash('success', 'Producto eliminado correctamente');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function modificarProducto($renglon, $codigo)
    {
        $this->redirectRoute('mozos.modificar-producto', [
            'mesa' => $this->numeroMesa,
            'renglon' => $renglon,
            'codigo' => $codigo
        ], navigate: true);
    }

    public function enviarComanda()
    {
        if (!session('mozo_comanda')) {
            session()->flash('error', 'No tiene permisos para enviar comanda');
            return;
        }

        try {
            $this->mozosService->enviarComanda($this->numeroMesa);
            session()->flash('success', 'Comanda enviada a cocina');
            $this->redirectRoute('mozos.mesas', navigate: true);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function enviarPrecuenta()
    {
        if (!session('mozo_precuenta')) {
            session()->flash('error', 'No tiene permisos para enviar precuenta');
            return;
        }

        try {
            $this->mozosService->enviarPrecuenta($this->numeroMesa);
            session()->flash('success', 'Precuenta enviada');
            $this->redirectRoute('mozos.mesas', navigate: true);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function cancelarMesa()
    {
        try {
            $this->mozosService->cancelarMesa($this->numeroMesa);
            session()->flash('success', 'Mesa cancelada');
            $this->redirectRoute('mozos.mesas', navigate: true);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function actualizarComensales()
    {
        // Solo actualizar si hay cambios
        if ($this->comensales != $this->mesa->comensales) {
            // Validar si cobra cubierto y tiene productos
            $param = $this->mozosService->obtenerParametros();
            $tieneProductos = count($this->detalle) > 0;

            if ($param && $param->precioxcub > 0 && $tieneProductos && $this->comensales < 1) {
                session()->flash('error', 'No puede reducir los comensales a 0 cuando cobra cubierto');
                $this->comensales = $this->mesa->comensales; // Restaurar valor anterior
                $this->cargarDatosMesa();
                return;
            }

            $comensalesAnteriores = $this->mesa->comensales;

            // Si pasa de 0 comensales a más de 0, ocupar la mesa
            if ($comensalesAnteriores == 0 && $this->comensales > 0) {
                \Illuminate\Support\Facades\DB::connection('client_db')
                    ->table(session('client_table_prefix') . 'mesas')
                    ->where('NUMERO', $this->numeroMesa)
                    ->update([
                        'COMENSALES' => $this->comensales,
                        'RECURSO' => \Illuminate\Support\Facades\DB::raw("IF(LEFT(recurso,1)='N',CONCAT(LEFT(recurso,6),'0'),CONCAT(LEFT(recurso,5),'0'))"),
                        'ocupada' => true,
                        'MOZO' => session('mozo_user_id'),
                        'fechaaper' => \Illuminate\Support\Facades\DB::raw('CURDATE()'),
                        'horaaper' => \Illuminate\Support\Facades\DB::raw('CURTIME()')
                    ]);

                // Registrar en auditoría
                \Illuminate\Support\Facades\DB::connection('client_db')
                    ->table(session('client_table_prefix') . 'auditoria')
                    ->insert([
                        'TIPO' => 1,
                        'DESCRIPCION' => 'APERTURA DE MESA ' . $this->numeroMesa . ' (W)',
                        'FECHA' => \Illuminate\Support\Facades\DB::raw('CURDATE()'),
                        'HORA' => \Illuminate\Support\Facades\DB::raw('CURTIME()'),
                        'USUARIO' => session('mozo_user'),
                        'MESA' => $this->numeroMesa,
                    ]);
            } else {
                // Solo actualizar comensales
                \Illuminate\Support\Facades\DB::connection('client_db')
                    ->table(session('client_table_prefix') . 'mesas')
                    ->where('NUMERO', $this->numeroMesa)
                    ->update(['COMENSALES' => $this->comensales]);
            }

            // Actualizar servicio de mesa si existe
            $this->mozosService->actualizarServicioMesa($this->numeroMesa, $this->comensales);

            $this->cargarDatosMesa();
            session()->flash('success', 'Comensales actualizados');
        }
    }

    public function incrementarComensales()
    {
        if ($this->comensales < 25) {
            $this->comensales++;
            $this->actualizarComensales();
        }
    }

    public function decrementarComensales()
    {
        // Validar si cobra cubierto
        $param = $this->mozosService->obtenerParametros();
        $tieneProductos = count($this->detalle) > 0;
        $minimo = ($param && $param->precioxcub > 0 && $tieneProductos) ? 1 : 0;

        if ($this->comensales > $minimo) {
            $this->comensales--;
            $this->actualizarComensales();
        }
    }

    public function mostrarBuscador()
    {
        $this->mostrarBusqueda = !$this->mostrarBusqueda;
        if ($this->mostrarBusqueda) {
            $this->busqueda = '';
        }
    }

    public function verPromociones()
    {
        $this->redirectRoute('mozos.promociones', ['mesa' => $this->numeroMesa], navigate: true);
    }

    #[Layout('layouts.mozos')]
    public function render()
    {
        return view('mozos.detalle-mesa');
    }
}
