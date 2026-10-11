@extends('layouts.dashboard')
@section('title', 'Configuración')
    @section('content')
      <div class="page-header">
        <div>
          <h1 class="page-title">Configuración</h1>
          <p class="page-sub">Administrá tu perfil de investigador y la seguridad de tu cuenta</p>
        </div>
      </div>

      <!-- PANEL: PERFIL -->
      <div class="settings-panel active" id="panel-perfil">
        <div class="settings-card">
          <div class="settings-card-header">
            <h2>Información personal</h2>
            <p>Estos datos identifican al investigador responsable de cada sesión registrada.</p>
          </div>

          <div class="profile-avatar-row">
            <div class="profile-avatar-lg">{{ $usuario->iniciales }}</div>
            <div class="profile-avatar-actions">
              {{-- Los botones de foto no están: subir imágenes necesita
                   almacenamiento persistente, y el disco de Render se borra
                   en cada despliegue. Las iniciales salen del nombre. --}}
              <span class="field-hint">El avatar se arma con las iniciales de tu nombre.</span>
            </div>
          </div>

          @if ($errors->perfil->any())
            <div class="aviso aviso-error" role="alert">
              <ul>
                @foreach ($errors->perfil->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form method="POST" action="{{ route('configuracion.perfil') }}" id="profileForm">
            @csrf
            @method('PUT')
            <div class="settings-form-grid">
              <div class="form-field">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $usuario->nombre) }}" maxlength="100" required>
              </div>
              <div class="form-field">
                <label for="apellido">Apellido</label>
                <input type="text" id="apellido" name="apellido" value="{{ old('apellido', $usuario->apellido) }}" maxlength="100">
              </div>
              <div class="form-field full">
                <label for="institucion">Institución / Laboratorio</label>
                <input type="text" id="institucion" name="institucion" value="{{ old('institucion', $usuario->institucion) }}" maxlength="150" placeholder="Ej: IIGHI–CONICET">
              </div>
              <div class="form-field full">
                <label for="email">Correo electrónico</label>
                {{-- El pattern repite la validación del login. type="email" a
                     secas acepta dominios sin punto, y guardar uno así dejaba
                     la cuenta sin poder iniciar sesión. --}}
                <input type="email" id="email" name="email" value="{{ old('email', $usuario->email) }}"
                       maxlength="255"
                       pattern="[^\s@]+@[^\s@]+\.[^\s@]+"
                       title="Escribí un correo completo, con dominio y terminación (por ejemplo, nombre@ejemplo.com)."
                       required>
                <span class="field-hint">Con este correo iniciás sesión. Cambiarlo cambia también tu usuario de acceso.</span>
              </div>
              <div class="form-field">
                <label for="rol">Rol en el proyecto</label>
                <select id="rol" name="rol">
                  <option value="">Sin especificar</option>
                  @foreach ($roles as $rol)
                    <option value="{{ $rol }}" {{ old('rol', $usuario->rol) === $rol ? 'selected' : '' }}>{{ $rol }}</option>
                  @endforeach
                </select>
                <span class="field-hint">Es descriptivo: no cambia lo que podés ver o hacer en el panel.</span>
              </div>
              <div class="form-field">
                <label for="telefono">Teléfono (opcional)</label>
                <input type="tel" id="telefono" name="telefono" value="{{ old('telefono', $usuario->telefono) }}" maxlength="40" placeholder="+54 9 ...">
              </div>
            </div>

            <div class="settings-actions" style="margin-top:1.75rem;">
              <button type="submit" class="btn-save">Guardar cambios</button>
              <a href="{{ route('configuracion') }}" class="btn-cancel">Cancelar</a>
            </div>
          </form>
        </div>

        <section id="seguridad">
          <div class="settings-card">
            <div class="settings-card-header">
              <h2>Seguridad</h2>
              <p>Actualizá tu contraseña de acceso al sistema.</p>
            </div>

            @if ($errors->password->any())
              <div class="aviso aviso-error" role="alert">
                <ul>
                  @foreach ($errors->password->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <form method="POST" action="{{ route('configuracion.password') }}" id="passwordForm">
              @csrf
              @method('PUT')
              <div class="settings-form-grid">
                <div class="form-field full">
                  <label for="passActual">Contraseña actual</label>
                  <input type="password" id="passActual" name="password_actual" placeholder="••••••••" autocomplete="current-password" required>
                </div>
                <div class="form-field">
                  <label for="passNueva">Nueva contraseña</label>
                  <input type="password" id="passNueva" name="password_nueva" placeholder="Mínimo 8 caracteres" minlength="8" autocomplete="new-password" required>
                </div>
                <div class="form-field">
                  <label for="passConfirm">Confirmar nueva contraseña</label>
                  <input type="password" id="passConfirm" name="password_nueva_confirmation" placeholder="Repetí la contraseña" minlength="8" autocomplete="new-password" required>
                </div>
              </div>
              <div class="settings-actions" style="margin-top:1.75rem;">
                <button type="submit" class="btn-save">Actualizar contraseña</button>
              </div>
            </form>
          </div>
        </section>
      </div>
    @endsection
