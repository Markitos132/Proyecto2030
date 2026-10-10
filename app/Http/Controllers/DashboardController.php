<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use App\Models\Sesion;
use App\Services\CierreDeSesiones;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function index(CierreDeSesiones $cierre)
    {
        $cierre->revisarSiCorresponde();

        // Mismo conjunto que devuelve PanelEstadoController: el scope es
        // compartido justamente para que no puedan divergir.
        $sesionesDelDia = Sesion::visiblesEnPanel()
            ->where('id_usuario', auth()->id())
            ->with(['individuo', 'dispositivo', 'ultimaMedicion'])
            ->orderByDesc('fecha_inicio')
            ->get();

        $dispositivos = Dispositivo::where('id_usuario', auth()->id())
            ->with('sesionActiva.ultimaMedicion')
            ->get();

        $dispositivosOnline = $dispositivos
            ->filter(fn ($d) => $d->estado_calculado !== 'offline')
            ->count();

        // Misma cuenta que hace Sesiones Activas: promedio de la última
        // lectura de cada sesión en curso (sin ventana de tiempo).
        $activas = $sesionesDelDia->where('estado', Sesion::ESTADO_ACTIVA);

        $tempPromedio = $activas
            ->map(fn ($s) => $s->ultimaMedicion?->temperatura)
            ->filter()
            ->avg();

        return view('admin.dashboard', [
            'sesionesDelDia'       => $sesionesDelDia,
            'sesionesActivasCount' => $activas->count(),
            'dispositivosOnline'   => $dispositivosOnline,
            'totalDispositivos'    => $dispositivos->count(),
            'tempPromedio'         => $tempPromedio,
        ]);
    }
}