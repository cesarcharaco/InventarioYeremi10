@extends('layouts.app')

@section('title') Correlativos SENIAT @endsection

@section('content')
<main class="app-content">
    <div class="app-title">
        <div>
            <h1><i class="fa fa-barcode"></i> Correlativos Fiscales SENIAT</h1>
            <p>Gestión de números de factura y números de control autorizados</p>
        </div>
        <a href="{{ route('correlativos.create') }}" class="btn btn-primary">
            <i class="fa fa-plus"></i> Cargar Correlativos
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="tile">
                <div class="tile-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th># Factura</th>
                                    <th># Control</th>
                                    <th>Estado</th>
                                    <th>Venta Asignada</th>
                                    <th>Fecha de Uso</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($correlativos as $item)
                                <tr>
                                    <td><strong>{{ $item->numero_factura }}</strong></td>
                                    <td>{{ $item->numero_control }}</td>
                                    <td>
                                        @if($item->estado == 'disponible')
                                            <span class="badge badge-success">Disponible</span>
                                        @elseif($item->estado == 'usado')
                                            <span class="badge badge-info">Usado</span>
                                        @else
                                            <span class="badge badge-danger">Anulado</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->venta_id ? 'Venta #'.$item->venta_id : 'N/A' }}</td>
                                    <td>{{ $item->fecha_uso ? \Carbon\Carbon::parse($item->fecha_uso)->format('d/m/Y H:i') : '-' }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('correlativos.edit', $item->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        @if($item->estado != 'usado')
                                        <form action="{{ route('correlativos.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Desea eliminar este correlativo?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" type="submit">
                                                <i class="fa fa-trash-alt"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No hay correlativos registrados.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $correlativos->links() }}
                </div>
            </div>
        </div>
    </div>
</main>
@endsection