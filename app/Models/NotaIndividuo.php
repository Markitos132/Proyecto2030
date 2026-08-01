<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tabla `notas_campo_individuo`. */
class NotaIndividuo extends Model
{
    protected $table      = 'notas_campo_individuo';
    protected $primaryKey = 'id_nota_individuo';

    public $timestamps = false;

    protected $fillable = [
        'id_individuo',
        'id_usuario',
        'fecha_alta',
        'contenido',
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
