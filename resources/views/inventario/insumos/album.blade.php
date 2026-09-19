@extends('layouts.app')

@section('title') Álbum de Insumo @endsection
@push('styles')
<style>
  /* --- MODAL FLOTANTE GLOBAL --- */
  #fbLightboxModal {
    display: none;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    max-width: none !important;
    max-height: none !important;
    margin: 0 !important;
    padding: 0 !important;
    background-color: rgba(0, 0, 0, 0.96) !important;
    z-index: 9999999 !important;
    overflow-y: auto !important;
    -webkit-overflow-scrolling: touch;
  }

  .fb-lightbox-wrapper {
    display: flex;
    width: 100%;
    min-height: 100%;
    box-sizing: border-box;
  }
  .fb-lightbox-img-container {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    position: relative;
    box-sizing: border-box;
    background-color: #000;
  }
  .fb-lightbox-sidebar {
    width: 380px;
    background-color: #242526;
    color: #e4e6eb;
    display: flex;
    flex-direction: column;
    border-left: 1px solid #393a3b;
    padding: 25px;
    box-sizing: border-box;
  }

  /* --- RESPONSIVE MÓVIL --- */
  @media (max-width: 1024px) {
    .fb-lightbox-wrapper {
      flex-direction: column !important;
      width: 100% !important;
      min-height: 100% !important;
      height: auto !important;
    }
    .fb-lightbox-img-container {
      width: 100% !important;
      height: 52vh !important;
      min-height: 300px !important;
      max-height: 52vh !important;
      padding: 10px !important;
      flex: none !important;
      position: relative !important;
    }
    .fb-lightbox-img-container img {
      max-width: 100% !important;
      max-height: 100% !important;
      object-fit: contain !important;
    }
    .fb-lightbox-sidebar {
      width: 100% !important;
      max-width: 100% !important;
      height: auto !important;
      border-left: none !important;
      border-top: 1px solid #393a3b !important;
      padding: 20px 15px !important;
      background-color: #242526 !important;
    }
  }
</style>
@endpush
@section('content')
<main class="app-content">
  <div class="app-title">
      <div>
        <h1><i class="fa fa-images"></i> Álbum de Fotos</h1>
        <p>Insumo: <strong>{{ $insumo->producto }}</strong> | Serial: <span class="badge badge-secondary">{{ $insumo->serial }}</span></p>
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

  {{-- Sección de registro múltiple con el campo de Título --}}
  <div class="tile mb-4">
    <h3 class="tile-title text-center mb-3"><i class="fa fa-cloud-upload-alt text-primary"></i> Agregar Fotografías al Álbum</h3>
    <div class="tile-body">

      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif
      <form action="{{ route('insumos.album.store_multiple', $insumo->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div class="row justify-content-center">
          <div class="col-md-8">
            <div class="form-group text-center p-4 border rounded bg-light position-relative" style="border: 2px dashed #007bff !important; transition: all 0.3s ease; cursor: pointer;">
              <i class="fa fa-cloud-upload-alt fa-3x text-primary mb-2"></i>
              <h5 class="mb-1">Haz clic aquí para seleccionar imágenes</h5>
              <p class="text-muted small mb-2">Puedes seleccionar múltiples archivos (Formatos: JPG, PNG, WEBP. Máx: 3MB c/u)</p>
              
              <input type="file" name="fotos[]" id="inputFotos" class="position-absolute w-100 h-100" style="top: 0; left: 0; opacity: 0; cursor: pointer;" multiple accept="image/jpeg,png,jpg,webp" required>
              
              <div id="fileFeedback" class="font-weight-bold text-success mt-2" style="font-size: 0.95rem;"></div>
            </div>
          </div>
        </div>

        <div class="row justify-content-center mt-3">
          <div class="col-md-6">
            <div class="form-group text-center">
              <label class="font-weight-bold">Título Base (Opcional)</label>
              <input type="text" name="titulo" class="form-control text-center" placeholder="Ej. Vista frontal de repuesto" maxlength="150">
              <small class="form-text text-muted">Si se deja en blanco, se usará el nombre original del archivo.</small>
            </div>
          </div>
        </div>

        <div class="row justify-content-center mt-3">
          <div class="col-md-3">
            <button type="submit" class="btn btn-primary btn-block py-2 shadow-sm">
              <i class="fa fa-upload mr-1"></i> Subir Imágenes
            </button>
          </div>
        </div>

      </form>
    </div>
  </div>

  {{-- Galería de Fotos --}}
  <div class="tile">
    <h3 class="tile-title mb-4"><i class="fa fa-th"></i> Galería de Fotos</h3>
    
    @if($insumo->fotos->count() > 0)
      <div class="row">
        @foreach($insumo->fotos as $foto)
          <div class="col-md-3 col-sm-6 mb-4">
            <div class="card h-100 shadow-sm position-relative">
              
              @if($foto->es_principal)
                <span class="badge badge-success position-absolute" style="top: 10px; left: 10px; z-index: 10; font-size: 0.8rem;">
                  <i class="fa fa-star">️</i> Principal
                </span>
              @endif

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

                  <a class="dropdown-item" href="{{ asset($foto->ruta) }}" download="insumo_{{ $insumo->serial }}_{{ $foto->id }}.jpg">
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

              @php
                  $nombreArchivo = basename($foto->ruta);
                  $rutaThumb = file_exists(public_path('albumes/thumbs/' . $nombreArchivo)) 
                               ? asset('albumes/thumbs/' . $nombreArchivo) 
                               : asset($foto->ruta);
              @endphp
              <div style="height: 200px; background-color: #f8f9fa; overflow: hidden; cursor: pointer;" class="d-flex align-items-center justify-content-center" onclick="abrirVisorFacebook({{ $loop->index }})">
                <img src="{{ $rutaThumb }}" loading="lazy" class="card-img-top" alt="{{ $foto->titulo }}" style="width: 100%; height: 100%; object-fit: cover;">
              </div>
              <div class="card-body p-2 text-center bg-light">
                <p class="card-text text-muted mb-0" style="font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                  {{ $foto->titulo ?: 'Sin título' }}
                </p>
              </div>

            </div>
          </div>
        @endforeach
      </div>
    @else
      <div class="text-center py-5">
        <i class="fa fa-image fa-4x text-muted mb-3"></i>
        <h5 class="text-muted">No hay fotografías registradas para este insumo.</h5>
        <p class="text-muted">Utiliza el formulario superior para comenzar a subir imágenes</p>
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

