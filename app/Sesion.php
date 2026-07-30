<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sesion extends Model
{
    protected $table = 'SESIONES';
    protected $primaryKey = 'id_sesion';

    protected $fillable = [
        'id_individuo',
        'id_usuario',
        'id_dispositivo',
        'fecha_inicio',
        'fecha_fin',
        'intervalo_minuto',
        'duracion_sesion',
        'estado'
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
    ];

    public function mediciones()
    {
        return $this->hasMany(Medicion::class, 'id_sesion');
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

    public function ultimaMedicion()
    {
        return $this->hasOne(Medicion::class, 'id_sesion')->latestOfMany('fecha_hora');
    }
}
