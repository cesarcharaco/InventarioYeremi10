<div class="modal fade" id="modalAnularAbono" tabindex="-1" role="dialog" aria-labelledby="modalAnularLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalAnularLabel"><i class="fa fa-exclamation-triangle"></i> Confirmar Anulación</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formAnularAbono" method="POST">
                @csrf
                <div class="modal-body text-center">
                    <p class="h5">¿Estás seguro de que deseas anular este pago?</p>
                    <p class="text-muted">Esta acción restaurará el monto de <strong id="montoAbonoText"></strong> al saldo pendiente del cliente y marcará el abono como anulado en la caja.</p>
                    <div class="alert alert-warning">
                        <i class="fa fa-info-circle"></i> Esta operación es irreversible.
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Anulación</button>
                </div>
            </form>
        </div>
    </div>
</div>