<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotasIndividuo extends Model
{
    public $timestamps = false;

    protected $table = 'NOTAS_CAMPO_INDIVIDUO';
    protected $primaryKey = 'id_nota_individuo';

    protected $fillable = [
        'id_individuo',
        'id_usuario',
        'contenido'
    ];

    protected $casts = [
        'fecha_alta' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function individuo()
    {
        return $this->belongsTo(Individuo::class, 'id_individuo');
    }
}