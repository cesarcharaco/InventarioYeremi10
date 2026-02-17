<div class="row">
    <div class="col-md-6">
        <p><strong>Origen:</strong> {{ $despacho->origen->nombre }}</p>
        <p><strong>Destino:</strong> {{ $despacho->destino->nombre }}</p>
    </div>
    <div class="col-md-6 text-right">
        <p><strong>Transporte:</strong> {{ $despacho->transportado_por }}</p>
        <p><strong>Placa:</strong> {{ $despacho->vehiculo_placa ?? 'N/A' }}</p>
    </div>
</div>
<table class="table table-bordered table-sm">
    <thead class="thead-light">
        <tr>
            <th>Producto</th>
            <th>Descripción</th>
            <th class="text-center">Cant.</th>
        </tr>
    </thead>
    <tbody>
        @foreach($despacho->detalles as $item)
        <tr>
            <td>{{ $item->insumos->producto }}</td>
            <td>{{ $item->insumos->descripcion }}</td>
            <td class="text-center">{{ $item->cantidad }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@if($despacho->observacion)
    <div class="alert alert-secondary"><strong>Obs:</strong> {{ $despacho->observacion }}</div>
@endif