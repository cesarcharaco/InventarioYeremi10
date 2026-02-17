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
                  @foreach($insumos as $key)
                <tr>
                    <td><span class="badge badge-secondary">{{ $key->serial }}</span></td>
                    <td><strong>{{ $key->producto }}</strong></td>
                    <td><small>{{ $key->descripcion }}</small></td>
                    {{-- COLUMNA: ESTADO GENERAL (GLOBAL) --}}
                    <td class="text-center">
                        @can('gestionar-estado-global')
                            {{-- MODO EDICIÓN: El Admin ve el dropdown --}}
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-dark dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fas fa-globe"></i> {{ $key->estado }}
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="#" onclick="updateInsumoEstado({{ $key->id }}, 'En Venta', {{ $key->id_local }}, 'global')">En Venta</a>
                                    <a class="dropdown-item" href="#" onclick="updateInsumoEstado({{ $key->id }}, 'Suspendido', {{ $key->id_local }}, 'global')">Suspendido</a>
                                    <a class="dropdown-item" href="#" onclick="updateInsumoEstado({{ $key->id }}, 'No Disponible', {{ $key->id_local }}, 'global')">No Disponible</a>
                                </div>
                            </div>
                        @else
                            {{-- MODO LECTURA: El Encargado solo ve la información --}}
                            <span class="badge {{ $key->estado === 'En Venta' ? 'badge-success' : 'badge-dark' }}" title="Solo el administrador puede cambiar este estado">
                                <i class="fas fa-eye"></i> {{ $key->estado }}
                            </span>
                        @endcan
                    </td>

                    {{-- COLUMNA: ESTADO LOCAL (ESPECÍFICO) --}}
                    <td class="text-center">
                        @can('gestionar-estado-local', $key->id_local)
                            {{-- MODO EDICIÓN: El encargado de ESTE local o el Admin pueden editar --}}
                            <div class="dropdown">
                                <button class="btn btn-sm {{ $key->estado_local === 'Disponible' ? 'btn-success' : 'btn-danger' }} dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fas fa-store"></i> {{ $key->estado_local }}
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="#" onclick="updateInsumoEstado({{ $key->id }}, 'Disponible', {{ $key->id_local }}, 'local')">Disponible</a>
                                    <a class="dropdown-item" href="#" onclick="updateInsumoEstado({{ $key->id }}, 'Suspendido', {{ $key->id_local }}, 'local')">Suspendido</a>
                                </div>
                            </div>
                        @else
                            {{-- MODO LECTURA: Ve el estado del local, pero no puede cambiarlo porque no es el suyo --}}
                            <span class="badge badge-secondary" style="opacity: 0.7;" title="No tienes permisos para este local">
                                <i class="fas fa-lock"></i> {{ $key->estado_local }}
                            </span>
                        @endcan
                    </td>
                    {{-- Stock Min/Max ahora vienen directamente del objeto insumo --}}
                    <td class="text-center"><span class="badge badge-warning">{{ $key->stock_min }}</span></td>
                    <td class="text-center"><span class="badge badge-dark">{{ $key->stock_max }}</span></td>
                    {{-- Mostramos la cantidad única de la tabla pivot --}}
                    <td class="text-center text-primary font-weight-bold" style="font-size: 1.1em;">
                        {{ $key->cantidad }}
                    </td>
                    <td>
                        <i class="fa fa-map-marker-alt text-danger"></i> 
                        {{ $key->nombre_local }}
                    </td>
                    
                    <td class="text-center">
                        <div class="btn-group">
                          @can('gestionar-insumos')
                            {{-- Ajustamos la ruta de edición a la estándar del resource --}}
                            <a href="{{ route('insumos.edit', $key->id) }}" class="btn btn-info btn-sm">
                                <i class="fa fa-edit"></i>
                            </a>
                          @endcan
                            <button class="btn btn-success btn-sm" 
                                onclick="detalles('{{ $key->producto }}','{{ $key->descripcion }}','{{ $key->serial }}','{{ $key->stock_min }}','{{ $key->stock_max }}','{{ $key->cantidad }}','{{ $key->nombre_local }}')" 
                                data-toggle="modal" data-target="#detalles">
                                <i class="fa fa-eye"></i>
                            </button>
                          @if(auth()->user()->esAdmin() || auth()->user()->hasRole('encargado'))
                            {{-- Botón eliminar --}}
                            <button class="btn btn-danger btn-sm" onclick="eliminar('{{ $key->id }}')" data-toggle="modal" data-target="#eliminar_insumo">
                                <i class="fa fa-trash"></i>
                            </button>
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

    // MANTENEMOS TU LÓGICA DE DATATABLE INTACTA PARA EVITAR EL ERROR DE PARSEO
    var tabla;
    try {
        tabla = $('#tabla-insumos').DataTable({
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
   
  function eliminar(id) {
    $("#id_insumo").val(id);
  }

  // Ajustado para recibir la nueva estructura de parámetros
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
                    tipo: tipoAccion, // 'global' o 'local'
                    id_local: idLocal,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire('¡Éxito!', response.message, 'success').then(() => location.reload());
                }
            });
        }
    });
}
</script>
@endsection