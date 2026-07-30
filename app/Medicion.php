<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Medicion extends Model
{
    protected $table = 'MEDICIONES';
    protected $primaryKey = 'id_medicion';

    protected $fillable = [
        'fecha_hora',
        'id_sesion',
        'temperatura'
    ];

    public function sesion()
    {
        return $this->belongsTo(Sesion::class, 'id_sesion');
    }
}
