@extends('layouts.dashboard')
@section('title', 'Dashboard')
    @section('content')
      <div class="content-overlay" id="modalOverlay"></div>
      <div class="modal-wrapper" id="nuevoIndividuoModal">
        <div class="pop-up">
          <img src="{{ asset('/imagenes/CirculoPatita.png') }}" alt="Patita">
          <div class="pop-up-card">
            <div><h2>Registre un nuevo ejemplar</h2></div>
            
            <!--FORMULARIO REGISTRAR INDIVIDUO-->
            <form action="{{ route('individuos.store')}}" method="POST" class="modal-form">
              @csrf
              <div class="form-group">
                <label for="codigo">Código del individuo</label>
                <input type="text" id="codigo" name="codigo_individuo" placeholder="Ej: LC-047" required>
              </div>

              <!-- Especie y otra especie -->
              <div class="form-group">
                <label for="especie">Especie</label>
                <select name="especie" id="especie">
                  <option value="" disabled selected> Seleccione la especie</option>
                  <option value="Liolaemus chacoensis">Liolaemus chacoensis</option>
                  <option value="otra">Otra especie...</option>
                </select>
              </div>
              
              <div class="form-group" id="grupo-otra-especie" style="display: none;">
                <label for="otra_especie">Especifique la especie</label>
                <input type="text" id="otra_especie" name="otra_especie" placeholder="Ej: Homonota borellii">
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="sexo">Sexo</label>
                  <select name="sexo" id="sexo" required>
                    <option value="" disabled selected>Seleccione</option>
                    <option value="Macho">Macho</option>
                    <option value="Hembra">Hembra</option>
                    <option value="Indeterminado">Indeterminado</option>
                  </select>
                </div>

                <div class="form-group">
                  <label for="estadio">Estadio</label>
                  <select name="estadio" id="estadio" required>
                    <option value="" disabled selected>Seleccione</option>
                    <option value="Adulto">Adulto</option>
                    <option value="Juvenil">Juvenil</option>
                    <option value="otro">Otro estadio...</option>
                  </select>
                </div>
              </div>

              <div class="form-group" id="grupo-otro-estadio" style="display: none;">
                <label for="otro_estadio">Especifique el estadio</label>
                <input type="text" id="otro_estadio" name="otro_estadio" placeholder="Ej: Subadulto / Neonato">
              </div>

              <!-- estado reproductivo -->
              <div class="form-group" id="grupo-estado-reproductivo" style="display: none;">
                <label for="estado_reproductivo">Estado reproductivo / Gravidez</label>
                <select name="estado_reproductivo" id="estado_reproductivo">
                  <option value="" selected>No presenta / Desconocido</option>
                  <option value="Grávida / Preñada">Grávida / Preñada</option>
                  <option value="Con huevos visibles">Con huevos visibles (Palpables)</option>
                  <option value="No grávida">No grávida</option>
                </select>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="svl">SVL (Longitud en mm)</label>
                  <input type="number" id="svl" name="svl" placeholder="Ej. 68" step="1" required>
                </div>
                
                <div class="form-group">
                  <label for="peso">Peso (Gramos)</label>
                  <input type="number" id="peso" name="peso" placeholder="Ej. 12.4" step="0.1" required>
                </div>
              </div>

              <div class="form-group">
                <label for="observaciones">Observaciones</label>
                <textarea id="observaciones" name="observaciones" rows="2" class="disp-form-textarea" placeholder="Observaciones iniciales del ejemplar (ej: marcas, ciclo de muda...)"></textarea>
              </div>

              <div class="modal-footer">
                <button type="button" class="btn-cancel" id="cancelModalBtn">Cancelar</button>
                <button type="submit" class="btn-primary">Guardar Ejemplar</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="page-header-top individuos-header-top">
        <h1 class="page-title">Individuos - <b>Registro de Ejemplares</b></h1>
        <button class="btn-add" id="btnNuevoIndividuo">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          Nuevo Individuo
        </button>
      </div>

      <div class="metrics-grid">
        <div class="metric-card">
          <div class="metric-header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
            Individuos Registrados
          </div>
          <div class="metric-value">{{ $totalIndividuos ?? 0 }}</div>
        </div>
        <div class="metric-card">
          <div class="metric-header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.6-6.4"/><path d="M21 4v5h-5"/></svg>
            Activos en Estudio
          </div>
          <div class="metric-value">{{ $activosCount ?? 0}}</div>
        </div>
        <div class="metric-card">
          <div class="metric-header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="13" rx="2"/><path d="M8 21h8M12 18v3"/></svg>
            Con Dispositivo Asignado
          </div>
          <div class="metric-value">{{ $conDispositivoCount ?? 0 }}</div>
        </div>
      </div>
      
      <div class="info-note">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        <span>Si desea eliminar o editar algun individuo, porfavor clickee en<strong> ver ficha</strong> y realice las acciones que necesite.</span>
      </div>

      <!-- FILTROS -->
      <form method="GET" action="{{ route('individuos') }}" class="history-filters">
        <div class="filter-field">
          <label for="filtroEspecie">Especie</label>
          <input type="text" id="filtroEspecie" name="especie" value="{{ request('especie') }}" placeholder="Ej. Liolaemus...">
        </div>
        
        <div class="filter-field">
          <label for="filtroEstado">Estado</label>
          <select id="filtroEstado" name="estado">
            <option value="">Todos</option>
            <option value="activo" {{ request('estado') == 'activo' ? 'selected' : '' }}>Activo</option>
            <option value="liberado" {{ request('estado') == 'liberado' ? 'selected' : '' }} >Liberado / Perdido</option>
          </select>
        </div>
        <div class="filter-field">
          <label for="filtroCodigo">Código</label>
          <input type="text" id="filtroCodigo"  name="codigo" value="{{ request('codigo') }}" placeholder="Ej. LC-045">
        </div>
        
        <button class="btn-add filter-apply" type="submit">Filtrar</button>
      </form>

      <!-- TABLA DE INDIVIDUOS -->
      <div class="table-panel">
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th>Código</th>
                <th>Especie</th>
                <th>Sexo / Estadio</th>
                <th>Dispositivo Actual</th>
                <th>Estado</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse($individuos as $individuo)
                <tr>
                  <td data-label="Código"><strong>{{ $individuo->codigo_individuo }}</strong></td>
                  <td data-label="Especie"><em>{{ $individuo->especie }}</em></td>
                  <td data-label="Sexo - Estadio">{{ $individuo->sexo }} - {{ $individuo->estadio }}</td>
                  <td data-label="Dispositivo Actual">
                    @if ($individuo->sesionActiva)
                      <span class="badge-disp">Asignado a {{ $individuo->sesionActiva?->dispositivo?->nombre }}</span>
                    @else
                      <span class="badge-disp">No posee ninguna sesión en curso.</span>  
                    @endif
                  </td>
                  <td data-label="Estado">
                    @if ($individuo->estado === 'Liberado/Perdido')
                      <span class="status-pill status-ind-inactivo">{{ $individuo->estado }}</span>
                    @else 
                      <span class="status-pill status-ind-activo">{{ $individuo->estado }}</span>
                    @endif
                  </td>
                  <td><a href="/individuos/{{ $individuo->id_individuo}}" class="link-graph">Ver Ficha</a></td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" style="text-align: center; color: #888; padding: 20px;">
                    No hay ejemplares registrados todavía.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    @endsection

