<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use App\Models\Individuo;
use App\Models\NotaDispositivo;
use App\Models\NotaIndividuo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/** Notas de campo asociadas a un individuo o a un dispositivo. */
class NotaController extends Controller
{
    public function storeIndividuo(Request $request, Individuo $individuo): RedirectResponse
    {
        $datos = $this->validar($request);

        NotaIndividuo::create([
            'id_individuo' => $individuo->id_individuo,
            'id_usuario'   => $request->user()->id_usuario,
            'fecha_alta'   => now(),
            'contenido'    => $datos['nota'],
        ]);

        return back()->with('exito', 'Nota agregada.');
    }

    public function storeDispositivo(Request $request, Dispositivo $dispositivo): RedirectResponse
    {
        $datos = $this->validar($request);

        NotaDispositivo::create([
            'id_dispositivo' => $dispositivo->id_dispositivo,
            'id_usuario'     => $request->user()->id_usuario,
            'fecha_alta'     => now(),
            'contenido'      => $datos['nota'],
        ]);

        return back()->with('exito', 'Nota agregada.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nota' => ['required', 'string', 'max:2000'],
        ], [
            'nota.required' => 'La nota no puede estar vacía.',
        ]);
    }
}
