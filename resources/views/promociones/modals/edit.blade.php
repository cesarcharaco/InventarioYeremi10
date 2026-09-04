<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form id="formEdit" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><i class="fa fa-edit"></i> Editar Regla de Promoción</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div id="edit-warning-alert" class="alert alert-warning" style="display: none;">
            <i class="fa fa-exclamation-triangle"></i> Esta promoción cuenta con registros de ventas asociados. Por seguridad financiera e integridad histórica, la sucursal y la fecha de inicio no se pueden modificar. Solo puedes actualizar el nombre, el porcentaje de descuento y la fecha de fin.
          </div>
          
          <div class="row">
            <div class="col-md-6 form-group">
              <label>Nombre de la Promoción</label>
              <input type="text" name="nombre" id="edit-nombre" class="form-control" required>
            </div>
            <div class="col-md-6 form-group">
              <label>Sucursal / Local</label>
              <select name="local_id" id="edit-local_id" class="form-control" required>
                <option value="">Seleccione una sucursal...</option>
                @foreach($locales as $local)
                  <option value="{{ $local->id }}">{{ $local->nombre }}</option>
                @endforeach
              </select>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Alcance (Fijo)</label>
              <input type="text" id="edit-alcance-text" class="form-control bg-light" readonly>
            </div>
            <div class="form-group col-md-6">
              <label>Elemento Asociado (Fijo)</label>
              <input type="text" id="edit-referencia-text" class="form-control bg-light" readonly>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-4">
              <label>Porcentaje de Descuento (%)</label>
              <input type="number" step="0.01" name="porcentaje_descuento" id="edit-porcentaje_descuento" class="form-control" min="0" max="100" required>
            </div>
            <div class="form-group col-md-4">
              <label>Fecha Inicio</label>
              <input type="date" name="fecha_inicio" id="edit-fecha_inicio" class="form-control" required>
            </div>
            <div class="form-group col-md-4">
              <label>Fecha Fin</label>
              <input type="date" name="fecha_fin" id="edit-fecha_fin" class="form-control" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Actualizar Cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>