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

    /*
    |--------------------------------------------------------------------------
    |  Progreso de la sesión
    |--------------------------------------------------------------------------
    |
    |  Viven acá y no en la vista porque los consumen dos lugares: el Blade
    |  que dibuja la tarjeta la primera vez y el endpoint /panel/estado que
    |  la refresca cada pocos segundos. Calculados por separado, uno se
    |  quedaría atrás del otro tarde o temprano.
    |
    |  Ojo con Carbon 3: diffInMinutes() devuelve float, no int. Ese detalle
    |  ya rompió un INSERT contra una columna entera y dejó etiquetas como
    |  "125.38333333 min" a la vista.
    |
    */

    public function getMinutosTranscurridosAttribute(): int
    {
        if (! $this->fecha_inicio) {
            return 0;
        }

        $hasta = $this->fecha_fin ?? now();

        return max(0, (int) round($this->fecha_inicio->diffInMinutes($hasta)));
    }

    public function getMinutosRestantesAttribute(): ?int
    {
        // Sin duración pactada no hay meta contra la cual medir: la sesión
        // corre hasta que alguien la corte o el dispositivo deje de reportar.
        if (! $this->duracion_sesion) {
            return null;
        }

        return max(0, $this->duracion_sesion - $this->minutos_transcurridos);
    }

    /** Porcentaje 0-100, o null si la sesión no tiene duración pactada. */
    public function getProgresoAttribute(): ?int
    {
        if (! $this->duracion_sesion) {
            return null;
        }

        $porcentaje = ($this->minutos_transcurridos / $this->duracion_sesion) * 100;

        // Se topa en 100: una sesión que se pasó de su duración no debería
        // desbordar la barra ni mostrar 140%.
        return (int) round(min(100, max(0, $porcentaje)));
    }

    /** True si la última lectura recibida quedó fuera del rango pactado. */
    public function getFueraDeRangoAttribute(): bool
    {
        return $this->ultimaMedicion?->alerta === Medicion::ALERTA_FUERA;
    }

    /**
     * Últimas temperaturas, para el mini-gráfico de la tarjeta.
     *
     * Se limita a 30 puntos: en 90 píxeles de alto no se distingue más, y
     * una sesión larga con lectura por minuto acumula cientos de valores
     * que habría que mandar enteros en cada refresco.
     */
    public function serieReciente(int $limite = 30): array
    {
        return $this->mediciones
            ->sortBy('id_medicion')
            ->pluck('temperatura')
            ->map(fn ($t) => (float) $t)
            ->slice(-$limite)
            ->values()
            ->all();
    }
}