{{-- VISOR TIPO FACEBOOK --}}
<div id="fbLightboxModal" style="display: none; position: fixed !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; max-width: none !important; max-height: none !important; margin: 0 !important; padding: 0 !important; background-color: rgba(0, 0, 0, 0.96) !important; z-index: 9999999 !important; overflow-y: auto !important; -webkit-overflow-scrolling: touch;">
  
  <button type="button" onclick="cerrarVisorFacebook()" style="position: fixed; top: 15px; right: 15px; background: rgba(0,0,0,0.7); border: none; color: #fff; font-size: 1.8rem; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; z-index: 10000000; outline: none; display: flex; align-items: center; justify-content: center;">
    &times;
  </button>

  <div class="fb-lightbox-wrapper">
    <div class="fb-lightbox-img-container">
      <button type="button" onclick="cambiarFoto(-1)" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); background: rgba(0, 0, 0, 0.7); border: none; color: #fff; font-size: 1.2rem; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; z-index: 10000000; outline: none; display: flex; align-items: center; justify-content: center;">
        <i class="fa fa-chevron-left"></i>
      </button>

      <img id="fbLightboxImg" src="" alt="" style="max-width: 100%; max-height: 100%; object-fit: contain;">

      <button type="button" onclick="cambiarFoto(1)" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: rgba(0, 0, 0, 0.7); border: none; color: #fff; font-size: 1.2rem; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; z-index: 10000000; outline: none; display: flex; align-items: center; justify-content: center;">
        <i class="fa fa-chevron-right"></i>
      </button>
    </div>

    {{-- BARRA LATERAL CON DIV SEPARADOR INTERNO --}}
    <div class="fb-lightbox-sidebar">
      <div style="padding: 5px 12px; width: 100%; box-sizing: border-box;">
        
        <h4 style="color: #fff; border-bottom: 1px solid #393a3b; padding-bottom: 15px; margin-bottom: 20px; font-size: 1.1rem;">
          <i class="fa fa-info-circle text-primary mr-2"></i> Detalle del Insumo
        </h4>

        <div style="margin-bottom: 15px;">
          <span style="font-size: 0.8rem; color: #b0b3b8; text-transform: uppercase; display: block; font-weight: bold;">Título de la Foto</span>
          <p id="fbLightboxTitulo" style="font-size: 1rem; margin-top: 5px; color: #fff; word-break: break-word;"></p>
        </div>

        <div style="margin-bottom: 15px;">
          <span style="font-size: 0.8rem; color: #b0b3b8; text-transform: uppercase; display: block; font-weight: bold;">Producto</span>
          <p id="fbLightboxProducto" style="font-size: 0.95rem; margin-top: 5px; word-break: break-word; color: #e4e6eb;"></p>
        </div>

        <div style="margin-bottom: 15px;">
          <span style="font-size: 0.8rem; color: #b0b3b8; text-transform: uppercase; display: block; font-weight: bold;">Serial</span>
          <p style="margin-top: 5px;"><span id="fbLightboxSerial" class="badge badge-secondary" style="font-size: 0.85rem; padding: 5px 10px;"></span></p>
        </div>

        <div style="margin-bottom: 15px; flex-grow: 1;">
          <span style="font-size: 0.8rem; color: #b0b3b8; text-transform: uppercase; display: block; font-weight: bold;">Descripción</span>
          <p id="fbLightboxDescripcion" style="font-size: 0.9rem; color: #b0b3b8; margin-top: 5px; line-height: 1.4; word-break: break-word;"></p>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
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

  document.getElementById('inputFotos').addEventListener('change', function(e) {
      let count = e.target.files.length;
      let feedback = document.getElementById('fileFeedback');
      if (count > 0) {
        feedback.textContent = count === 1 ? '1 archivo seleccionado' : count + ' archivos seleccionados';
      } else {
        feedback.textContent = '';
      }
    });

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

      const albumFotos = [
        @foreach($insumo->fotos as $foto)
          {
            ruta: "{{ asset($foto->ruta) }}",
            titulo: "{{ addslashes($foto->titulo ?: 'Sin título') }}"
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
      }

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
</script>
@endsection