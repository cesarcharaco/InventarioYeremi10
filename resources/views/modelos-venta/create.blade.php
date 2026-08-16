@extends('layouts.app')

@section('title') Nuevo Modelo de Venta @endsection

@section('content')
<main class="app-content">
  {{-- 1. VERIFICACIÓN DE PERMISO ESTRICTO (SOLO SUPERADMIN) --}}
  @cannot('crear-configuracion')
    <div class="tile text-center">
        <h1 class="text-danger"><i class="fa fa-lock"></i> Acceso Restringido</h1>
        <p>No tienes permisos para configurar modelos de cálculo o tasas de cambio.</p>
        <a href="{{ route('modelos-venta.index') }}" class="btn btn-primary">Volver al listado</a>
    </div>
  @else
    {{-- 2. ESTRUCTURA ORIGINAL PARA USUARIOS AUTORIZADOS --}}
    <div class="app-title">
      <div>
        <h1><i class="fa fa-edit"></i> Registrar Modelo</h1>
        <p>Configure las tasas y el método de cálculo para el inventario.</p>
      </div>
      <ul class="app-breadcrumb breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
        <li class="breadcrumb-item"><a href="{{ route('modelos-venta.index') }}">Modelos de Venta</a></li>
        <li class="breadcrumb-item">Nuevo</li>
      </ul>
    </div>

    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="tile">
          <h3 class="tile-title border-bottom pb-2">Datos del Modelo</h3>
          <div class="tile-body">
            @include('layouts.partials.flash-messages')

            <form action="{{ route('modelos-venta.store') }}" method="POST">
              @csrf
              
              <div class="form-group">
                <label class="control-label"><strong>Nombre del Modelo</strong> <b class="text-danger">*</b></label>
                <input class="form-control form-control-sm @error('modelo') is-invalid @enderror" type="text" name="modelo" placeholder="Ej: General, Bajo Costo..." value="{{ old('modelo') }}" required>
                @error('modelo') <span class="invalid-feedback">{{ $message }}</span> @enderror
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="control-label"><strong>Tasa BCV</strong></label>
                    <input class="form-control form-control-sm" type="number" step="0.01" name="tasa_bcv" placeholder="0.00" value="{{ old('tasa_bcv') }}">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="control-label"><strong>Tasa Binance (USDT)</strong></label>
                    <input class="form-control form-control-sm" type="number" step="0.01" name="tasa_binance" placeholder="0.00" value="{{ old('tasa_binance') }}">
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

              {{-- Sección Dinámica: Factor --}}
              <div id="seccion_factor" class="card bg-light mt-3">
                <div class="card-body py-2">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label><strong>Factor BCV (Divisor)</strong></label>
                        <input class="form-control form-control-sm" type="number" step="0.01" name="factor_bcv" placeholder="Ej: 0.70" value="{{ old('factor_bcv') }}">
                        <small class="text-muted">Para cálculo de Bs. y Dólar BCV</small>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <label><strong>Factor USDT (Divisor)</strong></label>
                        <input class="form-control form-control-sm" type="number" step="0.01" name="factor_usdt" placeholder="Ej: 0.60" value="{{ old('factor_usdt') }}">
                        <small class="text-muted">Para precio en Binance/Efectivo</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              {{-- Sección Dinámica: Porcentaje --}}
              <div id="seccion_porcentaje" class="card bg-light mt-3" style="display:none;">
                <div class="card-body py-2">
                  <label><strong>Porcentaje Extra (Margen Fijo) %</strong></label>
                  <input class="form-control form-control-sm campo-calculo" type="number" step="0.01" id="porcentaje_extra" name="porcentaje_extra" placeholder="Ej: 5 para 5%" value="{{ old('porcentaje_extra') }}">
                  <small class="text-muted">Ingrese el porcentaje directo (ejemplo: 5 representa 5% de ganancia).</small>
                </div>
              </div>
              {{-- SECCIÓN NUEVA: SIMULADOR DE PRUEBAS EN TIEMPO REAL --}}
              <div class="card border-info mt-4">
                <div class="card-header bg-info text-white py-2">
                  <i class="fa fa-calculator"></i> <strong>Simulador de Pruebas en Tiempo Real</strong>
                </div>
                <div class="card-body bg-light">
                  <div class="row align-items-center">
                    <div class="col-md-5">
                      <div class="form-group mb-md-0">
                        <label for="costo_prueba"><strong>Costo Base del Producto ($)</strong></label>
                        <input type="number" step="0.01" class="form-control form-control-sm border-info" id="costo_prueba" placeholder="Ej: 10.00">
                      </div>
                    </div>
                    <div class="col-md-7 border-left">
                      <div class="p-2">
                        <small class="text-muted d-block mb-1">Resultados estimados según los valores ingresados:</small>
                        
                        {{-- Vista cuando es por FACTOR --}}
                        <div id="resultado_factor_box">
                          <div class="d-flex justify-content-between mb-1">
                            <span>Precio BCV ($):</span>
                            <strong id="res_bcv_usd" class="text-primary">$0.00</strong>
                          </div>
                          <div class="d-flex justify-content-between mb-1">
                            <span>Precio USDT/Efectivo ($):</span>
                            <strong id="res_usdt_usd" class="text-success">$0.00</strong>
                          </div>
                          <div class="d-flex justify-content-between">
                            <span>Precio Final en Bolívares (Bs.):</span>
                            <strong id="res_precio_bs" class="text-dark">0.00 Bs.</strong>
                          </div>
                        </div>

                        {{-- Vista cuando es por PORCENTAJE --}}
                        <div id="resultado_porcentaje_box" style="display: none;">
                          <div class="d-flex justify-content-between mb-1">
                            <span>Precio Venta ($):</span>
                            <strong id="res_porcentaje_usd" class="text-success">$0.00</strong>
                          </div>
                          <div class="d-flex justify-content-between">
                            <span>Precio Final en Bolívares (Tasa BCV):</span>
                            <strong id="res_porcentaje_bs" class="text-dark">0.00 Bs.</strong>
                          </div>
                        </div>

                      </div>
                    </div>
                  </div>
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
  @endcannot
