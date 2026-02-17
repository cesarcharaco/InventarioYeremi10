@extends('layouts.app')
@section('title') Actualización de Local @endsection
@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-edit"></i> Local</h1>
      <p>Sistema de Inventario | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ url('local') }}">Local</a></li>
      <li class="breadcrumb-item">Actualización</li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head">Editar: {{ $local->nombre }}</h2>
        </div><br>
        @include('layouts.partials.flash-messages')
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="tile">
          <h4>Modificar Datos <small>Campos con (<b style="color: red;">*</b>) obligatorios.</small></h4>
          <hr>
          <div class="tile-body">
            {!! Form::model($local, ['route' => ['local.update', $local->id], 'method' => 'PUT', 'data-parsley-validate']) !!}
              @csrf
              <div class="row">
                {{-- Nombre --}}
                <div class="col-md-4">                  
                  <div class="form-group">
                    <label class="control-label">Nombre <b style="color: red;">*</b></label>
                    <input class="form-control" type="text" name="nombre" id="nombre" required value="{{ $local->nombre }}">
                  </div>
                </div>

                {{-- TIPO (Nuevo campo para editar) --}}
                <div class="col-md-4">
                  <div class="form-group">
                    <label for="tipo"><b>Tipo de Ubicación</b> <b style="color: red;">*</b></label>
                    <select name="tipo" id="tipo" class="form-control" required>
                        <option value="LOCAL" {{ $local->tipo == 'LOCAL' ? 'selected' : '' }}>TIENDA (LOCAL)</option>
                        <option value="DEPOSITO" {{ $local->tipo == 'DEPOSITO' ? 'selected' : '' }}>DEPÓSITO</option>
                    </select>
                  </div>
                </div>

                {{-- Estado --}}
                <div class="col-md-4">
                  <div class="form-group">
                      <label for="estado"><b>Estado</b> <b style="color: red;">*</b></label>
                      <select name="estado" id="estado" class="form-control" required>
                          <option value="Activo" {{ $local->estado == 'Activo' ? 'selected' : '' }}>Activo</option>
                          <option value="Inactivo" {{ $local->estado == 'Inactivo' ? 'selected' : '' }}>Inactivo</option>                        
                      </select>
                  </div>
                </div> 
              </div>
          </div>
          <div class="tile-footer">
            <button class="btn btn-primary" type="submit"><i class="fa fa-check-circle"></i> Guardar Cambios</button>
            &nbsp;&nbsp;&nbsp;
            <a class="btn btn-secondary" href="{{ url('local') }}"><i class="fa fa-times-circle"></i> Cancelar</a>
          </div>
            {!! Form::close() !!}
        </div>
      </div>
    </div>
  </div>
</main>
@endsection