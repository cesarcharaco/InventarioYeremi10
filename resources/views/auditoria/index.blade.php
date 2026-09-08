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

    {{-- SECCIÓN DE FILTROS POR FECHA --}}
    <div class="row mb-4 p-3 bg-light border rounded">
      <div class="col-md-4">
        <div class="form-group mb-0">
          <label for="fecha_inicio" class="font-weight-bold">Fecha Desde:</label>
          <input type="date" id="fecha_inicio" class="form-control">
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-group mb-0">
          <label for="fecha_fin" class="font-weight-bold">Fecha Hasta:</label>
          <input type="date" id="fecha_fin" class="form-control">
        </div>
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <button type="button" id="btnFiltrar" class="btn btn-primary mr-2">
          <i class="fa fa-filter"></i> Filtrar
        </button>
        <button type="button" id="btnLimpiar" class="btn btn-secondary">
          <i class="fa fa-undo"></i> Limpiar
        </button>
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
        <h5 class="modal-title"><i class="fa fa-exchange-alt"></i> Comparativa de Cambios en el Registro</h5>
        <button class="close text-white" type="button" data-dismiss="modal"><span>×</span></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-bordered table-striped" id="tabla-comparativa-audit" style="width: 100%;">
            <thead>
              <tr class="bg-dark text-white">
                <th>Campo Modificado</th>
                <th class="text-danger">Valor Anterior</th>
                <th class="text-success">Valor Nuevo</th>
              </tr>
            </thead>
            <tbody>
              <!-- Se llena dinámicamente con JavaScript -->
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cerrar</button>
      </div>
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
        "emptyTable": "No hay información de auditoría disponible para este rango de fechas",
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

    var table = $('#tabla-auditoria').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "{{ route('auditoria.data') }}",
            "type": "GET",
            "data": function (d) {
                // Se envían los valores de las fechas al servidor en cada petición Ajax
                d.fecha_inicio = $('#fecha_inicio').val();
                d.fecha_fin = $('#fecha_fin').val();
            }
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
        "order": [[3, 'desc']]
    });

    // Validar y ejecutar el filtro al hacer clic en "Filtrar"
    $('#btnFiltrar').click(function () {
        let fechaInicio = $('#fecha_inicio').val();
        let fechaFin = $('#fecha_fin').val();

        // Validación: Verificar que si ambas fechas están llenas, la inicial no sea mayor a la final
        if (fechaInicio && fechaFin && fechaInicio > fechaFin) {
            alert('Error: La "Fecha Desde" no puede ser posterior a la "Fecha Hasta".');
            return;
        }

        // Recargar la tabla aplicando los nuevos parámetros de fecha
        table.ajax.reload();
    });

    // Limpiar filtros y recargar la tabla
    $('#btnLimpiar').click(function () {
        $('#fecha_inicio').val('');
        $('#fecha_fin').val('');
        table.ajax.reload();
    });
  });

  function detallesAuditoria(data) {
      let anteriores = data.anteriores ? JSON.parse(data.anteriores) : {};
      let nuevos = data.nuevos ? JSON.parse(data.nuevos) : {};

      // Obtener todas las llaves (campos) únicas de ambos objetos
      let keys = [...new Set([...Object.keys(anteriores), ...Object.keys(nuevos)])];
      let html = '';

      if (keys.length === 0) {
          html = '<tr><td colspan="3" class="text-center text-muted">No hay datos registrados para esta acción.</td></tr>';
      } else {
          keys.forEach(key => {
              let valAnt = anteriores[key] !== undefined ? anteriores[key] : '<span class="text-muted font-italic">No existía</span>';
              let valNue = nuevos[key] !== undefined ? nuevos[key] : '<span class="text-muted font-italic">Eliminado</span>';

              // Opcional: Resaltar visualmente la fila si el valor cambió
              let rowClass = (valAnt !== valNue) ? 'table-warning' : '';

              html += `
                  <tr class="${rowClass}">
                      <td><strong>${key}</strong></td>
                      <td class="text-danger">${valAnt}</td>
                      <td class="text-success font-weight-bold">${valNue}</td>
                  </tr>
              `;
          });
      }

      $('#tabla-comparativa-audit tbody').html(html);
    }
</script>
@endsection