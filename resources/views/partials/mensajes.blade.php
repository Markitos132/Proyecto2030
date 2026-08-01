{{-- Avisos de éxito y de error del servidor.

     Sin esto, una validación fallida devolvía al usuario a la misma
     página sin ninguna señal: parecía que el botón no había hecho nada.
     Se incluye una sola vez en el layout, así vale para todo el panel. --}}

@if (session('exito'))
  <div class="aviso aviso-exito" role="status">
    {{ session('exito') }}
  </div>
@endif

@if ($errors->any())
  <div class="aviso aviso-error" role="alert">
    @if ($errors->count() === 1)
      {{ $errors->first() }}
    @else
      <strong>Revisá estos puntos:</strong>
      <ul>
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    @endif
  </div>
@endif
