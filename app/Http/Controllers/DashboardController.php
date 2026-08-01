<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use App\Models\Medicion;
use App\Models\Sesion;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        // Sesiones de hoy mas cualquier sesion todavia activa, aunque haya
        // empezado ayer: una medicion larga no deberia desaparecer del panel
        // al cambiar la fecha.
        $sesionesDelDia = Sesion::with(['individuo', 'dispositivo', 'ultimaMedicion'])
            ->where(function ($q) {
                $q->whereDate('fecha_inicio', today())
                  ->orWhere('estado', Sesion::ESTADO_ACTIVA);
            })
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
