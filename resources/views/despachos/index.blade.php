@extends('layouts.app')

@section('title') Historial de Despachos @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-history"></i> Historial de Despachos</h1>
      <p>Gestión y trazabilidad de traslados de mercancía entre sucursales</p>
    </div>
    {{-- PERMISO: Crear Solicitud --}}
        @can('crear-solicitud')
        <a href="{{ route('despacho.solicitud.create') }}" class="btn btn-success shadow-sm">
            <i class="fa fa-file-alt"></i> Nueva Solicitud
        </a>
        @endcan

        {{-- PERMISO: Crear Despacho --}}
        @can('crear-despacho')
        <a href="{{ route('despacho.create') }}" class="btn btn-primary shadow-sm">
            <i class="fa fa-plus-circle"></i> Nuevo Despacho
        </a>
        @endcan
  </div>

  {{-- =================================================== --}}
  {{-- SECCIÓN DE FILTROS REACTIVOS POR EVENTOS             --}}
  {{-- =================================================== --}}
  <div class="row mb-3">
    <div class="col-md-12">
      <div class="tile shadow-sm p-3">
        <h5 class="tile-title text-primary mb-3"><i class="fa fa-filter"></i> Filtros de Búsqueda</h5>
        <div class="row">
          <div class="col-md-3 form-group">
            <label for="filtro_desde">Fecha Desde:</label>
            <input type="date" id="filtro_desde" class="form-control form-control-sm" max="{{ date('Y-m-d') }}">
          </div>
          <div class="col-md-3 form-group">
            <label for="filtro_hasta">Fecha Hasta:</label>
            <input type="date" id="filtro_hasta" class="form-control form-control-sm" max="{{ date('Y-m-d') }}">
          </div>
          <div class="col-md-3 form-group">
            <label for="filtro_origen">Local Origen:</label>
            <select id="filtro_origen" class="form-control form-control-sm">
              <option value="">Todos los orígenes</option>
              @foreach($locales as $loc)
                <option value="{{ $loc->id }}">{{ $loc->nombre }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3 form-group">
            <label for="filtro_destino">Local Destino:</label>
            <select id="filtro_destino" class="form-control form-control-sm">
              <option value="">Todos los destinos</option>
              @foreach($locales as $loc)
                <option value="{{ $loc->id }}">{{ $loc->nombre }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3 form-group">
            <label for="filtro_estado">Estado:</label>
            <select id="filtro_estado" class="form-control form-control-sm">
              <option value="">Todos los estados</option>
              <option value="Pendiente">Pendiente</option>
              <option value="En Tránsito">En Tránsito</option>
              <option value="Recibido">Recibido</option>
              <option value="recibido_con_incidencias">Recibido con Incidencias</option>
              <option value="Rechazado">Rechazado</option>
              <option value="Cancelado">Cancelado</option>
            </select>
          </div>
          <div class="col-md-3 form-group d-flex align-items-end">
            <button type="button" id="btn-limpiar" class="btn btn-secondary btn-sm w-100">
              <i class="fa fa-undo"></i> Limpiar Filtros
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile shadow-sm">
        <div class="tile-body">
          <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle" id="tabla_despachos" width="100%">
              <thead class="table-light">
                <tr>
                  <th>Código</th>
                  <th>Fecha de Envío</th>
                  <th>Origen</th>
                  <th>Destino</th>
                  <th>Transportado por</th>
                  <th class="text-center">Estado</th>
                  <th width="120" class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                {{-- Los datos se cargan dinámicamente por AJAX --}}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

{{-- Modal para ver el detalle del despacho --}}
<div class="modal fade" id="modalDetalle" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle"><i class="fa fa-info-circle"></i> Detalle de Despacho</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="text-center py-4">
                    <i class="fa fa-spinner fa-spin fa-3x text-primary"></i>
                    <p class="mt-2">Cargando información...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Eliminar Despacho --}}
