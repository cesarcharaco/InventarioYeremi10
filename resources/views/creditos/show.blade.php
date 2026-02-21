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
                                <th>Estado</th> {{-- Nueva Columna --}}
                                @can('anular-abono') <th>Acción</th> @endcan {{-- Nueva Columna --}}
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($credito->abonos as $abono)
                            <tr style="{{ $abono->estado === 'Anulado' ? 'opacity: 0.6; text-decoration: line-through;' : '' }}">
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
                                {{-- Badge de Estado --}}
                                <td>
                                    <span class="badge badge-{{ $abono->estado === 'Realizado' ? 'success' : 'danger' }}">
                                        {{ $abono->estado }}
                                    </span>
                                </td>
                                {{-- Botón para Anular (Solo Admin y si está Realizado) --}}
                                @can('anular-abono')
                                <td>
                                    @if($abono->estado === 'Realizado')
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmarAnulacion('{{ route('abonos.anular', $abono->id) }}', '{{ number_format($abono->monto_pagado_usd, 2) }}')"
                                                title="Anular Abono">
                                            <i class="fa fa-ban"></i>
                                        </button>
                                    @endif
                                </td>
                                @endcan
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center">No hay abonos registrados para este crédito.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
<div class="modal fade" id="modalAnularAbono" tabindex="-1" role="dialog" aria-labelledby="modalAnularLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalAnularLabel"><i class="fa fa-exclamation-triangle"></i> Confirmar Anulación</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formAnularAbono" method="POST">
                @csrf
                <div class="modal-body text-center">
                    <p class="h5">¿Estás seguro de que deseas anular este pago?</p>
                    <p class="text-muted">Esta acción restaurará el monto de <strong id="montoAbonoText"></strong> al saldo pendiente del cliente y marcará el abono como anulado en la caja.</p>
                    <div class="alert alert-warning">
                        <i class="fa fa-info-circle"></i> Esta operación es irreversible.
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Anulación</button>
                </div>
            </form>
        </div>
    </div>
</div>
@include('creditos.modals.abono_modal')
@endsection
@section('scripts')
<script>
    function confirmarAnulacion(url, monto) {
        // 1. Actualizamos la acción del formulario con la URL del abono específico
        $('#formAnularAbono').attr('action', url);
        
        // 2. Mostramos el monto en el texto del modal para mayor seguridad
        $('#montoAbonoText').text('$' + monto);
        
        // 3. Abrimos el modal
        $('#modalAnularAbono').modal('show');
    }
</script>
@endsection