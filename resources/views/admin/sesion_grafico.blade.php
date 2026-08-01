@extends('layouts.dashboard')
@section('title', 'Sesión '.$sesion->id_sesion)

@section('content')

  <h1 class="page-title">
    Sesión {{ $sesion->id_sesion }} —
    <b>{{ $sesion->individuo->codigo_individuo ?? 'S/A' }}</b>
    <em style="font-weight:400;">{{ $sesion->individuo->especie ?? '' }}</em>
  </h1>

  <div class="metrics-grid">
    <div class="metric-card">
      <div class="metric-header">Dispositivo</div>
      <div class="metric-value">{{ $sesion->dispositivo->nombre ?? '—' }}</div>
    </div>
    <div class="metric-card">
      <div class="metric-header">Mediciones</div>
      <div class="metric-value">{{ $mediciones->count() }}</div>
    </div>
    <div class="metric-card">
      <div class="metric-header">Promedio</div>
      <div class="metric-value">
        {{ $mediciones->avg('temperatura') !== null
            ? number_format($mediciones->avg('temperatura'), 1).' °C'
            : '--' }}
      </div>
    </div>
    <div class="metric-card">
      <div class="metric-header">Fuera de rango</div>
      <div class="metric-value">{{ $mediciones->where('alerta', 'FUERA DE RANGO')->count() }}</div>
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
        <button type="submit" class="btn-submit">Finalizar sesión</button>
      </form>
    @endif
  </div>

@endsection

@push('scripts')
@if($mediciones->isNotEmpty())
<script>
  // Los datos se serializan desde el servidor; Blade escapa el JSON.
  const mediciones = @json($mediciones->map(fn ($m) => [
      'x' => $m->fecha_hora?->format('Y-m-d H:i:s'),
      'y' => (float) $m->temperatura,
      'alerta' => $m->alerta,
  ]));

  const tempMin = @json($sesion->temp_min !== null ? (float) $sesion->temp_min : null);
  const tempMax = @json($sesion->temp_max !== null ? (float) $sesion->temp_max : null);

  const etiquetas = mediciones.map(m => m.x.slice(11, 16));

  new Chart(document.getElementById('graficoTemperatura'), {
    type: 'line',
    data: {
      labels: etiquetas,
      datasets: [{
        label: 'Temperatura (°C)',
        data: mediciones.map(m => m.y),
        borderColor: '#378ADD',
        backgroundColor: 'rgba(55,138,221,0.12)',
        fill: true,
        tension: 0.25,
        pointRadius: 3,
        // Las mediciones fuera de rango se marcan en rojo.
        pointBackgroundColor: mediciones.map(
          m => m.alerta === 'FUERA DE RANGO' ? '#d32f2f' : '#378ADD'
        ),
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
            afterLabel: ctx => mediciones[ctx.dataIndex].alerta,
          }
        }
      }
    }
  });
</script>
@endif
@endpush
