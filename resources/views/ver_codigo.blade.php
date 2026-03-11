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
        // Sin deuda - No puede haber abono
        $('#btn-finalizar')
            .prop('disabled', true)
            .html('<i class="fa fa-exclamation-triangle"></i> EXCESO');
    }
}