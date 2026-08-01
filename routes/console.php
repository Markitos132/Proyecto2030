<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
|  Tareas programadas
|--------------------------------------------------------------------------
|
|  Requieren un proceso que ejecute `php artisan schedule:run` cada minuto.
|  En Render, el plan gratuito no ofrece cron jobs, así que el cierre de
|  sesiones también se dispara de forma oportunista al cargar el panel
|  (ver App\Services\CierreDeSesiones::revisarSiCorresponde).
|
*/

Schedule::command('bionea:cerrar-sesiones')
    ->everyFiveMinutes()
    ->withoutOverlapping();
