{{-- Simulador del ESP32, copiado de bionea/BioNEA-Organiks y adaptado a esta app. --}}
{{-- El bloque de abajo esta sin procesar para que Blade no toque el CSS ni el JS. --}}
@verbatim
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Simulador ESP32 — BioNEA Organiks</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --surface-0:     #f0efe8;
      --surface-1:     #f8f7f2;
      --surface-2:     #ffffff;
      --border:        rgba(0,0,0,0.10);
      --border-strong: rgba(0,0,0,0.18);
      --text-primary:   #1a1a18;
      --text-secondary: #5a5a55;
      --text-muted:     #9a9990;
      --radius:         8px;
      --bg-success:  #E1F5EE;
      --bg-warning:  #FAEEDA;
      --bg-danger:   #FCEBEB;
      --bg-accent:   #E6F1FB;
      --text-success: #0F6E56;
      --text-warning: #854F0B;
      --text-danger:  #A32D2D;
      --text-accent:  #185FA5;
      --fill-accent:  #378ADD;
      --on-accent:    #ffffff;
      --font-mono: 'Menlo', 'Consolas', 'Liberation Mono', monospace;
    }

    @media (prefers-color-scheme: dark) {
      :root {
        --surface-0:     #111110;
        --surface-1:     #1c1c1a;
        --surface-2:     #242422;
        --border:        rgba(255,255,255,0.10);
        --border-strong: rgba(255,255,255,0.18);
        --text-primary:   #e8e7e2;
        --text-secondary: #a8a79f;
        --text-muted:     #6a6a62;
        --bg-success:  #085041;
        --bg-warning:  #633806;
        --bg-danger:   #501313;
        --bg-accent:   #042C53;
        --text-success: #5DCAA5;
        --text-warning: #EF9F27;
        --text-danger:  #F09595;
        --text-accent:  #85B7EB;
        --fill-accent:  #185FA5;
        --on-accent:    #ffffff;
      }
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: var(--surface-0);
      color: var(--text-primary);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      padding: 2rem 1rem;
    }

    .container {
      width: 100%;
      max-width: 640px;
    }

    .header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 1.5rem;
    }
    .header-icon {
      width: 40px;
      height: 40px;
      border-radius: var(--radius);
      background: var(--bg-success);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--text-success);
      font-size: 20px;
      flex-shrink: 0;
    }
    .header-text h1 {
      font-size: 16px;
      font-weight: 500;
      color: var(--text-primary);
    }
    .header-text p {
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 1px;
    }
    .status-badge {
      margin-left: auto;
      font-size: 11px;
      font-weight: 500;
      padding: 3px 10px;
      border-radius: 20px;
      flex-shrink: 0;
    }
    .badge-idle   { background: var(--surface-1); color: var(--text-muted); border: 0.5px solid var(--border-strong); }
    .badge-active { background: var(--bg-success); color: var(--text-success); }
    .badge-error  { background: var(--bg-danger);  color: var(--text-danger); }

    .section {
      background: var(--surface-2);
      border: 0.5px solid var(--border);
      border-radius: 12px;
      padding: 1rem 1.25rem;
      margin-bottom: 10px;
    }
    .section-title {
      font-size: 10px;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.07em;
      color: var(--text-muted);
      margin-bottom: 12px;
    }

    .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .grid3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }

    .field { display: flex; flex-direction: column; gap: 5px; }
    .field label { font-size: 12px; color: var(--text-secondary); }

    input[type=text],
    input[type=number] {
      width: 100%;
      padding: 8px 10px;
      font-size: 13px;
      font-family: inherit;
      background: var(--surface-1);
      border: 0.5px solid var(--border-strong);
      border-radius: var(--radius);
      color: var(--text-primary);
      outline: none;
      transition: border-color 0.15s;
    }
    input[type=text]:focus,
    input[type=number]:focus {
      border-color: var(--fill-accent);
    }
    .mono input { font-family: var(--font-mono); font-size: 11px; }

    .divider { border: none; border-top: 0.5px solid var(--border); margin: 12px 0; }

    input[type=range] {
      -webkit-appearance: none;
      appearance: none;
      flex: 1;
      height: 4px;
      background: var(--border-strong);
      border-radius: 2px;
      outline: none;
      cursor: pointer;
    }
    input[type=range]::-webkit-slider-thumb {
      -webkit-appearance: none;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      background: var(--fill-accent);
      border: 2px solid var(--surface-2);
      cursor: pointer;
    }
    input[type=range]::-moz-range-thumb {
      width: 18px;
      height: 18px;
      border-radius: 50%;
      background: var(--fill-accent);
      border: 2px solid var(--surface-2);
      cursor: pointer;
    }

    .slider-row { display: flex; align-items: center; gap: 12px; }
    .slider-val { font-size: 14px; font-weight: 500; min-width: 48px; text-align: right; color: var(--text-primary); flex-shrink: 0; }

    .temp-display { display: flex; align-items: baseline; gap: 6px; margin-bottom: 10px; }
    .temp-big { font-size: 34px; font-weight: 500; line-height: 1; }
    .temp-unit { font-size: 14px; color: var(--text-muted); }
    .range-badge {
      font-size: 11px;
      font-weight: 500;
      padding: 2px 8px;
      border-radius: 20px;
      margin-left: 4px;
    }
    .range-ok   { background: var(--bg-success); color: var(--text-success); }
    .range-warn { background: var(--bg-warning); color: var(--text-warning); }

    .stats {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
      margin-bottom: 10px;
    }
    .stat {
      background: var(--surface-2);
      border: 0.5px solid var(--border);
      border-radius: var(--radius);
      padding: 10px 12px;
    }
    .stat-val { font-size: 22px; font-weight: 500; line-height: 1.2; }
    .stat-lbl { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

    .btn-row { display: flex; gap: 8px; margin-bottom: 10px; }
    button {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      padding: 9px 14px;
      font-size: 13px;
      font-family: inherit;
      font-weight: 500;
      border-radius: var(--radius);
      border: 0.5px solid var(--border-strong);
      background: var(--surface-2);
      color: var(--text-primary);
      cursor: pointer;
      transition: background 0.12s, opacity 0.12s, transform 0.1s;
    }
    button:hover:not(:disabled) { background: var(--surface-1); }
    button:active:not(:disabled) { transform: scale(0.98); }
    button:disabled { opacity: 0.4; cursor: not-allowed; }
    .btn-primary {
      background: var(--fill-accent);
      color: var(--on-accent);
      border-color: var(--fill-accent);
    }
    .btn-primary:hover:not(:disabled) { opacity: 0.88; background: var(--fill-accent); }
    .btn-danger {
      background: var(--bg-danger);
      color: var(--text-danger);
      border-color: var(--text-danger);
    }
    .btn-small { flex: 0 0 auto; padding: 9px 14px; }

    .progress-wrap { margin-bottom: 10px; }
    .progress-meta {
      display: flex;
      justify-content: space-between;
      font-size: 11px;
      color: var(--text-muted);
      margin-bottom: 5px;
    }
    .progress-bar { height: 3px; background: var(--surface-1); border-radius: 2px; overflow: hidden; }
    .progress-fill { height: 100%; background: var(--fill-accent); border-radius: 2px; width: 0%; transition: width 0.5s linear; }
    .progress-foot {
      display: flex;
      justify-content: space-between;
      font-size: 11px;
      color: var(--text-muted);
      margin-top: 4px;
    }

    .log-wrap {
      background: var(--surface-2);
      border: 0.5px solid var(--border);
      border-radius: 12px;
      overflow: hidden;
    }
    .log-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 12px;
      border-bottom: 0.5px solid var(--border);
    }
    .log-header span { font-size: 12px; color: var(--text-secondary); }
    .log-session { font-size: 11px; color: var(--text-muted); font-family: var(--font-mono); }
    .log {
      padding: 8px 12px;
      font-family: var(--font-mono);
      font-size: 11px;
      line-height: 1.6;
      height: 220px;
      overflow-y: auto;
    }
    .log-line { display: flex; gap: 8px; }
    .lt  { color: var(--text-muted); flex-shrink: 0; }
    .lok  { color: var(--text-success); }
    .lerr { color: var(--text-danger); }
    .lwarn{ color: var(--text-warning); }
    .linf { color: var(--text-secondary); }

    @media (max-width: 480px) {
      .grid3 { grid-template-columns: 1fr 1fr; }
      .stats { grid-template-columns: repeat(2, 1fr); }
    }
  </style>
