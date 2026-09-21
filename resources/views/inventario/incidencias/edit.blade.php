@extends('layouts.app')
@section('title') Actualización de Incidencia @endsection
@section('css')
<style>
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
        color: #0f172a !important;
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
                    $insumoActual =$insumos->first(function ($item) use ($incidencia) {
                        return $item->id_real_insumo ==$incidencia->id_insumo && $item->id_local ==$incidencia->id_local;
                    });

                    $localActualId =$insumoActual ? $insumoActual->id_local :$incidencia->id_local;
                    $localActualNombre = $insumoActual ? $insumoActual->local_nombre : 'No asignado';
                    $detalleInsumoActual =$insumoActual ? "{$insumoActual->producto} \vert{} Serial: {$insumoActual->serial}" . ($insumoActual->descripcion ? " \vert{} {$insumoActual->descripcion}" : "") : 'No asignado';
                    
                    // Clave calculada para el script JS de edición
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
                        @foreach($locales as$local)
                          <option value="{{ $local->id }}" @if($local->id ==$localActualId) selected @endif>
                            {{ $local->nombre }}
                          </option>
                        @endforeach
                      </select>
                    </div>
                  </div>

                  <div class="col-md-6">                  
                    <div class="form-group position-relative">
                      <label class="control-label">
                        Buscar Insumo <b style="color: red;">*</b>
                        <br><small class="text-info font-weight-bold"><i class="fa fa-info-circle"></i> Registrado: {{ $detalleInsumoActual }}</small>
                      </label>
                      <input type="text" id="buscador_insumo" class="form-control" placeholder="Escribe el serial o nombre..." autocomplete="off">
                      <input type="hidden" name="id_insumoc" id="id_insumoc" value="{{ $currentInsumoCtrolId }}" required>
                      <div id="resultados-busqueda-insumo" class="list-group position-absolute w-100 shadow" style="z-index: 1000; display:none;"></div>
                      <small id="seleccion_info" class="text-success font-weight-bold mt-1 d-block"></small>
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
    $('.select2').select2({ width: '100\%' });$('.datepicker').datepicker({ format: "yyyy-mm-dd", autoclose: true, endDate: "0d" });

    const ui = {
        cantidad:         $("#cantidad"),
        local:            $("#id_local"),
        insumoHidden:     $("#id_insumoc"),
        buscadorInsumo:   $("#buscador_insumo"),
        resultadosInsumo: $("#resultados-busqueda-insumo"),
        seleccionInfo:    $("#seleccion_info"),
        tipo:             $("#tipo"),
        obs:              $("#observacion"),
        mensaje:          $("#mensaje"),
        btnSubmit:        $("#registrar")
    };

    const allInsumos = @json($insumos);
    const tiposResta = ['Dañado de Fábrica', 'Dañado en Local', 'Perdido', 'Vencido', 'Salida', 'Egreso', 'Retiro', 'Desincorporacion', 'Otro'];
    
    // CORREGIDO: Usar la variable PHP previamente calculada $currentInsumoCtrolId
    const currentInsumoId = "{{ $currentInsumoCtrolId }}";
    const currentIncidenciaCantidad = parseInt("{{ $incidencia->cantidad }}") || 0;
    let selectedMaxStock = 0;

    const initialItem = allInsumos.find(item => item.id_insumoc == currentInsumoId);
    if (initialItem) {
        let maxStock = (parseInt(initialItem.cantidad) || 0) + currentIncidenciaCantidad;
        selectedMaxStock = maxStock;
        ui.seleccionInfo.text(`Registrado: [${initialItem.serial}] ${initialItem.producto} (Stock máx: ${maxStock})`);
    }

    ui.local.on('change', function() {
        const localId = $(this).val();
        const originalLocalId = "{{ $insumoActual ? $insumoActual->id_local :$incidencia->id_local }}";
        
        ui.buscadorInsumo.val('');
        ui.resultadosInsumo.hide();
        
        if (localId == originalLocalId && initialItem) {
            ui.insumoHidden.val(currentInsumoId);
            let maxStock = (parseInt(initialItem.cantidad) || 0) + currentIncidenciaCantidad;
            selectedMaxStock = maxStock;
            ui.seleccionInfo.text(`Seleccionado: [${initialItem.serial}] ${initialItem.producto} (Stock: ${maxStock})`);
        } else {
            ui.insumoHidden.val('');
            selectedMaxStock = 0;
            ui.seleccionInfo.text('');
        }
        
        validateForm();
    });

    ui.buscadorInsumo.on('keyup', function() {
        const localId = ui.local.val();
        const q = $(this).val().toLowerCase();
        
        if (q.length < 2 || !localId) {
            ui.resultadosInsumo.hide();
            return;
        }

        const filtrados = allInsumos.filter(item => {
            if (item.id_local != localId) return false;
            const texto = `[${item.serial}] ${item.producto} ${item.descripcion || ''}`.toLowerCase();
            return texto.includes(q);
        });

        if (filtrados.length === 0) {
            ui.resultadosInsumo.html('<div class="list-group-item text-muted">No se encontraron insumos</div>').show();
            return;
        }

        let container = $('<div>');
        filtrados.forEach(item => {
            let maxStock = parseInt(item.cantidad) || 0;
            if (item.id_insumoc == currentInsumoId) {
                maxStock += currentIncidenciaCantidad;
            }

            let itemLink = $('<a>')
                .attr('href', '#')
                .addClass('list-group-item list-group-item-action btn-seleccionar-insumo')
                .attr('data-id', item.id_insumoc)
                .attr('data-serial', item.serial)
                .attr('data-producto', item.producto)
                .attr('data-descripcion', item.descripcion || '')
                .attr('data-maxstock', maxStock)
                .html(`<strong>[${item.serial}]</strong> ${item.producto} ${item.descripcion ? '- ' + item.descripcion : ''} | <span class="text-info">Disponible: ${maxStock}</span>`);
            container.append(itemLink);
        });

        ui.resultadosInsumo.html(container.html()).show();
    });

    $(document).on('click', '.btn-seleccionar-insumo', function(e) {
        e.preventDefault();
        const el = $(this);
        const id = el.data('id');
        const serial = el.data('serial');
        const producto = el.data('producto');
        const descripcion = el.data('descripcion');
        const maxStock = parseInt(el.data('maxstock')) || 0;

        ui.insumoHidden.val(id);
        selectedMaxStock = maxStock;
        ui.buscadorInsumo.val('');
        ui.resultadosInsumo.hide();
        ui.seleccionInfo.text(`Seleccionado: [${serial}] ${producto} ${descripcion ? '- ' + descripcion : ''} (Stock: ${maxStock})`);
        validateForm();
    });

    const validateForm = () => {
        const max = selectedMaxStock;
        const val = parseInt(ui.cantidad.val()) || 0;
        const tipo = ui.tipo.val();
        const esResta = tiposResta.includes(tipo);
        
        let error = "";
        let isInvalid = false;

        if (ui.local.val() === "" || ui.insumoHidden.val() === "") {
            isInvalid = true;
        } else if (val <= 0) {
            error = "La cantidad debe ser mayor a 0";
            isInvalid = true;
        } else if (esResta && val > max) {
            error = `No hay suficiente stock (Máximo permitido: ${max})`;
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
    ui.tipo.on('change', validateForm);
    ui.obs.on('input', validateForm);
    validateForm();
});
</script>
@endsection