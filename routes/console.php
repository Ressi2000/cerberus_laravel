<?php

use App\Models\Prestamo;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('cerberus:notificar-alertas')->dailyAt('07:00');

Schedule::call(fn () => Prestamo::marcarVencidosAutomaticamente())
    ->hourly()
    ->name('prestamos:marcar-vencidos');
