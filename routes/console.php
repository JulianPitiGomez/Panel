<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ====================== Tienda web ======================
// Requiere el cron "* * * * * php artisan schedule:run" en el servidor. Si no esta, correr los
// comandos directamente desde el cron con la misma frecuencia.
Schedule::command('tienda:avisos-pendientes')->everyFiveMinutes()->withoutOverlapping();   // cambios de estado hechos desde gestion
Schedule::command('tienda:carritos-abandonados')->dailyAt('10:30');                        // WhatsApp de carrito abandonado (F7)
