<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Tabla `usuarios` en Supabase.
 *
 * Ojo: la columna de clave es `password` (no `pwd`), y los hashes
 * existentes fueron generados con bcrypt desde la version Node.
 * Hash::check() de Laravel los valida sin problema.
 */
class Usuario extends Authenticatable
{
    use HasFactory;

    protected $table      = 'usuarios';
    protected $primaryKey = 'id_usuario';

    /** La tabla no tiene created_at / updated_at. */
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'password',
        'institucion',
        'telefono',
        'rol',
        'notif_fuera_rango',
        'notif_sin_reportar',
        'notif_resumen_diario',
        'notif_push',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password'             => 'hashed',
            'notif_fuera_rango'    => 'boolean',
            'notif_sin_reportar'   => 'boolean',
            'notif_resumen_diario' => 'boolean',
            'notif_push'           => 'boolean',
        ];
    }

    /**
     * Roles que ofrece el formulario de perfil.
     *
     * Son descriptivos: hoy no restringen nada. Cualquier usuario
     * autenticado ve el panel completo.
     */
    public const ROLES = [
        'Investigador principal',
        'Investigador asistente',
        'Estudiante / becario',
    ];

    /**
     * Nombre para mostrar.
     *
     * Las cuentas creadas antes de que existiera la columna `apellido`
     * guardaron el nombre completo en `nombre`, así que concatenar a ciegas
     * no rompe nada: en esas filas `apellido` es null.
     */
    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombre.' '.($this->apellido ?? ''));
    }

    /**
     * Iniciales para el avatar: "Marcos Ortiz" -> "MO".
     */
    public function getInicialesAttribute(): string
    {
        $iniciales = collect(preg_split('/\s+/', $this->nombre_completo))
            ->filter()
            ->take(2)
            ->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
            ->implode('');

        return $iniciales !== '' ? $iniciales : '?';
    }

    public function sesiones()
    {
        return $this->hasMany(Sesion::class, 'id_usuario');
    }

    public function notasIndividuo()
    {
        return $this->hasMany(NotaIndividuo::class, 'id_usuario');
    }

    public function notasDispositivo()
    {
        return $this->hasMany(NotaDispositivo::class, 'id_usuario');
    }
}
