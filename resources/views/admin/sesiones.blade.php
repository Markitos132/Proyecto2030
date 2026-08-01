@extends('layouts.dashboard')
@section('title', 'Dashboard')
    @section('content')

      <div class="content-overlay" id="modalNuevaSesionOverlay"></div>

      <div class="modal-wrapper" id="nuevaSesionModal">
        <div class="pop-up pop-up-act">
          <img src="{{ asset('/imagenes/reloj.png') }}" alt="Patita">
          <div class="pop-up-card">
            <div><h2>Crear Nueva Sesión</h2></div>

            <form id="formNuevaSesion" action="{{ route('sesiones.store')}}" method="POST" class="modal-form">
              @csrf

            <!-- Seleccionar Individuo Activo -->
              <div class="form-group">
                <label for="individuo_id">Individuo (Activos)</label>
                <select name="individuo_id" id="individuo_id" required>
                  <option value="" disabled selected>Seleccione un ejemplar</option>
                  @forelse ($indActivos as $individuo)
                    <option value="{{ $individuo->id_individuo }}" data-codigo="{{ $individuo->codigo_individuo }}">{{ $individuo->codigo_individuo}} - {{ $individuo->especie}}</option>
                  @empty
                    <option value="" data-codigo="">No hay ningún individuo para medir.</option>
                  @endforelse
                </select>
              </div>

              <!-- Seleccionar Dispositivo Online y Disponible -->
              <div class="form-group">
                <label for="dispositivo_id">Dispositivo disponible</label>
                <select name="dispositivo_id" id="dispositivo_id" required>
                  <option value="" disabled selected>Seleccione un dispositivo</option>
                  @forelse ($dispositivosDisponibles as $dispositivo)
                    <option value="{{ $dispositivo->id_dispositivo}}">{{ $dispositivo->nombre }} (Online · Libre)</option>
                  @empty
                    <option value="">No hay ningun dispositivo que esté activo para comenzar a medir.</option>
                  @endforelse
                  </select>
              </div>

              <!-- Parámetros de la Sesión en 2 columnas -->
              <div class="form-row">
                <div class="form-group">
                  <label for="duracion">Duración (mín. 5 min)</label>
                  <input type="number" id="duracion" name="duracion" placeholder="Ej: 30" min="5" step="1" value="30" required>
                  <span class="input-help">Tiempo total en minutos</span>
                </div>

                <div class="form-group">
                  <label for="intervalo">Intervalo (mín. 1 min)</label>
                  <input type="number" id="intervalo" name="intervalo" placeholder="Ej: 1" min="1" step="1" value="1" required>
                  <span class="input-help">Lectura cada N minutos</span>
                </div>
              </div>

              <!-- Mensaje de advertencia o ayuda de límites -->
              <div class="info-note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                  <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
                </svg>
                <span>El intervalo mínimo de 1 min protege el consumo de batería y la memoria del dispositivo.</span>
              </div>

              <!-- Botones de Acción -->
              <div class="modal-footer">
                <button type="button" class="btn-cancel" id="btnCancelarNuevaSesion">Cancelar</button>
                <button type="submit" class="btn-primary" id="btnIniciarSesion">Iniciar Sesión</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="content-overlay" id="modalFinalizar"></div>
      <div class="modal-wrapper" id="confirmarFinalizarModal">
        <div class="pop-up">
          <div class="pop-up-card">
            <div><h2>¿Seguro de finalizar la sesión?</h2></div>
            <div class="modal-form">
              <p id="Finalizarmensaje">Esta acción no se podrá deshacer</p>
            </div>
            <!-- Botones de Acción -->
            <div class="modal-footer">
              <button type="button" class="btn-cancel" id="cancelarModalFinalizar">Cancelar</button>
              {{-- method="PUT" no existe en HTML: los formularios solo aceptan
                   GET y POST. Laravel usa el campo oculto @method para simularlo.
                   El @method('DELETE') anterior tampoco correspondía: finalizar
                   una sesión la actualiza, no la borra. --}}
              <form id="formFinalizarSesion" action="#" method="POST" style="display: inline;">
                @csrf
                @method('PUT')
                <button type="button" class="btn-primary" id="confirmarModalFinalizar">Si, finalizar</button>
              </form>
            </div>
          </div>
        </div>
      </div>
      <div class="content-overlay" id="detalleOverlay"></div>
      
      {{-- Comienzo de Ver detalle --}}
      
      <div class="modal-wrapper" id="detalleSesionModal">
        
        <div class="pop-up-s pop-up-lg">
          <div class="pop-up-card">
            {{-- Header: individuo + especie + dispositivo --}}
            <div class="detalle-header">
              <div class="detalle-header-top">
                <h2 id="detalleTitulo"></h2>
                <span class="status-pill-sm measuring" id="detalleEstadoPill">
                  <span class="pulse-dot"></span>MIDIENDO
                </span>
              </div>
              <p id="detalleSubtitulo"><em>Liolaemus chacoensis</em> · ESP-001</p>
              <div class="detalle-body">
                <div class="detalle-bio-card">
                  <h3>Datos biológicos</h3>
                  <div class="detalle-bio-grid">
                    <div class="detalle-bio-item">
                      <span class="ficha-data-label-s">Sexo:</span>
                      <span class="ficha-data-value-s" id="detalleSexo">indeterminado</span>
                    </div>
                    <div class="detalle-bio-item">
                      <span class="ficha-data-label-s">Estadio:</span>
                      <span class="ficha-data-value-s" id="detalleEstadio">juvenil adulto</span>
                    </div>
                    {{-- Este bloque solo se muestra si el individuo es hembra;
                    el JS/Blade lo oculta cuando corresponda --}}
                    <div class="detalle-bio-item" id="detallePreñezBloque">
                      <span class="ficha-data-label-s">Preñez:</span>
                      <span class="ficha-data-value-s" id="detallePreñez"></span>
                    </div>
                  </div>
                </div>
                
                <div class="detalle-chart-card">
                  <div id="detalleChart" class="detalle-chart">
                    <canvas id="canvasGraficoModal"></canvas>
                  </div>
                </div>
                  
                <div class="detalle-metrics-card">
                  <div class="detalle-metrics-grid">
                    <div class="detalle-metric">
                      <span class="session-meta-label">Temp. actual</span>
                      <span class="session-meta-value" id="detalleTempActual">31.8 °C</span>
                    </div>
                    <div class="detalle-metric">
                      <span class="session-meta-label">Duración</span>
                      <span class="session-meta-value" id="detalleDuracion">42 min</span>
                    </div>
                    <div class="detalle-metric">
                      <span class="session-meta-label">Lecturas</span>
                      <span class="session-meta-value" id="detalleLecturas">42</span>
                    </div>
                    <div class="detalle-metric">
                      <span class="session-meta-label">Tiempo restante estimado</span>
                      <span class="session-meta-value" id="detalleTiempoRestante">18 min</span>
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                <button type="button" class="btn-secondary-modal" id="cerrarDetalleBtn">Cerrar</button>
                </div>
              </div>
            </div>
          
          </div>
        </div>
      </div>

      <div>
        <div class="page-header-top">
          <h1 class="page-title"><b>Sesiones Activas</b></h1>
          <button class="btn-add" id="btnNuevaSesion">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            Crear Sesión
          </button>
        </div>
        <p class="page-sub">Mediciones en curso en este momento. Cada tarjeta muestra la tendencia de temperatura corporal de la sesión y se actualiza con cada nueva lectura del dispositivo.</p>        
      </div>

      <div class="summary-grid">
        <div class="summary-card">
          <div class="summary-label">Sesiones en curso</div>
          <div class="summary-value">{{ $sesionesEncurso ?? 0 }}</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Duración promedio</div>
          <div class="summary-value">{{ isset($duracionPromedio) ? number_format($duracionPromedio, 1) . ' min' : '-- min' }}</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Temp. promedio actual</div>
          <div class="summary-value">{{ isset($tempPromedioSesion) ? number_format($tempPromedioSesion, 1) . '°C' : '--°C' }}</div>
        </div>
      </div>

      <div class="session-grid" id="sessionGrid">
        @forelse ($sesionesActivas as $sesion)
          <div class="session-card" 
            data-id-sesion="{{ $sesion->id_sesion }}"
            data-individuo="{{ $sesion->individuo?->codigo_individuo }}" 
            data-especie="{{ $sesion->individuo?->especie }}" 
            data-dispositivo="{{ $sesion->dispositivo?->nombre }}" 
            data-temp-actual="{{ $sesion->ultimaMedicion?->temperatura ?? '--' }} °C"
            data-lecturas="{{ $sesion->mediciones()->count() }}" 
            data-duracion="{{ $sesion->fecha_inicio?->diffInMinutes() }} min" 
            data-estadio="{{ $sesion->individuo?->estadio }}" 
            data-sexo="{{ $sesion->individuo?->sexo }}" 
            data-preñez="{{ $sesion->individuo?->estado_reproductivo }}" 
            data-trend="{{ $sesion->mediciones->pluck('temperatura')->implode(',') }}" 
            data-minutos-restantes="{{ $sesion->duracion_sesion - $sesion->fecha_inicio?->diffInMinutes() }}" >

            <div class="session-top">
              <div>
                <div class="session-id">{{ $sesion->individuo?->codigo_individuo}} <span class="session-device">· {{ $sesion->dispositivo?->nombre }}</span></div>
                <div class="session-species"><em>{{ $sesion->individuo?->especie}}</em></div>
              </div>
              <span class="status-pill-sm measuring"><span class="pulse-dot"></span>MIDIENDO</span>
            </div>
            <div class="session-meta">
              <div class="session-meta-item">
                <span class="session-meta-label">Temp. actual</span>
                <span class="session-meta-value">{{ $sesion->ultimaMedicion?->temperatura ?? '--' }}°C</span>
              </div>
              <div class="session-meta-item">
                <span class="session-meta-label">Duración</span>
                <span class="session-meta-value">{{ $sesion->fecha_inicio?->diffInMinutes() }} min</span>
              </div>
              <div class="session-meta-item">
                <span class="session-meta-label">Lecturas</span>
                <span class="session-meta-value">{{ $sesion->mediciones()->count() }}</span>
              </div>
            </div>
            <div class="session-footer">
              <span style="font-size:0.78rem; color:var(--text-muted);">Última lectura hace {{ $sesion->ultimaMedicion?->fecha_hora?->diffForHumans(null, true) ?? '--' }}</span>
              <div class="session-actions">
                <button class="btn-action verdetalle">Ver detalle</button>
                <button class="btn-action danger">Finalizar</button>
              </div>
            </div>
          </div>
        @empty
          <p style="text-align:center; color:#888; padding:20px;">No hay ninguna sesión activa por el momento.</p>
        @endforelse
      </div>

      <div class="empty-state" id="emptyState" style="display:none;">
        No hay sesiones que coincidan con este filtro.
      </div>
    @endsection

