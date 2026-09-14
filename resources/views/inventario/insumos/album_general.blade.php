{{-- resources/views/inventario/insumos/album_general.blade.php --}}
@extends('layouts.app')

@section('title') Álbum General de Insumos @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-images"></i> Álbum General de Insumos</h1>
      <p>Visualización global de todas las fotografías registradas en el inventario</p>
    </div>
    <div>
      <a href="{{ route('insumos.index') }}" class="btn btn-secondary">
        <i class="fa fa-arrow-left mr-1"></i> Volver a Insumos
      </a>
    </div>
  </div>

  <div class="basic-tb-hd text-center">
    @include('layouts.partials.flash-messages')
  </div>

  {{-- Galería Global de Fotos --}}
  <div class="tile">
    <h3 class="tile-title mb-4"><i class="fa fa-th"></i> Galería General</h3>
    
    @if($fotos->count() > 0)
      <div class="row">
        @foreach($fotos as $foto)
          <div class="col-md-3 col-sm-6 mb-4">
            <div class="card h-100 shadow-sm position-relative">
              
              {{-- Indicador de Producto asociado --}}
              <span class="badge badge-primary position-absolute text-truncate" 
                    data-toggle="tooltip" 
                    data-placement="top" 
                    title="{{ $foto->titulo ?: 'Sin título' }}" 
                    style="top: 10px; left: 10px; z-index: 10; font-size: 0.75rem; max-width: 60%; cursor: pointer;">
                  {{ $foto->titulo ?: 'Sin título' }}
              </span>
              
              {{-- Indicador si es Principal --}}
              @if($foto->es_principal)
                <span class="badge badge-success position-absolute" style="top: 10px; right: 45px; z-index: 10; font-size: 0.75rem;">
                  <i class="fa fa-star"></i>
                </span>
              @endif

              {{-- Menú Desplegable estilo Facebook (Lápiz) idéntico a album.blade.php --}}
              <div class="dropdown position-absolute" style="top: 10px; right: 10px; z-index: 10;">
                <button class="btn btn-light btn-sm rounded-circle shadow-sm" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="width: 32px; height: 32px; background-color: rgba(255, 255, 255, 0.9);">
                  <i class="fa fa-pen text-dark" style="font-size: 0.85rem;"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-right shadow">
                  <a class="dropdown-item" href="#" onclick="abrirModalEditar({{ $foto->id }}, '{{ addslashes($foto->titulo) }}')">
                    <i class="fa fa-edit text-primary mr-2"></i> Editar título
                  </a>
                  
                  <form action="{{ route('insumos.album.set_principal', $foto->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="dropdown-item">
                      <i class="fa fa-star text-warning mr-2"></i> Seleccionar como principal
                    </button>
                  </form>

                  <a class="dropdown-item" href="{{ asset($foto->ruta) }}" download="insumo_foto_{{ $foto->id }}.jpg">
                    <i class="fa fa-download text-success mr-2"></i> Descargar
                  </a>

                  <div class="dropdown-divider"></div>

                  <form id="delete-form-{{ $foto->id }}" action="{{ route('insumos.album.destroy_foto', $foto->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="dropdown-item text-danger" onclick="eliminarFoto({{ $foto->id }})">
                      <i class="fa fa-trash mr-2"></i> Eliminar foto
                    </button>
                  </form>
                </div>
              </div>

              {{-- Imagen con altura fija y diseño adaptable (Clickeable para el visor) --}}
              @php
                  $nombreArchivo = basename($foto->ruta);
                  $rutaThumb = file_exists(public_path('albumes/thumbs/' . $nombreArchivo)) 
                               ? asset('albumes/thumbs/' . $nombreArchivo) 
                               : asset($foto->ruta); // Fallback por si la foto es antigua
              @endphp
              <div style="height: 200px; background-color: #f8f9fa; overflow: hidden; cursor: pointer;" class="d-flex align-items-center justify-content-center" onclick="abrirVisorFacebook({{ $loop->index }})">
                <img src="{{ $rutaThumb }}" class="card-img-top" alt="{{ $foto->titulo }}" style="width: 100%; height: 100%; object-fit: cover;">
              </div>

              <div class="card-body p-2 text-center bg-light">
                <p class="card-text text-muted mb-0" style="font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                  {{ optional($foto->insumo)->producto ?? 'Sin Insumo' }}: {{ optional($foto->insumo)->descripcion ?? 'Sin descripcion' }}
                </p>
                <small class="text-muted" style="font-size: 0.75rem;">Serial: {{ optional($foto->insumo)->serial ?? 'N/A' }}</small>
              </div>

            </div>
          </div>
        @endforeach
      </div>

      {{-- Paginación --}}
      <div class="d-flex justify-content-center mt-4">
        {{ $fotos->links('pagination::bootstrap-4') }}
      </div>
    @else
      <div class="text-center py-5">
        <i class="fa fa-image fa-4x text-muted mb-3"></i>
        <h5 class="text-muted">No hay fotografías registradas en el sistema.</h5>
      </div>
    @endif
  </div>
