@extends('layouts.app')

@section('title') Registrar Venta @endsection

@section('content')
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

<main class="app-content">
    <div class="app-title">
        <div>
            <h1><i class="fa fa-shopping-cart"></i> SAYER - Punto de Venta</h1>
            <p>Sede Actual: <strong>{{ $local->nombre }}</strong></p>
        </div>
        <ul class="app-breadcrumb breadcrumb">
            <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
            <li class="breadcrumb-item">Ventas</li>
            <li class="breadcrumb-item"><a href="#">Nueva</a></li>
        </ul>
    </div>

    @if(Gate::denies('operar-caja'))
        <div class="tile">
            <div class="alert alert-danger mb-0">
                <h5><i class="fa fa-ban"></i> ¡Atención!</h5>
                No tienes los permisos necesarios para operar el módulo de ventas.
            </div>
        </div>
    @else
    <form action="{{ route('ventas.store') }}" method="POST" id="venta-form">
        @csrf
        <input type="hidden" id="tasa_referencial" value="{{ $tasa_bcv ?? 0 }}">
        
        {{-- SECCIÓN SUPERIOR: CLIENTE Y BUSCADOR DE 12 COLUMNAS --}}
        <div class="row">
            <div class="col-md-12">
                <div class="tile">
                    <div class="row">
                        {{-- Cliente --}}
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold"><i class="fa fa-user"></i> Cliente</label>
                                <select name="id_cliente" id="id_cliente" class="form-control select2" required>
                                    <option value="">Seleccione cliente...</option>
                                    @foreach($clientes as $cliente)
                                        <option value="{{ $cliente->id }}" data-limite="{{ $cliente->limite_credito }}">
                                            {{ $cliente->nombre }} ({{ $cliente->identificacion }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        {{-- Buscador de 12 columnas (8 en este row para balancear con cliente) --}}
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="font-weight-bold text-primary"><i class="fa fa-search"></i> Buscador de Insumos</label>
                                <select id="buscador_insumos" class="form-control select2-custom">
                                    <option value="">Buscar por producto, descripción o serial...</option>
                                  
                                    @foreach($productos as $p)
                                        @php
                                            $stockLocal = $p->existencias->first()->cantidad ?? 0;
                                            $precio_bcv = $p->precio_venta_usd;
                                            $precio_bs = $p->precio_venta_bs;
                                            //$precio_usdt=$p->precio_venta_usdt;
                                        @endphp
                                        <option value="{{ $p->id }}" 
                                                data-serial="{{ $p->serial ?? 'S/N' }}"
                                                data-descripcion="{{ $p->descripcion }}"
                                                data-bcv="{{ number_format($precio_bcv, 2) }}"
                                                data-bs="{{ number_format($precio_bs, 2) }}"
                                                
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
            {{-- DETALLE DE LA VENTA --}}
            <div class="col-md-8">
                <div class="tile">
                    <h3 class="tile-title text-primary">Detalle de la Venta</h3>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" id="tabla-ventas">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>Producto / Descripción</th>
                                    <th width="120px">Cant.</th>
                                    <th>Precio ($)</th>
                                    <th>Subtotal ($)</th>
                                    <th width="40px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Se llena con JS --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- TOTALES Y PAGO --}}
            <div class="col-md-4">
                <div class="tile p-0 shadow">
                    <div class="bg-dark text-white text-center p-4">
                        <h6 class="mb-0 text-muted">TOTAL A PAGAR</h6>
                        <h1 id="total_final_usd" class="display-4 font-weight-bold">$ 0.00</h1>
                        <p id="total_final_bs" class="text-warning mb-0">Ref: 0.00 Bs</p>
                    </div>
                    <div class="p-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold small">Efectivo USD</label>
                                    <input type="number" step="0.01" name="pago_usd_efectivo" class="form-control monto-pago" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold small">Efectivo BS</label>
                                    <input type="number" step="0.01" name="pago_bs_efectivo" class="form-control monto-pago" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold small">Punto / Biopago (Bs)</label>
                            <input type="number" step="0.01" name="pago_bs_punto" class="form-control monto-pago" placeholder="Monto en Punto">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold small">Transferencia / Pago Móvil (Bs)</label>
                            <input type="number" step="0.01" name="pago_bs_digital" class="form-control monto-pago" placeholder="Monto en Transferencia">
                        </div>

                        <div class="alert alert-secondary d-flex justify-content-between mb-2">
                            <div class="text-center">
                                <small class="d-block text-muted">Restan $</small>
                                <strong id="display_restante_usd" class="text-danger">$ 0.00</strong>
                            </div>
                            <div class="text-center">
                                <small class="d-block text-muted">Restan Bs</small>
                                <strong id="display_restante_bs" class="text-danger">0.00 Bs</strong>
                            </div>
                        </div>

                        @can('gestionar-creditos-avanzado')
                        <div class="toggle-flip mt-2">
                            <label>
                                <input type="checkbox" id="switchCredito" name="es_credito">
                                <span class="flip-indictor" data-toggle-on="CRÉDITO" data-toggle-off="CONTADO"></span>
                            </label>
                        </div>
                        <div id="seccion_credito" style="display: none;" class="mt-2 p-2 border border-danger rounded">
                            <input type="hidden" name="monto_credito_usd" id="monto_credito_usd">
                            <small id="error_limite" class="text-danger" style="display:none;"></small>
                        </div>
                        @endcan

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
@endsection

@section('scripts')
<script type="text/javascript">
$(document).ready(function() {
    // Usamos el ID correcto que tienes en el hidden del HTML (tasa_referencial)
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

    // --- LÓGICA DE VENTA (SE MANTIENE IGUAL) ---
    $('#buscador_insumos').on('select2:select', function (e) {
        let data = e.params.data.element.dataset;
        let id = $(this).val();
        let nombre = e.params.data.text.trim();
        
        // 1. Si no hay ID seleccionado (ej. limpieza del buscador), detener la ejecución sin avisos
        if (!id || id === "") return;
        // Capturamos ambos precios del data-attribute
        let precio_bcv = parseFloat(data.bcv.replace(/,/g, '')); // Limpiamos comas si las hay
        let precio_bs = parseFloat(data.bs.replace(/,/g, ''));
        let stock = parseInt(data.stock);

        // 2. Solo mostrar error si el ID existe pero no hay stock
        if (stock <= 0) {
            Swal.fire({
                title: 'Sin Stock',
                text: 'Este producto no tiene existencias en esta sede',
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
            $(this).val(null).trigger('change');
            return;
        }

        let existe = detalleVentas.find(item => item.id === id);
        if (existe) {
            if (existe.cantidad + 1 > stock) {
                swal("Límite", "No hay más stock disponible", "warning");
                return;
            }
            existe.cantidad++;
        } else {
            // Guardamos AMBOS precios en el objeto del carrito
            detalleVentas.push({ 
                id, 
                nombre, 
                precio_bcv, 
                precio_bs, 
                cantidad: 1, 
                stock 
            });
        }

        $(this).val(null).trigger('change');
        renderTabla();
    });
    function renderTabla() {
        let html = '';
        let totalBCV = 0;
        let totalBS = 0;

        detalleVentas.forEach((item, index) => {
            let subtotalBCV = item.cantidad * item.precio_bcv;
            let subtotalBS = item.cantidad * item.precio_bs;
            
            totalBCV += subtotalBCV;
            totalBS += subtotalBS;

            html += `
                <tr>
                    <td><strong class="text-dark">${item.nombre}</strong></td>
                    <td>
                        <input type="number" class="form-control form-control-sm change-cant" 
                               data-index="${index}" value="${item.cantidad}" min="1" max="${item.stock}">
                    </td>
                    <td class="text-right">
                        $${item.precio_bcv.toFixed(2)}<br>
                        <small class="text-muted">${item.precio_bs.toFixed(2)} Bs</small>
                    </td>
                    <td class="text-right font-weight-bold text-primary">
                        $${subtotalBCV.toFixed(2)}<br>
                        <small class="text-info">${subtotalBS.toFixed(2)} Bs</small>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-item" data-index="${index}">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                    <input type="hidden" name="articulos[${index}][id_insumo]" value="${item.id}">
                    <input type="hidden" name="articulos[${index}][cantidad]" value="${item.cantidad}">
                    <input type="hidden" name="articulos[${index}][precio_unitario]" value="${item.precio_bcv}">
                    <input type="hidden" name="articulos[${index}][precio_bs]" value="${item.precio_bs}">
                </tr>`;
        });
        $('#tabla-ventas tbody').html(html);
        
        // Llamamos a tu función original pero pasando ambos totales
        calcularTotales(totalBCV, totalBS);
    }

    function calcularTotales(totalUSD, totalBS) {
        // Ya no hacemos: totalUSD * TASA
        // Usamos directamente el totalBS que viene de la suma de la tabla
        $('#total_final_usd').text(`$ ${totalUSD.toFixed(2)}`);
        $('#total_final_bs').text(`${totalBS.toLocaleString('es-VE', {minimumFractionDigits: 2})} Bs`);
        
        // Hidden para el total principal en dólares (para el backend)
        if ($('#total_hidden').length === 0) {
            $('#venta-form').append(`<input type="hidden" name="total_usd" id="total_hidden">`);
        }
        $('#total_hidden').val(totalUSD.toFixed(2));

        // Si necesitas enviar el total en Bs exacto al servidor, añade este hidden:
        if ($('#total_bs_hidden').length === 0) {
            $('#venta-form').append(`<input type="hidden" name="total_bs" id="total_bs_hidden">`);
        }
        $('#total_bs_hidden').val(totalBS.toFixed(2));

        actualizarCalculoPagos();
    }

    function actualizarCalculoPagos() {
        let totalFacturaUSD = parseFloat($('#total_hidden').val()) || 0;
        let totalFacturaBS  = parseFloat($('#total_bs_hidden').val()) || 0;

        let pUSD_efectivo = parseFloat($('input[name="pago_usd_efectivo"]').val()) || 0;
        let pBS_efectivo  = parseFloat($('input[name="pago_bs_efectivo"]').val()) || 0;
        let pBS_punto     = parseFloat($('input[name="pago_bs_punto"]').val()) || 0;
        let pBS_transf    = parseFloat($('input[name="pago_bs_digital"]').val()) || 0;

        let totalAbonadoBS = pBS_efectivo + pBS_punto + pBS_transf;

        // Cálculo de restantes
        let porcentajeCubiertoBS = totalFacturaBS > 0 ? (totalAbonadoBS / totalFacturaBS) : 0;
        let equivalenteEnUSD = totalFacturaUSD * porcentajeCubiertoBS;
        let restanteUSD = totalFacturaUSD - pUSD_efectivo - equivalenteEnUSD;

        let porcentajeCubiertoUSD = totalFacturaUSD > 0 ? (pUSD_efectivo / totalFacturaUSD) : 0;
        let restanteBS = totalFacturaBS - totalAbonadoBS - (totalFacturaBS * porcentajeCubiertoUSD);

        // --- NUEVA LÓGICA DE VALIDACIÓN DE EXCESO ---
        // Usamos un pequeño margen (0.01) para evitar problemas de decimales
        if (restanteUSD < -0.01) {
            $('#display_restante_usd').text("EXCESO").addClass('text-warning').removeClass('text-danger');
            $('#display_restante_bs').text("EXCESO").addClass('text-warning').removeClass('text-danger');
            
            // Opcional: Mostrar un mensaje de alerta pequeño debajo de los campos
            if($('#alerta-exceso').length === 0) {
                $('.alert-secondary').after('<small id="alerta-exceso" class="text-danger d-block text-center font-weight-bold">¡El monto ingresado supera el total de la factura!</small>');
            }
            
            $('#btn-finalizar').prop('disabled', true); // Bloquea la venta por seguridad
        } else {
            // Resetear estilos si el monto es correcto
            $('#display_restante_usd').text(`$ ${Math.max(0, restanteUSD).toFixed(2)}`).addClass('text-danger').removeClass('text-warning');
            $('#display_restante_bs').text(`${Math.max(0, restanteBS).toFixed(2)} Bs`).addClass('text-danger').removeClass('text-warning');
            $('#alerta-exceso').remove();

            // Lógica normal de finalización
            let esCredito = $('#switchCredito').is(':checked');
            let saldoCero = (restanteUSD <= 0.01);

            if (esCredito) {
                $('#monto_credito_usd').val(restanteUSD.toFixed(2));
                validarLimiteCredito(restanteUSD);
            } else {
                $('#btn-finalizar').prop('disabled', !saldoCero);
            }
        }
    }

    // Escuchar cambios en todos los campos de pago
    $(document).on('input', '.monto-pago', actualizarCalculoPagos);

    function validarLimiteCredito(monto) {
        let limite = parseFloat($('#id_cliente option:selected').data('limite')) || 0;
        if (monto > limite) {
            $('#error_limite').html(`<i class="fa fa-exclamation-triangle"></i> Excede el límite ($${limite})`).show();
            $('#btn-finalizar').prop('disabled', true);
        } else {
            $('#error_limite').hide();
            $('#btn-finalizar').prop('disabled', false);
        }
    }

    $(document).on('click', '.remove-item', function() {
        detalleVentas.splice($(this).data('index'), 1);
        renderTabla();
    });

    $(document).on('change', '.change-cant', function() {
        let index = $(this).data('index');
        let val = parseInt($(this).val());
        if (val > detalleVentas[index].stock) val = detalleVentas[index].stock;
        detalleVentas[index].cantidad = val || 1;
        renderTabla();
    });

    $('.monto-pago').on('input', actualizarCalculoPagos);

    $('#switchCredito').on('change', function() {
        $('#seccion_credito').toggle($(this).is(':checked'));
        actualizarCalculoPagos();
    });

    $('#venta-form').on('submit', function(e) {
        // 1. Detenemos cualquier otro evento
        e.stopImmediatePropagation();

        // 2. Validamos si hay productos
        if (detalleVentas.length === 0) {
            e.preventDefault();
            Swal.fire({
                title: 'Carrito Vacío',
                icon: 'error',
                text: 'Debes agregar al menos un producto.'
            });
            return false;
        }

        // 3. Validamos si hay exceso (Opcional: puedes dejarlo pasar o bloquearlo)
        let totalFacturaUSD = parseFloat($('#total_hidden').val()) || 0;
        let pUSD = parseFloat($('input[name="pago_usd_efectivo"]').val()) || 0;
        let pBS = (parseFloat($('input[name="pago_bs_efectivo"]').val()) || 0) + 
                  (parseFloat($('input[name="pago_bs_punto"]').val()) || 0) + 
                  (parseFloat($('input[name="pago_bs_digital"]').val()) || 0);

        // Si el pago total supera la factura, mostramos advertencia antes de ir al backend
        // (Calculamos excedente aproximado)
        let pagadoTotalUSD = pUSD + (pBS / (parseFloat($('#total_bs_hidden').val()) / totalFacturaUSD));
        
        if (pagadoTotalUSD > (totalFacturaUSD + 0.05)) { // 5 centavos de margen
            e.preventDefault();
            Swal.fire({
                title: 'Pago Excedido',
                text: 'El monto total ingresado es mayor a la factura. Por favor ajusta los pagos.',
                icon: 'warning'
            });
            return false;
        }

        // 4. Si todo está OK, procesamos
        $('#btn-finalizar').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Procesando...');
        return true; 
    });
});
</script>
@endsection