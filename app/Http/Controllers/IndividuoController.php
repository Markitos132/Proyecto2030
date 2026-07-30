<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Individuo;

class IndividuoController extends Controller
{

    // Método para mostrar la vista con la tabla y el modal
    public function index()
    {
        // Pasamos un array vacío temporalmente para que el @foreach de la tabla no dé error
        $individuos = Individuo::all();
        
        return view('admin.individuos', compact('individuos'));
    }

    // Método para guardar/probar el envío
    public function store(Request $request)
    {
        // 1. Resolver la especie si eligió "otra"
        $especieFinal = ($request->especie === 'otra') 
            ? $request->otra_especie 
            : $request->especie;

        // 2. Resolver estado reproductivo (solo si es Hembra)
        $estadoReproductivo = ($request->sexo === 'Hembra') 
            ? $request->estado_reproductivo 
            : null;

        // 3. Imprimir todos los datos recibidos en pantalla y frenar la ejecución
        // 3. ¡AQUÍ SE GUARDA EN LA BASE DE DATOS!
        Individuo::create([
            'codigo_individuo'    => $request->codigo_individuo,
            'especie'             => $especieFinal,
            'sexo'                => $request->sexo,
            'estadio'             => $request->estadio,
            'estado_reproductivo' => $estadoReproductivo,
            'svl'                 => $request->svl,
            'peso'                => $request->peso,
        ]);

        // 4. Volvemos a la vista con mensaje de éxito
        return redirect()->route('individuos')->with('success', '¡Ejemplar guardado correctamente!');
    }
}