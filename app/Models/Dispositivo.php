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

    public const UMBRAL_OFFLINE_MIN = 15;

    protected $fillable = [
        'nombre',
        'mac_address',
        'estado',
        'f_alta',
        'observaciones',
        'ultima_conexion',
        'id_usuario',
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

    public function notasDisp()
    {
        return $this->hasMany(NotaDispositivo::class, 'id_dispositivo');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function getUltimaConexionHumanAttribute(): string
    {
        if (! $this->ultima_conexion) {
            return 'nunca conectado';
        }

        return $this->ultima_conexion->diffForHumans(null, true);
    }

    public function getEstadoCalculadoAttribute(): string
    {
        if (! $this->ultima_conexion) {
            return 'offline';
        }

        if ($this->ultima_conexion->diffInMinutes(now()) > self::UMBRAL_OFFLINE_MIN) {
            return 'offline';
        }

        $sesion = $this->sesionActiva;

        if (! $sesion) {
            return 'online';
        }

        $ultima = $sesion->ultimaMedicion;

        if (! $ultima) {
            return 'online';
        }

        $intervalo = $sesion->intervalo_minuto ?: 10;

        return $ultima->fecha_hora->diffInMinutes(now()) > $intervalo * 2
            ? 'warning'
            : 'online';
    }
}