@extends('layouts.app')

@section('title') Auditoría del Sistema @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-history"></i> SAYER</h1>
      <p>Sistema Administrativo | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item">SAYER</li>
      <li class="breadcrumb-item">Auditoría del Sistema</li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head" id="indicators">
            Historial de Auditoría del Sistema
          </h2>
        </div>
        <div class="basic-tb-hd text-center">
          @include('layouts.partials.flash-messages')
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="tile">
          <div class="tile-body">
            <div class="table-responsive">
              <table class="table table-hover table-bordered" id="tabla-auditoria" style="width:100%">
                <thead>
                  <tr class="bg-primary text-white">
                    <th class="text-center">Acción</th>
                    <th>Tabla Afectada</th>
                    <th>Usuario</th>
                    <th>Fecha y Hora</th>
                    <th class="text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Los datos se cargan dinámicamente vía Ajax -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

{{-- MODAL DETALLES DE AUDITORÍA --}}
<div class="modal fade" id="detallesAuditoriaModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fa fa-eye"></i> Detalles del Registro de Auditoría</h5>
        <button class="close text-white" type="button" data-dismiss="modal"><span>×</span></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label class="font-weight-bold text-danger">Valores Anteriores:</label>
              <pre id="det_valores_anteriores" class="bg-light p-3 border rounded" style="white-space: pre-wrap; word-break: break-all; max-height: 300px; overflow-y: auto;"></pre>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label class="font-weight-bold text-success">Valores Nuevos:</label>
              <pre id="det_valores_nuevos" class="bg-light p-3 border rounded" style="white-space: pre-wrap; word-break: break-all; max-height: 300px; overflow-y: auto;"></pre>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
  $(document).ready(function () {
    var lenguajeEspanol = {
        "decimal": "",
        "emptyTable": "No hay información de auditoría disponible",
        "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
        "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
        "infoFiltered": "(Filtrado de _MAX_ entradas totales)",
        "search": "Buscar:",
        "zeroRecords": "Sin resultados encontrados",
        "paginate": {
            "first": "Primero",
            "last": "Último",
            "next": "Siguiente",
            "previous": "Anterior"
        }
    };

    $('#tabla-auditoria').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "{{ route('auditoria.data') }}",
            "type": "GET"
        },
        "columns": [
            { "data": "accion", "className": "text-center" },
            { "data": "tabla_afectada" },
            { "data": "nombre_usuario" },
            { "data": "ejecutado_en" },
            { "data": "acciones", "className": "text-center", "orderable": false, "searchable": false }
        ],
        "language": lenguajeEspanol,
        "responsive": true,
        "autoWidth": false,
        "pageLength": 10,
        "searchDelay": 500,
        "order": [[3, 'desc']] // Ordenar por ejecutado_en más reciente
    });
  });

  function detallesAuditoria(data) {
    // Formatear JSON si existe, de lo contrario mostrar texto plano o 'N/D'
    let anteriores = data.anteriores ? JSON.stringify(JSON.parse(data.anteriores), null, 2) : 'Sin registros anteriores (INSERT)';
    let nuevos = data.nuevos ? JSON.stringify(JSON.parse(data.nuevos), null, 2) : 'Sin registros nuevos (DELETE)';

    $("#det_valores_anteriores").text(anteriores);
    $("#det_valores_nuevos").text(nuevos);
  }
</script>
@endsection