@push('scripts')
<script>

//* ------------------
//* VARIABLES GLOBALES
//* ------------------
  
  let sesionAfinalizar = null; // todavía no sabemos cuál es
  let minutosGuardados = null; // todavía no sabemos cuál es
  let idSesionAfinalizar = null;
  let miGraficoModal = null; // Instancia global de Chart.js

  const urlFinalizarBase = "{{ route('sesiones.finalizar', ':id') }}";

//* =========================
//* MODAL DE FINALIZAR SESIÓN
//* =========================

//!Funcion para cerrar el modal del boton "finalizar"
  function cerrarModalFinalizar() {
      document.getElementById('modalFinalizar').classList.remove('open');
      document.getElementById('confirmarFinalizarModal').classList.remove('open');
      document.body.classList.remove('modal-open');
  }
  //!si se clickea en "cancelar"
  document.getElementById('cancelarModalFinalizar').addEventListener('click', cerrarModalFinalizar);
  //!si se clickea en "si, finalizar"
  document.getElementById('confirmarModalFinalizar').addEventListener('click', () => {
    const urlFinal = urlFinalizarBase.replace(':id', idSesionAfinalizar);

    document.getElementById('formFinalizarSesion').action = urlFinal;
    document.getElementById('formFinalizarSesion').submit();
    
    cerrarModalFinalizar();
  });
  //!cada vez q se toque escape salimos del modal
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && document.getElementById('confirmarFinalizarModal').classList.contains('open')) {
      cerrarModalFinalizar();
    }
  });
  //!cada vez que se clickea fuera del modal, se cierra.
  document.getElementById('confirmarFinalizarModal').addEventListener('click', (e) =>{
    if (e.target === e.currentTarget) {
      cerrarModalFinalizar();
    } 
  });

  //!cada vez que clickeamos en el boton "finalizar" se abre el modal y te avisa.
  document.querySelectorAll('.btn-action.danger').forEach(boton => {
    boton.addEventListener('click', () => {
      sesionAfinalizar = boton.closest('.session-card').dataset.individuo;
      idSesionAfinalizar = boton.closest('.session-card').dataset.idSesion
      minutosGuardados = boton.closest('.session-card').dataset.minutosRestantes

      document.getElementById('Finalizarmensaje').textContent =
      `¿Finalizar la sesión de ${sesionAfinalizar}? Te faltan ${minutosGuardados} minutos para terminar.`;

      document.getElementById('modalFinalizar').classList.add('open');
      document.getElementById('confirmarFinalizarModal').classList.add('open');
      document.body.classList.add('modal-open');
    });
  });

