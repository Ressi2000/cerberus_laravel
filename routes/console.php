<?php

use App\Models\Prestamo;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('cerberus:notificar-alertas')->dailyAt('07:00');

Schedule::command('cerberus:generar-mantenimientos-programados')->dailyAt('06:30');

Schedule::call(fn () => Prestamo::marcarVencidosAutomaticamente())
    ->hourly()
    ->name('prestamos:marcar-vencidos');

// Monitor de red: revisión de disponibilidad cada minuto (ping + TCP de respaldo)
// y purga diaria de mediciones de latencia antiguas.
Schedule::command('cerberus:monitor-red')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->runInBackground();

Schedule::command('cerberus:monitor-red --purgar')->dailyAt('03:15');
