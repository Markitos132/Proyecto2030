@extends('layouts.dashboard')
@section('title', 'Dashboard')
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
            <div class="profile-avatar-lg">MO</div>
            <div class="profile-avatar-actions">
              <button class="btn-text-action">Cambiar foto</button>
              <button class="btn-text-action danger">Quitar foto</button>
            </div>
          </div>

          <form id="profileForm">
            <div class="settings-form-grid">
              <div class="form-field">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" value="Marcos">
              </div>
              <div class="form-field">
                <label for="apellido">Apellido</label>
                <input type="text" id="apellido" value="Ortiz">
              </div>
              <div class="form-field full">
                <label for="institucion">Institución / Laboratorio</label>
                <input type="text" id="institucion" value="IIGHI–CONICET">
              </div>
              <div class="form-field full">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" value="marcos.ortiz@iighi-conicet.gob.ar">
                <span class="field-hint">Se usa para notificaciones de alertas y recuperación de cuenta.</span>
              </div>
              <div class="form-field">
                <label for="rol">Rol en el proyecto</label>
                <select id="rol">
                  <option>Investigador principal</option>
                  <option>Investigador asistente</option>
                  <option>Estudiante / becario</option>
                </select>
              </div>
              <div class="form-field">
                <label for="telefono">Teléfono (opcional)</label>
                <input type="tel" id="telefono" placeholder="+54 9 ...">
              </div>
            </div>

            <div class="settings-actions" style="margin-top:1.75rem;">
              <button type="submit" class="btn-save">Guardar cambios</button>
              <button type="button" class="btn-cancel">Cancelar</button>
              <span class="save-confirm" id="confirmPerfil">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                Cambios guardados
              </span>
            </div>
          </form>
        </div>
        <section id="Cambiar Contraseña">
          <div class="settings-card">
            <div class="settings-card-header">
              <h2>Seguridad</h2>
              <p>Actualizá tu contraseña de acceso al sistema.</p>
            </div>
            <form id="passwordForm">
              <div class="settings-form-grid">
                <div class="form-field full">
                  <label for="passActual">Contraseña actual</label>
                  <input type="password" id="passActual" placeholder="••••••••">
                </div>
                <div class="form-field">
                  <label for="passNueva">Nueva contraseña</label>
                  <input type="password" id="passNueva" placeholder="Mínimo 8 caracteres">
                </div>
                <div class="form-field">
                  <label for="passConfirm">Confirmar nueva contraseña</label>
                  <input type="password" id="passConfirm" placeholder="Repetí la contraseña">
                </div>
              </div>
              <div class="settings-actions" style="margin-top:1.75rem;">
                <button type="submit" class="btn-save">Actualizar contraseña</button>
                <span class="save-confirm" id="confirmPass">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                  Contraseña actualizada
                </span>
              </div>
            </form>
          </div>
        </section>
      </div>

      <!-- PANEL: CONECTIVIDAD Y NOTIFICACIONES -->
      <div class="settings-panel" id="panel-conectividad">
        <div class="settings-card">
          <div class="settings-card-header">
            <h2>Red WiFi del laboratorio</h2>
            <p>Las unidades ESP32 se conectan a esta red para sincronizar datos en tiempo real.</p>
          </div>

          <div class="wifi-status-row">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5a10 10 0 0 1 14 0M8 16a6 6 0 0 1 8 0M12 19.5h.01"/></svg>
            Conectado — 7 de 8 unidades sincronizadas correctamente
          </div>

          <form id="wifiForm">
            <div class="settings-form-grid">
              <div class="form-field full">
                <label for="ssid">Nombre de red (SSID)</label>
                <input type="text" id="ssid" value="Laboratorio-IIGHI-2G">
              </div>
              <div class="form-field full">
                <label for="wifiPass">Contraseña de red</label>
                <input type="password" id="wifiPass" value="••••••••••">
              </div>
            </div>
            <div class="settings-actions" style="margin-top:1.75rem;">
              <button type="submit" class="btn-save">Guardar red</button>
              <button type="button" class="btn-cancel">Probar conexión</button>
              <span class="save-confirm" id="confirmWifi">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                Red guardada
              </span>
            </div>
          </form>
        </div>

        <div class="settings-card">
          <div class="settings-card-header">
            <h2>Notificaciones</h2>
            <p>Elegí cuándo querés recibir avisos sobre el estado de tus sesiones y dispositivos.</p>
          </div>

          <div class="toggle-row">
            <div class="toggle-row-text">
              <strong>Alertas de temperatura fuera de rango</strong>
              <span>Avisa por correo cuando una sesión activa supera el rango esperado para la especie.</span>
            </div>
            <label class="switch">
              <input type="checkbox" checked>
              <span class="switch-slider"></span>
            </label>
          </div>

          <div class="toggle-row">
            <div class="toggle-row-text">
              <strong>Dispositivo sin reportar</strong>
              <span>Avisa cuando una unidad deja de enviar datos durante más del doble de su intervalo configurado.</span>
            </div>
            <label class="switch">
              <input type="checkbox" checked>
              <span class="switch-slider"></span>
            </label>
          </div>

          <div class="toggle-row">
            <div class="toggle-row-text">
              <strong>Resumen diario por correo</strong>
              <span>Un resumen con las sesiones del día y el estado general de las unidades, todas las noches a las 21:00.</span>
            </div>
            <label class="switch">
              <input type="checkbox">
              <span class="switch-slider"></span>
            </label>
          </div>

          <div class="toggle-row">
            <div class="toggle-row-text">
              <strong>Notificaciones push en el navegador</strong>
              <span>Mostrar una notificación del sistema mientras tenés la página abierta en otra pestaña.</span>
            </div>
            <label class="switch">
              <input type="checkbox">
              <span class="switch-slider"></span>
            </label>
          </div>

          <div class="settings-actions" style="margin-top:1.5rem;">
            <button type="button" class="btn-save" id="saveNotifs">Guardar preferencias</button>
            <span class="save-confirm" id="confirmNotifs">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
              Preferencias guardadas
            </span>
          </div>
        </div>
      </div>
    @endsection
