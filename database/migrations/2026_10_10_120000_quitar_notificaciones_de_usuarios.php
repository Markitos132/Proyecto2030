<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina las columnas notif_* de `usuarios`.
 *
 * Las preferencias de notificación se quitaron de Configuración y no se
 * van a implementar. Las bases que ya corrieron add_perfil_a_usuarios
 * (como producción) todavía tienen estas columnas; las instalaciones
 * nuevas ya no las crean, por eso cada una se borra solo si existe.
 */
return new class extends Migration
{
    private const COLUMNAS = [
        'notif_fuera_rango',
        'notif_sin_reportar',
        'notif_resumen_diario',
        'notif_push',
    ];

    public function up(): void
    {
        $existentes = array_values(array_filter(
            self::COLUMNAS,
            fn ($columna) => Schema::hasColumn('usuarios', $columna)
        ));

        if ($existentes === []) {
            return;
        }

        Schema::table('usuarios', function (Blueprint $table) use ($existentes) {
            $table->dropColumn($existentes);
        });
    }

    public function down(): void
    {
        // Sin vuelta atrás: la función se quitó del proyecto.
    }
};
