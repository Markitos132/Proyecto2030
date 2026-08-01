<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabla `sesiones` en Supabase.
 *
 * `sesion_externa` guarda el session_id que genera el ESP32 (millis()).
 * Permite retomar una sesion aunque el servidor se reinicie.
 *
 * Los valores de `estado` en la base son 'activa' y 'finalizada'
 * (la UI original asumia 'MIDIENDO'; se usan las constantes de abajo).
 */
class Sesion extends Model
{
    protected $table      = 'sesiones';
    protected $primaryKey = 'id_sesion';

    public $timestamps = false;

    public const ESTADO_ACTIVA     = 'activa';
    public const ESTADO_FINALIZADA = 'finalizada';

    protected $fillable = [
        'id_individuo',
        'id_usuario',
        'id_dispositivo',
        'fecha_inicio',
        'fecha_fin',
        'intervalo_minuto',
        'duracion_sesion',
        'estado',
        'sesion_externa',
        'temp_min',
        'temp_max',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin'    => 'datetime',
        'temp_min'     => 'decimal:2',
        'temp_max'     => 'decimal:2',
    ];

    public function mediciones()
    {
        return $this->hasMany(Medicion::class, 'id_sesion');
    }

    public function ultimaMedicion()
    {
        return $this->hasOne(Medicion::class, 'id_sesion')->latestOfMany('fecha_hora');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function individuo()
    {
        return $this->belongsTo(Individuo::class, 'id_individuo');
    }

    public function dispositivo()
    {
        return $this->belongsTo(Dispositivo::class, 'id_dispositivo');
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVA);
    }

    public function scopeFinalizadas($query)
    {
        return $query->where('estado', self::ESTADO_FINALIZADA);
    }

    public function estaActiva(): bool
    {
        return $this->estado === self::ESTADO_ACTIVA;
    }

    /** Etiqueta para mostrar en la UI. */
    public function getEtiquetaEstadoAttribute(): string
    {
        return $this->estaActiva() ? 'MIDIENDO' : 'FINALIZADO';
    }
}