<div class="modal fade" id="modalEliminarDespacho" tabindex="-1" role="document" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fa fa-trash"></i> Eliminar Registro de Despacho</h5>
                <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form id="form-eliminar-despacho" action="{{ route('despacho.destroy', 0) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body text-center py-4">
                    <h1 class="text-danger mb-3"><i class="fa fa-exclamation-triangle"></i></h1>
                    <h4>¿Está seguro de eliminar este despacho?</h4>
                    <p class="text-muted">Esta acción revertirá automáticamente el stock al depósito de origen y no se podrá deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-danger" type="submit"><i class="fa fa-trash"></i> Sí, eliminar</button>
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Confirmar Recepción de Despacho -->
<div class="modal fade" id="modalRecibirDespacho" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form id="form-recibir-despacho">
        @csrf
        <input type="hidden" id="recibir_despacho_id" name="despacho_id">
        
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="fa fa-check-square"></i> Recibir Despacho <span id="lbl_codigo_despacho" class="font-weight-bold"></span></h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 form-group">
                <label><b>Estado de Recepción</b> <b class="text-danger">*</b></label>
                <select name="estado" id="estado_recepcion" class="form-control" required>
                    <option value="Recibido">Recibido Conforme</option>
                    <option value="recibido_con_incidencias">Recibido con Incidencias</option>
                    <option value="Cancelado">Cancelado</option>
                </select>
            </div>
            <div class="col-md-6 form-group">
              <label><b>Observación / Novedades</b></label>
              <input type="text" name="observacion_recepcion" class="form-control" placeholder="Ej: Llegaron 2 cajas golpeadas...">
            </div>
          </div>

          <hr>
          <h6 class="font-weight-bold text-primary mb-3"><i class="fa fa-cogs"></i> Verificación Física de Ítems</h6>
          
          <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
              <thead class="table-light">
                <tr>
                  <th>Repuesto / Insumo</th>
                  <th class="text-center" width="130px">Cant. Enviada</th>
                  <th class="text-center" width="140px">Cant. Recibida</th>
                </tr>
              </thead>
              <tbody id="lista_detalles_recepcion">
                <!-- Se llena dinámicamente con AJAX -->
              </tbody>
            </table>
          </div>
        </div>

        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fa fa-times"></i> Cancelar
          </button>
          <button type="submit" class="btn btn-success">
            <i class="fa fa-check-circle"></i> Procesar Recepción
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal para Procesar Envío de Solicitud Pendiente -->
<div class="modal fade" id="modalProcesarEnvio" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form id="form-procesar-envio">
        @csrf
        <input type="hidden" id="procesar_despacho_id" name="despacho_id">
        
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><i class="fa fa-paper-plane"></i> Procesar Solicitud de Despacho <span id="lbl_codigo_solicitud" class="font-weight-bold"></span></h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 form-group">
              <label><b>Transportado por</b> <b class="text-danger">*</b></label>
              <input type="text" name="transportado_por" id="procesar_transportado_por" class="form-control" required placeholder="Nombre del conductor o transporte...">
            </div>
            <div class="col-md-6 form-group">
              <label><b>Placa del Vehículo</b></label>
              <input type="text" name="vehiculo_placa" id="procesar_vehiculo_placa" class="form-control" placeholder="Ej: MOTO-123 o ABC-456">
            </div>
          </div>

          <div class="form-group">
            <label><b>Observación / Notas de Despacho</b></label>
            <textarea name="observacion" id="procesar_observacion" class="form-control" rows="2" placeholder="Observaciones sobre el envío..."></textarea>
            <small class="text-muted">Si modificas este campo, se actualizará la observación registrada inicialmente en la solicitud.</small>
          </div>

          <hr>
          <h6 class="font-weight-bold text-primary mb-3"><i class="fa fa-boxes"></i> Verificación y Ajuste de Cantidades a Enviar</h6>
          
          <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
              <thead class="table-light">
                <tr>
                  <th>Repuesto / Insumo</th>
                  <th class="text-center" width="140px">Cant. Solicitada</th>
                  <th class="text-center" width="140px">Cant. a Enviar</th>
                </tr>
              </thead>
              <tbody id="lista_detalles_procesar">
                <!-- Se llena dinámicamente con AJAX -->
              </tbody>
            </table>
          </div>
        </div>

        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fa fa-times"></i> Cancelar
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-paper-plane"></i> Procesar y Enviar Despacho
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Configuración de idioma local para DataTable
        var lenguajeEspanol = {
            "decimal": "",
            "emptyTable": "No hay registros de despachos disponibles",
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

        try {
            var tablaDespachos = $('#tabla_despachos').DataTable({
                "responsive": true,
                "autoWidth": false,
                "processing": true,
                "serverSide": true,
                "language": lenguajeEspanol,
                "ajax": {
                    "url": "{{ route('despacho.data') }}",
                    "data": function (d) {
                        d.fecha_desde = $('#filtro_desde').val();
                        d.fecha_hasta = $('#filtro_hasta').val();
                        d.id_local_origen = $('#filtro_origen').val();
                        d.id_local_destino = $('#filtro_destino').val();
                        d.estado = $('#filtro_estado').val();
                    }
                },
                "columns": [
                    { "data": "codigo" },
                    { "data": "fecha_despacho" },
                    { "data": "origen_nombre" },
                    { "data": "destino_nombre" },
                    { "data": "transportado_por" },
                    { "data": "estado", "className": "text-center" },
                    { "data": "acciones", "orderable": false, "searchable": false, "className": "text-center" }
                ],
                "order": [[ 1, "desc" ]]
            });

            // Fecha máxima permitida (Hoy en formato YYYY-MM-DD)
            const fechaHoy = new Date().toISOString().split('T')[0];

            // Evento reactivo para Fecha Desde con validación de límite de fecha actual
            $('#filtro_desde').on('change', function() {
                let valor = $(this).val();
                if (valor > fechaHoy) {
                    Swal.fire('Fecha no válida', 'La fecha "Desde" no puede ser posterior al día de hoy.', 'warning');
                    $(this).val('');
                    return;
                }
                tablaDespachos.ajax.reload();
            });

            // Evento reactivo para Fecha Hasta con validación de límite de fecha actual
            $('#filtro_hasta').on('change', function() {
                let valor = $(this).val();
                if (valor > fechaHoy) {
                    Swal.fire('Fecha no válida', 'La fecha "Hasta" no puede ser posterior al día de hoy.', 'warning');
                    $(this).val('');
                    return;
                }
                tablaDespachos.ajax.reload();
            });

            // Eventos reactivos para los Selects (Origen, Destino, Estado)
            $('#filtro_origen, #filtro_destino, #filtro_estado').on('change', function() {
                tablaDespachos.ajax.reload();
            });

            // Evento para limpiar todos los filtros de forma automática
            $('#btn-limpiar').on('click', function() {
                $('#filtro_desde').val('');
                $('#filtro_hasta').val('');
                $('#filtro_origen').val('');
                $('#filtro_destino').val('');
                $('#filtro_estado').val('');
                tablaDespachos.ajax.reload();
            });

        } catch (e) {
            console.log("Error en DataTable: ", e);
        }
    });
    
    function verDetalle(id, codigo) {
        $('#modalTitle').html('<i class="fa fa-info-circle"></i> Detalle de Despacho: ' + codigo);
        $('#modalBody').html('<div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2">Cargando...</p></div>');
        $('#modalDetalle').modal('show');

        $.get("{{ url('despacho') }}/" + id, function(data) {
            $('#modalBody').html(data);
        }).fail(function() {
            $('#modalBody').html('<div class="alert alert-danger text-center">No se pudo cargar la información del despacho.</div>');
        });
    }

    function confirmarRecepcion(id) {
        $('#form-recibir-despacho')[0].reset();
        $('#lista_detalles_recepcion').html('<tr><td colspan="3" class="text-center text-muted py-3"><i class="fa fa-spinner fa-spin"></i> Cargando repuestos...</td></tr>');
        $('#recibir_despacho_id').val(id);
        
        let urlJson = "{{ route('despacho.json', ':id') }}".replace(':id', id);

        $.get(urlJson, function(response) {
            $('#lbl_codigo_despacho').text('(' + response.codigo + ')');
            
            let filas = '';
            response.detalles.forEach(function(det) {
                let ins = det.insumos;
                
                let infoInsumo = ins 
                    ? `
                        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;">${ins.producto}</div>
                        <div class="text-muted small">
                            <span><i class="fa fa-barcode"></i> Serial: <strong>${ins.serial ?? 'N/A'}</strong></span> | 
                            <span><i class="fa fa-info-circle"></i> Desc: ${ins.descripcion ?? 'Sin descripción'}</span>
                        </div>
                      `
                    : `<span class="text-danger font-weight-bold">Repuesto #${det.id_insumo} (No encontrado)</span>`;
                
                filas += `
                    <tr>
                        <td class="align-middle">
                            ${infoInsumo}
                        </td>
                        <td class="text-center align-middle">
                            <span class="badge badge-secondary font-weight-bold" style="font-size: 1rem;">${det.cantidad_enviada}</span>
                        </td>
                        <td class="text-center align-middle">
                            <input type="number" name="cantidades_recibidas[${det.id}]" 
                                   class="form-control form-control-sm text-center font-weight-bold" 
                                   min="0" value="${det.cantidad_enviada}" required>
                        </td>
                    </tr>
                `;
            });
            
            $('#lista_detalles_recepcion').html(filas);
            $('#modalRecibirDespacho').modal('show');
        }).fail(function() {
            Swal.fire('Error', 'No se pudieron cargar los datos del despacho para su recepción.', 'error');
        });
    }

    $('#form-recibir-despacho').on('submit', function(e) {
        e.preventDefault();
        let id = $('#recibir_despacho_id').val();
        let formData = $(this).serialize();
        let urlRecibir = "{{ route('despacho.confirmar', ':id') }}".replace(':id', id);

        Swal.fire({
            title: '¿Procesar Recepción?',
            text: "Se actualizará el inventario del local de destino de forma definitiva.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa fa-check"></i> Sí, confirmar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Actualizando stock y notificando a la sucursal de origen.',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                $.ajax({
                    url: urlRecibir,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#modalRecibirDespacho').modal('hide');

                        let estadoSeleccionado = $('#estado_recepcion').val();
                        let tituloSwal = '¡Procesado con Éxito!';
                        let iconoSwal = 'success';

                        if (estadoSeleccionado === 'Recibido') {
                            tituloSwal = '¡Despacho Recibido Conforme!';
                            iconoSwal = 'success';
                        } else if (estadoSeleccionado === 'recibido_con_incidencias') {
                            tituloSwal = '¡Recibido con Incidencias!';
                            iconoSwal = 'warning';
                        } else if (estadoSeleccionado === 'Cancelado') {
                            tituloSwal = '¡Despacho Cancelado!';
                            iconoSwal = 'error';
                        }

                        Swal.fire({
                            title: tituloSwal,
                            text: response.success,
                            icon: iconoSwal,
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            $('#tabla_despachos').DataTable().ajax.reload(null, false);
                        });
                    },
                    error: function(xhr) {
                        let mensaje = 'Ocurrió un error al procesar la recepción.';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            mensaje = xhr.responseJSON.error;
                        }
                        Swal.fire('Atención', mensaje, 'error');
                    }
                });
            }
        });
    });
    
    function eliminarDespacho(id) {
        let url = "{{ url('despacho') }}/" + id;
        $('#form-eliminar-despacho').attr('action', url);
        $('#modalEliminarDespacho').modal('show');
    }

    function eliminarSolicitud(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "¡Esta solicitud pendiente será eliminada permanentemente!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar solicitud',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                let url = "{{ route('despacho.solicitud.destroy', ':id') }}".replace(':id', id);

                Swal.fire({
                    title: 'Eliminando...',
                    text: 'Por favor, espere un momento.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(async response => {
                    let data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'Ocurrió un error al intentar eliminar.');
                    }
                    return data;
                })
                .then(data => {
                    Swal.fire({
                        title: '¡Eliminado!',
                        text: data.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        $('#tabla_despachos').DataTable().ajax.reload(null, false);
                    });
                })
                .catch(error => {
                    Swal.fire({
                        title: 'No se pudo eliminar',
                        text: error.message,
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                });
            }
        });
    }

    function procesarEnvioPendienteModal(id) {
        $('#form-procesar-envio')[0].reset();
        $('#lista_detalles_procesar').html('<tr><td colspan="3" class="text-center text-muted py-3"><i class="fa fa-spinner fa-spin"></i> Cargando repuestos solicitados...</td></tr>');
        $('#procesar_despacho_id').val(id);
        
        let urlJson = "{{ route('despacho.json', ':id') }}".replace(':id', id);

        $.get(urlJson, function(response) {
            $('#lbl_codigo_solicitud').text('(' + response.codigo + ')');
            
            // Precargar la observación existente por si el almacenista quiere editarla o complementarla
            $('#procesar_observacion').val(response.observacion || '');
            
            let filas = '';
            response.detalles.forEach(function(det) {
                let ins = det.insumos;
                
                let infoInsumo = ins 
                    ? `
                        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;">${ins.producto}</div>
                        <div class="text-muted small">
                            <span><i class="fa fa-barcode"></i> Serial: <strong>${ins.serial ?? 'N/A'}</strong></span> | 
                            <span><i class="fa fa-info-circle"></i> Desc: ${ins.descripcion ?? 'Sin descripción'}</span>
                        </div>
                      `
                    : `<span class="text-danger font-weight-bold">Repuesto #${det.id_insumo} (No encontrado)</span>`;
                
                filas += `
                    <tr>
                        <td class="align-middle">
                            ${infoInsumo}
                        </td>
                        <td class="text-center align-middle">
                            <span class="badge badge-info font-weight-bold" style="font-size: 1rem;">${det.cantidad_enviada}</span>
                        </td>
                        <td class="text-center align-middle">
                            <input type="number" name="cantidades_enviadas[${det.id}]" 
                                   class="form-control form-control-sm text-center font-weight-bold" 
                                   min="0" value="${det.cantidad_enviada}" required>
                        </td>
                    </tr>
                `;
            });
            
            $('#lista_detalles_procesar').html(filas);
            $('#modalProcesarEnvio').modal('show');
        }).fail(function() {
            Swal.fire('Error', 'No se pudo cargar la información de la solicitud.', 'error');
        });
    }

    // Evento submit para procesar el envío de la solicitud
    $('#form-procesar-envio').on('submit', function(e) {
        e.preventDefault();
        let id = $('#procesar_despacho_id').val();
        let formData = $(this).serialize();
        
        // Ajusta la URL según tu archivo web.php para la ruta que apunta a procesarEnvioPendiente
        // Ejemplo estándar: url('despacho/' + id + '/procesar-envio')
        let urlProcesar = "{{ url('despacho') }}/" + id + "/procesar-envio"; 

        Swal.fire({
            title: '¿Confirmar Envío de Solicitud?',
            text: "Se validará el stock, se descontará del origen y el estado pasará a 'En Tránsito'.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa fa-paper-plane"></i> Sí, procesar envío',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Actualizando inventario y notificando al local de destino.',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                $.ajax({
                    url: urlProcesar,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#modalProcesarEnvio').modal('hide');

                        Swal.fire({
                            title: '¡Procesado con Éxito!',
                            text: response.success,
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            $('#tabla_despachos').DataTable().ajax.reload(null, false);
                        });
                    },
                    error: function(xhr) {
                        let mensaje = 'Ocurrió un error al procesar el envío.';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            mensaje = xhr.responseJSON.error;
                        }
                        Swal.fire('Atención', mensaje, 'error');
                    }
                });
            }
        });
    });
</script>
@endsection