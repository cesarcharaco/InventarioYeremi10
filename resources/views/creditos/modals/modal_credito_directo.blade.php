<div class="modal fade" id="modalCreditoDirecto"  role="dialog" aria-labelledby="modalCreditoDirectoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalCreditoDirectoLabel">
                    <i class="fa fa-cart-plus"></i> Registrar Crédito Directo
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <form action="{{ route('creditos.directo.store', $cliente->id) }}" method="POST" id="formCreditoDirecto">
                @csrf
                <input type="hidden" name="pin_autorizacion" id="pin_autorizacion_directo">

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="monto_credito_usd" class="font-weight-bold">Monto del Crédito ($) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-dollar"></i></span>
                                </div>
                                <input type="number" step="0.01" min="0.01" name="monto_credito_usd" id="monto_credito_usd" class="form-control form-control-lg font-weight-bold text-danger" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="fecha_credito" class="font-weight-bold">Fecha del Registro</label>
                            <input type="datetime-local" name="fecha_credito" id="fecha_credito" class="form-control" value="{{ date('Y-m-d\TH:i') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="observacion" class="font-weight-bold">Detalle de Productos / Notas adicionales</label>
                        <textarea name="observacion" id="observacion" class="form-control" rows="3" placeholder="Ej: Quién lo retiró, qué retiró, etc."></textarea>
                    </div>

                    {{-- Bloque de autorización por PIN si no tiene permisos --}}
                    @cannot('gestionar-creditos-avanzado')
                        <div class="alert alert-warning border-warning d-flex justify-content-between align-items-center mb-0 mt-3" id="bloque_pin_warning">
                            <div>
                                <i class="fa fa-lock fa-lg text-warning mr-2"></i>
                                <span class="font-weight-bold" id="estado_pin_texto">Requiere autorización de supervisor</span>
                            </div>
                            <button type="button" class="btn btn-warning btn-sm font-weight-bold" id="btnSolicitarPinDirecto">
                                <i class="fa fa-key"></i> Solicitar / Validar PIN
                            </button>
                        </div>
                    @endcannot
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary font-weight-bold" id="btnGuardarCreditoDirecto">
                        <i class="fa fa-check"></i> Guardar Crédito Directo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>