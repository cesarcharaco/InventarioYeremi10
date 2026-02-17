@extends('layouts.app')

@section('title') Editar Categoría @endsection

@section('content')
<main class="app-content">
  {{-- Verificación de permiso para editar --}}
  @cannot('editar-configuracion')
    <div class="tile text-center">
        <h1 class="text-danger"><i class="fa fa-lock"></i> Acceso Restringido</h1>
        <p>No tienes privilegios suficientes para modificar categorías existentes.</p>
        <a href="{{ route('categorias.index') }}" class="btn btn-primary">Volver al listado</a>
    </div>
  @else
  <div class="app-title">
    <div>
      <h1><i class="fa fa-edit"></i> Editar Categoría</h1>
      <p>Sistema Administrativo | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('categorias.index') }}">Categorías</a></li>
      <li class="breadcrumb-item"><a href="#">Editar</a></li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head">Categoría: {{ $categoria->categoria }}</h2>
        </div><br>
        @include('layouts.partials.flash-messages')
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="tile">
          <div class="tile-body">
            {{-- Abrimos el formulario con el método PUT para actualización --}}
            <form action="{{ route('categorias.update', $categoria->id) }}" method="POST">
              @csrf
              @method('PUT')

              <div class="row">
                <div class="col-md-12">                  
                  <div class="form-group">
                    <label class="control-label">Nombre de la Categoría <b style="color: red;">*</b></label>
                    <input class="form-control @error('categoria') is-invalid @enderror" 
                           type="text" 
                           name="categoria" 
                           id="categoria" 
                           required 
                           value="{{ old('categoria', $categoria->categoria) }}">
                    
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
                    <i class="fa fa-fw fa-lg fa-check-circle"></i> Actualizar
                </button>
                &nbsp;&nbsp;&nbsp;
                <a class="btn btn-secondary" href="{{ route('categorias.index') }}">
                    <i class="fa fa-fw fa-lg fa-times-circle"></i> Cancelar
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