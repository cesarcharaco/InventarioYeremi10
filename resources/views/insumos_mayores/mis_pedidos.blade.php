@extends('layouts.app')
@section('title') Mis Pedidos @endsection

@section('content')
<main class="app-content">
    <div class="app-title">
        <div>
            <h1><i class="fa fa-briefcase"></i> Mi Historial de Pedidos</h1>
            <p>Seguimiento de tus compras al mayor en Yermotos</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="tile">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Oferta</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pedidos as $pedido)
                            <tr>
                                <td><strong>#{{ str_pad($pedido->id, 5, '0', STR_PAD_LEFT) }}</strong></td>
                                <td>{{ $pedido->created_at->format('d/m/Y') }}</td>
                                <td>{{ $pedido->listaOferta->nombre ?? 'N/A' }}</td>
                                <td>{{ number_format($pedido->total, 2) }} $</td>
                                <td>
                                    @php
                                        $badgeClass = [
                                            'PENDIENTE' => 'badge-secondary',
                                            'APROBADO' => 'badge-info',
                                            'EN PREPARACIÓN' => 'badge-warning',
                                            'ENVIADO' => 'badge-primary',
                                            'ENTREGADO' => 'badge-success',
                                            'CANCELADO' => 'badge-danger'
                                        ][$pedido->estado] ?? 'badge-dark';
                                    @endphp
                                    <span class="badge {{ $badgeClass }} p-2">{{ $pedido->estado }}</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('pedidos.show', $pedido->id) }}" class="btn btn-info btn-sm">
                                        <i class="fa fa-eye"></i> Ver Detalle
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center">Aún no has realizado ningún pedido.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection