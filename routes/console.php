<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Consolidación horaria de los eventos de analítica en agregados diarios.
Schedule::command('analytics:rollup')->hourly();

/*
 * Reset nocturno de la demo pública: deja la cuenta compartida como estaba,
 * borrando lo que hayan tocado los visitantes del día. Solo se registra en el
 * entorno de la demo; en la productiva el `when()` lo deja afuera.
 */
Schedule::command('demo:reset', ['--force' => true])
    ->dailyAt(config('demo.reset_at'))
    ->when(fn () => (bool) config('demo.enabled'));