</main>

{{-- MODAL PARA EDITAR TÍTULO --}}
<div class="modal fade" id="modalEditarTitulo" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fa fa-edit"></i> Editar Título de la Foto</h5>
        <button class="close text-white" type="button" data-dismiss="modal"><span>×</span></button>
      </div>
      <form id="formEditarTitulo">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <input type="hidden" id="edit_foto_id">
          <div class="form-group">
            <label>Título / Descripción corta</label>
            <input type="text" id="edit_titulo" name="titulo" class="form-control" maxlength="150" placeholder="Ej. Vista frontal del repuesto">
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" type="submit">Guardar Cambios</button>
          <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- VISOR TIPO FACEBOOK CON CARRUSEL --}}
<div id="fbLightboxModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.9); z-index: 9999; overflow: hidden;">
  
  {{-- Botón Cerrar (X) Superior Derecho --}}
  <button type="button" onclick="cerrarVisorFacebook()" style="position: absolute; top: 20px; right: 25px; background: none; border: none; color: #fff; font-size: 2.5rem; cursor: pointer; z-index: 10000; outline: none;">
    &times;
  </button>

  <div style="display: flex; width: 100%; height: 100%;">
    
    {{-- Contenedor de la Imagen y Flechas a la Izquierda --}}
    <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px; position: relative;">
      
      {{-- Botón Anterior (<) --}}
      <button type="button" onclick="cambiarFoto(-1)" style="position: absolute; left: 20px; background: rgba(0, 0, 0, 0.6); border: none; color: #fff; font-size: 1.8rem; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; z-index: 10000; outline: none; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">
        <i class="fa fa-chevron-left"></i>
      </button>

      <img id="fbLightboxImg" src="" alt="" style="max-width: 100%; max-height: 100%; object-fit: contain;">

      {{-- Botón Siguiente (>) --}}
      <button type="button" onclick="cambiarFoto(1)" style="position: absolute; right: 20px; background: rgba(0, 0, 0, 0.6); border: none; color: #fff; font-size: 1.8rem; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; z-index: 10000; outline: none; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">
        <i class="fa fa-chevron-right"></i>
      </button>
    </div>

    {{-- Panel de Información a la Derecha --}}
    <div style="width: 380px; background-color: #242526; color: #e4e6eb; display: flex; flex-direction: column; border-left: 1px solid #393a3b; padding: 25px; box-sizing: border-box;">
      
      <h4 style="color: #fff; border-bottom: 1px solid #393a3b; padding-bottom: 15px; margin-bottom: 20px; font-size: 1.2rem;">
        <i class="fa fa-info-circle text-primary mr-2"></i> Detalle del Insumo
      </h4>

      <div style="margin-bottom: 15px;">
        <span style="font-size: 0.85rem; color: #b0b3b8; text-transform: uppercase; display: block; font-weight: bold;">Título de la Foto</span>
        <p id="fbLightboxTitulo" style="font-size: 1.05rem; margin-top: 5px; color: #fff;"></p>
      </div>

      <div style="margin-bottom: 15px;">
        <span style="font-size: 0.85rem; color: #b0b3b8; text-transform: uppercase; display: block; font-weight: bold;">Producto</span>
        <p id="fbLightboxProducto" style="font-size: 1rem; margin-top: 5px;"></p>
      </div>

      <div style="margin-bottom: 15px;">
        <span style="font-size: 0.85rem; color: #b0b3b8; text-transform: uppercase; display: block; font-weight: bold;">Serial</span>
        <p style="margin-top: 5px;"><span id="fbLightboxSerial" class="badge badge-secondary" style="font-size: 0.9rem; padding: 6px 10px;"></span></p>
      </div>

      <div style="margin-bottom: 15px; flex-grow: 1;">
        <span style="font-size: 0.85rem; color: #b0b3b8; text-transform: uppercase; display: block; font-weight: bold;">Descripción</span>
        <p id="fbLightboxDescripcion" style="font-size: 0.95rem; color: #b0b3b8; margin-top: 5px; line-height: 1.4;"></p>
      </div>

    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  // Funciones para el modal de edición de título por AJAX
  function abrirModalEditar(id, titulo) {
    $('#edit_foto_id').val(id);
    $('#edit_titulo').val(titulo === 'null' ? '' : titulo);
    $('#modalEditarTitulo').modal('show');
  }

  $('#formEditarTitulo').on('submit', function(e) {
    e.preventDefault();
    var fotoId = $('#edit_foto_id').val();
    var nuevoTitulo = $('#edit_titulo').val();

    $.ajax({
      url: "{{ url('inventario/insumos/album/foto') }}/" + fotoId,
      type: 'POST',
      data: {
        _token: '{{ csrf_token() }}',
        _method: 'PUT',
        titulo: nuevoTitulo
      },
      success: function(response) {
        if(response.success) {
          $('#modalEditarTitulo').modal('hide');
          location.reload();
        }
      },
      error: function() {
        alert('Ocurrió un error al actualizar el título.');
      }
    });
  });

  // Alerta de confirmación para eliminar foto con SweetAlert2
  function eliminarFoto(fotoId) {
    swal.fire({
      title: "¿Estás seguro?",
      text: "¡No podrás recuperar esta fotografía una vez eliminada!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Sí, ¡eliminar!",
      cancelButtonText: "Cancelar"
    }).then((result) => {
      if (result.isConfirmed) {
        document.getElementById('delete-form-' + fotoId).submit();
      }
    });
  }

  // Mapeo dinámico de las fotos de la página actual hacia JavaScript para el carrusel
  const albumFotos = [
    @foreach($fotos as $foto)
      {
        ruta: "{{ asset($foto->ruta) }}",
        titulo: "{{ addslashes($foto->titulo ?: 'Sin título') }}",
        producto: "{{ addslashes(optional($foto->insumo)->producto ?? 'Sin producto') }}",
        serial: "{{ addslashes(optional($foto->insumo)->serial ?? 'N/A') }}",
        descripcion: "{{ addslashes(optional($foto->insumo)->descripcion ?? 'Sin descripción registrada.') }}"
      },
    @endforeach
  ];

  let currentIndex = 0;

  function abrirVisorFacebook(index) {
    currentIndex = index;
    actualizarContenidoVisor();
    document.getElementById('fbLightboxModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
  }

  function cerrarVisorFacebook() {
    document.getElementById('fbLightboxModal').style.display = 'none';
    document.body.style.overflow = 'auto';
  }

  function cambiarFoto(direccion) {
    currentIndex += direccion;
    
    if (currentIndex >= albumFotos.length) {
      currentIndex = 0;
    } else if (currentIndex < 0) {
      currentIndex = albumFotos.length - 1;
    }
    
    actualizarContenidoVisor();
  }

  function actualizarContenidoVisor() {
    let fotoActual = albumFotos[currentIndex];
    document.getElementById('fbLightboxImg').src = fotoActual.ruta;
    document.getElementById('fbLightboxTitulo').textContent = fotoActual.titulo;
    document.getElementById('fbLightboxProducto').textContent = fotoActual.producto;
    document.getElementById('fbLightboxSerial').textContent = fotoActual.serial;
    document.getElementById('fbLightboxDescripcion').textContent = fotoActual.descripcion;
  }

  // Controles por teclado (Escape para salir, flechas para navegar)
  document.addEventListener('keydown', function(event) {
    if (document.getElementById('fbLightboxModal').style.display === 'block') {
      if (event.key === "Escape") {
        cerrarVisorFacebook();
      } else if (event.key === "ArrowRight") {
        cambiarFoto(1);
      } else if (event.key === "ArrowLeft") {
        cambiarFoto(-1);
      }
    }
  });

  $(document).ready(function() {
    // Activar los tooltips de Bootstrap
    $('[data-toggle="tooltip"]').tooltip();
  });
</script>
@endsection