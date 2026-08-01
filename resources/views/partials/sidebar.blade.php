<aside class="sidebar" id="sidebar">
    <div class="sidebar-top">
      <div class="sidebar-header">
        <img src="{{ asset('imagenes/sidebarlogo.png') }}" alt="Bionea Logo" class="sidebar-top-img">
      </div>
      <ul class="nav-list">
        <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
          <a href="{{ route('dashboard') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/></svg>
            Dashboard
          </a>
        </li>
        <li class="nav-item {{ request()->routeIs('individuos') ? 'active' : '' }}">
          <a href="{{ route('individuos') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
            Individuos
          </a>
        </li>
        <li class="nav-item {{ request()->routeIs('dispositivos') ? 'active' : '' }}">
          <a href="{{ route('dispositivos') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="13" rx="2"/><path d="M8 21h8M12 18v3"/></svg>
            Dispositivos
          </a>
        </li>
        <li class="nav-item {{ request()->routeIs('sesiones') ? 'active' : '' }}">
          <a href="{{ route('sesiones') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21c-4-4-7-7.5-7-11a7 7 0 0 1 14 0c0 3.5-3 7-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
            Sesiones Activas
          </a>
        </li>
        <li class="nav-item {{ request()->routeIs('historial') ? 'active' : '' }}">
          <a href="{{ route('historial') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 4v0"/></svg>
            Historial
          </a>
        </li>
        <!-- Separador visual -->
        <li class="sidebar-separator"></li>
        <li class="sidebar-category-title">Otros</li>
        
        <li class="nav-item {{ request()->routeIs('configuracion') ? 'active' : '' }}">
          <a href="{{ route('configuracion') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
            Configuración
          </a>
        </li>
      </ul>
    </div>
</aside>