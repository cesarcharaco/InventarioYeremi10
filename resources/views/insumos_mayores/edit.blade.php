@extends('layouts.app')

@section('title') Editar Oferta @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-edit"></i> Editar Lista de Ofertas</h1>
      <p>Actualizar información de: <strong>{{ $lista->nombre }}</strong></p>
    </div>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        @include('layouts.partials.flash-messages')

        @if($tienePedidos)
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> <strong>Nota:</strong> Esta lista tiene {{ $lista->pedidos_count }} pedido(s) activo(s). Los campos bloqueados no son editables para mantener la integridad de los pedidos existentes.
            </div>
        @endif
        
        <form action="{{ route('insumos-mayores.actualizar', $lista->id) }}" method="POST" enctype="multipart/form-data">
          @csrf
          @method('PUT')
          
          <div class="row">
            {{-- Datos Siempre Editables --}}
            <div class="col-12 col-md-6 form-group">
              <label>Nombre de la Oferta <b class="text-danger">*</b></label>
              <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $lista->nombre) }}" required>
            </div>
            <div class="col-12 col-md-6 form-group">
              <label>Proveedor <b class="text-danger">*</b></label>
              <input type="text" name="proveedor" class="form-control" value="{{ old('proveedor', $lista->proveedor) }}" required>
            </div>
          </div>

          <div class="row">
            <div class="col-12 col-md-4 form-group">
              <label>Fecha Inicio <b class="text-danger">*</b></label>
              <input type="date" name="fecha_inicio" class="form-control" value="{{ $lista->fecha_inicio }}" {{ $bloqueoFechas ? 'readonly' : '' }} required>
            </div>
            <div class="col-12 col-md-4 form-group">
              <label>Fecha Fin <b class="text-danger">*</b></label>
              <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="{{ $lista->fecha_fin }}" required>
            </div>
            
            {{-- Datos bloqueados si hay pedidos --}}
            <div class="col-12 col-md-4 form-group">
              <label>Monto Mínimo ($) <b class="text-danger">*</b></label>
              <input type="number" step="0.01" name="monto_minimo" class="form-control" value="{{ $lista->monto_minimo }}" {{ $tienePedidos ? 'readonly' : '' }} required>
            </div>
          </div>

          <div class="row">
            <div class="col-12 col-md-6 form-group">
                <label>Porcentaje de Incremento (%) <b class="text-danger">*</b></label>
                <input type="number" step="0.01" name="incremento" class="form-control" value="{{ $lista->incremento }}" {{ $tienePedidos ? 'readonly' : '' }} required>
            </div>
            
            <div class="col-12 col-md-6 form-group">
              <label>Archivo Excel / CSV (Opcional)</label>
              <input type="file" name="archivo" class="form-control" accept=".csv, .xlsx, .xls" {{ $tienePedidos ? 'disabled' : '' }}>
              <small class="text-muted">Solo suba un nuevo archivo si desea reemplazar los productos actuales.</small>
            </div>
          </div>

          <div class="tile-footer mt-3">
            <button class="btn btn-primary btn-block btn-lg" type="submit">
              <i class="fa fa-save"></i> Guardar Cambios
            </button>
            <a class="btn btn-secondary btn-block mt-2" href="{{ route('insumos-mayores.gestion') }}">
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
    // Tu lógica de fechas sigue funcionando igual para ambos casos
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');
    fechaInicio.addEventListener('change', function() {
        fechaFin.min = this.value;
    });
</script>
@endsection