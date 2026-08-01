<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  
  <title>@yield('title', 'BIONEA ORGANIKS')</title>
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('css/style_admin.css') }}">
  <link rel="stylesheet" href="{{ asset('css/individuos.css') }}">
  <link rel="stylesheet" href="{{ asset('css/dispositivos.css') }}">
  <link rel="stylesheet" href="{{ asset('css/sesiones.css') }}">
  <link rel="stylesheet" href="{{ asset('css/historial.css') }}">
  <link rel="stylesheet" href="{{ asset('css/configuracion.css') }}">
  <link rel="stylesheet" href="{{ asset('css/modals.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ficha.css') }}">
  @stack('styles')
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="layout">
  <!-- SIDEBAR -->
  @include('partials.sidebar')
  <!-- MAIN -->
  <div class="main">
    @include('partials.topbar')
    <main class="content">
      @include('partials.mensajes')
      @yield('content')
    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>

  // =====
  //  Menú del usuario :)
  // =====

  const userBtn = document.getElementById("userMenuBtn");
  const userMenu = document.getElementById("userMenu");

  if (userBtn && userMenu) {
    
    userBtn.addEventListener("click", (e)=>{
      e.stopPropagation();
      userMenu.classList.toggle("open");
    });

    document.addEventListener("click", ()=>{
      userMenu.classList.remove("open");
    });

  }
  // =====
  //  SIDEBAR
  // =====
    const sidebar = document.getElementById('sidebar');
  //esto es para oscurecer cuando se abre el sidebar
    const overlay = document.getElementById('sidebarOverlay');
  //boton hamburguesa
    const menuToggle = document.getElementById('menuToggle');

  function openMenu() {
    sidebar.classList.add('open');
    overlay.classList.add('open');
  }
  function closeMenu() {
    sidebar.classList.remove('open');
    overlay.classList.remove('open');
  }

  if (sidebar && overlay && menuToggle)  {
    //si hacemos click en la hamburguesa, se abre
    menuToggle.addEventListener('click', openMenu);
    //si hacemos click en el fondo oscuro, se cierra
    overlay.addEventListener('click', closeMenu);
    //si hacemos click en alguna opcion del sidebar, busca en los enlaces, y ademas se cierra.
    sidebar.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMenu));
  }

  // =====
  //  Medir el layout para el modal (form)
  // =====  

  function medirLayout() {
    const topbar = document.querySelector('.topbar');
    const sidebar = document.querySelector('.sidebar');

    if (topbar) {
        document.documentElement.style.setProperty(
            '--topbar-height',
            topbar.offsetHeight + 'px'
        );
    }

    if (sidebar) {
        const anchoSidebar =
            window.innerWidth <= 1090 ? 0 : sidebar.offsetWidth;

        document.documentElement.style.setProperty(
            '--sidebar-width',
            anchoSidebar + 'px'
        );
    }
  }

  medirLayout();
  window.addEventListener('resize', medirLayout);

</script>
{{-- Refresco automático. Solo actúa en las vistas que declaran
     elementos con data-vivo; en el resto no hace nada. --}}
<script src="{{ asset('js/panel-vivo.js') }}" defer></script>

{{-- Scripts específicos de cada vista --}}
@stack('scripts')

</body>
</html>