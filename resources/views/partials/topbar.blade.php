@php
    // Nombre e iniciales salen del modelo: así el avatar de la topbar y el
    // de Configuración no pueden decir cosas distintas.
    $usuario   = auth()->user();
    $nombre    = $usuario?->nombre_completo ?: 'Invitado';
    $iniciales = $usuario?->iniciales ?? '?';
@endphp

<header class="topbar">
    <button class="menu-toggle" id="menuToggle" aria-label="Abrir menú">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
    </button>
    <div class="topbar-logo">
        <img class="topbar-logo-img" src="{{ asset('imagenes/TOPlogo.png') }}" alt="bionealogo">
    </div>
    <div class="topbar-user" id="userMenuBtn">
        <div class="topbar-avatar">
            {{ $iniciales }}
        </div>
        <div class="user-info user-name-desktop">
            <span>{{ $nombre }}</span>
        </div>
        <svg class="chevron-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>

        <div class="user-menu" id="userMenu">

            <div class="user-info-mobile">
                <span class="mobile-username">{{ $nombre }}</span>
                <hr>
            </div>

            <a href="{{ route('configuracion') }}">Mi perfil</a>
            <a href="{{ route('configuracion') }}">
                Configuración
            </a>
            {{-- El ancla apuntaba a #cambiar-contrasena, pero la sección se
                 llamaba "Cambiar Contraseña" (con espacio y tilde): el
                 enlace no saltaba a ningún lado. --}}
            <a href="{{ route('configuracion') }}#seguridad">
                Cambiar contraseña
            </a>
            <hr>
            {{-- Logout por POST: con un GET, cualquier enlace o imagen externa
                 podria desloguear al usuario sin que lo pida (CSRF). --}}
            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="logout">Cerrar sesión</button>
            </form>
        </div>
    </div>
</header>
