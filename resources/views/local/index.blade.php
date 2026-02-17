@extends('layouts.app')
@section('title') Locales y Depósitos @endsection
@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-map-marker"></i> Locales</h1>
      <p>Gestión de Tiendas y Depósitos | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item">Configuración</li>
      <li class="breadcrumb-item">Locales</li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head" id="indicators">Listado de Ubicaciones
            <a class="btn btn-primary icon-btn pull-right" href="{{ url('local/create') }}"><i class="fa fa-plus"></i> Registrar Local</a>
          </h2>
        </div>
        @include('layouts.partials.flash-messages')
      </div>
    </div>

    <div class="tile-body">
      <div class="table-responsive">
        <table class="table table-hover table-bordered" id="sampleTable">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Tipo</th> {{-- Nueva Columna --}}
              <th class="text-center">Estado</th>
              <th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @foreach($local as $key)
            <tr>
              <td><strong>{{ $key->nombre }}</strong></td>
              <td>
                @if($key->tipo == 'DEPOSITO')
                  <span class="badge badge-primary"><i class="fa fa-database"></i> DEPOSITO</span>
                @else
                  <span class="badge badge-info"><i class="fa fa-shopping-cart"></i> TIENDA LOCAL</span>
                @endif
              </td>
              <td class="text-center">
                @if($key->estado == "Activo")
                  <span class="badge badge-success">Activo</span>
                @else
                  <span class="badge badge-danger">Inactivo</span>
                @endif
              </td>
              <td class="text-center">
                <div class="btn-group">
                  <a href="{{ route('local.edit', $key->id) }}" class="btn btn-info btn-sm" title="Editar"><i class="fa fa-edit"></i></a>
                  
                  {{-- Botón Cambiar Estado --}}
                  <button class="btn btn-secondary btn-sm" onclick="prepararEstado('{{ $key->id }}', '{{ $key->estado }}')" data-toggle="modal" data-target="#modalEstado" title="Cambiar Estado">
                    <i class="fa fa-toggle-on"></i>
                  </button>

                  <button class="btn btn-danger btn-sm" onclick="eliminar('{{ $key->id }}')" data-toggle="modal" data-target="#eliminar_Local" title="Eliminar">
                    <i class="fa fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

{{-- MODAL ELIMINAR --}}
<div class="modal fade" id="eliminar_Local" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Eliminar Ubicación</h5>
        <button class="close text-white" type="button" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form action="{{ route('local.destroy', 0) }}" method="POST" id="formEliminar">
        @csrf @method('DELETE')
        <div class="modal-body text-center">
          <i class="fa fa-exclamation-triangle fa-3x text-warning mb-3"></i>
          <h4>¿Estás seguro?</h4>
          <p>Esta acción eliminará el local y podría afectar los registros de inventario asociados.</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-danger" type="submit">Confirmar Eliminación</button>
          <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- MODAL CAMBIAR ESTADO (Ajustado a tu controlador) --}}
<div class="modal fade" id="modalEstado" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
              <h5 class="modal-title"><i class="fa fa-refresh"></i> Actualizar Estado</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('local.cambiar_estado') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="id_local_estado" name="id_local">
                    <p id="texto-estado"></p>
                    <div class="form-group">
                        <label>Seleccione nuevo estado:</label>
                        <select name="estado" class="form-control" required>
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
  function eliminar(id) {
    let url = "{{ route('local.destroy', ':id') }}";
    url = url.replace(':id', id);
    $("#formEliminar").attr('action', url);
  }

  function prepararEstado(id, estadoActual) {
    $("#id_local_estado").val(id);
    let texto = estadoActual == 'Activo' ? "El local está actualmente ACTIVO." : "El local está actualmente INACTIVO.";
    $("#texto-estado").text(texto);
  }
</script>
@endsection