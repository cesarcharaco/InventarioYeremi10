@extends('layouts.app')

@section('title') Registro de Cliente @endsection

@section('content')
<main class="app-content">
  {{-- Verificación de permiso para crear clientes (SuperAdmin, Encargado, Vendedor) --}}
 @if(auth()->check() && !auth()->user()->can('gestionar-clientes'))
    <div class="tile text-center">
        <h1 class="text-danger"><i class="fa fa-lock"></i> Acceso Restringido</h1>
        <p>No tienes permisos para registrar nuevos clientes en el sistema.</p>
        <a href="{{ route('clientes.index') }}" class="btn btn-primary">Volver al listado</a>
    </div>
  @else
  <div class="app-title">
    <div>
      <h1><i class="fa fa-users"></i> Clientes</h1>
      <p>Gestión de Cartera | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('clientes.index') }}">Clientes</a></li>
      <li class="breadcrumb-item"><a href="#">Registro</a></li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head" id="indicators">Nuevo Cliente @guest Mayorista @endguest</h2>
        </div><br>
        @guest
        <div class="alert alert-warning" role="alert" style="border-left: 5px solid #ffc107;">
            <h5 class="alert-heading"><i class="fa fa-info-circle"></i> Aviso Importante para Clientes Mayoristas</h5>
            <p class="mb-0">
                Para acceder a nuestros precios al mayor y realizar pedidos a través del sistema, es necesario registrarse. 
                <strong>Por razones de seguridad, tu cuenta quedará en estado "Pendiente" tras completar este formulario.</strong> 
                Nuestro equipo administrativo validará tus datos y, una vez activado tu usuario, te notificaremos para que puedas comenzar a realizar tus compras. 
                Gracias por confiar en Yermotors Repuestos C.A.
            </p>
        </div>
        @endguest
        <div class="basic-tb-hd text-center">            
            @include('layouts.partials.flash-messages')
            
            {{-- Errores de validación --}}
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
          <h4>Datos del Cliente <small>Los campos con (<b style="color: red;">*</b>) son requeridos.</small></h4>
          <div class="tile-body">
            <form action="{{ route('clientes.store') }}" method="POST" name="registrar_cliente">
              @csrf
              
              <div class="row">
                {{-- Identificación --}}
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label">Cédula / RIF <b style="color: red;">*</b></label>
                    <input class="form-control @error('identificacion') is-invalid @enderror" 
                           type="text" name="identificacion" required 
                           value="{{ old('identificacion') }}" placeholder="V-12345678">
                  </div>
                </div>

                {{-- Nombre Completo --}}
                <div class="col-md-8">                  
                  <div class="form-group">
                    <label class="control-label">Nombre Completo o Razón Social <b style="color: red;">*</b></label>
                    <input class="form-control @error('nombre') is-invalid @enderror" 
                           type="text" name="nombre" required 
                           value="{{ old('nombre') }}" placeholder="Ej: Juan Pérez o Inversiones J.P, C.A.">
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
                           value="{{ old('telefono') }}" placeholder="0412-1234567">
                  </div>
                </div>
                @auth
                {{-- Límite de Crédito --}}
                <div class="col-md-4">                  
                  <div class="form-group">
                    <label class="control-label">Límite de Crédito (USD) <b style="color: red;">*</b></label>
                    <input class="form-control @error('limite_credito') is-invalid @enderror" 
                           type="number" step="0.01" name="limite_credito" required 
                           value="{{ old('limite_credito', 0) }}">
                    <small class="text-muted">Monto máximo de deuda permitido.</small>
                  </div>
                </div>
                @endauth
                {{-- Sede de Registro --}}
                <div class="col-md-4">                  
                  <div class="form-group">
                    <label class="control-label">Sede / Local <b style="color: red;">*</b></label>
                    <select name="id_local" class="form-control" required>
                        <option value="">Seleccione sede...</option>
                        @foreach($locales as $local)
                            <option value="{{ $local->id }}" {{ old('id_local') == $local->id ? 'selected' : '' }}>
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
                    <textarea class="form-control" name="direccion" rows="2" placeholder="Opcional...">{{ old('direccion') }}</textarea>
                  </div>
                </div>
              </div>
              @guest
              {{-- SECCIÓN DE ACCESO AL SISTEMA --}}
              <div class="tile-body">
                <h4 class="mb-3">Datos de Acceso al Sistema</h4>
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="control-label">Correo Electrónico <b style="color: red;">*</b></label>
                      <input class="form-control" type="email" name="email" required value="{{ old('email') }}" placeholder="ejemplo@correo.com">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="control-label">Contraseña <b style="color: red;">*</b></label>
                      <div class="input-group">
                        <input class="form-control" type="password" name="password" id="password" required>
                        <div class="input-group-append">
                          <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')"><i class="fa fa-eye"></i></button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="control-label">Confirmar Contraseña <b style="color: red;">*</b></label>
                      <div class="input-group">
                        <input class="form-control" type="password" name="password_confirmation" id="password_confirmation" required>
                        <div class="input-group-append">
                          <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_confirmation')"><i class="fa fa-eye"></i></button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              @endguest
              <div class="tile-footer">
                <button class="btn btn-primary" type="submit">
                    <i class="fa fa-fw fa-lg fa-check-circle"></i> Registrar Cliente
                </button>
                &nbsp;&nbsp;&nbsp;
                <a class="btn btn-secondary" href="{{ route('clientes.index') }}">
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
@section('scripts')
<script>
  function togglePassword(id) {
    var x = document.getElementById(id);
    if (x.type === "password") { x.type = "text"; } 
    else { x.type = "password"; }
  }
</script>

@endsection