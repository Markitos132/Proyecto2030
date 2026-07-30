<header class="topbar">
    <button class="menu-toggle" id="menuToggle" aria-label="Abrir menú">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
    </button>
    <div class="topbar-logo">
        <img class="topbar-logo-img" src="{{ asset('imagenes/TOPlogo.png') }}" alt="bionealogo">
    </div>
    <div class="topbar-user" id="userMenuBtn">
        <div class="topbar-avatar">
            MO
        </div>
        <div class="user-info user-name-desktop">
            <span>Marcos Ortiz</span>
        </div>
        <svg class="chevron-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
        
        <div class="user-menu" id="userMenu">

            <div class="user-info-mobile">
                <span class="mobile-username">Marcos Ortiz</span>
                <hr>
            </div>

            <a href="/configuracion">Mi perfil</a> 
            <a href="/configuracion">
                Configuración
            </a>
            <a href="/configuracion#Cambiar Contraseña">
                Cambiar contraseña
            </a>
            <hr>
            <a class="logout" href="/">
                Cerrar sesión
            </a>
        </div>
    </div>
</header>