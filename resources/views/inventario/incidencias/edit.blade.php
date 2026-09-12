@extends('layouts.app')
@section('title') Actualización de Incidencia @endsection
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
        <h1 class="text-danger"><i class="fa fa-lock"></i> Acceso Restringido</h1>
        <p>No tienes permisos para modificar registros de incidencias.</p>
        <a href="{{ route('incidencias.index') }}" class="btn btn-primary">Volver</a>
    </div>
  @else
  <div class="app-title">
    <div>
      <h1><i class="fa fa-edit"></i> SAYER</h1>
      <p>Sistema Administrativo | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item">SAYER</li>
      <li class="breadcrumb-item"><a href="{{ route('incidencias.index') }}">Incidencias</a></li>
      <li class="breadcrumb-item">Actualización</li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-md-12">
        <div class="tile">
          <h4>Actualización de Incidencia: codigo:{{ $incidencia->codigo }}</h4>
            <hr>
            <div class="tile-body">
              {!! Form::open(['route' => ['incidencias.update', $incidencia->id], 'method' => 'PUT', 'id' => 'editar_incidencia']) !!}
                @csrf
                
                @php
                    // Buscamos el insumo relacionando el id_insumo e id_local guardados en la incidencia
                    $insumoActual = $insumos->first(function ($item) use ($incidencia) {
                        return $item->id_real_insumo == $incidencia->id_insumo && $item->id_local == $incidencia->id_local;
                    });

                    $localActualId = $insumoActual ? $insumoActual->id_local : $incidencia->id_local;
                    $localActualNombre = $insumoActual ? $insumoActual->local_nombre : 'No asignado';
                    $detalleInsumoActual = $insumoActual ? "{$insumoActual->producto} | Serial: {$insumoActual->serial}" . ($insumoActual->descripcion ? " | {$insumoActual->descripcion}" : "") : 'No asignado';
                    
                    // Obtenemos el id_insumoc exacto para que JavaScript lo preseleccione
                    $currentInsumoCtrolId = $insumoActual ? $insumoActual->id_insumoc : '';
                @endphp

                <div class="row">
                  <div class="col-md-6">                  
                    <div class="form-group">
                      <label class="control-label">
                        Seleccione Local / Depósito <b style="color: red;">*</b>
                        <br><small class="text-info font-weight-bold"><i class="fa fa-info-circle"></i> Registrado: {{ $localActualNombre }}</small>
                      </label>
                      <select name="id_local" id="id_local" class="form-control select2" required>
                        <option value="">-- Seleccione un local --</option>
                        @foreach($locales as $local)
                          <option value="{{ $local->id }}" @if($local->id == $localActualId) selected @endif>
                            {{ $local->nombre }}
                          </option>
                        @endforeach
                      </select>
                    </div>
                  </div>

                  <div class="col-md-6">                  
                    <div class="form-group">
                      <label class="control-label">
                        Seleccione Insumo <b style="color: red;">*</b>
                        <br><small class="text-info font-weight-bold"><i class="fa fa-info-circle"></i> Registrado: {{ $detalleInsumoActual }}</small>
                      </label>
                      <select name="id_insumoc" id="id_insumoc" class="form-control select2" required>
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
                      <option value="Dañado de Fábrica" @if($incidencia->tipo == "Dañado de Fábrica") selected @endif>Dañado de Fábrica</option>
                      <option value="Dañado en Local" @if($incidencia->tipo == "Dañado en Local") selected @endif>Dañado en Local</option>
                      <option value="Dañado y Devuelto" @if($incidencia->tipo == "Dañado y Devuelto") selected @endif>Dañado y Devuelto</option>
                      <option value="Perdido" @if($incidencia->tipo == "Perdido") selected @endif>Perdido</option>
                      <option value="Vencido" @if($incidencia->tipo == "Vencido") selected @endif>Vencido</option>
                      <option value="Otro" @if($incidencia->tipo == "Otro") selected @endif>Otro (Especificar en observación)</option>
                    </select>
                  </div>
                </div>

                <div class="col-md-4">                  
                  <div class="form-group">
                    <label class="control-label">Fecha <b style="color: red;">*</b></label>
                    <input class="form-control datepicker" type="text" name="fecha_incidencia" id="fecha_incidencia" value="{{ $incidencia->fecha_incidencia }}" required readonly>
                  </div>
                </div>

                <div class="col-md-4">                  
                  <div class="form-group">
                    <label class="control-label">Cantidad <b style="color: red;">*</b></label>
                    <input class="form-control" type="number" name="cantidad" id="cantidad" min="1" value="{{ $incidencia->cantidad }}" required>
                    <small id="mensaje" style="color:red"></small>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-12">                  
                  <div class="form-group">
                    <label class="control-label">Observación</label>
                    <textarea name="observacion" id="observacion" class="form-control" rows="3">{{ $incidencia->observacion }}</textarea>
                  </div>
                </div>  
              </div>

              <div class="tile-footer">
                <button class="btn btn-primary" type="submit" id="registrar">
                  <i class="fa fa-fw fa-lg fa-check-circle"></i>Actualizar
                </button>
                <a class="btn btn-secondary" href="{{ route('incidencias.index') }}">
                  <i class="fa fa-fw fa-lg fa-times-circle"></i>Volver
                </a>
              </div>
            {!! Form::close() !!}
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
    const currentInsumoId = "{{ $incidencia->id_insumoc }}";
    const currentIncidenciaCantidad = parseInt("{{ $incidencia->cantidad }}") || 0;

    // Función para poblar el select de insumos según el local seleccionado
    const cargarInsumosPorLocal = (localId, selectedId = null) => {
        ui.insumo.empty().append('<option value="">-- Seleccione un insumo --</option>');
        
        if (localId) {
            const filtrados = allInsumos.filter(item => item.id_local == localId);
            
            filtrados.forEach(item => {
                let maxStock = parseInt(item.cantidad) || 0;
                
                // Si este es el insumo que ya tenía la incidencia, sumamos su cantidad actual para permitir reasignarla
                if (item.id_insumoc == currentInsumoId) {
                    maxStock += currentIncidenciaCantidad;
                }

                const descripcionText = item.descripcion ? ` | ${item.descripcion}` : '';
                const optionText = `Serial: ${item.serial} | ${item.producto}${descripcionText} | Disponible: ${maxStock}`;
                
                const option = new Option(optionText, item.id_insumoc, false, false);
                $(option).attr('data-max', maxStock);
                
                if (item.id_insumoc == selectedId) {
                    $(option).prop('selected', true);
                }
                
                ui.insumo.append(option);
            });
            
            ui.insumo.prop('disabled', false).trigger('change');
        } else {
            ui.insumo.prop('disabled', true);
        }
        validateForm();
    };

    // Evento al cambiar de local manualmente
    ui.local.on('change', function() {
        cargarInsumosPorLocal($(this).val());
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

    // Inicialización al cargar la página en modo edición
    const initialLocalId = ui.local.val();
    if (initialLocalId) {
        cargarInsumosPorLocal(initialLocalId, currentInsumoId);
    }

    ui.cantidad.on('input change', validateForm);
    ui.insumo.on('change', validateForm);
    ui.tipo.on('change', validateForm);
    ui.obs.on('input', validateForm);
});
</script>
@endsection