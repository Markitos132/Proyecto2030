<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use App\Models\Medicion;
use App\Models\Sesion;
use App\Services\CierreDeSesiones;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Estado del panel en JSON, para el refresco automático.
 *
 * Sustituye al stream SSE de la versión Node. Aquel abría una conexión
 * persistente por pestaña y cada una lanzaba sus propias consultas cada
 * 2 segundos: con tres pestañas abiertas, tres pollers independientes.
 *
 * PHP-FPM además bloquea un worker por conexión abierta, así que un SSE
 * con varios clientes agota el pool de procesos. Acá se responde y se
 * corta; el cliente decide cuándo volver a preguntar.
 */
class PanelEstadoController extends Controller
{
    public function __invoke(CierreDeSesiones $cierre): JsonResponse
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
                    'ultimaMedicion'])
            ->orderByDesc('fecha_inicio')
            ->get();

        $dispositivos = Dispositivo::with('sesionActiva.ultimaMedicion')->get();

        $tempPromedio = Medicion::query()
            ->whereHas('sesion', fn ($q) => $q->where('estado', Sesion::ESTADO_ACTIVA))
            ->where('fecha_hora', '>=', now()->subHour())
            ->avg('temperatura');

        return response()->json([
            'metricas' => [
                'sesiones_activas'    => $sesiones->where('estado', Sesion::ESTADO_ACTIVA)->count(),
                'dispositivos_online' => $dispositivos
                                            ->filter(fn ($d) => $d->estado_calculado !== 'offline')
                                            ->count(),
                'dispositivos_total'  => $dispositivos->count(),
                'temp_promedio'       => $tempPromedio !== null
                                            ? round((float) $tempPromedio, 1)
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
            ])->values(),
            'servidor' => now()->toIso8601String(),
        ]);
    }
}
