<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\Cliente;

class VendedorAuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.vendedor-login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'mail' => 'required|email',
            'usuario' => 'required',
            'password' => 'required',
        ]);

        // Buscar cliente de pyme por mail
        $cliente = Cliente::where('mail', $request->mail)
                         ->where('pack', 'GE3')
                         ->first();

        if (!$cliente) {
            return back()->withErrors(['mail' => 'Mail no encontrado para pymes']);
        }

        // Configurar conexión a la base del cliente
        Config::set('database.connections.client_db.database', $cliente->base);
        DB::purge('client_db');

        // Buscar vendedor en la tabla específica
        $tableName = $cliente->getTablePrefix() . 'vendedores';
        
        $vendedor = DB::connection('client_db')
                      ->table($tableName)
                      ->select('codigo', 'pass','user','nombre')
                      ->where('user', $request->usuario)
                      ->first();

        if ($vendedor && $request->password == $vendedor->pass) {
            session([
                'vendedor_user_id' => $vendedor->codigo,
                'client_id' => $cliente->id,
                'active_module' => 'vendedores',
                'client_table_prefix' => $cliente->getTablePrefix(),
                'vendedor_nombre' => $vendedor->nombre,
                'vendedor_user' => $vendedor->user,
                'cliente_nombre' => $cliente->nombre,
            ]);

            return redirect()->route('dashboard-vendedor');
        }

        return back()->withErrors(['usuario' => 'Credenciales de vendedor incorrectas']);
    }

    public function logout()
    {
        session()->forget(['vendedor_user_id', 'client_id', 'active_module', 'client_table_prefix']);
        return redirect()->route('login.vendedores');
    }
}