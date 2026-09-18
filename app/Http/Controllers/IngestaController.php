<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use App\Models\Individuo;
use App\Models\Medicion;
use App\Models\Sesion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class IngestaController extends Controller
{
    private const FRECUENCIA_LATIDO = 60;

    public function guardar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'session_id'  => ['required'],
            'tipo'        => ['required', 'string', 'in:medicion,fin_sesion'],
            'fecha'       => ['required', 'string'],
            'hora'        => ['required', 'string'],
            'individuo'   => ['nullable', 'string', 'max:50'],
            'especie'     => ['nullable', 'string', 'max:255'],
            'temperatura' => ['nullable', 'numeric'],
            'alerta'      => ['nullable', 'string', 'max:50'],
            // Solo hace falta cuando sesion_externa no matchea nada (el
            // equipo se reinició, por ejemplo): es lo único que permite
            // reencontrar de quién es la sesión sin adivinar.
            'mac'         => ['nullable', 'string', 'max:17'],
        ]);

        $datos['session_id'] = (string) $datos['session_id'];

        $momento = $this->parsearFechaHora($datos['fecha'], $datos['hora']);

        if (! $momento) {
            return response()->json([
                'error' => 'Formato de fecha u hora inválido. Se espera DD/MM/YYYY y HH:MM:SS.',
            ], 422);
        }

        try {
            return $datos['tipo'] === 'medicion'
                ? $this->registrarMedicion($datos, $momento)
                : DB::transaction(fn () => $this->finalizarSesion($datos, $momento));
        } catch (Throwable $e) {
            Log::error('[Ingesta ESP32] '.$e->getMessage(), [
                'session_id' => $datos['session_id'],
                'tipo'       => $datos['tipo'],
            ]);

            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * El dispositivo pregunta si tiene una sesión asignada, identificándose
     * por MAC.
     *
     * El mismo equipo físico puede estar registrado por varios usuarios
     * (se lo prestan entre sí) — cada uno tiene su propia fila en
     * `dispositivos` con la misma MAC. La sesión activa es lo único que
     * indica de qué cuenta es la medición en este momento; sin sesión
     * activa en ninguna de las filas, no hay quién reclame el dato.
     */
    public function sesionAsignada(Request $request): Response
    {
        $mac = $request->query('mac');

        if (! $mac) {
            return response()->json(['error' => 'Falta el parámetro mac'], 422);
        }

        $dispositivos = Dispositivo::whereRaw('upper(mac_address) = ?', [strtoupper($mac)])->get();

        if ($dispositivos->isEmpty()) {
            return response()->json([
                'error' => 'Dispositivo no registrado. Datelo de alta en el panel con esta MAC.',
                'mac'   => $mac,
            ], 404);
        }

        // Cada consulta es una señal de que el equipo está encendido, sin
        // importar quién lo esté usando ahora — así el estado online/offline
        // queda bien en TODAS las cuentas que lo registraron, no solo en la
        // que tiene la sesión activa.
        $this->registrarLatido($dispositivos->pluck('id_dispositivo')->all(), now());

        $dispositivo = Dispositivo::whereIn('id_dispositivo', $dispositivos->pluck('id_dispositivo'))
            ->whereHas('sesiones', fn ($q) => $q->where('estado', Sesion::ESTADO_ACTIVA))
            ->first();

        if (! $dispositivo) {
            return response()->noContent();
        }

        $sesion = Sesion::activas()
            ->where('id_dispositivo', $dispositivo->id_dispositivo)
            ->with('individuo')
            ->orderBy('fecha_inicio')
            ->first();

        if (! $sesion) {
            return response()->noContent();
        }

        if (blank($sesion->sesion_externa)) {
            $sesion->update(['sesion_externa' => (string) $sesion->id_sesion]);
        }

        return response()->json([
            'session_id' => $sesion->sesion_externa,
            'individuo'  => $sesion->individuo?->codigo_individuo ?? '',
            'especie'    => $sesion->individuo?->especie ?? '',
            'duracion'   => (int) ($sesion->duracion_sesion ?: 60),
            'intervalo'  => (int) ($sesion->intervalo_minuto ?: 10),
        ]);
    }

    public function health(): JsonResponse
    {
        try {
            $ahora = DB::selectOne('select now() as ahora')->ahora;

            return response()->json([
                'status'           => 'ok',
                'db'               => 'conectada',
                'hora_servidor'    => $ahora,
                'sesiones_activas' => Sesion::activas()->count(),
                'ingesta_protegida' => filled(config('bionea.clave_ingesta')),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'db'     => 'sin conexión',
            ], 500);
        }
    }

    // ── Casos ───────────────────────────────────────────────

    private function registrarMedicion(array $datos, \DateTimeInterface $momento): JsonResponse
    {
        if (! isset($datos['temperatura'])) {
            return response()->json(['error' => 'Falta temperatura'], 422);
        }

        $sesion = $this->obtenerSesion($datos);

        if (! $sesion) {
            Log::warning('[Ingesta ESP32] Medición sin sesión resoluble', [
                'session_id' => $datos['session_id'],
                'mac'        => $datos['mac'] ?? null,
            ]);

            return response()->json([
                'error' => 'No hay sesión activa asignada para este dispositivo. Asignala desde el panel.',
            ], 409);
        }

        Medicion::create([
            'id_sesion'   => $sesion->id_sesion,
            'fecha_hora'  => $momento,
            'temperatura' => $datos['temperatura'],
            'alerta'      => ($datos['alerta'] ?? null) === Medicion::ALERTA_FUERA
                                ? Medicion::ALERTA_FUERA
                                : Medicion::ALERTA_OK,
        ]);

        if ($sesion->id_dispositivo) {
            $this->registrarLatido([$sesion->id_dispositivo], $momento);
        }

        return response()->json([
            'ok'        => true,
            'tipo'      => 'medicion',
            'id_sesion' => $sesion->id_sesion,
        ]);
    }

    private function finalizarSesion(array $datos, \DateTimeInterface $momento): JsonResponse
    {
        $sesion = Sesion::where('sesion_externa', $datos['session_id'])->first();

        if (! $sesion) {
            Log::warning('[Ingesta ESP32] Cierre de sesión inexistente', [
                'session_id' => $datos['session_id'],
            ]);

            return response()->json(['ok' => true, 'tipo' => 'fin_sesion', 'aviso' => 'sesión no encontrada']);
        }

        $sesion->update([
            'fecha_fin'       => $momento,
            'estado'          => Sesion::ESTADO_FINALIZADA,
            'duracion_sesion' => $sesion->fecha_inicio
                                    ? (int) round($sesion->fecha_inicio->diffInMinutes($momento))
                                    : null,
        ]);

        return response()->json(['ok' => true, 'tipo' => 'fin_sesion', 'id_sesion' => $sesion->id_sesion]);
    }

    // ── Resolución de sesión ─────────────────────────────────

    /**
     * Resuelve a qué sesión pertenece una medición.
     *
     * Camino normal: el session_id ya matchea una sesión (fue asignada
     * desde el panel, el ESP32 la viene usando desde entonces).
     *
     * Camino de recuperación: el dispositivo perdió su session_id (se
     * reinició, por ejemplo) pero sigue teniendo una sesión activa
     * asignada — se lo reencuentra por MAC. Ya NO se crea una sesión ni
     * un individuo desde cero sin MAC: con el mismo equipo físico
     * registrado en varias cuentas, adivinar "cualquiera" mezclaría datos
     * entre usuarios. Sin poder identificar con certeza de quién es, se
     * rechaza la medición en vez de arriesgarse.
     */
    private function obtenerSesion(array $datos): ?Sesion
    {
        $sesion = Sesion::where('sesion_externa', $datos['session_id'])->first();

        if ($sesion) {
            return $sesion;
        }

        if (empty($datos['mac'])) {
            return null;
        }

        $dispositivo = Dispositivo::whereRaw('upper(mac_address) = ?', [strtoupper($datos['mac'])])
            ->whereHas('sesiones', fn ($q) => $q->where('estado', Sesion::ESTADO_ACTIVA))
            ->first();

        if (! $dispositivo) {
            return null;
        }

        $sesion = Sesion::activas()->where('id_dispositivo', $dispositivo->id_dispositivo)->first();

        // Recupera el session_id que el dispositivo venía usando, para que
        // las próximas mediciones matcheen directo sin pasar por acá.
        if ($sesion && blank($sesion->sesion_externa)) {
            $sesion->update(['sesion_externa' => $datos['session_id']]);
        }

        return $sesion;
    }

    /**
     * Actualiza ultima_conexion como mucho una vez cada minuto, por cada
     * dispositivo de la lista.
     */
    private function registrarLatido(array $idsDispositivo, \DateTimeInterface $momento): void
    {
        $pendientes = array_values(array_filter(array_unique($idsDispositivo), function ($id) {
            return ! Cache::store('file')->has("latido:dispositivo:{$id}");
        }));

        if (empty($pendientes)) {
            return;
        }

        Dispositivo::whereIn('id_dispositivo', $pendientes)
            ->update(['ultima_conexion' => $momento, 'estado' => 'activo']);

        foreach ($pendientes as $id) {
            Cache::store('file')->put("latido:dispositivo:{$id}", true, self::FRECUENCIA_LATIDO);
        }
    }

    /** Convierte "DD/MM/YYYY" + "HH:MM:SS" en un objeto de fecha. */
    private function parsearFechaHora(string $fecha, string $hora): ?\DateTimeInterface
    {
        foreach (['d/m/Y H:i:s', 'd/m/Y H:i', 'Y-m-d H:i:s'] as $formato) {
            $resultado = \DateTime::createFromFormat($formato, trim($fecha).' '.trim($hora));

            if ($resultado !== false) {
                return $resultado;
            }
        }

        return null;
    }
}