@push('scripts')
<script>

  const userBtn = document.getElementById("userMenuBtn");
  const userMenu = document.getElementById("userMenu");

  userBtn.addEventListener("click", (e)=>{
    e.stopPropagation();
    userMenu.classList.toggle("open");
  });

  document.addEventListener("click", ()=>{
    userMenu.classList.remove("open");
  });

  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const menuToggle = document.getElementById('menuToggle');

  function openMenu() {
    sidebar.classList.add('open');
    overlay.classList.add('open');
  }
  function closeMenu() {
    sidebar.classList.remove('open');
    overlay.classList.remove('open');
  }

  menuToggle.addEventListener('click', openMenu);
  overlay.addEventListener('click', closeMenu);
  sidebar.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMenu));

  // Tabs
  const tabs = document.querySelectorAll('.settings-tab');
  const panels = document.querySelectorAll('.settings-panel');

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      panels.forEach(p => p.classList.remove('active'));
      tab.classList.add('active');
      document.getElementById('panel-' + tab.dataset.tab).classList.add('active');
    });
  });

  // Confirmaciones de guardado (simulado, sin backend todavía)
  function flashConfirm(el) {
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 2500);
  }

  document.getElementById('profileForm').addEventListener('submit', (e) => {
    e.preventDefault();
    flashConfirm(document.getElementById('confirmPerfil'));
  });

  document.getElementById('passwordForm').addEventListener('submit', (e) => {
    e.preventDefault();
    flashConfirm(document.getElementById('confirmPass'));
  });

  document.getElementById('wifiForm').addEventListener('submit', (e) => {
    e.preventDefault();
    flashConfirm(document.getElementById('confirmWifi'));
  });

  document.getElementById('saveNotifs').addEventListener('click', () => {
    flashConfirm(document.getElementById('confirmNotifs'));
  });
</script>
@endpush
