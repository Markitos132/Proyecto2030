@extends('layouts.dashboard')
@section('title', 'Ficha de Individuo')
    @section('content')

      {{-- ==============================================
           MODAL DE EDICIÓN — mismo formulario que el alta,
           pero pre-cargado con los datos de este individuo.
           ============================================== --}}
      <!-- BOTON BORRAR INDIVIDUO -->
      <div class="content-overlay" id="modalBorrar"></div>
      <div class="modal-wrapper" id="confirmarBorrarInv">
        <div class="pop-up">
          <div class="pop-up-card">
            <div><h2>¿Seguro de eliminar el ejemplar {{ $individuo->codigo_individuo }}?</h2></div>
            <div class="modal-form">
              <p id="Borrarmensaje">Esta acción no se podrá deshacer</p>
            </div>
            <!-- Botones de Acción -->
            <div class="modal-footer">
              <button type="button" class="btn-secondary" id="cancelarBorrarInd">Cancelar</button>
              <form method="POST" action="{{ route('individuos.destroy', $individuo->id_individuo) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-primary" id="confirmarBorrarInd">Si, eliminar</button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="content-overlay" id="modalOverlay"></div>
      <div class="modal-wrapper" id="editarIndividuoModal">
        <div class="pop-up">
          <img src="{{ asset('/imagenes/CirculoPatita.png') }}" alt="Patita">
          <div class="pop-up-card">
            <div><h2>Editar ejemplar</h2></div>
            <form action="{{ route('individuos.update', $individuo->id_individuo) }}" method="POST" class="modal-form">
              @csrf
              @method('PUT')
              @include('partials.errores-form')
              <div class="form-group">
                <label for="codigo">Código del individuo</label>
                <input type="text" id="codigo" name="codigo" value="{{ old('codigo_individuo', $individuo->codigo_individuo) }}" required>
              </div>

              <div class="form-group">
                <label for="especie">Especie</label>
                @php
                  // La lista tiene que coincidir con las opciones de abajo:
                  // antes incluía Tropidurus etheridgei, que no figura como
                  // opción, así que un ejemplar de esa especie no marcaba
                  // "otra" ni tenía dónde mostrarse, y al guardar se
                  // convertía silenciosamente en Liolaemus chacoensis.
                  $especiesConocidas = ['Liolaemus chacoensis'];
                  $especieVal    = old('especie_select') === 'otra'
                                     ? old('especie_otra')
                                     : old('especie_select', $individuo->especie);
                  $esOtraEspecie = filled($especieVal) && ! in_array($especieVal, $especiesConocidas);
                @endphp
                <select name="especie_select" id="especie_select" required>
                  <option value="Liolaemus chacoensis" {{ $especieVal == 'Liolaemus chacoensis' ? 'selected' : '' }}>Liolaemus chacoensis</option>
                  <option value="otra" {{ $esOtraEspecie ? 'selected' : '' }}>Otra especie...</option>
                </select>
                <input type="text" id="especie_otra" name="especie_otra" value="{{ $esOtraEspecie ? $especieVal : '' }}" placeholder="Nombre científico de la especie" style="margin-top: 8px; display: {{ $esOtraEspecie ? 'block' : 'none' }};">
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="sexo">Sexo</label>
                  <select name="sexo" id="sexo" required>
                    <option value="Macho" {{ old('sexo', $individuo->sexo) === 'Macho' ? 'selected' : ''}}>Macho</option>
                    <option value="Hembra" {{ old('sexo', $individuo->sexo) === 'Hembra' ? 'selected' : ''}}>Hembra</option>
                    <option value="Indeterminado" {{ old('sexo', $individuo->sexo) === 'Indeterminado' ? 'selected' : ''}}>Indeterminado</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="estadio">Estadio</label>
                  @php
                    $estadioVal = old('estadio_select') === 'otro'
                                    ? old('estadio_otro')
                                    : old('estadio_select', $individuo->estadio);
                    $esOtroEstadio = filled($estadioVal) && ! in_array($estadioVal, ['Adulto', 'Juvenil']);
                  @endphp
                  <select name="estadio_select" id="estadio_select" required>
                    <option value="Adulto" {{ $estadioVal == 'Adulto' ? 'selected' : '' }}>Adulto</option>
                    <option value="Juvenil" {{ $estadioVal == 'Juvenil' ? 'selected' : '' }}>Juvenil</option>
                    <option value="otro" {{ $esOtroEstadio ? 'selected' : '' }}>Otro...</option>
                  </select>
                  <input type="text" id="estadio_otro" name="estadio_otro" value="{{ $esOtroEstadio ? $estadioVal : '' }}" placeholder="Especificar estadio (ej: Subadulto)" style="margin-top: 8px; display: {{ $esOtroEstadio ? 'block' : 'none' }};">
                </div>
              </div>

              <div class="form-group" id="grupo-estado-reproductivo" style="display: {{ old('sexo', $individuo->sexo) == 'Hembra' ? 'block' : 'none' }};">
                <label for="estado_reproductivo">Estado reproductivo / Gravidez</label>
                <select name="estado_reproductivo" id="estado_reproductivo">
                  <option value="" {{ old('estado_reproductivo', $individuo->estado_reproductivo) == '' ? 'selected' : '' }}>No presenta / Desconocido</option>
                  <option value="Grávida / Preñada" {{ old('estado_reproductivo', $individuo->estado_reproductivo) == 'Grávida / Preñada' ? 'selected' : '' }}>Grávida / Preñada</option>
                  <option value="Con huevos visibles" {{ old('estado_reproductivo', $individuo->estado_reproductivo) == 'Con huevos visibles' ? 'selected' : '' }}>Con huevos visibles (Palpables)</option>
                  <option value="No grávida" {{ old('estado_reproductivo', $individuo->estado_reproductivo) == 'No grávida' ? 'selected' : '' }}>No grávida</option>
                </select>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="svl">SVL (Longitud en mm)</label>
                  <input type="number" id="svl" name="svl" value="{{ old('svl', $individuo->svl)}}" step="1" required>
                </div>
                <div class="form-group">
                  <label for="peso">Peso (Gramos)</label>
                  <input type="number" id="peso" name="peso" value="{{ old('peso', $individuo->peso) }}" step="0.1" required>
                </div>
              </div>

              <div class="form-group">
                <label for="estado">Estado</label>
                {{-- El "selected" estaba fijo en activo, así que abrir el
                     formulario y guardar sin tocar nada devolvía a activo a
                     un ejemplar liberado. --}}
                @php $estadoVal = old('estado', $individuo->estado ?: 'activo'); @endphp
                <select name="estado" id="estado" required>
                  <option value="activo"      {{ $estadoVal === 'activo' ? 'selected' : '' }}>Activo</option>
                  <option value="recapturado" {{ $estadoVal === 'recapturado' ? 'selected' : '' }}>Recapturado</option>
                  <option value="liberado"    {{ $estadoVal === 'liberado' ? 'selected' : '' }}>Liberado / Perdido</option>
                </select>
              </div>

              <div class="modal-footer">
                <button type="button" class="btn-secondary" id="cancelModalBtn">Cancelar</button>
                <button type="submit" class="btn-primary">Guardar Cambios</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      {{-- ==============================================
           HEADER DE LA FICHA
           ============================================== --}}
      <a href="{{ route('individuos.index') }}" class="ficha-back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Volver a Individuos
      </a>

      <div>
        <div class="page-header-top">
          <div class="ficha-title-block">
            <h1 class="page-title">{{ $individuo->codigo_individuo }}</h1>
            @if($individuo->estado === 'liberado')
              <span class="status-pill status-ind-inactivo ficha-status-pill">{{ $individuo->estado }}</span>
            @else
              <span class="status-pill status-ind-activo ficha-status-pill">{{ $individuo->estado }}</span>
            @endif
          </div>
          <div>
            <button class="btn-add" id="btnEditarIndividuo">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>
              Editar Datos
            </button>
            <button class="btn-add delete" id="btnBorrarIndividuo">
              <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"> <path d="M0 0h24v24H0z" fill="none" /> <path fill="currentColor" d="M7 21q-.825 0-1.412-.587T5 19V6H4V4h5V3h6v1h5v2h-1v13q0 .825-.587 1.413T17 21zm2-4h2V8H9zm4 0h2V8h-2z" /> </svg>
              Eliminar individuo
            </button>
          </div>
        </div>
      </div>

      {{-- ==============================================
           BLOQUE 1 — DATOS FIJOS DEL INDIVIDUO
           ============================================== --}}
      <div class="ficha-card">
        <h2 class="ficha-card-title">Datos del individuo</h2>
        <div class="ficha-data-grid">
          <div class="ficha-data-item">
            <span class="ficha-data-label">Especie</span>
            <span class="ficha-data-value"><em>{{ $individuo->especie }}</em></span>
          </div>
          <div class="ficha-data-item">
            <span class="ficha-data-label">Sexo</span>
            <span class="ficha-data-value">{{ $individuo->sexo }}</span>
          </div>
          <div class="ficha-data-item">
            <span class="ficha-data-label">Estadio</span>
            <span class="ficha-data-value">{{ $individuo->estadio }}</span>
          </div>
          <div class="ficha-data-item">
            <span class="ficha-data-label">SVL</span>
            <span class="ficha-data-value">{{ $individuo->svl }}</span>
          </div>
          <div class="ficha-data-item">
            <span class="ficha-data-label">Peso</span>
            <span class="ficha-data-value">{{ $individuo->peso }}</span>
          </div>
          @if($individuo->observaciones)
            <div class="ficha-data-item" style="grid-column: 1 / -1;">
              <span class="ficha-data-label">Observaciones</span>
              <span class="ficha-data-value">{{ $individuo->observaciones }}</span>
            </div>
          @endif
        </div>
      </div>

      {{-- ==============================================
           BLOQUE 2 — DISPOSITIVO ACTUAL
           ============================================== --}}
      <div class="ficha-card">
        <h2 class="ficha-card-title">Dispositivo actual</h2>
        @if($individuo->sesionActiva)
          <div class="ficha-device-current">
            <div class="ficha-device-current-info">
              <span class="status-dot online"></span>
              <div>
                <div class="ficha-device-current-id">{{ $individuo->sesionActiva?->dispositivo?->nombre }}</div>
                <div class="ficha-device-current-detail">Asignado desde {{ $individuo->sesionActiva?->fecha_inicio?->diffForHumans() }} · midiendo actualmente</div>
              </div>
            </div>
            <a href="{{ route('sesiones.show', $individuo->sesionActiva?->id_sesion) }}" class="link-graph">Ver sesión en curso →</a>
          </div>
        @else
          {{-- Si no tiene dispositivo asignado, mostrar en su lugar: --}}
          <p class="ficha-empty-text">Sin dispositivo asignado actualmente.</p>
        @endif
      </div>

      {{-- ==============================================
           BLOQUE 3 — NOTAS DE CAMPO (bitácora)
           ============================================== --}}
      <div class="ficha-card">
        <h2 class="ficha-card-title">Notas de campo</h2>

        <form action="{{ route('notasindividuo.store', $individuo->id_individuo) }}" method="POST" class="ficha-nota-form">
          @csrf
          <textarea name="nota" rows="2" class="disp-form-textarea" placeholder="Agregar una observación de campo..."></textarea>
          <button type="submit" class="btn-add ficha-nota-btn">Agregar nota</button>
        </form>

        <ul class="ficha-notas-list">
          @forelse($individuo->notasIndividuo as $nota)
          <li class="ficha-nota-item">
            <div class="ficha-nota-meta">
              <strong>{{ $nota->usuario?->nombre ?? 'Investigador' }}</strong>
              <span>{{ $nota->fecha_alta?->format('d/m/Y H:i') }}</span>
            </div>
            <p>{{ $nota->contenido }}</p>
          </li>
          @empty
            <li class="ficha-nota-item" style="text-align: center; color: #888; list-style: none;">
              <p>No hay notas de campo registradas para este ejemplar.</p>
            </li>
          @endforelse
        </ul>
      </div>

      {{-- ==============================================
           BLOQUE 4 — HISTORIAL DE SESIONES DE ESTE INDIVIDUO
      ============================================== --}}
      <div class="ficha-card">
        <h2 class="ficha-card-title">Historial de sesiones</h2>
        <div class="table-panel">
          <div class="table-scroll">
            <table>
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Dispositivo</th>
                  <th>Duración</th>
                  <th>Temp. prom.</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @php
                  $sesionesFinalizadas = $individuo->sesiones->filter(function ($sesion) {
                    return $sesion->estado === 'FINALIZADA';
                  });
                  @endphp
                @forelse($sesiones as $sesion)
                  <tr>
                    <td>{{ $sesion->fecha_inicio?->format('d/m/Y H:i') }}</td>
                    <td>{{ $sesion->dispositivo?->nombre ?? 'N/A' }}</td>
                    <td>{{ $sesion->fecha_inicio?->diff($sesion->fecha_fin) }}</td>
                    <td>{{ $sesion->mediciones->avg('temperatura') }}</td>
                    <td><a href="{{ route('sesiones.show', $sesion->id_sesion) }}" class="link-graph">Ver Gráfico</a></td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" style="text-align: center; color: #888;">No hay sesiones históricas registradas.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

    @endsection

