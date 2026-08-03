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
// Recordatorio al cliente (no al comercial interno): una vez por semana, lunes 8am.
Schedule::command('app:notificar-clientes-cartera-pendiente')->weeklyOn(1, '08:00');
// Avisa a quien lleva 30+ min fichado en el kiosko sin registrar nada en Mi Día
// (con cooldown interno de 1h para no repetir el aviso cada 15 min).
Schedule::command('app:recordar-mi-dia-sin-actividad')->everyFifteenMinutes();
// Recuerda tickets abiertos asignados aunque ya se haya marcado como leída
// la notificación original de asignación (cooldown interno de 20h por ticket).
Schedule::command('app:recordar-tickets-abiertos')->twiceDaily(9, 15);
// Cierra conversaciones del chatbot sin actividad en 24h (bot, esperando humano o asignadas).
Schedule::command('app:cerrar-conversaciones-chatbot-inactivas')->hourly();
// Cada lunes gestiona como máximo 10 clientes con 30 días sin seguimiento.
Schedule::command('app:gestionar-clientes-inactivos-ia')->weeklyOn(1, '08:30')->timezone('America/Bogota')->withoutOverlapping();
// Congela el % de gestión de cartera del mes en curso (ver KpiService::guardarSnapshotCarteraMensual).
// Corre a diario cerca del cierre del día para que, al pasar de mes, el valor
// guardado quede fijo con el de la última corrida de ese mes.
Schedule::command('app:guardar-snapshot-cartera-mensual')->dailyAt('23:55');
