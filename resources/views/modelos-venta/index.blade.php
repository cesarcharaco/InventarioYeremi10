@extends('layouts.app')

@section('title') Gestión de Modelos de Venta @endsection

@section('content')
@push('css')
<style>
    #sampleTable { font-size: 0.85rem; }
    #sampleTable td, #sampleTable th { padding: 0.6rem; vertical-align: middle; }
    .badge { font-size: 85%; }
</style>
@endpush
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-calculator"></i> Modelos de Venta</h1>
      <p>Configuración de tasas y factores de cálculo</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item">Modelos de Venta</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-title-w-btn">
          <h3 class="title">Modelos Configurados</h3>
          <a class="btn btn-primary icon-btn" href="{{ route('modelos-venta.create') }}">
            <i class="fa fa-plus"></i> Nuevo Modelo
          </a>
        </div>
        
        <div class="tile-body">
          @include('layouts.partials.flash-messages')
          
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="sampleTable">
              <thead>
                <tr class="table-dark">
                  <th>Modelo</th>
                  <th>Tasa BCV</th>
                  <th>Tasa Binance</th>
                  <th>Cálculo BCV</th>
                  <th>Cálculo USDT</th>
                  <th class="text-center">Insumos</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                @foreach($modelos as $modelo)
                <tr>
                  <td><strong>{{ $modelo->modelo }}</strong></td>
                  <td>{{ number_format($modelo->tasa_bcv, 2) }} Bs.</td>
                  <td>{{ number_format($modelo->tasa_binance, 2) }} Bs.</td>
                  <td>
                    @if($modelo->factor_bcv)
                      <span class="badge badge-info">F. BCV: {{ $modelo->factor_bcv }}</span>
                    @elseif($modelo->porcentaje_extra)
                      <span class="badge badge-success">+{{ $modelo->porcentaje_extra * 100 }}%</span>
                    @endif
                  </td>
                  <td>
                    @if($modelo->factor_usdt)
                      <span class="badge badge-warning">F. USDT: {{ $modelo->factor_usdt }}</span>
                    @elseif($modelo->porcentaje_extra)
                      <span class="badge badge-success">+{{ $modelo->porcentaje_extra * 100 }}%</span>
                    @endif
                  </td>

                  <td class="text-center">
                    <span class="badge badge-pill badge-secondary">
                        {{ $modelo->insumos_count }}
                    </span>
                  </td>
                  <td>
                    <div class="btn-group">
                      <a class="btn btn-warning btn-sm" href="{{ route('modelos-venta.edit', $modelo->id) }}">
                        <i class="fa fa-edit"></i>
                      </a>
                      <button class="btn btn-danger btn-sm" type="button" onclick="eliminarModelo({{ $modelo->id }})" data-toggle="modal" data-target="#modalEliminar">
                        <i class="fa fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="mt-3">
              {{ $modelos->links() }}
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

{{-- Modal de Eliminación --}}
<div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      {!! Form::open(['route' => ['modelos-venta.destroy', 0], 'method' => 'DELETE', 'id' => 'form-eliminar']) !!}
      <div class="modal-header">
        <h5 class="modal-title">Confirmar Eliminación</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body text-center">
        <p>¿Está seguro de eliminar este modelo? Los cálculos asociados podrían verse afectados.</p>
        <input type="hidden" name="id" id="id_val">
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-danger">Eliminar</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
      {!! Form::close() !!}
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  function eliminarModelo(id) {
    $("#id_val").val(id);
    $('#form-eliminar').attr('action', "{{ url('modelos-venta') }}/" + id);
  }
</script>
@endsection