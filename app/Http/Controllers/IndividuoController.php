<?php

namespace App\Http\Controllers;

use App\Models\Individuo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class IndividuoController extends Controller
{
    /**
     * Estados posibles de un individuo.
     *
     * La UI es inconsistente: el desplegable de la ficha ofrece
     * 'activo' / 'recapturado' / 'liberado', pero varias vistas comparan
     * contra 'Liberado/Perdido'. Se aceptan todos para no romper datos
     * existentes; convendria unificarlo mas adelante.
     */
    private const ESTADOS = ['activo', 'recapturado', 'liberado', 'Liberado/Perdido'];

    public function index(Request $request)
    {
        $individuos = Individuo::query()
            ->with('sesionActiva.dispositivo')
            ->when($request->filled('especie'), fn ($q) => $q->where('especie', $request->especie))
            ->when($request->filled('estado'),  fn ($q) => $q->where('estado', $request->estado))
            ->when($request->filled('codigo'),  fn ($q) =>
                $q->where('codigo_individuo', 'ilike', '%'.$request->codigo.'%'))
            ->orderBy('codigo_individuo')
            ->get();

        $todos = Individuo::with('sesionActiva')->get();

        return view('admin.individuos', [
            'individuos'           => $individuos,
            'totalIndividuos'      => $todos->count(),
            'activosCount'         => $todos->where('estado', 'activo')->count(),
            'conDispositivoCount'  => $todos->filter(fn ($i) => $i->sesionActiva)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validarAlta($request);

        Individuo::create([
            'codigo_individuo'    => $datos['codigo_individuo'],
            'especie'             => $this->resolverOtro($request, 'especie', 'otra_especie'),
            'sexo'                => $datos['sexo'] ?? null,
            'estadio'             => $this->resolverOtro($request, 'estadio', 'otro_estadio'),
            // El estado reproductivo solo tiene sentido en hembras.
            'estado_reproductivo' => ($request->sexo === 'Hembra')
                                        ? $request->estado_reproductivo
                                        : null,
            'svl'                 => $datos['svl'] ?? null,
            'peso'                => $datos['peso'] ?? null,
            'observaciones'       => $datos['observaciones'] ?? null,
            'estado'              => 'activo',
        ]);

        return redirect()->route('individuos')
            ->with('exito', 'Ejemplar guardado correctamente.');
    }

    public function show(Individuo $individuo)
    {
        $individuo->load([
            'notasIndividuo.usuario',
            'sesionActiva.dispositivo',
            'sesiones.dispositivo',
            'sesiones.mediciones',
        ]);

        $sesiones = $individuo->sesiones->sortByDesc('fecha_inicio');

        // La vista ofrece un desplegable con especies y estadios conocidos,
        // mas una opcion "otra". Si el valor guardado no esta en la lista,
        // hay que preseleccionar "otra" y rellenar el campo de texto libre.
        $especiesConocidas = ['Liolaemus chacoensis'];
        $estadiosConocidos = ['Adulto', 'Juvenil', 'Indeterminado'];

        $esOtraEspecie = $individuo->especie && ! in_array($individuo->especie, $especiesConocidas, true);
        $esOtroEstadio = $individuo->estadio && ! in_array($individuo->estadio, $estadiosConocidos, true);

        return view('admin.individuo_ficha', [
            'individuo'           => $individuo,
            'sesiones'            => $sesiones,
            'sesionesFinalizadas' => $sesiones->where('estado', \App\Models\Sesion::ESTADO_FINALIZADA),
            'esOtraEspecie'       => $esOtraEspecie,
            'esOtroEstadio'       => $esOtroEstadio,
            'especieVal'          => $esOtraEspecie ? $individuo->especie : '',
            'estadioVal'          => $esOtroEstadio ? $individuo->estadio : '',
        ]);
    }

    public function update(Request $request, Individuo $individuo): RedirectResponse
    {
        $request->validate([
            'codigo'              => ['required', 'string', 'max:50'],
            'especie_select'      => ['nullable', 'string', 'max:255'],
            'especie_otra'        => ['nullable', 'string', 'max:255'],
            'sexo'                => ['nullable', 'string', 'max:50'],
            'estadio_select'      => ['nullable', 'string', 'max:50'],
            'estadio_otro'        => ['nullable', 'string', 'max:50'],
            'estado_reproductivo' => ['nullable', 'string', 'max:50'],
            'svl'                 => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'peso'                => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'estado'              => ['nullable', 'string', 'in:'.implode(',', self::ESTADOS)],
        ]);

        $individuo->update([
            'codigo_individuo'    => $request->codigo,
            'especie'             => $this->resolverOtro($request, 'especie_select', 'especie_otra'),
            'sexo'                => $request->sexo,
            'estadio'             => $this->resolverOtro($request, 'estadio_select', 'estadio_otro'),
            'estado_reproductivo' => ($request->sexo === 'Hembra')
                                        ? $request->estado_reproductivo
                                        : null,
            'svl'                 => $request->svl,
            'peso'                => $request->peso,
            'estado'              => $request->estado ?? $individuo->estado,
        ]);

        return back()->with('exito', 'Ficha actualizada.');
    }

    public function destroy(Individuo $individuo): RedirectResponse
    {
        // Un individuo con mediciones asociadas es dato de campo: borrarlo
        // perderia el historial y ademas violaria la clave foranea.
        if ($individuo->sesiones()->exists()) {
            return back()->withErrors([
                'individuo' => 'No se puede eliminar: el ejemplar tiene sesiones registradas. '
                             . 'Marcalo como Liberado/Perdido en su lugar.',
            ]);
        }

        $individuo->delete();

        return redirect()->route('individuos')->with('exito', 'Ejemplar eliminado.');
    }

    // ── Helpers ─────────────────────────────────────────────

    private function validarAlta(Request $request): array
    {
        return $request->validate([
            'codigo_individuo'    => ['required', 'string', 'max:50'],
            'especie'             => ['required', 'string', 'max:255'],
            'otra_especie'        => ['nullable', 'required_if:especie,otra', 'string', 'max:255'],
            'sexo'                => ['nullable', 'string', 'max:50'],
            'estadio'             => ['nullable', 'string', 'max:50'],
            'otro_estadio'        => ['nullable', 'required_if:estadio,otro', 'string', 'max:50'],
            'estado_reproductivo' => ['nullable', 'string', 'max:50'],
            'svl'                 => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'peso'                => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'observaciones'       => ['nullable', 'string', 'max:255'],
        ], [
            'otra_especie.required_if' => 'Indicá cuál es la especie.',
            'otro_estadio.required_if' => 'Indicá cuál es el estadio.',
        ]);
    }

    /**
     * Los desplegables ofrecen una opcion "otra" / "otro" que habilita un
     * campo de texto libre. Devuelve el valor que corresponda guardar.
     */
    private function resolverOtro(Request $request, string $campoSelect, string $campoLibre): ?string
    {
        $valor = $request->input($campoSelect);

        return in_array($valor, ['otra', 'otro'], true)
            ? $request->input($campoLibre)
            : $valor;
    }
}
