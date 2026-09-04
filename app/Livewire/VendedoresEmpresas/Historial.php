<?php

namespace App\Livewire\VendedoresEmpresas;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

class Historial extends Component
{
    public $pedidos = [];
    public $tablePrefix = '';
    public $vendedorCodigo;
    public $pedidoExpandido = null;
    public $detalle = [];

    public $mostrarModalImprimir = false;
    public $pedidoAImprimir = null;
    public $mostrarModalDuplicar = false;
    public $pedidoADuplicar = null;
    public $buscandoCliente = false;
    public $busqueda = '';
    public $desdefecha;
    public $hastafecha;
    public $estado = ' ';
    public $clientesFiltrados = [];


    public function mount()
    {
        $this->vendedorCodigo = session('vendedor_empresa_user_id');
        $this->desdefecha = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->hastafecha = Carbon::now()->format('Y-m-d');
        $this->cargarPedidos();
    }

    private function setupClientDatabase()
    {
        $clientId = session('client_id');
        if ($clientId) {
            $cliente = \App\Models\Cliente::find($clientId);
            if ($cliente) {
                Config::set('database.connections.client_db.database', $cliente->base);
                DB::purge('client_db');
                session(['client_table_prefix' => $cliente->getTablePrefix()]);
                $this->tablePrefix = $cliente->getTablePrefix();
            }
        }
    }

    /**
     * Pedidos propios del vendedor (codven) mas los que carga administracion
     * desde el sistema de gestion a su nombre (codven = 0 + nombre coincidente).
     */
    private function filtroPedidosDelVendedor($query)
    {
        $query->where('codven', $this->vendedorCodigo);

        $nombre = trim((string) session('vendedor_empresa_nombre'));

        if ($nombre !== '') {
            $query->orWhere(function ($q) use ($nombre) {
                $q->where('codven', 0)
                  ->whereRaw('TRIM(vendedor) = ?', [$nombre]);
            });
        }

        return $query;
    }

    public function cargarPedidos()
    {
        $this->setupClientDatabase();
        $this->pedidos = DB::connection('client_db')
            ->table("ma_pedidos_encab")
            ->where(fn ($q) => $this->filtroPedidosDelVendedor($q))
            ->where('cliente', 'like', '%' . $this->busqueda . '%')
            ->whereBetween('fecha', [$this->desdefecha, $this->hastafecha])
            ->orderByDesc('fecha')
            ->get();

        switch ($this->estado) {
            case 'A':
                // En Armado
                $this->pedidos = $this->pedidos->where('estado', 'A');
                break; 
            case 'P':
                // En Proceso
                $this->pedidos = $this->pedidos->where('estado', 'P');
                break;                

            case 'F':
                // Facturados
                $this->pedidos = $this->pedidos->where('estado', 'F');
                break;

            default:
                break;
        }

    }



    public function toggleExpand($id)
    {
        $this->pedidoExpandido = $this->pedidoExpandido === $id ? null : $id;
        if ($this->pedidoExpandido) {
            $this->setupClientDatabase();
            $this->detalle = DB::connection('client_db')
                ->table("ma_pedidos_det")
                ->where('idpedido', $id)
                ->get();
        } else {
            $this->detalle = [];
        }

    }

    public function imprimirPedido($id)
    {
        $this->pedidoAImprimir = $id;
        $this->mostrarModalImprimir = true;
    }

    public function pedirDuplicar($id)
    {
        $this->pedidoADuplicar = $id;
        $this->mostrarModalDuplicar = true;
    }

    public function confirmarDuplicacion()
    {
        $id = $this->pedidoADuplicar;
        $this->mostrarModalDuplicar = false;
        $this->pedidoADuplicar = null;
        return $this->duplicarPedido($id);
    }