</head>
<body>
<div class="container">

  <div class="header">
    <div class="header-icon"><i class="ti ti-device-desktop-analytics"></i></div>
    <div class="header-text">
      <h1>Simulador ESP32</h1>
      <p>BioNEA Organiks — envío de mediciones al servidor</p>
    </div>
    <span id="statusBadge" class="status-badge badge-idle">Inactivo</span>
  </div>

  <div class="section mono">
    <p class="section-title">Servidor</p>
    <div class="field">
      <label>URL del endpoint</label>
      <input type="text" id="url" value="/bionea/guardar">
    </div>
  </div>

  <div class="section">
    <p class="section-title">Individuo</p>
    <div class="grid2">
      <div class="field">
        <label>Código</label>
        <input type="text" id="individuo" value="LAG-001">
      </div>
      <div class="field">
        <label>Especie</label>
        <input type="text" id="especie" value="Liolaemus sp.">
      </div>
    </div>
  </div>

  <div class="section">
    <p class="section-title">Sesión</p>
    <div class="grid3" style="margin-bottom:12px">
      <div class="field">
        <label>Duración (min)</label>
        <input type="number" id="duracion" value="2" min="1" max="60">
      </div>
      <div class="field">
        <label>Temp. mínima (°C)</label>
        <input type="number" id="tmin" value="20">
      </div>
      <div class="field">
        <label>Temp. máxima (°C)</label>
        <input type="number" id="tmax" value="38">
      </div>
    </div>
    <hr class="divider">
    <label style="font-size:12px;color:var(--text-secondary);display:block;margin-bottom:6px">Intervalo entre mediciones</label>
    <div class="slider-row">
      <input type="range" id="intervalo" min="3" max="60" value="5" step="1">
      <span class="slider-val"><span id="intervaloVal">5</span> s</span>
    </div>
  </div>

  <div class="section">
    <p class="section-title">Temperatura simulada</p>
    <div class="temp-display">
      <span class="temp-big" id="tempBig">28.0</span>
      <span class="temp-unit">°C</span>
      <span id="rangeBadge" class="range-badge range-ok">En rango</span>
    </div>
    <div class="slider-row">
      <input type="range" id="tempSlider" min="10" max="50" value="28" step="0.5">
      <span class="slider-val" style="font-size:12px;color:var(--text-muted)">10 – 50 °C</span>
    </div>
  </div>

  <div class="stats">
    <div class="stat">
      <div class="stat-val" id="sEnviadas">0</div>
      <div class="stat-lbl">Enviadas</div>
    </div>
    <div class="stat">
      <div class="stat-val lok" id="sOk">0</div>
      <div class="stat-lbl">OK</div>
    </div>
    <div class="stat">
      <div class="stat-val lerr" id="sErr">0</div>
      <div class="stat-lbl">Errores</div>
    </div>
    <div class="stat">
      <div class="stat-val lwarn" id="sRango">0</div>
      <div class="stat-lbl">Fuera de rango</div>
    </div>
  </div>

  <div class="btn-row">
    <button id="btnIniciar" class="btn-primary" onclick="iniciarSesion()">
      <i class="ti ti-player-play"></i> Iniciar sesión
    </button>
    <button id="btnFin" class="btn-danger" onclick="finalizarSesion()" disabled>
      <i class="ti ti-player-stop"></i> Finalizar
    </button>
    <button class="btn-small" onclick="limpiarLog()" title="Limpiar log">
      <i class="ti ti-trash"></i>
    </button>
  </div>

  <div class="progress-wrap" id="progressWrap" style="display:none">
    <div class="progress-meta">
      <span>Progreso de la sesión</span>
      <span id="progressLabel">0%</span>
    </div>
    <div class="progress-bar">
      <div class="progress-fill" id="progressFill"></div>
    </div>
    <div class="progress-foot">
      <span>Próxima medición en <strong id="cuentaRegresiva">—</strong> s</span>
      <span id="tiempoRestante"></span>
    </div>
  </div>

  <div class="log-wrap">
    <div class="log-header">
      <span><i class="ti ti-terminal-2" style="font-size:13px;margin-right:4px;vertical-align:-1px"></i>Log del ESP32</span>
      <span class="log-session" id="sessionLabel"></span>
    </div>
    <div class="log" id="log"></div>
  </div>

