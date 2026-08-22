<div class="row mb-3">
    <div class="col-md-6">
        <p class="mb-1"><strong>Origen:</strong> {{ $despacho->origen->nombre }}</p>
        <p class="mb-1"><strong>Destino:</strong> {{ $despacho->destino->nombre }}</p>
    </div>
    <div class="col-md-6 text-md-right">
        <p class="mb-1"><strong>Transporte:</strong> {{ $despacho->transportado_por }}</p>
        <p class="mb-1"><strong>Placa:</strong> {{ $despacho->vehiculo_placa ?? 'N/A' }}</p>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-sm align-middle">
        <thead class="thead-light">
            <tr>
                <th>Producto</th>
                <th>Descripción</th>
                <th class="text-center" width="100">Enviado</th>
                <th class="text-center" width="100">Recibido</th>
            </tr>
        </thead>
        <tbody>
            @foreach($despacho->detalles as $item)
            <tr>
                <td>{{ $item->insumos->producto ?? 'N/A' }}</td>
                <td>{{ $item->insumos->descripcion ?? 'N/A' }}</td>
                <td class="text-center text-muted">{{ $item->cantidad_enviada }}</td>
                <td class="text-center font-weight-bold">
                    @if(in_array($despacho->estado, ['Recibido', 'Con Observaciones', 'recibido_con_incidencias']))
                        <span class="{{ $item->cantidad_recibida < $item->cantidad_enviada ? 'text-danger' : 'text-success' }}">
                            {{ $item->cantidad_recibida }}
                        </span>
                    @else
                        <span class="text-warning">Pendiente</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Bloques de Observaciones --}}
@if($despacho->observacion)
    <div class="alert alert-secondary py-2 mb-2">
        <small><strong><i class="fa fa-comment"></i> Obs. de Envío (Origen):</strong> {{ $despacho->observacion }}</small>
    </div>
@endif

@if($despacho->observacion_recepcion)
    <div class="alert alert-info py-2 mb-0">
        <small><strong><i class="fa fa-comments"></i> Obs. de Recepción (Destino):</strong> {{ $despacho->observacion_recepcion }}</small>
    </div>
@endif