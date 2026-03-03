@extends('layouts.app')
@section('title') Ofertas Especiales @endsection
@section('content')
@include('layouts.partials.flash-messages')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-percentage"></i> Ofertas Especiales</h1>
      <p>Sistema Administrativo | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('config-ofertas.index') }}">Ofertas</a></li>
      <li class="breadcrumb-item">Listado</li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head" id="indicators">Gestión de Ofertas
            {{-- Solo Admin puede ver el botón de nueva oferta --}}
            @can('gestionar-ofertas')
            <button class="btn btn-primary icon-btn pull-right" data-toggle="modal" data-target="#modalActivarOferta">
              <i class="fa fa-plus"></i> Activar Oferta
            </button>
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
              <table class="table table-hover table-bordered" id="sampleTable" style="width: 100%">
                <thead>
                  <tr>
                    <th>Fecha</th>
                    <th>Local</th>
                    <th>Motivo</th>
                    <th>Criterio de Fin</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($ofertas as $o)
                  <tr>
                    <td>{{ $o->created_at->format('d/m/Y h:i A') }}</td>
                    <td>{{ $o->local->nombre ?? 'N/A' }}</td>
                    <td>{{ $o->motivo }}</td>
                    <td>
                        <span class="badge badge-info">{{ strtoupper(str_replace('_', ' ', $o->criterio_fin)) }}</span>
                    </td>
                    <td>
                      @if($o->estado)
                        <span class="badge badge-success">ACTIVA</span>
                      @else
                        <span class="badge badge-secondary">FINALIZADA</span>
                      @endif
                    </td>
                    <td>
                      @can('gestionar-ofertas')
                        @if($o->estado)
                          <button class="btn btn-danger btn-sm" 
                                  onclick="confirmarDesactivacion('{{ $o->id }}')"
                                  data-toggle="tooltip" 
                                  title="Finalizar Oferta">
                            <i class="fa fa-power-off"></i>
                          </button>
                        @endif
                      @endcan
                      @cannot('gestionar-ofertas')
                        <span class="badge badge-light">Lectura</span>
                      @endcannot
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

{{-- Modal Activar Oferta --}}
<div class="modal fade" id="modalActivarOferta" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-plus"></i> Nueva Oferta</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            {!! Form::open(['route' => 'config-ofertas.store', 'method' => 'POST']) !!}
            <div class="modal-body">
                <div class="form-group">
                    <label>Local</label>
                    <select name="id_local" class="form-control" required>
                        @foreach($locales as $l)
                            <option value="{{ $l->id }}">{{ $l->id }}---{{ $l->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Motivo</label>
                    <input type="text" name="motivo" class="form-control" placeholder="Ej: Especial Navideño" required>
                </div>
                <div class="form-group">
                    <label>Criterio de Finalización</label>
                    <select name="criterio_fin" class="form-control" required>
                        <option value="manual">Manual (Hasta apagarla)</option>
                        <option value="cierre_caja">Al cerrar la caja</option>
                        <option value="fin_turno">Al terminar el turno</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Activar</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>

{{-- Formulario oculto para desactivar --}}
<form id="form-desactivar-oferta" action="" method="POST" style="display:none;">
    @csrf
    @method('PATCH')
</form>

@endsection

@section('scripts')
<script type="text/javascript">
  function confirmarDesactivacion(id) {
      Swal.fire({
          title: '¿Finalizar oferta?',
          text: "Esta acción desactivará los descuentos para este local de inmediato.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: '<i class="fa fa-power-off"></i> Sí, finalizar',
          cancelButtonText: 'Cancelar',
          reverseButtons: true
      }).then((result) => {
          if (result.isConfirmed) {
              let form = $('#form-desactivar-oferta');
              form.attr('action', "{{ url('config-ofertas') }}/" + id + "/desactivar");
              form.submit();
          }
      });
  }
  $(document).ready(function() {
    if ( ! $.fn.DataTable.isDataTable( '#sampleTable' ) ) {
        $('#sampleTable').DataTable({
          "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
          "responsive": true,
          "autoWidth": false,
          "order": [[ 0, "desc" ]] // Mostrar las más nuevas primero
        });
    }
  });
</script>
@endsection