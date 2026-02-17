@extends('layouts.app')
@section('title') Inventario - {{ $local->nombre }} @endsection

@section('content')
<main class="app-content">
    <div class="app-title">
        <div>
            <h1><i class="fa fa-boxes"></i> Inventario: {{ $local->nombre }}</h1>
            <p>Existencias detalladas en esta ubicación</p>
        </div>
        <ul class="app-breadcrumb breadcrumb">
            <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
            <li class="breadcrumb-item">Inventario</li>
            <li class="breadcrumb-item"><a href="#">{{ $local->nombre }}</a></li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="tile">
                {{-- Botón de acceso rápido a edición solo para autorizados --}}
                @can('gestionar-insumos')
                <div class="tile-title-w-btn">
                    <h3 class="title text-primary"><i class="fa fa-list-ul"></i> Tabla de Existencias</h3>
                    <p><a class="btn btn-primary icon-btn" href="{{ route('insumos.create') }}"><i class="fa fa-plus"></i>Añadir Nuevo</a></p>
                </div>
                @endcan
                <div class="tile-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" id="tabla_inventario">
                            <thead>
                                <tr>
                                    <th>Serial</th>
                                    <th>Repuesto / Producto</th>
                                    <th>Descripción</th>
                                    <th class="text-center">Stock Actual</th>
                                    <th class="text-center">Estado</th>
                                    @can('ver-costos')
                                    <th class="text-center">Costo Unit.</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stock as $item)
                                <tr>
                                    <td><span class="badge badge-secondary">{{ $item->serial }}</span></td>
                                    <td><strong>{{ $item->producto }}</strong></td>
                                    <td>{{ $item->descripcion }}</td>
                                    <td class="text-center">
                                        <h5 class="mb-0">{{ $item->cantidad }}</h5>
                                    </td>
                                    <td class="text-center">
                                        @if($item->cantidad <= $item->stock_min)
                                            <span class="badge badge-danger"><i class="fa fa-warning"></i> Stock Bajo</span>
                                        @elseif($item->cantidad >= $item->stock_max)
                                            <span class="badge badge-info"><i class="fa fa-check-circle"></i> Stock Lleno</span>
                                        @else
                                            <span class="badge badge-success"><i class="fa fa-check"></i> Óptimo</span>
                                        @endif
                                    </td>
                                    {{-- Columna de Costos protegida --}}
                                    @can('ver-costos')
                                    <td class="text-center text-muted">
                                        {{ number_format($item->precio_costo, 2) }} $
                                    </td>
                                    @endcan
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Tu objeto de idioma que ya sabemos que funciona
        var lenguajeEspanol = {
            "decimal": "",
            "emptyTable": "No hay información",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
            "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
            "infoFiltered": "(Filtrado de _MAX_ entradas totales)",
            "infoPostFix": "",
            "thousands": ",",
            "lengthMenu": "Mostrar _MENU_ entradas",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "Sin resultados encontrados",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        };

        // Aplicamos el bloque con try-catch para máxima seguridad
        var tabla;
        try {
            if ($.fn.DataTable.isDataTable('#tabla_inventario')) {
                $('#tabla_inventario').DataTable().destroy();
            }

            tabla = $('#tabla_inventario').DataTable({
                "responsive": true,
                "autoWidth": false,
                "language": lenguajeEspanol,
                "retrieve": true, // Evita errores si se intenta inicializar dos veces
                "paging": true,
                "searching": true,
                "order": [[ 3, "asc" ]] // Ordena por stock de menor a mayor
            });
        } catch (e) {
            console.log("Error en DataTable Inventario: ", e);
        }
    });
</script>
@endsection