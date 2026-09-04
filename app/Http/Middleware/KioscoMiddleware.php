<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Models\Cliente;

class KioscoMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('kiosco_authenticated') || !session('kiosco_client_id')) {
            return redirect()->route('kiosco.login');
        }

        // Configurar la base de datos del cliente
        $clientId = session('kiosco_client_id');
        $cliente = Cliente::find($clientId);

        if (!$cliente) {
            session()->forget(['kiosco_authenticated', 'kiosco_client_id']);
            return redirect()->route('kiosco.login');
        }

        Config::set('database.connections.client_db.database', $cliente->base);
        DB::purge('client_db');
        session(['client_table_prefix' => $cliente->getTablePrefix()]);

        // Asegurar que client_id esté disponible
        if (!session('client_id')) {
            session(['client_id' => $clientId]);
        }

        return $next($request);
    }
}
