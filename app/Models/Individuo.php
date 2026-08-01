<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabla `individuos` en Supabase.
 *
 * `svl` esta en milimetros y `peso` en gramos.
 * `estado_reproductivo` solo aplica a hembras; queda null en el resto.
 */
class Individuo extends Model
{
    protected $table      = 'individuos';
    protected $primaryKey = 'id_individuo';

    public $timestamps = false;

    protected $fillable = [
        'codigo_individuo',
        'especie',
        'sexo',
        'estadio',
        'estado_reproductivo',
        'svl',
        'peso',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'svl'  => 'decimal:2',
        'peso' => 'decimal:2',
    ];

    public function sesiones()
    {
        return $this->hasMany(Sesion::class, 'id_individuo');
    }

    /** Sesion en curso, si la hay. */
    public function sesionActiva()
    {
        return $this->hasOne(Sesion::class, 'id_individuo')
                    ->where('estado', Sesion::ESTADO_ACTIVA);
    }

    public function notasIndividuo()
    {
        return $this->hasMany(NotaIndividuo::class, 'id_individuo');
    }
}
