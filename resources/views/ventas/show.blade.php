@extends('layouts.app')

@section('title') Detalle de Venta #{{ $venta->codigo_factura }} @endsection

@section('css')
<style>
    /* Estilos para la Factura tipo Documento */
    .invoice {
        background: #fff;
        padding: 40px;
        border: 1px solid #ddd;
        max-width: 800px;
        margin: auto;
        color: #333;
    }
    
    .invoice-header {
        border-bottom: 2px solid #333;
        padding-bottom: 20px;
        margin-bottom: 20px;
    }

    .invoice-title {
        font-size: 2rem;
        font-weight: bold;
        color: #000;
    }

    .info-section {
        margin-bottom: 25px;
    }

    /* Estilo de tabla tipo factura */
    .table-invoice {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .table-invoice th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        border-top: 1px solid #dee2e6;
        padding: 12px;
        text-transform: uppercase;
        font-size: 0.85rem;
    }

    .table-invoice td {
        padding: 12px;
        border-bottom: 1px solid #dee2e6;
    }

    /* Resaltado de totales */
    .total-box {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 5px;
        border: 1px solid #ddd;
        text-align: right;
    }

    @media print {
        .no-print { display: none; }
        .invoice { border: none; padding: 0; }
        body { background: white; }
    }
</style>
@endsection

@section('content')
<main class="app-content">
    <div class="row">
        <div class="col-md-12">
            <div class="tile">
                <section class="invoice">
                    
                    {{-- Encabezado Estilo Profesional --}}
                    <div class="row invoice-header" style="border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px;">
                        <div class="col-md-6">
                            <h2 style="font-weight: 800; color: #000; margin-bottom: 5px;">Yermotors Repuestos, C.A.</h2>
                            <p style="margin: 0; font-weight: bold; color: #555; text-align: center;">RIF. J-50185803-4</p>
                            <p style="margin: 0; font-weight: bold; color: #555; text-align: center;">Venta de Lubricantes y Repuestos para Motos</p>
                            <p style="margin: 0;">Calle Páez entre Bolívar y Guzmán Blanco</p>
                            <p style="margin: 0;">Casa No, S/N, Sector El Centro</p>
                            <p style="margin: 0;">San José de Guaribe, Estado Guárico - Z.P. 2323</p>
                            <p style="margin: 0;"><strong>Teléfono:</strong> 0414-086 31 07</p>
                        </div>
                        
                        <div class="col-md-6 text-md-right">
                            <h3 style="color: #000; margin-top: 0;">FACTURA #{{ $venta->codigo_factura }}</h3>
                            <p style="margin: 0;"><strong>Fecha:</strong> {{ $venta->created_at->format('d/m/Y') }}</p>
                            
                            <div style="background-color: #f8f9fa; border: 1px solid #ddd; padding: 10px; margin-top: 10px; text-align: left;">
                                <p style="margin-bottom: 3px;"><strong>CLIENTE:</strong> {{ $venta->cliente->nombre }}</p>
                                <p style="margin-bottom: 3px;"><strong>C.I./RIF:</strong> {{ $venta->cliente->identificacion }}</p>
                                <p style="margin-bottom: 0;"><strong>TLF:</strong> {{ $venta->cliente->telefono ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Tabla de Productos --}}
                    <table class="table-invoice">
                        <thead>
                            <tr>
                                <th>Cant.</th>
                                <th>Producto</th>
                                <th>Descripción</th>
                                <th class="text-right">Precio</th>
                                <th class="text-right">Subtotal</th>
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

                    {{-- Totales --}}
                    <div class="row mt-4">
                        <div class="col-7">
                            <p><strong>Métodos de Pago:</strong></p>
                            <small>
                                @if($venta->pago_usd_efectivo > 0) USD: ${{ number_format($venta->pago_usd_efectivo, 2) }} | @endif
                                @if($venta->pago_bs_efectivo > 0) Bs: {{ number_format($venta->pago_bs_efectivo, 2) }} | @endif
                                @if($venta->monto_credito_usd > 0) Crédito: ${{ number_format($venta->monto_credito_usd, 2) }} @endif
                            </small>
                        </div>
                        <div class="col-5">
                            <div class="total-box">
                                <h4 class="text-muted">TOTAL USD</h4>
                                <h2 class="font-weight-bold">${{ number_format($venta->total_usd, 2) }}</h2>
                            </div>
                        </div>
                    </div>

                    {{-- Botones (Ocultos al imprimir) --}}
                    <div class="row no-print mt-4">
                        <div class="col-12 text-center">
                            <button class="btn btn-secondary" onclick="window.print();">
                                <i class="fa fa-print"></i> Imprimir Factura
                            </button>
                            <a href="{{ route('ventas.index') }}" class="btn btn-primary">
                                Volver al listado
                            </a>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>
@endsection