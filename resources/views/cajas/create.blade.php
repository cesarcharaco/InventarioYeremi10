@extends('layouts.app')

@section('title') Apertura de Caja @endsection

@section('content')
<main class="app-content">
  {{-- Verificación de permiso --}}
  @cannot('operar-caja')
    <div class="tile text-center">
        <h1 class="text-danger"><i class="fa fa-lock"></i> Acceso Restringido</h1>
        <p>No tienes permisos para registrar aperturas de caja en el sistema.</p>
        <a href="{{ route('home') }}" class="btn btn-primary">Volver al Inicio</a>
    </div>
  @else
  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head" id="indicators">Cajas</h2>
        </div><br>
        <div class="basic-tb-hd text-center">            
            @include('layouts.partials.flash-messages')
            
            {{-- Errores de validación --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul style="margin-bottom: 0;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="tile">
          <h4 class="text-center font-weight-bold"><i class="fa fa-cash-register mr-2"></i> INICIAR JORNADA DE TRABAJO</h4>
          <hr>
          <div class="tile-body">
            <form action="{{ route('cajas.store') }}" method="POST" name="registrar_caja">
              @csrf
              
              <div class="row">
                <div class="col-md-12">                  
                  <div class="form-group">
				    <label class="control-label font-weight-bold">Sede / Local <b style="color: red;">*</b></label>
				    
				    @if(auth()->user()->hasRole('admin'))
				        {{-- ADMIN: Elige entre todos los locales --}}
				        <select name="id_local" id="id_local" class="form-control select2 @error('id_local') is-invalid @enderror" required>
				            @foreach($locales as $local)
				                <option value="{{ $local->id }}" {{ (auth()->user()->localActual() && auth()->user()->localActual()->id == $local->id) ? 'selected' : '' }}>
				                    {{ $local->nombre }}
				                </option>
				            @endforeach
				        </select>
				    @else
				        {{-- VENDEDOR: Solo ve su local actual activo --}}
				        @php $miLocal = $locales->first(); @endphp
				        
				        <input type="text" class="form-control" value="{{ $miLocal->nombre }}" readonly>
				        {{-- Enviamos el ID del local obtenido de la tabla pivote --}}
				        <input type="hidden" name="id_local" value="{{ $miLocal->id }}">
				        
				        <small class="text-primary font-italic">Sede asignada por sistema según sucursal activa.</small>
				    @endif

				    @error('id_local')
				        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
				    @enderror
				</div>

                  <div class="form-group">
                    <label class="control-label font-weight-bold">Monto Inicial en Caja (USD Efectivo) <b style="color: red;">*</b></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-success text-white"><b>$</b></span>
                        </div>
                        <input class="form-control form-control-lg @error('monto_apertura_usd') is-invalid @enderror" 
                               type="number" 
                               step="0.01" 
                               placeholder="0.00" 
                               name="monto_apertura_usd" 
                               id="monto_apertura_usd" 
                               required="required" 
                               value="{{ old('monto_apertura_usd', '0.00') }}">
                    </div>
                    <small class="text-muted">Ingrese el efectivo base con el que inicia su turno.</small>

                    @error('monto_apertura_usd')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                  </div>
                </div>
              </div>

              <div class="tile-footer">
                <button class="btn btn-primary btn-block btn-lg" type="submit">
                    <i class="fa fa-fw fa-lg fa-check-circle"></i> ABRIR CAJA Y EMPEZAR
                </button>
                <br>
                <a class="btn btn-secondary btn-block" href="{{ route('home') }}">
                    <i class="fa fa-fw fa-lg fa-times-circle"></i> Volver al Inicio
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
@section('js')
<script>
    $(document).ready(function() {
        $('.select2').select2({ theme: 'bootstrap4' });
    });
</script>
@stop