<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los dispositivos pasan a ser privados por usuario, como individuos.
 * El mismo equipo físico (misma MAC) puede registrarse por separado en
 * varias cuentas distintas — cada registro es independiente, con su
 * propia sesión activa y su propio historial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispositivos', function (Blueprint $table) {
            if (! Schema::hasColumn('dispositivos', 'id_usuario')) {
                $table->integer('id_usuario')->nullable();
                $table->foreign('id_usuario')->references('id_usuario')->on('usuarios');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dispositivos', function (Blueprint $table) {
            if (Schema::hasColumn('dispositivos', 'id_usuario')) {
                $table->dropForeign(['dispositivos_id_usuario_foreign']);
                $table->dropColumn('id_usuario');
            }
        });
    }
};