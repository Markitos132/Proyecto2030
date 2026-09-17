<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use App\Models\Medicion;
use App\Models\Sesion;
use App\Services\CierreDeSesiones;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class PanelEstadoController extends Controller
{
    private const RITMO_ACTIVO = 3;
    private const RITMO_REPOSO = 15;

    public function __invoke(Request $request, CierreDeSesiones $cierre): Response
    {
        $cierre->revisarSiCorresponde();

        // Mismo scope que usa DashboardController para dibujar la tabla.
        $sesiones = Sesion::visiblesEnPanel()
            ->where('id_usuario', auth()->id())
            ->with(['individuo:id_individuo,codigo_individuo,especie',
                    'dispositivo:id_dispositivo,nombre',
                    'ultimaMedicion',
                    'mediciones:id_medicion,id_sesion,temperatura,fecha_hora'])
            ->orderByDesc('fecha_inicio')
            ->get();

        $dispositivos = Dispositivo::where('id_usuario', auth()->id())
            ->with('sesionActiva.ultimaMedicion')
            ->get();

        $hayActivas = $sesiones->contains(fn ($s) => $s->estaActiva());

        $tempPromedio = Medicion::query()
            ->whereHas('sesion', fn ($q) => $q->where('estado', Sesion::ESTADO_ACTIVA)
                                               ->where('id_usuario', auth()->id()))
            ->where('fecha_hora', '>=', now()->subHour())
            ->avg('temperatura');

        $activas = $sesiones->where('estado', Sesion::ESTADO_ACTIVA);

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
                'lecturas'    => $s->mediciones->count(),
                'duracion'    => $s->minutos_transcurridos,
                'restante'    => $s->minutos_restantes,
                'total'       => $s->duracion_sesion,
                'progreso'    => $s->progreso,
                'serie'       => $s->serieReciente(),
            ])->values(),

            'proximo_en' => $hayActivas ? self::RITMO_ACTIVO : self::RITMO_REPOSO,
        ];

        $etag = '"'.md5(json_encode($datos)).'"';

        if (trim($request->header('If-None-Match', ''), 'W/') === $etag) {
            return response('', 304)->header('ETag', $etag);
        }

        $datos['servidor'] = now()->toIso8601String();

        return response()->json($datos)->header('ETag', $etag);
    }
}