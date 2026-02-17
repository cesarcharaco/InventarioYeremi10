@extends('layouts.app')
@section('title') Incidencias @endsection
@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-th-list"></i> Inventario</h1>
      <p>Sistema Administrativo | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="">SAYER</a></li>
      <li class="breadcrumb-item"><a href="">Incidencias</a></li>
    </ul>
  </div>
  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head" id="indicators">&nbsp;&nbsp;Incidencias
            {{-- Todos pueden registrar incidencias según Gate 'registrar-incidencia' --}}
            @can('registrar-incidencia')
            <a class="btn btn-primary icon-btn pull-right" href="{{ route('incidencias.create') }}"><i class="fa fa-plus"></i>Registrar Incidencia</a>
             @endcan
              {{-- Solo Admin y SuperAdmin ven el botón de Historial Total --}}
            @can('ver-historial-total')
            <a class="btn btn-info icon-btn pull-left" href="{{ route('incidencias.historial') }}"><i class="fa fa-plus"></i>Historial</a>
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
            <table class="table table-hover table-bordered" id="sampleTable">
              <thead>
                <tr>
                  <th>Insumo</th>
                  <th>Serial</th>
                  <th>Tipo</th>
                  <th>Cantidad</th>
                  <th>Fecha Incidencia</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                @foreach($incidencias as $key)
                <tr>
                  <td>{{ $key->producto }} ({{ $key->descripcion }})</td>
                  <td>{{ $key->serial }}</td>
                  <td>{{ $key->tipo }}</td>
                  <td>{{ $key->cantidad }}</td>
                  <td>{{ $key->fecha_incidencia }}</td>
                    
                  <td>
                    <a href="{{ route('incidencias.edit',$key->id) }}" class="btn btn-info btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="Editar Incidencia"><i class="fa fa-edit"></i></a>
                    {{-- Eliminar: SOLO el SuperAdmin puede borrar rastros del historial --}}
                    @can('anular-historial')
                    <button class="btn btn-danger btn-sm" onclick="eliminar_incidencia({{ $key->id }})" data-toggle="modal" data-target="#eliminar_incidencia"><i class="fa fa-trash"></i></button>
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
</main>
{{-- MODAL ELIMINAR PROTEGIDO POR CAN --}}
@can('anular-historial')
<div class="bs-component">
  <div class="modal fade" id="eliminar_incidencia">
    <div class="modal-dialog modal-dialog_1" role="document">
      <div class="modal-content">
        {!! Form::open(['route' => ['incidencias.destroy',1033], 'method' => 'DELETE']) !!}
          <div class="modal-header">
            <h5 class="modal-title"><i class="fa fa-trash"></i> Eliminar Incidencia</h5>
            <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
          </div>
          <div class="modal-body">
            <p>¿Estas seguro que desea eliminar a esta incidencia?</p>
          </div>
          <input type="hidden" name="id_incidencia" id="id_incidencia">
          <div class="modal-footer">
            <button class="btn btn-danger" type="submit">Eliminar</button>
            <button class="btn btn-secondary" type="button" data-dismiss="modal">Cerrar</button>
          </div>          
        {!! Form::close() !!}
      </div>
    </div>
  </div>
</div>
@endcan

@endsection
@section('scripts')
<script type="text/javascript">
  function eliminar_incidencia(id_incidencia) {
    $("#id_incidencia").val(id_incidencia);
  }

</script>
@endsection
