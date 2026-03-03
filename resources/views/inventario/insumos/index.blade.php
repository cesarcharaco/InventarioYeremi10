@extends('layouts.app')

@section('title') Insumos @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-th-list"></i> SAYER</h1>
      <p>Sistema Administrativo | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item">SAYER</li>
      <li class="breadcrumb-item"><a href="{{ route('insumos.index') }}">Insumos</a></li>
      <li class="breadcrumb-item">Listado</li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head" id="indicators">
            Insumos
            <a class="btn btn-primary icon-btn pull-right" href="{{ route('insumos.create') }}">
              <i class="fa fa-plus"></i> Registrar insumo
            </a>
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
              <table class="table table-hover table-bordered" id="tabla-insumos">
                <thead>
                  <tr class="bg-primary text-white">
                    <th>Serial</th>
                    <th>Producto</th>
                    <th>Descripción</th>
                    {{-- Columna Estado General --}}
                    <th class="text-center">
                        <i class="fas fa-globe"></i> Estado General
                        @cannot('gestionar-estado-global')
                            <small class="d-block bg-white text-dark mt-1 rounded px-1" style="font-size: 0.65em; opacity: 0.9;">
                                (Solo Lectura)
                            </small>
                        @endcannot
                    </th>

                    {{-- Columna Estado Local --}}
                    <th class="text-center">
                        <i class="fas fa-store"></i> Estado Local
                        @php
                            // Verificamos si es un encargado para poner el aviso de solo lectura
                            $rol = is_object(auth()->user()->role) ? auth()->user()->role->nombre : auth()->user()->role;
                        @endphp
                        @if($rol === 'encargado')
                        <small class="d-block bg-white text-dark mt-1 rounded px-1" style="font-size: 0.65em; opacity: 0.9;">
                            (Editable en su local)
                        </small>
                            
                        @endif
                    </th>
                    <th class="text-center">Min</th>
                    <th class="text-center">Max</th>
                    <th class="text-center">Stock</th>
                    <th>Ubicación</th>
                    
                    <th class="text-center">Acciones</th>
                   
                  </tr>
                </thead>
                <tbody>
                  
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

{{-- MODAL ELIMINAR (Corregido para apuntar a la nueva ruta destroy_manual si es necesario) --}}
<div class="modal fade" id="eliminar_insumo" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fa fa-trash"></i> Eliminar Insumo</h5>
        <button class="close text-white" type="button" data-dismiss="modal"><span>×</span></button>
      </div>
      <form action="{{ route('insumos.destroy_manual') }}" method="POST" id="form-eliminar">
        @csrf
        <div class="modal-body text-center">
          <h4 class="text-danger">¿Está seguro?</h4>
          <p>Esta acción eliminará el registro del insumo y sus existencias permanentemente.</p>
          <input type="hidden" name="id_insumo" id="id_insumo">
        </div>
        <div class="modal-footer">
          <button class="btn btn-danger" type="submit">Eliminar Definitivamente</button>
          <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
        </div>          
      </form>
    </div>
  </div>
</div>

{{-- MODAL DETALLES --}}
<div class="modal fade" id="detalles" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fa fa-eye"></i> Detalles del Insumo</h5>
        <button class="close text-white" type="button" data-dismiss="modal"><span>×</span></button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered">
          <tr>
            <th class="bg-light" width="30%">Producto:</th>
            <td><span id="det_producto"></span></td>
          </tr>
          <tr>
            <th class="bg-light">Descripción:</th>
            <td><span id="det_descripcion"></span></td>
          </tr>
          <tr>
            <th class="bg-light">Serial:</th>
            <td><span id="det_serial" class="badge badge-info"></span></td>
          </tr>
          <tr>
            <th class="bg-light">Configuración Stock (Global):</th>
            <td>Mínimo: <span id="det_stock_min" class="text-danger font-weight-bold"></span> | Máximo: <span id="det_stock_max" class="text-success font-weight-bold"></span></td>
          </tr>
          <tr>
            <th class="bg-light">Existencia en Ubicación:</th>
            <td>Cantidad: <span id="det_cantidad" class="font-weight-bold"></span></td>
          </tr>
          <tr>
            <th class="bg-light">Ubicación específica:</th>
            <td><span id="det_nombre"></span></td>
          </tr>
        </table>
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
    
    // 1. Definición del lenguaje (Faltaba en tu bloque anterior)
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

    // 2. Inicialización de DataTable
    $('#tabla-insumos').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "{{ route('insumos.data') }}",
            "type": "GET"
        },
        "columns": [
            { "data": "serial" },
            { "data": "producto" },
            { "data": "descripcion" },
            { "data": "estado_global", "className": "text-center" },
            { "data": "estado_local", "className": "text-center" },
            { "data": "stock_min", "className": "text-center" },
            { "data": "stock_max", "className": "text-center" },
            { "data": "cantidad", "className": "text-center" },
            { "data": "nombre_local" },
            { "data": "acciones", "orderable": false, "searchable": false }
        ],
        "language": lenguajeEspanol,
        "responsive": true,
        "autoWidth": false,
        "pageLength": 10,
        "searchDelay": 500,
        "order": [[1, 'asc']] // Ordenar por Producto por defecto
    });
  });

  // Funciones auxiliares (Eliminar, Detalles, Estados)
  function eliminar(id) {
    $("#id_insumo").val(id);
  }

  function detalles(prod, desc, seri, smin, smax, cant, nom) {
    $("#det_producto").text(prod);
    $("#det_descripcion").text(desc);
    $("#det_serial").text(seri);
    $("#det_stock_min").text(smin);
    $("#det_stock_max").text(smax);
    $("#det_cantidad").text(cant);
    $("#det_nombre").text(nom);
  }

  function updateInsumoEstado(idInsumo, nuevoEstado, idLocal, tipoAccion) {
    Swal.fire({
        title: '¿Confirmar cambio?',
        text: `Vas a cambiar el estado ${tipoAccion} a: ${nuevoEstado}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, cambiar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ route('insumo.cambiarEstado') }}",
                type: 'POST',
                data: {
                    id: idInsumo,
                    estado: nuevoEstado,
                    tipo: tipoAccion, 
                    id_local: idLocal,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire('¡Éxito!', response.message, 'success').then(() => {
                        // En lugar de recargar la página completa, recargamos solo la tabla
                        $('#tabla-insumos').DataTable().ajax.reload(null, false);
                    });
                },
                error: function() {
                    Swal.fire('Error', 'No se pudo actualizar el estado', 'error');
                }
            });
        }
    });
  }
</script>
@endsection