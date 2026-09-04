<?php
// ================================================================
// routes/web.php
// ================================================================

use Illuminate\Support\Facades\Route;

// =============== AUTENTICACIÓN ===============
// Laravel Breeze maneja: /login, /register, /logout, etc.

Route::middleware(['auth', 'verified'])->group(function () {

    // =============== DASHBOARD ===============
    Route::get('/', App\Livewire\Dashboard\MainDashboard::class)->name('dashboard');

    // =============== CLIENTES ===============
    Route::prefix('clientes')->name('clients.')->group(function () {
        Route::get('/', App\Livewire\Clients\ClientIndex::class)->name('index');
        Route::get('/{id}/{system}', App\Livewire\Clients\ClientShow::class)->name('show');
    });

    // =============== ONBOARDING ===============
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/', App\Livewire\Onboarding\KanbanBoard::class)->name('kanban');
        Route::get('/capacitaciones', App\Livewire\Onboarding\TrainingScheduler::class)->name('training');
    });

    // =============== TICKETS (Atención al cliente) ===============
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', App\Livewire\Tickets\TicketIndex::class)->name('index');
        Route::get('/kanban', App\Livewire\Tickets\TicketKanban::class)->name('kanban');
        Route::get('/{ticket}', App\Livewire\Tickets\TicketShow::class)->name('show');
    });

    // =============== CONTACTOS / LEADS ===============
    Route::prefix('contactos')->name('contacts.')->group(function () {
        Route::get('/', App\Livewire\Contacts\ContactIndex::class)->name('index');
        Route::get('/{contact}', App\Livewire\Contacts\ContactShow::class)->name('show');
    });

    // =============== PENDIENTES DE DESARROLLO ===============
    Route::prefix('desarrollo')->name('dev-tasks.')->group(function () {
        Route::get('/', App\Livewire\DevTasks\DevTaskIndex::class)->name('index');
        Route::get('/{devTask}', App\Livewire\DevTasks\DevTaskShow::class)->name('show');
    });

    // =============== PAGOS ===============
    Route::prefix('pagos')->name('payments.')->group(function () {
        Route::get('/{id}/{system}/{tipo}', App\Livewire\Payments\PaymentForm::class)->name('form');
        Route::get('/detalle/{formapag}/{perfil}', App\Livewire\Payments\PaymentDetail::class)->name('detail');
    });

    // =============== FACTURACIÓN (solo SOCIOS) ===============
    Route::prefix('facturacion')->name('invoicing.')->middleware('role:socio')->group(function () {
        Route::get('/', App\Livewire\Invoicing\InvoiceIndex::class)->name('index');
        Route::get('/nueva', App\Livewire\Invoicing\InvoiceForm::class)->name('create');
        Route::get('/perfiles', App\Livewire\Invoicing\BillingProfileManager::class)->name('profiles');
    });

    // =============== GASTOS ===============
    Route::prefix('gastos')->name('expenses.')->middleware('role:socio|operador')->group(function () {
        Route::get('/', App\Livewire\Expenses\ExpenseIndex::class)->name('index');
    });

    // =============== CAJA ===============
    Route::prefix('caja')->name('cash.')->group(function () {
        Route::get('/', App\Livewire\Cash\CurrentCash::class)->name('index');
        Route::get('/cierres', App\Livewire\Cash\ClosingHistory::class)->name('closings');
        Route::get('/cierres/{closing}', App\Livewire\Cash\ClosingDetail::class)->name('closing-detail');
    });

    // =============== DEUDAS ===============
    Route::prefix('deudas')->name('debts.')->group(function () {
        Route::get('/', App\Livewire\Debts\DebtIndex::class)->name('index');
        Route::get('/{id}/{system}', App\Livewire\Debts\DebtDetail::class)->name('detail');
    });

    // =============== MERCADO PAGO ===============
    Route::prefix('mercadopago')->name('mp.')->middleware('role:socio')->group(function () {
        Route::get('/', App\Livewire\MercadoPago\AccountManager::class)->name('index');
    });

    // =============== PERSONAL ===============
    Route::prefix('personal')->name('personnel.')->group(function () {
        Route::get('/', App\Livewire\Personnel\PersonIndex::class)->name('index');
        Route::get('/guardias', App\Livewire\Personnel\GuardSchedule::class)->name('guards');
        Route::get('/liquidaciones/{responsable?}', App\Livewire\Personnel\CommissionSettlement::class)->name('settlements');
        Route::get('/sueldos', App\Livewire\Personnel\PayrollIndex::class)->name('payroll');
    });

    // =============== ADMINISTRACIÓN (solo SOCIOS) ===============
    Route::prefix('admin')->name('admin.')->middleware('role:socio')->group(function () {
        Route::get('/usuarios', App\Livewire\Admin\UserManager::class)->name('users');
        Route::get('/configuracion', App\Livewire\Admin\SystemSettings::class)->name('settings');
    });
});


// ================================================================
// routes/api.php
// Webhooks de LiveConnect (sin autenticación de sesión)
// ================================================================

use App\Http\Controllers\Api\LiveConnectWebhookController;

Route::prefix('liveconnect')->group(function () {
    Route::post('/contacto-crear', [LiveConnectWebhookController::class, 'contactoCrear']);
    Route::post('/contacto-descartado', [LiveConnectWebhookController::class, 'contactoDescartado']);
    Route::post('/contacto-alta-cliente', [LiveConnectWebhookController::class, 'altaCliente']);
    Route::post('/identifica-contacto', [LiveConnectWebhookController::class, 'identificaContacto']);
    Route::post('/contacto-cliente', [LiveConnectWebhookController::class, 'contactoCliente']);
    Route::post('/no-contacto-cliente', [LiveConnectWebhookController::class, 'noContactoCliente']);
});
