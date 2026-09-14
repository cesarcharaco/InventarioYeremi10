@extends('layouts.app')

@section('title') Notificaciones @endsection

@section('content')
@include('layouts.partials.flash-messages')

<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-bell"></i> Notificaciones</h1>
      <p>Historial de alertas del sistema | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="#">Notificaciones</a></li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head" id="indicators">Historial
            @if(auth()->user()->unreadNotifications->count() > 0)
              <form action="{{ route('notifications.markAllRead') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm pull-right">
                  <i class="fa fa-check-double"></i> Marcar todas como leídas
                </button>
              </form>
            @endif
          </h2>
        </div>
      </div>
    </div>

    {{-- SECCIÓN DE FILTROS SUPERIORES (Desde, Hasta, Estado) --}}
    <div class="tile mb-3 p-3 bg-light border">
      <div class="row align-items-end">
        <div class="col-md-3 form-group mb-md-0">
          <label for="desde" class="font-weight-bold small">Desde:</label>
          <input type="date" id="desde" class="form-control form-control-sm">
        </div>
        <div class="col-md-3 form-group mb-md-0">
          <label for="hasta" class="font-weight-bold small">Hasta:</label>
          <input type="date" id="hasta" class="form-control form-control-sm">
        </div>
        <div class="col-md-3 form-group mb-md-0">
          <label for="estado" class="font-weight-bold small">Estado:</label>
          <select id="estado" class="form-control form-control-sm">
            <option value="">Todas</option>
            <option value="no_leidas">No leídas</option>
            <option value="leidas">Leídas</option>
          </select>
        </div>
        <div class="col-md-3 form-group mb-md-0 d-flex">
          <a href="{{ route('notifications.index') }}" class="btn btn-secondary btn-sm btn-block" title="Limpiar filtros">
            <i class="fa fa-refresh"></i> Limpiar Filtros
          </a>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="tile">
          <div class="tile-body">
            <div class="table-responsive">
              <table class="table table-hover table-bordered" id="tabla-notificaciones" style="width:100%">
                <thead>
                  <tr class="bg-primary text-white">
                    <th width="50" class="text-center">Estado</th>
                    <th>Notificación</th>
                    <th>Mensaje</th>
                    <th>Fecha</th>
                    <th width="100" class="text-center">Acción</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Los datos se cargan dinámicamente vía AJAX por DataTables -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script type="text/javascript">
  $(document).ready(function () {
    
    // Traducción al español igual a insumos
    var lenguajeEspanol = {
        "decimal": "",
        "emptyTable": "No hay información disponible",
        "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
        "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
        "infoFiltered": "(Filtrado de _MAX_ entradas totales)",
        "infoPostFix": "",
        "thousands": ",",
        "lengthMenu": "Mostrar _MENU_ entradas",
        "loadingRecords": "Cargando...",
        "processing": "Procesando...",
        "search": "Buscar:",
        "zeroRecords": "Sin resultados encontrados",
        "paginate": {
            "first": "Primero",
            "last": "Último",
            "next": "Siguiente",
            "previous": "Anterior"
        }
    };

    // Inicialización del DataTable del lado del servidor
    var table = $('#tabla-notificaciones').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "{{ route('notifications.data') }}",
            "type": "GET",
            "data": function (d) {
                d.desde = $('#desde').val();
                d.hasta = $('#hasta').val();
                d.estado = $('#estado').val();
            }
        },
        "columns": [
            { "data": "estado", "orderable": false, "searchable": false, "className": "text-center align-middle" },
            { "data": "titulo", "className": "align-middle" },
            { "data": "mensaje", "className": "align-middle" },
            { "data": "fecha", "searchable": false, "className": "align-middle" }, // <--- Añadimos searchable: false aquí
            { "data": "acciones", "orderable": false, "searchable": false, "className": "text-center align-middle" }
        ],
        "language": lenguajeEspanol,
        "responsive": true,
        "autoWidth": false,
        "pageLength": 15,
        "searchDelay": 500,
        "order": [[3, 'desc']] // Ordenado por fecha descendente por defecto
    });

    // Eventos para actualizar la tabla instantáneamente al modificar los filtros sin perder paginación
    $('#desde, #hasta').on('change', function () {
        table.ajax.reload();
    });

    $('#estado').on('change', function () {
        table.ajax.reload();
    });
  });
</script>
@endsection