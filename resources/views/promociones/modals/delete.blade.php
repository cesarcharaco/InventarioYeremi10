<div class="modal fade" id="modalDelete" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="formDelete" method="POST">
        @csrf
        @method('DELETE')
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Confirmar Eliminación</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>¿Estás seguro de que deseas eliminar la regla de promoción: <strong id="delete-nombre"></strong>?</p>
          <p class="text-muted mb-3"><small>Sucursal asignada: <span id="delete-local" class="font-weight-bold text-dark"></span></small></p>
          <div id="delete-warning" class="alert alert-warning" style="display: none;">
            <i class="fa fa-exclamation-triangle"></i> Advertencia: Esta promoción ya ha sido aplicada en ventas, por lo que el sistema denegará la eliminación por integridad de datos.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger">Sí, Eliminar</button>
        </div>
      </form>
    </div>
  </div>
</div>