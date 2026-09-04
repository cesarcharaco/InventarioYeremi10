@extends('layouts.app')

@section('title') Reglas de Promoción @endsection

@section('content')
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-title-w-btn">
        <h3 class="title">Listado de Promociones</h3>
        @can('gestionar-promociones')
        <p><button class="btn btn-primary icon-btn" data-toggle="modal" data-target="#modalCreate"><i class="fa fa-plus"></i>Nueva Promoción</button></p>
        @endcan
      </div>
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover table-bordered" id="sampleTable">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Local / Sucursal</th>
                <th>Alcance</th>
                <th>Asociado (Categoría / Insumo)</th>
                <th>Descuento</th>
                <th>Vigencia</th>
                <th>Estado</th>
                <th>Usado en Ventas</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              @foreach($promociones as $promocion)
              <tr>
                <td><strong>{{ $promocion->nombre }}</strong></td>
                <td>
                  <span class="badge badge-secondary">{{ $promocion->local->nombre ?? 'N/A' }}</span>
                </td>
                <td>
                  @if($promocion->alcance == 'categoria')
                    <span class="badge badge-info">Categoría</span>
                  @else
                    <span class="badge badge-primary">Insumo</span>
                  @endif
                </td>
                <td>
                  @if($promocion->alcance == 'categoria')
                    {{ $promocion->referencia->categoria ?? 'N/A' }}
                  @else
                    (<em>{{ $promocion->referencia->serial ?? '' }}</em>) {{ $promocion->referencia->producto."-".$promocion->referencia->descripcion ?? 'N/A' }} 
                  @endif
                </td>
                <td>
                  <span class="text-success font-weight-bold">{{ number_format($promocion->porcentaje_descuento, 2) }}%</span>
                </td>
                <td>
                  <small>Desde: {{ \Carbon\Carbon::parse($promocion->fecha_inicio)->format('d/m/Y') }}</small><br>
                  <small>Hasta: {{ \Carbon\Carbon::parse($promocion->fecha_fin)->format('d/m/Y') }}</small>
                </td>
                <td>
                  <div class="toggle-button-wrapper">
                    <input type="checkbox" 
                           class="toggle-activo" 
                           data-id="{{ $promocion->id }}"
                           {{ $promocion->activo ? 'checked' : '' }}
                           data-toggle="toggle" 
                           data-size="sm" 
                           data-on="Activo" 
                           data-off="Inactivo" 
                           data-onstyle="success" 
                           data-offstyle="danger">
                  </div>
                </td>
                <td class="text-center">
                  <span class="badge badge-dark">{{ $promocion->detalle_ventas_count ?? 0 }} veces</span>
                </td>
                <td>
                  {{-- Botón Mostrar Modal Show --}}
                  <button class="btn btn-info btn-sm btn-show" 
                          data-id="{{ $promocion->id }}"
                          data-nombre="{{ $promocion->nombre }}"
                          data-local="{{ $promocion->local->nombre ?? 'N/A' }}"
                          data-alcance="{{ $promocion->alcance }}"
                          data-referencia="{{ $promocion->alcance == 'categoria' ? ($promocion->referencia->categoria ?? 'N/A') : ($promocion->referencia->producto ?? 'N/A') }}"
                          data-descuento="{{ $promocion->porcentaje_descuento }}"
                          data-inicio="{{ $promocion->fecha_inicio }}"
                          data-fin="{{ $promocion->fecha_fin }}"
                          data-activo="{{ $promocion->activo }}"
                          title="Ver Detalles">
                    <i class="fa fa-eye"></i>
                  </button>

                  {{-- Botón Editar Modal Edit --}}
                  @can('gestionar-promociones')
                  <button class="btn btn-primary btn-sm btn-edit" 
                          data-id="{{ $promocion->id }}"
                          data-local-id="{{ $promocion->local_id }}"
                          data-nombre="{{ $promocion->nombre }}"
                          data-alcance="{{ $promocion->alcance }}"
                          data-referencia-texto="{{ $promocion->alcance == 'categoria' ? ($promocion->referencia->categoria ?? 'N/A') : (($promocion->referencia->producto ?? 'N/A') . ' (' . ($promocion->referencia->serial ?? 'N/A') . ')') }}"
                          data-descuento="{{ $promocion->porcentaje_descuento }}"
                          data-inicio="{{ \Carbon\Carbon::parse($promocion->fecha_inicio)->format('Y-m-d') }}"
                          data-fin="{{ \Carbon\Carbon::parse($promocion->fecha_fin)->format('Y-m-d') }}"
                          data-ventas="{{ $promocion->detalle_ventas_count ?? 0 }}"
                          title="Editar Promoción">
                    <i class="fa fa-edit"></i>
                  </button>
                  @endcan

                  {{-- Botón Eliminar Modal Delete --}}
                  @can('gestionar-promociones')
                  <button class="btn btn-danger btn-sm btn-delete" 
                          data-id="{{ $promocion->id }}"
                          data-nombre="{{ $promocion->nombre }}"
                          data-local="{{ $promocion->local->nombre ?? 'N/A' }}"
                          data-ventas="{{ $promocion->detalle_ventas_count }}"
                          title="Eliminar">
                      <i class="fa fa-trash"></i>
                  </button>
                  @endcan
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

{{-- Inclusión de Modales desde la carpeta modals --}}
@include('promociones.modals.create')
@include('promociones.modals.edit')
@include('promociones.modals.show')
@include('promociones.modals.delete')
@endsection

