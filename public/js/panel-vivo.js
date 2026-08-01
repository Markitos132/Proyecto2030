/* ═══════════════════════════════════════════════════════════
   panel-vivo.js — refresco automático del panel

   Reemplaza el stream SSE de la versión Node. Un SSE no es viable acá:
   FrankenPHP en el plan gratuito de Render arranca con dos hilos de PHP
   y cada conexión abierta ocupa uno mientras dura, así que un par de
   pestañas del dashboard dejarían al ESP32 sin dónde entregar datos.

   En su lugar, consultas cortas con ritmo adaptativo:

   1. Un solo temporizador por pestaña, no uno por vista.
   2. El ritmo lo decide el servidor: 3 s mientras hay una sesión
      midiendo, 60 s en reposo. El SSE anterior consultaba cada 2 s
      siempre, midiera o no.
   3. Se detiene cuando la pestaña no está visible.
   4. ETag: si nada cambió, el servidor responde 304 sin cuerpo. Es lo
      que hace barato consultar cada 3 segundos.
   5. Si falla, espacia los reintentos en vez de insistir.

   Los elementos a actualizar se marcan en el HTML con data-vivo.
   ═══════════════════════════════════════════════════════════ */

(function () {
  'use strict';

  const RITMO_POR_DEFECTO = 15000;
  const ESPERA_MAXIMA     = 120000;
  const URL               = '/panel/estado';

  // Si la página no declara ningún elemento vivo, no hay nada que hacer.
  if (!document.querySelector('[data-vivo]')) return;

  let temporizador   = null;
  let fallosSeguidos = 0;
  let enVuelo        = false;
  let ritmo          = RITMO_POR_DEFECTO;
  let etag           = null;

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

      // Una sesión puede terminar sin que cambie el conjunto de filas:
      // se actualiza en el lugar, sin recargar.
      const celdaEstado = fila.querySelector('[data-vivo-celda="estado"]');
      if (celdaEstado) {
        const clase = s.activa ? 'status-measuring' : 'status-idle';
        celdaEstado.innerHTML =
          `<span class="status-pill ${clase}">${s.etiqueta}</span>`;
        fila.classList.toggle('row-active', s.activa);
      }
    });
  }

  /** Sin argumento usa la hora del navegador: es lo que corresponde
      tras un 304, donde el servidor no manda cuerpo. */
  function marcarActualizado(iso) {
    const sello = $vivo('actualizado');
    if (!sello) return;

    const hora = (iso ? new Date(iso) : new Date()).toLocaleTimeString('es-AR', {
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
    // Con una petición en curso no se lanza otra. Sin esto, volver a la
    // pestaña mientras hay una consulta abierta arrancaba una segunda
    // cadena de consultas en paralelo con la primera.
    if (enVuelo) return;
    enVuelo = true;

    try {
      const cabeceras = { 'Accept': 'application/json' };
      if (etag) cabeceras['If-None-Match'] = etag;

      const res = await fetch(URL, {
        headers: cabeceras,
        credentials: 'same-origin',
        // Sin esto el navegador revalida por su cuenta y nos entrega un
        // 200 con el cuerpo cacheado: nunca veríamos el 304.
        cache: 'no-store',
      });

      // La sesión expiró: recargar para que el servidor mande al login.
      if (res.status === 401 || res.status === 419) {
        window.location.reload();
        return;
      }

      // Nada cambió desde la consulta anterior. No hay cuerpo que leer
      // ni DOM que tocar; solo se refresca el sello para mostrar que
      // la conexión sigue viva.
      if (res.status === 304) {
        marcarActualizado();
        fallosSeguidos = 0;
        return;
      }

      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      etag = res.headers.get('ETag');

      const datos = await res.json();

      pintarMetricas(datos.metricas);
      pintarFilas(datos.sesiones);
      marcarActualizado(datos.servidor);

      // El servidor sugiere cada cuánto volver a preguntar: rápido
      // mientras algo mide, espaciado cuando no pasa nada.
      if (datos.proximo_en) {
        ritmo = datos.proximo_en * 1000;
      }

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
    } finally {
      enVuelo = false;

      // Reprogramar acá y no despues del try: varias ramas cortan con
      // return (304, sesion expirada, recarga) y se saltarian la linea,
      // dejando el panel congelado sin volver a consultar nunca.
      // En las ramas que recargan la pagina el temporizador se descarta
      // solo al descargarse el documento.
      programar();
    }
  }

  function programar() {
    clearTimeout(temporizador);

    // Retroceso exponencial ante fallos: no tiene sentido martillar
    // un servidor que no responde.
    const espera = fallosSeguidos === 0
      ? ritmo
      : Math.min(ritmo * Math.pow(2, fallosSeguidos), ESPERA_MAXIMA);

    temporizador = setTimeout(consultar, espera);
  }

  function alCambiarVisibilidad() {
    // Cancelar siempre el temporizador pendiente antes de decidir:
    // al volver a la pestaña, dejarlo vivo y ademas consultar de
    // inmediato dejaba dos ciclos solapados.
    clearTimeout(temporizador);

    if (! document.hidden) {
      consultar();
    }
  }

  document.addEventListener('visibilitychange', alCambiarVisibilidad);

  consultar();
})();
