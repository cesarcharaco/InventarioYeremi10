@extends('layouts.app')

@section('title', 'Historial de Entradas')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark"><i class="fas fa-file-import mr-2"></i>Historial de Entradas</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ url('home') }}">Inicio</a></li>
                <li class="breadcrumb-item active">Entradas</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    {{-- ✅ Mensajes Flash según tu estructura --}}
    @include('layouts.partials.flash-messages')

    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title">Cargas de Inventario Realizadas</h3>
            <div class="card-tools">
                <a href="{{ route('entradas.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus mr-1"></i> Nueva Entrada
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped sampleTable dt-responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th>Depósito Destino</th>
                            <th>Total (USD)</th>
                            <th>Usuario</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entradas as $entrada)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($entrada->created_at)->format('d/m/Y h:i A') }}</td>
                                <td>
                                    <span class="text-bold">{{ $entrada->proveedor->nombre }}</span>
                                    <br><small class="text-muted">{{ $entrada->proveedor->rif }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-info shadow-sm">
                                        <i class="fas fa-warehouse mr-1"></i> {{ $entrada->local->nombre }}
                                    </span>
                                </td>
                                <td class="text-orange">
                                    ${{ number_format($entrada->total_usd, 2) }}
                                </td>
                                <td>
                                    <small><i class="fas fa-user mr-1"></i> {{ $entrada->usuario->name }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        {{-- Botón ver detalle --}}
                                        <a href="{{ route('entradas.show', $entrada->id) }}" 
                                           class="btn btn-info btn-xs" 
                                           title="Ver Detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        {{-- Botón para anular (si aplica en tu lógica de negocio) --}}
                                        @if(auth()->user()->esAdmin())
                                        <button type="button" 
                                                class="btn btn-danger btn-xs btn-anular" 
                                                data-id="{{ $entrada->id }}"
                                                title="Anular Entrada">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form id="form-anular" action="" method="POST" style="display:none;">
        @csrf
        @method('DELETE')
    </form>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.btn-anular').click(function() {
            let id = $(this).data('id');
            let url = "{{ route('entradas.anular', ':id') }}";
            url = url.replace(':id', id);

            Swal.fire({
                title: '¿Anular esta entrada?',
                text: "Se restará automáticamente la cantidad del inventario en el depósito. Esta acción es irreversible.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, anular entrada',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Seteamos la URL dinámica al formulario y lo enviamos
                    $('#form-anular').attr('action', url);
                    $('#form-anular').submit();
                }
            });
        });
    });
</script>
@endsection