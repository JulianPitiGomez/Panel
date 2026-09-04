<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use App\Models\Cliente;

class KioscoLogin extends Component
{
    public $email = '';
    public $password = '';
    public $error = '';

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|min:1',
        ]);

        $this->error = '';

        // Buscar cliente por email
        $cliente = Cliente::where('mail', $this->email)->where('activo', 1)->first();

        if (!$cliente) {
            $this->error = 'No se encontró una cuenta con ese email.';
            return;
        }

        // Buscar config del kiosco en config_clientes_web
        $configWeb = DB::connection('config')
            ->table('config_clientes_web')
            ->where('cliente_id', $cliente->id)
            ->first();

        if (!$configWeb || !$configWeb->configuracion) {
            $this->error = 'El kiosco no está configurado para esta cuenta.';
            return;
        }

        $config = json_decode($configWeb->configuracion, true);
        $kioscoPassword = $config['kiosco_password'] ?? '';

        if (empty($kioscoPassword)) {
            $this->error = 'El kiosco no tiene contraseña configurada.';
            return;
        }

        if ($this->password !== $kioscoPassword) {
            $this->error = 'Contraseña incorrecta.';
            return;
        }

        // Auth exitoso: guardar en sesión
        session([
            'kiosco_authenticated' => true,
            'kiosco_client_id' => $cliente->id,
            'client_id' => $cliente->id,
        ]);

        return redirect()->route('kiosco.app');
    }

    #[Layout('layouts.fullscreen')]
    public function render()
    {
        return view('kiosco.login');
    }
}
