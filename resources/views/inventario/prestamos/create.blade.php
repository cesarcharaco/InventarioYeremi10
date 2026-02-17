@extends('layouts.app')
@section('title') Registro de Préstamo @endsection
@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-th-list"></i> Inventario</h1>
      <p>Sistema de Inventario | Licancabur</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="">Inventario</a></li>
      <li class="breadcrumb-item"><a href="{{ url('inventario/prestamos') }}">Préstamos</a></li>
      <li class="breadcrumb-item"><a href="">Registro de Préstamo</a></li>
    </ul>
  </div>
  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head" id="indicators">Préstamos</h2>
        </div>
        <br>
        <div class="basic-tb-hd text-center">            
            @include('layouts.partials.flash-messages')
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <div class="tile">
          <h4>Registro de Préstamo <small>Todos los campos (<b style="color: red;">*</b>) son requeridos.</small></h4>
          <div class="tile-body">
            <form name="form_prestamo" id="form_prestamo" action="{{ route('prestamos.store') }}" method="post" data-parsley-validate>
              @csrf
              <div class="row">
                <div class="col-lg-8 col-md-8 col-sm-4 col-xs-12">                  
                  <div class="form-group">
                    <input type="checkbox" name="todos" value="todos" id="todos" title="Seleccione si desea seleccionar todos los solicitantes activos"><label class="control-label">&nbsp;&nbsp; <b>Seleccionar todos los solicitantes activos</b></label><br>
                    <label class="control-label"> Solicitantes <b style="color: red;">*</b></label><br>
                    <select name="id_solicitante[]" id="id_solicitante" class="form-control select2" title="Seleccione el Solicitante" multiple="multiple">
                      @foreach($solicitantes as $key)
                      <option value="{{ $key->id }}">{{ $key->nombres }} | RUT: {{ $key->rut }} | Status: {{ $key->status }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-5 col-md-8 col-sm-8 col-xs-8">                  
                  <div class="form-group">
                    <label class="control-label">Insumos <b style="color: red;">*</b></label><br>
                    <select name="id_insumo" id="id_insumo" class="form-control select2" title="Seleccione un insumo">
                    </select>
                  </div>
                </div> 
                </div>
              <div class="row">
                <div class="col-md-3">                  
                  <div class="form-group">
                    <label class="control-label">Tipo de Préstamo <b style="color: red;">*</b></label>
                    <select name="tipo" id="tipo" title="Seleccione el tipo de Préstamo" class="form-control">
                      <option value="Prestar">Prestar</option>
                      <option value="Entregar">Entregar</option>
                    </select>
                  </div>
                </div>
              <div class="col-md-3">                  
                  <div class="form-group">
                    <label class="control-label">Observación</label>
                    <textarea name="observacion" id="observacion" class="form-control" cols="10" rows="5"></textarea>
                  </div>
                </div>
                <div class="col-md-3">                  
                  <div class="form-group">
                    <label class="control-label">Fecha <b style="color: red;">*</b></label>
                    <input class="form-control datepick" type="text" required="required" name="fecha_prestamo" id="fecha_prestamo" placeholder="Seleccione la fecha en la que se realiza" max="{{ $hoy }}">
                  </div>
                </div>
                <div class="col-md-3">                  
                  <div class="form-group">
                    <label class="control-label">Cantidad <b style="color: red;">*</b> <small>(Stock)</small></label>
                    <input class="form-control" type="number" name="cantidad" id="cantidad" placeholder="Ingrese cantidad" disabled="disabled" title="La cantidad no debe superar el máximo disponible del insumo" required="required">
                    <small>La cantidad no debe superar el máximo disponible del insumo</small><br>
                    <small><span id="mensaje" style="color:red"></span></small>
                  </div>
                </div>
              </div><hr>
              {{-- <div class="row">
                <div class="col-md-12 text-right">
                  <button class="btn btn-primary" type="button"><i class="fa fa-fw fa-lg fa-plus"></i>Agregar otro equipo</button>
                </div>
              </div> --}}
          </div>
          <div class="tile-footer">
            <button class="btn btn-primary" disabled="disabled" type="submit" name="registrar" id="registrar"><i class="fa fa-fw fa-lg fa-check-circle"></i>Registrar</button>&nbsp;&nbsp;&nbsp;<a class="btn btn-secondary" href="{{ url('inventario/prestamos') }}"><i class="fa fa-fw fa-lg fa-times-circle"></i>Volver</a>
          </div>
            </form>
        </div>
      </div>
    </div>
  </div>
</main>
@endsection
@section('scripts')
<script type="text/javascript">

  

  $("#todos").on('change',function (event) {
    var todos = event.target.value;
    if ($(this).is(':checked')) {
      //console.log("seleccionado");

      $("#id_solicitante").attr('disabled',true);
    }else{
      //console.log("deseleccionado");
      $("#id_solicitante").removeAttr('disabled');
    }
    
  });

  $("#cantidad").on('keyup',function (event) {
    var cantidad = event.target.value;
    //console.log(cantidad);
    var id_insumo=$("#id_insumo").val();
    if(id_insumo>0){
    $.get('/insumos/'+id_insumo+'/buscar_existencia',function(data){
      //console.log(data+'-'+cantidad);
      var resta = data-cantidad;
      console.log(resta);
      if (resta<0) {
        $("#mensaje").text('La cantidad ha superado el límite disponible');
        $("#registrar").attr('disabled',true);
      } else {
        $("#mensaje").text('');
        $("#registrar").removeAttr('disabled');
      }
    });
    }
  });

 


</script>
@endsection