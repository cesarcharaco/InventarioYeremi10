@extends('layouts.app')
@section('title') Estado de Cuenta @endsection

@section('content')
<main class="app-content">
    <div class="app-title">
        <div>
            <h1><i class="fa fa-user"></i> Estado de Cuenta</h1>
            <p class="text-muted mb-1">
                <strong>{{ $cliente->nombre }}</strong> | 
                Identificación: {{ $cliente->identificacion ?? 'N/A' }} | 
                Teléfono: {{ $cliente->telefono ?? 'N/A' }}
            </p>

            {{-- CÁLCULO Y VISUALIZACIÓN DE LÍMITE DE CRÉDITO --}}
            @php
                $limite = $cliente->limite_credito ?? 0; // Cambia 'limite_credito' si tu columna en BD se llama distinto
                $deudaActual = $resumen['deuda_total'] ?? 0;
                $disponible = $limite - $deudaActual;
            @endphp

            <div class="d-flex align-items-center flex-wrap gap-2 mt-1 small">
                <span class="mr-3">
                    <i class="fas fa-credit-card text-secondary"></i> <strong>Límite:</strong> 
                    {{ number_format($limite, 2) }}$
                </span>

                <span class="mr-3">
                    <i class="fas fa-wallet text-secondary"></i> <strong>Disponible:</strong> 
                    <span class="{{ $disponible > 0 ? 'text-success font-weight-bold' : 'text-danger font-weight-bold' }}">
                        {{ number_format(max(0, $disponible), 2) }}$
                    </span>
                </span>

                {{-- Badges Informativos sobre si se le puede otorgar más crédito --}}
                @if($limite > 0)
                    @if($disponible > 0)
                        <span class="badge badge-success px-2 py-1">
                            <i class="fa fa-check-circle"></i> Apto para Crédito
                        </span>
                    @else
                        <span class="badge badge-danger px-2 py-1">
                            <i class="fa fa-exclamation-triangle"></i> Límite Excedido
                        </span>
                    @endif
                @else
                    <span class="badge badge-info px-2 py-1">
                        <i class="fa fa-infinity"></i> Sin Límite Definido
                    </span>
                @endif
            </div>
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
                    <div class="text-muted text-uppercase medium font-weight-bold">Deuda Total <span class="badge badge-danger">Pendiente</span></div>
                    <div class="d-flex align-items-baseline justify-content-between mt-1">
                        <span class="h3 mb-0 font-weight-bold text-danger" style="font-variant-numeric: tabular-nums;">
                            {{ number_format($resumen['saldo_pendiente'], 2) }}$
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="tile p-0 border-left border-success shadow-sm" style="border-left-width: 4px !important;">
                <div class="p-3">
                    <div class="text-muted text-uppercase medium font-weight-bold">
                        Total Abonado 
                       
                            @php
                                // El total real del crédito es lo que ya pagó más lo que aún debe
                                $montoTotalCredito = $resumen['total_abonado'] + $resumen['deuda_total'];
                                $porcentaje = 0;
                                
                                if ($montoTotalCredito > 0) {
                                    $porcentaje = round(($resumen['total_abonado'] / $montoTotalCredito) * 100, 1);
                                }
                            @endphp
                            
                            <span class="badge badge-success">
                                {{ $porcentaje }}% Completado
                            </span>
                      
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between mt-1">
                        <span class="h3 mb-0 font-weight-bold text-success" style="font-variant-numeric: tabular-nums;">
                            {{ number_format($resumen['total_abonado'], 2) }}$
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="tile p-0 border-left {{ $resumen['saldo_a_favor'] > 0 ? 'border-info' : 'border-secondary' }} shadow-sm" style="border-left-width: 4px !important;">
                <div class="p-3">
                    <div class="text-muted text-uppercase medium font-weight-bold">
                        Saldo a Favor 
                        @if($resumen['saldo_a_favor'] > 0)
                            <span class="badge badge-info">Disponible</span>
                        @else
                            <span class="badge badge-secondary">Agotado</span>
                        @endif
                    </div>
                    
                    <div class="d-flex align-items-baseline justify-content-between mt-1">
                        <span id="saldo_a_favor_cliente" class="h3 mb-0 font-weight-bold {{ $resumen['saldo_a_favor'] > 0 ? 'text-info' : 'text-muted' }}" style="font-variant-numeric: tabular-nums;">
                            {{ number_format($resumen['saldo_a_favor'], 2) }}$
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
                        <span class="font-weight-bold" style="font-variant-numeric: tabular-nums;">{{ number_format($resumen['monto_inicial'], 2) }}$</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted small">Intereses (Indexación)</span>
                        <span class="font-weight-bold text-warning" style="font-variant-numeric: tabular-nums;">+ {{ number_format($resumen['total_intereses'], 2) }}$</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted small">Total Abonado</span>
                        <span class="font-weight-bold text-success" style="font-variant-numeric: tabular-nums;">- {{ number_format($resumen['total_abonado'], 2) }}$</span>
                    </li>

                    @if($resumen['saldo_a_favor'] > 0)
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 bg-info text-white">
                        <span class="small font-weight-bold"><i class="fa fa-star"></i> Saldo a Favor</span>
                        <div class="text-right">
                            <strong class="d-block" style="font-variant-numeric: tabular-nums;">+{{ number_format($resumen['saldo_a_favor'], 2) }}$</strong>
                            <button type="button" class="btn btn-xs btn-outline-light mt-1 py-0 px-2" style="font-size: 11px;" onclick="abrirModalGestionSaldo()">
                                Gestionar
                            </button>
                        </div>
                    </li>
                    @endif

                    <li class="list-group-item d-flex justify-content-between align-items-center py-3" style="background: #f8f9fa;">
                        <span class="font-weight-bold text-primary">Saldo Pendiente Neto</span>
                        <span class="h5 mb-0 font-weight-bold text-primary" style="font-variant-numeric: tabular-nums;">{{ number_format($resumen['saldo_pendiente'], 2) }}$</span>
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
                                       {{-- 1. Fecha --}}
                                       <td class="small text-nowrap" data-order="{{ $credito->created_at->timestamp }}">
                                           {{ $credito->created_at->format('d/m/Y h:i A') }}
                                       </td>

                                       {{-- 2. Código / Referencia --}}
                                       <td>
                                           <strong>
                                               
                                               {{ $credito->venta->codigo_factura }}
                                               
                                           </strong>
                                       </td>

                                       {{-- 3. Tipo --}}
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

                                       {{-- 4. Monto Original ($) --}}
                                       <td class="font-weight-bold text-right">
                                           @if($esAnticipo)
                                               <span class="text-success">{{ number_format($credito->monto_inicial, 2) }}$</span>
                                           @else
                                               {{ number_format($credito->monto_inicial, 2) }}$
                                           @endif
                                       </td>

                                       {{-- 5. Saldo ($) --}}
                                       <td class="font-weight-bold text-right">
                                           @if($esAnticipo)
                                               <span class="text-success">{{ number_format($credito->saldo_a_favor, 2) }}$ (A favor)</span>
                                           @else
                                               <span class="text-danger">{{ number_format($credito->saldo_pendiente, 2) }}$</span>
                                           @endif
                                       </td>

                                       {{-- 6. Estado (ESTA ERA LA COLUMNA FALTANTE) --}}
                                       <td class="text-center">
                                           @if($esAnticipo)
                                               <span class="badge badge-success">ANTICIPO</span>
                                           @elseif($credito->estado === 'pendiente')
                                               <span class="badge badge-danger">PENDIENTE</span>
                                           @elseif($credito->estado === 'pagado')
                                               <span class="badge badge-success">PAGADO</span>
                                           @else
                                               <span class="badge badge-secondary">{{ strtoupper($credito->estado) }}</span>
                                           @endif
                                       </td>

                                       {{-- 7. Acciones --}}
                                       <td class="text-center">
                                           <button type="button" 
                                                   class="btn btn-danger btn-sm btn-modal-eliminar-nuevo" 
                                                   data-id="{{ $credito->id }}"
                                                   data-codigo="{{ $esAnticipo ? 'ANT-' . $credito->id : ($credito->venta->codigo_factura ?? 'CRD-' . $credito->id) }}"
                                                   data-monto="{{ number_format($credito->monto_inicial, 2) }}"
                                                   data-saldo="{{ number_format($credito->saldo_pendiente, 2) }}"
                                                   data-tieneproductos="{{ ($credito->venta && $credito->venta->detalles->isNotEmpty()) ? '1' : '0' }}"
                                                   title="Eliminar Crédito/Anticipo-{{ $credito->id }}">
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
                                        <th>Fecha / Hora</th>
                                        <th>Cajero</th>
                                        <th class="d-md-table-cell"># Crédito(s)</th>
                                        <th class="d-md-table-cell text-right">Monto ($)</th>
                                        <th class="d-md-table-cell">Forma de Pago / Desglose</th>
                                        <th class="d-md-table-cell">Detalles</th>
                                        <th>Estado</th>
                                        @canany(['editar-abono', 'anular-abono'])<th class="text-center">Acción</th> @endcanany
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($historialAbonos as $abono)
                                    @php
                                        $esReembolso = $abono->monto_total_usd < 0;
                                    @endphp
                                    <tr style="{{ $abono->estado === 'Anulado' ? 'opacity: 0.6; text-decoration: line-through;' : '' }}" class="{{ $esReembolso ? 'table-warning' : '' }}">
                                        <td class="small text-nowrap" data-order="{{ $abono->created_at->timestamp }}">
                                            {{ $abono->created_at->format('d/m/Y h:i A') }}
                                        </td>
                                        <td>{{ $abono->usuario->name ?? 'N/A' }}</td>
                                        <td>
                                            {{-- Se accede a la relación HasMany evitando que el atributo de texto sobrescriba la colección --}}
                                            @foreach($abono->getRelation('detalles') as $detalle)
                                                <span class="badge badge-light border">ID: {{ $detalle->id_credito }}</span>
                                            @endforeach
                                        </td>
                                        <td class="font-weight-bold text-right {{ $esReembolso ? 'text-danger' : 'text-success' }}" style="font-variant-numeric: tabular-nums;">
                                            {{ $esReembolso ? '-' : '' }}{{ number_format(abs($abono->monto_total_usd), 2) }}$
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @if(($abono->pago_usd_efectivo ?? 0) > 0)
                                                    <small class="badge badge-light border">Efe: {{ number_format($abono->pago_usd_efectivo, 2) }}$</small>
                                                @endif
                                                @if(($abono->pago_bs_efectivo ?? 0) > 0)
                                                    <small class="badge badge-light border">Efe: {{ number_format($abono->pago_bs_efectivo, 2) }} Bs</small>
                                                @endif
                                                @if(($abono->pago_punto_bs ?? 0) > 0)
                                                    <small class="badge badge-light border">Punto: {{ number_format($abono->pago_punto_bs, 2) }} Bs</small>
                                                @endif
                                                @if(($abono->pago_pagomovil_bs ?? 0) > 0)
                                                    <small class="badge badge-light border">P.Móvil: {{ number_format($abono->pago_pagomovil_bs, 2) }} Bs</small>
                                                @endif
                                                @if($esReembolso)
                                                    <small class="badge badge-warning text-dark"><i class="fas fa-undo"></i> Reembolso / Devolución</small>
                                                @endif
                                            </div>
                                        </td>
                                        {{-- Se obtiene explícitamente el valor de la columna de texto de la base de datos --}}
                                        <td><small class="text-muted">{{ $abono->getAttributes()['detalles'] ?? 'N/A' }}</small></td>
                                        <td>
                                            <span class="badge badge-{{ $abono->estado === 'Realizado' ? 'success' : 'danger' }}">
                                                {{ $abono->estado }}
                                            </span>
                                        </td>
                                        @canany(['editar-abono', 'anular-abono'])
                                        <td class="text-center">
                                        @can('editar-abono')
                                            @if($abono->estado === 'Realizado' && !$esReembolso)
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-primary"
                                                        onclick="editarAbono({{ $abono->id }})"
                                                        title="Editar Abono">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                            @endif
                                        @endcan
                                        @can('anular-abono')
                                        
                                            @if($abono->estado === 'Realizado' && !$esReembolso)
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="confirmarAnulacion('{{ route('abonos.anular', $abono->id) }}', '{{ number_format($abono->monto_total_usd, 2) }}')"
                                                        title="Anular Abono">
                                                    <i class="fa fa-ban"></i>
                                                </button>
                                            @endif
                                        @endcan
                                        </td>
                                        @endcanany
                                       
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
                                        <td class="font-weight-bold text-danger text-right" style="font-variant-numeric: tabular-nums;">+{{ number_format($interes->monto_interes, 2) }}$</td>
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
@include('creditos.modals.modal_anular_abono')
@include('creditos.modals.modal_anular_interes')
@include('creditos.modals.modal_gestion_saldo')
@include('creditos.modals.modal_interes')
@include('creditos.modals.modal_credito_directo')
@include('creditos.modals.modal_eliminar_credito')
@include('creditos.modals.modal_historial_crediticio')
@include('creditos.modals.modalEditarAbono')
@endsection

