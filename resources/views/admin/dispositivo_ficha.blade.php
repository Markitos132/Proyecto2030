@extends('layouts.dashboard')
@section('title', 'Ficha de Dispositivo')
    @section('content')

      {{-- ==============================================
           MODAL DE EDICIÓN — mismo formulario que el alta,
           pero pre-cargado con los datos de este dispositivo.
           ============================================== --}}
      <!-- BOTON BORRAR INDIVIDUO -->
      <div class="content-overlay" id="modalBorrarDisp"></div>
      <div class="modal-wrapper" id="confirmarBorrarDisp">
        <div class="pop-up">
          <div class="pop-up-card">
            <div><h2>¿Seguro de eliminar el dispositivo {{ $dispositivo->codigo_disp }}?</h2></div>
            <div class="modal-form">
              <p id="BorrarmensajeDisp">Esta acción no se podrá deshacer</p>
            </div>
            <!-- Botones de Acción -->
            <div class="modal-footer">
              <button type="button" class="btn-secondary" id="cancelarBorrarDisp">Cancelar</button>
              <form action="{{ route('dispositivos.destroy', $dispositivo->id_dispositivo) }}" method="POST" style="display: inline;">
                @csrf
                @method('DELETE')
                <button type="button" class="btn-primary" id="BorrarDisp">Si, eliminar</button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="content-overlay" id="modalOverlayFicha"></div>
      <div class="modal-wrapper" id="nuevaUnidadModalFicha">
        <div class="pop-up">
          <img src="{{ asset('/imagenes/DispositivosFORM.png') }}" alt="Patita">
          <div class="pop-up-card">
            <div><h2>Editar Unidad</h2></div>
            <!--FORMULARIO REGISTRAR INDIVIDUO-->
            <form action="{{ route('dispositivos.update', $dispositivo->id_dispositivo) }}" method="POST" class="modal-form">
              @csrf
              @method('PUT')
              <!--Código de la unidad en letras y números-->
              <div class="form-group">
                <label for="codigo_disp">Código de la unidad</label>
                <input type="text" id="codigo_disp" name="codigo_disp" value="{{ old('codigo_disp', $dispositivo->codigo_disp)}}" pattern="^[a-zA-Z0-9\-_]+$" title="Solo se permiten letras, números, guiones y guiones bajos (sin espacios)." required>
              </div>
              
              <!--Dirección MAC-->
              <div class="form-group">
                <label for="MAC">Dirección MAC</label>
                <input type="text" id="MAC" name="MAC" value="{{ old('MAC', $dispositivo->MAC) }}" pattern="^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$" title="Debe ingresar una dirección MAC válida (ej: AA:BB:CC:DD:EE:FF o aa:bb:cc:dd:ee:ff)"required>
              </div>

              <!--Fecha de Alta-->
              <div class="form-group">
                <label for="f_alta">Fecha de alta del dispositivo</label>
                <input type="date" id="f_alta" name="f_alta" value="{{ old('f_alta', $dispositivo->f_alta?->format('Y-m-d')) }}" readonly style="background-color: #f4f5f7; cursor: not-allowed; opacity: 0.8;" required>
              </div>

              <div class="form-group">
                <label for="observaciones">Observaciones / Detalles técnicos</label>
                <textarea id="observaciones" name="observaciones" rows="3" class="disp-form-textarea">{{ old('observaciones', $dispositivo->observaciones) }}</textarea>
              </div>

              <!-- Botones de Acción -->
              <div class="modal-footer">
                <button type="button" class="btn-secondary" id="cancelModalDispBtn">Cancelar</button>
                <button type="submit" class="btn-primary">Registrar Unidad</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      {{-- ==============================================
           HEADER DE LA FICHA
           ============================================== --}}
      <a href="/dispositivos" class="ficha-back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Volver a dispositivos
      </a>

      <div>
        <div class="page-header-top">
          <div class="ficha-title-block">
            <h1 class="page-title">{{ $dispositivo->codigo_disp}}</h1>
            <span class="status-pill 
            
            @if($dispositivo->estado_calculado == 'online') status-ind-activo
            @elseif($dispositivo->estado_calculado == 'warning') status-ind-alerta
            @else status-ind-inactivo
            @endif ficha-status-pill">
            @if($dispositivo->estado_calculado == 'online') Online
            @elseif($dispositivo->estado_calculado == 'warning') Sin reportar
            @else Apagado
            @endif

          </span>
          </div>
          <div>
            <button class="btn-add" id="btnEditarDispositivo">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>
              Editar unidad
            </button>
            <button class="btn-add delete" id="btnBorrarDispositivo">
              <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"> <path d="M0 0h24v24H0z" fill="none" /> <path fill="currentColor" d="M7 21q-.825 0-1.412-.587T5 19V6H4V4h5V3h6v1h5v2h-1v13q0 .825-.587 1.413T17 21zm2-4h2V8H9zm4 0h2V8h-2z" /> </svg>
              Eliminar unidad
            </button>
          </div>
        </div>
      </div>

      {{-- ==============================================
           BLOQUE 1 — DATOS FIJOS DEL INDIVIDUO
           ============================================== --}}
      <div class="ficha-card">
        <h2 class="ficha-card-title">Datos del dispositivo</h2>
        <div class="ficha-data-grid ficha-data-grid-d">
          <div class="ficha-data-item">
            <span class="ficha-data-label">Dirección MAC</span>
            <span class="ficha-data-value"><em>{{ $dispositivo->MAC }}</em></span>
          </div>
          <div class="ficha-data-item">
            <span class="ficha-data-label">Fecha de alta del dispositivo</span>
            <span class="ficha-data-value">{{ $dispositivo->f_alta?->format('d/m/Y') }}</span>
          </div>
          @if($dispositivo->observaciones)
            <div class="ficha-data-item" style="grid-column: 1 / -1;">
              <span class="ficha-data-label">Observaciones técnicas</span>
              <span class="ficha-data-value">{{ $dispositivo->observaciones }}</span>
            </div>
          @endif
        </div>
      </div>

      {{-- ==============================================
           BLOQUE 2 — DISPOSITIVO ACTUAL
           ============================================== --}}
      <div class="ficha-card">
        <h2 class="ficha-card-title">Trabajando con:</h2>
        @if($dispositivo->sesionActiva)
        {{-- si tiene una sesion activa --}}
          <div class="ficha-device-current">
            <div class="ficha-device-current-info">
              <span class="status-dot online"></span>
              <div>
                <div class="ficha-device-current-id">{{ $dispositivo->sesionActiva->individuo->codigo_individuo }}</div>
                <div class="ficha-device-current-detail">Sesión iniciada hace {{ $dispositivo->sesionActiva->created_at->diffForHumans() }}</div>
              </div>
            </div>
            <a href="{{ route('sesiones.show', $dispositivo->sesionActiva->id_sesion) }}" class="link-graph">Ver sesión en curso →</a>
          </div>
        @elseif($dispositivo->estado_calculado === 'offline' || $dispositivo->estado_calculado === 'warning')
          {{-- No tiene sesión y además está desconectado o sin señal --}}
          <p class="ficha-empty-text">No se pudo establecer conexión con el dispositivo hace {{ $dispositivo->ultima_conexion_human ?? 'un tiempo' }}. No podrá medir hasta que vuelva a conectarse.</p>        
        @else
          {{-- Si no tiene señal hace tiempo, mostrar lo sgte: --}}
          <p class="ficha-empty-text">¡Disponible para asignar! <a href="/sesionesactivas?dispositivo={{ $dispositivo->id_dispositivo }}">Comenzar a medir →</a></p>
        @endif
        </div>

      {{-- ==============================================
           BLOQUE 3 — NOTAS DE CAMPO (bitácora)
           ============================================== --}}
      <div class="ficha-card">
        <h2 class="ficha-card-title">Notas de campo</h2>

        <form action="{{ route('notasdisp.store', $dispositivo->id_dispositivo) }}" method="POST" class="ficha-nota-form">
          @csrf
          <textarea name="nota" rows="2" class="disp-form-textarea" placeholder="Agregar una observación de campo..."></textarea>
          <button type="submit" class="btn-add ficha-nota-btn">Agregar nota</button>
        </form>

        <ul class="ficha-notas-list">
          @forelse($dispositivo->notasDisp as $nota)
            <li class="ficha-nota-item">
              <div class="ficha-nota-meta">
                <strong>{{ $nota->usuario?->nombre }}</strong>
                <span>{{ $nota->fecha_alta?->format('d/m/Y H:i') }}</span>
              </div>
              <p>{{ $nota->contenido }}</p>
            </li>
          @empty
            <li class="ficha-nota-item" style="text-align: center; color: #888; list-style: none;">
              <p>No hay notas de campo registradas para esta unidad.</p>
            </li>
          @endforelse
        </ul>
      </div>
    @endsection

