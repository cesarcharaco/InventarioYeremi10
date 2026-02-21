@extends('layouts.app')

@section('title') Cuentas por Cobrar @endsection

@section('content')
<main class="app-content">
    <div class="app-title">
        <div>
            <h1><i class="fa fa-money"></i> Cuentas por Cobrar</h1>
            <p>Listado de clientes con saldo pendiente</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="tile">
                <div class="tile-body">
                    <form action="{{ route('creditos.index') }}" method="GET" class="row mb-4">
                        <div class="col-md-8">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar por cliente o identificación..." value="{{ request('buscar') }}">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" id="tablaCreditos">
                            <thead>
                                <tr>
                                    <th>Factura</th>
                                    <th>Cliente</th>
                                    <th>Fecha Crédito</th>
                                    <th>Vencimiento</th>
                                    <th>Monto Inicial</th>
                                    <th>Saldo Pendiente</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($creditos as $credito)
                                <tr>
                                    {{-- Aquí SÍ puedes usar ->venta porque estás dentro del bucle --}}
                                    <td>{{ $credito->venta->codigo_factura ?? 'ID: '.$credito->id_venta }}</td>
                                    <td>{{ $credito->cliente->nombre }}</td>
                                    <td>{{ $credito->created_at->format('d/m/Y') }}</td>
                                    <td class="{{ $credito->fecha_vencimiento->isPast() ? 'text-danger font-weight-bold' : '' }}">
                                        {{ $credito->fecha_vencimiento->format('d/m/Y') }}
                                    </td>
                                    <td>${{ number_format($credito->monto_inicial, 2) }}</td>
                                    <td class="text-danger font-weight-bold">${{ number_format($credito->saldo_pendiente, 2) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $credito->saldo_pendiente <= 0 ? 'success' : ($credito->fecha_vencimiento->isPast() ? 'danger' : 'warning') }}">
                                            {{ $credito->saldo_pendiente <= 0 ? 'PAGADO' : ($credito->fecha_vencimiento->isPast() ? 'VENCIDO' : 'PENDIENTE') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('creditos.show', $credito->id) }}" class="btn btn-info btn-sm" title="Ver Historial">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            @can('registrar-abono')
                                            <button class="btn btn-success btn-sm" onclick="abrirModalAbono({{ json_encode($credito) }})" title="Registrar Abono">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">No hay cuentas por cobrar pendientes.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

@include('creditos.modals.abono_modal')
@endsection

@section('scripts')
<script>
    function abrirModalAbono(credito) {
        $('#formAbono')[0].reset();
        let url = "{{ route('creditos.abono', ':id') }}";
        url = url.replace(':id', credito.id);
        $('#formAbono').attr('action', url);

        $('#nombre_cliente').text(credito.cliente.nombre);
        let saldo = parseFloat(credito.saldo_pendiente);
        $('#txt_saldo_pendiente').text('$' + saldo.toFixed(2));
        $('#monto_total_usd').attr('max', saldo);
        
        $('#modalAbono').modal('show');
    }
</script>
@endsection