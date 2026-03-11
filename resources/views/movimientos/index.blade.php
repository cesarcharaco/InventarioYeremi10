@extends('layouts.app')
@section('title') Movimientos de Caja @endsection

@section('content')
@include('layouts.partials.flash-messages')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-exchange"></i> Movimientos de Caja</h1>
      <p>Gestión de Ingresos y Egresos Operativos | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('movimientos.index') }}">Movimientos</a></li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head" id="indicators">Historial de Movimientos
            @can('movimientos_caja')
            <a class="btn btn-primary icon-btn pull-right" href="{{ route('movimientos.create') }}">
              <i class="fa fa-plus"></i> Registrar Movimiento
            </a>
            @endcan
          </h2>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="tile">
          <div class="tile-body">
            <div class="table-responsive">
              <table class="table table-hover table-bordered" id="sampleTable">
                <thead>
                  <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Categoría</th>
                    <th>Efectivo USD</th>
                    <th>Efectivo Bs.</th>
                    <th>Local / Caja</th>
                    <th>Usuario</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($movimientos as $mov)
                  <tr>
                    <td>{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                      @if($mov->tipo == 'ingreso')
                        <span class="badge badge-success"><i class="fa fa-arrow-up"></i> Ingreso</span>
                      @else
                        <span class="badge badge-danger"><i class="fa fa-arrow-down"></i> Egreso</span>
                      @endif
                    </td>
                    <td><strong>{{ strtoupper($mov->categoria) }}</strong></td>
                    <td>
                        @if($mov->efectivo_usd > 0)
                            <span class="text-primary"><strong>{{ number_format($mov->efectivo_usd, 2) }} $</strong></span><br>
                        @endif
                        @if($mov->efectivo_bs > 0)
                            <span class="text-info"><strong>{{ number_format($mov->efectivo_bs, 2) }} Bs</strong></span>
                        @endif
                    </td>
                    <td>
                      <small>
                        {{ $mov->caja->local->nombre ?? 'N/A' }} <br>
                        <span class="text-muted">Caja #{{ $mov->id_caja }}</span>
                      </small>
                    </td>
                    <td>{{ $mov->usuario->name }}</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-info btn-sm" onclick="verObservacion('{{ $mov->observacion }}')" title="Ver Observación">
                                <i class="fa fa-eye"></i>
                            </button>
                            @can('movimientos_caja')
                            <a href="javascript:;" class="btn btn-danger btn-sm" onclick="eliminarMovimiento('{{ $mov->id }}')">
                                <i class="fa fa-trash"></i>
                            </a>
                            @endcan
                            @if($mov->caja->estado == 'abierta')
                                <a href="{{ route('movimientos.edit', $mov->id) }}" class="btn btn-info btn-sm">
                                    <i class="fa fa-edit"></i>
                                </a>
                            @endif
                        </div>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

{{-- Modal Eliminar Movimiento --}}
<div class="modal fade" id="modalEliminarMovimiento">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-trash"></i> Eliminar Registro</h5>
        <button class="close" type="button" data-dismiss="modal"><span>×</span></button>
      </div>
      {!! Form::open(['url' => '', 'method' => 'DELETE', 'id' => 'form-eliminar-movimiento']) !!}
        <div class="modal-body">
          <p>¿Está seguro que desea eliminar este movimiento de caja?</p>
          <p class="text-warning small"><i class="fa fa-warning"></i> Esta acción afectará el balance del cierre de caja actual.</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-danger" type="submit">Confirmar Eliminación</button>
          <button class="btn btn-secondary" type="button" data-dismiss="modal">Cerrar</button>
        </div>
      {!! Form::close() !!}
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
  function eliminarMovimiento(id) {
      let url = "{{ url('movimientos-caja') }}/" + id + "/destroy";
      $('#form-eliminar-movimiento').attr('action', url);
      $('#modalEliminarMovimiento').modal('show');
  }

  function verObservacion(texto) {
      swal({
          title: "Observación del Movimiento",
          text: texto,
          icon: "info",
          button: "Cerrar",
      });
  }

  $(document).ready(function() {
    if ( ! $.fn.DataTable.isDataTable( '#sampleTable' ) ) {
        $('#sampleTable').DataTable({
          "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
          "responsive": true,
          "order": [[ 0, "desc" ]] // Mostrar los más recientes primero
        });
    }
  });
</script>
@endsection