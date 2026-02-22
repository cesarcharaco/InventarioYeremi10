@extends('layouts.app')

@section('title') Detalle de Venta #{{ $venta->codigo_factura }} @endsection
@section('css')
<style>
    @media (max-width: 767px) {
        .invoice {
            padding: 10px !important;
            margin: 0 !important;
        }
        .page-header {
            font-size: 1.2rem;
        }
        .invoice-info .col-4 {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 20px;
        }
        /* Esto evita que la tabla rompa el layout */
        .table-responsive {
            border: 0;
        }
    }
</style>
@endsection
@section('content')
<main class="app-content">
    <div class="app-title">
        <div>
            <h1><i class="fa fa-file-text-o"></i> Detalle de Factura</h1>
            <p>Comprobante de transacción interna</p>
        </div>
        <ul class="app-breadcrumb breadcrumb">
            <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
            <li class="breadcrumb-item"><a href="{{ route('ventas.index') }}">Ventas</a></li>
            <li class="breadcrumb-item">Detalle</li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="tile">
                <section class="invoice">
                    <div class="row mb-4">
                        <div class="col-6">
                            <h2 class="page-header"><i class="fa fa-motorcycle"></i> YERMOTOS REPUESTOS</h2>
                        </div>
                        <div class="col-6">
                            <h5 class="text-right">Fecha: {{ $venta->created_at->format('d/m/Y') }}</h5>
                        </div>
                    </div>
                    
                    <div class="row invoice-info">
                        <div class="col-4">De:
                            <address>
                                <strong>Sede: {{ $venta->local->nombre }}</strong><br>
                                Vendedor: {{ $venta->usuario->name }}<br>
                                Estado: 
                                @if($venta->estado == 'completada')
                                    <span class="badge badge-success">COMPLETADA</span>
                                @else
                                    <span class="badge badge-danger">ANULADA</span>
                                @endif
                            </address>
                        </div>
                        <div class="col-4">Para:
                            <address>
                                <strong>{{ $venta->cliente->nombre }}</strong><br>
                                ID: {{ $venta->cliente->identificacion }}<br>
                                Tel: {{ $venta->cliente->telefono ?? 'N/A' }}
                            </address>
                        </div>
                        <div class="col-4">
                            <b>Factura #{{ $venta->codigo_factura }}</b><br>
                            <br>
                            <b>Tipo:</b> {{ $venta->monto_credito_usd > 0 ? 'Crédito' : 'Contado' }}<br>
                            <b>ID Venta:</b> {{ $venta->id }}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 table-responsive" style="width: 100%; -webkit-overflow-scrolling: touch;">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Cantidad</th>
                                        <th>Producto</th>
                                        <th>Descripción</th>
                                        <th class="text-right">Precio Unit. ($)</th>
                                        <th class="text-right">Subtotal ($)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($venta->detalles as $detalle)
                                    <tr>
                                        <td>{{ $detalle->cantidad }}</td>
                                        <td>{{ $detalle->insumo->producto }}</td>
                                        <td>{{ $detalle->insumo->descripcion }}</td>
                                        <td class="text-right">${{ number_format($detalle->precio_unitario, 2) }}</td>
                                        <td class="text-right">${{ number_format($detalle->cantidad * $detalle->precio_unitario, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-3">
                        {{-- DESGLOSE DE PAGOS --}}
                        <div class="col-6 col-md-6 mb-3">
                            <p class="lead font-weight-bold">Métodos de Pago:</p>
                                <div class="table-responsive">
                                    <table class="table table-sm border">
                                        <tbody>
                                            @if($venta->pago_usd_efectivo > 0)
                                                <tr>
                                                    <th>Efectivo USD:</th>
                                                    <td>${{ number_format($venta->pago_usd_efectivo, 2) }}</td>
                                                </tr>
                                            @endif
                                            @if($venta->pago_bs_efectivo > 0)
                                                <tr>
                                                    <th>Efectivo Bs:</th>
                                                    <td>{{ number_format($venta->pago_bs_efectivo, 2) }} Bs</td>
                                                </tr>
                                            @endif
                                            @if($venta->pago_punto_bs > 0)
                                                <tr>
                                                    <th>Punto / Biopago:</th>
                                                    <td>{{ number_format($venta->pago_punto_bs, 2) }} Bs</td>
                                                </tr>
                                            @endif
                                            @if($venta->pago_pagomovil_bs > 0)
                                                <tr>
                                                    <th>Pago Móvil:</th>
                                                    <td>{{ number_format($venta->pago_pagomovil_bs, 2) }} Bs</td>
                                                </tr>
                                            @endif
                                            @if($venta->monto_credito_usd > 0)
                                                <tr class="table-warning">
                                                    <th>Monto a Crédito:</th>
                                                    <td><strong>${{ number_format($venta->monto_credito_usd, 2) }}</strong></td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                        </div>
                        
                        {{-- TOTAL FINAL --}}
                        <div class="col-12 col-md-6 text-center text-md-right">
                            <div class="p-3 bg-light border rounded">
                                <h4 class="text-muted">TOTAL FACTURADO</h4>
                                <h2 class="text-primary font-weight-bold">${{ number_format($venta->total_usd, 2) }}</h2>
                            </div>
                        </div>
                    </div>

                    <div class="row no-print mt-4">
                        <div class="col-12 text-right">
                            <button class="btn btn-secondary" onclick="window.print();"><i class="fa fa-print"></i> Imprimir</button>
                            <a href="{{ route('ventas.index') }}" class="btn btn-primary"><i class="fa fa-list"></i> Volver al listado</a>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>
@endsection