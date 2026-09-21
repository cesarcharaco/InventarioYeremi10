{{-- MODAL DE EDICIÓN DE ABONO --}}
<div class="modal fade" id="modalEditarAbono" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="" method="POST" id="formEditarAbono">
                @csrf
                @method('PUT')
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title font-weight-bold">
                        <i class="fa fa-edit"></i> Editar Abono - <span id="edit_nombre_cliente"></span>
                    </h5>
                    <button type="button" class="close text-dark" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    {{-- Encabezado interno idéntico al modal de registro --}}
                    <div class="row text-center mb-3">
                        <div class="col-md-12">
                            <h4 class="text-muted">Abono #<span class="text-warning font-weight-bold" id="edit_abono_id"></span></h4>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 border-right">
                            
                            {{-- FECHA DEL ABONO --}}
                            <div class="form-group mb-3">
                                <label class="font-weight-bold">Fecha del Abono:</label>
                                <input type="date" name="fecha_abono" id="edit_fecha_abono" class="form-control" required>
                            </div>

                            {{-- MONTO TOTAL (PROTEGIDO) --}}
                            <div class="form-group">
                                <label class="font-weight-bold text-primary">Monto Total Abonado (USD):</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                    <input type="number" step="0.01" name="monto_total_usd" id="edit_monto_total_usd" class="form-control form-control-lg" >
                                </div>
                                <small class="text-muted d-block mt-1">
                                    <i class="fa fa-lock"></i> El monto abonado no se modifica para proteger los saldos de crédito procesados.
                                </small>
                            </div>

                            <hr>

                            {{-- REFERENCIA / NOTA --}}
                            <div class="form-group">
                                <label class="font-weight-bold">Referencia / Nota:</label>
                                <textarea name="referencia" id="edit_referencia" class="form-control" rows="2" placeholder="Ej: Pago móvil, transferencia Banesco, etc."></textarea>
                            </div>

                        </div>
                        
                        {{-- DESGLOSE DE PAGO --}}
                        <div class="col-md-6">
                            <label class="font-weight-bold">Desglose de Pago (Entrada a Caja):</label>
                            <div id="error-desglose-edit" class="alert alert-danger d-none py-1 small">
                                <i class="fa fa-exclamation-circle"></i> Debe ingresar al menos un valor en el desglose.
                            </div>
                            <div class="input-group mb-2">
                                <div class="input-group-prepend"><span class="input-group-text">Efectivo $</span></div>
                                <input type="number" step="0.01" name="pago_usd_efectivo" id="edit_pago_usd_efectivo" class="form-control input-desglose-edit" value="0">
                            </div>
                            <div class="input-group mb-2">
                                <div class="input-group-prepend"><span class="input-group-text">Efectivo Bs</span></div>
                                <input type="number" step="0.01" name="pago_bs_efectivo" id="edit_pago_bs_efectivo" class="form-control input-desglose-edit" value="0">
                            </div>
                            <div class="input-group mb-2">
                                <div class="input-group-prepend"><span class="input-group-text">Punto Bs</span></div>
                                <input type="number" step="0.01" name="pago_punto_bs" id="edit_pago_punto_bs" class="form-control input-desglose-edit" value="0">
                            </div>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">P. Móvil Bs</span></div>
                                <input type="number" step="0.01" name="pago_pagomovil_bs" id="edit_pago_pagomovil_bs" class="form-control input-desglose-edit" value="0">
                            </div>
                            <p class="mt-2 small text-muted">
                                <i class="fa fa-info-circle"></i> Corrija las vías de ingreso a caja si hubo un error al registrar el pago.
                            </p>
                            {{-- WIDGET CALCULADORA BCV (EDICIÓN) --}}
                            <div class="card bg-light border-warning mt-3 shadow-sm">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="font-weight-bold text-dark small">
                                            <i class="fa fa-calculator"></i> Calculadora de Conversión
                                        </span>
                                        <span class="badge badge-warning p-1">
                                            Tasa BCV: <b>{{ number_format(bcv_rate('USD') ?? 0, 2, ',', '.') }}</b> Bs.
                                        </span>
                                    </div>
                                    <div class="form-row">
                                        <div class="col-6 mb-2">
                                            <label class="small text-muted mb-0 font-weight-bold">Monto $</label>
                                            <input type="number" step="0.01" class="form-control form-control-sm" id="edit_calc_usd" placeholder="0.00">
                                        </div>
                                        <div class="col-6 mb-2">
                                            <label class="small text-muted mb-0 font-weight-bold">Monto Bs</label>
                                            <input type="number" step="0.01" class="form-control form-control-sm" id="edit_calc_bs" placeholder="0.00">
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mb-2">Transferir monto en Bs a:</small>
                                    <div class="btn-group btn-group-sm w-100" role="group">
                                        <button type="button" class="btn btn-outline-success btn-copiar-bs-edit" data-target-id="edit_pago_bs_efectivo">
                                            Efectivo Bs
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-copiar-bs-edit" data-target-id="edit_pago_punto_bs">
                                            Punto Bs
                                        </button>
                                        <button type="button" class="btn btn-outline-info btn-copiar-bs-edit" data-target-id="edit_pago_pagomovil_bs">
                                            P. Móvil Bs
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning font-weight-bold shadow">
                        <i class="fa fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>