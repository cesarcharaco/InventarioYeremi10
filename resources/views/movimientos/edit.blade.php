@extends('layouts.app')
@section('title') Editar Movimiento @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-edit"></i> Editar Movimiento #{{ $movimiento->id }}</h1>
      <p>Modificación de registros operativos</p>
    </div>
  </div>

  <div class="row">
    <div class="col-md-8 offset-md-2">
      <div class="tile shadow">
        <h4 class="tile-title text-primary"><i class="fa fa-pencil-square-o"></i> Modificar Datos</h4>
        <div class="tile-body">
          {!! Form::model($movimiento, ['route' => ['movimientos.update', $movimiento->id], 'method' => 'PUT', 'id' => 'formEditarMovimiento']) !!}
            
            <div class="row">
                <div class="form-group col-md-6">
                    <label class="control-label font-weight-bold">Tipo (No editable)</label>
                    <input class="form-control" type="text" value="{{ strtoupper($movimiento->tipo) }}" readonly>
                </div>
                <div class="form-group col-md-6">
                    <label class="control-label font-weight-bold">Local / Caja (No editable)</label>
                    <input class="form-control" type="text" value="{{ $movimiento->caja->local->nombre }} - Caja #{{ $movimiento->id_caja }}" readonly>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label font-weight-bold">Categoría <span class="text-danger">*</span></label>
                <input name="categoria" class="form-control" type="text" value="{{ $movimiento->categoria }}" required>
            </div>

            <div class="row p-3 mb-3 bg-light rounded">
                <div class="form-group col-md-6">
                    <label class="control-label font-weight-bold">Efectivo USD ($) <span class="text-info">*</span></label>
                    <input name="efectivo_usd" class="form-control" type="number" step="0.01" value="{{ $movimiento->efectivo_usd }}" required>
                </div>
                <div class="form-group col-md-6">
                    <label class="control-label font-weight-bold">Efectivo Bs (Bs) <span class="text-info">*</span></label>
                    <input name="efectivo_bs" class="form-control" type="number" step="0.01" value="{{ $movimiento->efectivo_bs }}" required>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label font-weight-bold">Observación de la corrección <span class="text-danger">*</span></label>
                <textarea name="observacion" class="form-control" rows="3" required>{{ $movimiento->observacion }}</textarea>
                <small class="text-muted">Indique claramente el motivo de la modificación.</small>
            </div>

            <div class="tile-footer text-right">
                <a class="btn btn-secondary" href="{{ route('movimientos.index') }}"><i class="fa fa-times"></i> Regresar</a>
                <button class="btn btn-primary" type="submit"><i class="fa fa-check"></i> Actualizar Registro</button>
            </div>
          {!! Form::close() !!}
        </div>
      </div>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script type="text/javascript">
    $(document).ready(function() {
        // Formateo automático de montos a 2 decimales
        $('input[type="number"]').on('blur', function() {
            let val = parseFloat($(this).val());
            if(!isNaN(val)) $(this).val(val.toFixed(2));
        });

        // Validación y confirmación
        $('#formEditarMovimiento').on('submit', function(e) {
            e.preventDefault();
            
            let usd = parseFloat($('input[name="efectivo_usd"]').val());
            let bs = parseFloat($('input[name="efectivo_bs"]').val());

            if (usd <= 0 && bs <= 0) {
                swal("Error", "Debe ingresar un monto mayor a cero en Dólares o Bolívares", "error");
                return false;
            }

            swal({
                title: "¿Confirmar cambios?",
                text: "Los cambios afectarán el balance de esta caja.",
                icon: "info",
                buttons: ["Cancelar", "Aceptar"],
            }).then((confirm) => {
                if (confirm) this.submit();
            });
        });
    });
</script>
@endsection