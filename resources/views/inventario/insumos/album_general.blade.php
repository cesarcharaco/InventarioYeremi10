@extends('layouts.app')

@section('title') Álbum General de Insumos @endsection
@push('styles')
<style>
  /* Estilos base del Lightbox (Desktop) */
  #fbLightboxModal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.95);
    z-index: 9999;
    overflow: hidden;
  }

  .fb-close-btn {
    position: fixed;
    top: 15px;
    right: 20px;
    background: rgba(0,0,0,0.6);
    border: none;
    color: #fff;
    font-size: 2rem;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    cursor: pointer;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    outline: none;
  }

  .fb-lightbox-wrapper {
    display: flex;
    width: 100%;
    height: 100%;
    box-sizing: border-box;
  }

  .fb-lightbox-img-container {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    position: relative;
    background-color: #000;
    box-sizing: border-box;
    height: 100%;
  }

  .fb-lightbox-img-container img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    display: block;
  }

  .fb-nav-btn {
    position: absolute;
    background: rgba(0, 0, 0, 0.6);
    border: none;
    color: #fff;
    font-size: 1.5rem;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    cursor: pointer;
    z-index: 10000;
    outline: none;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .fb-prev-btn { left: 20px; }
  .fb-next-btn { right: 20px; }

  .fb-lightbox-sidebar {
    width: 380px;
    min-width: 380px;
    background-color: #242526;
    color: #e4e6eb;
    display: flex;
    flex-direction: column;
    border-left: 1px solid #393a3b;
    padding: 25px;
    box-sizing: border-box;
    height: 100%;
    overflow-y: auto;
  }

  .fb-lightbox-sidebar h4 {
    color: #fff;
    border-bottom: 1px solid #393a3b;
    padding-bottom: 15px;
    margin-bottom: 20px;
    font-size: 1.2rem;
  }

  .fb-detail-group {
    margin-bottom: 15px;
  }

  .fb-label {
    font-size: 0.85rem;
    color: #b0b3b8;
    text-transform: uppercase;
    display: block;
    font-weight: bold;
  }

  .fb-value-white {
    font-size: 1.05rem;
    margin-top: 5px;
    color: #fff;
    word-break: break-word;
  }

  .fb-value {
    font-size: 1rem;
    margin-top: 5px;
    color: #e4e6eb;
    word-break: break-word;
  }

  .fb-value-muted {
    font-size: 0.95rem;
    color: #b0b3b8;
    margin-top: 5px;
    line-height: 1.4;
    word-break: break-word;
  }

  /* MEDIA QUERY RESPONSIVE PARA MÓVILES (<= 1024px) */
  @media (max-width: 1024px) {
    #fbLightboxModal {
      overflow-y: auto !important;
    }

    .fb-lightbox-wrapper {
      flex-direction: column !important;
      height: auto !important;
      min-height: 100% !important;
    }

    .fb-lightbox-img-container {
      width: 100% !important;
      height: 55vh !important;
      min-height: 300px !important;
      max-height: 60vh !important;
      flex: none !important;
      padding: 10px !important;
      background-color: #000 !important;
    }

    .fb-lightbox-img-container img {
      max-height: 100% !important;
      max-width: 100% !important;
      object-fit: contain !important;
    }

    .fb-lightbox-sidebar {
      width: 100% !important;
      min-width: 100% !important;
      height: auto !important;
      overflow-y: visible !important;
      border-left: none !important;
      border-top: 1px solid #393a3b !important;
      padding: 20px !important;
      flex: 1 !important;
    }

    .fb-close-btn {
      top: 10px;
      right: 10px;
      width: 38px;
      height: 38px;
      font-size: 1.5rem;
    }
  }
</style>
@endpush
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
      <div class="row" id="galeria-grid">
        @include('inventario.insumos.partials.grid_items', ['fotos' => $fotos])
      </div>

      <div id="scroll-sentinel" class="text-center py-4">
        <div id="loading-spinner" style="display: none;">
          <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
          <p class="text-muted mt-2 mb-0" style="font-size: 0.9rem;">Cargando más fotografías...</p>
        </div>
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

