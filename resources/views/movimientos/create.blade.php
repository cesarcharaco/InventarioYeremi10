@extends('layouts.app')
@section('title') Registrar Movimiento @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-plus-circle"></i> Nuevo Movimiento</h1>
      <p>Registro de ingresos y egresos de caja</p>
    </div>
  </div>

  <div class="row">
    <div class="col-md-8 offset-md-2">
      <div class="tile shadow">
        <h4 class="tile-title text-primary"><i class="fa fa-money"></i> Datos de la Operación</h4>
        <div class="tile-body">
          {!! Form::open(['route' => 'movimientos.store', 'method' => 'POST', 'id' => 'formMovimiento']) !!}
            
            <div class="row">
                <div class="form-group col-md-6">
                    <label class="control-label font-weight-bold">Tipo de Movimiento <span class="text-danger">*</span></label>
                    <select name="tipo" class="form-control" required>
                        <option value="egreso">SALIDA (Egreso)</option>
                        <option value="ingreso">ENTRADA (Ingreso)</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label class="control-label font-weight-bold">Local <span class="text-danger">*</span></label>
                    <select name="id_local" class="form-control select2" required>
                        <option value="">Seleccione...</option>
                        @foreach($locales as $local)
                            <option value="{{ $local->id }}">{{ $local->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label font-weight-bold">Categoría <span class="text-danger">*</span></label>
                <input name="categoria" class="form-control" type="text" placeholder="Ej: Pago de servicios, Emergencia, Insumos" required>
            </div>

            <div class="row p-3 mb-3 bg-light rounded">
                <div class="form-group col-md-6">
                    <label class="control-label font-weight-bold">Efectivo USD ($) <span class="text-info">*</span></label>
                    <input name="efectivo_usd" class="form-control" type="number" step="0.01" value="0.00" required>
                </div>
                <div class="form-group col-md-6">
                    <label class="control-label font-weight-bold">Efectivo Bs (Bs) <span class="text-info">*</span></label>
                    <input name="efectivo_bs" class="form-control" type="number" step="0.01" value="0.00" required>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label font-weight-bold">Observación <span class="text-danger">*</span></label>
                <textarea name="observacion" class="form-control" rows="3" placeholder="Explique brevemente el motivo..." required></textarea>
            </div>

            <div class="tile-footer text-right">
                <a class="btn btn-secondary" href="{{ route('movimientos.index') }}"><i class="fa fa-times"></i> Cancelar</a>
                <button class="btn btn-primary" type="submit"><i class="fa fa-check"></i> Registrar Movimiento</button>
            </div>
          {!! Form::close() !!}
        </div>
      </div>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({ width: '100%' });

    // Auto-formateo a 2 decimales
    $('input[type="number"]').on('blur', function() {
        let val = parseFloat($(this).val());
        if(!isNaN(val)) $(this).val(val.toFixed(2));
    });

    $('#formMovimiento').on('submit', function(e) {
        let usd = parseFloat($('input[name="efectivo_usd"]').val());
        let bs = parseFloat($('input[name="efectivo_bs"]').val());

        if (usd <= 0 && bs <= 0) {
            e.preventDefault();
            swal("Error", "Debe ingresar un monto mayor a cero en Dólares o Bolívares", "error");
            return false;
        }
    });
});
</script>
@endsection