@section('scripts')
<script>
    if (typeof window.insumosList === 'undefined') {
        window.insumosList = [
            @foreach($insumos as $insumo)
            { id: '{{ $insumo->id }}', text: '{{ $insumo->producto }} - {{ $insumo->descripcion ?? "Sin descripción" }} (Serial: {{ $insumo->serial ?? "N/A" }})' },
            @endforeach
        ];
    }

    if (typeof window.categoriasList === 'undefined') {
        window.categoriasList = [
            @foreach($categorias as $categoria)
            { id: '{{ $categoria->id }}', text: '{{ $categoria->categoria }}' },
            @endforeach
        ];
    }

    $(document).ready(function() {
        // Inicializar DataTables con soporte de idioma en español
        $('#sampleTable').DataTable({
            "language": {
                "sProcessing":     "Procesando...",
                "sLengthMenu":     "Mostrar _MENU_ registros",
                "sZeroRecords":    "No se encontraron resultados",
                "sEmptyTable":     "Ningún dato disponible en esta tabla",
                "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
                "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
                "sSearch":         "Buscar:",
                "sLoadingRecords": "Cargando...",
                "oPaginate": {
                    "sFirst":    "Primero",
                    "sLast":    "Último",
                    "sNext":    "Siguiente",
                    "sPrevious": "Anterior"
                }
            },
            "order": [[0, "asc"]]
        });

        // Inicializar Select2 en el modal de creación
        $('#create_referencia_id').select2({
            dropdownParent: $('#modalCreate'),
            placeholder: 'Seleccione los elementos...'
        });

        // Cambio dinámico basado en el alcance seleccionado (Create)
        $('#create_alcance').on('change', function() {
            let alcance = $(this).val();
            let $selectRef = $('#create_referencia_id');
            $selectRef.empty();

            let source = (alcance === 'insumo') ? window.insumosList : window.categoriasList;
            source.forEach(function(item) {
                $selectRef.append(new Option(item.text, item.id, false, false));
            });

            $selectRef.trigger('change');
        });
    });

    // Delegación global para botones y componentes de la tabla (Funciona con DataTables y paginación)

    // Mostrar Modal Show
    $(document).on('click', '.btn-show', function() {
        $('#show-nombre').text($(this).data('nombre'));
        $('#show-local').text($(this).data('local'));
        $('#show-alcance').text($(this).data('alcance').toUpperCase());
        $('#show-referencia').text($(this).data('referencia'));
        $('#show-descuento').text($(this).data('descuento') + '%');
        $('#show-inicio').text($(this).data('inicio'));
        $('#show-fin').text($(this).data('fin'));
        let activo = $(this).data('activo');
        $('#show-activo').html(activo ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>');
        
        $('#modalShow').modal('show');
    });

    // Mostrar Modal Edit
    $(document).on('click', '.btn-edit', function() {
        let id = $(this).data('id');
        let url = "{{ route('promociones.update', ':id') }}".replace(':id', id);
        let ventasCount = parseInt($(this).data('ventas'));

        $('#formEdit').attr('action', url);
        $('#edit-local_id').val($(this).data('local-id')).trigger('change');
        $('#edit-nombre').val($(this).data('nombre'));
        
        let alcance = $(this).data('alcance');
        $('#edit-alcance-text').val(alcance === 'categoria' ? 'Categoría' : 'Insumo / Producto');
        $('#edit-referencia-text').val($(this).data('referencia-texto'));

        $('#edit-porcentaje_descuento').val($(this).data('descuento'));
        $('#edit-fecha_inicio').val($(this).data('inicio'));
        $('#edit-fecha_fin').val($(this).data('fin'));

        if (ventasCount > 0) {
            $('#edit-local_id').prop('disabled', true);
            $('#edit-fecha_inicio').prop('readonly', true);
            $('#edit-warning-alert').show();
        } else {
            $('#edit-local_id').prop('disabled', false);
            $('#edit-fecha_inicio').prop('readonly', false);
            $('#edit-warning-alert').hide();
        }

        $('#modalEdit').modal('show');
    });

    // Mostrar Modal Delete
    $(document).on('click', '.btn-delete', function() {
        let id = $(this).data('id');
        let nombre = $(this).data('nombre');
        let localNombre = $(this).data('local');
        let ventasCount = parseInt($(this).data('ventas'));

        // Construcción segura de la URL sin errores de codificación de Blade
        let url = "{{ url('admin/promociones') }}" + "/" + id;
        
        $('#formDelete').attr('action', url);
        $('#delete-nombre').text(nombre);
        $('#delete-local').text(localNombre);
        
        if (ventasCount > 0) {
            $('#delete-warning').show();
        } else {
            $('#delete-warning').hide();
        }

        $('#modalDelete').modal('show');
    });

    // AJAX Toggle Activo
    $(document).on('change', '.toggle-activo', function() {
        let $checkbox = $(this);
        let id = $checkbox.data('id');
        let $row = $checkbox.closest('tr');
        let $btnShow = $row.find('.btn-show');
        let url = "{{ route('promociones.toggle', ':id') }}".replace(':id', id);

        $.ajax({
            url: url,
            type: 'PATCH',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if(response.success) {
                    $btnShow.attr('data-activo', response.activo ? 1 : 0);
                } else {
                    swal("Atención", response.message || "No se pudo cambiar el estado.", "warning");
                    $checkbox.bootstrapToggle('toggle');
                }
            },
            error: function(xhr) {
                let mensaje = "Ocurrió un error al intentar cambiar el estado.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    mensaje = xhr.responseJSON.message;
                }
                swal("¡Error!", mensaje, "error");
                $checkbox.bootstrapToggle('toggle');
            }
        });
    });
</script>
@endsection