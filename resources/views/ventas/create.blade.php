@extends('layouts.app')

@section('title') Registrar Venta @endsection

@section('content')
<style>
    <style>
    /* Estilos para el buscador de lujo */
    .select2-results__option {
        padding: 10px !important;
        border-bottom: 1px solid #eee;
    }
    .select2-results__option--highlighted {
        background-color: #f8f9fa !important;
        color: #000 !important;
    }
    .select2-container--bootstrap4 .select2-selection--single {
        height: calc(2.25rem + 2px) !important;
    }
    .badge-price {
        font-size: 11px !important;
        padding: 4px 8px;
        margin-right: 5px;
    }
</style>

</style>

<main class="app-content">
    <div class="app-title">
        <div>
            <h1><i class="fa fa-shopping-cart"></i> SAYER - Punto de Venta| TASA BCV:<strong> <span class="badge badge-warning">{{number_format($tasa_bcv, 2, '.', ',')}} Bs.</span></strong></p></h1>
            <p>Sede: <strong>{{ $local->nombre }}</strong> | Caja: <span class="badge badge-success">ACTIVA</span> </p>
        </div>
    </div>

    @if(Gate::denies('operar-caja'))
        <div class="tile"><div class="alert alert-danger mb-0">No tienes permisos para vender.</div></div>
    @else
    <form action="{{ route('ventas.store') }}" method="POST" id="venta-form">
        @csrf
        {{-- Guardamos la tasa solo para cálculos visuales en el JS --}}
        <input type="hidden" id="tasa_referencial" value="{{ $tasa_bcv ?? 1 }}">
        
        <div class="row">
            <div class="col-md-12">
                <div class="tile">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold"><i class="fa fa-user"></i> Cliente</label>
                                <div class="input-group">
                                    <select name="id_cliente" id="id_cliente" class="form-control select2" required>
                                        <option value="">Seleccione cliente...</option>
                                        @foreach($clientes as $cliente)
                                            <option value="{{ $cliente->id }}" data-limite="{{ $cliente->limite_credito }}">
                                                {{ $cliente->nombre }} ({{ $cliente->identificacion }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="button" data-toggle="modal" data-target="#modalClienteRapido"><i class="fa fa-plus"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="font-weight-bold text-primary"><i class="fa fa-search"></i> Buscador de Insumos</label>
                                <select id="buscador_insumos" class="form-control select2-custom">
                                    <option value="">Buscar por producto, descripción o serial...</option>
                                    @foreach($productos as $p)
                                        @php $stockLocal = $p->existencias->first()->cantidad ?? 0; @endphp
                                        <option value="{{ $p->id }}" 
                                                data-descripcion="{{ $p->descripcion }}"
                                                data-bcv="{{ $p->precio_venta_usd }}"
                                                data-bs="{{ $p->precio_venta_bs }}"
                                                data-stock="{{ $stockLocal }}">
                                            {{ $p->producto }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="tile">
                    <h3 class="tile-title text-primary">Detalle de la Venta</h3>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="tabla-ventas">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>Producto</th>
                                    <th width="100px">Cant.</th>
                                    <th>Precio ($)</th>
                                    <th>Subtotal ($)</th>
                                    <th width="40px"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="tile p-0 shadow">
                    <div class="bg-dark text-white text-center p-4">
                        <h6 class="mb-0 text-muted small">TOTAL A PAGAR</h6>
                        <h1 id="total_final_usd" class="display-4 font-weight-bold">$ 0.00</h1>
                        <p id="total_final_bs" class="text-warning mb-0">0.00 Bs</p>
                    </div>
                    <div class="p-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="small font-weight-bold">Efectivo $</label>
                                    <input type="number" step="0.01" name="pago_usd_efectivo" class="form-control monto-pago" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="small font-weight-bold">Efectivo Bs</label>
                                    <input type="number" step="0.01" name="pago_bs_efectivo" class="form-control monto-pago" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="small font-weight-bold">Punto / Biopago (Bs)</label>
                            <input type="number" step="0.01" name="pago_punto_bs" class="form-control monto-pago" value="0">
                        </div>

                        <div class="form-group">
                            <label class="small font-weight-bold">Pago Móvil / Transf. (Bs)</label>
                            <input type="number" step="0.01" name="pago_pagomovil_bs" class="form-control monto-pago" value="0">
                        </div>

                        <div class="alert alert-secondary d-flex justify-content-between mb-2">
                            <div class="text-center">
                                <small class="d-block text-muted">Resta $</small>
                                <strong id="display_restante_usd" class="text-danger">$ 0.00</strong>
                            </div>
                            <div class="text-center">
                                <small class="d-block text-muted">Resta Bs</small>
                                <strong id="display_restante_bs" class="text-danger">0.00 Bs</strong>
                            </div>
                        </div>

                        
                        <div class="toggle-flip mt-2">
                            <label>
                                <input type="checkbox" id="switchCredito" name="es_credito">
                                <span class="flip-indictor" data-toggle-on="CRÉDITO" data-toggle-off="CONTADO">Venta a Crédito</span>
                            </label>
                        </div>
                        <div id="seccion_credito" style="display: none;" class="mt-2 p-2 border border-danger rounded text-center">
                            <input type="hidden" name="monto_credito_usd" id="monto_credito_usd" value="0">
                            <span class="badge badge-danger">Monto a Crédito: $<span id="label_monto_credito">0.00</span></span>
                            <small id="error_limite" class="text-danger d-block mt-1" style="display:none;"></small>
                        </div>
                        

                        <button type="submit" class="btn btn-success btn-block btn-lg mt-3" id="btn-finalizar" disabled>
                            <i class="fa fa-check-circle"></i> FINALIZAR VENTA
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @endif
</main>
<div class="modal fade" id="modalConfirmarVenta" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fa fa-shopping-cart"></i> Confirmar Transacción</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <h6 class="text-muted">TOTAL A COBRAR</h6>
                    <h2 id="confirm_total_usd" class="font-weight-bold text-dark">$ 0.00</h2>
                    <h5 id="confirm_total_bs" class="text-secondary">0.00 Bs</h5>
                </div>
                
                <table class="table table-sm table-bordered">
                    <tr class="bg-light">
                        <th>Método / Concepto</th>
                        <th class="text-right">Monto</th>
                    </tr>
                    <tr>
                        <td>Efectivo USD</td>
                        <td id="confirm_p_usd" class="text-right">$ 0.00</td>
                    </tr>
                    <tr>
                        <td>Total en Bolívares (Efectivo/Punto/PM)</td>
                        <td id="confirm_p_bs" class="text-right text-info">0.00 Bs</td>
                    </tr>
                    <tr id="fila_confirm_credito" style="display:none;">
                        <td class="text-danger font-weight-bold">Monto a CRÉDITO</td>
                        <td id="confirm_monto_credito" class="text-right text-danger font-weight-bold">$ 0.00</td>
                    </tr>
                </table>

                <div class="alert alert-warning text-center">
                    <p class="mb-0">¿Está seguro que desea procesar esta venta? <br><strong>Esta acción no se puede deshacer.</strong></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success btn-lg" id="btnProcesarVentaFinal">
                    <i class="fa fa-check"></i> SÍ, PROCESAR VENTA
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalClienteRapido" role="dialog" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-user-plus"></i> Registro Rápido de Cliente</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formClienteRapido">
                <div class="modal-body">
                    {{-- Pasamos el ID del local actual automáticamente --}}
                    <input type="hidden" name="id_local" value="{{ $local->id }}">
                    
                    <div class="form-group">
                        <label>Identificación (Cédula/RIF)</label>
                        <input type="text" name="identificacion" class="form-control" required placeholder="V-12345678" maxlength="9">
                    </div>
                    <div class="form-group">
                        <label>Nombre Completo / Razón Social</label>
                        <input type="text" name="nombre" class="form-control" required placeholder="Juan Perez">
                    </div>
                    <div class="form-group">
                        <label>Teléfono (Opcional)</label>
                        <input type="text" name="telefono" class="form-control" placeholder="0412-1234567">
                    </div>
                    <div class="form-group">
                        <label>Límite de Crédito ($)</label>
                        <input type="number" step="0.01" name="limite_credito" class="form-control" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarCliente">
                        <i class="fa fa-save"></i> Guardar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    const TASA = parseFloat($('#tasa_referencial').val()) || 1;

    let detalleVentas = [];
    // --- CONFIGURACIÓN SELECT2 DE LUJO CORREGIDA ---
    function formatRepo(repo) {
        // VALIDACIÓN: Si es el placeholder (opción vacía) o está cargando, retornar el texto simple
        // Esto evita que aparezcan los badges con "undefined" al inicio
        if (repo.loading || !repo.id) {
            return repo.text;
        }
        
        let data = repo.element.dataset;
        
        // Estructura visual basada en tu tabla de referencia
        /* <span class='badge' style='background-color: #fd7e14; color: white; padding: 5px 8px;'>Venta USDT: ${data.usdt}</span>*/
        var $container = $(
            `<div class='select2-result-repository clearfix'>
                <div class='select2-result-repository__meta'>
                    <div class='d-flex justify-content-between'>
                        <span class='select2-result-repository__title' style='font-weight: bold; color: #333; font-size: 15px;'></span>
                        <small class='text-muted'>${data.serial || ''}</small>
                    </div>
                    <div class='select2-result-repository__description' style='font-size: 11px; color: #777; margin-bottom: 5px; line-height: 1.2;'></div>
                    <div class='d-flex justify-content-start flex-wrap' style='gap: 5px;'>
                        <span class='badge' style='background-color: #28a745; color: white; padding: 5px 8px;'>Venta BCV: $${data.bcv}</span>
                        <span class='badge' style='background-color: #007bff; color: white; padding: 5px 8px;'>Venta BS: ${data.bs} Bs</span>
                       
                        <span class='badge ${parseInt(data.stock) > 0 ? 'badge-dark' : 'badge-danger'}' style='padding: 5px 8px;'>📦 Stock: ${data.stock}</span>
                    </div>
                </div>
            </div>`
        );

        $container.find(".select2-result-repository__title").text(repo.text);
        $container.find(".select2-result-repository__description").text(data.descripcion || 'Sin descripción adicional');
        
        return $container;
    }

    function formatRepoSelection(repo) {
        return repo.text;
    }

    // Inicialización del Select2
    $('.select2-custom').select2({
        theme: 'bootstrap4',
        templateResult: formatRepo, 
        templateSelection: formatRepoSelection,
        width: '100%',
        escapeMarkup: function(m) { return m; } 
    });
    // --- LÓGICA DE TABLA ---
    $('#buscador_insumos').on('select2:select', function (e) {
        let data = e.params.data.element.dataset;
        let id = $(this).val();
        let nombre = e.params.data.text.trim();
        let precio_bcv = parseFloat(data.bcv);
        let precio_bs = parseFloat(data.bs);
        let stock = parseInt(data.stock);

        if (stock <= 0) {
            Swal.fire('Sin Stock', 'No hay existencias', 'error');
            return;
        }

        let existe = detalleVentas.find(item => item.id === id);
        if (existe) {
            if (existe.cantidad + 1 > stock) return;
            existe.cantidad++;
        } else {
            detalleVentas.push({ id, nombre, precio_bcv, precio_bs, cantidad: 1, stock });
        }
        $(this).val(null).trigger('change');
        renderTabla();
    });

    function renderTabla() {
        let html = '';
        let totalUSD = 0;
        let totalBS = 0;

        detalleVentas.forEach((item, index) => {
            let subtotal = item.cantidad * item.precio_bcv;
            totalUSD += subtotal;
            totalBS += (item.cantidad * item.precio_bs);

            html += `<tr>
                <td>${item.nombre}</td>
                <td><input type="number" class="form-control form-control-sm change-cant" data-index="${index}" value="${item.cantidad}" min="1"  max="${item.stock}"></td>
                <td>$${item.precio_bcv.toFixed(2)}</td>
                <td>$${subtotal.toFixed(2)}</td>
                <td><button type="button" class="btn btn-sm btn-danger remove-item" data-index="${index}"><i class="fa fa-trash"></i></button></td>
                <input type="hidden" name="articulos[${index}][id_insumo]" value="${item.id}">
                <input type="hidden" name="articulos[${index}][cantidad]" value="${item.cantidad}">
                <input type="hidden" name="articulos[${index}][precio_unitario]" value="${item.precio_bcv}">
            </tr>`;
        });
        $('#tabla-ventas tbody').html(html);
        actualizarTotales(totalUSD, totalBS);
    }

    function actualizarTotales(totalUSD, totalBS) {
        // Usamos directamente el totalBS que viene de la suma de la tabla
        $('#total_final_usd').text(`$ ${totalUSD.toFixed(2)}`);
        let total_bs_tasa=totalUSD*TASA;
        $('#total_final_bs').text(`${total_bs_tasa.toFixed(2)} Bs`);
        
        // Hidden para el total principal en dólares (para el backend)
        if ($('#total_hidden').length === 0) {
            $('#venta-form').append(`<input type="hidden" name="total_usd" id="total_hidden">`);
        }
        $('#total_hidden').val(totalUSD.toFixed(2));

        // Si necesitas enviar el total en Bs exacto al servidor, añade este hidden:
        if ($('#total_bs_hidden').length === 0) {
            $('#venta-form').append(`<input type="hidden" name="total_bs" id="total_bs_hidden">`);
        }
        $('#total_bs_hidden').val(total_bs_tasa.toFixed(2));

        actualizarCalculoPagos();
    }

    function actualizarCalculoPagos() {
        // --- LÓGICA DE VALIDACIÓN DE EXCESO CORREGIDA ---
let totalFacturaUSD = parseFloat($('#total_hidden').val()) || 0;

let pUSD = parseFloat($('input[name="pago_usd_efectivo"]').val()) || 0;
let pBsEfectivo = parseFloat($('input[name="pago_bs_efectivo"]').val()) || 0;
let pBsPunto = parseFloat($('input[name="pago_punto_bs"]').val()) || 0;
let pBsPmovil = parseFloat($('input[name="pago_pagomovil_bs"]').val()) || 0;

// Convertir Bs a USD con margen de error
let totalBs = pBsEfectivo + pBsPunto + pBsPmovil;
let abonadoEnBsConvertidoUSD = totalBs / TASA;

let totalPagadoUSD = pUSD + abonadoEnBsConvertidoUSD;
let restanteUSD = totalFacturaUSD - totalPagadoUSD;

console.log('Debug:', { 
    totalFactura: totalFacturaUSD, 
    pagadoUSD: totalPagadoUSD, 
    restanteUSD: restanteUSD 
});

if (restanteUSD < -0.01) {
    // ❌ EXCESO - BLOQUEAR
    $('#display_restante_usd').text("EXCESO").addClass('text-warning').removeClass('text-danger');
    $('#display_restante_bs').text("EXCESO").addClass('text-warning').removeClass('text-danger');
    
    if($('#alerta-exceso').length === 0) {
        $('.alert-secondary').after('<small id="alerta-exceso" class="text-warning d-block text-center font-weight-bold">¡PAGADO EN EXCESO!</small>');
    }
    
    $('#btn-finalizar').prop('disabled', true).html('<i class="fa fa-exclamation-triangle"></i> EXCESO');
    
} else if (restanteUSD > 0.01) {
    // ❌ FALTA PAGO - BLOQUEAR
    $('#display_restante_usd').text(`$ ${restanteUSD.toFixed(2)}`).addClass('text-danger').removeClass('text-warning');
    $('#display_restante_bs').text(`${(restanteUSD * TASA).toFixed(2)} Bs`).addClass('text-danger').removeClass('text-warning');
    $('#alerta-exceso').remove();
    
    $('#btn-finalizar').prop('disabled', true).html('<i class="fa fa-times-circle"></i> FALTANTE');
    
} else {
    // ✅ PAGO EXACTO - HABILITAR
    $('#display_restante_usd').text('$ 0.00').removeClass('text-danger text-warning');
    $('#display_restante_bs').text('0.00 Bs').removeClass('text-danger text-warning');
    $('#alerta-exceso').remove();
    
    $('#btn-finalizar').prop('disabled', false).html('<i class="fa fa-check-circle"></i> FINALIZAR VENTA');
}

        // Lógica de Crédito vs Contado
        let esCredito = $('#switchCredito').is(':checked');
        
        if (esCredito) {
            $('#monto_credito_usd').val(restanteUSD.toFixed(2));
            $('#label_monto_credito').text(restanteUSD.toFixed(2));
            validarLimiteCredito(restanteUSD);
        } else {
            // Si es contado, solo habilita si el restante es casi 0
            $('#btn-finalizar').prop('disabled', restanteUSD > 0.05);
        }
    }

    function validarLimiteCredito(monto) {
        let limite = parseFloat($('#id_cliente option:selected').data('limite')) || 0;
        if (monto > limite) {
            $('#error_limite').text(`Límite excedido ($${limite})`).show();
            $('#btn-finalizar').prop('disabled', true);
        } else {
            $('#error_limite').hide();
            $('#btn-finalizar').prop('disabled', monto <= 0); // No hacer crédito de $0
        }
    }

    // Handlers de eventos
    $(document).on('input', '.monto-pago', actualizarCalculoPagos);
    $(document).on('click', '.remove-item', function() {
        detalleVentas.splice($(this).data('index'), 1);
        renderTabla();
        actualizarCalculoPagos();
    });
    $('#switchCredito').on('change', function() {
        $('#seccion_credito').toggle(this.checked);
        actualizarCalculoPagos();
    });
    $(document).on('change', '.change-cant', function() {
        let index = $(this).data('index');
        let val = parseInt($(this).val());
        if (val > detalleVentas[index].stock) val = detalleVentas[index].stock;
        detalleVentas[index].cantidad = val || 1;
        renderTabla();
    });
    // Bloqueo de re-envío al procesar
    // Bloqueo de re-envío al procesar y Modal de Confirmación
    $('#venta-form').on('submit', function(e) {
        // 1. Detenemos cualquier otro evento y el envío inicial
        e.preventDefault();
        e.stopImmediatePropagation();

        // 2. Validamos si hay productos
        if (detalleVentas.length === 0) {
            Swal.fire({
                title: 'Carrito Vacío',
                icon: 'error',
                text: 'Debes agregar al menos un producto.'
            });
            return false;
        }

        // 3. Validamos si hay exceso
        let totalFacturaUSD = parseFloat($('#total_hidden').val()) || 0;
        let totalFacturaBS = parseFloat($('#total_bs_hidden').val()) || 0;
        let pUSD = parseFloat($('input[name="pago_usd_efectivo"]').val()) || 0;
        let pBS_Efectivo = parseFloat($('input[name="pago_bs_efectivo"]').val()) || 0;
        let pBS_Punto = parseFloat($('input[name="pago_punto_bs"]').val()) || 0;
        let pBS_PMovil = parseFloat($('input[name="pago_pagomovil_bs"]').val()) || 0;
        
        let pBS_Total = pBS_Efectivo + pBS_Punto + pBS_PMovil;

        // Si el pago total supera la factura, mostramos advertencia
        let pagadoTotalUSD = pUSD + (pBS_Total / (totalFacturaBS / totalFacturaUSD));
        
        if (pagadoTotalUSD > (totalFacturaUSD + 0.05)) { // 5 centavos de margen
            Swal.fire({
                title: 'Pago Excedido',
                text: 'El monto total ingresado es mayor a la factura. Por favor ajusta los pagos.',
                icon: 'warning'
            });
            return false;
        }

        // --- NUEVO: LLENAR Y MOSTRAR MODAL DE CONFIRMACIÓN ---
        
        // Asignar valores visuales al modal
        $('#confirm_total_usd').text(`$ ${totalFacturaUSD.toFixed(2)}`);
        $('#confirm_total_bs').text(`${totalFacturaBS.toLocaleString('es-VE', {minimumFractionDigits: 2})} Bs`);
        $('#confirm_p_usd').text(`$ ${pUSD.toFixed(2)}`);
        $('#confirm_p_bs').text(`${pBS_Total.toLocaleString('es-VE', {minimumFractionDigits: 2})} Bs`);

        // Lógica visual para la fila de crédito en el modal
        if ($('#switchCredito').is(':checked')) {
            let montoCredito = $('#monto_credito_usd').val();
            $('#fila_confirm_credito').show();
            $('#confirm_monto_credito').text(`$ ${montoCredito}`);
        } else {
            $('#fila_confirm_credito').hide();
        }

        // Abrir el modal
        $('#modalConfirmarVenta').modal('show');
    });

    // EVENTO PARA EL BOTÓN FINAL DENTRO DEL MODAL
    $(document).on('click', '#btnProcesarVentaFinal', function() {
        // 1. Deshabilitar el botón y mostrar spinner (como hacías en tu función original)
        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Procesando...');
        $('#btn-finalizar').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Procesando...');

        // 2. Enviar el formulario directamente al servidor
        document.getElementById('venta-form').submit();
    });
    // --- LÓGICA DE REGISTRO DE CLIENTE VÍA AJAX ---
    $('#formClienteRapido').on('submit', function(e) {
        e.preventDefault(); 
        
        let btn = $('#btnGuardarCliente');
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: "{{ route('clientes.store_ajax') }}", // Asegúrate que este nombre de ruta coincida con web.php
            method: "POST",
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // 1. Crear la nueva opción en el Select2
                    let newOption = new Option(
                        `${response.cliente.nombre} (${response.cliente.identificacion})`, 
                        response.cliente.id, 
                        true, 
                        true
                    );
                    
                    // 2. Añadir el data-limite para que la lógica de crédito funcione
                    $(newOption).attr('data-limite', response.cliente.limite_credito);
                    
                    $('#id_cliente').append(newOption).trigger('change');

                    // 3. Cerrar modal y limpiar
                    $('#modalClienteRapido').modal('hide');

                    $('#formClienteRapido')[0].reset();
                    
                    Swal.fire('¡Éxito!', 'Cliente registrado y seleccionado.', 'success');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON) {
                    // Errores de validación
                    let errors = xhr.responseJSON.errors || {};
                    let mensajes = [];

                    Object.keys(errors).forEach(function (campo) {
                        errors[campo].forEach(function (msg) {
                            mensajes.push(msg);
                        });
                    });

                    Swal.fire('Errores de validación', mensajes.join('<br>'), 'error');
                } else {
                    // Otros errores (500, etc.)
                    let errorMsg = 'Error al registrar cliente.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    Swal.fire('Error', errorMsg, 'error');
                }
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar Cliente');
            }
        });
    });
   
    $('#switchCredito').on('change', function() {
    let checkbox = $(this);
    let btnFinalizar = $('#btnFinalizarVenta'); // Asegúrate de que este sea el ID de tu botón
    
    if (checkbox.is(':checked')) {
        @cannot('gestionar-creditos-avanzado')
            checkbox.prop('checked', false);
            
            Swal.fire({
                title: '¿Solicitar Autorización?',
                text: "Se enviará un PIN de 6 dígitos al WhatsApp del jefe para habilitar este crédito.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, enviar PIN',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false // Obliga a interactuar con el modal
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post("{{ route('ventas.solicitar_pin') }}", {
                        _token: "{{ csrf_token() }}",
                        local_nombre: "{{ $local->nombre }}",
                        cliente_nombre: $('#id_cliente option:selected').text().trim(),
                        monto_total: $('#total_hidden').val(),
                        cantidad_items: detalleVentas.length
                    }, function(response) {
                        if(response.wa_link) { window.open(response.wa_link, '_blank'); }

                        Swal.fire({
                            title: 'Introduce el PIN',
                            text: 'El jefe recibió un código de 6 dígitos',
                            input: 'text',
                            inputAttributes: { maxlength: 6, autocapitalize: 'off' },
                            showCancelButton: true,
                            confirmButtonText: 'Validar PIN',
                            cancelButtonText: 'Cancelar',
                            showLoaderOnConfirm: true,
                            allowOutsideClick: false,
                            preConfirm: (pin) => {
                                return $.post("{{ route('ventas.verificar_pin') }}", {
                                    _token: "{{ csrf_token() }}",
                                    pin: pin
                                }).fail(error => {
                                    Swal.showValidationMessage(error.responseJSON.message);
                                });
                            }
                        }).then((res) => {
                            if (res.isConfirmed) {
                                checkbox.prop('checked', true);
                                $('#seccion_credito').fadeIn();
                                actualizarCalculoPagos(); 
                                Swal.fire('Autorizado', 'Crédito desbloqueado.', 'success');
                            } else {
                                console.log('segundo');
                                // SEGUNDO CASO: Canceló al meter el PIN
                                 $('#seccion_credito').hide();
                                checkbox.prop('checked', false);
                                btnFinalizar.prop('disabled', true); // Bloqueo preventivo
                                actualizarCalculoPagos();
                            }
                        });
                    });
                } else {
                    console.log('primero');
                    // PRIMER CASO: Canceló el envío del PIN
                    
                        $('#seccion_credito').hide();
                      
                    checkbox.prop('checked', false);
                    btnFinalizar.prop('disabled', true);
                    actualizarCalculoPagos();

                }
            });
        @else
            $('#seccion_credito').fadeIn();
            actualizarCalculoPagos();
        @endcannot
    } else {
        $('#seccion_credito').fadeOut();
        actualizarCalculoPagos();
    }
});
});
</script>
@endsection