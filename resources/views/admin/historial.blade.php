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
                  <td data-label="Duración">{{ $duracion->h }} h {{ $duracion->i }} min</td>
                  <td data-label="Temp. prom.">{{ isset($temperaturaProm) ? number_format($temperaturaProm, 1) . ' °C' : '-- °C' }}</td>
                  <td><a href="#" class="link-graph">Ver Gráfico</a></td>
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

  if (tildadas.length === 0) {
    botonExportar.disabled = true;
    textoExportar.textContent = 'Exportar CSV';
  }

  else if (tildadas.length === 1) {
    botonExportar.disabled = false;
    textoExportar.textContent = 'Exportar archivo';
  }

  else {
    botonExportar.disabled = false;
    textoExportar.textContent = 'Exportar archivos';
  }


}

document.getElementById('exportar-csv').addEventListener('click', () => {
  const filas = document.querySelectorAll('.fila-checkbox');
  const tildadas = Array.from(filas).filter(checkbox => checkbox.checked);
  const idsSeleccionados = tildadas.map(checkbox => checkbox.dataset.sesionId);

  if (idsSeleccionados.length === 0) {
    return; // no hay nada tildado, no hacemos nada
  }

  const idsTexto = idsSeleccionados.join(',');
  window.location.href = `/historial/exportar?ids=${idsTexto}`;
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

</script>
@endpush