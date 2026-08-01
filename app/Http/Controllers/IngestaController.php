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
// Ojo: el Response de Symfony, no el de Illuminate. JsonResponse no hereda
// del de Illuminate, y declararlo asi lanza un TypeError en tiempo de
// ejecucion. Ya paso una vez con /panel/estado.
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Ingesta de datos del ESP32.
 *
 * Port del server.js que corría como servicio Node aparte. Mantiene la
 * misma ruta (/bionea/guardar) y el mismo contrato JSON, para que el
 * firmware no tenga que cambiar.
 *
 * Diferencia de fondo con la versión Node: aquella mantenía un Map en
 * memoria con session_id -> id_sesion para evitar un SELECT por medición.
 * En PHP eso no es posible: cada request arranca con memoria limpia, no
 * hay proceso de larga vida donde guardar el Map. Se resuelve siempre
 * contra la columna sesion_externa, que está indexada; con un dispositivo
 * midiendo cada pocos minutos, el costo es irrelevante.
 */
class IngestaController extends Controller
{
    /** Segundos mínimos entre dos escrituras de ultima_conexion. */
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
            'temp_min'    => ['nullable', 'numeric'],
            'temp_max'    => ['nullable', 'numeric'],
            'alerta'      => ['nullable', 'string', 'max:50'],
        ]);

        // El firmware manda el session_id como número; en la base es texto.
        $datos['session_id'] = (string) $datos['session_id'];

        $momento = $this->parsearFechaHora($datos['fecha'], $datos['hora']);

        if (! $momento) {
            return response()->json([
                'error' => 'Formato de fecha u hora inválido. Se espera DD/MM/YYYY y HH:MM:SS.',
            ], 422);
        }

        try {
            // Sin transacción en el camino de la medición: cada BEGIN/COMMIT
            // son dos viajes extra a la base, y con ~800 ms de latencia hacia
            // Supabase eso duplicaba el tiempo de respuesta. El INSERT de una
            // medición ya es atómico por sí solo, y el UPDATE de
            // ultima_conexion no necesita ser atómico con él.
            return $datos['tipo'] === 'medicion'
                ? $this->registrarMedicion($datos, $momento)
                : DB::transaction(fn () => $this->finalizarSesion($datos, $momento));
        } catch (Throwable $e) {
            Log::error('[Ingesta ESP32] '.$e->getMessage(), [
                'session_id' => $datos['session_id'],
                'tipo'       => $datos['tipo'],
            ]);

            // Sin detalle del error hacia afuera: filtraría la estructura
            // de la base a un endpoint que hoy no exige autenticación.
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * El dispositivo pregunta si tiene una sesión asignada.
     *
     * Se identifica por su MAC, que es lo único que el ESP32 conoce de sí
     * mismo sin configuración previa. La sesión la crea una persona desde
     * el panel; el dispositivo solo la ejecuta.
     *
     * Responde 204 sin cuerpo cuando no hay nada asignado, que es el caso
     * frecuente: el equipo pregunta cada pocos minutos durante horas.
     */
    public function sesionAsignada(Request $request): Response
    {
        $mac = $request->query('mac');

        if (! $mac) {
            return response()->json(['error' => 'Falta el parámetro mac'], 422);
        }

        $dispositivo = Dispositivo::whereRaw('upper(mac_address) = ?', [strtoupper($mac)])->first();

        if (! $dispositivo) {
            return response()->json([
                'error' => 'Dispositivo no registrado. Datelo de alta en el panel con esta MAC.',
                'mac'   => $mac,
            ], 404);
        }

        // Cada consulta cuenta como señal de vida. Sin esto, un equipo
        // encendido pero sin medir figuraría como offline en el panel.
        $this->registrarLatido($dispositivo->id_dispositivo, now());

        $sesion = Sesion::activas()
            ->where('id_dispositivo', $dispositivo->id_dispositivo)
            ->with('individuo')
            ->orderBy('fecha_inicio')
            ->first();

        if (! $sesion) {
            return response()->noContent();
        }

        // La ingesta reconoce las sesiones por sesion_externa. Las que crea
        // el panel no tienen ese campo, así que se completa acá con el id:
        // sin esto, cada medición del dispositivo crearía una sesión nueva
        // en lugar de sumarse a la que le fue asignada.
        if (blank($sesion->sesion_externa)) {
            $sesion->update(['sesion_externa' => (string) $sesion->id_sesion]);
        }

        return response()->json([
            'session_id' => $sesion->sesion_externa,
            'individuo'  => $sesion->individuo?->codigo_individuo ?? '',
            'especie'    => $sesion->individuo?->especie ?? '',
            'duracion'   => (int) ($sesion->duracion_sesion ?: 60),
            'intervalo'  => (int) ($sesion->intervalo_minuto ?: 10),
            'temp_min'   => $sesion->temp_min !== null ? (float) $sesion->temp_min : null,
            'temp_max'   => $sesion->temp_max !== null ? (float) $sesion->temp_max : null,
        ]);
    }

    /** Diagnóstico: confirma que la app y la base responden. */
    public function health(): JsonResponse
    {
        try {
            $ahora = DB::selectOne('select now() as ahora')->ahora;

            return response()->json([
                'status'           => 'ok',
                'db'               => 'conectada',
                'hora_servidor'    => $ahora,
                'sesiones_activas' => Sesion::activas()->count(),
                // Visible a propósito: es la forma de darse cuenta de que
                // la clave quedó sin configurar y el endpoint sigue abierto.
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

        $sesion = $this->obtenerOCrearSesion($datos, $momento);

        Medicion::create([
            'id_sesion'   => $sesion->id_sesion,
            'fecha_hora'  => $momento,
            'temperatura' => $datos['temperatura'],
            'alerta'      => ($datos['alerta'] ?? null) === Medicion::ALERTA_FUERA
                                ? Medicion::ALERTA_FUERA
                                : Medicion::ALERTA_OK,
        ]);

        // Marcar el dispositivo como visto. Sin esto, ultima_conexion queda
        // siempre en null y el accessor estado_calculado reporta 'offline'
        // aunque el equipo esté midiendo: era lo que pasaba con la API Node,
        // que nunca tocaba esta columna.
        //
        // No hace falta escribirlo en cada medición: estado_calculado solo
        // distingue con granularidad de minutos. Actualizarlo cada
        // FRECUENCIA_LATIDO segundos ahorra un viaje a la base en la
        // mayoría de las peticiones.
        if ($sesion->id_dispositivo) {
            $this->registrarLatido($sesion->id_dispositivo, $momento);
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
            // No es un error del dispositivo: puede haberse reiniciado y
            // mandado el cierre de una sesión que nunca llegó a crearse.
            Log::warning('[Ingesta ESP32] Cierre de sesión inexistente', [
                'session_id' => $datos['session_id'],
            ]);

            return response()->json(['ok' => true, 'tipo' => 'fin_sesion', 'aviso' => 'sesión no encontrada']);
        }

        $sesion->update([
            'fecha_fin'       => $momento,
            'estado'          => Sesion::ESTADO_FINALIZADA,
            // Carbon 3 devuelve un float; la columna es entera.
            'duracion_sesion' => $sesion->fecha_inicio
                                    ? (int) round($sesion->fecha_inicio->diffInMinutes($momento))
                                    : null,
        ]);

        return response()->json(['ok' => true, 'tipo' => 'fin_sesion', 'id_sesion' => $sesion->id_sesion]);
    }

    // ── Resolución de sesión e individuo ────────────────────

    private function obtenerOCrearSesion(array $datos, \DateTimeInterface $momento): Sesion
    {
        $sesion = Sesion::where('sesion_externa', $datos['session_id'])->first();

        if ($sesion) {
            return $sesion;
        }

        return Sesion::create([
            'id_individuo'     => $this->obtenerOCrearIndividuo($datos)?->id_individuo,
            'id_dispositivo'   => $this->dispositivoPorDefecto()?->id_dispositivo,
            'id_usuario'       => null,
            'fecha_inicio'     => $momento,
            'intervalo_minuto' => 10,
            'estado'           => Sesion::ESTADO_ACTIVA,
            'sesion_externa'   => $datos['session_id'],
            'temp_min'         => $datos['temp_min'] ?? null,
            'temp_max'         => $datos['temp_max'] ?? null,
        ]);
    }

    /**
     * El ESP32 identifica al individuo por código (ej: "LAG-001").
     * Si no está cargado en el panel, se crea al vuelo para no perder
     * la medición; después se completa la ficha a mano.
     */
    private function obtenerOCrearIndividuo(array $datos): ?Individuo
    {
        $codigo = $datos['individuo'] ?? null;

        if (! $codigo) {
            return null;
        }

        return Individuo::firstOrCreate(
            ['codigo_individuo' => $codigo],
            ['especie' => $datos['especie'] ?? null, 'estado' => 'activo']
        );
    }

    /**
     * Actualiza ultima_conexion como mucho una vez cada minuto por
     * dispositivo. El marcador vive en el cache de la aplicación, no en la
     * base, así que el caso frecuente no cuesta ninguna consulta.
     */
    private function registrarLatido(int $idDispositivo, \DateTimeInterface $momento): void
    {
        $clave = "latido:dispositivo:{$idDispositivo}";

        if (Cache::store('file')->has($clave)) {
            return;
        }

        Dispositivo::whereKey($idDispositivo)
            ->update(['ultima_conexion' => $momento, 'estado' => 'activo']);

        Cache::store('file')->put($clave, true, self::FRECUENCIA_LATIDO);
    }

    private function dispositivoPorDefecto(): ?Dispositivo
    {
        return Dispositivo::where('estado', 'activo')->first()
            ?? Dispositivo::first();
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
