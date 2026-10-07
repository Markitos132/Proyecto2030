@extends('layouts.dashboard')
@section('title', 'Dashboard')
    @section('content')
      <div>
        <div class="page-header-top historial-header-top">
          <div class="historial-title-group">
            <h1 class="page-title"><b>Historial</b></h1>
            <p class="page-sub">Registro completo de sesiones finalizadas, listas para consulta y análisis estadístico.</p>
          </div>
          <div class="historial-export-block">
            <span id="contadorSeleccionadas" class="contador-pill"></span>
            <button class="btn-add" id="exportar-csv">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v13M7 11l5 5 5-5M5 21h14"/></svg>
              <span id="exportar-csv-texto">Exportar CSV</span>
            </button>
            <button class="btn-add delete" id="borrar-sesiones" disabled>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6h16Z"/></svg>
              <span id="borrar-sesiones-texto">Borrar sesión</span>
            </button>
          </div>
        </div>
      </div>

      {{-- MODAL DE CONFIRMACIÓN — mismo patrón que individuo_ficha /
           dispositivo_ficha. El form arranca vacío: los <input hidden>
           con los ids tildados se arman por JS recién al confirmar. --}}
      <div class="content-overlay" id="modalBorrarSesiones"></div>
      <div class="modal-wrapper" id="confirmarBorrarSesiones">
        <div class="pop-up">
          <div class="pop-up-card">
            <div><h2 id="borrarSesionesTitulo">¿Seguro de borrar la sesión seleccionada?</h2></div>
            <div class="modal-form">
              <p>Esta acción no se podrá deshacer. Se borran también sus mediciones.</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" id="cancelarBorrarSesiones">Cancelar</button>
              <form method="POST" action="{{ route('historial.eliminar') }}" id="formBorrarSesiones">
                @csrf
                <button type="submit" class="btn-primary" id="confirmarBorrarSesionesBtn">Sí, borrar</button>
              </form>
            </div>
          </div>
        </div>
      </div>
      
      <form method="GET" action="{{ route('historial') }}" class="history-filters">
        <div class="filter-field">
          <label for="filterIndividuo">Individuo</label>
          <input type="text" id="filterIndividuo" name="individuo" placeholder="Ej: LC-045" value="{{ request('individuo') }}">
        </div>
        <div class="filter-field">
          <label for="filterEspecie">Especie</label>
          <select id="filterEspecie" name="especie">
            <option value="">Todas</option>
            @forelse ($especiesDisponibles as $especie)
              <option value="{{ $especie }}" {{ request('especie') == $especie ? 'selected' : '' }}>{{ $especie }}</option>
            @empty
              <option value="">No hay especies añadidas al momento.</option>
            @endforelse
          </select>
        </div>
        
        <div class="filter-field">
          <label for="filterDesde">Desde</label>
          <input type="date" id="filterDesde" name="desde" value="{{ request('desde') }}">
        </div>
        <div class="filter-field">
          <label for="filterHasta">Hasta</label>
          <input type="date" id="filterHasta" name="hasta" value="{{ request('hasta') }}">
        </div>
        <button type="submit" class="btn-action primary filter-apply">Filtrar</button>
      </form>

      <div class="mobile-select-all-bar">
        <label class="checkbox-container">
          <input type="checkbox" id="selectAllMobile">
          <span class="checkmark"></span>
          <span class="label-text">Seleccionar todas las sesiones</span>
        </label>
      </div>

      <div class="table-panel">
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th><input type="checkbox" id="seleccionarTodas"></th>
                <th>Fecha</th>
                <th>Individuo</th>
                <th>Especie</th>
                <th>Dispositivo</th>
                <th>Duración</th>
                <th>Temp. prom.</th>
                <th>Gráficos</th>
              </tr>
            </thead>
            <tbody>
              @forelse($sesionesFinalizadas as $sesion)
                @php
                  $duracion = $sesion->fecha_inicio?->diff($sesion->fecha_fin);
                  $temperaturaProm = $sesion->mediciones->avg('temperatura');
                @endphp
                <tr>
                  <td><input type="checkbox" class="fila-checkbox" data-sesion-id="{{ $sesion->id_sesion }}"></td>
                  <td data-label="Fecha">{{ $sesion->fecha_inicio?->format('d/m/Y') }}</td>
                  <td data-label="Individuo">{{ $sesion->individuo?->codigo_individuo }}</td>
                  <td data-label="Especie"><em>{{ $sesion->individuo?->especie }}</em></td>
                  <td data-label="Dispositivo">{{ $sesion->dispositivo?->nombre }}</td>
                  <td data-label="Duración">{{ $duracion?->h ?? 0 }} h {{ $duracion?->i ?? 0 }} min</td>
                  <td data-label="Temp. prom.">{{ isset($temperaturaProm) ? number_format($temperaturaProm, 1) . ' °C' : '-- °C' }}</td>
                  {{-- El enlace apuntaba a "#": no llevaba a ningún lado. --}}
                  <td><a href="{{ route('sesiones.show', $sesion->id_sesion) }}" class="link-graph">Ver Gráfico</a></td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" style="text-align: center; color: #888; padding: 25px;">
                    No hay sesiones finalizadas que coincidan con los filtros.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
