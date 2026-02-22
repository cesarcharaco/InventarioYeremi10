@extends('layouts.app')

@section('title', 'Detalle de Entrada #' . $entrada->id)

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-file-invoice-dollar mr-2"></i>Entrada de Almacén</h1>
        </div>
        <div class="col-sm-6 text-right">
            <button onclick="window.print();" class="btn btn-default shadow-sm">
                <i class="fas fa-print"></i> Imprimir
            </button>
            <a href="{{ route('entradas.index') }}" class="btn btn-secondary shadow-sm">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="invoice p-3 mb-3 shadow-lg rounded">
    <div class="row">
        <div class="col-12">
            <h4>
                <img src="{{ asset('images/logo1Yerem.png') }}" alt="Logo" style="width: 40px; margin-top: -5px;">
                SAYER System
                <small class="float-right text-muted">Fecha: {{ \Carbon\Carbon::parse($entrada->created_at)->format('d/m/Y h:i A') }}</small>
            </h4>
        </div>
    </div>

    <hr>

    <div class="row invoice-info">
        <div class="col-sm-4 invoice-col">
            <strong class="text-primary">PROVEEDOR</strong>
            <address>
                <b class="h5">{{ $entrada->proveedor->nombre }}</b><br>
                RIF: {{ $entrada->proveedor->rif }}<br>
                Teléfono: {{ $entrada->proveedor->telefono ?? 'N/A' }}<br>
                Email: {{ $entrada->proveedor->email ?? 'N/A' }}
            </address>
        </div>
        <div class="col-sm-4 invoice-col border-left border-right">
            <strong class="text-success">DEPÓSITO DESTINO</strong>
            <address>
                <b class="h5 text-uppercase">{{ $entrada->local->nombre }}</b><br>
                Tipo: <span class="badge badge-info">{{ $entrada->local->tipo }}</span><br>
                Estado: <span class="text-success">Recibido</span>
            </address>
        </div>
        <div class="col-sm-4 invoice-col text-right">
            <b>Entrada ID: #{{ str_pad($entrada->id, 6, '0', STR_PAD_LEFT) }}</b><br>
            <br>
            <b>Registrado por:</b> {{ $entrada->user->name }}<br>
            <b>Total de Items:</b> {{ $entrada->detalles->count() }}
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12 table-responsive">
            <table class="table table-striped table-hover">
                <thead class="bg-light">
                    <tr>
                        <th width="10%">Cant.</th>
                        <th>Insumo / Repuesto</th>
                        <th class="text-right">Costo Unit. ($)</th>
                        <th class="text-right">Subtotal ($)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entrada->detalles as $detalle)
                    <tr>
                        <td class="text-bold">{{ number_format($detalle->cantidad, 2) }}</td>
                        <td>{{ $detalle->insumo->nombre }}</td>
                        <td class="text-right text-muted">$ {{ number_format($detalle->costo_unitario, 2) }}</td>
                        <td class="text-right text-bold text-dark">$ {{ number_format($detalle->cantidad * $detalle->costo_unitario, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-7 col-sm-12">
            <p class="lead text-bold">Observaciones:</p>
            <div class="text-muted well well-sm shadow-none p-3 bg-light rounded" style="min-height: 80px;">
                {{ $entrada->observaciones ?? 'Sin comentarios adicionales para esta carga.' }}
            </div>
        </div>
        <div class="col-md-5 col-sm-12">
            <div class="table-responsive">
                <table class="table table-borderless">
                    <tr>
                        <th style="width:50%" class="h5">TOTAL CARGA:</th>
                        <td class="text-right text-orange h4 text-bold">$ {{ number_format($entrada->total_usd, 2) }}</td>
                    </tr>
                </table>
            </div>
            
            <div class="mt-4 d-none d-print-block">
                <div class="row mt-5 text-center">
                    <div class="col-6">
                        <hr style="width: 80%; border-top: 1px solid #000;">
                        Firma Almacenista
                    </div>
                    <div class="col-6">
                        <hr style="width: 80%; border-top: 1px solid #000;">
                        Firma Proveedor / Chofer
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
    /* Estilos para que se vea bien al imprimir en papel */
    @media print {
        .btn, .main-footer, .main-sidebar, .breadcrumb {
            display: none !important;
        }
        .content-wrapper {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        .invoice {
            border: 0;
            margin: 0;
            padding: 0;
        }
    }
</style>
@endsection