@push('scripts')
<script>
  const btnNuevoIndividuo = document.getElementById('btnNuevoIndividuo');
  const modalOverlay = document.getElementById('modalOverlay');
  const nuevoIndividuoModal = document.getElementById('nuevoIndividuoModal');
  const cancelModalBtn = document.getElementById('cancelModalBtn');

  const formNuevoIndividuo = document.querySelector('#nuevoIndividuoModal .modal-form')

  //campos del formulario
  const codigoInput = document.getElementById('codigo');
  const especieSelect = document.getElementById('especie');
  const otraEspecieInput = document.getElementById('otra_especie');
  const grupoOtraEspecie = document.getElementById('grupo-otra-especie');
  const sexoSelect = document.getElementById('sexo');
  const estadioSelect = document.getElementById('estadio');
  const grupoEstadoReproductivo = document.getElementById('grupo-estado-reproductivo');
  const estadoReproductivoInput = document.getElementById('estado_reproductivo');
  const svlInput = document.getElementById('svl');
  const pesoInput = document.getElementById('peso');
  const otroEstadioInput = document.getElementById('otro_estadio');
  const grupoOtroEstadio = document.getElementById('grupo-otro-estadio');
  const observacionesInput = document.getElementById('observaciones');

  //*Apertura del modal
  function abrirModal() {
    if (modalOverlay && nuevoIndividuoModal) {
      modalOverlay.classList.add('open');
      nuevoIndividuoModal.classList.add('open');
      document.body.classList.add('modal-open');
      nuevoIndividuoModal.scrollTop = 0; 

      if (codigoInput) codigoInput.focus();
    }
  }

  function cerrarModal() {
    modalOverlay.classList.remove('open');
    nuevoIndividuoModal.classList.remove('open');
    document.body.classList.remove('modal-open');
    
    //*reiniciamos el formulario completo
    if (formNuevoIndividuo) formNuevoIndividuo.reset();

    //*Ocultamos los campos para que funcionen condicionalmente.
    if (grupoOtraEspecie) grupoOtraEspecie.style.display = 'none';
    if (grupoOtroEstadio) grupoOtroEstadio.style.display = 'none';
    if (grupoEstadoReproductivo) grupoEstadoReproductivo.style.display = 'none';

    limpiarEstilosValidacion();

    if (btnNuevoIndividuo) btnNuevoIndividuo.focus();
  }

  if (estadioSelect && grupoOtroEstadio) {
    estadioSelect.addEventListener('change', () => {
      if (estadioSelect.value === 'otro') {
        grupoOtroEstadio.style.display = 'flex';
      }
      else {
        grupoOtroEstadio.style.display = 'none';
        if (otroEstadioInput) otroEstadioInput.value = '';
      }
    });
  }

