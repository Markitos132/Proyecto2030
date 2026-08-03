@extends('layouts.dashboard')
@section('title', 'Configuración')
    @section('content')
      <div class="page-header">
        <div>
          <h1 class="page-title">Configuración</h1>
          <p class="page-sub">Administrá tu perfil de investigador y la conectividad del sistema con las unidades de laboratorio.</p>
        </div>
      </div>

      <div class="settings-tabs">
        <button class="settings-tab active" data-tab="perfil">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a7 7 0 0 1 14 0v1"/></svg>
          Perfil
        </button>
        <button class="settings-tab" data-tab="conectividad">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5a10 10 0 0 1 14 0M8 16a6 6 0 0 1 8 0M12 19.5h.01"/></svg>
          Conectividad y notificaciones
        </button>
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
                <input type="email" id="email" name="email" value="{{ old('email', $usuario->email) }}" maxlength="255" required>
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

      <!-- PANEL: CONECTIVIDAD Y NOTIFICACIONES -->
      <div class="settings-panel" id="panel-conectividad">
        <div class="settings-card">
          <div class="settings-card-header">
            <h2>Conexión de las unidades</h2>
            <p>Datos que hay que grabar en el firmware para que un ESP32 pueda reportar a este panel.</p>
          </div>

          {{-- Acá había un formulario de SSID y contraseña de WiFi. No podía
               funcionar: la red está compilada dentro del firmware, y sin
               WiFi el dispositivo nunca llega al servidor para recibir una
               configuración nueva. En su lugar va lo que sí hace falta. --}}

          <div class="wifi-status-row">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5a10 10 0 0 1 14 0M8 16a6 6 0 0 1 8 0M12 19.5h.01"/></svg>
            @if ($totalUnidades === 0)
              Todavía no hay unidades dadas de alta.
            @else
              {{ $unidadesActivas }} de {{ $totalUnidades }}
              {{ $totalUnidades === 1 ? 'unidad reportando' : 'unidades reportando' }}
              en los últimos {{ \App\Models\Dispositivo::UMBRAL_OFFLINE_MIN }} minutos
            @endif
          </div>

          <div class="settings-form-grid">
            <div class="form-field full">
              <label for="urlServidor">Dirección del servidor</label>
              <div class="copy-field">
                <input type="text" id="urlServidor" value="{{ $urlServidor }}" readonly>
                <button type="button" class="btn-cancel" data-copiar="urlServidor">Copiar</button>
              </div>
              <span class="field-hint">Va en <code>URL_BASE</code> dentro del firmware.</span>
            </div>

            <div class="form-field full">
              <label for="claveIngesta">Clave de la API</label>
              @if ($claveIngesta)
                <div class="copy-field">
                  <input type="password" id="claveIngesta" value="{{ $claveIngesta }}" readonly>
                  <button type="button" class="btn-cancel" data-ver="claveIngesta">Mostrar</button>
                  <button type="button" class="btn-cancel" data-copiar="claveIngesta">Copiar</button>
                </div>
                <span class="field-hint">
                  Va en <code>API_KEY</code> dentro del firmware. Cada dispositivo la manda
                  en la cabecera <code>X-API-Key</code>. Para cambiarla hay que editar la
                  variable <code>BIONEA_API_KEY</code> del servidor y regrabar los equipos.
                </span>
              @else
                <div class="aviso aviso-error" role="alert">
                  No hay clave configurada: el endpoint de ingesta acepta datos de
                  cualquier origen. Definí <code>BIONEA_API_KEY</code> en las variables
                  de entorno del servidor.
                </div>
              @endif
            </div>
          </div>

          <p class="field-hint" style="margin-top:1rem;">
            Además, cada equipo tiene que estar dado de alta en
            <a href="{{ route('dispositivos') }}">Dispositivos</a> con su dirección MAC real:
            así es como el panel lo reconoce y le asigna sesiones.
          </p>
        </div>

        <div class="settings-card">
          <div class="settings-card-header">
            <h2>Notificaciones</h2>
            <p>Elegí cuándo querés recibir avisos sobre el estado de tus sesiones y dispositivos.</p>
          </div>

          <div class="aviso aviso-info" role="status">
            Tus preferencias se guardan, pero el envío todavía no está activo:
            falta configurar un servicio de correo en el servidor.
          </div>

          <form method="POST" action="{{ route('configuracion.preferencias') }}" id="notifsForm">
            @csrf
            @method('PUT')

            {{-- El input oculto hace que un toggle apagado también viaje.
                 Sin él, una casilla sin tildar simplemente no se envía y el
                 servidor no puede distinguir "apagado" de "no tocado". --}}
            <div class="toggle-row">
              <div class="toggle-row-text">
                <strong>Alertas de temperatura fuera de rango</strong>
                <span>Avisa por correo cuando una sesión activa supera el rango esperado para la especie.</span>
              </div>
              <label class="switch">
                <input type="hidden" name="notif_fuera_rango" value="0">
                <input type="checkbox" name="notif_fuera_rango" value="1" {{ $usuario->notif_fuera_rango ? 'checked' : '' }}>
                <span class="switch-slider"></span>
              </label>
            </div>

            <div class="toggle-row">
              <div class="toggle-row-text">
                <strong>Dispositivo sin reportar</strong>
                <span>Avisa cuando una unidad deja de enviar datos durante más del doble de su intervalo configurado.</span>
              </div>
              <label class="switch">
                <input type="hidden" name="notif_sin_reportar" value="0">
                <input type="checkbox" name="notif_sin_reportar" value="1" {{ $usuario->notif_sin_reportar ? 'checked' : '' }}>
                <span class="switch-slider"></span>
              </label>
            </div>

            <div class="toggle-row">
              <div class="toggle-row-text">
                <strong>Resumen diario por correo</strong>
                <span>Un resumen con las sesiones del día y el estado general de las unidades, todas las noches a las 21:00.</span>
              </div>
              <label class="switch">
                <input type="hidden" name="notif_resumen_diario" value="0">
                <input type="checkbox" name="notif_resumen_diario" value="1" {{ $usuario->notif_resumen_diario ? 'checked' : '' }}>
                <span class="switch-slider"></span>
              </label>
            </div>

            <div class="toggle-row">
              <div class="toggle-row-text">
                <strong>Notificaciones push en el navegador</strong>
                <span>Mostrar una notificación del sistema mientras tenés la página abierta en otra pestaña.</span>
              </div>
              <label class="switch">
                <input type="hidden" name="notif_push" value="0">
                <input type="checkbox" name="notif_push" value="1" {{ $usuario->notif_push ? 'checked' : '' }}>
                <span class="switch-slider"></span>
              </label>
            </div>

            <div class="settings-actions" style="margin-top:1.5rem;">
              <button type="submit" class="btn-save">Guardar preferencias</button>
            </div>
          </form>
        </div>
      </div>
    @endsection

