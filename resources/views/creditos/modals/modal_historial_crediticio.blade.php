{{-- MODAL: BÚSQUEDA DE HISTORIAL CREDITICIO POR RANGO DE FECHAS --}}
<div class="modal fade" id="modalHistorialFechas" tabindex="-1" role="dialog" aria-labelledby="modalHistorialFechasLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="modalHistorialFechasLabel">
                    <i class="fa fa-calendar-alt text-warning mr-2"></i> Consultar Historial Crediticio
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            {{-- Formulario vinculado al ID del cliente actual --}}
            <form action="{{ route('creditos.historial_crediticio', $cliente->id) }}" method="GET" target="_blank" id="formHistorialFechas">
                <div class="modal-body">
                    <p class="text-muted small">
                        Selecciona el rango de fechas para consultar el historial detallado de créditos del cliente <strong>{{ $cliente->nombre }}</strong>, incluidos registros ya pagados.
                    </p>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="fecha_inicio" class="font-weight-bold small">Fecha de Inicio:</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="{{ date('Y-m-01') }}" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="fecha_fin" class="font-weight-bold small">Fecha de Fin:</label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary font-weight-bold">
                        <i class="fa fa-search mr-1"></i> Generar Historial
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>