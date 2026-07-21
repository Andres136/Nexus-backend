<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Antes vivían en app/Console/kernel.php, un App\Console\Kernel que esta app
// (Laravel 11, bootstrap/app.php) nunca carga — el schedule real se declara
// aquí, en routes/console.php.
Schedule::command('notificar:ordenesporvencer')->dailyAt('08:00');
Schedule::command('app:verificar-facturas-cartera')->dailyAt('09:00');
