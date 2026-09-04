<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\Cliente;
use App\Services\TiendaService;

class TiendaMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $tienda = $request->route('tienda');

        if (!$tienda) {
            return $next($request);
        }

        $cliente = Cliente::whereRaw('LOWER(tienda) = ?', [strtolower($tienda)])
                         ->where('pack', 'GE3')
                         ->first();

        // Tienda inexistente o sin el servicio contratado: pagina amigable, con 404 para los buscadores
        if (!$cliente || !TiendaService::habilitada($cliente->id)) {
            return response()->view('tienda.no-disponible', [
                'nombreTienda' => $cliente ? ($cliente->nomemp ?: $cliente->nombre) : null,
            ], 404);
        }

        // Configurar conexión a la base del cliente
        Config::set('database.connections.client_db.database', $cliente->base);
        DB::purge('client_db');

        $tablePrefix = $cliente->getTablePrefix();

        // Cargar datos del comercio
        $configWeb = DB::connection('config')
            ->table('config_clientes_web')
            ->where('cliente_id', $cliente->id)
            ->first();

        $datosComercio = [];
        if ($configWeb && $configWeb->configuracion) {
            $datosComercio = json_decode($configWeb->configuracion, true) ?: [];
        }

        // Modo de acceso: vive en el JSON de config web, no en codven
        $modo = TiendaService::normalizarModo($datosComercio[TiendaService::CLAVE_MODO] ?? null);

        // Detectar logo y portada
        $logoPath = "img/logos/{$cliente->id}/logo.png";
        $portadaPath = "img/logos/{$cliente->id}/portada.png";
        $logoUrl = file_exists(public_path($logoPath)) ? asset($logoPath) . '?v=' . filemtime(public_path($logoPath)) : '';
        $portadaUrl = file_exists(public_path($portadaPath)) ? asset($portadaPath) . '?v=' . filemtime(public_path($portadaPath)) : '';

        // Guardar en sesión
        session([
            'tienda_config_id' => $cliente->id,
            'tienda_prefix' => $tablePrefix,
            'tienda_nombre' => $cliente->nomemp ?: $cliente->nombre,
            'tienda_modo' => $modo,
            'tienda_slug' => $tienda,
            'tienda_base' => $cliente->base,
            'tienda_logo' => $logoUrl,
            'tienda_portada' => $portadaUrl,
            'tienda_datos' => $datosComercio,
        ]);

        // Control de acceso: si es privado y no está logueado, redirigir al index (login)
        // Excepto para la ruta index y login que siempre son accesibles
        $routeName = $request->route()->getName();
        $rutasPublicas = ['tienda.index', 'tienda.login'];

        if (TiendaService::esPrivada($modo) && !session('tienda_comprador_id') && !in_array($routeName, $rutasPublicas)) {
            return redirect()->route('tienda.index', $tienda);
        }

        return $next($request);
    }
}
