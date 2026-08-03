{{-- Errores de validación mostrados dentro de un modal.

     El aviso de partials/mensajes se dibuja en la página, detrás del
     overlay: al reabrirse el modal quedaba tapado y el usuario no
     entendía por qué no se guardaba. --}}

@if ($errors->any())
  <div class="aviso aviso-error aviso-en-modal" role="alert">
    @if ($errors->count() === 1)
      {{ $errors->first() }}
    @else
      <ul>
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    @endif
  </div>
@endif
