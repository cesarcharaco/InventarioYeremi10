@extends('layouts.app')

@section('title') Editar Modelo de Venta @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-edit"></i> Editar Modelo</h1>
      <p>Actualice las tasas o el método de cálculo para <strong>{{ $modeloVenta->modelo }}</strong></p>
    </div>
  </div>

  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="tile">
        <div class="tile-body">
          <form action="{{ route('modelos-venta.update', $modeloVenta->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="form-group">
              <label class="control-label"><strong>Nombre del Modelo</strong> <b class="text-danger">*</b></label>
              <input class="form-control form-control-sm" type="text" name="modelo" value="{{ $modeloVenta->modelo }}" required>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="control-label"><strong>Tasa BCV</strong></label>
                  <input class="form-control form-control-sm" type="number" step="0.01" name="tasa_bcv" value="{{ $modeloVenta->tasa_bcv }}">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="control-label"><strong>Tasa Binance (USDT)</strong></label>
                  <input class="form-control form-control-sm" type="number" step="0.01" name="tasa_binance" value="{{ $modeloVenta->tasa_binance }}">
                </div>
              </div>
            </div>

            <hr>

            <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
              <label class="btn btn-outline-primary {{ $modeloVenta->porcentaje_extra ? '' : 'active' }} w-100">
                <input type="radio" name="metodo_calculo" value="factor" {{ $modeloVenta->porcentaje_extra ? '' : 'checked' }}> Usar Factor
              </label>
              <label class="btn btn-outline-primary {{ $modeloVenta->porcentaje_extra ? 'active' : '' }} w-100">
                <input type="radio" name="metodo_calculo" value="porcentaje" {{ $modeloVenta->porcentaje_extra ? 'checked' : '' }}> Usar Porcentaje
              </label>
            </div>

            {{-- Sección Factor (Doble campo) --}}
            <div id="seccion_factor" class="card bg-light mt-3" style="{{ is_null($modeloVenta->porcentaje_extra) ? '' : 'display:none;' }}">
                <div class="card-body py-2">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Factor BCV (Divisor)</strong></label>
                                <input class="form-control form-control-sm" type="number" step="0.01" name="factor_bcv" value="{{ $modeloVenta->factor_bcv }}" placeholder="Ej: 0.70">
                                <small class="text-muted">Actual: Costo / {{ $modeloVenta->factor_bcv ?? '0.00' }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Factor USDT (Divisor)</strong></label>
                                <input class="form-control form-control-sm" type="number" step="0.01" name="factor_usdt" value="{{ $modeloVenta->factor_usdt }}" placeholder="Ej: 0.60">
                                <small class="text-muted">Actual: Costo / {{ $modeloVenta->factor_usdt ?? '0.00' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sección Porcentaje (Margen Fijo) --}}
            <div id="seccion_porcentaje" class="card bg-light mt-3" style="{{ !is_null($modeloVenta->porcentaje_extra) ? '' : 'display:none;' }}">
                <div class="card-body py-2">
                    <label><strong>Porcentaje Extra (Margen Fijo)</strong></label>
                    <input class="form-control form-control-sm" type="number" step="0.01" name="porcentaje_extra" value="{{ $modeloVenta->porcentaje_extra }}" placeholder="Ej: 0.10">
                    <small class="text-muted">Actual: Costo + {{ ($modeloVenta->porcentaje_extra * 100) }}%</small>
                </div>
            </div>

            <div class="tile-footer mt-4">
              <button class="btn btn-primary btn-sm" type="submit"><i class="fa fa-refresh"></i> Actualizar Modelo</button>
              <a class="btn btn-secondary btn-sm" href="{{ route('modelos-venta.index') }}"><i class="fa fa-times"></i> Cancelar</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script>
  $(document).ready(function() {
    if ($('input[name="metodo_calculo"]:checked').val() === 'porcentaje') {
        $('#seccion_factor').hide();
        $('#seccion_porcentaje').show();
    }
    $('input[name="metodo_calculo"]').change(function() {
      if ($(this).val() === 'factor') {
        $('#seccion_factor').fadeIn();
        $('#seccion_porcentaje').hide();
        // Opcional: limpiar el valor del campo oculto para evitar confusión
        $('input[name="porcentaje_extra"]').val('');
      } else {
        $('#seccion_factor').hide();
        $('#seccion_porcentaje').fadeIn();
        $('input[name="factor"]').val('');
      }
    });
  });
</script>
@endsection