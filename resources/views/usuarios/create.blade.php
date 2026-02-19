@extends('layouts.app')

@section('title') Registro de Usuario @endsection

@section('content')
<main class="app-content">
  {{-- Verificación de permiso única para SuperAdmin --}}
  @cannot('gestionar-usuarios')
    <div class="tile text-center">
        <h1 class="text-danger"><i class="fa fa-lock"></i> Acceso Restringido</h1>
        <p>No tienes permisos para registrar nuevos usuarios en el sistema.</p>
        <a href="{{ route('home') }}" class="btn btn-primary">Volver al inicio</a>
    </div>
  @else
  <div class="app-title">
    <div>
      <h1><i class="fa fa-user-plus"></i> Usuarios</h1>
      <p>Gestión de Personal | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('usuarios.index') }}">Usuarios</a></li>
      <li class="breadcrumb-item"><a href="#">Registro</a></li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head" id="indicators">Nuevo Usuario</h2>
        </div><br>
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
          <h4>Datos de Acceso <small>Todos los campos (<b style="color: red;">*</b>) son requeridos.</small></h4>
          <div class="tile-body">
            <form action="{{ route('usuarios.store') }}" method="POST" name="registrar_usuario">
              @csrf
              <div class="row">
                {{-- Nombre Completo --}}
                <div class="col-md-6">                  
                  <div class="form-group">
                    <label class="control-label">Nombre Completo <b style="color: red;">*</b></label>
                    <input class="form-control @error('name') is-invalid @enderror" 
                           type="text" name="name" required value="{{ old('name') }}" placeholder="Ej: Juan Pérez">
                  </div>
                </div>

                {{-- Cédula --}}
                <div class="col-md-3">                  
                  <div class="form-group">
                    <label class="control-label">Cédula <b style="color: red;">*</b></label>
                    <input class="form-control @error('cedula') is-invalid @enderror" 
                           type="text" name="cedula" required value="{{ old('cedula') }}" placeholder="V-12345678">
                  </div>
                </div>

                {{-- Teléfono --}}
                <div class="col-md-3">                  
                  <div class="form-group">
                    <label class="control-label">Teléfono</label>
                    <input class="form-control" type="text" name="telefono" value="{{ old('telefono') }}" placeholder="0412-0000000">
                  </div>
                </div>
              </div>

              <div class="row">
                {{-- Email --}}
                <div class="col-md-6">                  
                  <div class="form-group">
                    <label class="control-label">Correo Electrónico / Usuario <b style="color: red;">*</b></label>
                    <input class="form-control @error('email') is-invalid @enderror" 
                           type="email" name="email" required value="{{ old('email') }}" placeholder="ejemplo@yermotos.com">
                  </div>
                </div>

                {{-- Password --}}
                <div class="col-md-6">                  
                  <div class="form-group">
                    <label class="control-label">Contraseña <b style="color: red;">*</b></label>
                    <input class="form-control @error('password') is-invalid @enderror" 
                           type="password" name="password" required placeholder="Mínimo 6 caracteres">
                  </div>
                </div>
              </div>

              <hr>

              <div class="row">
                {{-- Selección de Rol --}}
                <div class="col-md-6">                  
                  <div class="form-group">
                    <label class="control-label">Rol del Sistema <b style="color: red;">*</b></label>
                    <select name="role" class="form-control" required>
					    <option value="">Seleccione un rol...</option>
					    
					    <option value="{{ \App\Models\User::ROLE_VENDEDOR }}" @selected(old('role') == \App\Models\User::ROLE_VENDEDOR)>
					        Vendedor (Ventas en tienda)
					    </option>

					    <option value="{{ \App\Models\User::ROLE_ALMACENISTA }}" @selected(old('role') == \App\Models\User::ROLE_ALMACENISTA)>
					        Almacenista (Gestión Global)
					    </option>

					    <option value="{{ \App\Models\User::ROLE_ENCARGADO }}" @selected(old('role') == \App\Models\User::ROLE_ENCARGADO)>
					        Encargado (Jefe de Sede)
					    </option>

					    <option value="{{ \App\Models\User::ROLE_SUPERADMIN }}" @selected(old('role') == \App\Models\User::ROLE_SUPERADMIN)>
					        Administrador (Dueño)
					    </option>
					</select>
                  </div>
                </div>

                {{-- Selección de Local --}}
                <div class="col-md-6">                  
                  <div class="form-group">
                    <label class="control-label">Sede / Local Asignado <b style="color: red;">*</b></label>
                    <select name="id_local" class="form-control" required>
                        <option value="">Seleccione el local donde labora...</option>
                        @foreach($locales as $local)
                            <option value="{{ $local->id }}" {{ old('id_local') == $local->id ? 'selected' : '' }}>
                                {{ $local->nombre }} ({{ $local->tipo }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Incluso el SuperAdmin debe estar vinculado a una sede principal.</small>
                  </div>
                </div>
              </div>

              <div class="tile-footer">
                <button class="btn btn-primary" type="submit">
                    <i class="fa fa-fw fa-lg fa-check-circle"></i> Registrar Usuario
                </button>
                &nbsp;&nbsp;&nbsp;
                <a class="btn btn-secondary" href="{{ route('usuarios.index') }}">
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