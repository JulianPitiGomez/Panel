<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\Cliente;

class ClientMiddleware
{
    public function handle(Request $request, Closure $next, $moduleType = null)
    {
        // Si ya hay una sesión activa de otro módulo, redirigir
        if (session('active_module') && session('active_module') !== $moduleType) {
            return redirect()->route('logout.force');
        }

        // Si es una ruta de autenticación, permitir acceso
        if ($request->routeIs(['login.*', 'authenticate.*'])) {
            return $next($request);
        }

        // Verificar autenticación según el módulo
        if (!$this->isAuthenticated($moduleType)) {
            return redirect()->route('login.' . $moduleType);
        }

        // Configurar conexión de base de datos del cliente
        $this->setupClientDatabase();
        

        return $next($request);
    }

    private function isAuthenticated($moduleType)
    {
        switch ($moduleType) {
            case 'panel':
                return session('panel_user_id') !== null;
            case 'mozos':
                return session('mozo_user_id') !== null && session('client_id') !== null;
            case 'vendedores':
                return session('vendedor_user_id') !== null && session('client_id') !== null;
            case 'vendedores_emp':
                return session('vendedor_user_id') !== null && session('client_id') !== null;
            case 'monitor':
                return session('vendedor_user_id') !== null && session('client_id') !== null;
            default:
                return false;
        }
    }

    private function setupClientDatabase()
    {
        $clientId = session('client_id');
        if ($clientId) {
            $cliente = Cliente::find($clientId);
            
            if ($cliente) {                
                // Configurar la conexión client_db
                Config::set('database.connections.client_db.database', $cliente->base);

                // Purgar la conexión para forzar reconexión
                DB::purge('client_db');

                
                // Establecer el prefijo de tabla en la sesión
                session(['client_table_prefix' => $cliente->getTablePrefix()]);
            }
        }
    }
}