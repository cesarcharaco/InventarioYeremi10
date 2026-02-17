@extends('layouts.app')
@section('title') Tablero @endsection
@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-dashboard"></i> Tablero</h1>
      <p>Sistema Administrativo | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ url('home') }}">Tablero</a></li>
    </ul>
  </div>
  <div class="row">
    {{-- <div class="col-md-6 col-lg-4">
      <div class="widget-small primary coloured-icon"><i class="icon fa fa-users fa-3x"></i>
        <div class="info">
          <h4>Usuarios</h4>
          <p><b>5</b></p>
        </div>
      </div>
    </div> --}}
    <div class="col-md-6 col-lg-4">
      <div class="widget-small info coloured-icon"><i class="icon fa fa-sitemap fa-3x"></i>
        <div class="info">
          <h4><a href="{{ route('insumos.index') }}" style="text-decoration: none">Insumos</a></h4>
          <p><b>{{ $i }}</b></p>
        </div>
      </div>
    </div>
    
    <div class="col-md-6 col-lg-4">
      <div class="widget-small danger coloured-icon"><i class="icon fa fa-star fa-3x"></i>
        <div class="info">
          <h4><a href="{{ route('incidencias.index') }}" style="text-decoration: none">Incidencias</a></h4>
          <p><b>{{ $in }}</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-4">
      <div class="widget-small warning coloured-icon"><i class="icon fa fa-money fa-3x"></i>
        <div class="info">
          <h4>Tasas del Día</h4>
          <p style="font-size: 14px; margin-bottom: 0;">
            <b>BCV:</b> {{ number_format($tasa_bcv, 2, ',', '.') }} Bs.
          </p>
          <p style="font-size: 14px; margin-bottom: 0;">
            <b>Binance:</b> {{ number_format($tasa_binance, 2, ',', '.') }} Bs.
          </p>
        </div>
      </div>
    </div>
  </div>
</main>
@endsection
