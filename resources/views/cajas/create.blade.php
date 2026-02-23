@extends('layouts.app')

@section('title') Apertura de Caja @endsection

@section('content')

<main class="app-content">
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
                        <select name="id_local" id="id_local" class="form-control select2 @error('id_local') is-invalid @enderror" required>
                            @foreach($locales as $local)        
                                <option value="{{ $local->id }}" {{ (auth()->user()->localActual() && auth()->user()->localActual()->id == $local->id) ? 'selected' : '' }}>
                                    {{ $local->nombre }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        @php $miLocal = $locales->first(); @endphp
                        <input type="text" class="form-control" value="{{ $miLocal->nombre }}" readonly>
                        <input type="hidden" name="id_local" value="{{ $miLocal->id }}">
                        <small class="text-primary font-italic">Sede asignada por sistema según sucursal activa.</small>
                    @endif

                    @error('id_local')
                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                    </div>
                  {{-- BLOQUE DE RESPONSABLE: Usamos el campo id_user existente en tu DB --}}
                    <div class="form-group">
                        <label class="control-label font-weight-bold">Asignar Responsable de Caja <b style="color: red;">*</b></label>
                        
                        @if(auth()->user()->role === 'admin' || auth()->user()->role === 'encargado')
                            {{-- Si es Admin o Encargado, puede elegir a qué vendedor le abre la caja --}}
                            <select name="id_user" id="id_user" class="form-control select2 @error('id_user') is-invalid @enderror" required>
                                <option value="">-- Seleccione el Vendedor --</option>
                                @foreach($vendedores as $vendedor)
                                    <option value="{{ $vendedor->id }}" {{ old('id_user') == $vendedor->id ? 'selected' : '' }}>
                                        {{ $vendedor->name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            {{-- Si es un vendedor abriendo su propia caja, el valor se pone automático --}}
                            <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                            <input type="hidden" name="id_user" value="{{ auth()->user()->id }}">
                        @endif

                        @error('id_user')
                            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                        <small class="text-muted">El usuario seleccionado será el titular de los ingresos de esta jornada.</small>
                    </div>
                    <div class="form-group">
                    <label class="control-label font-weight-bold">Monto Inicial (USD Efectivo) <b style="color: red;">*</b></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-success text-white"><b>$</b></span>
                        </div>
                        <input class="form-control form-control-lg @error('monto_apertura_usd') is-invalid @enderror" 
                               type="number" step="0.01" name="monto_apertura_usd" id="monto_apertura_usd" 
                               required value="{{ old('monto_apertura_usd', '0.00') }}">
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="control-label font-weight-bold">Monto Inicial (Bs Efectivo) <b style="color: red;">*</b></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-info text-white"><b>Bs</b></span>
                        </div>
                        <input class="form-control form-control-lg @error('monto_apertura_bs') is-invalid @enderror" 
                               type="number" step="0.01" name="monto_apertura_bs" id="monto_apertura_bs" 
                               required value="{{ old('monto_apertura_bs', '0.00') }}">
                    </div>
                    <small class="text-muted">Ingrese el efectivo base (USD y Bs) con el que inicia su turno.</small>
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
@section('scripts')
<script>
    $(document).ready(function() {
        // Inicializar Select2
          

        $('.select2').select2({ theme: 'bootstrap4' });

        // Evento para el cambio de local
        $('#id_local').on('change', function() {
            var localId = $(this).val();
            var selectVendedor = $('#id_user');

            // Si el usuario no es admin/encargado, id_user no es un select, así que no hacemos nada
            if (!selectVendedor.is('select')) return;

            if (localId) {
                // Bloqueamos el select y mostramos estado de carga
                selectVendedor.prop('disabled', true);
                
                $.ajax({
                    url: '/locales/' + localId + '/vendedores',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        // Limpiar opciones actuales
                        selectVendedor.empty();
                        selectVendedor.append('<option value="">-- Seleccione el Vendedor --</option>');
                        
                        // Llenar con los datos recibidos
                        $.each(data, function(key, vendedor) {
                            selectVendedor.append('<option value="' + vendedor.id + '">' + vendedor.name + '</option>');
                        });
                        
                        selectVendedor.prop('disabled', false);
                        
                        // IMPORTANTE: Refrescar Select2 para que muestre los nuevos datos
                        selectVendedor.trigger('change.select2'); 
                    },
                    error: function() {
                        Swal.fire('Error', 'No se pudieron cargar los vendedores de este local.', 'error');
                        selectVendedor.prop('disabled', false);
                    }
                });
            }
        });
    });
</script>
@endsection