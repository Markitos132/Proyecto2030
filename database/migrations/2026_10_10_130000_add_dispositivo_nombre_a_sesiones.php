<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nombre del dispositivo que midió cada sesión, como copia.
 *
 * Al eliminar un dispositivo, sus sesiones se conservan pero pierden la
 * referencia (id_dispositivo queda en null). Esta columna guarda el
 * nombre que tenía en ese momento, para que el historial siga mostrando
 * con qué equipo se midió. Solo se completa al eliminar un dispositivo;
 * mientras el dispositivo exista, el nombre se lee de él.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sesiones', function (Blueprint $table) {
            if (! Schema::hasColumn('sesiones', 'dispositivo_nombre')) {
                $table->string('dispositivo_nombre', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sesiones', function (Blueprint $table) {
            $table->dropColumn('dispositivo_nombre');
        });
    }
};
