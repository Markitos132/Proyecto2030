<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use App\Models\Medicion;
use App\Models\Sesion;
use App\Services\CierreDeSesiones;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
// Tipo de retorno de Symfony, no el de Illuminate: JsonResponse hereda de
// Symfony\...\JsonResponse, no de Illuminate\Http\Response. Declarar el de
// Illuminate hacia que devolver el JSON lanzara un TypeError y el endpoint
// respondiera 500, con el panel mostrando "Sin conexión con el servidor".
use Symfony\Component\HttpFoundation\Response;

/**
 * Estado del panel en JSON, para el refresco automático.
 *
 * Sustituye al stream SSE de la versión Node. Aquel abría una conexión
 * persistente por pestaña y cada una lanzaba sus propias consultas cada
 * 2 segundos: con tres pestañas abiertas, tres pollers independientes.
 *
 * Un SSE tampoco es viable acá: FrankenPHP en el plan gratuito de Render
 * arranca con dos hilos de PHP, y cada conexión abierta ocupa uno mientras
 * dura. Dos pestañas del dashboard dejarían al ESP32 sin hilos donde
 * entregar sus mediciones. Acá se responde y se corta; el cliente decide
 * cuándo volver a preguntar, y el servidor le sugiere cada cuánto.
 */
class PanelEstadoController extends Controller
{
    /** Segundos entre consultas cuando hay una sesión midiendo. */
    private const RITMO_ACTIVO = 3;

    /**
     * Segundos entre consultas cuando no hay ninguna sesión activa.
     *
     * Este número es, en el peor caso, lo que tarda el panel en enterarse
     * de que arrancó una sesión: el servidor no puede despertar a un
     * cliente dormido, el aviso solo llega en la consulta siguiente.
     *
     * Estuvo en 60 y la espera se hacía notar. El ahorro no lo justificaba:
     * una consulta en reposo es un 304 sin cuerpo y dos consultas livianas.
     */
    private const RITMO_REPOSO = 15;

    public function __invoke(Request $request, CierreDeSesiones $cierre): Response
    {
        // Cierra sesiones que dejaron de reportar. Se autolimita a una
        // pasada cada dos minutos, así que no encarece el polling.
        $cierre->revisarSiCorresponde();

        // Mismo scope que usa DashboardController para dibujar la tabla.
        // Si acá se devolviera otro conjunto (por ejemplo solo las activas),
        // el cliente vería una diferencia entre lo dibujado y lo consultado
        // y recargaría la página una y otra vez.
        $sesiones = Sesion::visiblesEnPanel()
            ->with(['individuo:id_individuo,codigo_individuo,especie',
                    'dispositivo:id_dispositivo,nombre',
                    'ultimaMedicion',
                    // Para el mini-gráfico de las tarjetas de Sesiones Activas.
                    // Solo dos columnas: la serie se recorta a 30 puntos más
                    // abajo, pero la consulta trae la sesión entera.
                    'mediciones:id_medicion,id_sesion,temperatura,fecha_hora'])
            ->orderByDesc('fecha_inicio')
            ->get();

        $dispositivos = Dispositivo::with('sesionActiva.ultimaMedicion')->get();

        $hayActivas = $sesiones->contains(fn ($s) => $s->estaActiva());

        $tempPromedio = Medicion::query()
            ->whereHas('sesion', fn ($q) => $q->where('estado', Sesion::ESTADO_ACTIVA))
            ->where('fecha_hora', '>=', now()->subHour())
            ->avg('temperatura');

        $activas = $sesiones->where('estado', Sesion::ESTADO_ACTIVA);

        // Estas dos son las que muestra Sesiones Activas, y no coinciden con
        // las del dashboard: allá el promedio de temperatura abarca la última
        // hora de mediciones, acá es el promedio de la última lectura de cada
        // sesión en curso. Son preguntas distintas y conviven.
        $tempActualPromedio = $activas
            ->map(fn ($s) => $s->ultimaMedicion?->temperatura)
            ->filter()
            ->avg();

        $duracionPromedio = $activas->map(fn ($s) => $s->minutos_transcurridos)->avg();

        $datos = [
            'metricas' => [
                'sesiones_activas'    => $activas->count(),
                'dispositivos_online' => $dispositivos
                                            ->filter(fn ($d) => $d->estado_calculado !== 'offline')
                                            ->count(),
                'dispositivos_total'  => $dispositivos->count(),
                'temp_promedio'       => $tempPromedio !== null
                                            ? round((float) $tempPromedio, 1)
                                            : null,
                'temp_actual_promedio' => $tempActualPromedio !== null
                                            ? round((float) $tempActualPromedio, 1)
                                            : null,
                'duracion_promedio'   => $duracionPromedio !== null
                                            ? round((float) $duracionPromedio, 1)
                                            : null,
            ],
            'sesiones' => $sesiones->map(fn ($s) => [
                'id_sesion'   => $s->id_sesion,
                'activa'      => $s->estaActiva(),
                'etiqueta'    => $s->etiqueta_estado,
                'individuo'   => $s->individuo?->codigo_individuo,
                'especie'     => $s->individuo?->especie,
                'dispositivo' => $s->dispositivo?->nombre,
                'temperatura' => $s->ultimaMedicion?->temperatura !== null
                                    ? (float) $s->ultimaMedicion->temperatura
                                    : null,
                'alerta'      => $s->ultimaMedicion?->alerta,
                'medido_hace' => $s->ultimaMedicion?->fecha_hora?->diffForHumans(null, true),

                // Lo que consumen las tarjetas de Sesiones Activas.
                'lecturas'    => $s->mediciones->count(),
                'duracion'    => $s->minutos_transcurridos,
                'restante'    => $s->minutos_restantes,
                'total'       => $s->duracion_sesion,
                'progreso'    => $s->progreso,
                'serie'       => $s->serieReciente(),
            ])->values(),

            // El servidor decide el ritmo: consultar seguido solo mientras
            // hay algo midiendo. En reposo, una vez por minuto alcanza.
            'proximo_en' => $hayActivas ? self::RITMO_ACTIVO : self::RITMO_REPOSO,
        ];

        // ETag sobre los datos, sin la hora del servidor: si no cambió nada,
        // se responde 304 sin cuerpo. Es lo que hace barato consultar cada
        // 3 segundos, porque entre medicion y medicion no hay novedades.
        $etag = '"'.md5(json_encode($datos)).'"';

        if (trim($request->header('If-None-Match', ''), 'W/') === $etag) {
            return response('', 304)->header('ETag', $etag);
        }

        $datos['servidor'] = now()->toIso8601String();

        return response()->json($datos)->header('ETag', $etag);
    }
}
