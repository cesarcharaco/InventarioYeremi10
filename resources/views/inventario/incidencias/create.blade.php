@extends('layouts.app')
@section('title') Registro de Incidencia @endsection
@section('css')

<style>
    /* Forzar la altura y padding del Select2 para que coincida con los form-control */
    .select2-container--default .select2-selection--single {
        height: calc(2.25rem + 2px) !important;
        padding: 0.375rem 0.75rem !important;
        border: 1px solid #ced4da !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5 !important;
        padding-left: 0 !important;
        color: #495057;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: calc(2.25rem + 2px) !important;
        top: 0 !important;
        right: 5px !important;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #63E2F7 !important;
        color: #0f172a !important; /* Texto oscuro para garantizar alto contraste y legibilidad */
    }
</style>
@endsection
@section('content')
<main class="app-content">
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
                <div class="col-md-6">                  
                  <div class="form-group">
                    <label class="control-label">Seleccione Local / Depósito <b style="color: red;">*</b></label>
                    <select name="id_local" id="id_local" class="form-control select2" required>
                      <option value="">-- Seleccione un local --</option>
                      @foreach($locales as $local)
                        <option value="{{ $local->id }}">{{ $local->nombre }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>

                <div class="col-md-6">                  
                  <div class="form-group">
                    <label class="control-label">Seleccione Insumo <b style="color: red;">*</b></label>
                    <select name="id_insumoc" id="id_insumoc" class="form-control select2" required disabled>
                      <option value="">-- Primero seleccione un local --</option>
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
    $('.select2').select2();
    $('.datepicker').datepicker({ format: "yyyy-mm-dd", autoclose: true, endDate: "0d" });

    const ui = {
        cantidad:    $("#cantidad"),
        local:       $("#id_local"),
        insumo:      $("#id_insumoc"),
        tipo:        $("#tipo"),
        obs:         $("#observacion"),
        mensaje:     $("#mensaje"),
        btnSubmit:   $("#registrar")
    };

    const allInsumos = @json($insumos);

    ui.local.on('change', function() {
        const localId = $(this).val();
        
        ui.insumo.empty().append('<option value="">-- Seleccione un insumo --</option>');
        
        if (localId) {
            const filtrados = allInsumos.filter(item => item.id_local == localId);
            
            filtrados.forEach(item => {
                const option = new Option(
                    `Serial: ${item.serial} | ${item.producto} | ${item.descripcion} | Disponible: ${item.cantidad}`, 
                    item.id_insumoc, 
                    false, 
                    false
                );
                $(option).attr('data-max', item.cantidad);
                ui.insumo.append(option);
            });
            
            ui.insumo.prop('disabled', false);
        } else {
            ui.insumo.prop('disabled', true);
        }
        
        ui.insumo.trigger('change');
        validateForm();
    });

    const validateForm = () => {
        const selected = ui.insumo.find(':selected');
        const max = parseInt(selected.data('max')) || 0;
        const val = parseInt(ui.cantidad.val()) || 0;
        const tipo = ui.tipo.val();
        
        let error = "";
        let isInvalid = false;

        if (ui.local.val() === "" || ui.insumo.val() === "") {
            isInvalid = true;
        } else if (val <= 0) {
            error = "La cantidad debe ser mayor a 0";
            isInvalid = true;
        } else if (val > max) {
            error = `No hay suficiente stock (Máximo: ${max})`;
            isInvalid = true;
        }

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