<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Autenticacion contra la tabla `usuarios`.
 *
 * Reemplaza el login por JWT en cookie de la version Node por el guard de
 * sesion de Laravel. Los hashes bcrypt existentes se siguen validando.
 */
class AuthController extends Controller
{
    public function mostrarLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'email'    => ['required', ...Usuario::REGLAS_EMAIL],
            'password' => ['required', 'string'],
        ], [
            'email.regex' => 'Escribí un correo electrónico válido.',
        ], [
            'email'    => 'correo electronico',
            'password' => 'contrasena',
        ]);

        $recordar = $request->boolean('remember');

        if (! Auth::attempt($credenciales, $recordar)) {
            // Mensaje generico a proposito: distinguir "usuario inexistente"
            // de "clave incorrecta" permite enumerar cuentas validas.
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no coinciden con nuestros registros.',
            ]);
        }

        $request->session()->regenerate();

        $this->reforzarHashSiHaceFalta($request->user(), $credenciales['password']);

        return redirect()->intended(route('dashboard'));
    }

    public function mostrarRegistro()
    {
        return view('auth.registro');
    }

    public function registro(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre'   => ['required', 'string', 'max:100'],
            'apellido' => ['nullable', 'string', 'max:100'],
            'email'    => ['required', ...Usuario::REGLAS_EMAIL, 'unique:usuarios,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.regex'        => 'El dominio del correo está incompleto: falta la parte después del punto (por ejemplo, .com).',
            'email.unique'       => 'Ya existe una cuenta con ese correo.',
            'password.confirmed' => 'Las contrasenas no coinciden.',
            'password.min'       => 'La contrasena debe tener al menos 8 caracteres.',
        ]);

        $usuario = Usuario::create([
            'nombre'   => $datos['nombre'],
            'apellido' => $datos['apellido'] ?? null,
            'email'    => $datos['email'],
            // El cast 'hashed' del modelo aplica bcrypt al guardar.
            'password' => $datos['password'],
        ]);

        // No se hace Auth::login: quien crea la cuenta ya esta logueado
        // y no queremos que la sesion salte al usuario recien creado.
        return redirect()->route('configuracion')
            ->with('exito', "Usuario {$usuario->nombre_completo} creado correctamente.");
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Los hashes heredados de la version Node se generaron con coste 5,
     * muy por debajo del 12 que usa Laravel. El login es el unico momento
     * en que tenemos la clave en claro, asi que aprovechamos para
     * regenerar el hash con el coste actual.
     */
    private function reforzarHashSiHaceFalta(Usuario $usuario, string $claveEnClaro): void
    {
        if (Hash::needsRehash($usuario->getAuthPassword())) {
            $usuario->forceFill(['password' => Hash::make($claveEnClaro)])->save();
        }
    }
}
