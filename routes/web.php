<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PanelAuthController;
use App\Http\Controllers\Auth\MozoAuthController;
use App\Http\Controllers\Auth\VendedorAuthController;

// Página principal pública
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Logout forzado (para cambio de módulos)
Route::get('/logout-force', function () {
    session()->flush();
    return redirect()->route('home');
})->name('logout.force');

// Rutas de Panel
Route::prefix('panel')->middleware(['client:panel'])->group(function () {
    Route::get('/login', [PanelAuthController::class, 'showLogin'])->name('login.panel');
    Route::post('/authenticate', [PanelAuthController::class, 'authenticate'])->name('authenticate.panel');
    Route::get('/logout', [PanelAuthController::class, 'logout'])->name('logout.panel');
        
    Route::get('/panel', \App\Livewire\Panel\Dashboard::class)->name('panel.dashboard');
    Route::get('/pànelresto', \App\Livewire\PanelResto\Dashboard::class)->name('panelresto.dashboard');
    
});

// Rutas de Panel Resto
/*Route::prefix('panelresto')->middleware(['client:panel'])->group(function () {
    Route::get('/login', [PanelAuthController::class, 'showLogin'])->name('login.panel');
    Route::post('/authenticate', [PanelAuthController::class, 'authenticate'])->name('authenticate.panel');
    Route::get('/logout', [PanelAuthController::class, 'logout'])->name('logout.panel');

    Route::get('/', \App\Livewire\PanelResto\Dashboard::class)->name('panelresto.dashboard');
    
});*/

// Rutas de Mozos
Route::prefix('mozos')->middleware(['client'])->group(function () {
    Route::get('/login', [MozoAuthController::class, 'showLogin'])->name('login.mozos');
    Route::post('/authenticate', [MozoAuthController::class, 'authenticate'])->name('authenticate.mozos');
    Route::post('/logout', [MozoAuthController::class, 'logout'])->name('logout.mozos');
    
    /*Route::middleware(['client:mozos'])->group(function () {
        Route::get('/', function () {
            return view('mozos.dashboard');
        })->name('mozos.dashboard');
    });*/
});

// Rutas de Vendedores
Route::prefix('vendedores')->middleware(['client:vendedores'])->group(function () {
    Route::get('/login', [VendedorAuthController::class, 'showLogin'])->name('login.vendedores');
    Route::post('/authenticate', [VendedorAuthController::class, 'authenticate'])->name('authenticate.vendedores');
    Route::post('/logout', [VendedorAuthController::class, 'logout'])->name('logout.vendedores');
    
    /*Route::middleware(['client:vendedores'])->group(function () {
        Route::get('/', function () {
            return view('vendedores.dashboard-vendedor');
        })->name('vendedores.dashboard-vendedor');
    });*/
    Route::middleware(['client:vendedores'])->group(function () {
        Route::get('/dashboard', \App\Livewire\Vendedores\DashboardVendedor::class)->name('dashboard-vendedor');
        Route::get('/pedido/nuevo', \App\Livewire\Vendedores\GestorPedidosVendedor::class)->name('gestor-pedido-vendedor-nuevo');
        Route::get('/pedido/editar/{pedidoId}', \App\Livewire\Vendedores\GestorPedidosVendedor::class)->name('gestor-pedido-vendedor-editar');
    
    });
});

// Las otras rutas (vendedores_emp, monitor) seguirían el mismo patrón