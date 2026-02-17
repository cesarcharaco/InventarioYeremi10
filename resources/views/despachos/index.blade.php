@extends('layouts.app')

@section('title') Historial de Despachos @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-history"></i> Historial de Despachos</h1>
      <p>Gestión de traslados de mercancía entre locales</p>
    </div>
    {{-- PERMISO: Crear Despacho --}}
    @can('crear-despacho')
    <a href="{{ route('despacho.create') }}" class="btn btn-primary">
        <i class="fa fa-plus"></i> Nuevo Despacho
    </a>
    @endcan
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-body">
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="tabla_despachos">
              <thead>
                <tr>
                  <th>Código</th>
                  <th>Fecha</th>
                  <th>Origen</th>
                  <th>Destino</th>
                  <th>Transportado por</th>
                  <th>Estado</th>
                  <th width="100">Acciones</th>
                </tr>
              </thead>
              <tbody>
                @foreach($despachos as $d)
                <tr>
                  <td><strong>{{ $d->codigo }}</strong></td>
                  <td>{{ \Carbon\Carbon::parse($d->fecha_despacho)->format('d/m/Y h:i A') }}</td>
                  <td>{{ $d->origen->nombre }}</td>
                  <td>{{ $d->destino->nombre }}</td>
                  <td>{{ $d->transportado_por }}</td>
                  <td>
                    @if($d->estado == 'En Tránsito')
                      <span class="badge badge-warning"><i class="fa fa-truck"></i> En Tránsito</span>
                    @elseif($d->estado == 'Recibido')
                      <span class="badge badge-success"><i class="fa fa-check"></i> Recibido</span>
                    @else
                      <span class="badge badge-secondary">{{ $d->estado }}</span>
                    @endif
                  </td>
                  <td>
                    <div class="btn-group">
                        <button class="btn btn-info btn-sm" onclick="verDetalle({{ $d->id }}, '{{ $d->codigo }}')" title="Ver Detalle">
						    <i class="fa fa-eye"></i>
					    </button>
                    @if($d->estado == 'En Tránsito')
                        @can('recibir-despacho')
					    <button class="btn btn-success btn-sm" onclick="confirmarRecepcion({{ $d->id }})" title="Confirmar Recepción">
					        <i class="fa fa-check-square"></i>
					    </button>
                        @endcan
					@endif
                        {{-- PERMISO: Editar Despacho --}}
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
<div class="modal fade" id="modalDetalle" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">Detalle de Despacho</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-3x"></i>
                    <p>Cargando información...</p>
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
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fa fa-trash"></i> Eliminar Registro de Despacho</h5>
                <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            {{-- El ID 0 en la ruta es solo un placeholder, el JS se encarga de cambiarlo --}}
            <form id="form-eliminar-despacho" action="{{ route('despacho.destroy', 0) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <h2 class="text-center text-danger"><i class="fa fa-exclamation-triangle"></i></h2>
                    <h4 class="text-center">¿Está seguro de eliminar este despacho?</h4>
                    <p class="text-center text-muted">Esta acción es irreversible y podría afectar el historial de inventario.</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-danger" type="submit">Sí, eliminar</button>
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
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
       // 1. OBJETO DE TRADUCCIÓN LOCAL (Adiós errores de CORS y red)
    var lenguajeEspanol = {
        "decimal": "",
        "emptyTable": "No hay información",
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

    // 2. INICIALIZACIÓN SIN BORRADO DE DATOS
    var tabla;
    try {
        // Inicialización directa. "retrieve: true" le dice que si ya existe la use, 
        // pero quitamos el .clear().destroy() para que no borre el tbody.
        tabla = $('#tabla_despachos').DataTable({
            "responsive": true,
            "autoWidth": false,
            "language": lenguajeEspanol,
            "retrieve": true,
            "paging": true,
            "searching": true
        });
    } catch (e) {
        console.log("Error en DataTable: ", e);
    }
 });
    
    function verDetalle(id, codigo) {
	    $('#modalTitle').text('Detalle de Despacho: ' + codigo);
	    $('#modalBody').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');
	    $('#modalDetalle').modal('show');

	    $.get("{{ url('despacho') }}/" + id, function(data) {
	        $('#modalBody').html(data);
	    });
	}
	function confirmarRecepcion(id) {
	    if (confirm("¿Está seguro de que desea confirmar la recepción de este despacho? Esto marcará la mercancía como entregada.")) {
	        
	        // Obtenemos el token CSRF que Laravel requiere para peticiones POST
	        let token = $('meta[name="csrf-token"]').attr('content');

	        $.ajax({
	            url: "{{ url('despacho/confirmar') }}/" + id,
	            type: 'POST',
	            data: {
	                _token: token
	            },
	            success: function(response) {
	                // Si tienes SweetAlert2 instalado, puedes usarlo aquí. Si no, alert normal.
	                alert(response.success);
	                location.reload(); // Recargamos para que se actualicen los badges y botones
	            },
	            error: function(xhr) {
	                let errorMsg = xhr.responseJSON ? xhr.responseJSON.error : "Error desconocido";
	                alert("Error: " + errorMsg);
	            }
	        });
	    }
	}
// Asegúrate de que esta función NO esté dentro de $(document).ready
// Las funciones llamadas desde onclick deben estar en el scope global.

function confirmarRecepcion(id) {
    Swal.fire({
        title: '¿Confirmar recepción?',
        text: "La mercancía se marcará como recibida en destino.",
        icon: 'question', // Cambié a 'question' para que sea más intuitivo
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: '<i class="fa fa-check"></i> Sí, recibir',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Procesando...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading() }
            });

            $.ajax({
                url: "{{ url('despacho/confirmar') }}/" + id,
                type: 'POST',
                // No enviamos el token aquí porque ya lo configuramos en el $.ajaxSetup
                success: function(response) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: response.success,
                        icon: 'success',
                        timer: 1500, // Se cierra solo en 1.5 seg
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    let mensaje = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Error desconocido';
                    Swal.fire('Error', mensaje, 'error');
                }
            });
        }
    });
    
    return false; // Evita cualquier acción colateral del botón
}
function eliminarDespacho(id) {
    // Construimos la URL dinámicamente usando el ID del despacho
    let url = "{{ url('despacho') }}/" + id;
    
    // Asignamos la URL al action del formulario dentro del modal
    $('#form-eliminar-despacho').attr('action', url);
    
    // Mostramos el modal
    $('#modalEliminarDespacho').modal('show');
}
</script>
@endsection