@push('scripts')
<script>
  // Ojo: este bloque se ejecuta en el mismo ámbito global que el script del
  // layout. Declarar acá un const con un nombre que el layout ya usa
  // (sidebar, userBtn, openMenu...) lanza un SyntaxError que mata el archivo
  // entero y deja las pestañas sin funcionar. Por eso todo va dentro de la
  // función y con nombres propios.
  (function () {
    const pestanas = document.querySelectorAll('.settings-tab');
    const paneles  = document.querySelectorAll('.settings-panel');

    function abrirPestana(nombre) {
      const panel = document.getElementById('panel-' + nombre);
      if (!panel) return;

      pestanas.forEach(t => t.classList.toggle('active', t.dataset.tab === nombre));
      paneles.forEach(p => p.classList.remove('active'));
      panel.classList.add('active');
    }

    pestanas.forEach(pestana => {
      pestana.addEventListener('click', () => abrirPestana(pestana.dataset.tab));
    });

    // Permite enlazar directo a una pestaña: /configuracion#conectividad
    if (location.hash === '#conectividad') {
      abrirPestana('conectividad');
    }

    // Mostrar/ocultar la clave de la API.
    document.querySelectorAll('[data-ver]').forEach(boton => {
      boton.addEventListener('click', () => {
        const campo = document.getElementById(boton.dataset.ver);
        const oculto = campo.type === 'password';
        campo.type = oculto ? 'text' : 'password';
        boton.textContent = oculto ? 'Ocultar' : 'Mostrar';
      });
    });

    // Copiar al portapapeles.
    document.querySelectorAll('[data-copiar]').forEach(boton => {
      boton.addEventListener('click', async () => {
        const campo = document.getElementById(boton.dataset.copiar);
        const original = boton.textContent;

        try {
          // navigator.clipboard solo existe en contextos seguros (https o
          // localhost). En http hay que caer al método viejo.
          if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(campo.value);
          } else {
            const tipoPrevio = campo.type;
            campo.type = 'text';
            campo.select();
            document.execCommand('copy');
            campo.type = tipoPrevio;
          }
          boton.textContent = 'Copiado';
        } catch (e) {
          boton.textContent = 'No se pudo';
        }

        setTimeout(() => { boton.textContent = original; }, 2000);
      });
    });
  })();
</script>
@endpush
