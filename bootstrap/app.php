<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ClientMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Registrar el middleware con alias
        $middleware->alias([
            'client' => ClientMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Manejo personalizado de sesión expirada
        $exceptions->respond(function ($response, $exception, $request) {
            // Solo para peticiones HTTP normales (no AJAX/Livewire)
            // Las peticiones AJAX de Livewire se manejan en app.js
            if (!$request->isMethod('POST') || !$request->expectsJson()) {
                return $response;
            }

            // Si es un error 419 (CSRF token mismatch - sesión expirada)
            if ($response->status() === 419) {
                // Detectar módulo desde la URL
                $module = 'panel';
                $path = $request->path();

                if (str_contains($path, 'mozos')) {
                    $module = 'mozos';
                } elseif (str_contains($path, 'vendedores-empresas')) {
                    $module = 'vendedores_empresas';
                } elseif (str_contains($path, 'vendedores')) {
                    $module = 'vendedores';
                } elseif (str_contains($path, 'monitor')) {
                    $module = 'monitor';
                }

                // Para peticiones AJAX, devolver JSON para que JavaScript lo maneje
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Sesión expirada',
                        'redirect' => route('session.expired', ['module' => $module])
                    ], 419);
                }

                // Para peticiones normales, redirigir directamente
                return redirect()->route('session.expired', ['module' => $module]);
            }

            return $response;
        });
    })->create();
