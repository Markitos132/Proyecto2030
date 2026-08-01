<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabla `mediciones` en Supabase.
 *
 * `alerta` la calcula el ESP32 comparando la temperatura
 * contra el rango temp_min / temp_max de la sesion.
 */
class Medicion extends Model
{
    protected $table      = 'mediciones';
    protected $primaryKey = 'id_medicion';

    public $timestamps = false;

    public const ALERTA_OK    = 'OK';
    public const ALERTA_FUERA = 'FUERA DE RANGO';

    protected $fillable = [
        'id_sesion',
        'fecha_hora',
        'temperatura',
        'alerta',
    ];

    protected $casts = [
        'fecha_hora'  => 'datetime',
        'temperatura' => 'decimal:2',
    ];

    public function sesion()
    {
        return $this->belongsTo(Sesion::class, 'id_sesion');
    }

    public function fueraDeRango(): bool
    {
        return $this->alerta === self::ALERTA_FUERA;
    }
}
