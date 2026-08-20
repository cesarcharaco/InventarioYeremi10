<div class="modal fade" id="modalCreditoDirectoGeneral" tabindex="-1" role="dialog" aria-labelledby="modalCreditoDirectoGeneralLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalCreditoDirectoGeneralLabel">
                    <i class="fa fa-plus-circle"></i> Registrar Nuevo Crédito Directo
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <form action="{{ route('creditos.directo.store_general') }}" method="POST" id="formCreditoDirectoGeneral">
                @csrf
                <input type="hidden" name="pin_autorizacion" id="pin_autorizacion_directo_general">

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label for="cliente_id_general" class="font-weight-bold">Seleccionar Cliente <span class="text-danger">*</span></label>
                            <select name="cliente_id" id="cliente_id_general" class="form-control select2-modal" required>
                                <option value="">-- Seleccione un cliente --</option>
                                @foreach($todosLosClientes as $cli)
                                    <option value="{{ $cli->id }}">
                                        {{ $cli->nombre }} ({{ $cli->identificacion }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="monto_credito_usd_general" class="font-weight-bold">Monto del Crédito ($) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-dollar"></i></span>
                                </div>
                                <input type="number" step="0.01" min="0.01" name="monto_credito_usd" id="monto_credito_usd_general" class="form-control form-control-lg font-weight-bold text-danger" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="fecha_credito_general" class="font-weight-bold">Fecha del Registro <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="fecha_credito" id="fecha_credito_general" class="form-control" value="{{ date('Y-m-d\TH:i') }}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="observacion_general" class="font-weight-bold">Detalle / Concepto del Crédito</label>
                        <textarea name="observacion" id="observacion_general" class="form-control" rows="3" placeholder="Ej: Crédito otorgado de forma directa sin venta en caja. Nota de entrega, etc."></textarea>
                    </div>

                    {{-- Validación de PIN si el usuario no posee permisos avanzados --}}
                    @cannot('gestionar-creditos-avanzado')
                        <div class="alert alert-warning border-warning d-flex justify-content-between align-items-center mb-0 mt-3" id="bloque_pin_warning_general">
                            <div>
                                <i class="fa fa-lock fa-lg text-warning mr-2"></i>
                                <span class="font-weight-bold" id="estado_pin_texto_general">Requiere autorización de supervisor</span>
                            </div>
                            <button type="button" class="btn btn-warning btn-sm font-weight-bold" id="btnSolicitarPinDirectoGeneral">
                                <i class="fa fa-key"></i> Solicitar / Validar PIN
                            </button>
                        </div>
                    @endcannot
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success font-weight-bold">
                        <i class="fa fa-check"></i> Guardar Crédito Directo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>