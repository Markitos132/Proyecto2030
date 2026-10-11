<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Configuración del panel: perfil del investigador, contraseña y
 * conectividad de las unidades.
 *
 * Toda la vista era una maqueta: los datos estaban escritos a mano en el
 * HTML y los formularios hacían preventDefault() para mostrar un tilde de
 * "guardado" que no guardaba nada.
 */
class ConfiguracionController extends Controller
{
    public function index(Request $request)
    {

        return view('admin.configuracion', [
            'usuario'         => $request->user(),
            'roles'           => Usuario::ROLES,
        ]);
    }

    public function perfil(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        // Bolsa de errores propia: los dos formularios conviven en la misma
        // página, y sin separarlos un error de contraseña aparecería también
        // sobre el formulario de perfil.
        $datos = $request->validateWithBag('perfil', [
            'nombre'      => ['required', 'string', 'max:100'],
            'apellido'    => ['nullable', 'string', 'max:100'],
            'institucion' => ['nullable', 'string', 'max:150'],
            // Ignora la fila propia: sin eso, guardar sin tocar el correo
            // chocaría contra su propio registro.
            'email'       => [
                'required',
                ...Usuario::REGLAS_EMAIL,
                Rule::unique('usuarios', 'email')->ignore($usuario->id_usuario, 'id_usuario'),
            ],
            'rol'         => ['nullable', Rule::in(Usuario::ROLES)],
            'telefono'    => ['nullable', 'string', 'max:40'],
        ], [
            'nombre.required' => 'El nombre no puede quedar vacío.',
            'email.required'  => 'El correo electrónico no puede quedar vacío.',
            'email.email'     => 'Escribí un correo electrónico válido.',
            // Es el caso de "federico@gmail" sin el .com: pasa el RFC pero
            // después el login no lo acepta.
            'email.regex'     => 'El dominio del correo está incompleto: falta la parte después del punto (por ejemplo, .com).',
            'email.unique'    => 'Ya existe otra cuenta con ese correo.',
            'rol.in'          => 'Seleccioná un rol de la lista.',
        ]);

        $usuario->update($datos);

        return redirect()->route('configuracion')
            ->with('exito', 'Perfil actualizado.');
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validateWithBag('password', [
            'password_actual' => ['required', 'string'],
            // 'confirmed' busca el campo password_nueva_confirmation.
            'password_nueva'  => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password_actual.required' => 'Ingresá tu contraseña actual.',
            'password_nueva.required'  => 'Ingresá la nueva contraseña.',
            'password_nueva.min'       => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password_nueva.confirmed' => 'La confirmación no coincide con la nueva contraseña.',
        ]);

        $usuario = $request->user();

        // Pedir la contraseña actual evita que alguien que encuentre la
        // sesión abierta en una máquina compartida se apropie de la cuenta.
        if (! Hash::check($request->input('password_actual'), $usuario->getAuthPassword())) {
            throw ValidationException::withMessages([
                'password_actual' => 'La contraseña actual no es correcta.',
            ])->errorBag('password');
        }

        if ($request->input('password_actual') === $request->input('password_nueva')) {
            throw ValidationException::withMessages([
                'password_nueva' => 'La contraseña nueva tiene que ser distinta de la actual.',
            ])->errorBag('password');
        }

        // El cast 'hashed' del modelo aplica bcrypt al guardar.
        $usuario->update(['password' => $request->input('password_nueva')]);

        // Identificador de sesión nuevo tras cambiar una credencial.
        //
        // No se usa logoutOtherDevices(): para que cierre las otras sesiones
        // hace falta el middleware AuthenticateSession, que no está puesto.
        // Sin él solo vuelve a hashear la clave, sin efecto real.
        $request->session()->regenerate();

        return redirect()->route('configuracion')
            ->with('exito', 'Contraseña actualizada.');
    }
}