</main>
@endsection

@section('scripts')
<script>
  $(document).ready(function() {

    // Helper para convertir string a número aceptando coma o punto
    function parseNum(selector) {
      let val = $(selector).val();
      if (!val) return 0;
      val = val.toString().replace(',', '.');
      const num = parseFloat(val);
      return isNaN(num) ? 0 : num;
    }

    // Función de cálculo
    function calcularSimulacion() {
      const costo = parseNum('#costo_prueba');
      const tasaBcv = parseNum('input[name="tasa_bcv"]');
      const metodo = $('input[name="metodo_calculo"]:checked').val();

      if (costo <= 0) {
        $('#res_bcv_usd, #res_usdt_usd, #res_porcentaje_usd').text('$0.00');
        $('#res_precio_bs, #res_porcentaje_bs').text('0,00 Bs.');
        return;
      }

      if (metodo === 'factor') {
        const factorBcv = parseNum('input[name="factor_bcv"]');
        const factorUsdt = parseNum('input[name="factor_usdt"]');

        const bcvUsd = factorBcv > 0 ? (costo / factorBcv) : 0;
        const usdtUsd = factorUsdt > 0 ? (costo / factorUsdt) : 0;
        const precioBs = bcvUsd * tasaBcv;

        $('#res_bcv_usd').text('$' + bcvUsd.toFixed(2));
        $('#res_usdt_usd').text('$' + usdtUsd.toFixed(2));
        $('#res_precio_bs').text(
          precioBs > 0 
            ? precioBs.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' Bs.' 
            : '0,00 Bs.'
        );

      } else {
        // CORRECCIÓN: Se divide entre 100 para tratar números enteros como porcentaje (ej: 5 -> 0.05)
        const pctInput = parseNum('input[name="porcentaje_extra"]');
        const pctExtra = pctInput > 0 ? (pctInput / 100) : 0;

        const precioUsd = costo + (costo * pctExtra);
        const precioBs = tasaBcv > 0 ? (precioUsd * tasaBcv) : 0;

        $('#res_porcentaje_usd').text('$' + precioUsd.toFixed(2));
        $('#res_porcentaje_bs').text(
          precioBs > 0 
            ? precioBs.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' Bs.' 
            : '0,00 Bs.'
        );
      }
    }

    // Escuchar eventos dinámicos
    $(document).on('input keyup change', 'input[name="tasa_bcv"], input[name="tasa_binance"], input[name="factor_bcv"], input[name="factor_usdt"], input[name="porcentaje_extra"], #costo_prueba', function() {
      calcularSimulacion();
    });

    // Evento de cambio de método de cálculo
    $('input[name="metodo_calculo"]').change(function() {
      if ($(this).val() === 'factor') {
        $('#seccion_factor').fadeIn();
        $('#seccion_porcentaje').hide();
        $('#resultado_factor_box').show();
        $('#resultado_porcentaje_box').hide();
        $('input[name="porcentaje_extra"]').val('');
      } else {
        $('#seccion_factor').hide();
        $('#seccion_porcentaje').fadeIn();
        $('#resultado_factor_box').hide();
        $('#resultado_porcentaje_box').show();
        $('input[name="factor_bcv"], input[name="factor_usdt"]').val('');
      }
      calcularSimulacion();
    });

  });
</script>
@endsection