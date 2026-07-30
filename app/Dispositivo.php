<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dispositivo extends Model
{
    protected $table = 'DISPOSITIVOS';
    protected $primaryKey = 'id_dispositivo';

    protected $fillable = [
        'codigo_disp',
        'MAC',
        'estado_d',
        'f_alta',
        'observaciones',
        'ultima_conexion'
    ];

    protected $casts = [
        'f_alta' => 'datetime',
        'ultima_conexion' => 'datetime',
    ];

    public function sesiones()
    {
        return $this->hasMany(Sesion::class, 'id_dispositivo');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function sesionActiva()
    {
      return $this->hasOne(Sesion::class, 'id_dispositivo')->where('estado', 'MIDIENDO');
    }

    public function notasDisp()
    {
        return $this->hasMany(NotasDisp::class, 'id_dispositivo');
    }

    public function getUltimaConexionHumanAttribute()
    {
        if (!$this->ultima_conexion) {
            return 'nunca conectado';
        }

        return $this->ultima_conexion->diffForHumans(null, true);
    }

    public function getEstadoCalculadoAttribute()
    {
        // 1. Primero chequeamos si está conectado, sin importar sesiones
        if (!$this->ultima_conexion) {
            return 'offline';
        }

        $minutosSinSenal = now()->diffInMinutes($this->ultima_conexion);

        if ($minutosSinSenal > 15) {
            return 'offline';
        }

        // 2. Está conectado. Ahora vemos si además está midiendo bien
        $sesion = $this->sesionActiva;

        if (!$sesion) {
            return 'online'; // conectado, disponible, sin sesión
        }

        $ultimaMedicion = $sesion->ultimaMedicion;

        if (!$ultimaMedicion) {
            return 'online'; // tiene sesión pero recién arrancó, sin mediciones aún
        }

        $minutosSinMedicion = now()->diffInMinutes($ultimaMedicion->fecha_hora);

        if ($minutosSinMedicion > $sesion->intervalo_minuto * 2) {
            return 'warning'; // conectado, pero la sesión no está reportando a tiempo
        }

        return 'online';
    }
}
