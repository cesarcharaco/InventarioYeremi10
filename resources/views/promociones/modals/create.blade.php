<div class="modal fade" id="modalCreate" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form action="{{ route('promociones.store') }}" method="POST">
      @csrf
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="fa fa-plus"></i> Nueva Regla de Promoción</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 form-group">
              <label>Nombre de la Promoción</label>
              <input type="text" name="nombre" class="form-control" required>
            </div>
            <div class="col-md-6 form-group">
              <label>Sucursal / Local</label>
              <select name="local_id" class="form-control" required>
                <option value="">Seleccione una sucursal...</option>
                @foreach($locales as $local)
                  <option value="{{ $local->id }}">{{ $local->nombre }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 form-group">
              <label>Alcance</label>
              <select name="alcance" id="create_alcance" class="form-control" required>
                <option value="insumo" selected>Insumo / Producto</option>
                <option value="categoria">Categoría</option>
              </select>
            </div>
            <div class="col-md-6 form-group">
              <label>Elementos Asociados (Búsqueda y Selección Múltiple)</label>
              <select name="referencia_id[]" id="create_referencia_id" class="form-control select2" multiple="multiple" style="width: 100%;" required>
                @foreach($insumos as $insumo)
                  <option value="{{ $insumo->id }}">
                    {{ $insumo->producto }} - {{ $insumo->descripcion ?? 'Sin descripción' }} (Serial: {{ $insumo->serial ?? 'N/A' }})
                  </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 form-group">
              <label>Porcentaje de Descuento (%)</label>
              <input type="number" step="0.01" name="porcentaje_descuento" class="form-control" min="0" max="100" required>
            </div>
            <div class="col-md-4 form-group">
              <label>Fecha Inicio</label>
              <input type="date" name="fecha_inicio" class="form-control" required>
            </div>
            <div class="col-md-4 form-group">
              <label>Fecha Fin</label>
              <input type="date" name="fecha_fin" class="form-control" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success">Guardar Promoción</button>
        </div>
      </div>
    </form>
  </div>
</div>