<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migracion por defecto de Laravel, recortada.
 *
 * La tabla `users` NO se crea: en BioNEA los usuarios viven en `usuarios`,
 * que ya existe en Supabase y se define en la migracion del esquema del
 * dominio. El modelo App\Models\Usuario apunta ahi.
 *
 * Se conservan `sessions` (SESSION_DRIVER=database) y
 * `password_reset_tokens`, que son infraestructura del framework.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                // integer, no foreignId: usuarios.id_usuario es serial (int4)
                $table->integer('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
