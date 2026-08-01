<?php

namespace App\Http\Controllers;

use App\Models\Individuo;
use App\Models\Sesion;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class HistorialController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ], [
            'hasta.after_or_equal' => 'La fecha "hasta" no puede ser anterior a "desde".',
        ]);

        $sesionesFinalizadas = Sesion::finalizadas()
            ->with(['individuo', 'dispositivo', 'mediciones'])
            ->when($request->filled('individuo'), fn ($q) =>
                $q->whereHas('individuo', fn ($i) =>
                    $i->where('codigo_individuo', 'ilike', '%'.$request->individuo.'%')))
            ->when($request->filled('especie'), fn ($q) =>
                $q->whereHas('individuo', fn ($i) => $i->where('especie', $request->especie)))
            ->when($request->filled('desde'), fn ($q) =>
                $q->whereDate('fecha_inicio', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) =>
                $q->whereDate('fecha_inicio', '<=', $request->hasta))
            ->orderByDesc('fecha_inicio')
            ->limit(200)
            ->get();

        // Para el desplegable de filtro por especie.
        $especiesDisponibles = Individuo::query()
            ->whereNotNull('especie')
            ->distinct()
            ->orderBy('especie')
            ->pluck('especie');

        return view('admin.historial', compact('sesionesFinalizadas', 'especiesDisponibles'));
    }
}