@section('scripts')
<script>
    // =========================================================================
    // ZONA 1: FUNCIONES GLOBALES (FUERA DE READY)
    // Se invocan directamente desde atributos HTML (onclick, onchange, etc.)
    // =========================================================================

    function abrirModalAbono(cliente) {
        $('#formAbono')[0].reset();
        $('#alerta_saldo_favor').addClass('d-none');
        $('#error-desglose').addClass('d-none');
        $('.input-desglose').removeClass('is-invalid');

        let creditosPendientes = cliente.creditos
            ? cliente.creditos.filter(c => c.estado === 'pendiente' && parseFloat(c.saldo_pendiente) > 0)
            : [];

        if (creditosPendientes.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Sin deudas pendientes',
                text: 'El cliente no tiene deudas pendientes activas para abonar.'
            });
            return;
        }

        let primerCredito = creditosPendientes[0];
        let url = "{{ route('creditos.abono', ':id') }}".replace(':id', primerCredito.id);

        $('#formAbono').attr('action', url);
        $('#nombre_cliente').text(cliente.nombre);

        let saldoTotal = creditosPendientes.reduce((sum, c) => sum + parseFloat(c.saldo_pendiente), 0);
        $('#txt_saldo_pendiente').text('$' + saldoTotal.toFixed(2));

        $('#modalAbono').modal('show');
    }

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
        let creditosPendientes = cliente.creditos
            ? cliente.creditos.filter(c => c.estado === 'pendiente')
            : [];
        let saldoTotal = creditosPendientes.reduce((sum, c) => sum + parseFloat(c.saldo_pendiente), 0);

        if (creditosPendientes.length === 0) {
            Swal.fire('Atención', 'El cliente no posee créditos pendientes para indexar.', 'warning');
            return;
        }

        let creditoId = creditosPendientes[0].id;

        $.ajax({
            url: `/creditos/${creditoId}/modal-interes`,
            type: 'GET',
            success: function(html) {
                $('#contenedor-modal-interes').remove();
                $('body').append('<div id="contenedor-modal-interes">' + html + '</div>');

                $('#formAplicarInteres').attr('action', `/creditos/${creditoId}/aplicar-interes`);
                $('#saldo_base_global').text('$' + saldoTotal.toFixed(2));
                $('#saldo_base_global').data('valor', saldoTotal);

                $('#modalAplicarInteres').modal('show');
            },
            error: function(xhr) {
                var msj = (xhr.responseJSON && xhr.responseJSON.error)
                    ? xhr.responseJSON.error
                    : "Error al cargar modal de indexación";
                Swal.fire('Error', msj, 'error');
            }
        });
    }

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
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Anulando la indexación y recalculando saldos.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                let form = $('<form>', {
                    'method': 'POST',
                    'action': url
                });

                form.append($('<input>', {
                    'type': 'hidden',
                    'name': '_token',
                    'value': '{{ csrf_token() }}'
                }));

                form.append($('<input>', {
                    'type': 'hidden',
                    'name': 'observacion',
                    'value': result.value || ''
                }));

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

    function editarAbono(id) {
        $.ajax({
            url: `/creditos/abonos/${id}/editar`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    let abono = response.abono;

                    $('#formEditarAbono').attr('action', `/creditos/abonos/${abono.id}`);
                    $('#edit_abono_id').text(abono.id);
                    $('#edit_nombre_cliente').text(abono.nombre_cliente || 'Cliente');
                    $('#edit_fecha_abono').val(abono.fecha_abono);
                    $('#edit_monto_total_usd').val(abono.monto_total_usd);
                    $('#edit_referencia').val(abono.referencia);

                    $('#edit_pago_usd_efectivo').val(abono.pago_usd_efectivo ?? 0);
                    $('#edit_pago_bs_efectivo').val(abono.pago_bs_efectivo ?? 0);
                    $('#edit_pago_punto_bs').val(abono.pago_punto_bs ?? 0);
                    $('#edit_pago_pagomovil_bs').val(abono.pago_pagomovil_bs ?? 0);

                    $('#modalEditarAbono').modal('show');
                }
            },
            error: function(xhr) {
                let errorMsg = (xhr.responseJSON && xhr.responseJSON.error)
                    ? xhr.responseJSON.error
                    : 'Error al cargar los datos del abono.';

                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', errorMsg, 'error');
                } else {
                    alert(errorMsg);
                }
            }
        });
    }

    // =========================================================================
    // ZONA 2: UN SOLO $(document).ready()
    // Todo lo que interactúa con el DOM, DataTables, formularios e inputs
    // =========================================================================

    $(document).ready(function() {

        const TASA_BCV = parseFloat("{{ bcv_rate('USD') }}") || 1;

        // ---------------------------------------------------------------------
        // 1. INICIALIZACIÓN DE DATATABLES
        // ---------------------------------------------------------------------
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
            if (!$.fn.DataTable.isDataTable('#tabla-creditos')) {
                $('#tabla-creditos').DataTable({
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
        }

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });

        // ---------------------------------------------------------------------
        // 2. MODAL DE ELIMINACIÓN DE CRÉDITO
        // ---------------------------------------------------------------------
        $(document).on('click', '.btn-modal-eliminar-nuevo', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $btn = $(this);

            var id = $btn.attr('data-id');
            var codigo = $btn.attr('data-codigo');
            var monto = $btn.attr('data-monto');
            var saldo = $btn.attr('data-saldo');
            var tieneProductos = $btn.attr('data-tieneproductos');

            if (!id) return;

            $('#formEliminarCredito').attr('action', "{{ url('creditos') }}/" + id);

            $('#eliminar_credito_codigo').text(codigo);
            $('#eliminar_credito_monto').text(monto);
            $('#eliminar_credito_saldo').text(saldo);

            if (tieneProductos === '1') {
                $('#msg_retorno_stock').removeClass('d-none');
                $('#msg_credito_directo').addClass('d-none');
            } else {
                $('#msg_credito_directo').removeClass('d-none');
                $('#msg_retorno_stock').addClass('d-none');
            }

            $('#modalEliminarCredito').modal('show');
        });

        // ---------------------------------------------------------------------
        // 3. VALIDACIÓN Y EVENTOS DEL FORMULARIO DE ABONO
        // ---------------------------------------------------------------------
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

        // ---------------------------------------------------------------------
        // 4. INDEXACIÓN DE INTERÉS Y AJAX
        // ---------------------------------------------------------------------
        $(document).on('input', '#input_porcentaje', function() {
            let saldoBase = parseFloat($('#saldo_base_global').data('valor')) || 0;
            let porcentaje = parseFloat($(this).val()) || 0;

            let btnConfirmar = $('#btn_confirmar_index');
            let previewInteres = $('#preview_interes');
            let previewTotal = $('#preview_total');

            if (porcentaje > 0 && saldoBase > 0) {
                let montoInteres = saldoBase * (porcentaje / 100);
                let nuevoTotal = saldoBase + montoInteres;

                previewInteres.text('$' + montoInteres.toFixed(2));
                previewTotal.text('$' + nuevoTotal.toFixed(2));
                btnConfirmar.prop('disabled', false);
            } else {
                previewInteres.text('$0.00');
                previewTotal.text('$' + saldoBase.toFixed(2));
                btnConfirmar.prop('disabled', true);
            }
        });

        $(document).on('submit', '#formAplicarInteres', function(e) {
            e.preventDefault();

            let form = $(this);
            let btnConfirmar = $('#btn_confirmar_index');

            btnConfirmar.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Procesando...');

            $.ajax({
                type: 'POST',
                url: form.attr('action'),
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Indexación Aplicada!',
                            text: response.mensaje,
                            confirmButtonText: 'Aceptar',
                            allowOutsideClick: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                    }
                },
                error: function(xhr) {
                    btnConfirmar.prop('disabled', false).text('Aplicar');

                    let errorMsg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Ocurrió un error al aplicar la indexación.';

                    Swal.fire('Error', errorMsg, 'error');
                }
            });
        });

        // ---------------------------------------------------------------------
        // 5. CRÉDITO DIRECTO Y SOLICITUD DE PIN
        // ---------------------------------------------------------------------
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

                        if (response.wa_link) {
                            window.open(response.wa_link, '_blank');
                        }

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
                                $('#estado_pin_texto').html('<i class="fa fa-check-circle text-success"></i> Crédito Autorizado por Supervisor')
                                    .removeClass('text-dark')
                                    .addClass('text-success');
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

        // ---------------------------------------------------------------------
        // 6. VALIDACIONES DE CUADRE EN TIEMPO REAL (ABONO REGISTRAR Y EDITAR)
        // ---------------------------------------------------------------------
        function validarCuadreMontos() {
            const inputMontoTotal = document.getElementById('monto_total_usd');
            if (!inputMontoTotal) return;

            const montoObjetivoUSD = parseFloat(inputMontoTotal.value) || 0;
            const usdEfectivo = parseFloat($('input[name="pago_usd_efectivo"]').val()) || 0;
            const bsEfectivo  = parseFloat($('input[name="pago_bs_efectivo"]').val()) || 0;
            const puntoBs     = parseFloat($('input[name="pago_punto_bs"]').val()) || 0;
            const pagoMovilBs = parseFloat($('input[name="pago_pagomovil_bs"]').val()) || 0;

            const totalBsEnUsd = (bsEfectivo + puntoBs + pagoMovilBs) / TASA_BCV;
            const totalDesgloseUSD = usdEfectivo + totalBsEnUsd;

            const diferencia = Math.abs(montoObjetivoUSD - totalDesgloseUSD);
            const estanCuadrados = montoObjetivoUSD > 0 && diferencia < 0.01;

            const divError = document.getElementById('error-desglose');
            const btnSubmit = inputMontoTotal.closest('form')
                ? inputMontoTotal.closest('form').querySelector('button[type="submit"]')
                : null;

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

        $(document).on('keyup input change', '#monto_total_usd, .input-desglose', validarCuadreMontos);

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

        // ---------------------------------------------------------------------
        // 7. CALCULADORAS DE CONVERSIÓN RÁPIDA (REGISTRAR Y EDITAR ABONO)
        // ---------------------------------------------------------------------
        $('#calc_usd').on('input', function () {
            let usd = parseFloat($(this).val()) || 0;
            $('#calc_bs').val((usd * TASA_BCV).toFixed(2));
        });

        $('#calc_bs').on('input', function () {
            let bs = parseFloat($(this).val()) || 0;
            if (TASA_BCV > 0) {
                $('#calc_usd').val((bs / TASA_BCV).toFixed(2));
            }
        });

        $('.btn-copiar-bs').on('click', function () {
            let inputTargetName = $(this).data('target');
            let montoBs = $('#calc_bs').val() || 0;

            $(`#modalAbono input[name="${inputTargetName}"]`)
                .val(parseFloat(montoBs).toFixed(2))
                .trigger('input')
                .trigger('change');
        });

        $('#edit_calc_usd').on('input', function () {
            let usd = parseFloat($(this).val()) || 0;
            $('#edit_calc_bs').val((usd * TASA_BCV).toFixed(2));
        });

        $('#edit_calc_bs').on('input', function () {
            let bs = parseFloat($(this).val()) || 0;
            if (TASA_BCV > 0) {
                $('#edit_calc_usd').val((bs / TASA_BCV).toFixed(2));
            }
        });

        $('.btn-copiar-bs-edit').on('click', function () {
            let targetId = $(this).data('target-id');
            let montoBs = $('#edit_calc_bs').val() || 0;

            $(`#${targetId}`)
                .val(parseFloat(montoBs).toFixed(2))
                .trigger('input')
                .trigger('change');
        });

        $('#modalAbono, #modalEditarAbono').on('hidden.bs.modal', function () {
            $('#calc_usd, #calc_bs, #edit_calc_usd, #edit_calc_bs').val('');
        });

    }); // fin de $(document).ready()
</script>
@endsection