@extends('layouts.app')
@section('title') Estado de Cuenta @endsection

@section('content')
<main class="app-content">
    <div class="app-title">
        <div>
            <h1><i class="fa fa-user"></i> Estado de Cuenta</h1>
            <p class="text-muted">
                <strong>{{ $cliente->nombre }}</strong> | 
                {{ $cliente->identificacion }} | 
                {{ $cliente->telefono }}
            </p>
        </div>
        <div class="basic-tb-hd text-center">
            @include('layouts.partials.flash-messages')
        </div>
        <a href="{{ route('creditos.index') }}" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="tile p-0 border-left border-danger" style="border-left-width: 4px !important;">
                <div class="p-3">
                    <div class="text-muted text-uppercase small font-weight-bold">Deuda Total <span class="badge badge-danger">Pendiente</span></div>
                    <div class="d-flex align-items-baseline justify-content-between mt-1">
                        <span class="h3 mb-0 font-weight-bold text-danger" style="font-variant-numeric: tabular-nums;">
                            ${{ number_format($resumen['saldo_pendiente'], 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="tile p-0 border-left border-success" style="border-left-width: 4px !important;">
                <div class="p-3">
                    <div class="text-muted text-uppercase small font-weight-bold">Total Abonado <span class="badge badge-success">{{ $resumen['monto_inicial'] > 0 ? round(($resumen['total_abonado'] / $resumen['monto_inicial']) * 100, 1) : 0 }}%</span></div>
                    <div class="d-flex align-items-baseline justify-content-between mt-1">
                        <span class="h3 mb-0 font-weight-bold text-success" style="font-variant-numeric: tabular-nums;">
                            ${{ number_format($resumen['total_abonado'], 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="tile p-0 border-left {{ $resumen['saldo_a_favor'] > 0 ? 'border-info' : 'border-primary' }}" style="border-left-width: 4px !important;">
                <div class="p-3">
                    @if($resumen['saldo_a_favor'] > 0)
                        <div class="text-muted text-uppercase small font-weight-bold">Saldo a Favor <span class="badge badge-info">Disponible</span></div>
                        <div class="d-flex align-items-baseline justify-content-between mt-1">
                            <span id="saldo_a_favor_cliente" class="h3 mb-0 font-weight-bold text-info" style="font-variant-numeric: tabular-nums;">
                                ${{ number_format($resumen['saldo_a_favor'], 2) }}
                            </span>
                        </div>
                    @else
                        <div class="text-muted text-uppercase small font-weight-bold">Saldo Restante <span class="badge badge-primary">
                                {{ $resumen['monto_inicial'] > 0 ? round(($resumen['saldo_pendiente'] / $resumen['monto_inicial']) * 100, 1) : 0 }}%
                            </span></div>
                        <div class="d-flex align-items-baseline justify-content-between mt-1">
                            <span id="saldo_total_cliente" class="h3 mb-0 font-weight-bold text-primary" style="font-variant-numeric: tabular-nums;"
                                  data-valor="{{ $resumen['saldo_pendiente'] }}">
                                ${{ number_format($resumen['saldo_pendiente'], 2) }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="tile p-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-success font-weight-bold" 
                                onclick="abrirModalAbono({{ $cliente->toJson() }})">
                            <i class="fa fa-plus"></i> Abonar
                        </button>
                        @if(auth()->user()->esAdmin())
                            <button class="btn btn-warning font-weight-bold text-white" 
                                    onclick="abrirModalInteres({{ $cliente->toJson() }})" 
                                    title="Indexar a todos los créditos">
                                <i class="fa fa-line-chart"></i> Indexar
                            </button>
                        @endif
                        <a href="{{ route('creditos.productos', $cliente->id) }}" class="btn btn-info font-weight-bold">
                            <i class="fa fa-list-ul"></i> Historial de Productos
                        </a>
                        <button type="button" 
                                class="btn btn-primary font-weight-bold" 
                                onclick="abrirModalCreditoDirecto({{ $cliente->id }})">
                            <i class="fa fa-plus"></i> Agregar Crédito Directo
                        </button>
                    </div>
                    <a href="{{ route('creditos.pdf_estado_cuenta', $cliente->id) }}" 
                       class="btn btn-outline-dark font-weight-bold" 
                       target="_blank"
                       title="Descargar Estado de Cuenta PDF">
                        <i class="fas fa-file-pdf"></i> Imprimir
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-md-4 mb-4">
            <div class="tile p-0 shadow-sm">
                <div class="bg-dark text-white p-3 rounded-top">
                    <span class="font-weight-bold"><i class="fa fa-calculator"></i> Detalle</span>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted small">Monto Original</span>
                        <span class="font-weight-bold" style="font-variant-numeric: tabular-nums;">${{ number_format($resumen['monto_inicial'], 2) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted small">Intereses (Indexación)</span>
                        <span class="font-weight-bold text-warning" style="font-variant-numeric: tabular-nums;">+ ${{ number_format($resumen['total_intereses'], 2) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted small">Total Abonado</span>
                        <span class="font-weight-bold text-success" style="font-variant-numeric: tabular-nums;">- ${{ number_format($resumen['total_abonado'], 2) }}</span>
                    </li>
                    @if($resumen['saldo_a_favor'] > 0)
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 bg-info text-white">
                        <span class="small"><i class="fa fa-star"></i> Saldo a Favor</span>
                        <div class="text-right">
                            <strong class="d-block" style="font-variant-numeric: tabular-nums;">${{ number_format($resumen['saldo_a_favor'], 2) }}</strong>
                            <button type="button" class="btn btn-xs btn-outline-light mt-1 py-0 px-2" style="font-size: 10px;" onclick="abrirModalGestionSaldo()">
                                Gestionar
                            </button>
                        </div>
                    </li>
                    @endif
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3" style="background: #f8f9fa;">
                        <span class="font-weight-bold text-primary">Saldo Pendiente Neto</span>
                        <span class="h5 mb-0 font-weight-bold text-primary" style="font-variant-numeric: tabular-nums;">${{ number_format($resumen['saldo_pendiente'], 2) }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-lg-9 col-md-8">
            <div class="tile p-3">
                <ul class="nav nav-tabs nav-justified" id="tabsEstadoCuenta" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active font-weight-bold" id="tab-creditos-tab" data-toggle="tab" href="#tab-creditos" role="tab" aria-controls="tab-creditos" aria-selected="true">
                            <i class="fa fa-credit-card text-primary"></i> Créditos / Saldos
                            <span class="badge badge-primary ml-1">{{ $cliente->creditos->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" id="tab-abonos-tab" data-toggle="tab" href="#tab-abonos" role="tab" aria-controls="tab-abonos" aria-selected="false">
                            <i class="fa fa-history text-success"></i> Historial de Abonos
                            <span class="badge badge-success ml-1">{{ $historialAbonos->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" id="tab-intereses-tab" data-toggle="tab" href="#tab-intereses" role="tab" aria-controls="tab-intereses" aria-selected="false">
                            <i class="fa fa-line-chart text-warning"></i> Indexación
                            <span class="badge badge-warning ml-1">{{ $historialIntereses->count() }}</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content pt-3" id="tabsEstadoCuentaContent">

                    <div class="tab-pane fade show active" id="tab-creditos" role="tabpanel" aria-labelledby="tab-creditos-tab">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover" id="tabla-creditos">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Código / Referencia</th>
                                        <th>Tipo</th>
                                        <th class="text-right">Monto Original ($)</th>
                                        <th class="text-right">Saldo ($)</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($cliente->creditos as $credito)
                                    @php
                                        $esAnticipo = $credito->estado === 'anticipo' || $credito->saldo_pendiente < 0;
                                    @endphp
                                    <tr class="{{ $esAnticipo ? 'table-info' : '' }}">
                                        <td class="small text-nowrap" data-order="{{ $credito->created_at->timestamp }}">
                                            {{ $credito->created_at->format('d/m/Y h:i A') }}
                                        </td>
                                        <td>
                                            <strong>
                                                @if($esAnticipo)
                                                    ANT-{{ $credito->id }}
                                                @else
                                                    {{ $credito->venta->codigo_factura ?? 'CRD-' . $credito->id }}
                                                @endif
                                            </strong>
                                        </td>
                                        <td>
                                            @if($esAnticipo)
                                                <span class="badge badge-info">
                                                    <i class="fas fa-wallet"></i> Saldo a Favor
                                                </span>
                                            @elseif($credito->venta && $credito->venta->detalles->isEmpty())
                                                <span class="badge badge-secondary" style="background-color: #6f42c1; color: #fff;">
                                                    <i class="fas fa-hand-holding-usd"></i> Directo
                                                </span>
                                            @else
                                                <span class="badge badge-info">
                                                    <i class="fas fa-shopping-cart"></i> Venta
                                                </span>
                                            @endif
                                        </td>
                                        <td class="font-weight-bold text-right" style="font-variant-numeric: tabular-nums;">
                                            ${{ number_format($credito->monto_inicial, 2) }}
                                        </td>
                                        <td class="font-weight-bold text-right {{ $esAnticipo ? 'text-info' : ($credito->saldo_pendiente > 0 ? 'text-danger' : 'text-success') }}" style="font-variant-numeric: tabular-nums;">
                                            @if($esAnticipo)
                                                +${{ number_format(abs($credito->saldo_pendiente), 2) }}
                                            @else
                                                ${{ number_format($credito->saldo_pendiente, 2) }}
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($esAnticipo)
                                                <span class="badge badge-info">Anticipo</span>
                                            @else
                                                <span class="badge badge-{{ $credito->estado === 'pendiente' ? 'danger' : 'success' }}">
                                                    {{ ucfirst($credito->estado) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button type="button" 
                                                        class="btn btn-danger btn-sm btn-eliminar-credito" 
                                                        data-toggle="modal" 
                                                        data-target="#modalEliminarCredito"
                                                        data-id="{{ $credito->id }}"
                                                        data-codigo="{{ $esAnticipo ? 'ANT-' . $credito->id : ($credito->venta->codigo_factura ?? 'CRD-' . $credito->id) }}"
                                                        data-monto="{{ number_format($credito->monto_inicial, 2) }}"
                                                        data-saldo="{{ number_format($credito->saldo_pendiente, 2) }}"
                                                        data-tieneproductos="{{ ($credito->venta && $credito->venta->detalles->isNotEmpty()) ? '1' : '0' }}"
                                                        title="Eliminar Crédito/Anticipo">
                                                    <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-3">No hay créditos ni saldos registrados para este cliente.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-abonos" role="tabpanel" aria-labelledby="tab-abonos-tab">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover" id="tabla-historial-abonos">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Fecha / Hora</th>
                                        <th>Cajero</th>
                                        <th class="d-md-table-cell">#Crédito</th>
                                        <th class="d-md-table-cell text-right">Monto ($)</th>
                                        <th class="d-md-table-cell">Forma de Pago</th>
                                        <th class="d-md-table-cell">Detalles</th>
                                        <th>Estado</th>
                                        @can('anular-abono') <th class="text-center">Acción</th> @endcan
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($historialAbonos as $abono)
                                    <tr style="{{ $abono->estado === 'Anulado' ? 'opacity: 0.6; text-decoration: line-through;' : '' }}">
                                        <td class="small text-nowrap" data-order="{{ $abono->created_at->timestamp }}">
                                            {{ $abono->created_at->format('d/m/Y h:i A') }}
                                        </td>
                                        <td>{{ $abono->usuario->name }}</td>
                                        <td>
                                            <span class="badge badge-light border">ID: {{ $abono->id_credito }}</span>
                                        </td>
                                        <td class="font-weight-bold text-success text-right" style="font-variant-numeric: tabular-nums;">
                                            ${{ number_format($abono->monto_pagado_usd, 2) }}
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @if($abono->pago_usd_efectivo > 0)
                                                    <small class="badge badge-light border">Efe $: {{ number_format($abono->pago_usd_efectivo, 2) }}</small>
                                                @endif
                                                @if($abono->pago_bs_efectivo > 0)
                                                    <small class="badge badge-light border">Efe Bs: {{ number_format($abono->pago_bs_efectivo, 2) }}</small>
                                                @endif
                                                @if($abono->pago_punto_bs > 0)
                                                    <small class="badge badge-light border">Punto: {{ number_format($abono->pago_punto_bs, 2) }}</small>
                                                @endif
                                                @if($abono->pago_pagomovil_bs > 0)
                                                    <small class="badge badge-light border">P.Móvil: {{ number_format($abono->pago_pagomovil_bs, 2) }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td><small class="text-muted">{{ $abono->detalles ?? 'N/A' }}</small></td>
                                        <td>
                                            <span class="badge badge-{{ $abono->estado === 'Realizado' ? 'success' : 'danger' }}">
                                                {{ $abono->estado }}
                                            </span>
                                        </td>
                                        @can('anular-abono')
                                        <td class="text-center">
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
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-intereses" role="tabpanel" aria-labelledby="tab-intereses-tab">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover" id="tabla-historial-intereses">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th class="d-md-table-cell">Crédito #</th>
                                        <th>Admin</th>
                                        <th class="d-md-table-cell text-right">Porcentaje</th>
                                        <th class="d-md-table-cell text-right">Monto ($)</th>
                                        <th>Estado</th>
                                        @if(auth()->user()->esAdmin()) <th class="text-center">Acción</th> @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($historialIntereses as $interes)
                                    <tr style="{{ $interes->estado === 'anulado' ? 'opacity: 0.6; text-decoration: line-through;' : '' }}">
                                        <td class="small text-nowrap" data-order="{{ $interes->aplicado_en->timestamp }}">
                                            {{ $interes->aplicado_en->format('d/m/Y h:i A') }}
                                        </td>
                                        <td>
                                            <span class="badge badge-light border">ID: {{ $interes->id_credito }}</span>
                                        </td>
                                        <td>{{ $interes->administrador?->name ?? 'N/A' }}</td>
                                        <td class="text-primary font-weight-bold text-right">{{ $interes->porcentaje }}%</td>
                                        <td class="font-weight-bold text-danger text-right" style="font-variant-numeric: tabular-nums;">${{ number_format($interes->monto_interes, 2) }}</td>
                                        <td>
                                            <span class="badge badge-{{ $interes->estado === 'aplicado' ? 'warning' : 'danger' }}">
                                                {{ ucfirst($interes->estado) }}
                                            </span>
                                        </td>
                                        @if(auth()->user()->esAdmin())
                                        <td class="text-center">
                                            @if($interes->estado === 'aplicado')
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Anular Indexación"
                                                        onclick="confirmarAnulacionInteres('{{ route('creditos.interes.anular', $interes->id) }}', '{{ number_format($interes->monto_interes, 2) }}')">
                                                    <i class="fa fa-ban"></i>
                                                </button>
                                            @endif
                                        </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div> </div> </div>
    </div>
</main>
@include('creditos.modals.abono_modal')
@include('creditos.modals.modal_anular_abono')
@include('creditos.modals.modal_anular_interes')
@include('creditos.modals.modal_gestion_saldo')
@include('creditos.modals.modal_interes')
@include('creditos.modals.modal_credito_directo')
@include('creditos.modals.modal_eliminar_credito')
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        if ($('#tabla-historial-abonos').length) {
            $('#tabla-historial-abonos').DataTable({
                retrieve: true,
                pageLength: 5,
                lengthMenu: [5, 10, 20],
                responsive: true,
                autoWidth: false,
                language: {
                    search: "Buscar:",
                    paginate: { next: "Sig", previous: "Ant" },
                    info: "Mostrando _START_ a _END_ de _TOTAL_ abonos",
                    emptyTable: "No hay abonos registrados para este cliente."
                },
                dom: '<"row"<"col-sm-12"f>>t<"row"<"col-sm-12"p>>',
                order: [[0, 'desc']]
            });
        }

        if ($('#tabla-historial-intereses').length) {
            if ($.fn.DataTable.isDataTable('#tabla-historial-intereses')) {
                $('#tabla-historial-intereses').DataTable().destroy();
            }

            $('#tabla-historial-intereses').DataTable({
                destroy: true,
                pageLength: 5,
                lengthMenu: [5, 10],
                responsive: true,
                autoWidth: false,
                language: {
                    search: "Buscar:",
                    paginate: { next: "Sig", previous: "Ant" },
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    emptyTable: "No se han aplicado indexaciones a los créditos de este cliente."
                },
                dom: '<"row"<"col-sm-12"f>>t<"row"<"col-sm-12"p>>',
                order: [[0, 'desc']]
            });
        }

        if ($('#tabla-creditos').length) {
            if ($.fn.DataTable.isDataTable('#tabla-creditos')) {
                $('#tabla-creditos').DataTable().destroy();
            }

            $('#tabla-creditos').DataTable({
                destroy: true,
                pageLength: 5,
                lengthMenu: [5, 10, 20],
                responsive: true,
                autoWidth: false,
                language: {
                    search: "Buscar:",
                    paginate: { next: "Sig", previous: "Ant" },
                    info: "Mostrando _START_ a _END_ de _TOTAL_ créditos",
                    emptyTable: "No hay créditos registrados para este cliente."
                },
                dom: '<"row"<"col-sm-12"f>>t<"row"<"col-sm-12"p>>',
                order: [[0, 'desc']]
            });
        }

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });
    });

    function confirmarAnulacion(url, monto) {
        $('#formAnularAbono').attr('action', url);
        $('#montoAbonoText').text('$' + monto);
        $('#modalAnularAbono').modal('show');
    }

    function abrirModalInteres(cliente) {
        let saldoTotal = cliente.creditos.reduce((sum, c) => sum + parseFloat(c.saldo_pendiente), 0);
        $.ajax({
            url: `/creditos/${cliente.id}/modal-interes`, 
            type: 'GET',
            success: function(html) {
                $('#contenedor-modal-interes').remove();
                $('body').append('<div id="contenedor-modal-interes">' + html + '</div>');
                
                let url = "{{ route('creditos.aplicarInteres', ':id') }}";
                $('#formAplicarInteres').attr('action', url.replace(':id', cliente.creditos[0].id));
                
                $('#saldo_base_global').text('$' + saldoTotal.toFixed(2));
                $('#saldo_base_global').data('valor', saldoTotal);

                $('#modalAplicarInteres').modal('show');
            },
            error: function(xhr) {
                var msj = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : "Error al cargar modal";
                
                $('.app-content').prepend(`
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error:</strong> ${msj}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                `);
            }
        });
    }

    $(document).on('submit', '#formAplicarInteres', function(e) {
        e.preventDefault();
        let form = $(this);
        let btn = form.find('button[type="submit"]');
        
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Indexando...');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    $('#modalAplicarInteres').modal('hide');
                    
                    $('.app-content').prepend(`
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>¡Éxito!</strong> ${response.mensaje}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    `);
                    
                    setTimeout(() => { location.reload(); }, 2500);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).text('Confirmar Indexación');
                let errorMsg = (xhr.responseJSON && xhr.responseJSON.mensaje) ? xhr.responseJSON.mensaje : "Error en el servidor";
                
                $('.modal-body').prepend(`
                    <div class="alert alert-danger">
                        ${errorMsg}
                    </div>
                `);
            }
        });
    });

    $(document).on('input', '#input_porcentaje', function() {
        let porcentaje = parseFloat($(this).val()) || 0;
        let saldoBase = parseFloat($('#saldo_base_global').data('valor')) || 0;
        let btn = $('#btn_confirmar_index');

        if (porcentaje > 0) {
            let montoInteres = saldoBase * (porcentaje / 100);
            let nuevoTotal = saldoBase + montoInteres;
            $('#preview_interes').text('+$' + montoInteres.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#preview_total').text('$' + nuevoTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            btn.prop('disabled', false);
        } else {
            $('#preview_interes').text('$0.00');
            $('#preview_total').text('$' + saldoBase.toFixed(2));
            btn.prop('disabled', true);
        }
    });

    function abrirModalAbono(cliente) {
        $('#formAbono')[0].reset();
        
        // Ocultar alertas dinámicas
        $('#alerta_saldo_favor').addClass('d-none');
        $('#error-desglose').addClass('d-none');
        $('.input-desglose').removeClass('is-invalid');

        if (!cliente.creditos || cliente.creditos.length === 0) {
            alert("El cliente no posee créditos pendientes.");
            return;
        }

        let primerCredito = cliente.creditos[0];
        let url = "{{ route('creditos.abono', ':id') }}";
        url = url.replace(':id', primerCredito.id);
        
        $('#formAbono').attr('action', url);
        $('#nombre_cliente').text(cliente.nombre);
        
        // Calcular saldo total
        let saldoTotal = cliente.creditos.reduce((sum, c) => sum + parseFloat(c.saldo_pendiente), 0);
        $('#txt_saldo_pendiente').text('$' + saldoTotal.toFixed(2));

        $('#modalAbono').modal('show');
    }
    $(document).on('input', '#monto_total_usd', function() {
        let montoAbono = parseFloat($(this).val()) || 0;
        let saldoPendiente = parseFloat($('#txt_saldo_pendiente').text().replace(/[^0-9.-]+/g, "")) || 0;

        if (montoAbono > saldoPendiente && saldoPendiente > 0) {
            let exceso = montoAbono - saldoPendiente;
            $('#monto_saldo_favor').text('$' + exceso.toFixed(2));
            $('#alerta_saldo_favor').removeClass('d-none');
        } else {
            $('#alerta_saldo_favor').addClass('d-none');
        }
    });

    $('#formAbono').on('submit', function(e) {
        let form = this;
        let saldoPendiente = parseFloat($('#txt_saldo_pendiente').text().replace(/[^0-9.-]+/g, "")) || 0;
        let montoAbono = parseFloat($('#monto_total_usd').val()) || 0;

        // 1. VALIDACIÓN DEL DESGLOSE DE PAGO
        let totalDesglose = 0;
        let inputs = $('.input-desglose');
        let errorDiv = $('#error-desglose');

        $('.input-desglose').each(function() {
            let valor = parseFloat($(this).val()) || 0;
            totalDesglose += valor;
        });

        if (totalDesglose <= 0) {
            e.preventDefault();
            errorDiv.removeClass('d-none').hide().fadeIn();
            inputs.addClass('is-invalid');
            $('.modal-body').animate({ scrollTop: 0 }, 'slow');
            return false;
        }
        $('.input-desglose').removeClass('is-invalid');

        // 2. CONFIRMACIÓN PROFESIONAL DE EXCEDENTE (SweetAlert2)
        if (montoAbono > saldoPendiente && saldoPendiente > 0) {
            e.preventDefault(); // Detenemos el envío automático
            
            let exceso = montoAbono - saldoPendiente;

            Swal.fire({
                title: '<strong>Confirmar Saldo a Favor</strong>',
                icon: 'info',
                html: `
                    <div class="text-left font-weight-normal fs-6">
                        <p class="mb-2">El monto ingresado excede la deuda actual del cliente. Se generará un anticipo automático.</p>
                        <div class="card bg-light border-0 my-3 p-3 text-dark">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Deuda Pendiente:</span>
                                <strong class="text-danger">$${saldoPendiente.toFixed(2)}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Monto Ingresado:</span>
                                <strong class="text-dark">$${montoAbono.toFixed(2)}</strong>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="font-weight-bold text-primary">Saldo a Favor del Cliente:</span>
                                <span class="badge badge-info fs-6 px-2 py-1">+$${exceso.toFixed(2)}</span>
                            </div>
                        </div>
                        <p class="mb-0 text-muted small"><i class="fa fa-info-circle"></i> La deuda quedará totalmente liquidada y el excedente disponible para futuras compras.</p>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa fa-check"></i> Sí, Procesar Pago',
                cancelButtonText: 'Corregir Monto',
                reverseButtons: true,
                customClass: {
                    popup: 'shadow-lg rounded-3'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Deshabilitar botón para evitar envíos dobles
                    $(form).find('button[type="submit"]').prop('disabled', true);
                    form.submit(); // Dispara el envío real del formulario
                }
            });

            return false;
        }
    });

    function confirmarAnulacionAbono(url, monto) {
        $('#formAnularAbono').attr('action', url);
        $('#montoAbonoText').text('$' + parseFloat(monto).toFixed(2));
        $('#modalAnularAbono').modal('show');
    }

    $(document).on('input', '.input-desglose', function() {
        let val = parseFloat($(this).val()) || 0;
        if (val > 0) {
            $('.input-desglose').removeClass('is-invalid').addClass('is-valid');
            $('#error-desglose').fadeOut().addClass('d-none');
        }
    });

    function confirmarAnulacionInteres(url, monto) {
        $('#formAnularInteres')[0].reset();
        $('#formAnularInteres').attr('action', url);
        $('#montoInteresText').text('$' + monto);
        $('#modalAnularInteres').modal('show');
    }

    function abrirModalGestionSaldo() {
        let modal = $('#modalReembolso'); 
        if (modal.length > 0) {
            modal.modal('show');
        } else {
            console.error("El modal #modalReembolso no se encontró.");
        }
    }

    $('#input_porcentaje').on('input', function() {
        let porcentaje = parseFloat($(this).val()) || 0;
        let saldoBase = parseFloat($('#saldo_base').data('valor'));
        let montoInteres = saldoBase * (porcentaje / 100);

        $('#preview_interes').text('$' + montoInteres.toFixed(2));
        $('#preview_total').text('$' + (saldoBase + montoInteres).toFixed(2));

        $('#btn_confirmar_index').prop('disabled', porcentaje <= 0);
    });

    function abrirModalCreditoDirecto(clienteId) {
        $('#formCreditoDirecto')[0].reset();
        $('#pin_autorizacion_directo').val('');
        
        @cannot('gestionar-creditos-avanzado')
            $('#estado_pin_texto').html('Requiere autorización de supervisor').removeClass('text-success').addClass('text-dark');
            $('#bloque_pin_warning').removeClass('alert-success').addClass('alert-warning');
        @endcannot

        $('#modalCreditoDirecto').modal('show');
    }

    $(document).ready(function() {

        $('#btnSolicitarPinDirecto').on('click', function() {
            let monto = $('#monto_credito_usd').val();

            if (!monto || parseFloat(monto) <= 0) {
                Swal.fire('Monto Requerido', 'Por favor ingresa primero el monto del crédito antes de solicitar el PIN.', 'warning');
                return;
            }

            Swal.fire({
                title: '¿Solicitar Autorización?',
                text: "Se enviará un PIN de 6 dígitos al supervisor para autorizar $" + monto,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, enviar PIN',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false 
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post("{{ route('ventas.solicitar_pin') }}", {
                        _token: "{{ csrf_token() }}",
                        local_nombre: "{{ auth()->user()->localActual()->nombre ?? 'Local' }}",
                        cliente_nombre: "{{ $cliente->nombre ?? 'Cliente' }}",
                        monto_total: monto,
                        cantidad_items: 1
                    }, function(response) {

                        if(response.wa_link) { window.open(response.wa_link, '_blank'); }

                        Swal.fire({
                            title: 'Introduce el PIN',
                            text: 'El supervisor recibió un código de 6 dígitos',
                            input: 'text',
                            inputAttributes: { maxlength: 6, autocapitalize: 'off', id: 'swal_pin_input' },
                            showCancelButton: true,
                            confirmButtonText: 'Validar PIN',
                            cancelButtonText: 'Cancelar',
                            showLoaderOnConfirm: true,
                            allowOutsideClick: false,
                            didOpen: () => {
                                $(document).off('focusin.bs.modal');
                                if ($.fn.modal && $.fn.modal.Constructor) {
                                    $.fn.modal.Constructor.prototype._enforceFocus = function() {};
                                }

                                setTimeout(() => {
                                    const input = Swal.getInput();
                                    if (input) {
                                        $(input).removeAttr('readonly').removeAttr('disabled').focus();
                                    }
                                }, 200);
                            },
                            preConfirm: (pin) => {
                                return $.post("{{ route('ventas.verificar_pin') }}", {
                                    _token: "{{ csrf_token() }}",
                                    pin: pin
                                }).done(res => {
                                    $('#pin_autorizacion_directo').val(pin); 
                                }).fail(error => {
                                    Swal.showValidationMessage(error.responseJSON.message || 'PIN Incorrecto');
                                });
                            }
                        }).then((res) => {
                            if (res.isConfirmed) {
                                $('#estado_pin_texto').html('<i class="fa fa-check-circle text-success"></i> Crédito Autorizado por Supervisor').removeClass('text-dark').addClass('text-success');
                                $('#bloque_pin_warning').removeClass('alert-warning').addClass('alert-success');
                                Swal.fire('Autorizado', 'Crédito habilitado con éxito.', 'success');
                            } else {
                                $('#pin_autorizacion_directo').val('');
                            }
                        });
                    });
                }
            });
        });

        $('#formCreditoDirecto').on('submit', function(e) {
            @cannot('gestionar-creditos-avanzado')
                let pin = $('#pin_autorizacion_directo').val();
                if (!pin) {
                    e.preventDefault();
                    Swal.fire('Autorización Requerida', 'Debes solicitar y validar el PIN del supervisor para guardar este crédito.', 'error');
                    return false;
                }
            @endcannot
        });

        $('.btn-eliminar-credito').on('click', function() {
            var id = $(this).data('id');
            var codigo = $(this).data('codigo');
            var monto = $(this).data('monto');
            var saldo = $(this).data('saldo');
            var tieneProductos = $(this).data('tieneproductos');

            var actionUrl = "{{ url('creditos') }}/" + id;
            $('#formEliminarCredito').attr('action', actionUrl);

            $('#eliminar_credito_codigo').text(codigo);
            $('#eliminar_credito_monto').text(monto);
            $('#eliminar_credito_saldo').text(saldo);

            if (tieneProductos == '1') {
                $('#msg_retorno_stock').removeClass('d-none');
                $('#msg_credito_directo').addClass('d-none');
            } else {
                $('#msg_credito_directo').removeClass('d-none');
                $('#msg_retorno_stock').addClass('d-none');
            }
        });
    });
</script>
@endsection