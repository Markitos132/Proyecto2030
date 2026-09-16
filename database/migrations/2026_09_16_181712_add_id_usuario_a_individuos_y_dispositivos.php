<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aislar datos por usuario: cada investigador ve solo lo que dio de alta.
 *
 * `sesiones` ya tenía id_usuario desde el esquema original. Faltaban
 * individuos y dispositivos, que hasta ahora eran globales para
 * cualquier usuario autenticado.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['individuos', 'dispositivos'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                if (! Schema::hasColumn($tabla, 'id_usuario')) {
                    $table->integer('id_usuario')->nullable();
                    $table->foreign('id_usuario')
                        ->references('id_usuario')->on('usuarios');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['individuos', 'dispositivos'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                if (Schema::hasColumn($tabla, 'id_usuario')) {
                    $table->dropForeign([$tabla . '_id_usuario_foreign']);
                    $table->dropColumn('id_usuario');
                }
            });
        }
    }
};