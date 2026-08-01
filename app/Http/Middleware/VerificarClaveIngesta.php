<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege el endpoint de ingesta del ESP32.
 *
 * El dispositivo manda la clave en la cabecera X-API-Key. Se acepta
 * también Authorization: Bearer, por si al firmware le resulta más cómodo.
 *
 * Dos escapes deliberados:
 *
 * 1. Si no hay clave configurada, se deja pasar todo. Es el comportamiento
 *    histórico, y permite desplegar sin cortar la ingesta mientras el
 *    firmware todavía no la envía.
 * 2. Un usuario autenticado pasa sin clave. Eso es lo que mantiene
 *    funcionando el simulador de /simulador sin tener que incrustar la
 *    clave en una página web, donde quedaría a la vista.
 */
class VerificarClaveIngesta
{
    public function handle(Request $request, Closure $next): Response
    {
        $esperada = config('bionea.clave_ingesta');

        // Sin clave configurada el endpoint queda abierto, como antes.
        if (blank($esperada)) {
            return $next($request);
        }

        // El simulador corre dentro del panel, detrás del login.
        if (Auth::check()) {
            return $next($request);
        }

        $recibida = $request->header('X-API-Key')
            ?? $request->bearerToken();

        // hash_equals compara en tiempo constante: una comparación normal
        // tarda distinto según cuántos caracteres coincidan, y eso permite
        // deducir la clave a fuerza de medir tiempos de respuesta.
        if (! is_string($recibida) || ! hash_equals($esperada, $recibida)) {
            Log::warning('[Ingesta ESP32] Clave inválida o ausente', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'No autorizado'], 401);
        }

        return $next($request);
    }
}
