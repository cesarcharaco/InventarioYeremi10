@extends('layouts.app')

@section('title') Editar Cliente: {{ $cliente->nombre }} @endsection

@section('content')
<main class="app-content">
  {{-- Verificación de permiso para editar clientes --}}
  @cannot('gestionar-clientes')
    <div class="tile text-center">
        <h1 class="text-danger"><i class="fa fa-lock"></i> Acceso Restringido</h1>
        <p>No tienes permisos para modificar información de clientes.</p>
        <a href="{{ route('clientes.index') }}" class="btn btn-primary">Volver al listado</a>
    </div>
  @else
  <div class="app-title">
    <div>
      <h1><i class="fa fa-edit"></i> Clientes</h1>
      <p>Editar Información | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('clientes.index') }}">Clientes</a></li>
      <li class="breadcrumb-item"><a href="#">Edición</a></li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head" id="indicators">Modificar Cliente: {{ $cliente->nombre }}</h2>
        </div><br>
        <div class="basic-tb-hd text-center">            
            @include('layouts.partials.flash-messages')
            
            {{-- Errores de validación --}}
            @if ($errors->any())
                <div class="alert alert-danger text-left">
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
          <h4>Actualizar Datos <small>Campos con (<b style="color: red;">*</b>) son requeridos.</small></h4>
          <div class="tile-body">
            <form action="{{ route('clientes.update', $cliente->id) }}" method="POST" name="editar_cliente">
              @csrf
              @method('PUT')
              
              <div class="row">
                {{-- Identificación --}}
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label">Cédula / RIF <b style="color: red;">*</b></label>
                    <input class="form-control @error('identificacion') is-invalid @enderror" 
                           type="text" name="identificacion" required 
                           value="{{ old('identificacion', $cliente->identificacion) }}">
                  </div>
                </div>

                {{-- Nombre Completo --}}
                <div class="col-md-8">                  
                  <div class="form-group">
                    <label class="control-label">Nombre Completo o Razón Social <b style="color: red;">*</b></label>
                    <input class="form-control @error('nombre') is-invalid @enderror" 
                           type="text" name="nombre" required 
                           value="{{ old('nombre', $cliente->nombre) }}">
                  </div>
                </div>
              </div>

              <div class="row">
                {{-- Teléfono --}}
                <div class="col-md-4">                  
                  <div class="form-group">
                    <label class="control-label">Teléfono de Contacto <b style="color: red;">*</b></label>
                    <input class="form-control @error('telefono') is-invalid @enderror" 
                           type="text" name="telefono" required 
                           value="{{ old('telefono', $cliente->telefono) }}">
                  </div>
                </div>

                {{-- Límite de Crédito --}}
                <div class="col-md-4">                  
                  <div class="form-group">
                    <label class="control-label">Límite de Crédito (USD) <b style="color: red;">*</b></label>
                    <input class="form-control @error('limite_credito') is-invalid @enderror" 
                           type="number" step="0.01" name="limite_credito" required 
                           value="{{ old('limite_credito', $cliente->limite_credito) }}">
                  </div>
                </div>

                {{-- Sede de Registro --}}
                <div class="col-md-4">                  
                  <div class="form-group">
                    <label class="control-label">Sede / Local <b style="color: red;">*</b></label>
                    <select name="id_local" class="form-control" required>
                        @foreach($locales as $local)
                            <option value="{{ $local->id }}" {{ (old('id_local', $cliente->id_local) == $local->id) ? 'selected' : '' }}>
                                {{ $local->nombre }}
                            </option>
                        @endforeach
                    </select>
                  </div>
                </div>
              </div>

              <div class="row">
                {{-- Dirección --}}
                <div class="col-md-12">                  
                  <div class="form-group">
                    <label class="control-label">Dirección de Domicilio / Fiscal</label>
                    <textarea class="form-control" name="direccion" rows="2">{{ old('direccion', $cliente->direccion) }}</textarea>
                  </div>
                </div>
              </div>

              <div class="tile-footer">
                <button class="btn btn-primary" type="submit">
                    <i class="fa fa-fw fa-lg fa-check-circle"></i> Guardar Cambios
                </button>
                &nbsp;&nbsp;&nbsp;
                <a class="btn btn-secondary" href="{{ route('clientes.index') }}">
                    <i class="fa fa-fw fa-lg fa-times-circle"></i> Volver al listado
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