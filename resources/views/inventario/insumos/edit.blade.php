@extends('layouts.app')
@section('title') Editar Insumo @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-th-list"></i> SAYER</h1>
      <p>Sistema Administrativo | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item">SAYER</li>
      <li class="breadcrumb-item"><a href="{{ route('insumos.index') }}">Insumos</a></li>
      <li class="breadcrumb-item">Modificar</li>
    </ul>
  </div>

  <div class="tile">
    <div class="row">
        <div class="col-md-12">
            @if(Gate::denies('editar-datos-maestros'))
                <div class="alert alert-info border-left-info">
                    <i class="fa fa-info-circle"></i> <strong>Modo Ajuste:</strong> Solo puedes modificar parámetros de stock. Los datos maestros están protegidos.
                </div>
            @endif
        </div>
    </div>
    <h4 class="line-head">Editar: {{ $insumo->producto }}</h4>
    
    {!! Form::open(['route' => ['insumos.update', $insumo->id], 'method' => 'PUT', 'data-parsley-validate']) !!}
      @csrf
      
      <div class="row">
        {{-- Bloque de Identificación --}}
        <div class="col-md-6">
          <div class="form-group">
            <label>Nombre del Producto <b class="text-danger">*</b></label>
            <input class="form-control" type="text" name="producto" value="{{ $insumo->producto }}" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label>Categoría <b class="text-danger">*</b></label>
            {!! Form::select('categoria_id', $categorias, $insumo->categoria_id, ['class' => 'form-control', 'required']) !!}
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="form-group">
            <label>Descripción detallada</label>
            <textarea class="form-control" name="descripcion" rows="3" 
                      @cannot('editar-datos-maestros') readonly @endcannot>{{ $insumo->descripcion }}</textarea>
          </div>
        </div>
      </div>

      <div class="row">
        {{-- Bloque de Ventas --}}
        <div class="col-md-4">
          <div class="form-group">
            <label>Modelo de Venta <b class="text-danger">*</b></label>
            {!! Form::select('modelo_venta_id', $modelos, $insumo->modelo_venta_id, ['class' => 'form-control', 'required',
                Gate::denies('editar-datos-maestros') ? 'disabled' : '']) !!}
                @if(Gate::denies('editar-datos-maestros'))
                <input type="hidden" name="modelo_venta_id" value="{{ $insumo->modelo_venta_id }}">
                @endif
            <small class="text-muted">El cambio de modelo recalculará los precios de venta.</small>
          </div>
        </div>

        {{-- Parámetros de Alerta de Inventario --}}
        <div class="col-md-4">
          <div class="form-group">
            <label>Stock Mínimo (Alerta de reposición)</label>
            <input class="form-control" type="number" name="stock_min" value="{{ $insumo->stock_min }}" min="0">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Stock Máximo (Capacidad ideal)</label>
            <input class="form-control" type="number" name="stock_max" value="{{ $insumo->stock_max }}" min="0">
          </div>
        </div>
      </div>

      <div class="tile-footer">
        <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Guardar Cambios</button>
        <a class="btn btn-secondary" href="{{ route('insumos.index') }}"><i class="fa fa-arrow-left"></i> Volver</a>
      </div>
    {!! Form::close() !!}
  </div>
</main>
@endsection