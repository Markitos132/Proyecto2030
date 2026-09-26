<?php

namespace App\Http\Controllers;

use App\Models\Individuo;
use App\Models\Sesion;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            ->where('id_usuario', auth()->id())
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
            ->paginate(15)
            ->withQueryString();

        // Para el desplegable de filtro por especie.
        $especiesDisponibles = Individuo::query()
            ->where('id_usuario', auth()->id())
            ->whereNotNull('especie')
            ->distinct()
            ->orderBy('especie')
            ->pluck('especie');

        return view('admin.historial', compact('sesionesFinalizadas', 'especiesDisponibles'));
    }

    /**
     * Descarga las mediciones de UNA sesión como CSV.
     *
     * Tres columnas: fecha, hora y temperatura. Una fila por lectura.
     *
     * Es de a una a propósito. Cuando se tildan varias sesiones, la vista
     * llama a esta ruta una vez por cada una y el navegador baja un archivo
     * por sesión, en vez de un solo archivo con todo mezclado.
     */
    public function exportar(Request $request): StreamedResponse
    {
        $datos = $request->validate([
            'sesion' => ['required', 'integer', 'exists:sesiones,id_sesion'],
        ], [
            'sesion.required' => 'Falta indicar qué sesión exportar.',
            'sesion.exists'   => 'Esa sesión no existe.',
        ]);

        $sesion = Sesion::with('individuo:id_individuo,codigo_individuo')
            ->findOrFail($datos['sesion']);

        abort_if($sesion->id_usuario !== auth()->id(), 403);

        return response()->streamDownload(
            fn () => $this->escribirCsv($sesion),
            $this->nombreDelArchivo($sesion),
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    private function escribirCsv(Sesion $sesion): void
    {
    $salida = fopen('php://output', 'w');

    fwrite($salida, "\xEF\xBB\xBF");

    fputcsv($salida, ['individuo', 'fecha', 'hora', 'n_medicion', 'temperatura', 'promedio', 'minima', 'maxima'], ';');

    $individuo = $sesion->individuo?->codigo_individuo ?? '';

    $promediotodosdecimales = $sesion->mediciones()->avg('temperatura');
    $promedio  = $promediotodosdecimales !== null ? $this->decimal(round($promediotodosdecimales, 2)): '';

    $minima = $this->decimal($sesion->mediciones()->min('temperatura'));
    $maxima = $this->decimal($sesion->mediciones()->max('temperatura'));

    $numero = 0;

    $sesion->mediciones()
        ->orderBy('fecha_hora')
        ->orderBy('id_medicion')
        ->chunk(500, function ($mediciones) use ($salida, $individuo, $promedio, &$numero) {
            foreach ($mediciones as $m) {
                $numero++;

                fputcsv($salida, [
                    $individuo,
                    $m->fecha_hora?->format('d/m/Y'),
                    $m->fecha_hora?->format('H:i:s'),
                    $numero,
                    $this->decimal($m->temperatura),
                    //aca solo se escribe el promedio en la primera fila, sino se repite en la cantidad de columnas que tengan mediciones
                    $numero == 1 ? $promedio : '',
                    $numero == 1 ? $minima : '',
                    $numero == 1 ? $maxima : '',
                ], ';');
            }
        });

    fclose($salida);
}

    private function nombreDelArchivo(Sesion $sesion): string
    {
        $codigo = $sesion->individuo?->codigo_individuo;

        $codigo = blank($codigo)
            ? 'sesion-'.$sesion->id_sesion
            : preg_replace('/[^A-Za-z0-9_-]/', '-', $codigo);

        $cuando = $sesion->fecha_inicio?->format('Y-m-d_H\hi')
            ?? 'sin-fecha';

        return "{$codigo}_{$cuando}.csv";
    }

    private function decimal($valor): string
    {
        return $valor === null ? '' : str_replace('.', ',', (string) $valor);
    }
}