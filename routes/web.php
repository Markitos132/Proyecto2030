<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

Route::view('/login', 'auth.login');

Route::view('/registro', 'auth.registro');

//vistas momentaneas

Route::view('/dashboard', 'admin.dashboard')->name('dashboard');

Route::view('/individuos', 'admin.individuos')->name('individuos');

Route::view('/dispositivos', 'admin.dispositivos')->name('dispositivos');

Route::view('/sesionesactivas', 'admin.sesiones')->name('sesiones activas');

Route::view('/historial', 'admin.historial')->name('historial');

Route::view('/configuracion', 'admin.configuracion')->name('configuracion');

Route::view('/ficha', 'admin.individuo_ficha')->name('ficha');

Route::view('/fichadisp', 'admin.dispositivo_ficha')->name('fichadisp');