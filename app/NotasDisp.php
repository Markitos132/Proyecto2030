<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotasDisp extends Model
{
    public $timestamps = false;    
    
    protected $table = 'NOTAS_CAMPO_DISP';
    protected $primaryKey = 'id_nota_campo';

    protected $fillable = [
        'id_dispositivo',
        'id_usuario',
        'fecha_alta',
        'contenido'
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
