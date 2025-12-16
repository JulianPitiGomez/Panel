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
                  ->select('CODIGO as codigo', 'NOMBRE as nombre', 'USER as user', 'PASS as pass',
                           'COMANDA as comanda', 'BORRAITEM as borraitem',
                           'PANEL_BORRACC as borracc', 'PANEL_BORRASC as borrasc', 'PRECUENTA as precuenta')
                  ->where('USER', $request->usuario)
                  ->where('PASS', $request->password)
                  ->first();

        if ($mozo) {
            // Obtener nombre del cliente
            $parametros = DB::connection('client_db')
                           ->table($cliente->getTablePrefix() . 'parametros')
                           ->select('fantasia', 'NOMB_EMP')
                           ->first();

            $nombreCliente = !empty($parametros->fantasia) ? $parametros->fantasia : $parametros->NOMB_EMP;

            session([
                'mozo_user_id' => $mozo->codigo,
                'mozo_nombre' => $mozo->nombre,
                'mozo_user' => $mozo->user,
                'mozo_comanda' => $mozo->comanda,
                'mozo_borraitem' => $mozo->borraitem,
                'mozo_borracc' => $mozo->borracc,
                'mozo_borrasc' => $mozo->borrasc,
                'mozo_precuenta' => $mozo->precuenta,
                'client_id' => $cliente->id,
                'active_module' => 'mozos',
                'client_table_prefix' => $cliente->getTablePrefix(),
                'cliente_nombre' => $nombreCliente,
            ]);

            return redirect()->route('mozos.mesas');
        }

        return back()->withErrors(['usuario' => 'Credenciales de mozo incorrectas']);
    }

    public function logout()
    {
        session()->forget(['mozo_user_id', 'mozo_nombre', 'mozo_user', 'mozo_comanda', 'mozo_borraitem',
                          'mozo_borracc', 'mozo_borrasc', 'mozo_precuenta', 'client_id', 'active_module',
                          'client_table_prefix', 'cliente_nombre']);
        return redirect()->route('login.mozos');
    }
}