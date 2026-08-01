<?php

namespace App\Services;

use App\Models\Sesion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cierra sesiones que quedaron abiertas sin que nadie las finalice.
 *
 * El ESP32 avisa el fin de una sesión con un POST tipo 'fin_sesion'. Ese
 * aviso no llega si el equipo se queda sin batería, pierde el WiFi o se
 * reinicia a mitad de una medición. Sin esto, la sesión queda 'activa'
 * para siempre: ensucia el dashboard y deja el dispositivo bloqueado,
 * porque un dispositivo con sesión en curso no aparece como disponible.
 */
class CierreDeSesiones
{
    /**
     * Margen sobre el intervalo configurado antes de dar por muerta una
     * sesión. Se toleran tres intervalos perdidos seguidos.
     */
    private const INTERVALOS_TOLERADOS = 3;

    /** Piso en minutos, para intervalos muy cortos (pruebas, simulador). */
    private const MINIMO_MINUTOS = 15;

    /** Segundos entre dos pasadas cuando se invoca de forma oportunista. */
    private const FRECUENCIA_REVISION = 120;

    /**
     * Revisa como mucho una vez cada FRECUENCIA_REVISION segundos.
     *
     * Pensado para llamarse desde el panel: en Render el plan gratuito no
     * ejecuta tareas programadas, así que no se puede depender solo del
     * scheduler. El marcador vive en el cache de archivos, no cuesta
     * ninguna consulta.
     */
    public function revisarSiCorresponde(): int
    {
        $clave = 'cierre-sesiones:ultima-revision';

        if (Cache::store('file')->has($clave)) {
            return 0;
        }

        Cache::store('file')->put($clave, true, self::FRECUENCIA_REVISION);

        return $this->cerrarHuerfanas();
    }

    /**
     * Cierra todas las sesiones huérfanas y devuelve cuántas cerró.
     *
     * La fecha de fin es la de la última medición recibida, no el momento
     * de la revisión: interesa cuándo dejó de medir el equipo, no cuándo
     * se dio cuenta el sistema.
     */
    public function cerrarHuerfanas(): int
    {
        $sesiones = Sesion::activas()->with('ultimaMedicion')->get();
        $cerradas = 0;

        foreach ($sesiones as $sesion) {
            $ultimaSenal = $sesion->ultimaMedicion?->fecha_hora ?? $sesion->fecha_inicio;

            // Sin fecha de inicio ni mediciones no hay forma de decidir.
            if (! $ultimaSenal) {
                continue;
            }

            $intervalo = max((int) ($sesion->intervalo_minuto ?: 10), 1);
            $umbral    = max($intervalo * self::INTERVALOS_TOLERADOS, self::MINIMO_MINUTOS);

            if ($ultimaSenal->diffInMinutes(now()) < $umbral) {
                continue;
            }

            $sesion->update([
                'fecha_fin'       => $ultimaSenal,
                'estado'          => Sesion::ESTADO_FINALIZADA,
                'duracion_sesion' => $sesion->fecha_inicio
                                        ? (int) round($sesion->fecha_inicio->diffInMinutes($ultimaSenal))
                                        : null,
            ]);

            $cerradas++;

            Log::info('[BioNEA] Sesión cerrada por inactividad', [
                'id_sesion'      => $sesion->id_sesion,
                'ultima_senal'   => $ultimaSenal->toDateTimeString(),
                'umbral_minutos' => $umbral,
            ]);
        }

        return $cerradas;
    }
}
