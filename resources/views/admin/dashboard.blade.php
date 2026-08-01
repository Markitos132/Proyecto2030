@extends('layouts.dashboard')
@section('title', 'Dashboard')
    @section('content')
      
      <h1 class="page-title">
        Dashboard - <b>Monitoreo en Curso</b>
        <span class="sello-actualizado" data-vivo="actualizado"></span>
      </h1>
      <div class="metrics-grid">
        <div class="metric-card">
          <div class="metric-header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="13" rx="2"/><path d="M8 21h8M12 18v3"/></svg>
            Dispositivos Online
          </div>
          <div class="metric-value" data-vivo="dispositivos">{{ $dispositivosOnline ?? 0}}/{{ $totalDispositivos ?? 0}}</div>
        </div>
        <div class="metric-card">
          <div class="metric-header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.6-6.4"/><path d="M21 4v5h-5"/></svg>
            Sesiones Activas
          </div>
          <div class="metric-value" data-vivo="sesiones">{{ $sesionesActivasCount ?? 0}}</div>
        </div>
        <div class="metric-card">
          <div class="metric-header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            Temperatura Promedio
          </div>
          <div class="metric-value" data-vivo="temperatura">
            {{ isset($tempPromedio) ? number_format($tempPromedio, 1) . '°C' : '--°C' }}
          </div>
        </div>
      </div>

      <div class="table-panel">
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th>Dispositivo</th>
                <th>Individuo</th>
                <th>Especie</th>
                <th>Temp Actual</th>
                <th>Estado</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
            <!-- Ejemplo para una fila activa (MIDIENDO) -->
              @forelse($sesionesDelDia as $sesion)
                <tr class="{{ $sesion->estaActiva() ? 'row-active' : '' }}" data-sesion-fila="{{ $sesion->id_sesion }}">
                  <td data-label="Dispositivo">{{ $sesion->dispositivo?->nombre ?? $sesion->id_dispositivo }}</td>
                  <td data-label="Individuo">{{ $sesion->individuo?->codigo_individuo ?? 'S/A' }}</td>
                  <td data-label="Especie"><em>{{ $sesion->individuo?->especie ?? 'Sin especificar' }}</em></td>
                  <td data-label="Temp Actual" data-vivo-celda="temperatura">{{ $sesion->ultimaMedicion?->temperatura ?? '--' }} °C</td>
                  <td data-label="Estado">
                    @if($sesion->estaActiva())
                      <span class="status-pill status-measuring">MIDIENDO</span>
                    @else
                      <span class="status-pill status-idle">FINALIZADO</span>
                    @endif
                  </td>
                  <td data-label="">
                    <a href="{{ route('sesiones.show', $sesion->id_sesion) }}" class="link-graph">Ver Gráfico</a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" style="text-align: center; color: #888; padding: 25px;">
                    No hay registros o sesiones iniciadas en el día de hoy.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    @endsection
