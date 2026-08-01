<?php

namespace App\Models;

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
    protected $table      = 'usuarios';
    protected $primaryKey = 'id_usuario';

    /** La tabla no tiene created_at / updated_at. */
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
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
