@extends('layouts.app')

@section('title') Nuevo Despacho @endsection

@section('content')
<main class="app-content">
  {{-- 1. VERIFICACIÓN DE PERMISO PARA CREAR (ESTILO CATEGORÍAS) --}}
  @cannot('crear-despacho')
    <div class="tile text-center">
        <h1 class="text-danger"><i class="fa fa-lock"></i> Acceso Restringido</h1>
        <p>No tienes permisos para registrar salidas de mercancía en el sistema.</p>
        <a href="{{ route('despacho.index') }}" class="btn btn-primary">Volver al listado</a>
    </div>
  @else
  <div class="app-title">
    <div>
      <h1><i class="fa fa-truck"></i> Gestión de Despachos</h1>
      <p>Salida de Mercancía | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('despacho.index') }}">Despachos</a></li>
      <li class="breadcrumb-item"><a href="#">Nuevo</a></li>
    </ul>
  </div>

  <form action="{{ route('despacho.store') }}" method="POST" id="form-despacho">
    @csrf
    <div class="row">
      {{-- PANEL IZQUIERDO: DATOS DE CABECERA --}}
      <div class="col-md-4">
        <div class="tile">
          <h3 class="tile-title">Datos del Envío</h3>
          <div class="tile-body">
            <div class="form-group">
              <label><b>Código de Despacho</b></label>
              <input class="form-control" type="text" name="codigo" value="{{ $codigo }}" readonly>
            </div>

            <div class="form-group">
              <label><b>Origen (Donde sale)</b> <b class="text-danger">*</b></label>
              {{-- APLICACIÓN DE LA NUEVA GATE DE ALCANCE --}}
              @can('seleccionar-cualquier-origen')
                <select name="id_local_origen" id="id_local_origen" class="form-control select2" required>
                    <option value="">Seleccione origen...</option>
                    @foreach($locales as $local)
                        <option value="{{ $local->id }}">{{ $local->nombre }} ({{ $local->tipo }})</option>
                    @endforeach
                </select>
              @else
                @php $miLocal = auth()->user()->localActual(); @endphp
                <select class="form-control" disabled>
                    <option value="{{ $miLocal->id ?? '' }}">{{ $miLocal->nombre ?? 'Sin Local Asignado' }}</option>
                </select>
                <input type="hidden" name="id_local_origen" id="id_local_origen" value="{{ $miLocal->id ?? '' }}">
              @endcan
            </div>

            <div class="form-group">
              <label><b>Destino (A donde va)</b> <b class="text-danger">*</b></label>
              <select name="id_local_destino" id="id_local_destino" class="form-control select2" required>
                <option value="">Seleccione destino...</option>
                @foreach($locales as $local)
                  <option value="{{ $local->id }}">{{ $local->nombre }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group">
              <label><b>Transportado por:</b> <b class="text-danger">*</b></label>
              <input class="form-control" type="text" name="transportado_por" placeholder="Nombre del chofer" required>
            </div>

            <div class="form-group">
              <label><b>Vehículo / Placa</b></label>
              <input class="form-control" type="text" name="vehiculo_placa" placeholder="Ej: Toyota Blanca - AB123">
            </div>

            <div class="form-group">
              <label><b>Observaciones</b></label>
              <textarea class="form-control" name="observacion" rows="2"></textarea>
            </div>
          </div>
        </div>
      </div>

      {{-- PANEL DERECHO: SELECCIÓN DE PRODUCTOS --}}
      <div class="col-md-8">
        <div class="tile">
          <h3 class="tile-title">Cargar Repuestos</h3>
          <div class="tile-body">
            <div class="row">
              <div class="col-md-7">
                <div class="form-group">
                  <label><b>Buscar Insumo</b></label>
                  <select id="select_insumo" class="form-control select2">
                    <option value="">Seleccione un repuesto...</option>
                    @foreach($insumos as $insumo)
                      <option value="{{ $insumo->id }}">{{ $insumo->serial }} | {{ $insumo->producto }} | {{ $insumo->descripcion }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label><b>Cantidad</b></label>
                  <input type="number" id="input_cantidad" class="form-control" min="1" value="1">
                </div>
              </div>
              <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-info btn-block" onclick="agregarProducto()">
                  <i class="fa fa-plus"></i>
                </button>
              </div>
            </div>

            <table class="table table-bordered table-hover mt-3" id="tabla_productos">
              <thead>
                <tr>
                  <th>Repuesto</th>
                  <th width="100px">Cant.</th>
                  <th width="50px"></th>
                </tr>
              </thead>
              <tbody id="detalles_despacho">
                {{-- Aquí se cargarán las filas vía JS --}}
              </tbody>
            </table>
          </div>
          
          <div class="tile-footer">
            <button class="btn btn-primary" type="submit" id="btn-guardar" disabled>
              <i class="fa fa-fw fa-lg fa-check-circle"></i> Procesar Despacho
            </button>
            &nbsp;&nbsp;&nbsp;
            <a class="btn btn-secondary" href="{{ route('despacho.index') }}">
                <i class="fa fa-fw fa-lg fa-times-circle"></i> Cancelar
            </a>
          </div>
        </div>
      </div>
    </div>
  </form>
  @endcannot
</main>
@endsection

@section('scripts')
<script>
    if (typeof items === 'undefined') {
        var items = 0; 
    } else {
        items = 0; 
    }

    function agregarProducto() {
        let insumo_id = $('#select_insumo').val();
        let insumo_text = $('#select_insumo option:selected').text();
        let cantidad = $('#input_cantidad').val();

        if (insumo_id == "" || cantidad <= 0) {
            Swal.fire('Atención', 'Seleccione un producto y una cantidad válida.', 'warning');
            return;
        }

        let existe = false;
        $('input[name="id_insumo[]"]').each(function() {
            if ($(this).val() == insumo_id) existe = true;
        });

        if (existe) {
            Swal.fire('Repetido', 'Este producto ya está en la lista.', 'info');
            return;
        }

        let fila = `
            <tr id="fila_${items}">
                <td>
                    <input type="hidden" name="id_insumo[]" value="${insumo_id}">
                    ${insumo_text}
                </td>
                <td>
                    <input type="number" name="cantidad[]" class="form-control form-control-sm" value="${cantidad}" readonly>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(${items})">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#detalles_despacho').append(fila);
        items++; 
        verificarBoton();
        
        $('#select_insumo').val(null).trigger('change');
        $('#input_cantidad').val(1);
    }

    function eliminarFila(index) {
        $(`#fila_${index}`).remove();
        verificarBoton();
    }

    function verificarBoton() {
        let totalFilas = $('#detalles_despacho tr').length;
        $('#btn-guardar').prop('disabled', totalFilas === 0);
    }

    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });

        $('#form-despacho').on('submit', function(e) {
            e.preventDefault();
            let form = this;

            Swal.fire({
                title: '¿Procesar Despacho?',
                text: "Se generará la salida de mercancía y se actualizará el inventario.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#009688',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, procesar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Generando Despacho',
                        text: 'Por favor espere...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading() }
                    });
                    form.submit();
                }
            });
        });

        $('#id_local_origen').on('change', function() {
            let origenId = $(this).val();
            let destinoSelect = $('#id_local_destino');
            
            destinoSelect.find('option').prop('disabled', false);
            
            if (origenId) {
                destinoSelect.find(`option[value="${origenId}"]`).prop('disabled', true);
                if (destinoSelect.val() === origenId) {
                    destinoSelect.val(null).trigger('change');
                }
            }
            destinoSelect.select2({ width: '100%' });
        });
    });
</script>
@endsection