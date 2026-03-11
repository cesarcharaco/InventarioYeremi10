@extends('layouts.app')
@section('title') Detalle del Pedido #{{ $pedido->id }} @endsection

@section('content')
<main class="app-content">
    <div class="app-title">
        <div>
            <h1><i class="fa fa-file-text-o"></i> Detalle de Pedido #{{ str_pad($pedido->id, 5, '0', STR_PAD_LEFT) }}</h1>
            <p>Estado actual: <span class="badge badge-primary">{{ $pedido->estado }}</span></p>
        </div>
        <a href="{{ route('pedidos.mis_pedidos') }}" class="btn btn-secondary">Volver al listado</a>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="tile">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Datos de Logística</h5>
                        <p>
                            Transporte: <strong>{{ $pedido->transporte ?? 'Pendiente' }}</strong><br>
                            Guía/Seguimiento: <strong>{{ $pedido->nro_guia ?? 'Pendiente' }}</strong><br>
                            Fecha Envío: {{ $pedido->fecha_despacho ? date('d/m/Y', strtotime($pedido->fecha_despacho)) : 'Pendiente' }}
                        </p>
                    </div>
                    <div class="col-md-6 text-right">
                        <h5>Total a Pagar</h5>
                        <h2 class="text-primary">{{ number_format($pedido->total, 2) }} $</h2>
                    </div>
                </div>

                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Solicitado</th>
                            <th class="text-center">Despachado</th>
                            <th class="text-right">Precio</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pedido->detalles as $detalle)
                        <tr>
                            <td>{{ $detalle->producto->descripcion }}</td>
                            <td class="text-center">{{ $detalle->cantidad_solicitada }}</td>
                            <td class="text-center">
                                @if($detalle->cantidad_despachada < $detalle->cantidad_solicitada)
                                    <span class="text-danger font-weight-bold">{{ $detalle->cantidad_despachada }}</span>
                                @else
                                    {{ $detalle->cantidad_despachada }}
                                @endif
                            </td>
                            <td class="text-right">{{ number_format($detalle->precio_unitario, 2) }} $</td>
                            <td class="text-right">{{ number_format($detalle->subtotal, 2) }} $</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
@endsection