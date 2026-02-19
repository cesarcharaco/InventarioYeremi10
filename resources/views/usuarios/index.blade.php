@extends('layouts.app')

@section('title') Usuarios @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-users"></i> Gestión de Usuarios</h1>
      <p>Control de acceso | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('usuarios.index') }}">Usuarios</a></li>
      <li class="breadcrumb-item">Listado</li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head" id="indicators">Usuarios Registrados
            @can('gestionar-usuarios')
            <a class="btn btn-primary icon-btn pull-right" href="{{ route('usuarios.create') }}">
              <i class="fa fa-user-plus"></i> Registrar Usuario
            </a>
            @endcan
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
              <table class="table table-hover table-bordered" id="sampleTable">
                <thead>
                  <tr>
                    <th>Nombre</th>
                    <th>Cédula</th>
                    <th>Email / Usuario</th>
                    <th>Rol</th>
                    <th>Sede / Local Actual</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($users as $user)
                  <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->cedula }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                      @if($user->role === \App\Models\User::ROLE_SUPERADMIN)
                        <span class="badge badge-danger">ADMINISTRADOR</span>
                      @elseif($user->role === \App\Models\User::ROLE_ENCARGADO)
                        <span class="badge badge-warning">ENCARGADO</span>
                      @elseif($user->role === \App\Models\User::ROLE_ALMACENISTA)
                        <span class="badge badge-primary">ALMACENISTA</span>
                      @else
                        <span class="badge badge-info">VENDEDOR</span>
                      @endif
                    </td>
                    <td>
                      @if($user->localActual())
                        <i class="fa fa-map-marker text-danger"></i> {{ $user->localActual()->nombre }}
                      @else
                        <span class="text-muted">Sin asignar</span>
                      @endif
                    </td>
                    <td class="text-center">
                        @if($user->activo)
                            <span class="badge badge-success">Activo</span>
                        @else
                            <span class="badge badge-secondary">Inactivo</span>
                        @endif
                    </td>
                    <td>
                      @can('gestionar-usuarios')
                        <a class="btn btn-info btn-sm" href="{{ route('usuarios.show', $user->id) }}" title="Ver Detalles">
                            <i class="fa fa-eye"></i>
                        </a>
                        <a href="{{ route('usuarios.edit', $user->id) }}" 
                           class="btn btn-info btn-sm" 
                           data-toggle="tooltip" 
                           title="Editar Usuario">
                          <i class="fa fa-edit"></i>
                        </a>

                        @if($user->id !== auth()->id()) {{-- Evitar que se elimine a sí mismo --}}
                        <a href="javascript:;" 
                           class="btn btn-danger btn-sm" 
                           data-toggle="modal" 
                           data-target="#eliminar_Usuario" 
                           onclick="prepararEliminar('{{ $user->id }}', '{{ $user->name }}')">
                          <i class="fa fa-trash"></i>
                        </a>
                        @endif
                      @else
                        <span class="badge badge-light">Lectura</span>
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
  </div>
</main>

{{-- Modal Eliminar --}}
<div class="modal fade" id="eliminar_Usuario" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fa fa-trash"></i> Eliminar Usuario</h5>
        <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <form id="form-eliminar-usuario" method="POST">
        @csrf
        @method('DELETE')
        <div class="modal-body text-center">
            <h3>¿Está seguro?</h3>
            <p>Se eliminará el acceso para: <strong id="nombre_usuario_modal"></strong></p>
            <p class="text-muted small">Esta acción no se puede deshacer.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger" type="submit">Confirmar Eliminación</button>
            <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
        </div>          
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
  function prepararEliminar(id, nombre) {
    $('#nombre_usuario_modal').text(nombre);
    $('#form-eliminar-usuario').attr('action', "{{ url('usuarios') }}/" + id + "/eliminar");
  }

  $(document).ready(function() {
    if ( ! $.fn.DataTable.isDataTable( '#sampleTable' ) ) {
        $('#sampleTable').DataTable({
          "language": { 
              "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" 
          },
          "responsive": true,
          "order": [[ 0, "asc" ]] // Ordenar por nombre por defecto
        });
    }
  });
</script>
@endsection 