    public function duplicarPedido($id)
    {
        $this->setupClientDatabase();

        $original = DB::connection('client_db')
            ->table("ma_pedidos_encab")
            ->where('id', $id)
            ->where(fn ($q) => $this->filtroPedidosDelVendedor($q))
            ->first();

        if (!$original) {
            session()->flash('error', 'No se encontró el pedido a duplicar.');
            return;
        }

        $cliente = DB::connection('client_db')
            ->table("{$this->tablePrefix}clientes")
            ->where('CODIGO', $original->codcli)
            ->first();

        if (!$cliente) {
            session()->flash('error', 'El cliente del pedido original ya no existe en el sistema.');
            return;
        }

        $detalleOriginal = DB::connection('client_db')
            ->table("ma_pedidos_det")
            ->where('idpedido', $id)
            ->get();

        if ($detalleOriginal->isEmpty()) {
            session()->flash('error', 'El pedido original no tiene artículos para duplicar.');
            return;
        }

        $lispreesp = $cliente->lispreesp ?? $cliente->LISPREESP ?? null;
        $lispre = $cliente->LISPRE ?? $cliente->lispre ?? null;
        $descup = floatval($cliente->DESCUENTO ?? 0);

        $nuevasLineas = [];
        $omitidos = [];

        foreach ($detalleOriginal as $item) {
            $articulo = DB::connection('client_db')
                ->table("{$this->tablePrefix}articu")
                ->where('CODIGO', $item->codart)
                ->first();

            if (!$articulo || ($articulo->inactivo ?? 0) == 1) {
                $omitidos[] = trim($item->codart . ' - ' . $item->detart, ' -');
                continue;
            }

            $precio = null;
            if ($lispreesp) {
                $precio = DB::connection('client_db')
                    ->table("ma_lispredet")
                    ->where('CODLIS', $lispreesp)
                    ->where('CODART', $item->codart)
                    ->value('PRECIO');
            }
            if ($precio === null) {
                $precio = $lispre == 2 ? $articulo->REVENTA : $articulo->PRECIO;
            }

            $descu = round($precio * $descup / 100, 2);

            $nuevasLineas[] = [
                'codart' => $articulo->CODIGO,
                'detart' => $articulo->NOMBRE,
                'cantidad' => $item->cantidad,
                'cantidadreal' => $item->cantidad,
                'punitario' => $precio,
                'descu' => $descu,
                'descup' => $descup,
            ];
        }

        if (empty($nuevasLineas)) {
            session()->flash('error', 'Ninguno de los artículos del pedido original está disponible actualmente.');
            return;
        }

        $nuevoId = DB::connection('client_db')
            ->table("ma_pedidos_encab")
            ->insertGetId([
                'codcli' => $cliente->CODIGO,
                'cliente' => $cliente->NOMBRE,
                'codven' => $this->vendedorCodigo,
                'vendedor' => session('vendedor_empresa_nombre'),
                'fecha' => DB::raw('CURDATE()'),
                'parafecha' => DB::raw('CURDATE()'),
                'estado' => 'A',
            ]);

        foreach ($nuevasLineas as $linea) {
            $linea['idpedido'] = $nuevoId;
            DB::connection('client_db')
                ->table("ma_pedidos_det")
                ->insert($linea);
        }

        if (!empty($omitidos)) {
            session()->flash(
                'aviso_duplicado',
                'Se omitieron ' . count($omitidos) . ' artículo(s) que ya no están disponibles: ' . implode(', ', $omitidos)
            );
        }

        return redirect('vendedores-empresas/pedido/editar/' . $nuevoId);
    }

    public function confirmarImpresion($valorizado)
    {
        $this->mostrarModalImprimir = false;
        $url = route('imprimir-pedido-empresa', $this->pedidoAImprimir) . ($valorizado ? '' : '?valorizado=0');
        $this->dispatch('abrir-impresion', [
            'url' => $url
        ]);
        $this->pedidoAImprimir = null;
    }

    // Método para la ruta
    public function imprimirPedidoRuta($id)
    {
        $this->setupClientDatabase();
        $pedido = DB::connection('client_db')->table("ma_pedidos_encab")->find($id);
        $detalles = DB::connection('client_db')->table("ma_pedidos_det")->where('idpedido', $id)->get();
        $valorizado = request('valorizado', '1') !== '0';

        return view('vendedores-empresas.imprimir-pedido', compact('pedido', 'detalles', 'valorizado'));
    }

    #[Layout('layouts.vendedores-empresas')]
    public function render()
    {
        $this->cargarPedidos();
        return view('vendedores-empresas.historial');
    }
}
