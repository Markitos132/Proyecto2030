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
use Illuminate\Validation\Rule;

class SesionController extends Controller
{
    public function index(CierreDeSesiones $cierre)
    {
        $cierre->revisarSiCorresponde();

        $sesionesActivas = Sesion::activas()
            ->where('id_usuario', auth()->id())
            ->with([
                'individuo',
                'dispositivo',
                'ultimaMedicion',
                'mediciones:id_medicion,id_sesion,temperatura,fecha_hora',
            ])
            ->orderByDesc('fecha_inicio')
            ->get();

        $tempPromedioSesion = $sesionesActivas
            ->map(fn ($s) => $s->ultimaMedicion?->temperatura)
            ->filter()
            ->avg();

        $duracionPromedio = $sesionesActivas
            ->map(fn ($s) => $s->minutos_transcurridos)
            ->avg();

        // Individuos y dispositivos elegibles para una sesion nueva:
        // los propios del usuario que no estan ya midiendo.
        $indActivos = Individuo::where('estado', 'activo')
            ->where('id_usuario', auth()->id())
            ->whereDoesntHave('sesiones', fn ($q) => $q->where('estado', Sesion::ESTADO_ACTIVA))
            ->orderBy('codigo_individuo')
            ->get();

        $dispositivosDisponibles = Dispositivo::where('id_usuario', auth()->id())
            ->whereDoesntHave('sesiones', fn ($q) => $q->where('estado', Sesion::ESTADO_ACTIVA))
            ->orderBy('nombre')
            ->get();

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
            'individuo_id'   => ['required',
                                 Rule::exists('individuos', 'id_individuo')
                                     ->where('id_usuario', auth()->id())],
            'dispositivo_id' => ['required',
                                 Rule::exists('dispositivos', 'id_dispositivo')
                                     ->where('id_usuario', auth()->id())],
            'duracion'       => ['nullable', 'integer', 'min:1', 'max:10080'],
            'intervalo'      => ['nullable', 'integer', 'min:1', 'max:1440'],
        ], [
            'individuo_id.exists'   => 'El ejemplar seleccionado no existe.',
            'dispositivo_id.exists' => 'El dispositivo seleccionado no existe.',
        ]);

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
            'estado'           => Sesion::ESTADO_ACTIVA,
        ]);

        return redirect()->route('sesiones')->with('exito', 'Sesión iniciada.');
    }

    public function show(Sesion $sesion)
    {
        abort_if($sesion->id_usuario !== auth()->id(), 403);

        $sesion->load(['individuo', 'dispositivo']);

        $mediciones = $sesion->mediciones()
            ->orderBy('fecha_hora')
            ->get(['fecha_hora', 'temperatura', 'alerta']);

        $formato = $this->formatoDeEtiqueta($mediciones);

        $serie = $mediciones->map(fn ($m) => [
            'hora'        => $m->fecha_hora?->format($formato),
            'temperatura' => (float) $m->temperatura,
            'alerta'      => $m->alerta,
        ])->values();

        $promedio = $mediciones->avg('temperatura');

        return view('admin.sesion_grafico', [
            'sesion'       => $sesion,
            'mediciones'   => $mediciones,
            'serie'        => $serie,
            'promedio'     => $promedio !== null ? round((float) $promedio, 1) : null,
        ]);
    }

    private function formatoDeEtiqueta($mediciones): string
    {
        $primera = $mediciones->first()?->fecha_hora;
        $ultima  = $mediciones->last()?->fecha_hora;

        if (! $primera || ! $ultima) {
            return 'H:i';
        }

        $minutos = $primera->diffInMinutes($ultima);

        if ($minutos < 60) {
            return 'H:i:s';
        }

        return $primera->isSameDay($ultima) ? 'H:i' : 'd/m H:i';
    }

    public function finalizar(Sesion $sesion): RedirectResponse
    {
        abort_if($sesion->id_usuario !== auth()->id(), 403);

        if (! $sesion->estaActiva()) {
            return back()->withErrors(['sesion' => 'La sesión ya estaba finalizada.']);
        }

        $fin = now();

        $sesion->update([
            'fecha_fin'       => $fin,
            'estado'          => Sesion::ESTADO_FINALIZADA,
            'duracion_sesion' => $sesion->fecha_inicio
                                    ? (int) round($sesion->fecha_inicio->diffInMinutes($fin))
                                    : null,
        ]);

        return back()->with('exito', 'Sesión finalizada.');
    }
}