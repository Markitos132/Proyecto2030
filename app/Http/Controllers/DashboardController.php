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

        $tempPromedio = Medicion::query()
            ->whereHas('sesion', fn ($q) => $q->where('estado', Sesion::ESTADO_ACTIVA)
                                               ->where('id_usuario', auth()->id()))
            ->where('fecha_hora', '>=', now()->subHour())
            ->avg('temperatura');

        return view('admin.dashboard', [
            'sesionesDelDia'       => $sesionesDelDia,
            'sesionesActivasCount' => Sesion::activas()->where('id_usuario', auth()->id())->count(),
            'dispositivosOnline'   => $dispositivosOnline,
            'totalDispositivos'    => $dispositivos->count(),
            'tempPromedio'         => $tempPromedio,
        ]);
    }
}