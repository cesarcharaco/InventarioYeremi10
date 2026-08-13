<div class="modal fade" id="modalInsumoRapido" tabindex="-1" role="dialog" aria-labelledby="modalInsumoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalInsumoLabel">
                    <i class="fa fa-cubes"></i> Registrar Nuevo Insumo / Producto
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <form id="formInsumoRapido">
                @csrf
                <div class="modal-body">
                    <div id="modal_insumo_errors" class="alert alert-danger d-none"></div>

                    <div class="row">
                        <!-- Nombre del producto -->
                        <div class="col-md-8 form-group">
                            <label class="font-weight-bold">Nombre del Insumo/Producto <span class="text-danger">*</span></label>
                            <input type="text" name="producto" id="modal_producto" class="form-control" placeholder="Ej: Cadena 428H-120" required>
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Serial / Código de Barra</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-barcode"></i></span>
                                </div>
                                <input type="text" 
                                       name="serial" 
                                       id="modal_serial" 
                                       class="form-control" 
                                       placeholder="Escanear o dejar vacío para autogenerar">
                            </div>
                            <small class="form-text text-muted">
                                <i class="fa fa-info-circle text-primary"></i> Si lo dejas en blanco, se creará según la categoría.
                            </small>
                        </div>
                        <div class="col-md-12 form-group">
                            <label class="font-weight-bold">Descripción del Producto</label>
                            <textarea name="descripcion" id="modal_descripcion" class="form-control" rows="2" placeholder="Detalles del producto, especificaciones, observaciones..."></textarea>
                        </div>
                        <!-- Categoría -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Categoría <span class="text-danger">*</span></label>
                            <select name="categoria_id" id="modal_categoria_id" class="form-control" required>
                                <option value="">Seleccione categoría...</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->categoria }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Unidad / Modelo de Venta -->
                        <!-- Cargar data-attributes en el select del modal -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Modelo de Venta / Unidad <span class="text-danger">*</span></label>
                            <select name="modelo_venta_id" id="modal_modelo_venta_id" class="form-control" required>
                                <option value="">-- Seleccione un modelo --</option>
                                @foreach($modelosVenta as $m)
                                    <option value="{{ $m->id }}"
                                            data-factor-bcv="{{ $m->factor_bcv }}" 
                                            data-factor-usdt="{{ $m->factor_usdt }}" 
                                            data-extra="{{ $m->porcentaje_extra }}"
                                            data-bcv="{{ $m->tasa_bcv }}"
                                            data-binance="{{ $m->tasa_binance }}">
                                        {{ $m->modelo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Input de Costo -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Costo del Insumo (USD $) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="costo" id="modal_costo_input" class="form-control" placeholder="0.00" required>
                        </div>

                        <!-- Bloque de Precios Calculados Dinámicamente -->
                        <div class="col-md-12">
                            <div class="row bg-light py-2 border rounded shadow-sm mb-3">
                                <div class="col-md-4 form-group mb-0">
                                    <label class="text-primary font-weight-bold small">Venta USD ($)</label>
                                    <input type="text" name="precio_venta_usd" id="modal_res_usd" class="form-control font-weight-bold" readonly style="background-color: #e9ecef;">
                                </div>
                                <div class="col-md-4 form-group mb-0">
                                    <label class="text-success font-weight-bold small">Venta BS (BCV)</label>
                                    <input type="text" name="precio_venta_bs" id="modal_res_bs" class="form-control" readonly style="background-color: #e9ecef;">
                                </div>
                                <div class="col-md-4 form-group mb-0">
                                    <label class="text-warning font-weight-bold small">Venta USDT (Binance)</label>
                                    <input type="text" name="precio_venta_usdt" id="modal_res_usdt" class="form-control" readonly style="background-color: #e9ecef;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Campo Oculto: ID del Local Actual de la venta -->
                    <input type="hidden" name="id_local" value="{{ $local->id }}">

                    <!-- Fila para la Cantidad Inicial asignada al Local Actual -->
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Ubicación Actual</label>
                            <input type="text" class="form-control" value="{{ $local->nombre }}" readonly style="background-color: #e9ecef;">
                        </div>
                        
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Cantidad / Stock Inicial <span class="text-danger">*</span></label>
                            <input type="number" name="cantidad" id="modal_cantidad" class="form-control" value="1" min="1" required>
                            <small class="form-text text-muted">
                                <i class="fa fa-info-circle text-primary"></i> Se asignará directamente a {{ $local->nombre }}.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fa fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-success" id="btnGuardarInsumoRapido">
                            <i class="fa fa-save"></i> Guardar y Seleccionar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    // Se espera a que la página y jQuery estén cargados antes de usar "$"
    document.addEventListener('DOMContentLoaded', function() {

        // --- CÁLCULO LOCAL EN TIEMPO REAL ---
        const $modalSelModelo = $('#modal_modelo_venta_id');
        const $modalInCosto   = $('#modal_costo_input');
        const $modalResUsd    = $('#modal_res_usd');
        const $modalResBs     = $('#modal_res_bs');
        const $modalResUsdt   = $('#modal_res_usdt');

        function ejecutarCalculoModal() {
            const costo = parseFloat($modalInCosto.val());
            const $opcion = $modalSelModelo.find('option:selected');
            const modeloId = $modalSelModelo.val();

            if (isNaN(costo) || !modeloId || costo <= 0) {
                $modalResUsd.val(''); 
                $modalResBs.val(''); 
                $modalResUsdt.val('');
                return;
            }

            const f_bcv     = parseFloat($opcion.data('factor-bcv')) || 0;
            const f_usdt    = parseFloat($opcion.data('factor-usdt')) || 0;
            const extra     = parseFloat($opcion.data('extra')) || 0;
            const t_bcv     = parseFloat($opcion.data('bcv')) || 0;
            const t_binance = parseFloat($opcion.data('binance')) || 0;

            let vUsdBcv = 0;
            let vUsdt   = 0;

            if (f_bcv > 0) {
                const diferencial = (t_bcv > 0) ? (t_binance / t_bcv) : 1;
                vUsdBcv = (diferencial / f_bcv) * costo;
            } else if (extra > 0) {
                vUsdBcv = costo * (1 + extra);
            }

            if (f_usdt > 0) {
                vUsdt = costo / f_usdt;
            } else if (extra > 0) {
                vUsdt = costo * (1 + extra);
            } else {
                vUsdt = costo;
            }

            const vBs = vUsdBcv * t_bcv;

            $modalResUsd.val(vUsdBcv.toFixed(2));
            $modalResUsdt.val(vUsdt.toFixed(1));
            $modalResBs.val(vBs.toFixed(2));
        }

        $modalInCosto.on('input keyup change', ejecutarCalculoModal);
        $modalSelModelo.on('change', ejecutarCalculoModal);

        // --- AL GUARDAR: ENVÍO VÍA AJAX ---
        $('#formInsumoRapido').on('submit', function(e) {
            e.preventDefault();

            const $btn = $('#btnGuardarInsumoRapido');
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');
            $('#modal_insumo_errors').addClass('d-none').html('');

            $.ajax({
                url: "{{ route('insumos.store_rapido') }}",
                type: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar y Seleccionar');

                    if (response.success) {
                        const insumo = response.insumo;

                        // 1. Cerrar modal y resetear campos
                        $('#modalInsumoRapido').modal('hide');
                        $('#formInsumoRapido')[0].reset();
                        $modalResUsd.val('');
                        $modalResBs.val('');
                        $modalResUsdt.val('');

                        // 2. Alerta / Notificación elegante
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Producto Registrado!',
                                text: `El insumo "${insumo.producto}" se guardó correctamente. Ya puedes buscarlo.`,
                                timer: 3000,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            });
                        } else if (typeof toastr !== 'undefined') {
                            toastr.success(`El insumo "${insumo.producto}" fue registrado con éxito.`, '¡Guardado!');
                        } else {
                            const alertHtml = `
                                <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
                                    <i class="fa fa-check-circle"></i> Producto <strong>${insumo.producto}</strong> registrado con éxito.
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>`;
                            $('#formInsumoRapido').parent().prepend(alertHtml);
                        }
                    }
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar y Seleccionar');
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        let errorHtml = '<ul class="mb-0 pl-3">';
                        $.each(errors, function(key, value) {
                            errorHtml += '<li>' + value[0] + '</li>';
                        });
                        errorHtml += '</ul>';
                        $('#modal_insumo_errors').removeClass('d-none').html(errorHtml);
                    } else {
                        let msg = (xhr.responseJSON && xhr.responseJSON.message) 
                            ? xhr.responseJSON.message 
                            : 'Error ' + xhr.status + ': Ocurrió un fallo en el servidor.';
                        
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: msg,
                                confirmButtonColor: '#dc3545'
                            });
                        } else {
                            $('#modal_insumo_errors').removeClass('d-none').html(msg);
                        }
                    }
                }
            });
        });

    });
</script>