<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tabla `notas_campo_disp`. */
class NotaDispositivo extends Model
{
    protected $table      = 'notas_campo_disp';
    protected $primaryKey = 'id_nota_campo';

    public $timestamps = false;

    protected $fillable = [
        'id_dispositivo',
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

    public function dispositivo()
    {
        return $this->belongsTo(Dispositivo::class, 'id_dispositivo');
    }
}
