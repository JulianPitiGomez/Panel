<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\Cliente;

class MozoAuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.mozo-login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'mail' => 'required|email',
            'usuario' => 'required',
            'password' => 'required',
        ]);

        // Buscar cliente de restaurante por mail
        $cliente = Cliente::where('mail', $request->mail)
                         ->where('pack', 'RE3')
                         ->first();

        if (!$cliente) {
            return back()->withErrors(['mail' => 'Mail no encontrado para restaurantes']);
        }

        // Configurar conexión a la base del cliente
        Config::set('database.connections.client_db.database', $cliente->base);
        DB::purge('client_db');

        // Buscar mozo en la tabla específica
        $tableName = $cliente->getTablePrefix() . 'mozos';
        
        $mozo = DB::connection('client_db')
                  ->table($tableName)
                  ->where('user', $request->usuario)
                  ->first();

        if ($mozo && Hash::check($request->password, $mozo->pass)) {
            session([
                'mozo_user_id' => $mozo->codigo,
                'client_id' => $cliente->id,
                'active_module' => 'mozos',
                'client_table_prefix' => $cliente->getTablePrefix()
            ]);

            return redirect()->route('mozos.dashboard');
        }

        return back()->withErrors(['usuario' => 'Credenciales de mozo incorrectas']);
    }

    public function logout()
    {
        session()->forget(['mozo_user_id', 'client_id', 'active_module', 'client_table_prefix']);
        return redirect()->route('home');
    }
}