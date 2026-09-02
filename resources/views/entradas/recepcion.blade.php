@extends('layouts.app')

@section('title', 'Recepción de Mercancía - Almacén')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark"><i class="fas fa-boxes mr-2 text-primary"></i>Bandeja de Entrada / Cuarentena</h1>
        </div>
    </div>
@endsection

@section('content')
    @include('layouts.partials.flash-messages')

    <div class="card card-outline card-primary shadow">
        <div class="card-header">
            <h3 class="card-title text-bold">Insumos Pendientes de Revisión Física y Cuarentena</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-sm" id="tabla_recepcion">
                    <thead class="thead-light">
                        <tr>
                            <th>Fecha Ingreso</th>
                            <th>Nro. Orden / Factura</th>
                            <th>Proveedor</th>
                            <th>Depósito Destino</th>
                            <th>Insumo</th>
                            <th>Cantidad</th>
                            <th>Costo U. ($)</th>
                            <th>Estado</th>
                            <th width="12%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recepciones as $rec)
                            <tr>
                                <td>{{ $rec->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $rec->detalleEntrada->entrada->nro_orden_entrega ?? 'S/N' }}</td>
                                <td>{{ $rec->detalleEntrada->entrada->proveedor->nombre ?? 'N/D' }}</td>
                                <td><span class="badge badge-info">{{ $rec->local->nombre ?? 'N/D' }}</span></td>
                                <td>
                                    <strong>{{ $rec->insumo->producto ?? 'N/D' }}</strong>
                                    <br><small class="text-muted">{{ $rec->insumo->descripcion ?? '' }}</small>
                                    @if($rec->observacion_recepcion)
                                        <br><small class="text-primary font-italic">Obs: {{ $rec->observacion_recepcion }}</small>
                                    @endif
                                </td>
                                <td class="text-bold" id="total_qty_{{ $rec->id }}">{{ $rec->cantidad }}</td>
                                <td>$ {{ number_format($rec->costo_unitario_usd, 2) }}</td>
                                <td>
                                    @if($rec->estado === 'PENDIENTE')
                                        <span class="badge badge-warning">PENDIENTE</span>
                                    @elseif($rec->estado === 'RETENIDO')
                                        <span class="badge badge-danger">RETENIDO</span>
                                    @elseif($rec->estado === 'PROCESADO')
                                        <span class="badge badge-success">PROCESADO</span>
                                    @else
                                        <span class="badge badge-secondary">{{ $rec->estado }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{-- Botón para procesar/revisar (Disponible para PENDIENTE o RETENIDO) --}}
                                    @if($rec->estado === 'PENDIENTE' || $rec->estado === 'RETENIDO')
                                        <button type="button" class="btn btn-success btn-xs btn-block mb-1" data-toggle="modal" data-target="#modalProcesar_{{ $rec->id }}">
                                            <i class="fas fa-check-circle mr-1"></i> Revisar
                                        </button>
                                    @endif

                                    {{-- Botón de Reversión: Solo visible si NO está PENDIENTE (ya fue procesado o retenido previamente) --}}
                                    @if($rec->estado !== 'PENDIENTE')
                                       <form action="{{ route('entradas.revertir', $rec->id_detalle_entrada) }}" method="POST" id="form-revertir-{{ $rec->id }}">
                                           @csrf
                                           @method('DELETE')
                                           <button type="button" class="btn btn-danger btn-xs btn-block btn-revertir" data-id="{{ $rec->id }}" title="Revertir y corregir distribución">
                                               <i class="fas fa-undo mr-1"></i> Revertir
                                           </button>
                                       </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fas fa-clipboard-check fa-3x mb-2"></i>
                                    <p>No hay insumos en la bandeja de recepción o buffer de almacén.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modales fuera de la tabla para evitar conflictos con DataTables --}}
    @foreach($recepciones as $rec)
        @if($rec->estado === 'PENDIENTE' || $rec->estado === 'RETENIDO')
            <div class="modal fade" id="modalProcesar_{{ $rec->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <form action="{{ route('entradas.procesar', $rec->id) }}" method="POST" id="formProcesar_{{ $rec->id }}">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header bg-primary">
                                <h5 class="modal-title">Gestionar: {{ $rec->insumo->producto ?? '' }}</h5>
                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-secondary">
                                    <small>
                                        <i class="fas fa-info-circle mr-1"></i> 
                                        Cantidad Disponible: <strong class="total-factura">{{ $rec->cantidad }}</strong> | 
                                        Costo unitario: <strong>$ {{ number_format($rec->costo_unitario_usd, 2) }}</strong>
                                    </small>
                                </div>

                                {{-- Información del Modelo de Venta Actual --}}
                                @if($rec->insumo && $rec->insumo->modeloVenta)
                                    <div class="alert alert-light border py-2 mb-3">
                                        <small><i class="fas fa-tag text-primary mr-1"></i> Modelo de venta actual: <strong class="text-dark">{{ $rec->insumo->modeloVenta->modelo }}</strong></small>
                                    </div>
                                @else
                                    <div class="alert alert-light border py-2 mb-3">
                                        <small><i class="fas fa-exclamation-circle text-warning mr-1"></i> Este producto aún no tiene un modelo de venta asignado previamente.</small>
                                    </div>
                                @endif

                                {{-- Distribución de Cantidades --}}
                                <h6 class="text-bold text-dark mb-2"><i class="fas fa-sliders-h mr-1"></i> Distribución de Cantidades:</h6>
                                <div class="row">
                                    <div class="col-md-4 form-group">
                                        <label class="text-success">Aprobar (Stock Real)</label>
                                        <input type="number" step="0.01" name="cant_aprobar" id="cant_aprobar_{{ $rec->id }}" value="{{ $rec->cantidad }}" class="form-control distribucion-input" data-id="{{ $rec->id }}" min="0" max="{{ $rec->cantidad }}" required>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label class="text-warning">Retener (Cuarentena)</label>
                                        <input type="number" step="0.01" name="cant_retenido" id="cant_retenido_{{ $rec->id }}" value="0" class="form-control distribucion-input" data-id="{{ $rec->id }}" min="0" max="{{ $rec->cantidad }}" required>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label class="text-danger">Rechazar (Dañado)</label>
                                        <input type="number" step="0.01" name="cant_rechazado" id="cant_rechazado_{{ $rec->id }}" value="0" class="form-control distribucion-input" data-id="{{ $rec->id }}" min="0" max="{{ $rec->cantidad }}" required>
                                    </div>
                                </div>
                                
                                <div id="alertaSuma_{{ $rec->id }}" class="alert alert-danger py-1 px-2 mb-3" style="display: none; font-size: 0.85rem;">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> La suma de las cantidades distribuidas debe ser exactamente igual a <strong>{{ $rec->cantidad }}</strong>.
                                </div>

                                <div id="seccionAprobacion_{{ $rec->id }}" class="border p-3 rounded bg-light mb-3">
                                    <h6 class="text-bold text-primary mb-3"><i class="fas fa-calculator mr-1"></i> Configuración de Costos y Precios</h6>
                                    
                                    <div class="row">
                                        <div class="col-md-6 form-group">
                                            <label>Costo Unitario Final ($) <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" name="costo_unitario" id="costo_{{ $rec->id }}" value="{{ $rec->costo_unitario_usd }}" class="form-control costo-input" data-id="{{ $rec->id }}">
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>Modelo de Venta <span class="text-danger">*</span></label>
                                            <select name="modelo_venta_id" id="modelo_{{ $rec->id }}" class="form-control modelo-select" data-id="{{ $rec->id }}">
                                                <option value="">Seleccione...</option>
                                                @foreach($modelosVenta as $mod)
                                                    <option value="{{ $mod->id }}" 
                                                        {{ ($rec->insumo && $rec->insumo->modelo_venta_id == $mod->id) ? 'selected' : '' }}
                                                        data-tasa-bcv="{{ $mod->tasa_bcv }}"
                                                        data-tasa-binance="{{ $mod->tasa_binance }}"
                                                        data-factor-bcv="{{ $mod->factor_bcv }}"
                                                        data-factor-usdt="{{ $mod->factor_usdt }}"
                                                        data-porcentaje-extra="{{ $mod->porcentaje_extra }}">
                                                        {{ $mod->modelo }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row text-center mt-2">
                                        <div class="col-4">
                                            <div class="card p-2 bg-white border">
                                                <small class="text-muted">USD (BCV)</small>
                                                <h6 class="text-bold text-success mb-0" id="prev_usd_{{ $rec->id }}">$ 0.00</h6>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="card p-2 bg-white border">
                                                <small class="text-muted">Precio Bs</small>
                                                <h6 class="text-bold text-info mb-0" id="prev_bs_{{ $rec->id }}">Bs 0.00</h6>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="card p-2 bg-white border">
                                                <small class="text-muted">USD (USDT)</small>
                                                <h6 class="text-bold text-warning mb-0" id="prev_usdt_{{ $rec->id }}">$ 0.00</h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Observaciones de Recepción</label>
                                    <textarea name="observacion_recepcion" class="form-control" rows="2" placeholder="Detalles de la recepción...">{{ $rec->observacion_recepcion }}</textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary btn-sm" id="btnSubmit_{{ $rec->id }}">Aplicar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        // Solo inicializar DataTables si hay registros en la tabla
        @if(count($recepciones) > 0)
            try {
                $('#tabla_recepcion').DataTable({
                    "responsive": true,
                    "autoWidth": false,
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                    },
                    "order": [[ 0, "asc" ]]
                });
            } catch (e) {
                console.log("DataTable init error:", e);
            }
        @endif

        function calcularPreciosLive(id) {
            let costo = parseFloat($('#costo_' + id).val()) || 0;
            let option = $('#modelo_' + id).find(':selected');

            if (!option.val()) {
                $('#prev_usd_' + id).text('$ 0.00');
                $('#prev_bs_' + id).text('Bs 0.00');
                $('#prev_usdt_' + id).text('$ 0.00');
                return;
            }

            let tasa_bcv = parseFloat(option.data('tasa-bcv')) || 0;
            let tasa_binance = parseFloat(option.data('tasa-binance')) || 0;
            let factor_bcv = parseFloat(option.data('factor-bcv')) || 0;
            let factor_usdt = parseFloat(option.data('factor-usdt')) || 0;
            let porcentaje_extra = parseFloat(option.data('porcentaje-extra')) || 0;

            let venta_bcv = 0;
            let venta_usdt = 0;

            if (factor_bcv > 0) {
                let diferencial = (tasa_bcv > 0) ? (tasa_binance / tasa_bcv) : 1;
                venta_bcv = (diferencial / factor_bcv) * costo;
            } else if (porcentaje_extra > 0) {
                venta_bcv = costo * (1 + porcentaje_extra);
            }

            if (factor_usdt > 0) {
                venta_usdt = costo / factor_usdt;
            } else if (porcentaje_extra > 0) {
                venta_usdt = costo * (1 + porcentaje_extra);
            } else {
                venta_usdt = costo;
            }

            $('#prev_usd_' + id).text('$ ' + venta_bcv.toFixed(2));
            $('#prev_bs_' + id).text('Bs ' + (venta_bcv * tasa_bcv).toFixed(2));
            $('#prev_usdt_' + id).text('$ ' + venta_usdt.toFixed(2));
        }

        $('.modelo-select').each(function() {
            let id = $(this).data('id');
            if ($(this).val()) calcularPreciosLive(id);
        });

        $('.distribucion-input').on('input', function() {
            let id = $(this).data('id');
            let total = parseFloat($('#total_qty_' + id).text()) || 0;
            let aprobar = parseFloat($('#cant_aprobar_' + id).val()) || 0;
            let retenido = parseFloat($('#cant_retenido_' + id).val()) || 0;
            let rechazado = parseFloat($('#cant_rechazado_' + id).val()) || 0;
            
            let suma = aprobar + retenido + rechazado;

            if (suma !== total) {
                $('#alertaSuma_' + id).show();
                $('#btnSubmit_' + id).prop('disabled', true);
            } else {
                $('#alertaSuma_' + id).hide();
                $('#btnSubmit_' + id).prop('disabled', false);
            }

            if (aprobar > 0) {
                $('#seccionAprobacion_' + id).slideDown();
                $('#costo_' + id).prop('required', true);
                $('#modelo_' + id).prop('required', true);
            } else {
                $('#seccionAprobacion_' + id).slideUp();
                $('#costo_' + id).prop('required', false);
                $('#modelo_' + id).prop('required', false);
            }
        });

        $('.costo-input, .modelo-select').on('input change', function() {
            calcularPreciosLive($(this).data('id'));
        });

        $(document).on('click', '.btn-revertir', function() {
            let id = $(this).data('id');

            Swal.fire({
                title: '¿Estás seguro de revertir?',
                text: "Se descontará del stock lo aprobado y se unificará de nuevo para corregir.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, revertir',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#form-revertir-' + id).submit();
                }
            });
        });
    });
</script>
@endsection