{{-- VISOR TIPO FACEBOOK RESPONSIVE --}}
<div id="fbLightboxModal">
  <button type="button" onclick="cerrarVisorFacebook()" class="fb-close-btn">&times;</button>

  <div class="fb-lightbox-wrapper">
    
    <div class="fb-lightbox-img-container">
      <button type="button" onclick="cambiarFoto(-1)" class="fb-nav-btn fb-prev-btn">
        <i class="fa fa-chevron-left"></i>
      </button>

      <img id="fbLightboxImg" src="" alt="">

      <button type="button" onclick="cambiarFoto(1)" class="fb-nav-btn fb-next-btn">
        <i class="fa fa-chevron-right"></i>
      </button>
    </div>

    <div class="fb-lightbox-sidebar">
      <h4><i class="fa fa-info-circle text-primary mr-2"></i> Detalle del Insumo</h4>

      <div class="fb-detail-group">
        <span class="fb-label">Título de la Foto</span>
        <p id="fbLightboxTitulo" class="fb-value-white"></p>
      </div>

      <div class="fb-detail-group">
        <span class="fb-label">Producto</span>
        <p id="fbLightboxProducto" class="fb-value"></p>
      </div>

      <div class="fb-detail-group">
        <span class="fb-label">Serial</span>
        <p><span id="fbLightboxSerial" class="badge badge-secondary" style="font-size: 0.9rem; padding: 6px 10px;"></span></p>
      </div>

      <div class="fb-detail-group" style="flex-grow: 1;">
        <span class="fb-label">Descripción</span>
        <p id="fbLightboxDescripcion" class="fb-value-muted"></p>
      </div>
    </div>

  </div>
</div>
@endsection

@section('scripts')
<script>
  let currentIndex = 0;
  let triggerElements = [];
  let page = 1;
  let hasMore = true;
  let loading = false;

  // Delegación de eventos global (Funciona incluso con elementos cargados por AJAX)
  $(document).on('click', '.lightbox-trigger', function() {
    console.log("¡Clic detectado en la foto con éxito!");
    triggerElements = Array.from(document.querySelectorAll('.lightbox-trigger'));
    currentIndex = triggerElements.indexOf(this);
    actualizarContenidoVisor();
    document.getElementById('fbLightboxModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
  });

  function cerrarVisorFacebook() {
    document.getElementById('fbLightboxModal').style.display = 'none';
    document.body.style.overflow = 'auto';
  }

  function cambiarFoto(direccion) {
    currentIndex += direccion;
    if (currentIndex >= triggerElements.length) {
      currentIndex = 0;
    } else if (currentIndex < 0) {
      currentIndex = triggerElements.length - 1;
    }
    actualizarContenidoVisor();
  }

  function actualizarContenidoVisor() {
    let el = triggerElements[currentIndex];
    if (!el) return;
    document.getElementById('fbLightboxImg').src = el.getAttribute('data-ruta');
    document.getElementById('fbLightboxTitulo').textContent = el.getAttribute('data-titulo');
    
    // Si estás en el álbum individual (donde estos elementos no aplican, evitamos errores validando si existen)
    let prod = document.getElementById('fbLightboxProducto');
    if (prod) prod.textContent = el.getAttribute('data-producto');
    
    let ser = document.getElementById('fbLightboxSerial');
    if (ser) ser.textContent = el.getAttribute('data-serial');
    
    let desc = document.getElementById('fbLightboxDescripcion');
    if (desc) desc.textContent = el.getAttribute('data-descripcion');
  }

  const observer = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting && hasMore && !loading) {
      cargarMasFotos();
    }
  }, { rootMargin: '300px' });

  const sentinel = document.getElementById('scroll-sentinel');
  if (sentinel) {
    observer.observe(sentinel);
  }

  function cargarMasFotos() {
    loading = true;
    page++;
    document.getElementById('loading-spinner').style.display = 'block';

    $.ajax({
      url: "{{ route('insumos.album.general') }}?page=" + page,
      type: 'GET',
      success: function(response) {
        $('#galeria-grid').append(response.html);
        hasMore = response.has_more;
        loading = false;
        document.getElementById('loading-spinner').style.display = 'none';

        if (!hasMore) {
          observer.disconnect();
          document.getElementById('scroll-sentinel').innerHTML = '<p class="text-muted small">No hay más fotografías que mostrar.</p>';
        }

        $('[data-toggle="tooltip"]').tooltip();
      },
      error: function() {
        loading = false;
        document.getElementById('loading-spinner').style.display = 'none';
      }
    });
  }

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
    $('[data-toggle="tooltip"]').tooltip();
  });
</script>
@endsection