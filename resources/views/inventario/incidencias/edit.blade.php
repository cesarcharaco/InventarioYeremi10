@extends('layouts.app')
@section('title') Actualización de Incidencia @endsection
@section('content')
<main class="app-content">
  {{-- 1. Verificación de permiso para editar --}}
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
          <h4>Actualización de Incidencia <small>ID: #{{ $incidencia->id }}</small></h4>
          <hr>
          <div class="tile-body">
            {!! Form::open(['route' => ['incidencias.update', $incidencia->id], 'method' => 'PUT', 'id' => 'editar_incidencia']) !!}
              @csrf
              <div class="row">
                <div class="col-lg-12">                  
                  <div class="form-group">
                    <label class="control-label">Insumo y Ubicación <b style="color: red;">*</b></label>
                    <select name="id_insumoc" id="id_insumoc" class="form-control select2" required>
                      @foreach($insumos as $key)
                        {{-- 2. Filtro de seguridad: Solo mostrar el insumo actual o los del local del usuario si no es admin --}}
                        @if(auth()->user()->esAdmin() || auth()->user()->local_id == $key->local_id || $key->id_insumoc == $incidencia->id_insumoc)
                          <option value="{{ $key->id_insumoc }}" 
                            data-max="{{ $key->cantidad + ($key->id_insumoc == $incidencia->id_insumoc ? $incidencia->cantidad : 0) }}"
                            @if($key->id_insumoc == $incidencia->id_insumoc) selected @endif>
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
                      <option value="Dañado de Fábrica" @if($incidencia->tipo == "Dañado de Fábrica") selected @endif>Dañado de Fábrica</option>
                      <option value="Dañado en Local" @if($incidencia->tipo == "Dañado en Local") selected @endif>Dañado en Local</option>
                      <option value="Dañado y Devuelto" @if($incidencia->tipo == "Dañado y Devuelto") selected @endif>Dañado y Devuelto</option>
                      <option value="Perdido" @if($incidencia->tipo == "Perdido") selected @endif>Perdido</option>
                      <option value="Vencido" @if($incidencia->tipo == "Vencido") selected @endif>Vencido</option>
                      {{-- Opción OTRO --}}
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

    // 2. Funciones de Validación Reutilizables
    const validateStock = () => {
        const selected = ui.insumo.find(':selected');
        const max      = parseInt(selected.data('max')) || 0;
        const val      = parseInt(ui.cantidad.val()) || 0;
        
        const isInvalid = (ui.insumo.val() === "" || val <= 0 || val > max);
        
        ui.mensaje.text(val > max ? `Límite superado (Máx: ${max})` : '');
        ui.btnSubmit.prop('disabled', isInvalid);
    };

    const toggleOtroRequirement = () => {
        const isOtro = (ui.tipo.val() === 'Otro');
        ui.obs.prop('required', isOtro)
              .attr('placeholder', isOtro ? 'ESPECIFIQUE LA INCIDENCIA AQUÍ...' : '')
              .css('border', isOtro ? '1px solid red' : '1px solid #ced4da');
    };

    // 3. Registro de Eventos (Adaptabilidad)
    ui.cantidad.on('keyup change', validateStock);
    ui.insumo.on('change', validateStock);
    ui.tipo.on('change', toggleOtroRequirement);

    // 4. Inicialización (Punto Clave para Reingeniería)
    // Esto hace que el script funcione en EDIT automáticamente al cargar datos existentes
    validateStock();
    toggleOtroRequirement();
});
</script>
@endsection