<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use App\Models\Medicion;
use App\Models\Sesion;
use App\Services\CierreDeSesiones;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function index(CierreDeSesiones $cierre)
    {
        // Cierra sesiones que dejaron de reportar antes de contarlas.
        $cierre->revisarSiCorresponde();

        // Mismo conjunto que devuelve PanelEstadoController: el scope es
        // compartido justamente para que no puedan divergir.
        $sesionesDelDia = Sesion::visiblesEnPanel()
            ->with(['individuo', 'dispositivo', 'ultimaMedicion'])
            ->orderByDesc('fecha_inicio')
            ->get();

        $dispositivos = Dispositivo::with('sesionActiva.ultimaMedicion')->get();

        // estado_calculado es un accessor, no una columna: no se puede
        // contar con SQL. El volumen de dispositivos es chico, asi que
        // filtrar en memoria no es problema.
        $dispositivosOnline = $dispositivos
            ->filter(fn ($d) => $d->estado_calculado !== 'offline')
            ->count();

        $tempPromedio = Medicion::query()
            ->whereHas('sesion', fn ($q) => $q->where('estado', Sesion::ESTADO_ACTIVA))
            ->where('fecha_hora', '>=', now()->subHour())
            ->avg('temperatura');

        return view('admin.dashboard', [
            'sesionesDelDia'       => $sesionesDelDia,
            'sesionesActivasCount' => Sesion::activas()->count(),
            'dispositivosOnline'   => $dispositivosOnline,
            'totalDispositivos'    => $dispositivos->count(),
            'tempPromedio'         => $tempPromedio,
        ]);
    }
}
