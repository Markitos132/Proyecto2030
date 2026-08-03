<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use App\Models\Individuo;
use App\Models\Medicion;
use App\Models\Sesion;
use App\Services\CierreDeSesiones;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SesionController extends Controller
{
    public function index(CierreDeSesiones $cierre)
    {
        // Antes de listar, descartar las que dejaron de reportar.
        $cierre->revisarSiCorresponde();

        $sesionesActivas = Sesion::activas()
            ->with(['individuo', 'dispositivo', 'ultimaMedicion', 'mediciones'])
            ->orderByDesc('fecha_inicio')
            ->get();

        $tempPromedioSesion = $sesionesActivas
            ->map(fn ($s) => $s->ultimaMedicion?->temperatura)
            ->filter()
            ->avg();

        $duracionPromedio = $sesionesActivas
            ->map(fn ($s) => $s->fecha_inicio?->diffInMinutes(now()))
            ->filter()
            ->avg();

        // Individuos y dispositivos elegibles para una sesion nueva:
        // los que no estan ya midiendo.
        $indActivos = Individuo::where('estado', 'activo')
            ->whereDoesntHave('sesiones', fn ($q) => $q->where('estado', Sesion::ESTADO_ACTIVA))
            ->orderBy('codigo_individuo')
            ->get();

        $dispositivosDisponibles = Dispositivo::whereDoesntHave(
                'sesiones', fn ($q) => $q->where('estado', Sesion::ESTADO_ACTIVA)
            )->orderBy('nombre')->get();

        return view('admin.sesiones', [
            'sesionesActivas'         => $sesionesActivas,
            'sesionesEncurso'         => $sesionesActivas->count(),
            'tempPromedioSesion'      => $tempPromedioSesion,
            'duracionPromedio'        => $duracionPromedio,
            'indActivos'              => $indActivos,
            'dispositivosDisponibles' => $dispositivosDisponibles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'individuo_id'   => ['required', 'exists:individuos,id_individuo'],
            'dispositivo_id' => ['required', 'exists:dispositivos,id_dispositivo'],
            'duracion'       => ['nullable', 'integer', 'min:1', 'max:10080'],
            'intervalo'      => ['nullable', 'integer', 'min:1', 'max:1440'],
            'temp_min'       => ['nullable', 'numeric', 'min:-50', 'max:100'],
            'temp_max'       => ['nullable', 'numeric', 'min:-50', 'max:100', 'gt:temp_min'],
        ], [
            'individuo_id.exists'   => 'El ejemplar seleccionado no existe.',
            'dispositivo_id.exists' => 'El dispositivo seleccionado no existe.',
            'temp_max.gt'           => 'La temperatura máxima debe ser mayor que la mínima.',
        ]);

        // Un dispositivo solo puede medir un individuo a la vez.
        $ocupado = Sesion::activas()
            ->where('id_dispositivo', $datos['dispositivo_id'])
            ->exists();

        if ($ocupado) {
            return back()->withErrors([
                'dispositivo_id' => 'Ese dispositivo ya tiene una sesión en curso.',
            ])->withInput();
        }

        Sesion::create([
            'id_individuo'     => $datos['individuo_id'],
            'id_dispositivo'   => $datos['dispositivo_id'],
            'id_usuario'       => $request->user()->id_usuario,
            'fecha_inicio'     => now(),
            'duracion_sesion'  => $datos['duracion'] ?? null,
            'intervalo_minuto' => $datos['intervalo'] ?? 10,
            'temp_min'         => $datos['temp_min'] ?? null,
            'temp_max'         => $datos['temp_max'] ?? null,
            'estado'           => Sesion::ESTADO_ACTIVA,
        ]);

        return redirect()->route('sesiones')->with('exito', 'Sesión iniciada.');
    }

    public function show(Sesion $sesion)
    {
        $sesion->load(['individuo', 'dispositivo']);

        $mediciones = $sesion->mediciones()
            ->orderBy('fecha_hora')
            ->get(['fecha_hora', 'temperatura', 'alerta']);

        // La serie se arma acá y no en la vista a propósito.
        //
        // Estaba con @json(...) y una función flecha repartida en varias
        // líneas: Blade extrae los argumentos de una directiva contando
        // paréntesis, y con un array multilínea adentro genera PHP inválido.
        // El resultado era un ParseError que tumbaba la página entera,
        // incluso cuando el bloque estaba dentro de un @if que daba falso.
        $serie = $mediciones->map(fn ($m) => [
            'hora'        => $m->fecha_hora?->format('H:i'),
            'temperatura' => (float) $m->temperatura,
            'alerta'      => $m->alerta,
        ])->values();

        $promedio = $mediciones->avg('temperatura');

        return view('admin.sesion_grafico', [
            'sesion'       => $sesion,
            'mediciones'   => $mediciones,
            'serie'        => $serie,
            'promedio'     => $promedio !== null ? round((float) $promedio, 1) : null,
            'fueraDeRango' => $mediciones->where('alerta', Medicion::ALERTA_FUERA)->count(),
            'tempMin'      => $sesion->temp_min !== null ? (float) $sesion->temp_min : null,
            'tempMax'      => $sesion->temp_max !== null ? (float) $sesion->temp_max : null,
        ]);
    }

    public function finalizar(Sesion $sesion): RedirectResponse
    {
        if (! $sesion->estaActiva()) {
            return back()->withErrors(['sesion' => 'La sesión ya estaba finalizada.']);
        }

        $fin = now();

        $sesion->update([
            'fecha_fin'       => $fin,
            'estado'          => Sesion::ESTADO_FINALIZADA,
            // Carbon 3 devuelve un float en diffInMinutes; la columna es
            // entera y Postgres rechaza el decimal.
            'duracion_sesion' => $sesion->fecha_inicio
                                    ? (int) round($sesion->fecha_inicio->diffInMinutes($fin))
                                    : null,
        ]);

        return back()->with('exito', 'Sesión finalizada.');
    }
}
