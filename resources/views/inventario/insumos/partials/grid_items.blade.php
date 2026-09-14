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

      {{-- Imagen con miniatura optimizada --}}
      @php
          $nombreArchivo = basename($foto->ruta);
          $rutaThumb = asset('albumes/thumbs/' . $nombreArchivo);
      @endphp
      <div style="height: 200px; background-color: #f8f9fa; overflow: hidden; cursor: pointer;" class="d-flex align-items-center justify-content-center" onclick="abrirVisorFacebookDynamic({{ $foto->id }})">
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