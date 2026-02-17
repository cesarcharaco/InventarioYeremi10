@extends('layouts.app')
@section('title') Registro de Incidencia @endsection

@section('content')
<main class="app-content">
  {{-- Bloque de seguridad: Solo quienes tienen permiso de registro --}}
  @cannot('registrar-incidencia')
    <div class="tile text-center">
        <h1 class="text-danger"><i class="fa fa-ban"></i> Acceso No Autorizado</h1>
        <p>Tu perfil no tiene permisos para reportar incidencias de inventario.</p>
    </div>
  @else
  <div class="app-title">
    <div>
      <h1><i class="fa fa-th-list"></i> SAYER</h1>
      <p>Sistema Administrativo | Yermotos Repuestos C.A.</p>
      </div>
      <ul class="app-breadcrumb breadcrumb">
        <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
        <li class="breadcrumb-item">SAYER</li>
        <li class="breadcrumb-item"><a href="{{ route('incidencias.index') }}">Incidencias</a></li>
        <li class="breadcrumb-item">Registro de Incidencia</li>
      </ul>
  </div>
  

  <div class="tile mb-4">
    <div class="row">
      <div class="col-md-12">
        <div class="tile">
          <h4>Registro de Incidencia <small>Todos los campos (<b style="color: red;">*</b>) son requeridos.</small></h4>
          <hr>
          <div class="basic-tb-hd text-center">            
              @include('layouts.partials.flash-messages')
          </div>
          
          <div class="tile-body">
            <form action="{{ route('incidencias.store') }}" method="post" id="form_incidencia" data-parsley-validate>
              @csrf
              <div class="row">
                <div class="col-lg-12">                  
                  <div class="form-group">
                    <label class="control-label">Seleccione Insumo y Ubicación <b style="color: red;">*</b></label>
                    <select name="id_insumoc" id="id_insumoc" class="form-control select2" required>
                      <option value="">-- Seleccione un insumo --</option>
                      @foreach($insumos as $key)
                        {{-- Usamos id_insumoc que es el ID de la tabla puente --}}
                        @if(auth()->user()->esAdmin() || auth()->user()->local_id == $key->local_id)
                        <option value="{{ $key->id_insumoc }}" data-max="{{ $key->cantidad }}">
                          {{ $key->producto }} | Serial: {{ $key->serial }} | Ubicación: {{ $key->local_nombre }} | Disponible: {{ $key->cantidad }}
                        </option>
                        @endif
                      @endforeach
                    </select>
                  </div>
                </div> 
              </div>

              <div class="row">
                <div class="col-md-4">                  
                  <div class="form-group">
                    <label class="control-label">Tipo de Incidencia <b style="color: red;">*</b></label>
                    <select name="tipo" id="tipo" class="form-control" required>
                      <option value="Dañado de Fábrica">Dañado de Fábrica</option>
                      <option value="Dañado en Local">Dañado en Local</option>
                      <option value="Dañado y Devuelto">Dañado y Devuelto</option>
                      <option value="Perdido">Perdido</option>
                      <option value="Vencido">Vencido</option>
                      <option value="Otro">Otro (Especificar en observación)</option>
                    </select>
                  </div>
                </div>
                
                <div class="col-md-4">                  
                  <div class="form-group">
                    <label class="control-label">Fecha <b style="color: red;">*</b></label>
                    <input class="form-control datepicker" type="text" name="fecha_incidencia" id="fecha_incidencia" value="{{ $hoy }}" required readonly>
                  </div>
                </div>

                <div class="col-md-4">                  
                  <div class="form-group">
                    <label class="control-label">Cantidad <b style="color: red;">*</b></label>
                    <input class="form-control" type="number" name="cantidad" id="cantidad" min="1" placeholder="0" required>
                    <small id="mensaje" style="color:red"></small>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-12">                  
                  <div class="form-group">
                    <label class="control-label">Observación</label>
                    <textarea name="observacion" id="observacion" class="form-control" rows="3"></textarea>
                  </div>
                </div>  
              </div>

              <div class="tile-footer">
                <button class="btn btn-primary" type="submit" id="registrar" disabled>
                  <i class="fa fa-fw fa-lg fa-check-circle"></i>Registrar
                </button>
                <a class="btn btn-secondary" href="{{ route('incidencias.index') }}">
                  <i class="fa fa-fw fa-lg fa-times-circle"></i>Volver
                </a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endcannot
</main>
@endsection

@section('scripts')
<script type="text/javascript">
$(document).ready(function() {
    // 1. Instancias de componentes
    const ui = {
        cantidad:    $("#cantidad"),
        insumo:      $("#id_insumoc"),
        tipo:        $("#tipo"),
        obs:         $("#observacion"),
        mensaje:     $("#mensaje"),
        btnSubmit:   $("#registrar")
    };

    $('.select2').select2();
    $('.datepicker').datepicker({ format: "yyyy-mm-dd", autoclose: true, endDate: "0d" });

    const validateForm = () => {
        const selected = ui.insumo.find(':selected');
        const max = parseInt(selected.data('max')) || 0;
        const val = parseInt(ui.cantidad.val()) || 0;
        const tipo = ui.tipo.val();
        
        let error = "";
        let isInvalid = false;

        if (ui.insumo.val() === "") {
            isInvalid = true;
        } else if (val <= 0) {
            error = "La cantidad debe ser mayor a 0";
            isInvalid = true;
        } else if (val > max) {
            error = `No hay suficiente stock (Máximo: ${max})`;
            isInvalid = true;
        }

        // Validación de observación si es "Otro"
        if (tipo === 'Otro' && ui.obs.val().trim().length < 5) {
            isInvalid = true;
            ui.obs.addClass('is-invalid');
        } else {
            ui.obs.removeClass('is-invalid');
        }

        ui.mensaje.text(error);
        ui.btnSubmit.prop('disabled', isInvalid);
        ui.cantidad.toggleClass('is-invalid', error !== "");
    };

    ui.cantidad.on('input change', validateForm);
    ui.insumo.on('change', validateForm);
    ui.tipo.on('change', validateForm);
    ui.obs.on('input', validateForm);
});
</script>
@endsection