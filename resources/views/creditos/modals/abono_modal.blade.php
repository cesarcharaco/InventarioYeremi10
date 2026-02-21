{{-- MODAL DE ABONO UNIFICADO --}}
<div class="modal fade" id="modalAbono" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="" method="POST" id="formAbono">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fa fa-money"></i> Registrar Abono - <span id="nombre_cliente"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row text-center mb-3">
                        <div class="col-md-12">
                            <h4 class="text-muted">Saldo Pendiente: <span class="text-danger" id="txt_saldo_pendiente"></span></h4>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 border-right">
                            <div class="form-group">
                                <label class="font-weight-bold text-primary">Monto Total a Abonar (USD):</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                    <input type="number" step="0.01" name="monto_total_usd" id="monto_total_usd" class="form-control form-control-lg" placeholder="0.00" required>
                                </div>
                                <small class="text-muted">Este monto se descontará de la deuda principal.</small>
                            </div>
                            <hr>
                            <div class="form-group">
                                <label>Referencia / Nota:</label>
                                <textarea name="referencia" class="form-control" rows="3" placeholder="Ej: Pago móvil, transferencia Banesco, etc."></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="font-weight-bold">Desglose de Pago (Entrada a Caja):</label>
                            <div class="input-group mb-2">
                                <div class="input-group-prepend"><span class="input-group-text">Efectivo $</span></div>
                                <input type="number" step="0.01" name="pago_usd_efectivo" class="form-control" value="0">
                            </div>
                            <div class="input-group mb-2">
                                <div class="input-group-prepend"><span class="input-group-text">Efectivo Bs</span></div>
                                <input type="number" step="0.01" name="pago_bs_efectivo" class="form-control" value="0">
                            </div>
                            <div class="input-group mb-2">
                                <div class="input-group-prepend"><span class="input-group-text">Punto Bs</span></div>
                                <input type="number" step="0.01" name="pago_punto_bs" class="form-control" value="0">
                            </div>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">P. Móvil Bs</span></div>
                                <input type="number" step="0.01" name="pago_pagomovil_bs" class="form-control" value="0">
                            </div>
                            <p class="mt-2 small text-muted"><i class="fa fa-info-circle"></i> El desglose ayuda a cuadrar la caja diaria sin afectar el monto en dólares de la deuda.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success shadow">
                        <i class="fa fa-save"></i> Procesar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>