</div>

<script>
  let sessionId = null, intervaloTimer = null, cuentaTimer = null;
  let enviadas = 0, okCount = 0, errCount = 0, fueraRango = 0;
  let sesionActiva = false, inicioSesion = 0, duracionMs = 0;

  const $ = id => document.getElementById(id);

  $('tempSlider').addEventListener('input', actualizarTemp);
  $('intervalo').addEventListener('input', () => {
    $('intervaloVal').textContent = $('intervalo').value;
  });

  function actualizarTemp() {
    const v = parseFloat($('tempSlider').value);
    $('tempBig').textContent = v.toFixed(1);
    const enRango = v >= parseFloat($('tmin').value) && v <= parseFloat($('tmax').value);
    const b = $('rangeBadge');
    b.className = 'range-badge ' + (enRango ? 'range-ok' : 'range-warn');
    b.textContent = enRango ? 'En rango' : 'Fuera de rango';
  }

  function setStatus(s) {
    const b = $('statusBadge');
    const map = {
      idle:   ['badge-idle',   'Inactivo'],
      active: ['badge-active', 'Sesión activa'],
      error:  ['badge-error',  'Error'],
    };
    const [cls, txt] = map[s] || map.idle;
    b.className = 'status-badge ' + cls;
    b.textContent = txt;
  }

  function log(msg, tipo = 'inf') {
    const el = $('log');
    const t = new Date().toLocaleTimeString('es-AR', { hour12: false });
    const div = document.createElement('div');
    div.className = 'log-line';
    div.innerHTML = `<span class="lt">${t}</span><span class="l${tipo}">${msg}</span>`;
    el.appendChild(div);
    el.scrollTop = el.scrollHeight;
  }

  function limpiarLog() { $('log').innerHTML = ''; }

  function ahora() {
    const d = new Date(), p = n => String(n).padStart(2, '0');
    return {
      fecha: `${p(d.getDate())}/${p(d.getMonth() + 1)}/${d.getFullYear()}`,
      hora:  `${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`
    };
  }

  async function enviar(payload) {
    try {
      const r = await fetch($('url').value.trim(), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await r.json();
      return { ok: r.ok, status: r.status, data };
    } catch (e) {
      return { ok: false, status: 0, error: e.message };
    }
  }

  function actualizarStats() {
    $('sEnviadas').textContent = enviadas;
    $('sOk').textContent       = okCount;
    $('sErr').textContent      = errCount;
    $('sRango').textContent    = fueraRango;
  }

  function actualizarProgreso() {
    if (!sesionActiva) return;
    const transcurrido = Date.now() - inicioSesion;
    const pct = Math.min(100, Math.round(transcurrido / duracionMs * 100));
    $('progressFill').style.width = pct + '%';
    $('progressLabel').textContent = pct + '%';
    const restMs  = Math.max(0, duracionMs - transcurrido);
    const restMin = Math.floor(restMs / 60000);
    const restSec = Math.floor((restMs % 60000) / 1000);
    $('tiempoRestante').textContent = `${restMin}:${String(restSec).padStart(2, '0')} restantes`;
  }

  function iniciarCuenta(segs) {
    let c = segs;
    $('cuentaRegresiva').textContent = c;
    if (cuentaTimer) clearInterval(cuentaTimer);
    cuentaTimer = setInterval(() => {
      c--;
      if (c <= 0) { clearInterval(cuentaTimer); $('cuentaRegresiva').textContent = '…'; }
      else $('cuentaRegresiva').textContent = c;
    }, 1000);
  }

  async function iniciarSesion() {
    if (sesionActiva) return;
    sessionId    = String(Date.now()).slice(-8);
    enviadas = okCount = errCount = fueraRango = 0;
    actualizarStats();
    sesionActiva  = true;
    duracionMs    = parseInt($('duracion').value) * 60 * 1000;
    inicioSesion  = Date.now();
    const segs    = parseInt($('intervalo').value);

    $('btnIniciar').disabled = true;
    $('btnFin').disabled     = false;
    $('progressWrap').style.display = 'block';
    $('sessionLabel').textContent   = 'id: ' + sessionId;
    setStatus('active');

    log(`Sesión iniciada — id: ${sessionId}`, 'ok');
    log(`Individuo: ${$('individuo').value} | ${$('especie').value}`, 'inf');
    log(`Duración: ${$('duracion').value} min | Intervalo: ${segs}s | Rango: ${$('tmin').value}–${$('tmax').value}°C`, 'inf');

    await enviarMedicion();
    actualizarProgreso();
    iniciarCuenta(segs);

    intervaloTimer = setInterval(async () => {
      actualizarProgreso();
      if (Date.now() - inicioSesion >= duracionMs) { finalizarSesion(); return; }
      await enviarMedicion();
      iniciarCuenta(segs);
    }, segs * 1000);

    setInterval(actualizarProgreso, 1000);
  }

  async function enviarMedicion() {
    const temp   = parseFloat($('tempSlider').value);
    const tmin   = parseFloat($('tmin').value);
    const tmax   = parseFloat($('tmax').value);
    const alerta = (temp < tmin || temp > tmax) ? 'FUERA DE RANGO' : 'OK';
    const { fecha, hora } = ahora();

    if (alerta === 'FUERA DE RANGO') fueraRango++;
    enviadas++;
    actualizarStats();

    log(`[${hora}]  ${temp.toFixed(1)}°C — ${alerta}`, alerta === 'OK' ? 'ok' : 'warn');

    const res = await enviar({
      session_id:  sessionId,
      tipo:        'medicion',
      fecha, hora,
      individuo:   $('individuo').value,
      especie:     $('especie').value,
      temperatura: temp,
      temp_min:    tmin,
      temp_max:    tmax,
      alerta
    });

    if (res.ok) {
      okCount++;
      log(`   → HTTP 200 | id_sesion: ${res.data?.id_sesion ?? '?'}`, 'ok');
    } else {
      errCount++;
      log(`   → HTTP ${res.status} | ${res.error || JSON.stringify(res.data)}`, 'err');
      setStatus('error');
    }
    actualizarStats();
  }

  async function finalizarSesion() {
    if (!sesionActiva) return;
    clearInterval(intervaloTimer);
    clearInterval(cuentaTimer);
    sesionActiva = false;
    $('btnIniciar').disabled = false;
    $('btnFin').disabled     = true;
    $('progressFill').style.width   = '100%';
    $('progressLabel').textContent  = '100%';
    $('cuentaRegresiva').textContent = '—';

    const { fecha, hora } = ahora();
    log('Enviando fin de sesión…', 'inf');

    const res = await enviar({
      session_id: sessionId,
      tipo:       'fin_sesion',
      fecha, hora,
      individuo:  $('individuo').value,
      especie:    $('especie').value
    });

    if (res.ok) {
      log(`Sesión cerrada — ${enviadas} mediciones | ${okCount} ok | ${errCount} errores`, 'ok');
      setStatus('idle');
    } else {
      log(`Error al cerrar sesión: ${res.error || res.status}`, 'err');
      setStatus('error');
    }

    $('progressWrap').style.display = 'none';
    $('sessionLabel').textContent   = '';
    sessionId = null;
  }

  actualizarTemp();
</script>
</body>
</html>
@endverbatim
