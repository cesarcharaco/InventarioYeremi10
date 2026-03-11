@extends('layouts.app')
@section('title') Detalle Movimiento @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-file-text-o"></i> Detalle de Movimiento #{{ $movimiento->id }}</h1>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6 offset-md-3">
      <div class="tile shadow">
        <h3 class="tile-title text-center"><i class="fa fa-clipboard"></i> Comprobante de Caja</h3>
        <hr>
        <div class="tile-body">
            <dl class="row">
                <dt class="col-sm-6">Tipo:</dt>
                <dd class="col-sm-6 text-uppercase">{{ $movimiento->tipo }}</dd>

                <dt class="col-sm-6">Categoría:</dt>
                <dd class="col-sm-6">{{ $movimiento->categoria }}</dd>

                <dt class="col-sm-6">Efectivo USD:</dt>
                <dd class="col-sm-6 font-weight-bold text-primary">{{ number_format($movimiento->efectivo_usd, 2) }} $</dd>

                <dt class="col-sm-6">Efectivo Bs:</dt>
                <dd class="col-sm-6 font-weight-bold text-info">{{ number_format($movimiento->efectivo_bs, 2) }} Bs</dd>

                <dt class="col-sm-6">Fecha:</dt>
                <dd class="col-sm-6">{{ $movimiento->created_at->format('d/m/Y H:i') }}</dd>

                <dt class="col-sm-6">Registrado por:</dt>
                <dd class="col-sm-6">{{ $movimiento->usuario->name }}</dd>

                <dt class="col-sm-6">Local / Caja:</dt>
                <dd class="col-sm-6">{{ $movimiento->caja->local->nombre ?? 'N/A' }} (#{{ $movimiento->id_caja }})</dd>
            </dl>
            
            <div class="mt-3">
                <p><strong>Observación:</strong></p>
                <div class="p-3 bg-light border rounded">{{ $movimiento->observacion }}</div>
            </div>
        </div>
        
        <div class="tile-footer text-center mt-3">
            <a class="btn btn-secondary" href="{{ route('movimientos.index') }}"><i class="fa fa-arrow-left"></i> Volver</a>
            @if($movimiento->caja->estado == 'abierta')
                <a class="btn btn-info" href="{{ route('movimientos.edit', $movimiento->id) }}"><i class="fa fa-edit"></i> Editar</a>
            @endif
        </div>
      </div>
    </div>
  </div>
</main>
@endsection