<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Individuo extends Model
{
    public $timestamps = false;
    
    protected $table = 'INDIVIDUO';
    protected $primaryKey = 'id_individuo';

    protected $fillable = [
        'codigo_individuo',
        'especie',
        'sexo',
        'estadio',
        'estado_reproductivo',
        'svl',
        'peso',
        'observaciones',
        'estado'
    ];

    public function sesiones()
    {
        return $this->hasMany(Sesion::class, 'id_individuo');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    // Sesión en curso
    public function sesionActiva()
    {
        return $this->hasOne(Sesion::class, 'id_individuo')->where('estado', 'MIDIENDO');
    }

    // Notas del individuo (si tenés la tabla de notas para el individuo)
    public function notasIndividuo()
    {
        return $this->hasMany(NotasIndividuo::class, 'id_individuo');
    }

}
