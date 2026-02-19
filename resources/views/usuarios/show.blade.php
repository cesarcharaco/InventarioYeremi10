@extends('layouts.app')

@section('title') Perfil de {{ $user->name }} @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-eye"></i> Detalle de Usuario</h1>
      <p>Información completa del personal</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('usuarios.index') }}">Usuarios</a></li>
      <li class="breadcrumb-item text-primary">Detalle</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-body">
          <div class="row">
            {{-- Columna de la Foto --}}
            <div class="col-md-4 text-center border-right">
              <div class="profile-card">
                <img src="{{ $user->foto ? asset('fotosperfil/'.$user->foto) : asset('images/user-default.png') }}" 
                     class="img-fluid rounded shadow-sm mb-3" 
                     style="width: 220px; height: 220px; object-fit: cover; border: 4px solid #009688;">
                <h4>{{ $user->name }}</h4>
                <span class="badge {{ $user->activo ? 'badge-success' : 'badge-secondary' }} p-2">
                    {{ $user->activo ? 'CUENTA ACTIVA' : 'CUENTA INACTIVA' }}
                </span>
              </div>
            </div>

            {{-- Columna de Información --}}
            <div class="col-md-8">
              <h4 class="line-head">Datos de Identidad</h4>
              <div class="row mb-3">
                <div class="col-sm-4"><strong>Cédula:</strong></div>
                <div class="col-sm-8">{{ $user->cedula }}</div>
              </div>
              <div class="row mb-3">
                <div class="col-sm-4"><strong>Teléfono:</strong></div>
                <div class="col-sm-8">{{ $user->telefono ?? 'No registrado' }}</div>
              </div>
              <div class="row mb-3">
                <div class="col-sm-4"><strong>Correo / Usuario:</strong></div>
                <div class="col-sm-8">{{ $user->email }}</div>
              </div>

              <h4 class="line-head mt-4">Asignación Laboral</h4>
              <div class="row mb-3">
                <div class="col-sm-4"><strong>Rol en Sistema:</strong></div>
                <div class="col-sm-8"><span class="text-primary font-weight-bold">{{ strtoupper($user->role) }}</span></div>
              </div>
              <div class="row mb-3">
                <div class="col-sm-4"><strong>Sede / Local:</strong></div>
                <div class="col-sm-8">{{ $localActual->nombre ?? 'Sin sede asignada' }} ({{ $localActual->tipo ?? 'N/A' }})</div>
              </div>

              <h4 class="line-head mt-4">Registro</h4>
              <div class="row mb-3">
                <div class="col-sm-4"><strong>Miembro desde:</strong></div>
                <div class="col-sm-8">{{ $user->created_at->format('d/m/Y h:i A') }}</div>
              </div>
            </div>
          </div>
        </div>
        <div class="tile-footer text-right">
          <a class="btn btn-secondary" href="{{ route('usuarios.index') }}">
            <i class="fa fa-fw fa-lg fa-arrow-left"></i> Volver a la lista
          </a>
          @can('gestionar-usuarios')
          <a class="btn btn-info" href="{{ route('usuarios.edit', $user->id) }}">
            <i class="fa fa-fw fa-lg fa-edit"></i> Editar Datos
          </a>
          @endcan
        </div>
      </div>
    </div>
  </div>
</main>
@endsection