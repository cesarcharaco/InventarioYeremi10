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
    @include('layouts.partials.flash-messages')

    {{-- Tarjeta de Filtros --}}
    <div class="card card-outline card-info mb-3">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filtros de Búsqueda</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Fecha Desde:</label>
                        <input type="date" id="filtro_desde" class="form-control form-control-sm" max="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Fecha Hasta:</label>
                        <input type="date" id="filtro_hasta" class="form-control form-control-sm" max="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Proveedor:</label>
                        <select id="filtro_proveedor" class="form-control form-control-sm">
                            <option value="">Todos los proveedores</option>
                            @foreach($proveedores as $prov)
                                <option value="{{ $prov->id }}">{{ $prov->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Estado:</label>
                        <select id="filtro_estado" class="form-control form-control-sm">
                            <option value="">Todos los estados</option>
                            <option value="PENDIENTE">PENDIENTE</option>
                            <option value="APROBADO">APROBADO</option>
                            <option value="ANULADO">ANULADO</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 text-right">
                    <button type="button" id="btn-limpiar" class="btn btn-secondary btn-sm">
                        <i class="fas fa-eraser mr-1"></i> Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

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
                <table id="tabla-entradas" class="table table-bordered table-striped sampleTable dt-responsive nowrap" style="width:100%">
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
                        {{-- Se llena vía AJAX --}}
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
        if ($.fn.DataTable.isDataTable('#tabla-entradas')) {
            $('#tabla-entradas').DataTable().destroy();
        }

        var table = $('#tabla-entradas').DataTable({
            processing: true,
            serverSide: true,
            destroy: true,
            retrieve: true,
            ajax: {
                url: "{{ route('entradas.data') }}",
                data: function (d) {
                    d.fecha_desde = $('#filtro_desde').val();
                    d.fecha_hasta = $('#filtro_hasta').val();
                    d.id_proveedor = $('#filtro_proveedor').val();
                    d.estado = $('#filtro_estado').val();
                }
            },
            columns: [
                { data: 'created_at', name: 'entradas_almacen.created_at' },
                { data: 'proveedor', name: 'proveedor.nombre' },
                { data: 'local', name: 'local.nombre' },
                { data: 'total_costo_usd', name: 'entradas_almacen.total_costo_usd' },
                { data: 'usuario', name: 'usuario.name' },
                { data: 'acciones', name: 'acciones', orderable: false, searchable: false, className: 'text-center' }
            ],
            language: {
                url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
            }
        });

        // Filtrado automático onchange en todos los campos de selección y fechas
        $('#filtro_desde, #filtro_hasta, #filtro_proveedor, #filtro_estado').on('change', function() {
            table.ajax.reload();
        });

        // Botón Limpiar filtros
        $('#btn-limpiar').click(function() {
            $('#filtro_desde').val('');
            $('#filtro_hasta').val('');
            $('#filtro_proveedor').val('');
            $('#filtro_estado').val('');
            table.ajax.reload();
        });

        // Delegación de eventos para el botón anular
        $(document).on('click', '.btn-anular', function() {
            let id = $(this).data('id');
            let url = "{{ route('entradas.anular', '__ID__') }}".replace('__ID__', id);

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
                    $('#form-anular').attr('action', url);
                    $('#form-anular').submit();
                }
            });
        });
    });
</script>
@endsection