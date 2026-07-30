<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Crear Cuenta — BIONEA ORGANIKS</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/css/style_auth.css">
</head>
<body class="register-page">

  <div class="auth-layout">

    <!-- PANEL IZQUIERDO -->
    <div class="auth-brand">
      <div class="auth-brand-blob"></div>
      <div class="auth-brand-logo">
        <img src="/imagenes/BIO.png" alt="Bionea Organiks">
      </div>
      <div class="auth-brand-body">
        <h2>Sumate al sistema de monitoreo térmico</h2>
        <p>Creá tu cuenta para configurar dispositivos, asignar individuos a cada sesión y acceder al historial de mediciones desde cualquier lugar.</p>
      </div>
      <img class="brand-image" src="/imagenes/lagartija.png" alt="">
      <div class="auth-brand-footer">Desarrollado junto a investigadores del IIGHI–CONICET</div>
    </div>

    <!-- PANEL DERECHO -->
    <div class="auth-form-side">
      <div class="auth-form-card">
        <a href="/" class="back-to-home">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
          Volver al inicio
        </a>
        <h1>Crear Cuenta</h1>
        <p class="auth-subtitle">Completá tus datos para registrarte en el sistema.</p>

        <form id="registerForm" novalidate>
          <div class="form-row">
            <div class="form-group">
              <label for="nombre">Nombre</label>
              <div class="input-wrap" id="nombreWrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a7 7 0 0 1 14 0v1"/></svg>
                <input type="text" id="nombre" name="nombre" placeholder="Tu nombre" autocomplete="given-name" required>
              </div>
              <div class="field-error" id="nombreError">Ingresá tu nombre.</div>
            </div>

            <div class="form-group">
              <label for="apellido">Apellido</label>
              <div class="input-wrap" id="apellidoWrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a7 7 0 0 1 14 0v1"/></svg>
                <input type="text" id="apellido" name="apellido" placeholder="Tu apellido" autocomplete="family-name" required>
              </div>
              <div class="field-error" id="apellidoError">Ingresá tu apellido.</div>
            </div>
          </div>

          <div class="form-group">
            <label for="email">Correo electrónico</label>
            <div class="input-wrap" id="emailWrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 6 8.5 6L20 6"/></svg>
              <input type="email" id="email" name="email" placeholder="nombre@ejemplo.com" autocomplete="email" required>
            </div>
            <div class="field-error" id="emailError">Ingresá un correo electrónico válido.</div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="password">Contraseña</label>
              <div class="input-wrap" id="passwordWrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres" autocomplete="new-password" required>
                <button type="button" class="toggle-pass" id="togglePassword" aria-label="Mostrar contraseña">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
              <div class="field-error" id="passwordError">La contraseña debe tener al menos 8 caracteres.</div>
              <div class="field-hint" id="passwordHint">Debe tener al menos 8 caracteres.</div>
            </div>
            <div class="form-group">
              <label for="confirmPassword">Confirmar contraseña</label>
              <div class="input-wrap" id="confirmPasswordWrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Repetí tu contraseña" autocomplete="new-password" required>
              </div>
              <div class="field-error" id="confirmPasswordError">Las contraseñas no coinciden.</div>
            </div>
          </div>

          <button type="submit" class="btn-submit">Crear Cuenta</button>

          <p class="auth-switch">¿Ya tenés una cuenta? <a href="/login" class="link-muted">Iniciá sesión</a></p>
        </form>
      </div>
    </div>

  </div>

  <script>
    const form = document.getElementById('registerForm');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    togglePassword.addEventListener('click', () => {
      const isHidden = passwordInput.type === 'password';
      passwordInput.type = isHidden ? 'text' : 'password';
    });

    function setError(wrapId, errorId, show) {
      document.getElementById(wrapId).classList.toggle('has-error', show);
      document.getElementById(errorId).style.display = show ? 'block' : 'none';
    }

    form.addEventListener('submit', (e) => {
      e.preventDefault();

      const nombre = document.getElementById('nombre').value.trim();
      const apellido = document.getElementById('apellido').value.trim();
      const email = document.getElementById('email').value.trim();
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirmPassword').value;
      const terms = document.getElementById('terms').checked;

      const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
      const passwordValid = password.length >= 8;
      const passwordsMatch = password === confirmPassword && confirmPassword.length > 0;

      setError('nombreWrap', 'nombreError', nombre.length === 0);
      setError('apellidoWrap', 'apellidoError', apellido.length === 0);
      setError('institucionWrap', 'institucionError', institucion.length === 0);
      setError('emailWrap', 'emailError', !emailValid);
      setError('passwordWrap', 'passwordError', !passwordValid);
      setError('confirmPasswordWrap', 'confirmPasswordError', !passwordsMatch);

      const allValid = nombre && apellido && institucion && emailValid && passwordValid && passwordsMatch && terms;

      if (allValid) {
        // Acá se conectará la lógica real de registro contra el backend.
        // Por el momento, redirige al login como demostración del flujo.
        window.location.href = 'login.html';
      }
    });
  </script>

</body>
</html>
