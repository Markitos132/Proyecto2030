<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DispositivoController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\IndividuoController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\SesionController;
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
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ── Panel ──────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // El alta de usuarios no es pública: se hace desde el panel.
    Route::get('/usuarios/nuevo',  [AuthController::class, 'mostrarRegistro'])->name('registro');
    Route::post('/usuarios/nuevo', [AuthController::class, 'registro']);

    // ── Individuos ─────────────────────────────────────────
    Route::get('/individuos', [IndividuoController::class, 'index'])->name('individuos');
    // Alias: algunas vistas enlazan como individuos.index
    Route::get('/individuos/listado', [IndividuoController::class, 'index'])->name('individuos.index');
    Route::post('/individuos', [IndividuoController::class, 'store'])->name('individuos.store');
    Route::get('/individuos/{individuo}', [IndividuoController::class, 'show'])->name('individuos.show');
    Route::put('/individuos/{individuo}', [IndividuoController::class, 'update'])->name('individuos.update');
    Route::delete('/individuos/{individuo}', [IndividuoController::class, 'destroy'])->name('individuos.destroy');

    // ── Dispositivos ───────────────────────────────────────
    Route::get('/dispositivos', [DispositivoController::class, 'index'])->name('dispositivos');
    Route::post('/dispositivos', [DispositivoController::class, 'store'])->name('dispositivos.store');
    Route::get('/dispositivos/{dispositivo}', [DispositivoController::class, 'show'])->name('dispositivos.show');
    Route::put('/dispositivos/{dispositivo}', [DispositivoController::class, 'update'])->name('dispositivos.update');
    Route::delete('/dispositivos/{dispositivo}', [DispositivoController::class, 'destroy'])->name('dispositivos.destroy');

    // ── Sesiones ───────────────────────────────────────────
    Route::get('/sesionesactivas', [SesionController::class, 'index'])->name('sesiones');
    Route::post('/sesiones', [SesionController::class, 'store'])->name('sesiones.store');
    Route::get('/sesiones/{sesion}', [SesionController::class, 'show'])->name('sesiones.show');
    Route::put('/sesiones/{sesion}/finalizar', [SesionController::class, 'finalizar'])->name('sesiones.finalizar');

    // ── Notas de campo ─────────────────────────────────────
    Route::post('/individuos/{individuo}/notas', [NotaController::class, 'storeIndividuo'])->name('notasindividuo.store');
    Route::post('/dispositivos/{dispositivo}/notas', [NotaController::class, 'storeDispositivo'])->name('notasdisp.store');

    // ── Historial y configuración ──────────────────────────
    Route::get('/historial', [HistorialController::class, 'index'])->name('historial');
    Route::view('/configuracion', 'admin.configuracion')->name('configuracion');
});
