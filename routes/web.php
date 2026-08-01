<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
|  Rutas web — BioNEA Organiks
|--------------------------------------------------------------------------
*/

// ── Público ────────────────────────────────────────────────
Route::view('/', 'landing')->name('landing');

// ── Autenticación ──────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'mostrarLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/registro',  [AuthController::class, 'mostrarRegistro'])->name('registro');
    Route::post('/registro', [AuthController::class, 'registro']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ── Panel ──────────────────────────────────────────────────
// Antes eran Route::view sin protección: cualquiera con la URL entraba.
// Los controladores con datos reales llegan en el paso siguiente;
// por ahora quedan como vistas, pero ya detrás del middleware auth.
Route::middleware('auth')->group(function () {
    Route::view('/dashboard',       'admin.dashboard')->name('dashboard');
    Route::view('/individuos',      'admin.individuos')->name('individuos');
    Route::view('/dispositivos',    'admin.dispositivos')->name('dispositivos');
    Route::view('/sesionesactivas', 'admin.sesiones')->name('sesiones');
    Route::view('/historial',       'admin.historial')->name('historial');
    Route::view('/configuracion',   'admin.configuracion')->name('configuracion');
    Route::view('/ficha',           'admin.individuo_ficha')->name('ficha');
    Route::view('/fichadisp',       'admin.dispositivo_ficha')->name('fichadisp');
});
