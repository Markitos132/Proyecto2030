@extends('layouts.dashboard')
@section('title', 'Sesión '.$sesion->id_sesion)

@section('content')

  <h1 class="page-title">
    Sesión {{ $sesion->id_sesion }} —
    <b>{{ $sesion->individuo?->codigo_individuo ?? 'S/A' }}</b>
    <em style="font-weight:400;">{{ $sesion->individuo?->especie ?? '' }}</em>
  </h1>

  <div class="metrics-grid">
    <div class="metric-card">
      <div class="metric-header">Dispositivo</div>
      <div class="metric-value">{{ $sesion->dispositivo?->nombre ?? '—' }}</div>
    </div>
    <div class="metric-card">
      <div class="metric-header">Mediciones</div>
      <div class="metric-value">{{ $mediciones->count() }}</div>
    </div>
    <div class="metric-card">
      <div class="metric-header">Promedio</div>
      <div class="metric-value">{{ $promedio !== null ? $promedio.' °C' : '--' }}</div>
    </div>
    <div class="metric-card">
      <div class="metric-header">Fuera de rango</div>
      <div class="metric-value">{{ $fueraDeRango }}</div>
    </div>
  </div>

  <div class="table-panel" style="padding: 1.5rem;">
    @if($mediciones->isEmpty())
      <p style="text-align:center; color:#888; padding:25px;">
        Esta sesión todavía no tiene mediciones registradas.
      </p>
    @else
      <canvas id="graficoTemperatura" height="110"></canvas>
    @endif
  </div>

  <div style="margin-top:1rem;">
    @if($sesion->estaActiva())
      <form method="POST" action="{{ route('sesiones.finalizar', $sesion->id_sesion) }}">
        @csrf
        @method('PUT')
        <button type="submit" class="btn-primary">Finalizar sesión</button>
      </form>
    @endif
  </div>

@endsection

@push('scripts')
@if($mediciones->isNotEmpty())
<script>
  // Los datos vienen ya armados desde el controlador: acá solo se vuelcan
  // variables simples. Las expresiones complejas dentro de una directiva
  // de serialización rompen la compilación de Blade.
  //
  // Y ojo con los comentarios: Blade no distingue el JavaScript del resto,
  // así que nombrar una directiva acá dentro la expande igual y rompe el
  // archivo. Por eso este comentario no la nombra.
  const serie   = @json($serie);
  const tempMin = @json($tempMin);
  const tempMax = @json($tempMax);

  new Chart(document.getElementById('graficoTemperatura'), {
    type: 'line',
    data: {
      labels: serie.map(m => m.hora),
      datasets: [{
        label: 'Temperatura (°C)',
        data: serie.map(m => m.temperatura),
        borderColor: '#378ADD',
        backgroundColor: 'rgba(55,138,221,0.12)',
        fill: true,
        tension: 0.25,
        pointRadius: 3,
        // Las mediciones fuera de rango se marcan en rojo.
        pointBackgroundColor: serie.map(m => m.alerta === 'FUERA DE RANGO' ? '#d32f2f' : '#378ADD'),
      }]
    },
    options: {
      responsive: true,
      interaction: { intersect: false, mode: 'index' },
      scales: {
        y: {
          title: { display: true, text: '°C' },
          suggestedMin: tempMin !== null ? tempMin - 2 : undefined,
          suggestedMax: tempMax !== null ? tempMax + 2 : undefined,
        }
      },
      plugins: {
        tooltip: {
          callbacks: {
            afterLabel: ctx => serie[ctx.dataIndex].alerta,
          }
        }
      }
    }
  });
</script>
@endif
@endpush
