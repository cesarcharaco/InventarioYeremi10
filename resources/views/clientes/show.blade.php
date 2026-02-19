@extends('layouts.app')

@section('title') Perfil de {{ $cliente->nombre }} @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-user"></i> Detalle del Cliente</h1>
      <p>Información de contacto y estado de cuenta</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('clientes.index') }}">Clientes</a></li>
      <li class="breadcrumb-item text-primary">Perfil</li>
    </ul>
  </div>

  <div class="row">
    {{-- Columna Izquierda: Información General --}}
    <div class="col-md-4">
      <div class="tile p-0">
        <div class="tile-body text-center p-4">
          <div class="img-container mb-3">
             <i class="fa fa-user-circle-o fa-5x text-primary"></i>
          </div>
          <h4 class="mb-1">{{ $cliente->nombre }}</h4>
          <span class="badge badge-dark p-2">{{ $cliente->identificacion }}</span>
          <hr>
          <div class="text-left">
            <p><strong><i class="fa fa-phone"></i> Teléfono:</strong><br> {{ $cliente->telefono }}</p>
            <p><strong><i class="fa fa-map-marker"></i> Dirección:</strong><br> {{ $cliente->direccion ?? 'Sin dirección registrada' }}</p>
            <p><strong><i class="fa fa-building"></i> Sede de origen:</strong><br> {{ $cliente->local->nombre ?? 'N/A' }}</p>
          </div>
        </div>
        <div class="tile-footer text-center bg-light">
           <small>Registrado el: {{ $cliente->created_at->format('d/m/Y') }}</small>
        </div>
      </div>
    </div>

    {{-- Columna Derecha: Estado Financiero --}}
    <div class="col-md-8">
      <div class="tile">
        <h4 class="line-head"><i class="fa fa-money"></i> Resumen de Crédito</h4>
        <div class="row">
          <div class="col-md-6">
            <div class="widget-small info coloured-icon">
              <i class="icon fa fa-credit-card fa-3x"></i>
              <div class="info">
                <h4>Límite de Crédito</h4>
                <p><b>{{ number_format($cliente->limite_credito, 2) }}$</b></p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            {{-- Este campo se calculará luego con la tabla de créditos --}}
            <div class="widget-small danger coloured-icon">
              <i class="icon fa fa-balance-scale fa-3x"></i>
              <div class="info">
                <h4>Deuda Actual</h4>
                <p><b>0.00$</b></p>
              </div>
            </div>
          </div>
        </div>

        <h4 class="line-head mt-4"><i class="fa fa-history"></i> Últimos Movimientos</h4>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Concepto</th>
                        <th>Monto (USD)</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" class="text-center text-muted italic">No hay créditos registrados para este cliente.</td>
                    </tr>
                </tbody>
            </table>
        </div>
      </div>

      <div class="tile-footer text-right mt-3">
        <a class="btn btn-secondary" href="{{ route('clientes.index') }}">
          <i class="fa fa-fw fa-lg fa-arrow-left"></i> Volver
        </a>
        @can('gestionar-clientes')
        <a class="btn btn-primary" href="{{ route('clientes.edit', $cliente->id) }}">
          <i class="fa fa-fw fa-lg fa-edit"></i> Editar Cliente
        </a>
        @endcan
      </div>
    </div>
  </div>
</main>
@endsection