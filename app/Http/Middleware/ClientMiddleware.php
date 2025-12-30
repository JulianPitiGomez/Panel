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

        // Si es una ruta de autenticación o sesión expirada, permitir acceso
        if ($request->routeIs(['login.*', 'authenticate.*', 'session.expired'])) {
            return $next($request);
        }

        // Verificar autenticación según el módulo
        if (!$this->isAuthenticated($moduleType)) {
            // Detectar si había una sesión previa (sesión expirada)
            // Verificamos si hubo actividad previa en este módulo
            $hadSession = session()->has('last_module') && session('last_module') === $moduleType;

            if ($hadSession) {
                // Limpiar sesión antes de redirigir
                session()->flush();
                return redirect()->route('session.expired', ['module' => $moduleType]);
            }

            // Primera visita, redirigir directamente al login
            return redirect()->route('login.' . $moduleType);
        }

        // Guardar el módulo actual en la sesión para detección de expiración
        session(['last_module' => $moduleType]);

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
            case 'vendedores_empresas':
                return session('vendedor_empresa_user_id') !== null && session('client_id') !== null;
            case 'monitor':
                return session('monitor_user_id') !== null && session('client_id') !== null;
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