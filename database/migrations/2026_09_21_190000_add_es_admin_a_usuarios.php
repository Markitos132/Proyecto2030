<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bandera de administrador, para restringir el alta de usuarios.
 *
 * Ojo con la columna `rol` que ya existe (ver add_perfil_a_usuarios):
 * es puramente descriptiva — "Investigador principal", "Estudiante /
 * becario" — no da ni quita permisos. Esta es otra cosa a propósito:
 * un booleano simple, sin relación con `rol`, para no mezclar "cómo
 * se presenta el usuario" con "qué puede hacer en el sistema".
 *
 * Se eligió una columna en `usuarios` y no una tabla aparte
 * (administradores) porque solo hay dos estados posibles y una tabla
 * separada obligaría a un JOIN (o una consulta extra) en cada chequeo
 * de permisos, sin ganar nada a cambio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            if (! Schema::hasColumn('usuarios', 'es_admin')) {
                $table->boolean('es_admin')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('es_admin');
        });
    }
};
