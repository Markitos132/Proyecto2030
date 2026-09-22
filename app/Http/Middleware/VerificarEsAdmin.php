<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deja pasar solo a usuarios con es_admin = true.
 *
 * Se aplica junto con el middleware `auth`, así que acá ya se asume
 * que hay sesión iniciada — no hace falta chequear Auth::check().
 *
 * No alcanza con ocultar el link en el sidebar: la ruta /usuarios/nuevo
 * ya existía y cualquier usuario logueado podía escribirla a mano en
 * el navegador. Este middleware es lo que realmente lo impide.
 */
class VerificarEsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->es_admin) {
            return redirect()
                ->route('dashboard')
                ->withErrors(['acceso' => 'Esa sección es solo para administradores.']);
        }

        return $next($request);
    }
}
