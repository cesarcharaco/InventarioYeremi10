ALTER TABLE `detalle_ventas` 
ADD COLUMN `promocion_regla_id` BIGINT UNSIGNED NULL AFTER `id_insumo`,
ADD COLUMN `porcentaje_descuento_aplicado` DECIMAL(5,2) NULL AFTER `precio_unitario`,
ADD CONSTRAINT `detalle_ventas_promocion_regla_id_foreign` 
FOREIGN KEY (`promocion_regla_id`) REFERENCES `promociones_reglas` (`id`) 
ON DELETE SET NULL;

CREATE TABLE `promociones_reglas` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `local_id` BIGINT UNSIGNED NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `alcance` ENUM('categoria', 'grupo', 'insumo') NOT NULL,
  `referencia_id` BIGINT UNSIGNED NOT NULL,
  `porcentaje_descuento` DECIMAL(5,2) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_promociones_reglas_local` FOREIGN KEY (`local_id`) REFERENCES `local` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


@section('scripts')
<script>
$(document).ready(function() {
    const TASA = parseFloat($('#tasa_referencial').val()) || 1;

    let detalleVentas = [];

    // VARIABLE GLOBAL PARA PRESERVAR SUBTOTAL
        window.subtotalSinDescuento = 0;
        window.subtotalSinDescuentoBs = 0;
    // --- CONFIGURACIÓN SELECT2 DE LUJO CORREGIDA ---
    // 1. Renderizado visual de los elementos en el buscador desplegable
    function formatRepo(repo) {
        if (repo.loading || !repo.id) {
            return repo.text;
        }
        
        let data = repo.element ? repo.element.dataset : {};
        let precioBCV = parseFloat(data.bcv) || 0;
        let precioBS = parseFloat(data.bs) || 0;
        let stock = parseInt(data.stock) || 0;

        let enOferta = data.enOferta === "1" || data.enOferta === 1;
        let precioOferta = parseFloat(data.precioOferta) || 0;
        let porcentajeDescuento = parseFloat(data.porcentajeDescuento) || 0;

        let preciosHtml = '';
        if (enOferta && porcentajeDescuento > 0) {
            preciosHtml = `
                <span class='badge' style='background-color: #28a745; color: white; padding: 5px 8px;'>Venta BCV: <del>$${precioBCV.toFixed(2)}</del> <strong>$${precioOferta.toFixed(2)}</strong></span>
                <span class='badge' style='background-color: #ffc107; color: #212529; padding: 5px 8px; font-weight: bold;'>
                    <i class="fa fa-tag"></i> Promo -${porcentajeDescuento}% 
                </span>
                <span class='badge' style='background-color: #007bff; color: white; padding: 5px 8px;'>Venta BS: ${precioBS.toFixed(2)} Bs</span>
            `;
        } else {
            preciosHtml = `
                <span class='badge' style='background-color: #28a745; color: white; padding: 5px 8px;'>Venta BCV: $${precioBCV.toFixed(2)}</span>
                <span class='badge' style='background-color: #007bff; color: white; padding: 5px 8px;'>Venta BS: ${precioBS.toFixed(2)} Bs</span>
            `;
        }

        var $container = $(
            `<div class='select2-result-repository clearfix' style='${stock <= 0 ? "opacity: 0.6;" : ""}'>
                <div class='select2-result-repository__meta'>
                    <div class='d-flex justify-content-between'>
                        <span class='select2-result-repository__title' style='font-weight: bold; color: #333; font-size: 15px;'></span>
                        <small class='text-muted'>${data.serial || ''}</small>
                    </div>
                    <div class='select2-result-repository__description' style='font-size: 11px; color: #777; margin-bottom: 5px; line-height: 1.2;'></div>
                    <div class='d-flex justify-content-start flex-wrap' style='gap: 5px;'>
                        ${preciosHtml}
                        <span class='badge ${stock > 0 ? 'badge-dark' : 'badge-danger'}' style='padding: 5px 8px;'>
                            📦 Stock: ${stock}
                        </span>
                    </div>
                </div>
            </div>`
        );

        $container.find(".select2-result-repository__title").text(repo.text);
        $container.find(".select2-result-repository__description").text(data.descripcion || 'Sin descripción adicional');
        
        return $container;
    }

    // 2. Texto que se muestra en el input al seleccionar el producto
    function formatRepoSelection(repo) {
        return repo.text;
    }

    // 3. Matcher personalizado para buscar por producto, descripción o serial de forma nativa
    function matchCustom(params, data) {
        if ($.trim(params.term) === '') {
            return data;
        }

        if (!data.element) {
            return null;
        }

        let term = params.term.toLowerCase();
        let dataset = data.element.dataset;
        let producto = (dataset.producto || data.text).toLowerCase();
        let descripcion = (dataset.descripcion || '').toLowerCase();
        let serial = (dataset.serial || '').toLowerCase();

        if (producto.indexOf(term) > -1 || descripcion.indexOf(term) > -1 || serial.indexOf(term) > -1) {
            return data;
        }

        return null;
    }

    $('#buscador_insumos').select2({
        theme: 'bootstrap4',
        templateResult: formatRepo, 
        templateSelection: formatRepoSelection,
        matcher: matchCustom,
        width: '100%',
        escapeMarkup: function(m) { return m; } 
    });

    // 5. Listener de cambios de descuento global
    $('#porcentaje_descuento').on('change', function() {
        if (window.subtotalSinDescuento > 0) {
            actualizarTotales(window.subtotalSinDescuento);
        }
    });
    // Inicialización del Select2
    $('.select2-custom').select2({
        theme: 'bootstrap4',
        templateResult: formatRepo, 
        templateSelection: formatRepoSelection,
        width: '100%',
        escapeMarkup: function(m) { return m; } 
    });
    // Evento al cambiar el porcentaje de descuento
    $('#porcentaje_descuento').on('change', function() {
        if (window.subtotalSinDescuento > 0) {
            // Recalcular totales con el nuevo porcentaje
            actualizarTotales(window.subtotalSinDescuento);
        }
    });

    /*if ($('#buscador_insumos').data('select2')) {
        $('#buscador_insumos').data('select2').options.set('matcher', function(params, data) {
            if ($.trim(params.term) === '') {
                return data;
            }

            if (!data.element) {
                return null;
            }

            let term = params.term.toLowerCase();
            let dataset = data.element.dataset;

            // Compara contra producto (o texto visible) Y contra la descripción o serial
            let producto = (dataset.producto || data.text).toLowerCase();
            let descripcion = (dataset.descripcion || '').toLowerCase();
            let serial = (dataset.serial || '').toLowerCase();

            if (producto.indexOf(term) > -1 || descripcion.indexOf(term) > -1 || serial.indexOf(term) > -1) {
                return data;
            }

            return null;
        });
    }*/
    $('#buscador_insumos').select2({
        theme: 'bootstrap4',
        templateResult: formatRepo, 
        templateSelection: formatRepoSelection,
        matcher: matchCustom,
        width: '100%',
        escapeMarkup: function(m) { return m; } 
    });
    // --- LÓGICA DE TABLA ---
    $('#buscador_insumos').on('select2:select', function (e) {
            let data = e.params.data.element.dataset;
            let id = $(this).val();
            let producto = data.producto || e.params.data.text.trim();
            let descripcion = data.descripcion || '';
            
            let precioBaseBcv = parseFloat(data.bcv) || 0;
            let precioBs = parseFloat(data.bs) || 0;
            let stock = parseInt(data.stock) || 0;

            // Lectura limpia de atributos con guion
            let enOferta = data.enOferta === "1" || data.enOferta === 1;
            let precioOferta = parseFloat(data.precioOferta) || 0;
            let promocionReglaId = data.promocionId ? parseInt(data.promocionId) : null;
            let porcentajeDescuento = parseFloat(data.porcentajeDescuento) || 0;
            
            // Asignar precio de oferta si está activo
            let precio_bcv = (enOferta && precioOferta > 0) ? precioOferta : precioBaseBcv;

            if (stock <= 0) {
                Swal.fire('Sin Stock', 'No hay existencias de este producto', 'error');
                $(this).val(null).trigger('change');
                return;
            }

            let existe = detalleVentas.find(item => item.id === id);
            if (existe) {
                if (existe.cantidad + 1 > stock) {
                    Swal.fire('Límite de Stock', 'No puedes agregar más de lo disponible', 'warning');
                    $(this).val(null).trigger('change');
                    return;
                }
                existe.cantidad++;
            } else {
                detalleVentas.push({ 
                    id, 
                    producto, 
                    descripcion, 
                    precio_bcv, 
                    precio_bs, 
                    cantidad: 1, 
                    stock,
                    promocion_regla_id: promocionReglaId,
                    porcentaje_descuento_aplicado: enOferta ? porcentajeDescuento : 0
                });
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

            let descHtml = item.descripcion ? `<small class="d-block text-muted" style="font-size: 11px;">${item.descripcion}</small>` : '';

            // Si el producto tiene una promoción aplicada, mostramos una etiqueta sutil de descuento aplicado
            let badgePromoItem = (item.porcentaje_descuento_aplicado > 0) 
                ? `<span class="badge badge-warning" style="font-size: 9px;">Promo -${item.porcentaje_descuento_aplicado}%</span>` 
                : '';

            html += `<tr>
                <td data-label="Producto">
                    <strong>${item.producto}</strong> ${badgePromoItem}
                    ${descHtml}
                </td>
                <td data-label="Cant.">
                    <input type="number" class="form-control change-cant" 
                        data-index="${index}" value="${item.cantidad}" min="1" max="${item.stock}">
                </td>
                <td data-label="Precio">$${item.precio_bcv.toFixed(2)}</td>
                <td data-label="Subtotal" class="font-weight-bold">$${subtotal.toFixed(2)}</td>
                <td data-label="Acción">
                    <button type="button" class="btn btn-sm btn-danger remove-item" data-index="${index}">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
                <input type="hidden" name="articulos[${index}][id_insumo]" value="${item.id}">
                <input type="hidden" name="articulos[${index}][cantidad]" value="${item.cantidad}">
                <input type="hidden" name="articulos[${index}][precio_unitario]" value="${item.precio_bcv}">
                <input type="hidden" name="articulos[${index}][promocion_regla_id]" value="${item.promocion_regla_id ?? ''}">
            </tr>`;
        });

        if (detalleVentas.length === 0) {
            html = '<tr><td colspan="5" class="text-center text-muted">El carrito está vacío</td></tr>';
        }
        
        $('#tabla-ventas tbody').html(html);
        actualizarTotales(totalUSD);
    }

    function actualizarTotales(subtotalUSD) {
        // GUARDAR SUBTOTALES ORIGINALES
        window.subtotalSinDescuento = subtotalUSD;
        let subtotalBS = subtotalUSD * TASA;
        window.subtotalSinDescuentoBs = subtotalBS;

        // Determinar condiciones de descuento
        const ofertaGlobalActiva = {{ $ofertasActivas ? 'true' : 'false' }};
        const porcentajeDescuento = parseFloat($('#porcentaje_descuento').val()) || 0;
        
        let totalUSD = subtotalUSD;
        let totalBS = subtotalBS;
        let montoDescuentoUSD = 0;
        let montoDescuentoBS = 0;
        
        // Verificar si hay pago en divisas
        const hayPagoDivisas = (parseFloat($('input[name="pago_usd_efectivo"]').val()) || 0) > 0 
                            || (parseFloat($('input[name="pago_zelle_usd"]').val()) || 0) > 0;
        
        // APLICAR DESCUENTO SI CORRESPONDE
        if (porcentajeDescuento > 0 && (ofertaGlobalActiva || hayPagoDivisas)) {
            montoDescuentoUSD = subtotalUSD * (porcentajeDescuento / 100);
            montoDescuentoBS = subtotalBS * (porcentajeDescuento / 100);
            
            totalUSD = subtotalUSD - montoDescuentoUSD;
            totalBS = subtotalBS - montoDescuentoBS;
        }
        
        // VISUALIZACIÓN DEL DESCUENTO
        if (montoDescuentoUSD > 0) {
            $('#antes_usd').text('$ ' + subtotalUSD.toFixed(2));
            $('#ahorro_usd').text('$ ' + montoDescuentoUSD.toFixed(2));
            $('#contenedor_referencia_original').slideDown(200);
            $('#total_final_usd').addClass('text-success');
        } else {
            $('#contenedor_referencia_original').slideUp(200);
            $('#total_final_usd').removeClass('text-success');
        }
        
        // DISPLAY PRINCIPAL (siempre muestra el total con descuento aplicado)
        $('#total_final_usd').text(`$ ${totalUSD.toFixed(2)}`);
        $('#total_final_bs').text(`${totalBS.toFixed(2)} Bs`);
        
        // INPUTS HIDDEN PARA BACKEND
        if ($('#total_hidden').length === 0) {
            $('#venta-form').append(`<input type="hidden" name="total_usd" id="total_hidden">`);
        }
        $('#total_hidden').val(totalUSD.toFixed(2));

        if ($('#total_bs_hidden').length === 0) {
            $('#venta-form').append(`<input type="hidden" name="total_bs" id="total_bs_hidden">`);
        }
        $('#total_bs_hidden').val(totalBS.toFixed(2));
        
        // NUEVOS: Inputs para descuento
        if ($('#descuento_usd_hidden').length === 0) {
            $('#venta-form').append(`<input type="hidden" name="descuento_usd" id="descuento_usd_hidden">`);
        }
        $('#descuento_usd_hidden').val(montoDescuentoUSD.toFixed(2));
        
        if ($('#descuento_bs_hidden').length === 0) {
            $('#venta-form').append(`<input type="hidden" name="descuento_bs" id="descuento_bs_hidden">`);
        }
        $('#descuento_bs_hidden').val(montoDescuentoBS.toFixed(2));
        
        if ($('#porcentaje_descuento_hidden').length === 0) {
            $('#venta-form').append(`<input type="hidden" name="porcentaje_descuento" id="porcentaje_descuento_hidden">`);
        }
        $('#porcentaje_descuento_hidden').val(porcentajeDescuento);

        // Reset de pagos si carrito vacío
        if (totalUSD <= 0) {
            $('.monto-pago').val('');
            $('input[name^="referencia_"]').val('');
        }

        // Disparar validación de pagos con el nuevo total
        actualizarCalculoPagos();
    }

    function actualizarCalculoPagos() {
        let totalFacturaUSD = parseFloat($('#total_hidden').val()) || 0;
        const TASA = parseFloat($('#tasa_referencial').val()) || 1;
        // --- NUEVO: Si está en modo presupuesto, no evaluar faltantes ni desactivar el botón ---
            if ($('#switchPresupuesto').is(':checked')) {
                $('#btn-finalizar')
                    .prop('disabled', totalFacturaUSD <= 0)
                    .className = "btn btn-warning btn-block btn-lg mt-3 shadow font-weight-bold text-dark";
                $('#btn-finalizar').html('<i class="fa fa-file-pdf-o"></i> GENERAR PRESUPUESTO');
                return;
            }
        // Total en Bs (fuente de verdad para cálculos Bs)
        let totalFacturaBs = totalFacturaUSD * TASA;

        // Captura de inputs
        let pUSD = parseFloat($('input[name="pago_usd_efectivo"]').val()) || 0;
        let pZelle = parseFloat($('input[name="pago_zelle_usd"]').val()) || 0;
        let pBsEfec = parseFloat($('input[name="pago_bs_efectivo"]').val()) || 0;
        let pBsPunto = parseFloat($('input[name="pago_punto_bs"]').val()) || 0;
        let pBsPM = parseFloat($('input[name="pago_pagomovil_bs"]').val()) || 0;
        
        let totalBsPagado = pBsEfec + pBsPunto + pBsPM;
        let totalUSDPagado = pUSD + pZelle;

        // DETECTAR INPUT ACTIVO AUTOMÁTICAMENTE
        let inputActivo = document.activeElement;
        let esInputBs = inputActivo && $(inputActivo).hasClass('monto-pago') && (
            $(inputActivo).attr('name') === 'pago_bs_efectivo' ||
            $(inputActivo).attr('name') === 'pago_punto_bs' ||
            $(inputActivo).attr('name') === 'pago_pagomovil_bs'
        );

        // Variables de control
        let checkboxAbono = $('#pago_excedente_abono');
        let deudaCliente = parseFloat($('#id_cliente option:selected').data('deuda')) || 0;
        let esCredito = $('#switchCredito').is(':checked');
        
        // Reset visual
        $('#display_restante_usd').removeClass('text-warning text-success text-danger');
        $('#display_restante_bs').removeClass('text-warning text-success text-danger');
        $('#contenedor_excedente').hide();
        $('#seccion_abono_excedente').hide();
        $('#alerta-exceso').remove();
        $('.info-deuda-pago').hide();

        // Determinar fuente de verdad según input activo
        let restanteUSD, restanteBs;
        
        if (esInputBs) {
            // 🎯 FUENTE DE VERDAD: BOLÍVARES
            let totalBsEquivalentePagado = totalBsPagado + (totalUSDPagado * TASA);
            restanteBs = totalFacturaBs - totalBsEquivalentePagado;

            if (restanteBs < -0.01) {
                restanteUSD = restanteBs / TASA;
            } else if (restanteBs > 0.01) {
                restanteUSD = restanteBs / TASA;
            } else {
                restanteUSD = 0;
                restanteBs = 0;
            }
            
        } else {
            // 🎯 FUENTE DE VERDAD: DÓLARES (default)
            restanteUSD = totalFacturaUSD - totalUSDPagado - (totalBsPagado / TASA);
            
            if (restanteUSD > 0.01) {
                restanteBs = restanteUSD * TASA;
            } else if (restanteUSD < -0.01) {
                restanteBs = restanteUSD * TASA;
            } else {
                restanteUSD = 0;
                restanteBs = 0;
            }
        }

        // Redondeo SOLO para decisión lógica, NO para display de Bs
        let diffUSD = Math.round(restanteUSD * 100) / 100;
        let diffBs = Math.round(restanteBs * 100) / 100;

        // Evaluación de casos
        if (diffUSD > 0.01 || diffBs > 0.01) {
            // ❌ CASO: FALTANTE
            
            $('#seccion_abono_excedente').hide();
            
            // Display USD
            $('#display_restante_usd')
                .text(`$ ${Math.abs(restanteUSD).toFixed(2)}`)
                .addClass('text-danger');
            
            // Display Bs - PRESERVAMOS CÁLCULO EXACTO
            let displayBs = Math.abs(restanteBs);
            $('#display_restante_bs')
                .text(`${displayBs.toFixed(2)} Bs`)
                .addClass('text-danger');
            
            if (esCredito) {
                $('#monto_credito_usd').val(restanteUSD.toFixed(2));
                $('#label_monto_credito').text(restanteUSD.toFixed(2));
                validarLimiteCredito(restanteUSD);
            } else {
                $('#btn-finalizar')
                    .prop('disabled', true)
                    .html('<i class="fa fa-times-circle"></i> FALTANTE');
            }

        } else if (diffUSD < -0.01 || diffBs < -0.01) {
            // ⚠️ CASO: EXCESO
            
            let excesoUSD = Math.abs(restanteUSD);
            
            $('#display_restante_usd').text("$ 0.00").removeClass('text-danger');
            $('#display_restante_bs').text("0.00 Bs").removeClass('text-danger');
            $('#contenedor_excedente').show();
            $('#display_excedente_usd').text(`$ ${excesoUSD.toFixed(2)}`);

            if (deudaCliente > 0) {
                // NUEVO: Validar si excedente supera la deuda
        if (excesoUSD > deudaCliente) {
            // Excedente mayor que deuda - BLOQUEAR
            $('#seccion_abono_excedente').hide();
            $('#btn-finalizar')
                .prop('disabled', true)
                .html('<i class="fa fa-ban"></i> EXCEDENTE SOBRE DEUDA');
            
            // Opcional: Mostrar alerta visual
            Swal.fire({
                icon: 'warning',
                title: 'Excedente inválido',
                text: `El excedente ($${excesoUSD.toFixed(2)}) supera la deuda ($${deudaCliente.toFixed(2)})`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        } else {
            // Excedente menor o igual a deuda - PERMITIR ABONO
            $('#seccion_abono_excedente').fadeIn();
            $('#monto_a_abonar').text(excesoUSD.toFixed(2));
            
            if (checkboxAbono.is(':checked')) {
                $('#btn-finalizar')
                    .prop('disabled', false)
                    .html('<i class="fa fa-check-circle"></i> FINALIZAR (+ ABONO)');
            } else {
                $('#btn-finalizar')
                    .prop('disabled', true)
                    .html('<i class="fa fa-hand-paper"></i> ¿ES ABONO?');
            }
        }
            } else {
                // sin deuda no hay abono
                $('#btn-finalizar')
                    .prop('disabled', true)
                    .html('<i class="fa fa-exclamation-triangle"></i> EXCESO');
            }

        } else {
            // ✅ CASO: PAGO EXACTO
            
            $('#display_restante_usd').text('$ 0.00').removeClass('text-danger');
            $('#display_restante_bs').text('0.00 Bs').removeClass('text-danger');
            $('#btn-finalizar')
                .prop('disabled', false)
                .html('<i class="fa fa-check-circle"></i> FINALIZAR VENTA');
        }

        // Aviso de deuda persistente
        if (deudaCliente > 0) {
            $('#aviso_deuda_cliente')
                .html(`<i class="fa fa-info-circle"></i> Deuda pendiente: $${deudaCliente.toFixed(2)}`)
                .show();
        } else {
            $('#aviso_deuda_cliente').hide();
        }
    }

    function validarLimiteCredito(monto) {
        let clienteSeleccionado = $('#id_cliente option:selected');
        let limite = parseFloat(clienteSeleccionado.data('limite')) || 0;
        let pinAutorizado = $('#pin_autorizacion').val(); // Campo donde guardaremos el éxito del PIN

        // Si el monto es mayor al límite Y no hay un PIN de autorización previo
        if (monto > limite && !pinAutorizado) {
            $('#error_limite')
                .html(`<i class="fa fa-exclamation-circle"></i> Límite excedido (Máx: $${limite.toFixed(2)})`)
                .show();
            
            $('#btn-finalizar')
                .prop('disabled', true)
                .html('<i class="fa fa-lock"></i> REQUIERE AUTORIZACIÓN');
        } else {
            // Si está dentro del límite o ya fue autorizado con PIN
            $('#error_limite').hide();
            
            // Solo habilitamos si el monto es mayor a 0 (no tiene sentido un crédito de $0)
            if (monto > 0) {
                $('#btn-finalizar')
                    .prop('disabled', false)
                    .html('<i class="fa fa-check-circle"></i> FINALIZAR CRÉDITO');
            }
        }
    }

    //HANDLER DE EVENTOS
    // Actualizar al escribir montos
    $(document).on('input', '.monto-pago', actualizarCalculoPagos);

    // Eliminar producto
    $(document).on('click', '.remove-item', function() {
        detalleVentas.splice($(this).data('index'), 1);
        renderTabla();
        
    });

    // Cambio de cantidad
    $(document).on('change', '.change-cant', function() {
        let index = $(this).data('index');
        let val = parseInt($(this).val());
        let stock = detalleVentas[index].stock;

        if (val > stock) {
            Swal.fire('Stock insuficiente', `Solo hay ${stock} disponibles`, 'warning');
            val = stock;
        }
        detalleVentas[index].cantidad = val || 1;
        renderTabla();
    });

    // ==========================================
        // 1. EVENTO GLOBAL: Cambio de Tipo Documento
        // (Debe estar AFUERA del evento submit)
        // ==========================================
        $(document).on('change', 'input[name="tipo_documento"]', function() {
            let valorSeleccionado = $(this).val();
            
            // Actualizamos o creamos el input hidden que realmente leerá el backend
            if ($('#tipo_documento_real').length === 0) {
                $('#venta-form').append('<input type="hidden" name="tipo_documento" id="tipo_documento_real">');
            }
            $('#tipo_documento_real').val(valorSeleccionado);

            // Clases de Bootstrap
            $('input[name="tipo_documento"]').closest('label').removeClass('active');
            $(this).closest('label').addClass('active');
        });

        // ==========================================
        // 2. SUBMIT UNIFICADO DEL FORMULARIO DE VENTAS
        // ==========================================
        $('#venta-form').on('submit', function(e) {

            // 1. VERIFICAR SI ESTÁ ACTIVO EL MODO PRESUPUESTO / COTIZACIÓN
            
            if ($('#switchPresupuesto').is(':checked')) {
                
                // Configuramos la acción hacia la ruta del presupuesto y abrimos en pestaña nueva
                $(this).attr('action', '{{ route("ventas.presupuesto") }}'); 
                $(this).attr('target', '_blank');
                
                // Como la pestaña nueva no recarga esta página, restauramos el botón tras 1.2 segundos para evitar el "Procesando..." eterno
                setTimeout(function() {
                    const $btn = $('#btn-finalizar'); 
                    $btn.prop('disabled', false);
                    $btn.html('<i class="fa fa-file-pdf-o"></i> GENERAR PRESUPUESTO'); 
                }, 1200);

                return true; // Permite que el formulario se envíe de forma nativa para generar el PDF
            }

            // 2. FLUJO NORMAL DE VENTA (Si el switch NO está marcado)
            $(this).attr('action', '{{ route("ventas.store") }}'); 
            $(this).removeAttr('target');

            e.preventDefault();
            e.stopImmediatePropagation();

            // --- (A partir de aquí sigue todo tu código normal de validación y el modal de pago) ---
            if (typeof detalleVentas === 'undefined' || detalleVentas.length === 0) {
                Swal.fire('Carrito Vacío', 'Debes agregar al menos un producto.', 'error');
                return false;
            }
            
            // ... (todo el resto de tu código de montos, referencias y modal) ...
            
            $('#modalConfirmarVenta').modal('show');
        });

   
    // --- NUEVO: FUNCIÓN PARA SOLICITAR REFERENCIAS ---
    function solicitarReferencia(metodo) {
    const mapeo = {
        'pago_pagomovil_bs': { 
            t: 'Referencia Pago Móvil', 
            id: 'referencia_pagomovil',
            btnClass: 'btn-info',
            icon: 'fa-university'
        },
        'pago_zelle_usd': { 
            t: 'Confirmación Zelle', 
            id: 'referencia_zelle',
            btnClass: 'btn-warning',
            icon: 'fa-key'
        }
    };

    if (!mapeo[metodo]) return;

    // Crear input hidden si no existe (CRÍTICO para el backend)
    if ($(`#${mapeo[metodo].id}`).length === 0) {
        $('#venta-form').append(`<input type="hidden" name="${mapeo[metodo].id}" id="${mapeo[metodo].id}">`);
    }

    let valorActual = $(`#${mapeo[metodo].id}`).val();

    Swal.fire({
        title: `<span class="swal-title-mobile">${mapeo[metodo].t}</span>`,
        input: 'text',
        inputLabel: 'Ingrese el número de comprobante',
        inputPlaceholder: 'Ej: 123456',
        inputValue: valorActual,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        heightAuto: false,
        inputAttributes: {
            autocapitalize: 'off',
            autocorrect: 'off',
            autocomplete: 'off'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Guardar valor
            $(`#${mapeo[metodo].id}`).val(result.value);
            
            // Feedback visual en input y botón
            let $input = $(`input[name="${metodo}"]`);
            let $boton = $input.closest('.input-group').find('button');
            
            if (result.value.trim() !== "") {
                $input.addClass('is-valid');
                $boton.removeClass('btn-warning btn-primary btn-info').addClass('btn-success');
                $boton.html('<i class="fa fa-check"></i>');
                
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Referencia guardada',
                    showConfirmButton: false,
                    timer: 1500
                });
            } else {
                // Valor vacío: resetear
                $input.removeClass('is-valid');
                resetBotonEstado(metodo, $boton, mapeo[metodo]);
            }
        }
        // Si cancela, no hacemos nada (mantiene valor anterior)
    });
}
window.solicitarReferencia = solicitarReferencia;
function resetBotonEstado(metodo, $boton, config) {
    $boton.removeClass('btn-success').addClass(config.btnClass);
    $boton.html(`<i class="fa ${config.icon}"></i>`);
}

 /// ==========================================
    // 3. ENVÍO FINAL DESDE EL MODAL
    // ==========================================
    $(document).on('click', '#btnProcesarVentaFinal', function() {
        // 1. Obtener el correlativo sugerido del DOM
        let correlativo = $('#correlativo_nota').val() || '';

        // 2. Asegurar que el input 'correlativo_nota' esté dentro de #venta-form
        if ($('#venta-form input[name="correlativo_nota"]').length === 0) {
            $('#venta-form').append(`<input type="hidden" name="correlativo_nota" value="${correlativo}">`);
        } else {
            $('#venta-form input[name="correlativo_nota"]').val(correlativo);
        }
        // Capturar el tipo de documento seleccionado en ese instante
        let tipoDoc = $('input[name="tipo_documento"]:checked').val() || $('#tipo_documento_hidden').val() || 'nota_entrega';

        // Asegurar que exista un input con name="tipo_documento" que Laravel pueda leer
        if ($('#tipo_documento_real').length === 0) {
            $('#venta-form').append(`<input type="hidden" name="tipo_documento" id="tipo_documento_real" value="${tipoDoc}">`);
        } else {
            $('#tipo_documento_real').val(tipoDoc);
        }

        // Deshabilitar botones para evitar doble submit
        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Procesando...');
        $('#btn-finalizar').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Procesando...');

        // Enviar formulario al controlador
        document.getElementById('venta-form').submit();
    });

    //validando el campo identificación
    $(document).ready(function() {
        const $input = $('input[name="identificacion"]');

        // Formatear valor inicial si viene de old() o de la base de datos
        let inicial = $input.val().replace(/\D/g, '').substring(0, 9);
        if (inicial.length > 0) {
            $input.val('V-' + inicial);
        }

        $input.on('input', function() {
            // Extraer únicamente los dígitos numéricos y limitar a 9 números
            let numeros = $(this).val().replace(/\D/g, '').substring(0, 9);

            // Al escribir cualquier número, fija el prefijo V-
            if (numeros.length > 0) {
                $(this).val('V-' + numeros);
            } else {
                // Si borra todos los números, el prefijo permanece
                $(this).val('V-');
            }
        });

        // Bloquear que el cursor se posicione antes del prefijo 'V-'
        $input.on('keydown click focus select', function() {
            if (this.selectionStart < 2) {
                this.setSelectionRange(2, 2);
            }
        });

        // Evitar borrar el prefijo 'V-' con Backspace o Delete
        $input.on('keydown', function(e) {
            if ((e.key === 'Backspace' || e.key === 'Delete') && this.selectionStart <= 2 && this.selectionEnd <= 2) {
                e.preventDefault();
            }
        });
    });

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
                    $(newOption).attr('data-deuda', 0);
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
        let btnFinalizar = $('#btn-finalizar'); // ID unificado
        
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
                    allowOutsideClick: false 
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
                                    }).done(res => {
                                        // CAMBIO 1: Guardamos el PIN validado para que 
                                        // validarLimiteCredito() sepa que ya está autorizado.
                                        $('#pin_autorizacion').val(pin); 
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
                                    // Caso: Canceló al meter el PIN
                                    $('#seccion_credito').hide();
                                    checkbox.prop('checked', false);
                                    $('#pin_autorizacion').val(''); // Limpiamos por seguridad
                                    actualizarCalculoPagos();
                                }
                            });
                        });
                    } else {
                        // Caso: Canceló el envío del PIN
                        $('#seccion_credito').hide();
                        checkbox.prop('checked', false);
                        actualizarCalculoPagos();
                    }
                });
            @else
                $('#seccion_credito').fadeIn();
                actualizarCalculoPagos();
            @endcannot
        } else {
            // CAMBIO 2: Limpiar el PIN si el usuario desmarca el crédito
            $('#pin_autorizacion').val(''); 
            $('#seccion_credito').fadeOut();
            actualizarCalculoPagos();
        }
    });

    $(document).on('input', 'input[name="pago_usd_efectivo"], input[name="pago_zelle_usd"]', function() {
        gestionarEstadoDescuento();
    });

        function gestionarEstadoDescuento() {
            const ofertaGlobalActiva = {{ $ofertasActivas ? 'true' : 'false' }};
            if (ofertaGlobalActiva) return; 

            let dolares = parseFloat($('input[name="pago_usd_efectivo"]').val()) || 0;
            let zelle = parseFloat($('input[name="pago_zelle_usd"]').val()) || 0;
            
            const $select = $('#porcentaje_descuento');
            const $contenedor = $('#contenedor_descuento');
            const $info = $('#info-descuento');
            const teniaDescuento = parseFloat($select.val()) > 0;

            if (dolares > 0 || zelle > 0) {
                $select.prop('disabled', false);
                $contenedor.css({
                    'background-color': '#fff9db',
                    'border-color': '#ffe066'
                });
                $info.html('<i class="fa fa-unlock text-warning"></i> Descuento habilitado por pago en divisas.');
            } else {
                // Si se quitan los dólares, resetear descuento
                $select.prop('disabled', true).val(0);
                $contenedor.css({
                    'background-color': '#f8f9fa',
                    'border-color': '#dee2e6'
                });
                $info.text('* Se habilitará automáticamente al ingresar Dólares o Zelle.');
                
                // RECALCULAR SIN DESCUENTO si había uno aplicado
                if (teniaDescuento && window.subtotalSinDescuento > 0) {
                    actualizarTotales(window.subtotalSinDescuento);
                }
            }
        }

        function visualizarComparativaDescuento(subtotalSinDescuento, totalConDescuento) {
            const ahorro = subtotalSinDescuento - totalConDescuento;
            
            if (ahorro > 0) {
                // Mostramos la referencia de lo que costaba antes
                $('#antes_usd').text('$ ' + subtotalSinDescuento.toFixed(2));
                $('#ahorro_usd').text('$ ' + ahorro.toFixed(2));
                
                $('#contenedor_referencia_original').slideDown(200);
                
                // Opcional: Resaltamos el total_final_usd para que se note el cambio
                $('#total_final_usd').addClass('text-success');
            } else {
                // Si no hay ahorro, escondemos la sección y volvemos al estado base
                $('#contenedor_referencia_original').slideUp(200);
                $('#total_final_usd').removeClass('text-success');
            }
        }


        $('#id_cliente').on('change', function() {
            let limite = $('option:selected', this).data('limite') || 0;
            let deuda = $('option:selected', this).data('deuda') || 0;
            
            $('#cliente_limite').text(parseFloat(limite).toFixed(2));
            
            if (deuda > 0) {
                $('#cliente_deuda').text(parseFloat(deuda).toFixed(2));
                $('#cliente_deuda_container').show();
            } else {
                $('#cliente_deuda_container').hide();
            }
            
            $('#info_cliente').show();
        });

        $(document).on('change', '#pago_excedente_abono', function() {
            actualizarCalculoPagos();
        });

        @if(session('imprimir_documento'))
          $(document).ready(function() {
            const docData = @json(session('imprimir_documento'));

            // 1. Asignar tipo (Ej: Nota de Entrega) y código (Ej: NE-000048)
            $('#modal_titulo_doc').text(docData.tipo + ' Generada:');
            $('#modal_codigo_doc').text(docData.codigo);
            
            // 2. Construir la URL hacia la función show del controlador
            // Usa route() si tu ruta se llama 'ventas.show', o url() directa:
            const urlShow = "{{ url('ventas') }}/" + docData.venta_id;
            
            // Asignamos la URL y forzamos que abra en una pestaña nueva
            $('#btn_confirmar_impresion')
              .attr('href', urlShow)
              .attr('target', '_blank');

            // 3. Desplegar el modal automáticamente
            $('#modalImprimirDocumento').modal('show');

            // 4. Al hacer clic en Imprimir, ocultar el modal tras 1 segundo
            $('#btn_confirmar_impresion').on('click', function() {
              setTimeout(function() {
                $('#modalImprimirDocumento').modal('hide');
              }, 1000);
            });
          });
        @endif

       // ==========================================
       // INTERRUPTOR: MODO PRESUPUESTO / VENTA
       // ==========================================
       $('#switchPresupuesto').on('change', function() {
           const $form = $('#venta-form');
           const $btn = $('#btn-finalizar');
           const $paneles = $('.panel-bloqueable');
           let totalUSD = parseFloat($('#total_hidden').val()) || 0;

           if ($(this).is(':checked')) {
               // MODO PRESUPUESTO ACTIVADO
               $form.attr('action', "{{ route('ventas.presupuesto') }}"); 
               $form.attr('target', "_blank"); // Abre el PDF en una pestaña nueva
               
               // Estética y estado del botón
               $btn.attr('class', 'btn btn-warning btn-block btn-lg mt-3 shadow font-weight-bold text-dark');
               $btn.html('<i class="fa fa-file-pdf-o"></i> GENERAR PRESUPUESTO');
               
               // Se habilita si hay items en la tabla
               if (typeof detalleVentas !== 'undefined' && detalleVentas.length > 0) {
                   $btn.prop('disabled', false);
               } else {
                   $btn.prop('disabled', true);
               }

               // Atenuar visualmente y deshabilitar controles de pago
               $paneles.css({
                   'opacity': '0.3',
                   'pointer-events': 'none'
               });
               $paneles.find('input, select, button').prop('disabled', true);

               // Desactivar también switch de crédito si estuviera activo
               if ($('#switchCredito').is(':checked')) {
                   $('#switchCredito').prop('checked', false).trigger('change');
               }

           } else {
               // MODO VENTA NORMAL
               $form.attr('action', "{{ route('ventas.store') }}");
               $form.removeAttr('target');
               
               // Restaurar aspecto visual del botón
               $btn.attr('class', 'btn btn-success btn-block btn-lg mt-3 shadow');
               $btn.html('<i class="fa fa-check-circle"></i> FINALIZAR VENTA');
               
               // Restaurar paneles
               $paneles.css({
                   'opacity': '1',
                   'pointer-events': 'auto'
               });
               
               // Reactivar campos según oferta activa
               const ofertaGlobalActiva = {{ $ofertasActivas ? 'true' : 'false' }};
               $paneles.find('input, select, button').each(function() {
                   if (this.id === 'porcentaje_descuento' && !ofertaGlobalActiva) {
                       return; // Mantener deshabilitado si no hay oferta activa
                   }
                   $(this).prop('disabled', false);
               });
               
               // Recalcular montos/pagos normales
               actualizarCalculoPagos();
           }
       });
});
</script>
@endsection