@foreach($fotos as $foto)
  <div class="col-md-3 col-sm-6 mb-4">
    <div class="card h-100 shadow-sm position-relative">
      
      @if($foto->es_principal)
        <span class="badge badge-success position-absolute" style="top: 10px; left: 10px; z-index: 10; font-size: 0.8rem;">
          <i class="fa fa-star"></i> Principal
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

          <a class="dropdown-item" href="{{ asset($foto->ruta) }}" download="insumo_{{ $foto->insumo->serial ?? 'general' }}_{{ $foto->id }}.jpg">
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
      <div style="height: 200px; background-color: #f8f9fa; overflow: hidden; cursor: pointer;" 
           class="d-flex align-items-center justify-content-center lightbox-trigger" 
           data-ruta="{{ asset($foto->ruta) }}"
           data-titulo="{{ $foto->titulo ?: 'Sin título' }}"
           data-producto="{{ $foto->insumo->producto ?? 'N/D' }}"
           data-serial="{{ $foto->insumo->serial ?? 'N/D' }}"
           data-descripcion="{{ $foto->insumo->descripcion ?? 'Sin descripción registrada.' }}">
        <img src="{{ $rutaThumb }}" class="card-img-top" alt="{{ $foto->titulo }}" style="width: 100%; height: 100%; object-fit: cover; pointer-events: none;">
      </div>
      <div class="card-body p-2 text-center bg-light">
        <p class="card-text text-muted mb-0" style="font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
          {{ $foto->titulo ?: 'Sin título' }}
        </p>
      </div>

    </div>
  </div>
@endforeach