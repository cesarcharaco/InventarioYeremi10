@extends('layouts.app')
@section('title') Detalle de Crédito @endsection

@section('content')
<main class="app-content">
    <div class="app-title">
        <div>
            <h1><i class="fa fa-file-text-o"></i> Crédito #{{ $credito->id }}</h1>
            <p>Cliente: {{ $credito->cliente->nombre }} | Factura Origen: {{ $credito->venta->codigo_factura ?? $credito->id_venta }}</p>
        </div>
        <a href="{{ route('creditos.index') }}" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Volver</a>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="tile p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item bg-dark text-white font-weight-bold">Resumen de Deuda</li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Monto Inicial:</span> <strong>${{ number_format($credito->monto_inicial, 2) }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Total Abonado:</span> <strong class="text-success">${{ number_format($credito->monto_inicial - $credito->saldo_pendiente, 2) }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between bg-light">
                        <span class="h5">Saldo Restante:</span> <strong class="h5 text-danger">${{ number_format($credito->saldo_pendiente, 2) }}</strong>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-md-8">
            <div class="tile">
                <h3 class="tile-title">Historial de Abonos</h3>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Cajero</th>
                                <th>Monto ($)</th>
                                <th>Forma de Pago</th>
                                <th>Detalles</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($credito->abonos as $abono)
                            <tr>
                                <td>{{ $abono->created_at->format('d/m/Y h:i A') }}</td>
                                <td>{{ $abono->usuario->name }}</td>
                                <td class="font-weight-bold">${{ number_format($abono->monto_pagado_usd, 2) }}</td>
                                <td>
                                    @if($abono->pago_usd_efectivo > 0) <small class="badge badge-light">Efectivo $</small> @endif
                                    @if($abono->pago_bs_efectivo > 0) <small class="badge badge-light">Efectivo Bs</small> @endif
                                    @if($abono->pago_punto_bs > 0) <small class="badge badge-light">Punto</small> @endif
                                    @if($abono->pago_pagomovil_bs > 0) <small class="badge badge-light">P.Móvil</small> @endif
                                </td>
                                <td><small>{{ $abono->detalles ?? 'N/A' }}</small></td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center">No hay abonos registrados para este crédito.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
@include('creditos.modals.abono_modal')
@endsection