<div class="modal fade" id="modalEliminarCredito" tabindex="-1" role="dialog" aria-labelledby="modalEliminarCreditoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white py-2">
                <h5 class="modal-title font-weight-bold" id="modalEliminarCreditoLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <form id="formEliminarCredito" action="" method="POST">
                @csrf
                @method('DELETE')
                
                <div class="modal-body">
                    <p class="mb-2">¿Está seguro de que desea eliminar permanentemente este crédito?</p>
                    
                    <div class="card bg-light p-3 mb-3 border">
                        <small class="text-muted d-block"><strong>Referencia:</strong> <span id="eliminar_credito_codigo"></span></small>
                        <small class="text-muted d-block"><strong>Monto Original:</strong> $<span id="eliminar_credito_monto"></span></small>
                        <small class="text-muted d-block"><strong>Saldo Pendiente:</strong> $<span id="eliminar_credito_saldo"></span></small>
                    </div>

                    {{-- Mensaje dinámico según tenga productos o sea crédito directo --}}
                    <div id="msg_retorno_stock" class="alert alert-warning p-2 small mb-0 d-none">
                        <i class="fas fa-box-open mr-1"></i> 
                        <strong>Retorno de inventario:</strong> Los productos asociados a esta venta serán devueltos automáticamente al stock.
                    </div>
                    
                    <div id="msg_credito_directo" class="alert alert-info p-2 small mb-0 d-none">
                        <i class="fas fa-info-circle mr-1"></i> 
                        Este es un crédito directo (sin productos en inventario).
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger btn-sm font-weight-bold">
                        <i class="fas fa-trash-alt mr-1"></i> Sí, Eliminar Crédito
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>