<div>
  @if($sesionesFinalizadas->hasPages())
    <div class="pagination-bar">
      <a href="{{ $sesionesFinalizadas->previousPageUrl() }}"
        class="btn-action {{ $sesionesFinalizadas->onFirstPage() ? 'disabled' : '' }}"
        @if($sesionesFinalizadas->onFirstPage()) aria-disabled="true" onclick="return false"; @endif>
        <- Anterior
      </a>
      @php
      $actual = $sesionesFinalizadas->currentPage();
      $ultima = $sesionesFinalizadas->lastPage();
      $desde = max($actual - 2, 1);
      $hasta = min($actual +2, $ultima);
      @endphp
      
      @if ($desde > 1)
        <a href="{{ $sesionesFinalizadas->url(1) }}" class="btn-action">1</a>
        @if ($desde > 2)
          <span>...</span>
        @endif
      @endif

      @for ($pagina = $desde; $pagina <= $hasta; $pagina++)
        @if($pagina == $actual)
        <span class ="btn-action active">{{ $pagina }}</span>
        @else
          <a href="{{ $sesionesFinalizadas->url(1) }}" class="btn-action">{{ $pagina }}</a>
        @endif
      @endfor

      @if ($hasta < $ultima)
        @if  ($hasta < $ultima -1)
          <span>...</span>
        @endif
        <a href="{{ $sesionesFinalizadas->url($ultima) }}" class="btn-action">{{ $ultima }}</a>
      @endif

      <a href="{{ $sesionesFinalizadas->nextPageUrl() }}"
       class="btn-action {{ $sesionesFinalizadas->hasMorePages() ? '' : 'disabled' }}"
       @if(! $sesionesFinalizadas->hasMorePages()) aria-disabled="true" onclick="return false;" @endif>
        Siguiente ->
    </a>
</div>
@endif
      <div class="info-note">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        <span>Cada sesión finalizada queda asociada permanentemente a su individuo y dispositivo. El botón <strong>"Exportar CSV"</strong> descarga los datos filtrados, ya estructurados, listos para análisis estadístico sin procesamiento manual.</span>
      </div>
    @endsection
@push('scripts')
<script>
//* Revisemos si todas las filas están tildadas, para despues saber si marca a todas o no
function revisarSiTodasEstanTildadas() {
  const contador = document.getElementById('contadorSeleccionadas');
  const filas = document.querySelectorAll ('.fila-checkbox');

  const todasTildadas = Array.from(filas).every(checkbox => checkbox.checked);
  const tildadas = Array.from(filas).filter(checkbox => checkbox.checked);

  const selectDesktop = document.getElementById('seleccionarTodas');
  const selectMobile = document.getElementById('selectAllMobile');

  if (selectDesktop) selectDesktop.checked = todasTildadas;
  if (selectMobile) selectMobile.checked = todasTildadas;
  
  document.getElementById('seleccionarTodas').checked = todasTildadas;
  
  console.log(tildadas.length);

  if (tildadas.length === 0) {
    contador.style.display = 'none';
  }
  else {
    contador.style.display = 'flex';
    contador.textContent = `Seleccionado: ${tildadas.length}`;
  }

  const botonExportar = document.getElementById('exportar-csv');
  const textoExportar = document.getElementById('exportar-csv-texto');
  const botonBorrar = document.getElementById('borrar-sesiones');
  const textoBorrar = document.getElementById('borrar-sesiones-texto');

  if (tildadas.length === 0) {
    botonExportar.disabled = true;
    textoExportar.textContent = 'Exportar CSV';

    botonBorrar.disabled = true;
    textoBorrar.textContent = 'Borrar sesión';
  }

  else if (tildadas.length === 1) {
    botonExportar.disabled = false;
    textoExportar.textContent = 'Exportar archivo';

    botonBorrar.disabled = false;
    textoBorrar.textContent = 'Borrar sesión';
  }

  else {
    botonExportar.disabled = false;
    textoExportar.textContent = 'Exportar archivos';

    botonBorrar.disabled = false;
    textoBorrar.textContent = `Borrar ${tildadas.length} sesiones`;
  }


}