//* =========================
//* MODAL DE CREAR SESIÓN
//* =========================

const modalNuevaSesionOverlay = document.getElementById('modalNuevaSesionOverlay')
const nuevaSesionModal = document.getElementById('nuevaSesionModal')
const btnNuevaSesion = document.getElementById('btnNuevaSesion')
const btnCancelarNuevaSesion = document.getElementById('btnCancelarNuevaSesion')
const btnIniciarSesion = document.getElementById('btnIniciarSesion')
//*Guardamos la const del formulario para que podamos limpiar los campos cada vez q salgamos:
const formNuevaSesion = document.getElementById('formNuevaSesion');

// Campos numéricos
const duracionInput = document.getElementById('duracion');
const intervaloInput = document.getElementById('intervalo');

// Funcion para validar limites numericos
function validarParametros() {
  if (!duracionInput || !intervaloInput || !btnIniciarSesion) return true;
  //convertimos el string de duracion e intervalo con parseInt en número con base 10, y si no hay nada, lo dejamos en 0.
  const duracionVal = parseInt(duracionInput.value, 10) || 0;
  const intervaloVal = parseInt(intervaloInput.value, 10) || 0;
  //guardamos siempre q cumpla la condicion, y devuelve true o false
  const duracionValida = duracionVal >= 5;
  const intervaloValido = intervaloVal >= 1;
  //avisamos si no corresponde lo ingresado:
  duracionInput.style.borderColor = duracionValida ? '' : '#e53e3e';
  intervaloInput.style.borderColor = intervaloValido ? '' : '#e53e3e';

  //habilitamos o no el boton para comenzar a medir
  const esValido = duracionValida && intervaloValido;
  btnIniciarSesion.disabled = !esValido;
  btnIniciarSesion.style.opacity = esValido ? '1' : '0.5';
  btnIniciarSesion.style.cursor = esValido ? 'pointer' : 'not-allowed';

  return esValido
}

