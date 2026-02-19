@extends('layouts.app')

@section('title') Mi Perfil @endsection

@section('content')
<main class="app-content">
  {{-- 
    A diferencia del registro, aquí no usamos la Gate 'gestionar-usuarios' 
    porque cualquier usuario logueado debe poder ver su propio perfil.
  --}}
  <div class="app-title">
    <div>
      <h1><i class="fa fa-user-circle"></i> Mi Perfil</h1>
      <p>Configuración de cuenta personal | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item">Usuario</li>
      <li class="breadcrumb-item"><a href="#">Perfil</a></li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head" id="indicators">Información de Usuario</h2>
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
          <h4>Mis Datos Actuales <small>Los campos marcados con (<b style="color: red;">*</b>) son obligatorios.</small></h4>
          <div class="tile-body">
            {{-- Apuntamos a la ruta del PerfilController --}}
            <form action="{{ route('perfil.update') }}" method="POST" enctype="multipart/form-data">
              @csrf
              @method('PUT')

              <div class="row">
                {{-- Nombre Completo --}}
                <div class="col-md-6">                  
                  <div class="form-group">
                    <label class="control-label">Nombre Completo <b style="color: red;">*</b></label>
                    <input class="form-control" type="text" name="name" required value="{{ old('name', $user->name) }}">
                  </div>
                </div>

                {{-- Cédula --}}
                <div class="col-md-3">                  
                  <div class="form-group">
                    <label class="control-label">Cédula <b style="color: red;">*</b></label>
                    <input class="form-control" type="text" name="cedula" required value="{{ old('cedula', $user->cedula) }}">
                  </div>
                </div>

                {{-- Teléfono --}}
                <div class="col-md-3">                  
                  <div class="form-group">
                    <label class="control-label">Teléfono</label>
                    <input class="form-control" type="text" name="telefono" value="{{ old('telefono', $user->telefono) }}">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4 text-center">
                    {{-- Mostramos la foto actual o una por defecto --}}
                    <img src="{{ $user->foto ? asset('fotosperfil/'.$user->foto) : asset('images/user-default.png') }}" 
                         class="img-thumbnail" style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%;">
                    
                    <div class="form-group mt-2">
                        <label>Cambiar Foto</label>
                        <input type="file" name="foto" class="form-control-file">
                    </div>
                </div>
                
                <div class="col-md-8">
                    {{-- Aquí van tus inputs de Name, Email, etc. --}}
                </div>
            </div>
              <div class="row">
                {{-- Email --}}
                <div class="col-md-6">                  
                  <div class="form-group">
                    <label class="control-label">Correo Electrónico / Usuario <b style="color: red;">*</b></label>
                    <input class="form-control" type="email" name="email" required value="{{ old('email', $user->email) }}">
                  </div>
                </div>

                {{-- Cambio de Password --}}
                <div class="col-md-6">                  
                  <div class="form-group">
                    <label class="control-label">Cambiar Contraseña <small class="text-primary">(Dejar en blanco para mantener la actual)</small></label>
                    <input class="form-control" type="password" name="password" placeholder="Mínimo 6 caracteres">
                  </div>
                </div>
              </div>

              <hr>

              {{-- Información No Editable para el Usuario (Solo lectura) --}}
              <div class="row">
                <div class="col-md-6">                  
                  <div class="form-group">
                    <label class="control-label">Rol Asignado</label>
                    <input class="form-control" type="text" value="{{ strtoupper($user->role) }}" readonly style="background-color: #e9ecef;">
                    <small class="form-text text-muted">El rol solo puede ser cambiado por un Administrador.</small>
                  </div>
                </div>

                <div class="col-md-6">                  
                  <div class="form-group">
                    <label class="control-label">Sede / Local Laboral</label>
                    <input class="form-control" type="text" value="{{ $user->localActual()->nombre ?? 'SIN SEDE' }}" readonly style="background-color: #e9ecef;">
                    <small class="form-text text-muted">Su sede está vinculada a su contrato de trabajo.</small>
                  </div>
                </div>
              </div>

              <div class="tile-footer">
                <button class="btn btn-primary" type="submit">
                    <i class="fa fa-fw fa-lg fa-check-circle"></i> Actualizar Mis Datos
                </button>
                &nbsp;&nbsp;&nbsp;
                <a class="btn btn-secondary" href="{{ route('home') }}">
                    <i class="fa fa-fw fa-lg fa-times-circle"></i> Cancelar
                </a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
@endsection