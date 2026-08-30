@extends('layouts.app')

@section('title') Cuentas por Cobrar @endsection

@section('content')
<style>
    /* Evita que la tabla se rompa en móviles */
    .table-custom {
        border-collapse: separate !important;
        border-spacing: 0 8px !important; /* Efecto de filas separadas */
    }

    .table-custom tbody tr {
        background-color: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border-radius: 8px;
    }

    /* Ajuste de botones en móvil */
    @media (max-width: 768px) {
        .btn-block-mobile {
            width: 100%;
            display: block;
            margin-top: 5px;
        }
        
        .tile {
            padding: 10px;
            margin-bottom: 10px;
        }

        .badge {
            font-size: 0.9rem;
            width: 100%;
            text-align: center;
        }
    }

    /* Estilo limpio para los encabezados */
    .table-custom thead th {
        border: none;
        background: transparent;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
    }
</style>
<main class="app-content">
    <div class="app-title d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h3 class="title"><i class="fa fa-address-book"></i> Cuentas por Cobrar</h3>
            <p class="mb-0">Listado de clientes con saldo pendiente</p>
        </div>
        <div>
            <button type="button" class="btn btn-success font-weight-bold btn-block-mobile" data-toggle="modal" data-target="#modalCreditoDirectoGeneral">
                <i class="fa fa-plus-circle"></i> Nuevo Crédito Directo
            </button>
        </div>
    </div>

    <div class="basic-tb-hd text-center">          
        @include('layouts.partials.flash-messages')
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="tile">
                <div class="table-responsive">
                    <table class="table table-hover table-custom" id="tabla-clientes" style="width:100%">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th class="d-none d-md-table-cell">Identificación</th> 
                                <th>Saldo Pendiente</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clientes as $cliente)
                            <tr>
                                <td>
                                    <div class="font-weight-bold">{{ $cliente->nombre }}</div>
                                    @if(!empty($cliente->alias))
                                        <small class="text-muted d-block">({{ $cliente->alias }})</small>
                                    @endif
                                </td>
                                <td class="d-none d-md-table-cell text-muted">
                                    {{ $cliente->identificacion }}
                                </td>
                               <td>
                                   @php
                                       $saldo = $cliente->saldo_total_pendiente ?? 0;
                                   @endphp

                                   @if($saldo < 0)
                                       {{-- SALDO A FAVOR / ANTICIPO --}}
                                       <span class="badge badge-info px-3 py-2" data-toggle="tooltip" title="Saldo a Favor">
                                           +${{ number_format(abs($saldo), 2) }}
                                       </span>
                                   @elseif($saldo > 0)
                                       {{-- DEUDA PENDIENTE --}}
                                       <span class="badge badge-danger px-3 py-2" data-toggle="tooltip" title="Deuda Pendiente">
                                           ${{ number_format($saldo, 2) }}
                                       </span>
                                   @else
                                       {{-- AL DÍA (SIN DEUDA NI SALDO) --}}
                                       <span class="badge badge-success px-3 py-2">
                                           $0.00
                                       </span>
                                   @endif
                               </td>
                                <td class="text-center">
                                    <a href="{{ route('creditos.show', $cliente->id) }}" class="btn btn-info btn-sm btn-block-mobile">
                                        <i class="fa fa-eye"></i> <span class="d-none d-md-inline">Ver Detalle</span>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>


@include('creditos.modals.modal_credito_directo_general')
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#tabla-clientes').DataTable({
            responsive: true,
            language: {
                "decimal": "",
                "emptyTable": "No hay datos disponibles en la tabla",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "lengthMenu": "Mostrar _MENU_ registros",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron resultados",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            },
            dom: 'ftip',
            pageLength: 20
        });

        // Inicializar Select2 en el modal
        if ($.fn.select2) {
            $('.select2-modal').select2({
                dropdownParent: $('#modalCreditoDirectoGeneral'),
                width: '100%'
            });
        }
    });
</script>
@endsection