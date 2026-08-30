@extends('layouts.app')
@section('title') Estado de Cuenta @endsection

@section('content')
<main class="app-content">
    <div class="app-title">
        <div>
            <h1><i class="fa fa-user"></i> Estado de Cuenta</h1>
            <p class="text-muted mb-0">
                <strong>{{ $cliente->nombre }}</strong> | 
                Identificación: {{ $cliente->identificacion ?? 'N/A' }} | 
                Teléfono: {{ $cliente->telefono ?? 'N/A' }}
            </p>
        </div>
        <div class="basic-tb-hd text-center">
            @include('layouts.partials.flash-messages')
        </div>
        <a href="{{ route('creditos.index') }}" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i> Volver
        </a>
    </div>

    {{-- Tarjetas de Resumen Financiero --}}
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="tile p-0 border-left border-danger shadow-sm" style="border-left-width: 4px !important;">
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
            <div class="tile p-0 border-left border-success shadow-sm" style="border-left-width: 4px !important;">
                <div class="p-3">
                    <div class="text-muted text-uppercase small font-weight-bold">
                        Total Abonado 
                        <span class="badge badge-success">
                            {{ $resumen['monto_inicial'] > 0 ? round(($resumen['total_abonado'] / $resumen['monto_inicial']) * 100, 1) : 0 }}%
                        </span>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between mt-1">
                        <span class="h3 mb-0 font-weight-bold text-success" style="font-variant-numeric: tabular-nums;">
                            ${{ number_format($resumen['total_abonado'], 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="tile p-0 border-left {{ $resumen['saldo_a_favor'] > 0 ? 'border-info' : 'border-secondary' }} shadow-sm" style="border-left-width: 4px !important;">
                <div class="p-3">
                    <div class="text-muted text-uppercase small font-weight-bold">
                        Saldo a Favor 
                        @if($resumen['saldo_a_favor'] > 0)
                            <span class="badge badge-info">Disponible</span>
                        @else
                            <span class="badge badge-secondary">Agotado</span>
                        @endif
                    </div>
                    
                    <div class="d-flex align-items-baseline justify-content-between mt-1">
                        <span id="saldo_a_favor_cliente" class="h3 mb-0 font-weight-bold {{ $resumen['saldo_a_favor'] > 0 ? 'text-info' : 'text-muted' }}" style="font-variant-numeric: tabular-nums;">
                            ${{ number_format($resumen['saldo_a_favor'], 2) }}
                        </span>
                        
                        @if($resumen['saldo_a_favor'] > 0)
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="abrirModalGestionSaldo()">
                                <i class="fas fa-cog"></i> Gestionar
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Barra de Acciones Rápidas (Estilo AdminLTE Optimizado) --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="tile p-3 shadow-sm">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    
                    {{-- Grupo de Acciones Operativas Principales (Izquierda) --}}
                    <div class="d-flex flex-wrap align-items-center mb-2 mb-md-0" style="gap: 8px;">
                        <button class="btn btn-success font-weight-bold shadow-sm" onclick="abrirModalAbono({{ $cliente->toJson() }})">
                            <i class="fa fa-plus-circle mr-1"></i> Registrar Abono
                        </button>

                        <button type="button" class="btn btn-primary font-weight-bold shadow-sm" onclick="abrirModalCreditoDirecto({{ $cliente->id }})">
                            <i class="fa fa-cart-plus mr-1"></i> Crédito Directo
                        </button>

                        @if(auth()->user()->esAdmin())
                            <button class="btn btn-warning font-weight-bold text-white shadow-sm" onclick="abrirModalInteres({{ $cliente->toJson() }})" title="Indexar a todos los créditos pendientes">
                                <i class="fa fa-line-chart mr-1"></i> Indexar
                            </button>
                        @endif
                    </div>

                    {{-- Grupo de Reportes, Historiales y Búsqueda Avanzada (Derecha) --}}
                    <div class="d-flex flex-wrap align-items-center" style="gap: 8px;">
                        <a href="{{ route('creditos.productos', $cliente->id) }}" class="btn btn-outline-info font-weight-bold shadow-sm">
                            <i class="fa fa-list-ul mr-1"></i> Productos
                        </a>

                        <button type="button" class="btn btn-outline-secondary font-weight-bold shadow-sm" data-toggle="modal" data-target="#modalHistorialFechas" title="Buscar registros anteriores y pagados">
                            <i class="fa fa-calendar-alt mr-1"></i> Historial por Fecha
                        </button>

                        <a href="{{ route('creditos.pdf_estado_cuenta', $cliente->id) }}" class="btn btn-outline-dark font-weight-bold shadow-sm" target="_blank" title="Imprimir Estado de Cuenta PDF">
                            <i class="fa fa-file-pdf mr-1"></i> Imprimir
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Detalles y Pestañas de Tablas --}}
    <div class="row">
        {{-- Desglose Lateral --}}
        <div class="col-lg-3 col-md-4 mb-4">
            <div class="tile p-0 shadow-sm">
                <div class="bg-dark text-white p-3 rounded-top">
                    <span class="font-weight-bold"><i class="fa fa-calculator"></i> Desglose Global</span>
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
                        <span class="small font-weight-bold"><i class="fa fa-star"></i> Saldo a Favor</span>
                        <div class="text-right">
                            <strong class="d-block" style="font-variant-numeric: tabular-nums;">+${{ number_format($resumen['saldo_a_favor'], 2) }}</strong>
                            <button type="button" class="btn btn-xs btn-outline-light mt-1 py-0 px-2" style="font-size: 11px;" onclick="abrirModalGestionSaldo()">
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

        {{-- Contenido Principal de Tablas --}}
        <div class="col-lg-9 col-md-8">
            <div class="tile p-3 shadow-sm">
                <ul class="nav nav-tabs nav-justified" id="tabsEstadoCuenta" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active font-weight-bold" id="tab-creditos-tab" data-toggle="tab" href="#tab-creditos" role="tab" aria-controls="tab-creditos" aria-selected="true">
                            <i class="fa fa-credit-card text-primary"></i> Créditos y Anticipos
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

                    {{-- TAB 1: CREDITOS Y ANTICIPOS --}}
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

                    {{-- TAB 2: HISTORIAL DE ABONOS --}}
                    <div class="tab-pane fade" id="tab-abonos" role="tabpanel" aria-labelledby="tab-abonos-tab">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover" id="tabla-historial-abonos">
                                <thead class="thead-light">
                                    <tr>
                                        <th>N° Recibo / Fecha</th>
                                        <th>Cajero / Caja</th>
                                        <th>Créditos Afectados</th>
                                        <th class="text-right">Monto Total ($)</th>
                                        <th>Forma de Pago</th>
                                        <th>Detalles / Nota</th>
                                        <th>Estado</th>
                                        @can('anular-abono') <th class="text-center">Acción</th> @endcan
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($historialAbonos as $abono)
                                    @php
                                        $montoTotal = $abono->monto_total_usd ?? 0;
                                        $esReembolso = $montoTotal < 0;
                                    @endphp
                                    <tr style="{{ $abono->estado === 'Anulado' ? 'opacity: 0.6; text-decoration: line-through;' : '' }}" class="{{ $esReembolso ? 'table-warning' : '' }}">
                                        <td class="small text-nowrap" data-order="{{ $abono->created_at->timestamp }}">
                                            <strong>#{{ str_pad($abono->id, 6, '0', STR_PAD_LEFT) }}</strong><br>
                                            <span class="text-muted">{{ $abono->created_at->format('d/m/Y h:i A') }}</span>
                                        </td>
                                        <td>
                                            <small class="d-block font-weight-bold">{{ $abono->usuario->name ?? 'N/A' }}</small>
                                            @if($abono->caja)
                                                <small class="text-muted"><i class="fa fa-cash-register"></i> {{ $abono->caja->nombre ?? 'Caja' }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap" style="gap: 4px;">
                                                @forelse($abono->getRelation('detalles') ?? [] as $detalle)
                                                    <span class="badge badge-light border" title="Monto aplicado al crédito: ${{ number_format($detalle->monto_aplicado_usd, 2) }}">
                                                        #{{ $detalle->id_credito }} (${{ number_format($detalle->monto_aplicado_usd, 2) }})
                                                    </span>
                                                @empty
                                                    <span class="badge badge-secondary">Sin desglose</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td class="font-weight-bold text-right {{ $esReembolso ? 'text-danger' : 'text-success' }}" style="font-variant-numeric: tabular-nums;">
                                            {{ $esReembolso ? '-' : '' }}${{ number_format(abs($montoTotal), 2) }}
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap" style="gap: 2px;">
                                                @if(($abono->pago_usd_efectivo ?? 0) > 0)
                                                    <small class="badge badge-light border">Efe $: {{ number_format($abono->pago_usd_efectivo, 2) }}</small>
                                                @endif
                                                @if(($abono->pago_bs_efectivo ?? 0) > 0)
                                                    <small class="badge badge-light border">Efe Bs: {{ number_format($abono->pago_bs_efectivo, 2) }}</small>
                                                @endif
                                                @if(($abono->pago_punto_bs ?? 0) > 0)
                                                    <small class="badge badge-light border">Punto: {{ number_format($abono->pago_punto_bs, 2) }}</small>
                                                @endif
                                                @if(($abono->pago_pagomovil_bs ?? 0) > 0)
                                                    <small class="badge badge-light border">P.Móvil: {{ number_format($abono->pago_pagomovil_bs, 2) }}</small>
                                                @endif
                                                @if($esReembolso)
                                                    <small class="badge badge-warning text-dark"><i class="fas fa-undo"></i> Reembolso / Devolución</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $abono->getAttribute('detalles') ?? 'Abono general' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $abono->estado === 'Realizado' ? 'success' : 'danger' }}">
                                                {{ $abono->estado }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                {{-- BOTÓN EDITAR --}}
                                                @can('editar-abono')
                                                    @if($abono->estado === 'Realizado' && !$esReembolso)
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-warning"
                                                                onclick="editarAbono({{ $abono->id }})"
                                                                title="Editar Abono">
                                                            <i class="fa fa-edit text-dark" style="opacity: 1;"></i>
                                                        </button>
                                                    @endif
                                                @endcan

                                                {{-- BOTÓN ANULAR --}}
                                                @can('anular-abono')
                                                    @if($abono->estado === 'Realizado' && !$esReembolso)
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                onclick="confirmarAnulacion('{{ route('abonos.anular', $abono->id) }}', '{{ number_format($montoTotal, 2) }}')"
                                                                title="Anular Abono">
                                                            <i class="fa fa-ban text-danger" style="opacity: 1;"></i>
                                                        </button>
                                                    @endif
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- TAB 3: INDEXACION --}}
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
                                        <td class="font-weight-bold text-danger text-right" style="font-variant-numeric: tabular-nums;">+${{ number_format($interes->monto_interes, 2) }}</td>
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

                </div>
            </div>
        </div>
    </div>