if (duracionInput && intervaloInput) {
  duracionInput.addEventListener('input', validarParametros);
  intervaloInput.addEventListener('input', validarParametros);
}

// Función para cerrar el modal
function AbrirModalCrearSesion() {
  modalNuevaSesionOverlay.classList.add('open');
  nuevaSesionModal.classList.add('open');
  document.body.classList.add('modal-open');
  validarParametros();
}

// Función para cerrar el modal
function cerrarModalCrearSesion() {
  if (modalNuevaSesionOverlay && nuevaSesionModal) {
    modalNuevaSesionOverlay.classList.remove('open');
    nuevaSesionModal.classList.remove('open');
    document.body.classList.remove('modal-open');
    //*limpiamos los camposs...
    if (formNuevaSesion) formNuevaSesion.reset();
    if (duracionInput) duracionInput.style.borderColor = '';
    if (intervaloInput) intervaloInput.style.borderColor = '';
    if (btnIniciarSesion) {
      btnIniciarSesion.disabled = false;
      btnIniciarSesion.style.opacity = '1';
      btnIniciarSesion.style.cursor = 'pointer';
    }
  }
}

//Para abrir modal con el botón:
if (btnNuevaSesion && modalNuevaSesionOverlay && nuevaSesionModal) {
    btnNuevaSesion.addEventListener('click', AbrirModalCrearSesion);
}
//Para salir del modal con el botón cancelar:
if (btnCancelarNuevaSesion) {
  btnCancelarNuevaSesion.addEventListener('click', cerrarModalCrearSesion);
}

if (formNuevaSesion) {
  formNuevaSesion.addEventListener('submit', (e)=>{
    
    if (!validarParametros()) {
      e.preventDefault();
      alert('Por favor verifica los parámetros: Mínimo 5 min de duración y 1 min de intervalo.');
      return;
    }
  });
}

//* url selección de dispositivo
const params = new URLSearchParams(window.location.search);
const dispositivoDesdeURL = params.get('dispositivo');
if (dispositivoDesdeURL) {
  document.getElementById('dispositivo_id').value = dispositivoDesdeURL;
  AbrirModalCrearSesion();
}


//!cada vez q se toque escape salimos del modal
document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && document.getElementById('nuevaSesionModal').classList.contains('open')) {
    cerrarModalCrearSesion();
  }
});

//!cada vez que se clickea fuera del modal, se cierra!
document.getElementById('nuevaSesionModal').addEventListener('click', (e) =>{
  if (e.target === e.currentTarget) {
    cerrarModalCrearSesion();
  } 
});

//* =========================
//* MODAL VER DETALLE
//* =========================

//!Abrir cerra modal de "Ver detalle"

