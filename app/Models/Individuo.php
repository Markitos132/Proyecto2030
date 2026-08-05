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

    /**
     * Estados válidos de un ejemplar. Los valida el controlador al guardar.
     *
     * Son dos a propósito. Hubo un tercero, 'recapturado', que quedó a
     * medias: estaba en el formulario de la ficha y tenía su color en el
     * CSS, pero no en el filtro de la lista. Al conectar el CRUD se tomó el
     * formulario como fuente de verdad y se completó en los demás lugares,
     * sin saber que el equipo ya había decidido dejar solo dos casos.
     * Se retiró de todos lados; ninguna fila de la base lo usaba.
     */
    public const ESTADOS = ['activo', 'liberado'];

    /**
     * Clase CSS de la píldora de estado.
     *
     * Vivía duplicada como un @if en la lista y en la ficha, y las dos
     * copias comparaban contra el texto 'Liberado/Perdido' mientras el
     * desplegable guardaba 'liberado', así que la comparación no daba nunca
     * y todos los ejemplares salían en verde.
     */
    public function getClaseEstadoAttribute(): string
    {
        return match ($this->estado) {
            'liberado' => 'status-ind-liberado',
            default    => 'status-ind-activo',
        };
    }

    /** Texto de la píldora. En la base el estado se guarda en minúsculas. */
    public function getEtiquetaEstadoAttribute(): string
    {
        return match ($this->estado) {
            'liberado' => 'Liberado / Perdido',
            'activo'   => 'Activo',
            default    => ucfirst((string) $this->estado),
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
