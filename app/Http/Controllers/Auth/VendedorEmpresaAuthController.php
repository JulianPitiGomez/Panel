<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use App\Models\Cliente;

class VendedorEmpresaAuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.vendedor-empresa-login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'mail' => 'required|email',
            'usuario' => 'required',
            'password' => 'required',
        ]);

        // Buscar cliente por mail (cualquier pack)
        $cliente = Cliente::where('mail', $request->mail)
                         ->where('pack', 'RE3')
                         ->first();

        if (!$cliente) {
            return back()->withErrors(['mail' => 'Mail no encontrado']);
        }

        // Configurar conexión a la base del cliente
        Config::set('database.connections.client_db.database', $cliente->base);
        DB::purge('client_db');

        // Verificar si existe la tabla ma_vendedores
        try {
            if (!Schema::connection('client_db')->hasTable('ma_vendedores')) {
                return back()->withErrors(['mail' => 'Este cliente no tiene habilitado el módulo de vendedores']);
            }
        } catch (\Exception $e) {
            return back()->withErrors(['mail' => 'Error de configuración del cliente']);
        }

        // Buscar vendedor en la tabla ma_vendedores (sin prefijo)
        $vendedor = DB::connection('client_db')
                      ->table('ma_vendedores')
                      ->select('codigo', 'pass','user','nombre')
                      ->where('user', $request->usuario)
                      ->first();

        if ($vendedor && $request->password == $vendedor->pass) {
            session([
                'vendedor_empresa_user_id' => $vendedor->codigo,
                'client_id' => $cliente->id,
                'active_module' => 'vendedores_empresas',
                'client_table_prefix' => $cliente->getTablePrefix(),
                'vendedor_empresa_nombre' => $vendedor->nombre,
                'vendedor_empresa_user' => $vendedor->user,
                'cliente_nombre' => $cliente->nombre,
            ]);

            return redirect()->route('dashboard-vendedor-empresa');
        }

        return back()->withErrors(['usuario' => 'Credenciales de vendedor incorrectas']);
    }

    public function logout()
    {
        session()->forget(['vendedor_empresa_user_id', 'client_id', 'active_module', 'client_table_prefix']);
        return redirect()->route('login.vendedores-empresas');
    }
}
