<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Se elimina la alerta de rango de temperatura por completo: dependía de
 * temp_min/temp_max, que ya no se definen al crear una sesión (se
 * reemplazan por el mínimo/máximo real de las mediciones, calculado en
 * PanelEstadoController). Sin umbral, ni la columna `alerta` de mediciones
 * ni `temp_min`/`temp_max` de sesiones tienen ya ningún uso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mediciones', function (Blueprint $table) {
            if (Schema::hasColumn('mediciones', 'alerta')) {
                $table->dropColumn('alerta');
            }
        });

        Schema::table('sesiones', function (Blueprint $table) {
            if (Schema::hasColumn('sesiones', 'temp_min')) {
                $table->dropColumn('temp_min');
            }
            if (Schema::hasColumn('sesiones', 'temp_max')) {
                $table->dropColumn('temp_max');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mediciones', function (Blueprint $table) {
            if (! Schema::hasColumn('mediciones', 'alerta')) {
                $table->string('alerta', 50)->nullable();
            }
        });

        Schema::table('sesiones', function (Blueprint $table) {
            if (! Schema::hasColumn('sesiones', 'temp_min')) {
                $table->decimal('temp_min', 5, 2)->nullable();
            }
            if (! Schema::hasColumn('sesiones', 'temp_max')) {
                $table->decimal('temp_max', 5, 2)->nullable();
            }
        });
    }
};