/* ═══════════════════════════════════════════════════════════
   panel-vivo.js — refresco automático del panel

   Reemplaza el stream SSE de la versión Node. Diferencias:

   1. Un solo temporizador por pestaña, no uno por vista.
   2. Se detiene cuando la pestaña no está visible. El SSE anterior
      seguía consultando la base cada 2 s aunque nadie mirara.
   3. Intervalo de 15 s en lugar de 2. Las mediciones llegan cada
      varios minutos; consultar cada 2 s era pedirle a la base
      cientos de veces lo mismo. Además, con la latencia hacia
      Supabase (~800 ms) las peticiones de 2 s se solapaban.
   4. Si falla, espacia los reintentos en vez de insistir.

   Los elementos a actualizar se marcan en el HTML con data-vivo.
   ═══════════════════════════════════════════════════════════ */

(function () {
  'use strict';

  const INTERVALO_MS   = 15000;
  const ESPERA_MAXIMA  = 120000;
  const URL            = '/panel/estado';

  // Si la página no declara ningún elemento vivo, no hay nada que hacer.
  if (!document.querySelector('[data-vivo]')) return;

  let temporizador = null;
  let fallosSeguidos = 0;

  // Firma de las sesiones que el servidor ya dibujó en esta página.
  // Arrancar desde el DOM (y no desde null) permite detectar en la primera
  // consulta que la tabla quedó vieja: si una sesión empezó justo después
  // de renderizar, la fila no existe y hay que pedirle el HTML al servidor.
  const firmaDelDom = () => Array.from(
    document.querySelectorAll('[data-sesion-fila]')
  ).map((el) => el.dataset.sesionFila).join(',');

  let firmaSesiones = firmaDelDom();

  const $vivo = (nombre) => document.querySelector(`[data-vivo="${nombre}"]`);

  /* Salvaguarda contra bucles de recarga: si por algún motivo el servidor
     devolviera un conjunto de sesiones distinto en cada consulta, la
     página entraría en un ciclo infinito de recargas. Se permite como
     mucho una recarga automática cada 30 segundos. */
  const CLAVE_RECARGA = 'panel-vivo:ultima-recarga';

  function recargadoReciente() {
    const sello = Number(sessionStorage.getItem(CLAVE_RECARGA) || 0);
    return Date.now() - sello < 30000;
  }

  function marcarRecarga() {
    sessionStorage.setItem(CLAVE_RECARGA, String(Date.now()));
  }

  function pintarMetricas(m) {
    const online = $vivo('dispositivos');
    if (online) online.textContent = `${m.dispositivos_online}/${m.dispositivos_total}`;

    const sesiones = $vivo('sesiones');
    if (sesiones) sesiones.textContent = m.sesiones_activas;

    const temp = $vivo('temperatura');
    if (temp) {
      temp.textContent = m.temp_promedio !== null
        ? `${m.temp_promedio.toFixed(1)}°C`
        : '--°C';
    }
  }

  function pintarFilas(sesiones) {
    sesiones.forEach((s) => {
      const fila = document.querySelector(`[data-sesion-fila="${s.id_sesion}"]`);
      if (!fila) return;

      const celdaTemp = fila.querySelector('[data-vivo-celda="temperatura"]');
      if (celdaTemp) {
        celdaTemp.textContent = s.temperatura !== null
          ? `${s.temperatura.toFixed(1)} °C`
          : '-- °C';
        celdaTemp.classList.toggle('fuera-de-rango', s.alerta === 'FUERA DE RANGO');
      }
    });
  }

  function marcarActualizado(iso) {
    const sello = $vivo('actualizado');
    if (!sello) return;

    const hora = new Date(iso).toLocaleTimeString('es-AR', {
      hour: '2-digit', minute: '2-digit', second: '2-digit'
    });
    sello.textContent = `Actualizado ${hora}`;
    sello.classList.remove('vivo-error');
  }

  function marcarError() {
    const sello = $vivo('actualizado');
    if (!sello) return;
    sello.textContent = 'Sin conexión con el servidor';
    sello.classList.add('vivo-error');
  }

  async function consultar() {
    try {
      const res = await fetch(URL, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
      });

      // La sesión expiró: recargar para que el servidor mande al login.
      if (res.status === 401 || res.status === 419) {
        window.location.reload();
        return;
      }

      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const datos = await res.json();

      pintarMetricas(datos.metricas);
      pintarFilas(datos.sesiones);
      marcarActualizado(datos.servidor);

      // Si aparecen o desaparecen sesiones, la tabla necesita filas nuevas
      // y eso lo resuelve mejor el servidor que este script.
      const firma = datos.sesiones.map((s) => s.id_sesion).join(',');
      if (firma !== firmaSesiones && !recargadoReciente()) {
        marcarRecarga();
        window.location.reload();
        return;
      }
      firmaSesiones = firma;

      fallosSeguidos = 0;
    } catch (e) {
      fallosSeguidos++;
      marcarError();
      console.warn('[panel-vivo]', e.message);
    }

    programar();
  }

  function programar() {
    clearTimeout(temporizador);

    // Retroceso exponencial ante fallos: no tiene sentido martillar
    // un servidor que no responde.
    const espera = fallosSeguidos === 0
      ? INTERVALO_MS
      : Math.min(INTERVALO_MS * Math.pow(2, fallosSeguidos), ESPERA_MAXIMA);

    temporizador = setTimeout(consultar, espera);
  }

  function alCambiarVisibilidad() {
    if (document.hidden) {
      clearTimeout(temporizador);
    } else {
      consultar();
    }
  }

  document.addEventListener('visibilitychange', alCambiarVisibilidad);

  consultar();
})();