@push('scripts')
<script>
  const btnEditarDispositivo = document.getElementById('btnEditarDispositivo');
  const modalOverlayFicha = document.getElementById('modalOverlayFicha');
  const nuevaUnidadModalFicha = document.getElementById('nuevaUnidadModalFicha');
  const cancelModalDispBtn = document.getElementById('cancelModalDispBtn');

  function abrirModal() {
    modalOverlayFicha.classList.add('open');
    nuevaUnidadModalFicha.classList.add('open');
    document.body.classList.add('modal-open');
  }

  function cerrarModal() {
    modalOverlayFicha.classList.remove('open');
    nuevaUnidadModalFicha.classList.remove('open');
    document.body.classList.remove('modal-open');
  }

  if (btnEditarDispositivo) btnEditarDispositivo.addEventListener('click', abrirModal);
  if (cancelModalDispBtn) cancelModalDispBtn.addEventListener('click', cerrarModal);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && nuevaUnidadModalFicha.classList.contains('open')) {
      cerrarModal();
    }
  });

  const btnBorrarDispositivo = document.getElementById('btnBorrarDispositivo');
  const modalBorrarDisp = document.getElementById('modalBorrarDisp');
  const confirmarBorrarDisp = document.getElementById('confirmarBorrarDisp');
  const cancelarBorrarDisp = document.getElementById('cancelarBorrarDisp');

  function abrirModalDispBorrar() {
    modalBorrarDisp.classList.add('open');
    confirmarBorrarDisp.classList.add('open');
    document.body.classList.add('modal-open');
  }

  function cerrarModalDispBorrar() {
    modalBorrarDisp.classList.remove('open');
    confirmarBorrarDisp.classList.remove('open');
    document.body.classList.remove('modal-open');
  }

  if (btnBorrarDispositivo) btnBorrarDispositivo.addEventListener('click', abrirModalDispBorrar);
  if (cancelarBorrarDisp) cancelarBorrarDisp.addEventListener('click', cerrarModalDispBorrar);

  document.getElementById('BorrarDisp').addEventListener('click', () => {
    console.log(`Borrando este dispositivo...`);
    cerrarModalDispBorrar();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && confirmarBorrarDisp.classList.contains('open')) {
      cerrarModalDispBorrar();
    }
  });
</script>
@endpush
