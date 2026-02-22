@extends('layouts.app')

@section('title') Registro de Categoría @endsection

@section('content')
@include('layouts.partials.flash-messages')
<main class="app-content">
  {{-- Verificación de permiso para crear --}}
  @cannot('crear-configuracion')
    <div class="tile text-center">
        <h1 class="text-danger"><i class="fa fa-lock"></i> Acceso Restringido</h1>
        <p>No tienes permisos para registrar nuevas categorías en el sistema.</p>
        <a href="{{ route('categorias.index') }}" class="btn btn-primary">Volver al listado</a>
    </div>
  @else
  <div class="app-title">
    <div>
      <h1><i class="fa fa-tags"></i> Categorías</h1>
      <p>Sistema Administrativo | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('categorias.index') }}">Categorías</a></li>
      <li class="breadcrumb-item"><a href="#">Registro</a></li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head" id="indicators">Categorías</h2>
        </div><br>
        <div class="basic-tb-hd text-center">            
            @include('layouts.partials.flash-messages')
            
            {{-- Errores de validación --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul style="margin-bottom: 0;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="tile">
          <h4>Nueva Categoría <small>Todos los campos (<b style="color: red;">*</b>) son requeridos.</small></h4>
          <div class="tile-body">
            <form action="{{ route('categorias.store') }}" method="POST" name="registrar_categoria" data-parsley-validate>
              @csrf
              <div class="row">
                <div class="col-md-12">                  
                  <div class="form-group">
                    <label class="control-label">Nombre de la Categoría <b style="color: red;">*</b></label>
                    <input class="form-control @error('categoria') is-invalid @enderror" 
                           type="text" 
                           placeholder="Ej: Accesorios, Frenos, Motor..." 
                           name="categoria" 
                           id="categoria" 
                           required="required" 
                           value="{{ old('categoria') }}">
                    
                    @error('categoria')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                  </div>
                </div>
              </div>

              <div class="tile-footer">
                <button class="btn btn-primary" type="submit">
                    <i class="fa fa-fw fa-lg fa-check-circle"></i> Registrar
                </button>
                &nbsp;&nbsp;&nbsp;
                <a class="btn btn-secondary" href="{{ route('categorias.index') }}">
                    <i class="fa fa-fw fa-lg fa-times-circle"></i> Volver
                </a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endcannot
</main>
@endsection