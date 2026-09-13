{{-- resources/views/inventario/insumos/album.blade.php --}}
@extends('layouts.app')

@section('title') Álbum de Insumo @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-images"></i> Álbum de Fotos</h1>
      <p>Insumo: <strong>{{ $insumo->producto }}</strong> | Serial: <span class="badge badge-secondary">{{ $insumo->serial }}</span></p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item">SAYER</li>
      <li class="breadcrumb-item"><a href="{{ route('insumos.index') }}">Insumos</a></li>
      <li class="breadcrumb-item">Álbum</li>
    </ul>
  </div>

  <div class="basic-tb-hd text-center">
    @include('layouts.partials.flash-messages')
  </div>

  {{-- Sección de registro múltiple con el campo de Título en resources/views/inventario/insumos/album.blade.php --}}
  <div class="tile mb-4">
    <h3 class="tile-title text-center mb-3"><i class="fa fa-cloud-upload-alt text-primary"></i> Agregar Fotografías al Álbum</h3>
    <div class="tile-body">

      {{-- MOSTRAR ERRORES DE VALIDACIÓN SI LOS HAY --}}
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
        
        {{-- Área Centralizada de Carga Estilo Dropzone --}}
        <div class="row justify-content-center">
          <div class="col-md-8">
            <div class="form-group text-center p-4 border rounded bg-light position-relative" style="border: 2px dashed #007bff !important; transition: all 0.3s ease; cursor: pointer;">
              <i class="fa fa-cloud-upload-alt fa-3x text-primary mb-2"></i>
              <h5 class="mb-1">Haz clic aquí para seleccionar imágenes</h5>
              <p class="text-muted small mb-2">Puedes seleccionar múltiples archivos (Formatos: JPG, PNG, WEBP. Máx: 3MB c/u)</p>
              
              {{-- Input invisible cubriendo toda la caja para facilitar el clic --}}
              <input type="file" name="fotos[]" id="inputFotos" class="position-absolute w-100 h-100" style="top: 0; left: 0; opacity: 0; cursor: pointer;" multiple accept="image/jpeg,png,jpg,webp" required>
              
              {{-- Mensaje dinámico de archivos seleccionados --}}
              <div id="fileFeedback" class="font-weight-bold text-success mt-2" style="font-size: 0.95rem;"></div>
            </div>
          </div>
        </div>

        {{-- Campo de Título Centrado --}}
        <div class="row justify-content-center mt-3">
          <div class="col-md-6">
            <div class="form-group text-center">
              <label class="font-weight-bold">Título Base (Opcional)</label>
              <input type="text" name="titulo" class="form-control text-center" placeholder="Ej. Vista frontal de repuesto" maxlength="150">
              <small class="form-text text-muted">Si se deja en blanco, se usará el nombre original del archivo.</small>
            </div>
          </div>
        </div>

        {{-- Botón de Envío Centrado --}}
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

  {{-- Visualizador de Imágenes estilo Facebook --}}
  <div class="tile">
    <h3 class="tile-title mb-4"><i class="fa fa-th"></i> Galería de Fotos</h3>
    
    @if($insumo->fotos->count() > 0)
      <div class="row">
        @foreach($insumo->fotos as $foto)
          <div class="col-md-3 col-sm-6 mb-4">
            <div class="card h-100 shadow-sm position-relative">
              
              {{-- Indicador si es Principal --}}
              @if($foto->es_principal)
                <span class="badge badge-success position-absolute" style="top: 10px; left: 10px; z-index: 10; font-size: 0.8rem;">
                  <i class="fa fa-star">️</i> Principal
                </span>
              @endif

              {{-- Menú Desplegable estilo Facebook (Lápiz) --}}
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

              {{-- Imagen con altura fija y diseño adaptable --}}
              <div style="height: 200px; background-color: #f8f9fa; overflow: hidden;" class="d-flex align-items-center justify-content-center">
                <img src="{{ asset($foto->ruta) }}" class="card-img-top" alt="{{ $foto->titulo }}" style="width: 100%; height: 100%; object-fit: cover;">
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
</script>
@endsection