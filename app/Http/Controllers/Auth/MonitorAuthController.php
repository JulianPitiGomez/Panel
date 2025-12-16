<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\Cliente;

class MonitorAuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.monitor-login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'id' => 'required',            
        ]);

        // Buscar cliente de restaurante por mail
        $cliente = Cliente::where('id', $request->id)
                         ->where('pack', 'RE3')
                         ->first();

        if (!$cliente) {
            return back()->withErrors(['id' => 'No existe un restaurante']);
        }

        // Configurar conexión a la base del cliente
        Config::set('database.connections.client_db.database', $cliente->base);
        DB::purge('client_db');


        session([
                'monitor_user_id' => $cliente->id,
                'client_id' => $cliente->id,
                'active_module' => 'monitor',
                'client_table_prefix' => $cliente->getTablePrefix()
            ]);

        return redirect()->route('monitor.dashboard');
        
    }

    public function logout()
    {
        session()->forget(['monitor_user_id', 'client_id', 'active_module', 'client_table_prefix']);
        return redirect()->route('home');
    }
}