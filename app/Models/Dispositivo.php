<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabla `dispositivos` en Supabase.
 *
 * Las columnas reales son `nombre` y `mac_address`
 * (no `codigo_disp` / `MAC`, que era el nombre supuesto en el diseno original).
 */
class Dispositivo extends Model
{
    protected $table      = 'dispositivos';
    protected $primaryKey = 'id_dispositivo';

    public $timestamps = false;

    /** Minutos sin señal a partir de los cuales se considera offline. */
    public const UMBRAL_OFFLINE_MIN = 15;

    protected $fillable = [
        'nombre',
        'mac_address',
        'estado',
        'f_alta',
        'observaciones',
        'ultima_conexion',
    ];

    protected $casts = [
        'f_alta'          => 'datetime',
        'ultima_conexion' => 'datetime',
    ];

    public function sesiones()
    {
        return $this->hasMany(Sesion::class, 'id_dispositivo');
    }

    public function sesionActiva()
    {
        return $this->hasOne(Sesion::class, 'id_dispositivo')
                    ->where('estado', Sesion::ESTADO_ACTIVA);
    }

    public function notas()
    {
        return $this->hasMany(NotaDispositivo::class, 'id_dispositivo');
    }

    public function getUltimaConexionHumanAttribute(): string
    {
        if (! $this->ultima_conexion) {
            return 'nunca conectado';
        }

        return $this->ultima_conexion->diffForHumans(null, true);
    }

    /**
     * Estado real del dispositivo, derivado de la actividad reciente
     * en lugar de la columna `estado`, que puede quedar desactualizada.
     *
     * offline  → sin señal hace mas de UMBRAL_OFFLINE_MIN minutos
     * warning  → conectado, pero la sesion no reporta a tiempo
     * online   → todo en orden
     */
    public function getEstadoCalculadoAttribute(): string
    {
        if (! $this->ultima_conexion) {
            return 'offline';
        }

        if ($this->ultima_conexion->diffInMinutes(now()) > self::UMBRAL_OFFLINE_MIN) {
            return 'offline';
        }

        $sesion = $this->sesionActiva;

        // Conectado y disponible, sin sesion en curso.
        if (! $sesion) {
            return 'online';
        }

        $ultima = $sesion->ultimaMedicion;

        // Sesion recien arrancada, todavia sin mediciones.
        if (! $ultima) {
            return 'online';
        }

        $intervalo = $sesion->intervalo_minuto ?: 10;

        return $ultima->fecha_hora->diffInMinutes(now()) > $intervalo * 2
            ? 'warning'
            : 'online';
    }
}
