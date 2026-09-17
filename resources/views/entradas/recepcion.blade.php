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

    {{-- Tarjeta de Filtros Dinámicos (Onchange) --}}
    <div class="card card-outline card-primary shadow mb-3">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filtros de Búsqueda</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 form-group">
                    <label>Fecha Desde:</label>
                    <input type="date" id="filtro_fecha_desde" class="form-control form-control-sm filtro-datatable">
                </div>
                <div class="col-md-3 form-group">
                    <label>Fecha Hasta:</label>
                    <input type="date" id="filtro_fecha_hasta" class="form-control form-control-sm filtro-datatable">
                </div>
                <div class="col-md-3 form-group">
                    <label>Proveedor:</label>
                    <select id="filtro_proveedor" class="form-control form-control-sm filtro-datatable">
                        <option value="">Todos los Proveedores</option>
                        @foreach($proveedores as $prov)
                            <option value="{{ $prov->id }}">{{ $prov->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Depósito Destino:</label>
                    <select id="filtro_local" class="form-control form-control-sm filtro-datatable">
                        <option value="">Todos los Depósitos</option>
                        @foreach($depositos as $dep)
                            <option value="{{ $dep->id }}">{{ $dep->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Estado:</label>
                    <select id="filtro_estado" class="form-control form-control-sm filtro-datatable">
                        <option value="">PENDIENTE, RETENIDO y PROCESADO</option>
                        <option value="PENDIENTE">PENDIENTE</option>
                        <option value="RETENIDO">RETENIDO</option>
                        <option value="PROCESADO">PROCESADO</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary shadow">
        <div class="card-header">
            <h3 class="card-title text-bold">Insumos Pendientes de Revisión Física y Cuarentena</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-sm" id="tabla_recepcion" style="width:100%">
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
                        {{-- Renderizado mediante AJAX por DataTables --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        // Inicialización de DataTable con Server-Side AJAX
        let tablaRecepcion = $('#tabla_recepcion').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: "{{ route('entradas.recepcion.data') }}",
                data: function (d) {
                    d.fecha_desde = $('#filtro_fecha_desde').val();
                    d.fecha_hasta = $('#filtro_fecha_hasta').val();
                    d.id_proveedor = $('#filtro_proveedor').val();
                    d.id_local = $('#filtro_local').val();
                    d.estado = $('#filtro_estado').val();
                }
            },
            columns: [
                { data: 'created_at', name: 'insumos_recepcion.created_at' },
                { data: 'orden', name: 'detalleEntrada.entrada.nro_orden_entrega', defaultContent: 'S/N' },
                { data: 'proveedor', name: 'detalleEntrada.entrada.proveedor.nombre' },
                { data: 'local', name: 'local.nombre' },
                { data: 'insumo', name: 'insumo.producto' },
                { data: 'cantidad', name: 'insumos_recepcion.cantidad' },
                { data: 'costo_unitario_usd', name: 'insumos_recepcion.costo_unitario_usd' },
                { data: 'estado', name: 'insumos_recepcion.estado' },
                { data: 'acciones', name: 'acciones', orderable: false, searchable: false }
            ],
            order: [[ 0, "desc" ]], // Orden descendente por fecha
            language: {
                url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
            }
        });

        // Eventos onchange / input para recargar la tabla dinámicamente
        $('.filtro-datatable').on('change input', function() {
            tablaRecepcion.ajax.reload();
        });

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

        $(document).on('shown.bs.modal', '.modal', function () {
            let modalId = $(this).attr('id');
            let id = modalId.split('_')[1];
            if ($('#modelo_' + id).val()) {
                calcularPreciosLive(id);
            }
        });

        $(document).on('input', '.distribucion-input', function() {
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

        $(document).on('input change', '.costo-input, .modelo-select', function() {
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