@push('scripts')
<script>
  const btnEditarIndividuo = document.getElementById('btnEditarIndividuo');
  const modalOverlay = document.getElementById('modalOverlay');
  const editarIndividuoModal = document.getElementById('editarIndividuoModal');
  const cancelModalBtn = document.getElementById('cancelModalBtn');

  function abrirModal() {
    modalOverlay.classList.add('open');
    editarIndividuoModal.classList.add('open');
    document.body.classList.add('modal-open');
  }

  function cerrarModal() {
    modalOverlay.classList.remove('open');
    editarIndividuoModal.classList.remove('open');
    document.body.classList.remove('modal-open');
  }

  if (btnEditarIndividuo) btnEditarIndividuo.addEventListener('click', abrirModal);
  if (cancelModalBtn) cancelModalBtn.addEventListener('click', cerrarModal);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && editarIndividuoModal.classList.contains('open')) {
      cerrarModal();
    }
  });

  const btnBorrarIndividuo = document.getElementById('btnBorrarIndividuo');
  const modalBorrar = document.getElementById('modalBorrar');
  const confirmarBorrarInv = document.getElementById('confirmarBorrarInv');
  const cancelarBorrarInd = document.getElementById('cancelarBorrarInd');

  function abrirModalBorrar() {
    modalBorrar.classList.add('open');
    confirmarBorrarInv.classList.add('open');
    document.body.classList.add('modal-open');
  }

  function cerrarModalBorrar() {
    modalBorrar.classList.remove('open');
    confirmarBorrarInv.classList.remove('open');
    document.body.classList.remove('modal-open');
  }

  if (btnBorrarIndividuo) btnBorrarIndividuo.addEventListener('click', abrirModalBorrar);
  if (cancelarBorrarInd) cancelarBorrarInd.addEventListener('click', cerrarModalBorrar);

  document.getElementById('confirmarBorrarInd').addEventListener('click', () => {
    console.log(`Borrando este individuo...`);
    cerrarModalBorrar();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && confirmarBorrarInv.classList.contains('open')) {
      cerrarModalBorrar();
    }
  });