const detalleOverlay = document.getElementById('detalleOverlay');
const detalleSesionModal = document.getElementById('detalleSesionModal');

  function AbrirModalDetalle() {
    detalleOverlay.classList.add('open');
    detalleSesionModal.classList.add('open');
    document.body.classList.add('modal-open');
    detalleSesionModal.scrollTop = 0; 
  }

  function CerrarModalDetalle() {
    detalleOverlay.classList.remove('open');
    detalleSesionModal.classList.remove('open');
    document.body.classList.remove('modal-open');

  }

  document.querySelectorAll('.btn-action.verdetalle').forEach(boton => {
    boton.addEventListener('click', () => {
      const card = boton.closest('.session-card');
      const data = card.dataset;

      //!Datos técnicos del animal
      document.getElementById('detalleTitulo').textContent = data.individuo || 'S/N';
      document.getElementById('detalleSubtitulo').innerHTML = `<em>${data.especie || 'Especie no especificada'}</em> · ${data.dispositivo || 'S/D'}`;
      //!datos biológicos
      document.getElementById('detalleSexo').textContent = data.sexo || 'Indeterminado';
      document.getElementById('detalleEstadio').textContent = data.estadio || 'No especificado';

      //!preñez
      const bloquePreñez = document.getElementById('detallePreñezBloque');
      const valPreñez = document.getElementById('detallePreñez');

      if (data.sexo && data.sexo.toLowerCase() === 'hembra' && data.preñez) {
        if (valPreñez) valPreñez.textContent = data.preñez;
        if (bloquePreñez) bloquePreñez.style.display = 'flex';
      }
      else {
        if (bloquePreñez) bloquePreñez.style.display = 'none';
      }

      //!datos del modal interno, los más generales
      document.getElementById('detalleTempActual').textContent = data.tempActual || '-- °C';
      document.getElementById('detalleDuracion').textContent = data.duracion || '-- min';
      document.getElementById('detalleLecturas').textContent = data.lecturas || '--';
      document.getElementById('detalleTiempoRestante').textContent = data.minutosRestantes ? `${data.minutosRestantes} min` : `-- min`;

      //!renderizar gráfico del modal con chart.js
      actualizarGraficoModal(data.trend);
      AbrirModalDetalle();
    });
  });

document.getElementById('cerrarDetalleBtn').addEventListener('click', CerrarModalDetalle);

//!cada vez q se toque escape salimos del modal
document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && document.getElementById('detalleSesionModal').classList.contains('open')) {
    CerrarModalDetalle();
  }
});

//!cada vez que se clickea fuera del modal, se cierra!
document.getElementById('detalleSesionModal').addEventListener('click', (e) =>{
  if (e.target === e.currentTarget) {
    CerrarModalDetalle();
  } 
});

//*==================
//* GRÁFICO DEL MODAL
//*==================

function actualizarGraficoModal(tendenciaString) {
  const canvas = document.getElementById('canvasGraficoModal');
  if (!canvas) return;

  const valores = tendenciaString ? tendenciaString.split(',').map(Number) : [];
  const etiquetas = valores.map((_, index) => `L${index + 1}`);

  //*Si ya existe una gráfica previa, la borramos para dibujar una nueva
  if (miGraficoModal) {
    miGraficoModal.destroy();
  }

  const ctx = canvas.getContext('2d');
  miGraficoModal = new Chart(ctx, {
    type: 'line',
    data: {
      labels: etiquetas,
      datasets: [{
        label: 'Temperatura (°C)',
        data: valores,
        borderColor: '#3a8a4a',
        backgroundColor: 'rgba(58, 138, 74, 0.15)',
        borderWidth: 2,
        fill: true,
        tension: 0.3,
        pointRadius: 3,
        pointHoverRadius: 5
      }]
    },
    options: {
      responsive: true, 
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false}
      },
      scales: {
        x: { grid: { display: false} },
        y: {
          suggestedMin: 20,
          suggestedMax: 40,
          ticks: { callback: value => value + ' °C' }
        }
      }
    }
  });
}

function agregarLecturaEnTiempoReal(nuevaTemperatura) {
  if (miGraficoModal) {
    //*agregamos un nuevo dato
    miGraficoModal.data.datasets[0].data.push(nuevaTemperatura);

    //*agregamos una nueva etiqueta de lectura
    const nuevaLecturaNum = miGraficoModal.data.datasets[0].data.length;
    miGraficoModal.data.labels.push(`L${nuevaLecturaNum}`);

    //*actualizamos la curva
    miGraficoModal.update();
    //*actualicemos el texto de temp actual también, pq si no desp me voy a olvidar
    document.getElementById('detalleTempActual').textContent = `${nuevaTemperatura} °C`
  }
}

</script>
@endpush