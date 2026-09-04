<?php

namespace App\Livewire\Vendedores;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Livewire\Attributes\Layout;

class NuevoCliente extends Component
{
    public $nombre = '';
    public $direccion = '';
    public $localidad = '';
    public $telefono = '';
    public $cuit = '';
    public $coniva = '5';
    public $lispreesp = '';
    public $listasEspeciales = [];
    public $tablePrefix = '';

    public function updatedCuit($value)
    {
        // Eliminar todo excepto números
        $numeros = preg_replace('/[^0-9]/', '', $value);

        // Formatear según longitud
        if (strlen($numeros) <= 2) {
            $this->cuit = $numeros;
        } elseif (strlen($numeros) <= 10) {
            $this->cuit = substr($numeros, 0, 2) . '-' . substr($numeros, 2);
        } else {
            $this->cuit = substr($numeros, 0, 2) . '-' . substr($numeros, 2, 8) . '-' . substr($numeros, 10, 1);
        }
    }

    public function mount()
    {
        $this->setupClientDatabase();

        // Si el vendedor tiene LISTAPRE = 3, cargar las listas especiales
        if (session('vendedor_listapre') == 3) {
            $this->listasEspeciales = DB::connection('client_db')
                ->table($this->tablePrefix . 'lispre')
                ->select('CODIGO', 'NOMBRE')
                ->get();
        }
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

    public function guardar()
    {
        // Validación
        $this->validate([
            'nombre' => 'required|min:3',
            'direccion' => 'required',
            'localidad' => 'required',
            'telefono' => 'nullable',
            'cuit' => 'nullable|size:13',
            'coniva' => 'required|in:1,4,5,6',
        ], [
            'cuit.size' => 'El CUIT debe tener el formato XX-XXXXXXXX-X (11 dígitos)',
        ]);

        // Validar lista especial si corresponde
        if (session('vendedor_listapre') == 3) {
            $this->validate([
                'lispreesp' => 'required',
            ]);
        }

        $this->setupClientDatabase();
        $vendedorCodigo = session('vendedor_user_id');

        // Generar código de cliente automático
        $ultimoCodigo = DB::connection('client_db')
            ->table($this->tablePrefix . 'clientes')
            ->max('CODIGO');

        $nuevoCodigo = $ultimoCodigo ? $ultimoCodigo + 1 : 1;

        // Determinar lispre y lispreesp según LISTAPRE del vendedor
        $lispre = 1;
        $lispreesp = 0;

        if (session('vendedor_listapre') == 1) {
            $lispre = 1;
            $lispreesp = 0;
        } elseif (session('vendedor_listapre') == 2) {
            $lispre = 2;
            $lispreesp = 0;
        } elseif (session('vendedor_listapre') == 3) {
            $lispre = 1;
            $lispreesp = $this->lispreesp;
        }

        // Insertar nuevo cliente
        DB::connection('client_db')
            ->table($this->tablePrefix . 'clientes')
            ->insert([
                'CODIGO' => $nuevoCodigo,
                'NOMBRE' => strtoupper($this->nombre),
                'DIRECCION' => strtoupper($this->direccion),
                'LOCALIDAD' => strtoupper($this->localidad),
                'TELEFONO' => $this->telefono,
                'CUIT' => $this->cuit,
                'CONIVA' => $this->coniva,
                'LISPRE' => $lispre,
                'LISPREESP' => $lispreesp,
                'VENDEDOR' => $vendedorCodigo,
                'descuento' => 0,
                'fecing'    => db::raw('CURDATE()'),
                'dni'       => 0,
                'saldo'     => 0,
                'zona'      => '1',
                'mail'      => ''
            ]);

        session()->flash('success', 'Cliente creado exitosamente');

        // Redirigir al dashboard
        return redirect()->route('dashboard-vendedor');
    }

    public function cancelar()
    {
        return redirect()->route('dashboard-vendedor');
    }

    #[Layout('layouts.vendedores')]
    public function render()
    {
        return view('vendedores.nuevo-cliente');
    }
}
