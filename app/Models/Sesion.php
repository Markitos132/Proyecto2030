<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabla `sesiones` en Supabase.
 *
 * `sesion_externa` guarda el session_id que genera el ESP32 (millis()).
 * Permite retomar una sesion aunque el servidor se reinicie.
 *
 * Los valores de `estado` en la base son 'activa' y 'finalizada'
 * (la UI original asumia 'MIDIENDO'; se usan las constantes de abajo).
 */
class Sesion extends Model
{
    protected $table      = 'sesiones';
    protected $primaryKey = 'id_sesion';

    public $timestamps = false;

    public const ESTADO_ACTIVA     = 'activa';
    public const ESTADO_FINALIZADA = 'finalizada';

    protected $fillable = [
        'id_individuo',
        'id_usuario',
        'id_dispositivo',
        'fecha_inicio',
        'fecha_fin',
        'intervalo_minuto',
        'duracion_sesion',
        'estado',
        'sesion_externa',
        'temp_min',
        'temp_max',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin'    => 'datetime',
        'temp_min'     => 'decimal:2',
        'temp_max'     => 'decimal:2',
    ];

    public function mediciones()
    {
        return $this->hasMany(Medicion::class, 'id_sesion');
    }

    /**
     * Ultima medicion recibida, por orden de llegada.
     *
     * Antes se ordenaba por fecha_hora, que es el reloj del ESP32, y eso
     * congelaba el dashboard: si una medicion llegaba con la hora adelantada,
     * ninguna posterior la superaba y el panel se quedaba mostrando ese valor.
     *
     * Pasa mas seguido de lo que parece. El dispositivo envia sin esperar la
     * respuesta anterior, asi que dos mediciones en vuelo pueden insertarse
     * en orden invertido; y el reloj del ESP32 puede derivar o saltar si
     * sincroniza por NTP a mitad de sesion.
     *
     * Para "temperatura actual" lo que importa es que sea el ultimo dato
     * recibido. fecha_hora se conserva intacta y sigue siendo la que ordena
     * los graficos y el historial, donde lo que interesa es cuando se tomo
     * la medicion, no cuando llego.
     */
    public function ultimaMedicion()
    {
        return $this->hasOne(Medicion::class, 'id_sesion')->latestOfMany('id_medicion');
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

    public function scopeActivas($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVA);
    }

    public function scopeFinalizadas($query)
    {
        return $query->where('estado', self::ESTADO_FINALIZADA);
    }

    /**
     * Lo que muestra el dashboard: lo que arrancó hoy, mas cualquier
     * sesion todavia en curso aunque haya empezado ayer, para que una
     * medicion larga no desaparezca del panel al cambiar la fecha.
     *
     * Vive acá y no en el controlador porque el endpoint de refresco
     * automatico tiene que devolver exactamente el mismo conjunto: si
     * las dos consultas se separan, el panel entra en un ciclo de
     * recargas al detectar diferencias entre lo dibujado y lo consultado.
     */
    public function scopeVisiblesEnPanel($query)
    {
        return $query->where(function ($q) {
            $q->whereDate('fecha_inicio', today())
              ->orWhere('estado', self::ESTADO_ACTIVA);
        });
    }

    public function estaActiva(): bool
    {
        return $this->estado === self::ESTADO_ACTIVA;
    }

    /** Etiqueta para mostrar en la UI. */
    public function getEtiquetaEstadoAttribute(): string
    {
        return $this->estaActiva() ? 'MIDIENDO' : 'FINALIZADO';
    }
}
