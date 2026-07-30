<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Iniciar Sesión — BIONEA ORGANIKS</title>
  <link rel="stylesheet" href="/css/style_auth.css">
</head>
<body class="login-page">

  <div class="auth-layout">

    <!-- PANEL IZQUIERDO -->
    <div class="auth-brand">
      <div class="decoracion-grupo">
        <img src="/imagenes/topotopo.png" class="auth-brand-blob" alt="Ondas topográficas">
        <img src="/imagenes/lagar.gif" class="huellas" alt="Huellas">
      </div>
      <div class="auth-brand-logo">
        <img src="/imagenes/BIO.png" alt="Bionea Organiks">
      </div>
      <div class="auth-brand-body">
        <h2>Monitoreo térmico para investigación en ecofisiología</h2>
        <p>Accedé al sistema para gestionar sesiones activas, visualizar mediciones en tiempo real y consultar el historial de cada individuo bajo estudio.</p>
        <div class="auth-brand-stats">
        </div>
      </div>
      <div class="auth-brand-footer">Desarrollado junto a investigadores del IIGHI–CONICET</div>
    </div>

    <!-- PANEL DERECHO -->
    <div class="auth-form-side">
      <div class="mobile-logo-container">
        <img src="/imagenes/BIO.png" alt="Bionea Organiks Logo" class="mobile-brand-logo">
      </div>
      <div class="auth-form-card">
        <a href="/" class="back-to-home">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
          Volver al inicio
        </a>
        <h1>Iniciar Sesión</h1>
        <p class="auth-subtitle">Ingresá tus credenciales para acceder al panel.</p>

        <form id="loginForm" novalidate>
          <div class="form-group">
            <label for="email">Correo electrónico</label>
            <div class="input-wrap" id="emailWrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 6 8.5 6L20 6"/></svg>
              <input type="email" id="email" name="email" placeholder="nombre@ejemplo.com" autocomplete="email" required>
            </div>
            <div class="field-error" id="emailError">Ingresá un correo electrónico válido.</div>
          </div>

          <div class="form-group">
            <label for="password">Contraseña</label>
            <div class="input-wrap" id="passwordWrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
              <button type="button" class="toggle-pass" id="togglePassword" aria-label="Mostrar contraseña">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <div class="field-error" id="passwordError">Ingresá tu contraseña.</div>
          </div>

          <div class="form-options">
            <label class="checkbox-wrap">
              <input type="checkbox" id="remember">
              Recordarme
            </label>
            <a href="#" class="link-muted">¿Olvidaste tu contraseña?</a>
          </div>

          <button type="submit" class="btn-submit">Ingresar</button>

          <p class="auth-switch">¿No tenés una cuenta? <a href="/registro" class="link-muted">Registrate</a></p>
        </form>
      </div>
    </div>

  </div>

  <script>
    const form = document.getElementById('loginForm');
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
      const email = document.getElementById('email').value.trim();
      const password = document.getElementById('password').value;
      const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

      setError('emailWrap', 'emailError', !emailValid);
      setError('passwordWrap', 'passwordError', password.length === 0);

      if (emailValid && password.length > 0) {
        // Acá se conectará la lógica real de autenticación contra el backend.
        // Por el momento, redirige al dashboard como demostración del flujo.
        window.location.href = '../admin/dashboard.html';
      }
    });
  </script>

</body>
</html>
