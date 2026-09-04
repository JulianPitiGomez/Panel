<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PanelAuthController;
use App\Http\Controllers\Auth\MozoAuthController;
use App\Http\Controllers\Auth\VendedorAuthController;
use App\Http\Controllers\Auth\VendedorEmpresaAuthController;
use App\Http\Controllers\Auth\MonitorAuthController;
use App\Http\Controllers\SessionExpiredController;
use App\Http\Controllers\TiendaController;
use App\Http\Controllers\MercadoPagoController;


// Página principal pública
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Página de sesión expirada
Route::get('/session-expired', [SessionExpiredController::class, 'show'])->name('session.expired');

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
    Route::get('/panelresto', \App\Livewire\Panelresto\Dashboard::class)->name('panelresto.dashboard');
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
        Route::get('/cliente/nuevo', \App\Livewire\Vendedores\NuevoCliente::class)->name('vendedor.nuevo-cliente');
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

// Rutas de Tienda Web (E-commerce)
Route::prefix('web')->middleware([\App\Http\Middleware\TiendaMiddleware::class])->group(function () {
    Route::get('/{tienda}', [TiendaController::class, 'index'])->name('tienda.index');
    Route::post('/{tienda}/login', [TiendaController::class, 'login'])->name('tienda.login');
    Route::get('/{tienda}/logout', [TiendaController::class, 'logout'])->name('tienda.logout');
    Route::get('/{tienda}/catalogo', \App\Livewire\Tienda\Catalogo::class)->name('tienda.catalogo');
    Route::get('/{tienda}/carrito', \App\Livewire\Tienda\Carrito::class)->name('tienda.carrito');
    Route::get('/{tienda}/mis-pedidos', \App\Livewire\Tienda\MisPedidos::class)->name('tienda.mis-pedidos');
});

// Rutas de Kiosco público (sin middleware de panel)
Route::get('/kiosco', \App\Livewire\KioscoLogin::class)->name('kiosco.login');
Route::get('/kiosco/app', \App\Livewire\KioscoRestaurant::class)
    ->middleware(\App\Http\Middleware\KioscoMiddleware::class)
    ->name('kiosco.app');
Route::get('/kiosco/logout', function (\Illuminate\Http\Request $request) {
    $password = $request->query('password', '');
    $clientId = session('kiosco_client_id');

    if (!$clientId) {
        return response()->json(['ok' => true]);
    }

    // Obtener la contraseña configurada
    $configWeb = \Illuminate\Support\Facades\DB::connection('config')
        ->table('config_clientes_web')
        ->where('cliente_id', $clientId)
        ->first();

    $kioscoPassword = '';
    if ($configWeb && $configWeb->configuracion) {
        $config = json_decode($configWeb->configuracion, true);
        $kioscoPassword = $config['kiosco_password'] ?? '';
    }

    if ($kioscoPassword && $password !== $kioscoPassword) {
        return response()->json(['ok' => false, 'error' => 'Contraseña incorrecta']);
    }

    session()->forget(['kiosco_authenticated', 'kiosco_client_id', 'kiosco_session_id']);
    return response()->json(['ok' => true]);
})->name('kiosco.logout');

// Rutas de Mercado Pago (webhook + polling)
Route::post('/mp/webhook/{clientId}', [MercadoPagoController::class, 'webhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('mp.webhook');
Route::get('/mp/status/{clientId}/{reference}', [MercadoPagoController::class, 'consultarEstado'])
    ->name('mp.status');
Route::get('/mp/point-status/{clientId}/{deviceId}/{intentId}', [MercadoPagoController::class, 'consultarEstadoPoint'])
    ->name('mp.point-status');

// Rutas de Monitor
Route::prefix('monitor')->middleware(['client:monitor'])->group(function () {
    Route::get('/login', [MonitorAuthController::class, 'showLogin'])->name('login.monitor');
    Route::post('/authenticate', [MonitorAuthController::class, 'authenticate'])->name('authenticate.monitor');
    Route::get('/logout', [MonitorAuthController::class, 'logout'])->name('logout.monitor');

    Route::middleware(['client:monitor'])->group(function () {
        Route::get('/monitor', \App\Livewire\Panelresto\Monitor::class)->name('monitor.dashboard');
    });
});