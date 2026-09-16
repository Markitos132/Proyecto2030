<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class DispositivoController extends Controller
{
    public function index()
    {
        $dispositivos = Dispositivo::where('id_usuario', auth()->id())
            ->with([
                'sesionActiva.individuo',
                'sesionActiva.ultimaMedicion',
            ])->orderBy('id_dispositivo')->get();

        $porEstado = $dispositivos->groupBy->estado_calculado;

        return view('admin.dispositivos', [
            'dispositivos'      => $dispositivos,
            'totalDispositivos' => $dispositivos->count(),
            'onlineCount'       => $porEstado->get('online',  collect())->count(),
            'offlineCount'      => $porEstado->get('offline', collect())->count(),
            'sinReportarCount'  => $porEstado->get('warning', collect())->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);

        Dispositivo::create([
            'nombre'        => $datos['codigo_disp'],
            'mac_address'   => $datos['MAC'] ?? null,
            'f_alta'        => $datos['f_alta'] ?? now(),
            'observaciones' => $datos['observaciones'] ?? null,
            'estado'        => 'activo',
            'id_usuario'    => auth()->id(),
        ]);

        return redirect()->route('dispositivos')
            ->with('exito', 'Dispositivo dado de alta.');
    }

    public function show(Dispositivo $dispositivo)
    {
        abort_if($dispositivo->id_usuario !== auth()->id(), 403);

        $dispositivo->load([
            'notasDisp.usuario',
            'sesionActiva.individuo',
            'sesionActiva.ultimaMedicion',
        ]);

        return view('admin.dispositivo_ficha', compact('dispositivo'));
    }

    public function update(Request $request, Dispositivo $dispositivo): RedirectResponse
    {
        abort_if($dispositivo->id_usuario !== auth()->id(), 403);

        $datos = $this->validar($request, $dispositivo->id_dispositivo);

        $dispositivo->update([
            'nombre'        => $datos['codigo_disp'],
            'mac_address'   => $datos['MAC'] ?? null,
            'f_alta'        => $datos['f_alta'] ?? $dispositivo->f_alta,
            'observaciones' => $datos['observaciones'] ?? null,
        ]);

        return back()->with('exito', 'Dispositivo actualizado.');
    }

    public function destroy(Dispositivo $dispositivo): RedirectResponse
    {
        abort_if($dispositivo->id_usuario !== auth()->id(), 403);

        if ($dispositivo->sesiones()->exists()) {
            return back()->withErrors([
                'dispositivo' => 'No se puede eliminar: el dispositivo tiene sesiones registradas.',
            ]);
        }

        $dispositivo->delete();

        return redirect()->route('dispositivos')->with('exito', 'Dispositivo eliminado.');
    }

    private function validar(Request $request, ?int $ignorarId = null): array
    {
        // La MAC identifica el equipo físico, pero la unicidad ahora es
        // por usuario: dos usuarios distintos pueden registrar el mismo
        // ESP32 (misma MAC), cada uno como su propio dispositivo. Lo que
        // no puede pasar es que un mismo usuario la registre dos veces.
        $reglaMac = ['nullable', 'string', 'max:17',
                     'regex:/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/'];

        $reglaMac[] = Rule::unique('dispositivos', 'mac_address')
            ->where('id_usuario', auth()->id())
            ->ignore($ignorarId, 'id_dispositivo');

        return $request->validate([
            'codigo_disp'   => ['required', 'string', 'max:255'],
            'MAC'           => $reglaMac,
            'f_alta'        => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ], [
            'MAC.regex'  => 'La MAC debe tener el formato AA:BB:CC:DD:EE:FF.',
            'MAC.unique' => 'Ya tenés registrado un dispositivo con esa MAC.',
        ]);
    }
}