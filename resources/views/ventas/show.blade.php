@extends('layouts.app')

@section('title') Detalle de Venta #{{ $venta->codigo_factura }} @endsection

@section('css')
<style>
    /* Estilos generales para asegurar que nada se desborde */
    .table-responsive {
        border: none !important;
    }

    @media (max-width: 768px) {
        .content-wrapper, .app-content {
            padding: 5px !important;
            overflow-x: hidden !important;
        }

        .invoice {
            margin: 0 !important;
            padding: 10px !important;
            width: 100% !important;
        }

        /* Forzamos a que los números no se rompan en varias líneas */
        .text-right, .font-weight-bold {
            white-space: nowrap !important;
        }

        /* Reducción de fuentes para ganar espacio en móvil */
        .page-header {
            font-size: 1.1rem !important;
        }

        /* Ocultar columnas no vitales en móvil mediante CSS como respaldo */
        .table thead th:nth-child(3), 
        .table tbody td:nth-child(3),
        .table thead th:nth-child(4),
        .table tbody td:nth-child(4) {
            display: none !important;
        }

        /* Ajuste de anchos para que el total se vea claro */
        .table thead th:nth-child(1) { width: 15%; } /* Cantidad */
        .table thead th:nth-child(2) { width: 50%; } /* Producto */
        .table thead th:nth-child(5) { width: 35%; } /* Subtotal */

        .invoice-info .col-12 {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
    }
</style>
@endsection

@section('content')
<main class="app-content">
    <div class="app-title d-none d-md-flex"> {{-- Oculto en móvil para ahorrar espacio --}}
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
                    {{-- Encabezado --}}
                    <div class="row mb-4">
                        <div class="col-8 col-md-6">
                            <h2 class="page-header"><i class="fa fa-motorcycle"></i> YERMOTOS</h2>
                        </div>
                        <div class="col-4 col-md-6">
                            <h5 class="text-right" style="font-size: 0.9rem;">{{ $venta->created_at->format('d/m/Y') }}</h5>
                        </div>
                    </div>
                    
                    {{-- Información de Factura --}}
                    <div class="row invoice-info">
                        <div class="col-12 col-md-4 mb-3">
                            <strong>De:</strong>
                            <address>
                                <strong>Sede: {{ $venta->local->nombre }}</strong><br>
                                Vendedor: {{ $venta->usuario->name }}<br>
                                @if($venta->estado == 'completada')
                                    <span class="badge badge-success">COMPLETADA</span>
                                @else
                                    <span class="badge badge-danger">ANULADA</span>
                                @endif
                            </address>
                        </div>
                        <div class="col-12 col-md-4 mb-3">
                            <strong>Para:</strong>
                            <address>
                                <strong>{{ $venta->cliente->nombre }}</strong><br>
                                ID: {{ $venta->cliente->identificacion }}<br>
                                Tel: {{ $venta->cliente->telefono ?? 'N/A' }}
                            </address>
                        </div>
                        <div class="col-12 col-md-4 mb-3">
                            <b>Factura #{{ $venta->codigo_factura }}</b><br>
                            <b>Tipo:</b> {{ $venta->monto_credito_usd > 0 ? 'Crédito' : 'Contado' }}<br>
                            <b>ID Venta:</b> {{ $venta->id }}
                        </div>
                    </div>

                    {{-- Tabla de Productos --}}
                    <div class="row">
                        <div class="col-12 p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Cant.</th>
                                            <th>Producto</th>
                                            <th class="d-none d-md-table-cell">Descripción</th>
                                            <th class="d-none d-md-table-cell text-right">Precio ($)</th>
                                            <th class="text-right">Subtotal ($)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($venta->detalles as $detalle)
                                        <tr>
                                            <td>{{ $detalle->cantidad }}</td>
                                            <td>{{ $detalle->insumo->producto }}</td>
                                            <td class="d-none d-md-table-cell">{{ $detalle->insumo->descripcion }}</td>
                                            <td class="d-none d-md-table-cell text-right">${{ number_format($detalle->precio_unitario, 2) }}</td>
                                            <td class="text-right"><strong>${{ number_format($detalle->cantidad * $detalle->precio_unitario, 2) }}</strong></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Pagos y Total --}}
                    <div class="row mt-3">
                        <div class="col-12 col-md-6 mb-3">
                            <p class="lead font-weight-bold">Métodos de Pago:</p>
                            <div class="table-responsive">
                                <table class="table table-sm border">
                                    <tbody>
                                        @if($venta->pago_usd_efectivo > 0)
                                            <tr>
                                                <th>Efectivo USD:</th>
                                                <td class="text-right">${{ number_format($venta->pago_usd_efectivo, 2) }}</td>
                                            </tr>
                                        @endif
                                        @if($venta->pago_bs_efectivo > 0)
                                            <tr>
                                                <th>Efectivo Bs:</th>
                                                <td class="text-right">{{ number_format($venta->pago_bs_efectivo, 2) }} Bs</td>
                                            </tr>
                                        @endif
                                        @if($venta->pago_punto_bs > 0)
                                            <tr>
                                                <th>Punto / Bio:</th>
                                                <td class="text-right">{{ number_format($venta->pago_punto_bs, 2) }} Bs</td>
                                            </tr>
                                        @endif
                                        @if($venta->pago_pagomovil_bs > 0)
                                            <tr>
                                                <th>Pago Móvil:</th>
                                                <td class="text-right">{{ number_format($venta->pago_pagomovil_bs, 2) }} Bs</td>
                                            </tr>
                                        @endif
                                        @if($venta->monto_credito_usd > 0)
                                            <tr class="table-warning">
                                                <th>Monto a Crédito:</th>
                                                <td class="text-right"><strong>${{ number_format($venta->monto_credito_usd, 2) }}</strong></td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="col-12 col-md-6 text-center text-md-right">
                            <div class="p-3 bg-light border rounded shadow-sm">
                                <h4 class="text-muted" style="font-size: 1rem;">TOTAL FACTURADO</h4>
                                <h2 class="text-primary font-weight-bold">${{ number_format($venta->total_usd, 2) }}</h2>
                            </div>
                        </div>
                    </div>

                    {{-- Botones --}}
                    <div class="row no-print mt-4">
                        <div class="col-12 text-right">
                            <button class="btn btn-secondary btn-block d-md-inline-block mb-2" style="max-width: 200px;" onclick="window.print();">
                                <i class="fa fa-print"></i> Imprimir
                            </button>
                            <a href="{{ route('ventas.index') }}" class="btn btn-primary btn-block d-md-inline-block mb-2" style="max-width: 200px;">
                                <i class="fa fa-list"></i> Volver al listado
                            </a>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>
@endsection