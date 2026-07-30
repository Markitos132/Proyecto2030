<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'USUARIOS';
    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'nombre',
        'email',
        'pwd'
    ];

    public function sesiones()
    {
        return $this->hasMany(Sesion::class, 'id_usuario');
    }

    public function individuos()
    {
        return $this->hasMany(Individuo::class, 'id_usuario');
    }

    public function dispositivos()
    {
        return $this->hasMany(Dispositivo::class, 'id_usuario');
    }
}
