<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Esquema del dominio BioNEA.
 *
 * Estas tablas YA EXISTEN en el Supabase de produccion; fueron creadas a mano
 * antes de que el proyecto usara Laravel. Esta migracion las documenta y
 * permite levantar el proyecto desde cero en otra base.
 *
 * Por eso cada bloque esta protegido con Schema::hasTable(): correrla contra
 * la base actual es un no-op seguro, y contra una base vacia la construye
 * completa.
 *
 * Los nombres van en minuscula a proposito. Postgres pliega a minuscula todo
 * identificador sin comillas, asi que usar mayusculas obligaria a citar cada
 * tabla entre comillas dobles para siempre.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── usuarios ────────────────────────────────────────────────
        if (! Schema::hasTable('usuarios')) {
            Schema::create('usuarios', function (Blueprint $table) {
                $table->increments('id_usuario');
                $table->string('nombre', 255)->nullable();
                $table->string('email', 255)->nullable();
                $table->string('password', 255)->nullable();
            });
        }

        // ── dispositivos ────────────────────────────────────────────
        if (! Schema::hasTable('dispositivos')) {
            Schema::create('dispositivos', function (Blueprint $table) {
                $table->increments('id_dispositivo');
                $table->string('mac_address', 255)->nullable();
                $table->string('nombre', 255)->nullable();
                $table->string('estado', 255)->nullable();
                $table->timestamp('f_alta')->useCurrent();
                $table->timestamp('ultima_conexion')->nullable();
                $table->text('observaciones')->nullable();
            });
        }

        // ── individuos ──────────────────────────────────────────────
        if (! Schema::hasTable('individuos')) {
            Schema::create('individuos', function (Blueprint $table) {
                $table->increments('id_individuo');
                $table->string('codigo_individuo', 50)->nullable();
                $table->string('especie', 255)->nullable();
                $table->string('estado', 255)->nullable();
                $table->string('sexo', 255)->nullable();
                $table->string('estadio', 50)->nullable();
                $table->string('estado_reproductivo', 50)->nullable();
                $table->decimal('svl', 6, 2)->nullable();   // longitud hocico-cloaca, mm
                $table->decimal('peso', 6, 2)->nullable();  // gramos
                $table->string('observaciones', 255)->nullable();

                $table->index('codigo_individuo', 'idx_individuos_codigo');
            });
        }

        // ── sesiones ────────────────────────────────────────────────
        if (! Schema::hasTable('sesiones')) {
            Schema::create('sesiones', function (Blueprint $table) {
                $table->increments('id_sesion');
                $table->integer('id_individuo')->nullable();
                $table->integer('id_dispositivo')->nullable();
                $table->integer('id_usuario')->nullable();
                $table->timestamp('fecha_inicio')->nullable();
                $table->timestamp('fecha_fin')->nullable();
                $table->integer('intervalo_minuto')->nullable();
                $table->integer('duracion_sesion')->nullable();
                $table->string('estado', 255)->nullable();
                // session_id que genera el ESP32 con millis(); permite
                // retomar la sesion aunque el servidor se reinicie.
                $table->text('sesion_externa')->nullable();
                $table->decimal('temp_min', 5, 2)->nullable();
                $table->decimal('temp_max', 5, 2)->nullable();

                $table->foreign('id_individuo')->references('id_individuo')->on('individuos');
                $table->foreign('id_dispositivo')->references('id_dispositivo')->on('dispositivos');
                $table->foreign('id_usuario')->references('id_usuario')->on('usuarios');

                $table->index('estado', 'idx_sesiones_estado');
                $table->index('sesion_externa', 'idx_sesiones_externa');
            });
        }

        // ── mediciones ──────────────────────────────────────────────
        if (! Schema::hasTable('mediciones')) {
            Schema::create('mediciones', function (Blueprint $table) {
                $table->increments('id_medicion');
                $table->integer('id_sesion')->nullable();
                $table->timestamp('fecha_hora')->nullable();
                $table->decimal('temperatura', 5, 2)->nullable();
                $table->text('alerta')->default('OK');

                $table->foreign('id_sesion')->references('id_sesion')->on('sesiones');

                // Indice compuesto: es el patron de acceso dominante
                // (ultima medicion de una sesion, series para graficos).
                $table->index(['id_sesion', 'fecha_hora'], 'idx_mediciones_sesion_fecha');
            });
        }

        // ── notas de campo ──────────────────────────────────────────
        if (! Schema::hasTable('notas_campo_individuo')) {
            Schema::create('notas_campo_individuo', function (Blueprint $table) {
                $table->increments('id_nota_individuo');
                $table->integer('id_individuo')->nullable();
                $table->integer('id_usuario')->nullable();
                $table->timestamp('fecha_alta')->useCurrent();
                $table->text('contenido');

                $table->foreign('id_individuo')->references('id_individuo')
                      ->on('individuos')->cascadeOnDelete();
                $table->foreign('id_usuario')->references('id_usuario')->on('usuarios');

                $table->index('id_individuo', 'idx_notas_individuo');
            });
        }

        if (! Schema::hasTable('notas_campo_disp')) {
            Schema::create('notas_campo_disp', function (Blueprint $table) {
                $table->increments('id_nota_campo');
                $table->integer('id_dispositivo')->nullable();
                $table->integer('id_usuario')->nullable();
                $table->timestamp('fecha_alta')->useCurrent();
                $table->text('contenido');

                $table->foreign('id_dispositivo')->references('id_dispositivo')
                      ->on('dispositivos')->cascadeOnDelete();
                $table->foreign('id_usuario')->references('id_usuario')->on('usuarios');

                $table->index('id_dispositivo', 'idx_notas_disp');
            });
        }

        // ── Row Level Security ──────────────────────────────────────
        // La app se conecta como `postgres`, que tiene BYPASSRLS, asi que
        // esto no la afecta. Lo que bloquea es el acceso directo con las
        // claves anon / authenticated de Supabase, que de otro modo
        // dejarian toda la base expuesta desde el navegador.
        if (DB::getDriverName() === 'pgsql') {
            $tablas = [
                'usuarios', 'dispositivos', 'individuos', 'sesiones',
                'mediciones', 'notas_campo_individuo', 'notas_campo_disp',
            ];

            foreach ($tablas as $tabla) {
                DB::statement("ALTER TABLE public.{$tabla} ENABLE ROW LEVEL SECURITY");
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_campo_disp');
        Schema::dropIfExists('notas_campo_individuo');
        Schema::dropIfExists('mediciones');
        Schema::dropIfExists('sesiones');
        Schema::dropIfExists('individuos');
        Schema::dropIfExists('dispositivos');
        Schema::dropIfExists('usuarios');
    }
};