</main>

@include('creditos.modals.abono_modal')
@include('creditos.modals.modalEditarAbono')
@include('creditos.modals.modal_anular_abono')
@include('creditos.modals.modal_anular_interes')
@include('creditos.modals.modal_gestion_saldo')
@include('creditos.modals.modal_interes')
@include('creditos.modals.modal_credito_directo')
@include('creditos.modals.modal_eliminar_credito')
@include('creditos.modals.modal_historial_crediticio')
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // DataTables Inicialización
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

    function abrirModalAbono(cliente) {
        $('#formAbono')[0].reset();
        
        $('#alerta_saldo_favor').addClass('d-none');
        $('#error-desglose').addClass('d-none');
        $('.input-desglose').removeClass('is-invalid');

        // Filtrar solo créditos pendientes válidos para abonar
        let creditosPendientes = cliente.creditos ? cliente.creditos.filter(c => c.estado === 'pendiente' && parseFloat(c.saldo_pendiente) > 0) : [];

        if (creditosPendientes.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Sin deudas pendientes',
                text: 'El cliente no tiene deudas pendientes activas para abonar.'
            });
            return;
        }

        let primerCredito = creditosPendientes[0];
        let url = "{{ route('creditos.abono', ':id') }}";
        url = url.replace(':id', primerCredito.id);
        
        $('#formAbono').attr('action', url);
        $('#nombre_cliente').text(cliente.nombre);
        
        // Calcular la deuda pendiente real acumulada
        let saldoTotal = creditosPendientes.reduce((sum, c) => sum + parseFloat(c.saldo_pendiente), 0);
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

        // Validar Desglose
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

        // Confirmar excedente que pasará a Saldo a Favor / Anticipo
        if (montoAbono > saldoPendiente && saldoPendiente > 0) {
            e.preventDefault();
            let exceso = montoAbono - saldoPendiente;

            Swal.fire({
                title: '<strong>Confirmar Generación de Saldo a Favor</strong>',
                icon: 'info',
                html: `
                    <div class="text-left font-weight-normal fs-6">
                        <p class="mb-2">El monto ingresado excede la deuda actual. El sobrante se guardará como <strong>Saldo a Favor / Anticipo</strong>.</p>
                        <div class="card bg-light border-0 my-3 p-3 text-dark">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Deuda Cancelada:</span>
                                <strong class="text-danger">$${saldoPendiente.toFixed(2)}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Monto Recibido:</span>
                                <strong class="text-dark">$${montoAbono.toFixed(2)}</strong>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="font-weight-bold text-primary">Nuevo Saldo a Favor:</span>
                                <span class="badge badge-info fs-6 px-2 py-1">+$${exceso.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa fa-check"></i> Sí, Procesar Pago',
                cancelButtonText: 'Corregir Monto',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $(form).find('button[type="submit"]').prop('disabled', true);
                    form.submit();
                }
            });

            return false;
        }
    });

    function abrirModalGestionSaldo() {
        let modal = $('#modalGestionSaldo').length ? $('#modalGestionSaldo') : $('#modalReembolso'); 
        if (modal.length > 0) {
            modal.modal('show');
        } else {
            console.error("Modal de gestión de saldo a favor no encontrado.");
        }
    }

    function confirmarAnulacion(url, monto) {
        $('#formAnularAbono').attr('action', url);
        $('#montoAbonoText').text('$' + monto);
        $('#modalAnularAbono').modal('show');
    }

    function abrirModalInteres(cliente) {
        let creditosPendientes = cliente.creditos ? cliente.creditos.filter(c => c.estado === 'pendiente') : [];
        let saldoTotal = creditosPendientes.reduce((sum, c) => sum + parseFloat(c.saldo_pendiente), 0);

        // Validar que el cliente tenga al menos un crédito pendiente
        if (creditosPendientes.length === 0) {
            Swal.fire('Atención', 'El cliente no posee créditos pendientes para indexar.', 'warning');
            return;
        }

        // Se usa el ID del CRÉDITO en lugar del ID del cliente
        let creditoId = creditosPendientes[0].id; 

        $.ajax({
            url: `/creditos/${creditoId}/modal-interes`, 
            type: 'GET',
            success: function(html) {
                $('#contenedor-modal-interes').remove();
                $('body').append('<div id="contenedor-modal-interes">' + html + '</div>');
                
                // Asignar la ruta de acción directamente en JavaScript
                $('#formAplicarInteres').attr('action', `/creditos/${creditoId}/aplicar-interes`);
                
                $('#saldo_base_global').text('$' + saldoTotal.toFixed(2));
                $('#saldo_base_global').data('valor', saldoTotal);

                $('#modalAplicarInteres').modal('show');
            },
            error: function(xhr) {
                var msj = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : "Error al cargar modal de indexación";
                Swal.fire('Error', msj, 'error');
            }
        });
    }

        // =========================================================================
        // CÁLCULO DINÁMICO PARA EL MODAL DE INDEXAR INTERÉS
        // =========================================================================
        $(document).on('input', '#input_porcentaje', function() {
            // 1. Obtener el saldo base desde el data-valor (asignado al abrir el modal)
            let saldoBase = parseFloat($('#saldo_base_global').data('valor')) || 0;
            
            // 2. Obtener el porcentaje actual escrito por el usuario
            let porcentaje = parseFloat($(this).val()) || 0;

            // 3. Referencias a los elementos del DOM
            let btnConfirmar = $('#btn_confirmar_index');
            let previewInteres = $('#preview_interes');
            let previewTotal = $('#preview_total');

            // 4. Validar y calcular
            if (porcentaje > 0 && saldoBase > 0) {
                let montoInteres = saldoBase * (porcentaje / 100);
                let nuevoTotal = saldoBase + montoInteres;

                // Actualizar la interfaz (redondeando a 2 decimales)
                previewInteres.text('$' + montoInteres.toFixed(2));
                previewTotal.text('$' + nuevoTotal.toFixed(2));

                // Habilitar el botón de submit
                btnConfirmar.prop('disabled', false);
            } else {
                // Resetear la vista si el usuario borra el input o coloca 0
                previewInteres.text('$0.00');
                previewTotal.text('$' + saldoBase.toFixed(2)); 
                
                // Volver a bloquear el botón
                btnConfirmar.prop('disabled', true);
            }
        });

    // =========================================================================
    // ENVÍO DEL FORMULARIO VÍA AJAX PARA EVITAR REDIRECCIÓN
    // =========================================================================
    $(document).on('submit', '#formAplicarInteres', function(e) {
        // 1. Evita que el navegador cambie de página (fundamental)
        e.preventDefault(); 

        let form = $(this);
        let url = form.attr('action');
        let formData = form.serialize();
        let btnConfirmar = $('#btn_confirmar_index');

        // 2. Cambiar estado del botón mientras procesa
        btnConfirmar.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Procesando...');

        // 3. Petición AJAX
        $.ajax({
            type: 'POST',
            url: url,
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Cerrar el modal de indexación actual (opcional, si usas Bootstrap)
                    // $('#modalIndexacion').modal('hide'); 

                    // 4. Mostrar el Modal de Confirmación 
                    // Si usas SweetAlert2 (muy recomendado y común en estos paneles)
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Indexación Aplicada!',
                            text: response.mensaje, // El mensaje que viene de tu controlador
                            confirmButtonText: 'Aceptar',
                            allowOutsideClick: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // 5. Recargar la vista show para actualizar la deuda
                                location.reload(); 
                            }
                        });
                    } else {
                        // Alternativa si no tienes SweetAlert2 instalado
                        alert('¡Éxito! ' + response.mensaje);
                        location.reload();
                    }
                }
            },
            error: function(xhr) {
                // Restaurar el botón si hay un error
                btnConfirmar.prop('disabled', false).text('Aplicar');
                
                // Mostrar error
                let errorMsg = 'Ocurrió un error al aplicar la indexación.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', errorMsg, 'error');
                } else {
                    alert(errorMsg);
                }
            }
        });
    });

    // =========================================================================
    // CONFIRMACIÓN Y ANULACIÓN DE INDEXACIÓN
    // =========================================================================
    function confirmarAnulacionInteres(url, monto) {
        if (typeof Swal === 'undefined') {
            alert('SweetAlert2 no está cargado.');
            return;
        }

        Swal.fire({
            title: '¿Anular Indexación?',
            html: `Estás a punto de anular una indexación por <b>$${monto}</b>.<br><br>Ingresa una observación o motivo (opcional):`,
            icon: 'warning',
            input: 'text',
            inputPlaceholder: 'Ej: Ajuste acordado con el cliente, error de cálculo...',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '<i class="fa fa-ban"></i> Sí, anular',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // 1. Mostrar estado de carga mientras el controlador procesa
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Anulando la indexación y recalculando saldos.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // 2. Crear un formulario dinámico para enviarlo por POST
                let form = $('<form>', {
                    'method': 'POST',
                    'action': url
                });

                // 3. Agregar el token CSRF de Laravel
                form.append($('<input>', {
                    'type': 'hidden',
                    'name': '_token',
                    'value': '{{ csrf_token() }}'
                }));

                // 4. Agregar la observación capturada en el modal
                form.append($('<input>', {
                    'type': 'hidden',
                    'name': 'observacion',
                    'value': result.value || '' // Envía el texto o vacío si no escribió nada
                }));

                // 5. Adjuntar al DOM y enviar
                $('body').append(form);
                form.submit();
            }
        });
    }
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

    document.addEventListener('DOMContentLoaded', function () {
        // 1. Tasa BCV desde Laravel
        const TASA_BCV = parseFloat("{{ bcv_rate('USD') }}") || 1;

        // 2. Elementos del DOM
        const inputMontoTotal  = document.getElementById('monto_total_usd');
        const inputUsdEfectivo = document.querySelector('input[name="pago_usd_efectivo"]');
        const inputBsEfectivo  = document.querySelector('input[name="pago_bs_efectivo"]');
        const inputPuntoBs     = document.querySelector('input[name="pago_punto_bs"]');
        const inputPagoMovilBs = document.querySelector('input[name="pago_pagomovil_bs"]');

        const inputsDesglose = document.querySelectorAll('.input-desglose');
        const divError       = document.getElementById('error-desglose');
        
        const formAbono = inputMontoTotal ? inputMontoTotal.closest('form') : null;
        const btnSubmit = formAbono ? formAbono.querySelector('button[type="submit"]') : null;

        function getNum(input) {
            if (!input) return 0;
            const val = parseFloat(input.value);
            return isNaN(val) ? 0 : val;
        }

        // 3. Validación estricta de coincidencia
        function validarCuadreMontos() {
            const montoObjetivoUSD = getNum(inputMontoTotal);

            const usdEfectivo = getNum(inputUsdEfectivo);
            const bsEfectivo  = getNum(inputBsEfectivo);
            const puntoBs     = getNum(inputPuntoBs);
            const pagoMovilBs = getNum(inputPagoMovilBs);

            // Convertir montos de bolívares a dólares
            const totalBsEnUsd = (bsEfectivo + puntoBs + pagoMovilBs) / TASA_BCV;
            const totalDesgloseUSD = usdEfectivo + totalBsEnUsd;

            // Tolerancia para comparar flotantes
            const diferencia = Math.abs(montoObjetivoUSD - totalDesgloseUSD);
            const estanCuadrados = montoObjetivoUSD > 0 && diferencia < 0.01;

            if (estanCuadrados) {
                if (divError) divError.classList.add('d-none');
                if (btnSubmit) btnSubmit.disabled = false;
            } else {
                if (btnSubmit) btnSubmit.disabled = true;
                if (divError) {
                    divError.classList.remove('d-none');

                    if (montoObjetivoUSD <= 0) {
                        divError.innerHTML = '<i class="fa fa-exclamation-circle"></i> Ingrese un monto a abonar válido.';
                    } else {
                        divError.innerHTML = `<i class="fa fa-exclamation-circle"></i> Discrepancia: El desglose suma <b>$${totalDesgloseUSD.toFixed(2)}</b> y el monto a abonar es <b>$${montoObjetivoUSD.toFixed(2)}</b>.`;
                    }
                }
            }
        }

        // 4. Asignar eventos keyup/input/change al campo total y al desglose
        if (inputMontoTotal) {
            ['keyup', 'input', 'change'].forEach(evt => {
                inputMontoTotal.addEventListener(evt, validarCuadreMontos);
            });
        }

        inputsDesglose.forEach(input => {
            ['keyup', 'input', 'change'].forEach(evt => {
                input.addEventListener(evt, validarCuadreMontos);
            });

            input.addEventListener('blur', function() {
                if (this.value.trim() === '' || isNaN(parseFloat(this.value))) {
                    this.value = '0';
                    validarCuadreMontos();
                }
            });
        });

        // Ejecutar al cargar
        validarCuadreMontos();
    });

    // Validación del formulario de Historial Crediticio por Fecha
    $('#formHistorialFechas').on('submit', function(e) {
        let fechaInicio = new Date($('#fecha_inicio').val());
        let fechaFin = new Date($('#fecha_fin').val());

        if (fechaInicio > fechaFin) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Rango de fechas inválido',
                text: 'La fecha de inicio no puede ser posterior a la fecha de fin.',
                confirmButtonColor: '#343a40'
            });
            return false;
        }
    });

    function editarAbono(id) {
        $.ajax({
            url: `/creditos/abonos/${id}/editar`,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    let abono = data.abono;

                    $('#edit_abono_id').text(abono.id);
                    $('#edit_nombre_cliente').text(abono.nombre_cliente);
                    $('#edit_fecha_abono').val(abono.fecha_abono);
                    $('#edit_monto_total_usd').val(parseFloat(abono.monto_total_usd).toFixed(2));
                    $('#edit_referencia').val(abono.referencia);
                    
                    $('#edit_pago_usd_efectivo').val(abono.pago_usd_efectivo);
                    $('#edit_pago_bs_efectivo').val(abono.pago_bs_efectivo);
                    $('#edit_pago_punto_bs').val(abono.pago_punto_bs);
                    $('#edit_pago_pagomovil_bs').val(abono.pago_pagomovil_bs);

                    // Asignar ruta dinámica al formulario de actualización
                    $('#formEditarAbono').attr('action', `/creditos/abonos/${abono.id}`);

                    // Ocultar alerta de error previa
                    $('#error-desglose-edit').addClass('d-none');

                    $('#modalEditarAbono').modal('show');
                }
            },
            error: function(err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: err.responseJSON?.error || 'No se pudieron cargar los datos del abono.',
                    confirmButtonColor: '#d33'
                });
            }
        });
    }

    $(document).ready(function() {

        // Limpieza en tiempo real de la alerta de desglose al escribir en los inputs de edición
        $(document).on('input', '.input-desglose-edit', function() {
            let usdEfectivo = parseFloat($('#edit_pago_usd_efectivo').val()) || 0;
            let bsEfectivo  = parseFloat($('#edit_pago_bs_efectivo').val()) || 0;
            let bsPunto     = parseFloat($('#edit_pago_punto_bs').val()) || 0;
            let bsPagoMovil = parseFloat($('#edit_pago_pagomovil_bs').val()) || 0;

            if ((usdEfectivo + bsEfectivo + bsPunto + bsPagoMovil) > 0) {
                $('#error-desglose-edit').addClass('d-none');
            }
        });

        // Validación y Confirmación previa al enviar
        $('#formEditarAbono').on('submit', function(e) {
            e.preventDefault();

            let form = this;

            // 1. Validar fecha
            let fecha = $('#edit_fecha_abono').val();
            if (!fecha) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo Requerido',
                    text: 'Por favor, ingrese una fecha válida para el abono.',
                    confirmButtonColor: '#f39c12'
                });
                return false;
            }

            // 2. Validar desglose mayor a 0
            let usdEfectivo = parseFloat($('#edit_pago_usd_efectivo').val()) || 0;
            let bsEfectivo  = parseFloat($('#edit_pago_bs_efectivo').val()) || 0;
            let bsPunto     = parseFloat($('#edit_pago_punto_bs').val()) || 0;
            let bsPagoMovil = parseFloat($('#edit_pago_pagomovil_bs').val()) || 0;

            let totalDesglose = usdEfectivo + bsEfectivo + bsPunto + bsPagoMovil;

            if (totalDesglose <= 0) {
                $('#error-desglose-edit').removeClass('d-none');
                
                Swal.fire({
                    icon: 'warning',
                    title: 'Desglose Inválido',
                    text: 'Debe ingresar al menos un valor mayor a cero (0) en el desglose de pago.',
                    confirmButtonColor: '#f39c12'
                });
                return false;
            }

            $('#error-desglose-edit').addClass('d-none');

            // 3. Confirmación previa con Swal.fire
            Swal.fire({
                title: '¿Confirmar Edición?',
                text: "Se actualizarán las observaciones y vías de ingreso de este abono.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#f39c12',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa fa-check"></i> Sí, actualizar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

    });
</script>
@endsection