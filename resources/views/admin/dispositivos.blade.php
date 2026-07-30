@extends('layouts.dashboard')
@section('title', 'Dashboard')
    @section('content')
      <div class="content-overlay" id="modalOverlay"></div>
      <div class="modal-wrapper" id="nuevaUnidadModal">
        <div class="pop-up">
          <img src="{{ asset('/imagenes/DispositivosFORM.png') }}" alt="Patita">
          <div class="pop-up-card">
            <div><h2>Registre nueva unidad</h2></div>
            <!--FORMULARIO REGISTRAR INDIVIDUO-->
            <form action="{{ route('dispositivos.store') }}" method="POST" class="modal-form">
              @csrf

              <!--Código de la unidad en letras y números-->
              <div class="form-group">
                <label for="codigo_disp">Código de la unidad</label>
                <input type="text" id="codigo_disp" name="codigo_disp" placeholder="Ej: ESP-001" pattern="^[a-zA-Z0-9\-_]+$" title="Solo se permiten letras, números, guiones y guiones bajos (sin espacios)." required>
              </div>
              
              <!--Dirección MAC-->
              <div class="form-group">
                <label for="MAC">Dirección MAC</label>
                <input type="text" id="MAC" name="MAC" placeholder="AA:BB:CC:DD:EE:FF" pattern="^([0-9A-Fa-f]{2}[:\-]){5}([0-9A-Fa-f]{2})$" title="Debe ingresar una dirección MAC válida (ej: AA:BB:CC:DD:EE:FF o aa:bb:cc:dd:ee:ff)"required>
              </div>

              <!--Fecha de Alta-->
              <div class="form-group">
                <label for="f_alta">Fecha de alta del dispositivo</label>
                <input type="date" id="f_alta" name="f_alta" required>
              </div>

              <!-- Observaciones / Notas de Hardware -->
              <div class="form-group">
                <label for="observaciones">Observaciones / Detalles técnicos</label>
                <textarea id="observaciones" name="observaciones" rows="3" class="disp-form-textarea"></textarea>
              </div>

              <!-- Botones de Acción -->
              <div class="modal-footer">
                <button type="button" class="btn-cancel" id="cancelModalBtn">Cancelar</button>
                <button type="submit" class="btn-primary">Registrar Unidad</button>
              </div>
            </form>
          
          
          </div>
        </div>
      </div>
      <div>
        <div class="page-header-top">
          <h1 class="page-title"><b>Dispositivos</b></h1>
          <button class="btn-add" id="btnNuevaUnidad">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            Registrar unidad
          </button>
        </div>
        <p class="page-sub">Inventario completo de unidades ESP32 registradas, con su estado de conexión actual y la asignación vigente de cada una.</p>        
      </div>

      <div class="summary-grid">
        <div class="summary-card">
          <div class="summary-label">Total registradas</div>
          <div class="summary-value">{{ $totalDispositivos ?? 0 }}</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Online ahora</div>
          <div class="summary-value dot-online">{{ $onlineCount ?? 0 }}</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Sin reportar</div>
          <div class="summary-value dot-warning">{{ $sinReportarCount ?? 0 }}</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Apagadas</div>
          <div class="summary-value dot-offline">{{ $offlineCount ?? 0 }}</div>
        </div>
      </div>

      <div class="filter-bar">
        <button class="filter-chip active" data-filter="all">Todas</button>
        <button class="filter-chip" data-filter="online">Online</button>
        <button class="filter-chip" data-filter="warning">Sin reportar</button>
        <button class="filter-chip" data-filter="offline">Apagadas</button>
      </div>

      <div class="device-list" id="deviceList">
        @forelse($dispositivos as $dispositivo)
          <div class="device-card" data-status="{{ $dispositivo->estado_calculado ?? 'offline' }}">
            <div class="device-left">
              <span class="status-dot {{ $dispositivo->estado_calculado ?? 'offline' }}"></span>
              <div class="device-info">
                <div class="device-id">
                  {{ $dispositivo->codigo_disp }}
                  <span class="device-mac">- MAC {{ $dispositivo->MAC }}</span>
                </div>
                <div class="device-detail">
                  @if($dispositivo->sesionActiva)
                    Asignado a {{ $dispositivo->sesionActiva->individuo->codigo_individuo }}
                    · <em>{{ $dispositivo->sesionActiva->individuo->especie }}</em>
                    @if($dispositivo->sesionActiva->ultimaMedicion)
                      última lectura hace {{ $dispositivo->sesionActiva->ultimaMedicion->fecha_hora->diffForHumans(null, true) }}
                    @else 
                      esperando primera lectura
                    @endif
                  @else
                    Disponible · sin asignar
                    -
                    @if($dispositivo->ultima_conexion)
                      última vez visto hace {{ $dispositivo->ultima_conexion->diffForHumans(null, true) }}
                    @else
                      nunca conectado
                    @endif
                  @endif
                </div>
              </div>
            </div>
            <div class="device-right">
              <span class="status-badge {{ $dispositivo->estado_calculado }}">
                {{ ucfirst($dispositivo->estado_calculado) }}
              </span>
              
              <a href="/dispositivos/{{ $dispositivo->id_dispositivo }}" class="btn-action">Ver Ficha</a>
            
              @if($dispositivo->sesionActiva)
                <a href="{{ route('sesiones.show', $dispositivo->sesionActiva->id_sesion) }}" class="btn-action primary">Ver Sesión Activa</a>
              @elseif($dispositivo->estado_calculado === 'online')
                <a href="/sesionesactivas?dispositivo={{ $dispositivo->codigo_disp }}" class="btn-action primary">Comenzar a medir</a>
              @else
                <button class="btn-action" disabled>Comenzar a medir</button>
              @endif
            </div>
          </div>
        @empty
          <p style="text-align:center; color:#888; padding:20px;">No hay dispositivos registrados todavía.</p>
        @endforelse
      </div>
      <div class="info-note">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        <span>El estado se calcula automáticamente según la última vez que cada unidad envió datos. Una unidad pasa a <strong>"Sin reportar"</strong> si no envía una nueva lectura dentro del doble de su intervalo configurado, y a <strong>"Apagado"</strong> cuando lleva más de 10 minutos sin conexión y no tiene sesión activa.</span>
      </div>
    @endsection

