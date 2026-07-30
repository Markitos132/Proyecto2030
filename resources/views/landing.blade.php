<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BIONEA ORGANIKS — Monitoreo térmico para investigación biológica</title>
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght=400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/css/style_index.css"/>
</head>
<body>
<!-- NAV -->
<nav>
  <a class="logo" href="#">
    <img src="/imagenes/BIO.png" alt="Logo BIONEA ORGANIKS" class="logo-img">
  </a>
  <ul class="nav-links" id="navLinks">
    <li><a href="#inicio">Inicio</a></li>
    <li><a href="#sistema">Sistema</a></li>
    <li><a href="#como-funciona">Cómo Funciona</a></li>
  </ul>
  <button class="nav-toggle" id="navToggle" aria-label="Abrir menú">
    <span></span><span></span><span></span>
  </button>
</nav>

<!-- HERO -->
<section class="hero" id="inicio">
  <h1 class="heroh1TG">Monitoreo inteligente de temperatura</h1>
  <h1 class="heroh1TM">para investigación biológica</h1>
  <p>BIONEA permite registrar y visualizar la temperatura corporal de individuos ectotérmicos en tiempo real, con almacenamiento de datos y acceso remoto desde cualquier dispositivo.</p>
  <div class="hero-buttons">
    <a href="/login" class="btn btn-dark">Iniciar Sesión</a>
    <a href="#sistema" class="btn btn-outline"><img src="/icons/system.svg" alt="">Conocé el sistema</a>
  </div>

  <!-- Dashboard mockup -->
  <div class="hero-mockup">
    <div class="mockup-header">
      <p>Bienvenido, IIGHI — CONICET</p>
      <p>Las métricas de la sesión activa están listas para revisar.</p>
    </div>
    <div class="mockup-stats">
      <div class="stat-card">
        <div class="label">Individuos activos</div>
        <div class="value">6</div>
        <div class="sub">↑ +2 esta sesión</div>
      </div>
      <div class="stat-card yellow">
        <div class="label">Temperatura promedio</div>
        <div class="value">33.7 °C</div>
        <div class="sub">Rango: 29–38 °C</div>
      </div>
      <div class="stat-card">
        <div class="label">Registros hoy</div>
        <div class="value">1.204</div>
        <div class="sub">↑ +12% vs. ayer</div>
      </div>
    </div>
    <div class="chart-area">
      <div class="chart-label">Tendencia de temperatura — últimas 6 horas</div>
      <div class="chart-bars">
        <div class="bar" style="height:35%"></div>
        <div class="bar" style="height:40%"></div>
        <div class="bar" style="height:38%"></div>
        <div class="bar" style="height:50%"></div>
        <div class="bar" style="height:60%"></div>
        <div class="bar" style="height:55%"></div>
        <div class="bar" style="height:65%"></div>
        <div class="bar" style="height:70%"></div>
        <div class="bar highlight" style="height:88%"></div>
        <div class="bar" style="height:75%"></div>
        <div class="bar" style="height:72%"></div>
        <div class="bar" style="height:68%"></div>
        <div class="bar" style="height:74%"></div>
        <div class="bar" style="height:80%"></div>
        <div class="bar" style="height:76%"></div>
        <div class="bar" style="height:70%"></div>
        <div class="bar" style="height:65%"></div>
        <div class="bar" style="height:60%"></div>
        <div class="bar" style="height:55%"></div>
        <div class="bar" style="height:58%"></div>
      </div>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="features" id="sistema">
  <div class="container">
    <div class="features-header">
      <h2 class="section-title" style="text-align:center;">Todo lo necesario para monitorear,<span style="display:block;">registrar y analizar datos</span>
      </h2>
    </div>
    
    <div class="features-grid">
      <div class="features-left">
        <h2>aca va el video del esp32 funcionando</h2>
      </div>
      <div class="feature-cards">
        <div class="feature-card">
          <div class="feature-card-header">
            <span class="dot dot-green"></span>
            Monitorear
          </div>
          <p>Lectura de temperatura corporal en tiempo real directamente desde el sensor cloacal conectado a cada unidad ESP32.</p>
        </div>
        <div class="feature-card">
          <div class="feature-card-header">
            <span class="dot dot-yellow"></span>
            Registrar
          </div>
          <p>Guardado automático de mediciones por sesión, asociadas a cada individuo con sus metadatos: especie, código y fecha.</p>
        </div>
        <div class="feature-card">
          <div class="feature-card-header">
            <span class="dot dot-orange"></span>
            Analizar
          </div>
          <p>Consulta del historial completo de cada individuo con visualización de datos para análisis estadístico posterior.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- DESIGNED FOR -->
<section class="designed" id="como-funciona">
  <div class="container">
    <h2 class="heroh1TG">Diseñado para</h2>
    <h2 class="heroh1TM">la investigación</h2>
    <div class="designed-cards">
      <div class="designed-card">
        <img src="/icons/monitor.svg" class="card-icon" alt="">
        <h3>Monitoreo continuo</h3>
        <p>Permite registrar datos térmicos sin intervención manual, durante el tiempo y con el intervalo que el investigador configure.</p>
      </div>
      <div class="designed-card">
        <img src="/icons/lagartija.svg" class="card-icon" alt="">
        <h3>Trazabilidad por individuo</h3>
        <p>Cada sesión queda vinculada a un animal específico y su unidad de hardware, evitando errores de etiquetado manuales.</p>
      </div>
      <div class="designed-card">
        <img src="/icons/acceso.svg" class="card-icon" alt="">
        <h3>Acceso centralizado</h3>
        <p>Toda la información se consulta desde una sola plataforma web, accesible desde computadora, tablet o celular.</p>
      </div>
    </div>
  </div>
</section>

<!-- CASE / CONTEXT -->
<section class="case">
  <div class="container">
    <div class="case-grid">
      <div class="case-img">
        <div class="case-img-placeholder">
          Foto de investigadores trabajando con lagartijas en laboratorio
        </div>
      </div>
      <div class="case-content">
        <h3>¿Por qué BIONEA para el CONICET?</h3>
        <p>Los equipos comerciales disponibles para monitoreo térmico en laboratorio son de origen importado, costosos y sin posibilidad de personalización ni acceso remoto. Investigadores del IIGHI-CONICET identificaron esta brecha tecnológica para sus estudios de ecofisiología en lagartijas de la región chaqueña.</p>
        <p>BIONEA cubre esa brecha: hardware accesible con firmware programable, almacenamiento local y en nube, y una interfaz web que funciona desde cualquier dispositivo, a una fracción del costo de los modelos importados.</p>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta">
  <div class="container">
    <h2>¿Listo para comenzar?</h2>
    <p>Ingresá al sistema para gestionar sesiones, visualizar mediciones y consultar el historial de cada individuo.</p>
    <a href="/login" class="btn btn-dark">INICIAR SESIÓN</a>
  </div>
</section>

<footer>
  <p><strong>BIONEA ORGANIKS</strong> — Sistema de monitoreo térmico modular para investigación en ectotermos</p>
</footer>

<script>
  const toggle = document.getElementById('navToggle');
  const links = document.getElementById('navLinks');
  toggle.addEventListener('click', () => links.classList.toggle('open'));
  links.querySelectorAll('a').forEach(a => a.addEventListener('click', () => links.classList.remove('open')));
</script>
</body>
</html>
