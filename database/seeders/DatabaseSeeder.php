<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Crea un usuario de acceso para entornos de desarrollo.
     *
     * firstOrCreate para que correrlo dos veces no duplique nada
     * ni pise la clave de un usuario que ya exista.
     */
    public function run(): void
    {
        Usuario::firstOrCreate(
            ['email' => 'admin@bionea.local'],
            [
                'nombre'   => 'admin',
                // El cast 'hashed' del modelo aplica bcrypt al guardar.
                'password' => 'cambiar-esta-clave',
            ]
        );
    }
}
