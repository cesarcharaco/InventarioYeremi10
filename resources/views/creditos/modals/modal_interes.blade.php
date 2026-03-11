<div class="modal fade" id="modalAplicarInteres" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title font-weight-bold"><i class="fa fa-percent"></i> Indexar Interés</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formAplicarInteres" action="" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Saldo Global Pendiente:</span>
                        <strong id="saldo_base_global" data-valor="0">$0.00</strong>
                    </div>

                    <div class="form-group">
                        <label>Porcentaje a Indexar (%)</label>
                        <input type="number" id="input_porcentaje" name="porcentaje" class="form-control form-control-lg" step="0.01" min="0.01" max="100" required placeholder="0.00">
                    </div>

                    <div class="card bg-light border-warning mb-3">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between text-muted small">
                                <span>Monto del Interés:</span>
                                <span id="preview_interes">$0.00</span>
                            </div>
                            <hr class="my-1">
                            <div class="d-flex justify-content-between font-weight-bold text-primary">
                                <span>NUEVO SALDO TOTAL:</span>
                                <span id="preview_total">$0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Observación (Opcional)</label>
                        <textarea name="observacion" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btn_confirmar_index" class="btn btn-warning font-weight-bold" disabled>Confirmar Indexación</button>
                </div>
            </form>
        </div>
    </div>
</div>