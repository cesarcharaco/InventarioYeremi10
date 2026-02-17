@extends('layouts.app')
@section('title') Registro de Local @endsection
@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-map-marker"></i> Local</h1>
      <p>Sistema de Inventario | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ url('local') }}">Local</a></li>
      <li class="breadcrumb-item">Registro de Local</li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head">Nueva Ubicación</h2>
        </div><br>
        <div class="basic-tb-hd text-center">            
            @include('layouts.partials.flash-messages')
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="tile">
          <h4>Datos del Local/Depósito <small>Todos los campos (<b style="color: red;">*</b>) son requeridos.</small></h4>
          <hr>
          <div class="tile-body">
            <form action="{{route('local.store')}}" method="POST" name="registrar_local" data-parsley-validate>
              @csrf
              <div class="row">
                {{-- Campo Nombre --}}
                <div class="col-md-4">                  
                  <div class="form-group">
                    <label class="control-label">Nombre de Ubicación <b style="color: red;">*</b></label>
                    <input class="form-control" type="text" placeholder="Ej: Depósito Central / Tienda Principal" name="nombre" id="nombre" required="required" value="{{ old('nombre') }}">
                  </div>
                </div>

                {{-- Campo TIPO (Vital para el stock) --}}
                <div class="col-md-4">
                  <div class="form-group">
                    <label for="tipo"><b>Tipo de Ubicación</b> <b style="color: red;">*</b></label>
                    <select name="tipo" id="tipo" class="form-control" required="required">
                        <option value="LOCAL">TIENDA (LOCAL)</option>
                        <option value="DEPOSITO">DEPÓSITO</option>
                    </select>
                    <small class="text-muted">Define si es punto de venta o almacenamiento.</small>
                  </div>
                </div>

                {{-- Campo Estado --}}
                <div class="col-md-4">
                  <div class="form-group">
                      <label for="estado"><b>Estado Inicial</b> <b style="color: red;">*</b></label>
                      <select name="estado" id="estado" class="form-control" required="required">
                          <option value="Activo">Activo</option>
                          <option value="Inactivo">Inactivo</option>                        
                      </select>
                  </div>
                </div> 
              </div>
          </div>
          <div class="tile-footer">
            <button class="btn btn-primary" type="submit"><i class="fa fa-fw fa-lg fa-check-circle"></i> Registrar Ubicación</button>
            &nbsp;&nbsp;&nbsp;
            <a class="btn btn-secondary" href="{{ url('local') }}"><i class="fa fa-fw fa-lg fa-times-circle"></i> Volver</a>
          </div>
            </form>
        </div>
      </div>
    </div>
  </div>
</main>
@endsection