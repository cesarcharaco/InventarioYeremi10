@extends('layouts.app')

@section('title') Álbum General de Insumos @endsection

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
  const albumFotos = [
    @foreach($fotos as $foto)
      {
        id: {{ $foto->id }},
        ruta: "{{ asset($foto->ruta) }}",
        titulo: "{{ addslashes($foto->titulo ?: 'Sin título') }}",
        producto: "{{ addslashes(optional($foto->insumo)->producto ?? 'Sin producto') }}",
        serial: "{{ addslashes(optional($foto->insumo)->serial ?? 'N/A') }}",
        descripcion: "{{ addslashes(optional($foto->insumo)->descripcion ?? 'Sin descripción registrada.') }}"
      },
    @endforeach
  ];

  let currentIndex = 0;
  let page = 1;
  let hasMore = true;
  let loading = false;

  function abrirVisorFacebookDynamic(fotoId) {
    let index = albumFotos.findIndex(f => f.id === fotoId);
    if (index !== -1) {
      currentIndex = index;
      actualizarContenidoVisor();
      document.getElementById('fbLightboxModal').style.display = 'block';
      document.body.style.overflow = 'hidden';
    }
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
        albumFotos.push(...response.fotos);
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
    // MAGIA AUTOMÁTICA: Mueve el modal al body al cargar para evitar márgenes del layout
    const modal = document.getElementById('fbLightboxModal');
    if (modal) {
      document.body.appendChild(modal);
    }

    $('[data-toggle="tooltip"]').tooltip();
  });
</script>
@endsection