@extends('layouts.app')

@section('title') Editar Usuario @endsection

@section('content')
<main class="app-content">
  {{-- Verificación de permiso: Solo SuperAdmin gestiona usuarios --}}
  @cannot('gestionar-usuarios')
    <div class="tile text-center">
        <h1 class="text-danger"><i class="fa fa-lock"></i> Acceso Restringido</h1>
        <p>No tienes permisos para editar usuarios en el sistema.</p>
        <a href="{{ route('home') }}" class="btn btn-primary">Volver al inicio</a>
    </div>
  @else
  <div class="app-title">
    <div>
      <h1><i class="fa fa-edit"></i> Editar Usuario</h1>
      <p>Modificando perfil de: <strong>{{ $user->name }}</strong></p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('usuarios.index') }}">Usuarios</a></li>
      <li class="breadcrumb-item">Edición</li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="basic-tb-hd text-center">            
            @include('layouts.partials.flash-messages')
            
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul style="margin-bottom: 0; text-align: left;">
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
          <h4>Datos Actuales <small>Campos con (<b style="color: red;">*</b>) son obligatorios.</small></h4>
          <div class="tile-body">
            <form action="{{ route('usuarios.update', $user->id) }}" method="POST">
              @csrf
              @method('PUT')
              
              <div class="row">
                <div class="col-md-6">                  
                  <div class="form-group">
                    <label class="control-label">Nombre Completo <b style="color: red;">*</b></label>
                    <input class="form-control" type="text" name="name" required value="{{ old('name', $user->name) }}">
                  </div>
                </div>

                <div class="col-md-3">                  
                  <div class="form-group">
                    <label class="control-label">Cédula <b style="color: red;">*</b></label>
                    <input class="form-control" type="text" name="cedula" required value="{{ old('cedula', $user->cedula) }}">
                  </div>
                </div>

                <div class="col-md-3">                  
                  <div class="form-group">
                    <label class="control-label">Teléfono</label>
                    <input class="form-control" type="text" name="telefono" value="{{ old('telefono', $user->telefono) }}">
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">                  
                  <div class="form-group">
                    <label class="control-label">Correo / Usuario <b style="color: red;">*</b></label>
                    <input class="form-control" type="email" name="email" required value="{{ old('email', $user->email) }}">
                  </div>
                </div>

                <div class="col-md-6">                  
                  <div class="form-group">
                    <label class="control-label">Nueva Contraseña <small class="text-primary">(Dejar en blanco para mantener la actual)</small></label>
                    <input class="form-control" type="password" name="password" placeholder="Mínimo 6 caracteres">
                  </div>
                </div>
              </div>

              <hr>

              <div class="row">
                {{-- Selección de Rol --}}
                <div class="col-md-5">                  
                  <div class="form-group">
                    <label class="control-label">Rol del Sistema <b style="color: red;">*</b></label>
                    <select name="role" class="form-control" required>
                        <option value="{{ \App\Models\User::ROLE_VENDEDOR }}" @selected(old('role', $user->role) == \App\Models\User::ROLE_VENDEDOR)>Vendedor</option>
                        <option value="{{ \App\Models\User::ROLE_ALMACENISTA }}" @selected(old('role', $user->role) == \App\Models\User::ROLE_ALMACENISTA)>Almacenista</option>
                        <option value="{{ \App\Models\User::ROLE_ENCARGADO }}" @selected(old('role', $user->role) == \App\Models\User::ROLE_ENCARGADO)>Encargado</option>
                        <option value="{{ \App\Models\User::ROLE_SUPERADMIN }}" @selected(old('role', $user->role) == \App\Models\User::ROLE_SUPERADMIN)>Administrador (SuperAdmin)</option>
                    </select>
                  </div>
                </div>

                {{-- Selección de Local corregida --}}
                <div class="col-md-5">                  
                  <div class="form-group">
                    <label class="control-label">Sede / Local Asignado <b style="color: red;">*</b></label>
                    <select name="id_local" class="form-control" required>
                        @foreach($locales as $local)
                            <option value="{{ $local->id }}" @selected(old('id_local', $localActualId) == $local->id)>
                                {{ $local->nombre }} ({{ $local->tipo }})
                            </option>
                        @endforeach
                    </select>
                  </div>
                </div>

                {{-- Estado Activo/Inactivo (Bloqueo de acceso) --}}
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="control-label">Estado de Acceso</label>
                        <div class="toggle-flip">
                          <label>
                            <input type="checkbox" name="activo" @checked(old('activo', $user->activo))>
                            <span class="flip-indecator" data-toggle-on="Activo" data-toggle-off="Inactivo"></span>
                          </label>
                        </div>
                    </div>
                </div>
              </div>

              <div class="tile-footer">
                <button class="btn btn-primary" type="submit">
                    <i class="fa fa-fw fa-lg fa-check-circle"></i> Guardar Cambios
                </button>
                &nbsp;&nbsp;&nbsp;
                <a class="btn btn-secondary" href="{{ route('usuarios.index') }}">
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