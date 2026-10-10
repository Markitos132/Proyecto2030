<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos de perfil del usuario.
 *
 * La tabla `usuarios` venía de la versión Node con lo mínimo para
 * autenticar: nombre, email y password. El formulario de Configuración
 * pedía cuatro datos más que no tenían dónde guardarse.
 *
 * Sobre `rol`: es descriptivo, no da ni quita permisos. Hoy todo usuario
 * autenticado ve el panel entero. Sirve para saber quién firmó cada
 * sesión, no para restringir nada — conviene tenerlo presente antes de
 * asumir que "Estudiante / becario" está limitado en algo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            if (! Schema::hasColumn('usuarios', 'apellido')) {
                $table->string('apellido', 100)->nullable();
            }

            if (! Schema::hasColumn('usuarios', 'institucion')) {
                $table->string('institucion', 150)->nullable();
            }

            if (! Schema::hasColumn('usuarios', 'telefono')) {
                $table->string('telefono', 40)->nullable();
            }

            if (! Schema::hasColumn('usuarios', 'rol')) {
                $table->string('rol', 60)->nullable();
            }

            // El login tiene una casilla "Recordarme" desde el principio,
            // pero la tabla nunca tuvo dónde guardar el token: al tildarla,
            // Auth::attempt() intentaba escribir en una columna inexistente.
            if (! Schema::hasColumn('usuarios', 'remember_token')) {
                $table->rememberToken();
            }
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn([
                'apellido',
                'institucion',
                'telefono',
                'rol',
                'remember_token',
            ]);
        });
    }
};
