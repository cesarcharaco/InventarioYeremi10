@extends('layouts.app')
@section('title') Préstamos @endsection
@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-th-list"></i> Inventario</h1>
      <p>Sistema de Inventario | Licancabur</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="">Inventario</a></li>
      <li class="breadcrumb-item"><a href="">Préstamos</a></li>
    </ul>
  </div>
  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head" id="indicators"> &nbsp;&nbsp;Préstamos
            <a class="btn btn-primary icon-btn pull-right" href="{{ route('prestamos.create') }}"><i class="fa fa-plus"></i>Registrar Préstamo</a>
            <a class="btn btn-info icon-btn pull-left" href="{{ route('prestamos.historial') }}"><i class="fa fa-plus"></i>Historial</a>
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
                  <th>Solicitante</th>
                  <th>Rut</th>
                  <th>Insumo</th>
                  <th>Serial</th>
                  <th>Tipo</th>
                  <th>Cantidad</th>
                  <th>Fecha préstamo</th>
                  <th>Fecha de entrega</th>
                  <th>Status</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                @foreach($prestamos as $key)
                <tr>
                  <td>{{ $key->nombres }}</td>
                  <td>{{ $key->rut }}</td>
                  <td>{{ $key->producto }} ({{ $key->descripcion }})</td>
                  <td>{{ $key->serial }}</td>
                  <td>{{ $key->tipo }}</td>
                  <td>{{ $key->cantidad }}</td>
                  <td>{{ $key->fecha_prestamo }}</td>
                  <td>{{ $key->fecha_devuelto }}</td>
                  <td>
                    @if($key->status=="Sin Devolver")
                      <span class="badge badge-danger">Sin Devolver</span></td>
                    @elseif($key->status=="Devuelto")
                      <span class="badge badge-success">Devuelto</span></td>
                    @elseif($key->status=="No Aplica")
                      <span class="badge badge-info">No Aplica</span></td>
                    @endif
                    
                  <td>
                    <a href="{{ route('prestamos.edit',$key->id) }}" class="btn btn-info btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="Editar Préstamo"><i class="fa fa-edit"></i></a>
                    <button class="btn btn-danger btn-sm" onclick="eliminar_prestamo({{ $key->id }})" data-toggle="modal" data-target="#eliminar_prestamo"><i class="fa fa-trash"></i></button>
                    @if($key->tipo=="Prestar")
                    <a href="#" data-toggle="tooltip" class="btn btn-secondary btn-sm" data-placement="top" title="Cambiar status del Préstamo" onclick="status('{{ $key->id }}')" id="cambiar_status">
                    <i class="fa fa-lock" data-toggle="modal" data-target="#myModaltwo"></i>
                    </a>
                    @endif
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

<div class="bs-component">
  <div class="modal" id="eliminar_prestamo">
    <div class="modal-dialog  modal-dialog_1 " role="document">
      <div class="modal-content">
        {!! Form::open(['route' => ['prestamos.destroy',1033], 'method' => 'DELETE']) !!}
          <div class="modal-header">
            <h5 class="modal-title"><i class="fa fa-trash"></i> Eliminar Préstamo</h5>
            <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
          </div>
          <div class="modal-body">
            <p>¿Estas seguro que desea eliminar a este préstamo?</p>
          </div>
          <input type="hidden" name="id_prestamo" id="id_prestamo1">
          <div class="modal-footer">
            <button class="btn btn-danger" type="submit">Eliminar</button>
            <button class="btn btn-secondary" type="button" data-dismiss="modal">Cerrar</button>
          </div>          
        {!! Form::close() !!}
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="myModaltwo" role="dialog">
    <div class="modal-dialog modal-dialog_1 modal-md">
        <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="fa fa-lock"></i> Cambiar Status de Préstamo</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            {!! Form::open(['route' => ['prestamos.cambiar_status'], 'method' => 'POST', 'name' => 'cambiar_status', 'id' => 'cambiar_status', 'data-parsley-validate']) !!}
            @csrf
            <div class="modal-body">
                {{-- <h2>Cambiar de status del Solicitante</h2> --}}
                <p>¿Estas seguro que desea cambiar de status a este préstamo?.</p>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="status"><b>Status</b> <b style="color: red;">*</b></label>
                            <input type="hidden" id="id_prestamo2" name="id_prestamo">
                            <select name="status" id="status" class="form-control" required="required">
                                <option value="Devuelto">Devuelto</option>
                                <option value="Sin Devolver">Sin Devolver</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-default">Cambiar status</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script type="text/javascript">
  function eliminar_prestamo(id_prestamo) {
    $("#id_prestamo1").val(id_prestamo);
  }

  function status(id_prestamo) {
    $("#id_prestamo2").val(id_prestamo);
  }

</script>
@endsection