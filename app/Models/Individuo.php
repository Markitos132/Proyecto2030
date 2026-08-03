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

    /** Estados válidos de un ejemplar. Los valida el controlador al guardar. */
    public const ESTADOS = ['activo', 'recapturado', 'liberado'];

    /**
     * Clase CSS de la píldora de estado.
     *
     * Vivía duplicada como un @if en la lista y en la ficha, y en las dos
     * contemplaba solo 'liberado': un ejemplar recapturado caía en el else
     * y salía en verde, igual que uno activo, aunque el CSS ya tenía
     * .status-ind-recapturado en ámbar esperando que alguien lo usara.
     */
    public function getClaseEstadoAttribute(): string
    {
        return match ($this->estado) {
            'liberado'    => 'status-ind-liberado',
            'recapturado' => 'status-ind-recapturado',
            default       => 'status-ind-activo',
        };
    }

    /** Texto de la píldora. En la base el estado se guarda en minúsculas. */
    public function getEtiquetaEstadoAttribute(): string
    {
        return match ($this->estado) {
            'liberado'    => 'Liberado / Perdido',
            'recapturado' => 'Recapturado',
            'activo'      => 'Activo',
            default       => ucfirst((string) $this->estado),
        };
    }

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
