@extends('layouts.app')

@section('title') Nuevo Modelo de Venta @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-edit"></i> Registrar Modelo</h1>
      <p>Configure las tasas y el método de cálculo para el inventario.</p>
    </div>
  </div>

  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="tile">
        <h3 class="tile-title border-bottom pb-2">Datos del Modelo</h3>
        <div class="tile-body">
          <form action="{{ route('modelos-venta.store') }}" method="POST">
            @csrf
            
            <div class="form-group">
              <label class="control-label"><strong>Nombre del Modelo</strong> <b class="text-danger">*</b></label>
              <input class="form-control form-control-sm" type="text" name="modelo" placeholder="Ej: General, Bajo Costo..." required>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="control-label"><strong>Tasa BCV</strong></label>
                  <input class="form-control form-control-sm" type="number" step="0.01" name="tasa_bcv" placeholder="0.00">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="control-label"><strong>Tasa Binance (USDT)</strong></label>
                  <input class="form-control form-control-sm" type="number" step="0.01" name="tasa_binance" placeholder="0.00">
                </div>
              </div>
            </div>

            <hr>

            <div class="form-group">
              <label class="control-label d-block"><strong>Método de Cálculo</strong></label>
              <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                <label class="btn btn-outline-primary active w-100">
                  <input type="radio" name="metodo_calculo" value="factor" checked> Usar Factor (Divisor)
                </label>
                <label class="btn btn-outline-primary w-100">
                  <input type="radio" name="metodo_calculo" value="porcentaje"> Usar Porcentaje Extra
                </label>
              </div>
            </div>

            {{-- Sección Dinámica --}}
            <div id="seccion_factor" class="card bg-light mt-3">
              <div class="card-body py-2">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><strong>Factor BCV (Divisor)</strong></label>
                      <input class="form-control form-control-sm" type="number" step="0.01" name="factor_bcv" placeholder="Ej: 0.70">
                      <small class="text-muted">Para cálculo de Bs. y Dólar BCV</small>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><strong>Factor USDT (Divisor)</strong></label>
                      <input class="form-control form-control-sm" type="number" step="0.01" name="factor_usdt" placeholder="Ej: 0.60">
                      <small class="text-muted">Para precio en Binance/Efectivo</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div id="seccion_porcentaje" class="card bg-light mt-3" style="display:none;">
              <div class="card-body py-2">
                <label><strong>Porcentaje Extra (Margen Fijo)</strong></label>
                <input class="form-control form-control-sm" type="number" step="0.01" name="porcentaje_extra" placeholder="Ej: 0.10 para 10%">
                <small class="text-muted">Se aplica el mismo margen a todos los precios de venta.</small>
              </div>
            </div>

            <div class="tile-footer mt-4">
              <button class="btn btn-primary btn-sm" type="submit"><i class="fa fa-save"></i> Guardar</button>
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
    $('input[name="factor_bcv"], input[name="factor_usdt"]').val('');
    $('input[name="metodo_calculo"]').change(function() {
      if ($(this).val() === 'factor') {
        $('#seccion_factor').fadeIn();
        $('#seccion_porcentaje').hide();
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