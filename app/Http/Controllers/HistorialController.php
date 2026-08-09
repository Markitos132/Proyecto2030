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

        // streamDownload en lugar de armar el texto entero en memoria: una
        // campaña larga son decenas de miles de lecturas, y el plan gratuito
        // de Render no tiene memoria de sobra.
        return response()->streamDownload(
            fn () => $this->escribirCsv($sesion),
            $this->nombreDelArchivo($sesion),
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    private function escribirCsv(Sesion $sesion): void
    {
        $salida = fopen('php://output', 'w');

        // Excel no detecta UTF-8 por su cuenta. Hoy los encabezados no
        // llevan tildes, pero la marca se deja igual: alcanza con que
        // alguien renombre una columna para que salgan rotas.
        fwrite($salida, "\xEF\xBB\xBF");

        fputcsv($salida, ['fecha', 'hora', 'temperatura'], ';');

        // Se recorre por bloques para no cargar en memoria todas las
        // mediciones de la sesión de una sola vez.
        //
        // El id va como segundo criterio y no es decorativo: chunk() pagina
        // por posición, y con un orden que empata (dos lecturas en el mismo
        // segundo, que pasa seguido con el simulador) el motor puede
        // devolverlas en distinto orden en cada bloque y terminar salteando
        // o repitiendo filas.
        $sesion->mediciones()
            ->orderBy('fecha_hora')
            ->orderBy('id_medicion')
            ->chunk(500, function ($mediciones) use ($salida) {
                foreach ($mediciones as $m) {
                    fputcsv($salida, [
                        $m->fecha_hora?->format('d/m/Y'),
                        $m->fecha_hora?->format('H:i:s'),
                        $this->decimal($m->temperatura),
                    ], ';');
                }
            });

        fclose($salida);
    }

    /**
     * Nombre del archivo: ejemplar, día y hora en que se midió.
     *
     *     LAG-001_2026-08-01_21h08.csv
     *
     * La fecha es la del inicio de la sesión, no la de la descarga: el
     * archivo tiene que decir cuándo se tomó el dato, no cuándo alguien
     * se lo bajó.
     *
     * La hora hace falta aunque parezca de más. LAG-001 tiene diez sesiones
     * el 1 de agosto; sin ella los diez archivos se llamarían igual y el
     * navegador les iría agregando (1), (2), (3) sin que se sepa cuál es
     * cuál. Va como "21h08" y no como "2108" para que se lea como hora.
     */
    private function nombreDelArchivo(Sesion $sesion): string
    {
        $codigo = $sesion->individuo?->codigo_individuo;

        // Los códigos son del estilo LAG-001, pero nada impide que alguien
        // cargue uno con una barra o un acento y arme un nombre inválido.
        $codigo = blank($codigo)
            ? 'sesion-'.$sesion->id_sesion
            : preg_replace('/[^A-Za-z0-9_-]/', '-', $codigo);

        $cuando = $sesion->fecha_inicio?->format('Y-m-d_H\hi')
            ?? 'sin-fecha';

        return "{$codigo}_{$cuando}.csv";
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