document.getElementById('exportar-csv').addEventListener('click', () => {
  const filas = document.querySelectorAll('.fila-checkbox');
  const tildadas = Array.from(filas).filter(checkbox => checkbox.checked);
  const idsSeleccionados = tildadas.map(checkbox => checkbox.dataset.sesionId);

  if (idsSeleccionados.length === 0) {
    return; // no hay nada tildado, no hacemos nada
  }

  // Una descarga por sesión: el servidor devuelve un CSV por vez, con el
  // ejemplar y la fecha de medición en el nombre.
  //
  // Van espaciadas 400 ms. Disparadas todas juntas, el navegador descarta
  // las que llegan mientras todavía está resolviendo la anterior y bajan
  // solo dos o tres de las que tildaste.
  //
  // La URL sale del nombre de ruta y no escrita a mano.
  const urlExportar = "{{ route('historial.exportar') }}";

  idsSeleccionados.forEach((id, i) => {
    setTimeout(() => {
      const enlace = document.createElement('a');
      enlace.href = urlExportar + '?sesion=' + encodeURIComponent(id);
      // Sin download: el nombre lo decide el servidor por cabecera.
      document.body.appendChild(enlace);
      enlace.click();
      enlace.remove();
    }, i * 400);
  });
});

// Borrar sesiones: el botón de la barra solo abre el modal de
// confirmación. Los <input hidden> con los ids tildados recién se
// arman al tocar "Sí, borrar" — si se armaran antes, destildar una
// fila después de abrir el modal no se reflejaría en el envío.
const modalBorrarSesiones = document.getElementById('modalBorrarSesiones');
const confirmarBorrarSesiones = document.getElementById('confirmarBorrarSesiones');
const formBorrarSesiones = document.getElementById('formBorrarSesiones');
const borrarSesionesTitulo = document.getElementById('borrarSesionesTitulo');

function abrirModalBorrarSesiones() {
  const tildadas = Array.from(document.querySelectorAll('.fila-checkbox')).filter(c => c.checked);
  if (tildadas.length === 0) return;

  borrarSesionesTitulo.textContent = tildadas.length === 1
    ? '¿Seguro de borrar la sesión seleccionada?'
    : `¿Seguro de borrar las ${tildadas.length} sesiones seleccionadas?`;

  modalBorrarSesiones.classList.add('open');
  confirmarBorrarSesiones.classList.add('open');
}

function cerrarModalBorrarSesiones() {
  modalBorrarSesiones.classList.remove('open');
  confirmarBorrarSesiones.classList.remove('open');
}

document.getElementById('borrar-sesiones').addEventListener('click', abrirModalBorrarSesiones);
document.getElementById('cancelarBorrarSesiones').addEventListener('click', cerrarModalBorrarSesiones);

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && confirmarBorrarSesiones.classList.contains('open')) {
    cerrarModalBorrarSesiones();
  }
});

formBorrarSesiones.addEventListener('submit', () => {
  // Limpiamos por si el usuario abrió y cerró el modal más de una vez.
  formBorrarSesiones.querySelectorAll('input[name="sesiones[]"]').forEach(i => i.remove());

  const tildadas = Array.from(document.querySelectorAll('.fila-checkbox')).filter(c => c.checked);
  tildadas.forEach(checkbox => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'sesiones[]';
    input.value = checkbox.dataset.sesionId;
    formBorrarSesiones.appendChild(input);
  });
});

document.getElementById('seleccionarTodas').addEventListener('change', function() {
  document.querySelectorAll('.fila-checkbox').forEach(checkbox => {
    checkbox.checked = this.checked;
  });
  revisarSiTodasEstanTildadas();
});

document.getElementById('selectAllMobile')?.addEventListener('change', function() {
  document.querySelectorAll('.fila-checkbox').forEach(checkbox => {
    checkbox.checked = this.checked;
  });
  revisarSiTodasEstanTildadas();
});

document.querySelectorAll('.fila-checkbox').forEach(checkbox => {
  checkbox.addEventListener('change', revisarSiTodasEstanTildadas);
});

// Faltaba esta línea. La función que apaga el botón solo corría al tildar
// algo, así que al cargar la página arrancaba habilitado con cero sesiones
// seleccionadas: se podía tocar y el propio script cortaba sin hacer nada.
// De ahí la impresión de que el botón estaba muerto.
revisarSiTodasEstanTildadas();

</script>
@endpush