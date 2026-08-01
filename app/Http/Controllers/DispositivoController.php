<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DispositivoController extends Controller
{
    public function index()
    {
        $dispositivos = Dispositivo::with([
            'sesionActiva.individuo',
            'sesionActiva.ultimaMedicion',
        ])->orderBy('id_dispositivo')->get();

        // estado_calculado deriva de ultima_conexion y del ritmo de
        // mediciones, no de la columna `estado`, que se desactualiza.
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
        ]);

        return redirect()->route('dispositivos')
            ->with('exito', 'Dispositivo dado de alta.');
    }

    public function show(Dispositivo $dispositivo)
    {
        $dispositivo->load([
            'notasDisp.usuario',
            'sesionActiva.individuo',
            'sesionActiva.ultimaMedicion',
        ]);

        return view('admin.dispositivo_ficha', compact('dispositivo'));
    }

    public function update(Request $request, Dispositivo $dispositivo): RedirectResponse
    {
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
        // La MAC identifica fisicamente al equipo: no puede repetirse.
        $reglaMac = ['nullable', 'string', 'max:17',
                     'regex:/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/'];

        $unica = 'unique:dispositivos,mac_address';
        if ($ignorarId !== null) {
            $unica .= ','.$ignorarId.',id_dispositivo';
        }
        $reglaMac[] = $unica;

        return $request->validate([
            'codigo_disp'   => ['required', 'string', 'max:255'],
            'MAC'           => $reglaMac,
            'f_alta'        => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ], [
            'MAC.regex'  => 'La MAC debe tener el formato AA:BB:CC:DD:EE:FF.',
            'MAC.unique' => 'Ya hay un dispositivo registrado con esa MAC.',
        ]);
    }
}