@push('scripts')
<script>

//*Guardamos en variables a los overlays, modales, botones para usarlos:
const btnNuevaUnidad = document.getElementById('btnNuevaUnidad');
const modalOverlay = document.getElementById('modalOverlay');
const nuevaUnidadModal = document.getElementById('nuevaUnidadModal');
const cancelModalBtn = document.getElementById('cancelModalBtn');
const formNuevaUnidad = document.querySelector('.modal-form');

//*campos del formulario:
const codigodispInput = document.getElementById('codigo_disp');
const MACInput = document.getElementById('MAC');
const fAltaInput = document.getElementById('f_alta');

function abrirModal() {
  if (modalOverlay && nuevaUnidadModal) {
    modalOverlay.classList.add('open');
    nuevaUnidadModal.classList.add('open');
    document.body.classList.add('modal-open');
    nuevaUnidadModal.scrollTop = 0; 

    if (codigodispInput) codigodispInput.focus();
  }
}

function cerrarModal() {
  if (modalOverlay && nuevaUnidadModal) {
    modalOverlay.classList.remove('open');
    nuevaUnidadModal.classList.remove('open');
    document.body.classList.remove('modal-open');

    if (formNuevaUnidad) formNuevaUnidad.reset();
    limpiarEstilosValidacion();

    if (btnNuevaUnidad) btnNuevaUnidad.focus();
  }
  
}
//* quitamos las marcas rojas de los campos:
function limpiarEstilosValidacion() {
  [codigodispInput, MACInput, fAltaInput].forEach(input => {
    if (input) input.style.borderColor = '';
  });
}

//* Validamos los datos mientras se ingresan:
function validarDispositivo() {
  if (!codigodispInput || !MACInput || !fAltaInput) return true;

  const codigoValido = codigodispInput.value.trim() != '' && !/\s/.test(codigodispInput.value);

  const regexMAC = /^([0-9A-Fa-f]{2}[:\-]){5}([0-9A-Fa-f]{2})$/;
  const macValida = regexMAC.test(MACInput.value.trim());

  const fechaIngresada = new Date(fAltaInput.value);
  const hoy = new Date();
  const fechaValida = fAltaInput.value != '' && fechaIngresada <= hoy;

  codigodispInput.style.borderColor = (codigodispInput.value === '' || codigoValido) ? '' : '#e53e3e';
  MACInput.style.borderColor = (MACInput.value === '' || macValida) ? '' : '#e53e3e';
  fAltaInput.style.borderColor = (fAltaInput.value === '' || fechaValida) ? '' : '#e53e3e';

  return codigoValido && macValida && fechaValida;
}

const tarjetas = document.querySelectorAll('.device-card');
const filtros = document.querySelectorAll('.filter-chip');

filtros.forEach(boton => {
  boton.addEventListener('click', () => {

    filtros.forEach(Botones => {
      Botones.classList.remove('active');
    });

    boton.classList.add('active')

    const filtroseleccionado = boton.dataset.filter;

    tarjetas.forEach(tarjeta => {
      if(filtroseleccionado === 'all' || tarjeta.dataset.status === filtroseleccionado) {
        tarjeta.style.display = 'flex';
      }
      else {
        tarjeta.style.display = 'none';
      }
    });
  });
});

//* SUBMIT para el formulario:
if (formNuevaUnidad) {
  formNuevaUnidad.addEventListener('submit', (e) => {
    
    if (!validarDispositivo()) {
      e.preventDefault();
      alert('Por favor verifica los datos ingresados antes de guardar.');
      return;
    }
  });
}

[codigodispInput, MACInput, fAltaInput].forEach(input => {
  if (input) input.addEventListener('input', validarDispositivo);
})



if (btnNuevaUnidad && modalOverlay && nuevaUnidadModal) {
  btnNuevaUnidad.addEventListener('click', abrirModal);
}

if (cancelModalBtn) {
  cancelModalBtn.addEventListener('click', cerrarModal);
}

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && nuevaUnidadModal.classList.contains('open')) {
    cerrarModal();
  }
});
</script>
@endpush