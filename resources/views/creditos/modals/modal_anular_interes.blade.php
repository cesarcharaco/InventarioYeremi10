{{-- MODAL DE ANULACIÓN DE INTERÉS --}}
<div class="modal fade" id="modalAnularInteres" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="" method="POST" id="formAnularInteres">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fa fa-ban"></i> Confirmar Anulación de Interés</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="h5 text-center">¿Seguro que deseas anular este interés?</p>
                    <div class="alert alert-warning text-center">
                        <strong>Monto a revertir: <span id="montoInteresText"></span></strong>
                    </div>
                    
                    <div class="form-group">
                        <label class="font-weight-bold">Razón de la anulación:</label>
                        <textarea name="observacion" class="form-control" rows="3" 
                                  placeholder="Explique brevemente por qué se está anulando esta indexación..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa fa-check"></i> Confirmar Anulación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>