// Manejo desplegable de "Otro estadio"
const estadioSelect = document.getElementById('estadio_select');
const estadioOtroInput = document.getElementById('estadio_otro');

if (estadioSelect) {
  estadioSelect.addEventListener('change', function() {
    if (this.value === 'otro') {
      estadioOtroInput.style.display = 'block';
      estadioOtroInput.required = true;
    } else {
      estadioOtroInput.style.display = 'none';
      estadioOtroInput.required = false;
    }
  });
}

// Manejo desplegable de "Otra especie"
const especieSelect = document.getElementById('especie_select');
const especieOtraInput = document.getElementById('especie_otra');

if (especieSelect) {
  especieSelect.addEventListener('change', function() {
    if (this.value === 'otra') {
      especieOtraInput.style.display = 'block';
      especieOtraInput.required = true;
    } else {
      especieOtraInput.style.display = 'none';
      especieOtraInput.required = false;
    }
  });
}

{{-- Acá había una segunda copia idéntica del bloque de "Otro estadio".
     Redeclarar un const es un error de sintaxis, y eso impedía que se
     ejecutara TODO el script de la ficha: por eso no respondían ni el
     botón de editar ni el de eliminar. --}}

// Mostrar "Estado reproductivo" SOLO si es Hembra
const sexoSelect = document.getElementById('sexo');
const grupoEstadoRepro = document.getElementById('grupo-estado-reproductivo');

if (sexoSelect) {
  sexoSelect.addEventListener('change', function() {
    if (this.value === 'Hembra') {
      grupoEstadoRepro.style.display = 'block';
    } else {
      grupoEstadoRepro.style.display = 'none';
      document.getElementById('estado_reproductivo').value = ''; // Limpia el valor si cambia de sexo
    }
  });
}

@if ($errors->any() && old('codigo') !== null)
  // El servidor rechazó la edición: se reabre el modal con lo que se había
  // escrito, en lugar de dejar la ficha como si nada hubiera pasado.
  abrirModal();
@endif
</script>
@endpush
