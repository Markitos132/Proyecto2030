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

    /**
     * Descarga las mediciones de las sesiones tildadas como CSV.
     *
     * Una fila por lectura, con los datos del ejemplar y de la sesión
     * repetidos al lado. Es redundante a propósito: así el archivo se abre
     * en cualquier planilla o en R sin tener que cruzarlo con nada.
     */
    public function exportar(Request $request): StreamedResponse
    {
        $datos = $request->validate([
            // Llegan como "12,15,26" desde la casilla de cada fila.
            'ids' => ['required', 'string', 'regex:/^\d+(,\d+)*$/'],
        ], [
            'ids.required' => 'No hay sesiones seleccionadas para exportar.',
            'ids.regex'    => 'La lista de sesiones no es válida.',
        ]);

        $ids = array_slice(array_unique(explode(',', $datos['ids'])), 0, 500);

        $sesiones = Sesion::whereIn('id_sesion', $ids)
            ->with(['individuo', 'dispositivo'])
            ->orderBy('fecha_inicio')
            ->get();

        abort_if($sesiones->isEmpty(), 404, 'Ninguna de esas sesiones existe.');

        $nombre = 'bionea-mediciones-'.now()->format('Y-m-d-Hi').'.csv';

        // streamDownload en lugar de armar el texto entero en memoria: una
        // campaña larga son decenas de miles de lecturas, y el plan gratuito
        // de Render no tiene memoria de sobra.
        return response()->streamDownload(
            fn () => $this->escribirCsv($sesiones),
            $nombre,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    private function escribirCsv($sesiones): void
    {
        $salida = fopen('php://output', 'w');

        // Excel no detecta UTF-8 por su cuenta: sin esta marca al inicio,
        // "Liolaemus chacoensis" está bien pero "Duración" y "sesión" salen
        // con caracteres rotos.
        fwrite($salida, "\xEF\xBB\xBF");

        fputcsv($salida, [
            'id_sesion',
            'individuo',
            'especie',
            'sexo',
            'estadio',
            'dispositivo',
            'inicio_sesion',
            'fin_sesion',
            'duracion_min',
            'intervalo_min',
            'temp_min_configurada',
            'temp_max_configurada',
            'fecha',
            'hora',
            'temperatura_c',
            'alerta',
            'fuera_de_rango',
        ], ';');

        foreach ($sesiones as $sesion) {
            $comunes = [
                $sesion->id_sesion,
                $sesion->individuo?->codigo_individuo,
                $sesion->individuo?->especie,
                $sesion->individuo?->sexo,
                $sesion->individuo?->estadio,
                $sesion->dispositivo?->nombre,
                $sesion->fecha_inicio?->format('d/m/Y H:i:s'),
                $sesion->fecha_fin?->format('d/m/Y H:i:s'),
                $sesion->duracion_sesion,
                $sesion->intervalo_minuto,
                $this->decimal($sesion->temp_min),
                $this->decimal($sesion->temp_max),
            ];

            // Se recorre por bloques para no cargar en memoria todas las
            // mediciones de la sesión de una sola vez.
            //
            // El id va como segundo criterio y no es decorativo: chunk()
            // pagina por posición, y con un orden que empata (dos lecturas
            // en el mismo segundo, que pasa seguido con el simulador) el
            // motor puede devolverlas en distinto orden en cada bloque y
            // terminar salteando o repitiendo filas.
            $sesion->mediciones()
                ->orderBy('fecha_hora')
                ->orderBy('id_medicion')
                ->chunk(500, function ($mediciones) use ($salida, $comunes) {
                    foreach ($mediciones as $m) {
                        fputcsv($salida, array_merge($comunes, [
                            $m->fecha_hora?->format('d/m/Y'),
                            $m->fecha_hora?->format('H:i:s'),
                            $this->decimal($m->temperatura),
                            $m->alerta,
                            $m->fueraDeRango() ? 'si' : 'no',
                        ]), ';');
                    }
                });
        }

        fclose($salida);
    }

    /**
     * Excel en castellano espera la coma como separador decimal. Con el
     * punto lee "31.5" como texto y no deja calcular promedios.
     */
    private function decimal($valor): string
    {
        return $valor === null ? '' : str_replace('.', ',', (string) $valor);
    }
}