function limpiarEstilosValidacion() {
  [codigoInput, especieSelect, otraEspecieInput, sexoSelect, estadioSelect, svlInput, pesoInput].forEach(el =>{
    if (el) el.style.borderColor = '';
  });
}

  // si selecciona "otra especie...", muestra el input para escribir
  if (especieSelect && grupoOtraEspecie) {
    especieSelect.addEventListener('change', () => {
      if (especieSelect.value === 'otra') {
        grupoOtraEspecie.style.display = 'flex';
      } else {
        grupoOtraEspecie.style.display = 'none';
        otraEspecieInput.value = '';   // 👈 nuevo: limpia lo que haya escrito
      }
    });
  }

  // Mostrar / Ocultar campo de Estado Reproductivo según Sexo
  if (sexoSelect && grupoEstadoReproductivo) {
    sexoSelect.addEventListener('change', () => {
      if (sexoSelect.value === 'Hembra') {
        grupoEstadoReproductivo.style.display = 'flex';
      } else {
        grupoEstadoReproductivo.style.display = 'none';
        if (estadoReproductivoInput) estadoReproductivoInput.value = ''; // Limpia la selección al cambiar a Macho o Indeterminado
      }
    });
  }

  //Validar datos de peso, largo, codigo
  function validarIndividuo() {
    if (!codigoInput || !svlInput || !pesoInput) return true;
    const codigoValido = codigoInput.value.trim() !== '';
    //*convertimos lo que son el svl y el peso en float:
    const svlVal = parseFloat(svlInput.value);
    const pesoVal = parseFloat(pesoInput.value);
    //*Comprobamos que sean mayores a 0 y a la vez que no sean NaN:
    const svlValido = !isNaN(svlVal) && svlVal > 0;
    const pesoValido = !isNaN(pesoVal) && pesoVal > 0;

    let especieValida = especieSelect.value !== '';
    if (especieSelect.value === 'otra') {
      especieValida = otraEspecieInput && otraEspecieInput.value.trim() !== '';
    }

    let estadioValido = estadioSelect.value !== '';
    if (estadioSelect.value === 'otro') {
      estadioValido = otroEstadioInput && otroEstadioInput.value.trim() !== '';
    }

    //! Marcar errores
    codigoInput.style.borderColor = (codigoInput.value === '' || codigoValido) ? '' : '#e53e3e';
    svlInput.style.borderColor = (svlInput.value === '' || svlValido) ? '' : '#e53e3e';
    pesoInput.style.borderColor = (pesoInput.value === '' || pesoValido) ? '' : '#e53e3e';

    return codigoValido && svlValido && pesoValido && especieValida && estadioValido;
  }

  [codigoInput, svlInput, pesoInput, otraEspecieInput, otroEstadioInput].forEach(input => {
    if (input) input.addEventListener('input', validarIndividuo);
  });


  //* Botones y escape 
  if (btnNuevoIndividuo && modalOverlay && nuevoIndividuoModal) {
    btnNuevoIndividuo.addEventListener('click', abrirModal);
  }

  if (cancelModalBtn) {
    cancelModalBtn.addEventListener('click', cerrarModal);
  }

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && nuevoIndividuoModal.classList.contains('open')) {
      cerrarModal();
    }
  });

  //*Submit del formulario

  if (formNuevoIndividuo) {
    formNuevoIndividuo.addEventListener('submit', (e) => {

      if (!validarIndividuo()) {
        e.preventDefault();
        alert('Por favor, antes de enviar, verifique que los campos estén correctos')
      }

    });
  }
</script>
@endpush