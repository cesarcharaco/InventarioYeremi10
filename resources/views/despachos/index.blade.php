@extends('layouts.app')

@section('title') Historial de Despachos @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-history"></i> Historial de Despachos</h1>
      <p>Gestión y trazabilidad de traslados de mercancía entre sucursales</p>
    </div>
    {{-- PERMISO: Crear Despacho --}}
    @can('crear-despacho')
    <a href="{{ route('despacho.create') }}" class="btn btn-primary shadow-sm">
        <i class="fa fa-plus-circle"></i> Nuevo Despacho
    </a>
    @endcan
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
                @foreach($despachos as $d)
                <tr>
                  <td><strong class="text-primary">{{ $d->codigo }}</strong></td>
                  <td>{{ \Carbon\Carbon::parse($d->fecha_despacho)->format('d/m/Y h:i A') }}</td>
                  <td>{{ $d->origen->nombre ?? 'N/D' }}</td>
                  <td>{{ $d->destino->nombre ?? 'N/D' }}</td>
                  <td>
                    {{ $d->transportado_por }} 
                    @if($d->vehiculo_placa)
                      <small class="text-muted d-block">Placa: {{ $d->vehiculo_placa }}</small>
                    @endif
                  </td>
                  <td class="text-center">
                    @if($d->estado == 'En Tránsito')
                      <span class="badge badge-warning text-dark p-2"><i class="fa fa-truck"></i> En Tránsito</span>
                    @elseif($d->estado == 'Recibido')
                      <span class="badge badge-success p-2"><i class="fa fa-check-circle"></i> Recibido</span>
                    @elseif($d->estado == 'Con Observaciones')
                      <span class="badge badge-info p-2"><i class="fa fa-exclamation-circle"></i> Con Observaciones</span>
                    @elseif($d->estado == 'Rechazado')
                      <span class="badge badge-danger p-2"><i class="fa fa-times-circle"></i> Rechazado</span>
                    @else
                      <span class="badge badge-secondary p-2">{{ $d->estado }}</span>
                    @endif
                  </td>
                  <td class="text-center">
                      <div class="d-flex justify-content-center align-items-center" style="gap: 5px;">
                          {{-- Botón Ver Detalle --}}
                          <button class="btn btn-info btn-sm text-white" onclick="verDetalle({{ $d->id }}, '{{ $d->codigo }}')" title="Ver Detalle">
                              <i class="fa fa-eye"></i>
                          </button>

                          {{-- Acciones exclusivas si el despacho SÓLO está En Tránsito --}}
                          @if($d->estado == 'En Tránsito')
                              {{-- PERMISO: Recibir Despacho --}}
                              @can('recibir-despacho')
                              <button class="btn btn-success btn-sm" onclick="confirmarRecepcion({{ $d->id }})" title="Confirmar Recepción">
                                  <i class="fa fa-check-square"></i>
                              </button>
                              @endcan

                              {{-- PERMISO: Editar Despacho (Solo visible para perfiles con alcance global/admin, no para encargados de sucursal) --}}
                              @can('seleccionar-cualquier-origen')
                                  @can('editar-despacho')
                                  <a href="{{ route('despacho.edit', $d->id) }}" class="btn btn-warning btn-sm" title="Editar Despacho">
                                      <i class="fa fa-edit"></i>
                                  </a>
                                  @endcan
                                  {{-- PERMISO: Eliminar Despacho --}}
                                  @can('eliminar-despacho')
                                  <button class="btn btn-danger btn-sm" onclick="eliminarDespacho({{ $d->id }})" title="Eliminar Despacho">
                                      <i class="fa fa-trash"></i>
                                  </button>
                                  @endcan
                              @endcan

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

        // Inicialización de DataTable
        try {
            $('#tabla_despachos').DataTable({
                "responsive": true,
                "autoWidth": false,
                "language": lenguajeEspanol,
                "order": [[ 1, "desc" ]] // Ordenar por fecha descendente por defecto
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

    // 1. Abrir el modal y cargar los detalles del despacho seleccionado
    function confirmarRecepcion(id) {
        $('#form-recibir-despacho')[0].reset();
        $('#lista_detalles_recepcion').html('<tr><td colspan="3" class="text-center text-muted py-3"><i class="fa fa-spinner fa-spin"></i> Cargando repuestos...</td></tr>');
        $('#recibir_despacho_id').val(id);
        
        // Usamos la ruta nombrada de Laravel generada dinámicamente
        let urlJson = "{{ route('despacho.json', ':id') }}".replace(':id', id);

        $.get(urlJson, function(response) {
            $('#lbl_codigo_despacho').text('(' + response.codigo + ')');
            
            let filas = '';
            response.detalles.forEach(function(det) {
                // Verificamos que la relación 'insumos' exista
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
                                   min="0" max="${det.cantidad_enviada}" value="${det.cantidad_enviada}" required>
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

    // 2. Enviar el formulario de recepción vía AJAX al controlador
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

                        // 1. Capturamos el estado que el usuario seleccionó en el modal
                        let estadoSeleccionado = $('#estado_recepcion').val();

                        // 2. Definimos títulos e iconos personalizados según el estado
                        let tituloSwal = '¡Procesado con Éxito!';
                        let iconoSwal = 'success';

                        if (estadoSeleccionado === 'Recibido') {
                            tituloSwal = '¡Despacho Recibido Conforme!';
                            iconoSwal = 'success';
                        } else if (estadoSeleccionado === 'recibido_con_incidencias') {
                            tituloSwal = '¡Recibido con Incidencias!';
                            iconoSwal = 'warning'; // Amarillo de alerta para incidencias
                        } else if (estadoSeleccionado === 'Cancelado') {
                            tituloSwal = '¡Despacho Cancelado!';
                            iconoSwal = 'error';   // Rojo para cancelación
                        }

                        // 3. Mostramos la alerta dinámica
                        Swal.fire({
                            title: tituloSwal,
                            text: response.success,
                            icon: iconoSwal,
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            location.reload();
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
</script>
@endsection