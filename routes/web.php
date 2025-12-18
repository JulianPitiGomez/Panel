<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PanelAuthController;
use App\Http\Controllers\Auth\MozoAuthController;
use App\Http\Controllers\Auth\VendedorAuthController;
use App\Http\Controllers\Auth\VendedorEmpresaAuthController;
use App\Http\Controllers\Auth\MonitorAuthController;


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
    Route::get('/panelresto', \App\Livewire\PanelResto\Dashboard::class)->name('panelresto.dashboard');
    Route::get('/kiosco', \App\Livewire\KioscoRestaurant::class)->name('kiosco');
    
});

// Rutas de Panel Resto
/*Route::prefix('panelresto')->middleware(['client:panel'])->group(function () {
    Route::get('/login', [PanelAuthController::class, 'showLogin'])->name('login.panel');
    Route::post('/authenticate', [PanelAuthController::class, 'authenticate'])->name('authenticate.panel');
    Route::get('/logout', [PanelAuthController::class, 'logout'])->name('logout.panel');

    Route::get('/', \App\Livewire\PanelResto\Dashboard::class)->name('panelresto.dashboard');
    
});*/

// Rutas de Mozos
Route::prefix('mozos')->middleware(['client:mozos'])->group(function () {
    Route::get('/login', [MozoAuthController::class, 'showLogin'])->name('login.mozos');
    Route::post('/authenticate', [MozoAuthController::class, 'authenticate'])->name('authenticate.mozos');
    Route::get('/logout', [MozoAuthController::class, 'logout'])->name('logout.mozos');

    Route::middleware(['client:mozos'])->group(function () {
        Route::get('/mesas', \App\Livewire\Mozos\MapaMesas::class)->name('mozos.mesas');
        Route::get('/mesa/{mesa}', \App\Livewire\Mozos\DetalleMesa::class)->name('mozos.mesa');
        Route::get('/mesa/{mesa}/agregar/{codigo}', \App\Livewire\Mozos\AgregarConOpcionales::class)->name('mozos.agregar-opcionales');
        Route::get('/mesa/{mesa}/modificar/{renglon}/{codigo}', \App\Livewire\Mozos\ModificarProducto::class)->name('mozos.modificar-producto');
        Route::get('/mesa/{mesa}/promociones', \App\Livewire\Mozos\Promociones::class)->name('mozos.promociones');
    });
});

// Rutas de Vendedores
Route::prefix('vendedores')->middleware(['client:vendedores'])->group(function () {
    Route::get('/login', [VendedorAuthController::class, 'showLogin'])->name('login.vendedores');
    Route::post('/authenticate', [VendedorAuthController::class, 'authenticate'])->name('authenticate.vendedores');
    Route::get('/logout', [VendedorAuthController::class, 'logout'])->name('logout.vendedores');
    
    /*Route::middleware(['client:vendedores'])->group(function () {
        Route::get('/', function () {
            return view('vendedores.dashboard-vendedor');
        })->name('vendedores.dashboard-vendedor');
    });*/
    Route::middleware(['client:vendedores'])->group(function () {
        Route::get('/dashboard', \App\Livewire\Vendedores\DashboardVendedor::class)->name('dashboard-vendedor');
        Route::get('/pedido/nuevo', \App\Livewire\Vendedores\GestorPedidosVendedor::class)->name('gestor-pedido-vendedor-nuevo');
        Route::get('/pedido/editar/{id}', \App\Livewire\Vendedores\GestorPedidosVendedor::class)->name('gestor-pedido-vendedor-editar');
        Route::get('/pedido/imprimir/{id}', [App\Livewire\Vendedores\DashboardVendedor::class, 'imprimirPedidoRuta'])->name('imprimir-pedido');
        Route::get('/historial', \App\Livewire\Vendedores\Historial::class)->name('historial');
        Route::get('/listasprecios', \App\Livewire\Vendedores\ListasPrecios::class)->name('listas-precios');
        Route::get('/imprimirlista', [App\Livewire\Vendedores\ListasPrecios::class, 'imprimirListaRuta'])->name('imprimir-lista');
        Route::get('/deudas', \App\Livewire\Vendedores\Deudas::class)->name('deudas');
    });
});

// Rutas de Vendedores Empresas
Route::prefix('vendedores-empresas')->middleware(['client:vendedores_empresas'])->group(function () {
    Route::get('/login', [VendedorEmpresaAuthController::class, 'showLogin'])->name('login.vendedores-empresas');
    Route::post('/authenticate', [VendedorEmpresaAuthController::class, 'authenticate'])->name('authenticate.vendedores-empresas');
    Route::get('/logout', [VendedorEmpresaAuthController::class, 'logout'])->name('logout.vendedores-empresas');

    Route::middleware(['client:vendedores_empresas'])->group(function () {
        Route::get('/dashboard', \App\Livewire\VendedoresEmpresas\DashboardVendedorEmpresa::class)->name('dashboard-vendedor-empresa');
        Route::get('/pedido/nuevo', \App\Livewire\VendedoresEmpresas\GestorPedidosVendedorEmpresa::class)->name('gestor-pedido-vendedor-empresa-nuevo');
        Route::get('/pedido/editar/{id}', \App\Livewire\VendedoresEmpresas\GestorPedidosVendedorEmpresa::class)->name('gestor-pedido-vendedor-empresa-editar');
        Route::get('/pedido/imprimir/{id}', [App\Livewire\VendedoresEmpresas\DashboardVendedorEmpresa::class, 'imprimirPedidoRuta'])->name('imprimir-pedido-empresa');
        Route::get('/historial', \App\Livewire\VendedoresEmpresas\Historial::class)->name('historial-empresa');
        Route::get('/listasprecios', \App\Livewire\VendedoresEmpresas\ListasPrecios::class)->name('listas-precios-empresa');
        Route::get('/imprimirlista', [App\Livewire\VendedoresEmpresas\ListasPrecios::class, 'imprimirListaRuta'])->name('imprimir-lista-empresa');
        Route::get('/deudas', \App\Livewire\VendedoresEmpresas\Deudas::class)->name('deudas-empresa');
    });
});

// Rutas de Monitor
Route::prefix('monitor')->middleware(['client:monitor'])->group(function () {
    Route::get('/login', [MonitorAuthController::class, 'showLogin'])->name('login.monitor');
    Route::post('/authenticate', [MonitorAuthController::class, 'authenticate'])->name('authenticate.monitor');
    Route::get('/logout', [MonitorAuthController::class, 'logout'])->name('logout.monitor');

    Route::middleware(['client:monitor'])->group(function () {
        Route::get('/monitor', \App\Livewire\PanelResto\Monitor::class)->name('monitor.dashboard');
    });
});