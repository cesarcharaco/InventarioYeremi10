<div class="modal fade" id="modalReembolso" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="" method="POST" id="formReembolso">
                @csrf
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fa fa-share-square-o"></i> Procesar Reembolso / Salida de Efectivo</h5>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label>Monto a Reembolsar (USD):</label>
                            <input type="number" step="0.01" name="monto_reembolso" id="input_monto_reembolso" class="form-control" required>
                            <small class="text-muted">Disponible: <span id="saldo_favor_disponible">$0.00</span></small>
                        </div>
                        <div class="col-md-6">
                            <label>Forma de Salida:</label>
                            <select name="forma_salida" class="form-control" required>
                                <option value="efectivo_usd">Efectivo USD</option>
                                <option value="efectivo_bs">Efectivo Bs</option>
                                <option value="transferencia_bs">Transferencia Bs</option>
                                <option value="pagomovil_bs">Pago Móvil Bs</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group mt-3">
                        <label>Referencia o Nota de Salida:</label>
                        <textarea name="referencia" class="form-control" required placeholder="Ej: Reembolso por anulación de interés #123"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">Confirmar Salida de Dinero</button>
                </div>
            </form>
        </div>
    </div>
</div>