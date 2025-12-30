<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SessionExpiredController extends Controller
{
    public function show(Request $request)
    {
        $module = $request->query('module', 'panel');

        // Mapeo de módulos a nombres amigables
        $moduleNames = [
            'panel' => 'Panel de Gestión',
            'mozos' => 'Sistema de Mozos',
            'vendedores' => 'Sistema de Vendedores',
            'vendedores_empresas' => 'Sistema de Vendedores Empresas',
            'monitor' => 'Monitor de Cocina'
        ];

        // Mapeo de módulos a rutas de login
        $loginRoutes = [
            'panel' => route('login.panel'),
            'mozos' => route('login.mozos'),
            'vendedores' => route('login.vendedores'),
            'vendedores_empresas' => route('login.vendedores-empresas'),
            'monitor' => route('login.monitor')
        ];

        $moduleName = $moduleNames[$module] ?? 'Sistema';
        $loginRoute = $loginRoutes[$module] ?? route('login.panel');

        return view('session-expired', [
            'moduleName' => $moduleName,
            'loginRoute' => $loginRoute,
            'module' => $module
        ]);
    }
}
