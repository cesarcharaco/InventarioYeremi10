@extends('layouts.app')

@section('title') Cargar Ofertas @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-file-upload"></i> Carga Masiva</h1>
      <p>Importar lista de ofertas</p>
    </div>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <h4 class="mb-4">Información de la Lista</h4>
        
        @include('layouts.partials.flash-messages')
        
        <form action="{{ route('insumos-mayores.importar') }}" method="POST" enctype="multipart/form-data">
          @csrf
          
          <div class="row">
            {{-- Nombre y Proveedor --}}
            <div class="col-12 col-md-6 form-group">
              <label>Nombre de la Oferta <b class="text-danger">*</b></label>
              <input type="text" name="nombre" class="form-control" placeholder="Ej: Oferta Espacial" required>
            </div>
            <div class="col-12 col-md-6 form-group">
              <label>Proveedor <b class="text-danger">*</b></label>
              <input type="text" name="proveedor" class="form-control" placeholder="Ej: SPACE VISION" required>
            </div>
          </div>

          <div class="row">
            {{-- Fechas y Monto (Optimizados para móvil con inputmode) --}}
            <div class="col-12 col-md-4 form-group">
              <label>Fecha Inicio <b class="text-danger">*</b></label>
              <input type="date" name="fecha_inicio" id="fecha_inicio" 
                     class="form-control" 
                     min="{{ date('Y-m-d') }}" required>
            </div>

            <div class="col-12 col-md-4 form-group">
              <label>Fecha Fin <b class="text-danger">*</b></label>
              <input type="date" name="fecha_fin" id="fecha_fin" 
                     class="form-control" required>
            </div>
            <div class="col-12 col-md-4 form-group">
              <label>Monto Mínimo ($) <b class="text-danger">*</b></label>
              <input type="number" step="0.01" inputmode="decimal" name="monto_minimo" class="form-control" value="0.00" required>
            </div>
          </div>

          <div class="row">
            <div class="col-12 col-md-6 form-group">
                <label>Porcentaje de Incremento (%) <b class="text-danger">*</b></label>
                <input type="number" step="0.01" inputmode="decimal" name="incremento" class="form-control" placeholder="Ej: 10.5" required>
                <small class="text-muted">Se aplicará este porcentaje al costo del producto.</small>
            </div>
            {{-- Archivo --}}
            <div class="col-12 form-group">
              <label>Archivo Excel / CSV <b class="text-danger">*</b></label>
              <div class="custom-file">
                <input type="file" name="archivo" class="form-control" accept=".csv, .xlsx, .xls" required>
              </div>
              <small class="text-muted">Formato soportado: .csv, .xlsx</small>
            </div>
          </div>

          <div class="tile-footer mt-3">
            <button class="btn btn-success btn-block btn-lg" type="submit">
              <i class="fa fa-check-circle"></i> Procesar y Crear Lista
            </button>
            <a class="btn btn-secondary btn-block mt-2" href="{{ route('insumos-mayores.index') }}">
              <i class="fa fa-times-circle"></i> Cancelar
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script>
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');

    fechaInicio.addEventListener('change', function() {
        if (this.value) {
            // La fecha fin debe ser al menos un día después de la inicio
            fechaFin.min = this.value; 
            if (fechaFin.value && fechaFin.value < this.value) {
                fechaFin.value = ''; // Limpia si la fecha fin era anterior
            }
        }
    });
</script>
@endsection
