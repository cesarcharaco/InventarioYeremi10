@extends('layouts.app')
@section('title') Registro de Insumos @endsection
@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-edit"></i> Registro de Insumo</h1>
      <p>Gestión de Inventario Centralizado</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ route('insumos.index') }}">Insumos</a></li>
      <li class="breadcrumb-item">Registro</li>
    </ul>
  </div>

  <div class="tile mb-4">
    <form action="{{route('insumos.store')}}" method="POST" id="form-insumo" data-parsley-validate>
      @csrf
      <div class="tile-body">
        <h4><i class="fa fa-info-circle"></i> Datos del Producto</h4>
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
                <label>Categoría <b class="text-danger">*</b></label>
                <select class="form-control" name="categoria_id" required>
                    <option value="">-- Seleccione Categoría --</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->categoria }}</option>
                    @endforeach
                </select>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label>Producto <b class="text-danger">*</b></label>
              <input class="form-control" type="text" name="producto" required value="{{ old('producto') }}">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Descripción</label>
              <input class="form-control" name="description" value="{{ old('descripcion') }}">
            </div>
          </div>
        </div>

        {{-- NUEVA SECCIÓN: Límites de Stock Globales --}}
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                  <label>Stock Mínimo <b class="text-danger">*</b></label>
                  <input class="form-control" type="number" name="stock_min" required value="0" min="0">
                  <small class="form-text text-muted">Alerta de reposición</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                  <label>Stock Máximo <b class="text-danger">*</b></label>
                  <input class="form-control" type="number" name="stock_max" required value="0" min="0">
                </div>
            </div>
        </div>

        <hr>
        <h4><i class="fa fa-calculator"></i> Modelo de Venta y Costos</h4>
        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label>Seleccionar Modelo de Venta <b class="text-danger">*</b></label>
              <select class="form-control" name="modelo_venta_id" id="modelo_venta_id" required>
                <option value="">-- Seleccione un modelo --</option>
                @foreach($modelos as $m)
                    <option value="{{ $m->id }}" 
                            data-factor-bcv="{{ $m->factor_bcv }}" 
                            data-factor-usdt="{{ $m->factor_usdt }}" 
                            data-extra="{{ $m->porcentaje_extra }}"
                            data-bcv="{{ $m->tasa_bcv }}"
                            data-binance="{{ $m->tasa_binance }}">
                        {{ $m->modelo }}
                    </option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group">
              <label>Costo del Insumo (USD $) <b class="text-danger">*</b></label>
              <input class="form-control" type="number" step="0.01" name="costo" id="costo_input" placeholder="0.00" required>
            </div>
          </div>
        </div>

        <div class="row bg-light py-3 border rounded shadow-sm mx-1">
          <div class="col-md-4">
            <div class="form-group">
              <label class="text-primary font-weight-bold">Venta USD ($)</label>
              <input class="form-control font-weight-bold" type="text" name="precio_venta_usd" id="res_usd" readonly style="background-color: #e9ecef;">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label class="text-success font-weight-bold">Venta BS (BCV)</label>
              <input class="form-control" type="text" name="precio_venta_bs" id="res_bs" readonly style="background-color: #e9ecef;">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label class="text-warning font-weight-bold">Venta USDT (Binance)</label>
              <input class="form-control" type="text" name="precio_venta_usdt" id="res_usdt" readonly style="background-color: #e9ecef;">
            </div>
          </div>
        </div>

        <hr>
        <h4><i class="fa fa-map-marker"></i> Distribución Inicial de Stock</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Ubicación (Local/Depósito)</th>
                        <th>Tipo</th>
                        <th width="200">Cantidad Inicial</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($locales as $local)
                    <tr>
                        <td>
                            <strong>{{ $local->nombre }}</strong>
                            <input type="hidden" name="id_local[]" value="{{ $local->id }}">
                        </td>
                        <td>
                            <span class="badge {{ $local->tipo == 'DEPOSITO' ? 'badge-primary' : 'badge-info' }}">
                                {{ $local->tipo }}
                            </span>
                        </td>
                        <td>
                            <input class="form-control" type="number" name="cantidad[{{ $local->id }}]" required value="0" min="0">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
      </div>

      <div class="tile-footer text-right">
        <button class="btn btn-primary" type="submit"><i class="fa fa-check-circle"></i> Guardar Insumo</button>
        <a class="btn btn-secondary" href="{{ route('insumos.index') }}"><i class="fa fa-times-circle"></i> Cancelar</a>
      </div>
    </form>
  </div>
</main>
@endsection

@section('scripts')
{{-- El script de cálculo se mantiene igual, ya que la lógica de costos no cambió --}}
<script>
    (function() {
        "use strict";
        $(document).ready(function() {
            const $selModelo = $('#modelo_venta_id');
            const $inCosto = $('#costo_input');
            const $resUsd = $('#res_usd');
            const $resBs = $('#res_bs');
            const $resUsdt = $('#res_usdt');

            function ejecutarCalculo() {
                const costo = parseFloat($inCosto.val());
                const $opcion = $selModelo.find('option:selected');
                const modeloId = $selModelo.val();

                if (isNaN(costo) || !modeloId || costo <= 0) {
                    $resUsd.val(''); $resBs.val(''); $resUsdt.val('');
                    return;
                }

                const f_bcv = parseFloat($opcion.data('factor-bcv')) || 0;
                const f_usdt = parseFloat($opcion.data('factor-usdt')) || 0;
                const extra = parseFloat($opcion.data('extra')) || 0;
                const t_bcv = parseFloat($opcion.data('bcv')) || 0;
                const t_binance = parseFloat($opcion.data('binance')) || 0;

                let vUsdBcv = 0;
                let vUsdt = 0;

                if (f_bcv > 0) {
                    const diferencial = (t_bcv > 0) ? (t_binance / t_bcv) : 1;
                    vUsdBcv = (diferencial / f_bcv) * costo;
                } else if (extra > 0) {
                    vUsdBcv = costo * (1 + extra);
                }

                if (f_usdt > 0) {
                    vUsdt = costo / f_usdt;
                } else if (extra > 0) {
                    vUsdt = costo * (1 + extra);
                } else {
                    vUsdt = costo;
                }

                const vBs = vUsdBcv * t_bcv;

                $resUsd.val(vUsdBcv.toFixed(2));
                $resUsdt.val(vUsdt.toFixed(1));
                $resBs.val(vBs.toFixed(2));
            }

            $inCosto.on('input keyup change', ejecutarCalculo);
            $selModelo.on('change', ejecutarCalculo);
        });
    })();
</script>
@endsection