@extends('layouts.app')
@section('title') Productos al Mayor @endsection

@section('content')
@include('layouts.partials.flash-messages')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-tags"></i> Productos al Mayor</h1>
      <p>Sistema Administrativo | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="#">Mayorista</a></li>
      <li class="breadcrumb-item"><a href="#">Listado</a></li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-md-12">
        <div class="tile">
          <div class="tile-body">
            <div class="table-responsive">
              <table class="table table-hover table-bordered" id="gestion" style="width:100%">
                <thead>
                  <tr>
                    <th>Nombre</th>
                    <th>Proveedor</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>Monto Mínimo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($listas as $lista)
                      <tr>
                          <td>{{ $lista->nombre }}</td>
                          <td>{{ $lista->proveedor }}</td>
                          <td>{{ $lista->fecha_inicio }}</td>
                          <td>{{ $lista->fecha_fin }}</td>
                          <td>{{ number_format($lista->monto_minimo, 2) }}</td>
                          <td>{{ $lista->estado }}</td>
                          <td>
                          	@if($lista->estado === 'activo')
                              {{-- Ahora siempre permitimos el acceso, sin bloquear el botón --}}
                              <a href="{{ route('insumos-mayores.editar', $lista->id) }}" class="btn btn-warning btn-sm">
                                  <i class="fas fa-edit"></i> Editar
                              </a>
                              
                              @if($lista->pedidos_count > 0)
                                  <span class="badge badge-info" title="Esta lista tiene pedidos activos. Solo podrás editar datos informativos.">
                                      <i class="fas fa-info-circle"></i> Info. Protegida
                                  </span>
                              @endif

                              <button type="button" class="btn btn-danger btn-sm" 
                                      data-toggle="modal" 
                                      data-target="#modalAnular"
                                      data-id="{{ $lista->id }}"
                                      data-nombre="{{ $lista->nombre }}"
                                      data-pedidos="{{ $lista->pedidos_count }}">
                                  <i class="fas fa-trash"></i> Anular
                              </button>
                              @else
                                  {{-- Mostrar un badge indicativo --}}
                                  <span class="badge badge-secondary">Sin acciones disponibles</span>
                              @endif
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
<div class="modal fade" id="modalAnular" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Confirmar Acción</h5>
      </div>
      <div class="modal-body">
        <p>¿Está seguro de anular la oferta: <strong id="nombreOferta"></strong>?</p>
        <div id="mensajeAlerta"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <form id="formAnular" action="" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Confirmar Anulación</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
  $(document).ready(function() {
    if ( ! $.fn.DataTable.isDataTable( '#gestion' ) ) {
        $('#gestion').DataTable({
            "language": { 
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" 
            },
            "responsive": true,
            "autoWidth": false,
            // Importante: al añadir columnas, asegúrate de que el índice de orden sea correcto.
            // Si quieres ordenar por la columna 0 (Nombre), está bien.
            "order": [[0, 'asc']]
        });
    }
  });
  $('#modalAnular').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget); // Botón que disparó el modal
    var id = button.data('id');
    var nombre = button.data('nombre');
    var pedidos = button.data('pedidos');
    
    var modal = $(this);
    modal.find('#nombreOferta').text(nombre);
    
    // Ajustar el formulario
    var action = "{{ route('insumos-mayores.anular', ':id') }}";
    modal.find('#formAnular').attr('action', action.replace(':id', id));
    
    // Ajustar mensaje de alerta
    if (pedidos > 0) {
        modal.find('#mensajeAlerta').html('<div class="alert alert-warning">Esta oferta tiene ' + pedidos + ' pedido(s) activo(s). Se cancelarán automáticamente.</div>');
    } else {
        modal.find('#mensajeAlerta').html('<div class="alert alert-danger">Esta oferta no tiene pedidos asociados. Se eliminará permanentemente.</div>');
    }
});
</script>
@endsection