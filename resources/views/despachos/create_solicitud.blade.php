@extends('layouts.app')

@section('title') Nueva Solicitud de Pedido @endsection

@section('content')
<main class="app-content">
  {{-- VERIFICACIÓN DE PERMISO PARA CREAR SOLICITUD --}}
  @cannot('crear-solicitud')
    <div class="tile text-center shadow-sm py-5">
        <h1 class="text-danger mb-3"><i class="fa fa-lock fa-2x"></i></h1>
        <h3 class="text-danger">Acceso Restringido</h3>
        <p class="text-muted">No tienes permisos para generar solicitudes de pedido en el sistema.</p>
        <a href="{{ route('despacho.index') }}" class="btn btn-primary mt-2">
            <i class="fa fa-arrow-left"></i> Volver al listado
        </a>
    </div>
  @else
  <div class="app-title">
    <div>
      <h1><i class="fa fa-file-text-o"></i> Gestión de Solicitudes</h1>
      <p>Solicitud de Reabastecimiento de Mercancía | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('despacho.index') }}">Logística</a></li>
      <li class="breadcrumb-item active">Nueva Solicitud</li>
    </ul>
  </div>

  @if(session('danger') || session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('danger') ?? session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

  <form action="{{ route('despacho.solicitud.store') }}" method="POST" id="form-solicitud">
    @csrf
    <div class="row">
      {{-- PANEL IZQUIERDO: DATOS DE CABECERA DE LA SOLICITUD --}}
      <div class="col-md-4">
        <div class="tile shadow-sm">
          <h3 class="tile-title"><i class="fa fa-info-circle text-primary"></i> Datos de la Solicitud</h3>
          <div class="tile-body">
            <div class="form-group">
              <label><b>Código de Solicitud</b></label>
              <input class="form-control bg-light font-weight-bold text-primary" type="text" name="codigo" value="{{ $codigo }}" readonly>
            </div>

            <div class="form-group">
              <label><b>Origen (Depósito / Almacén que surte)</b> <b class="text-danger">*</b></label>
              <select name="id_local_origen" id="id_local_origen" class="form-control select2" required>
                  <option value="">Seleccione depósito origen...</option>
                  @foreach($localesOrigen as $local)
                      <option value="{{ $local->id }}">{{ $local->nombre }} ({{ $local->tipo }})</option>
                  @endforeach
              </select>
            </div>

            <div class="form-group">
              <label><b>Destino (Tu Local / Sucursal que recibe)</b> <b class="text-danger">*</b></label>
              @if(auth()->user()->role === \App\Models\User::ROLE_ENCARGADO)
                <select class="form-control" disabled>
                    <option value="{{ $localesDestino->id ?? '' }}">{{ $localesDestino->nombre ?? 'Sin Local Asignado' }}</option>
                </select>
                <input type="hidden" name="id_local_destino" id="id_local_destino" value="{{ $localesDestino->id ?? '' }}">
              @else
                <select name="id_local_destino" id="id_local_destino" class="form-control select2" required>
                    <option value="">Seleccione local destino...</option>
                    @foreach($localesDestino as $local)
                      <option value="{{ $local->id }}">{{ $local->nombre }}</option>
                    @endforeach
                </select>
              @endif
            </div>

            <div class="form-group">
              <label><b>Observaciones / Justificación</b></label>
              <textarea class="form-control" name="observacion" rows="3" placeholder="Motivo de la solicitud, urgencia, notas..."></textarea>
            </div>
          </div>
        </div>
      </div>

      {{-- PANEL DERECHO: SELECCIÓN DE INSUMOS A SOLICITAR --}}
      <div class="col-md-8">
        <div class="tile shadow-sm">
          <h3 class="tile-title"><i class="fa fa-cogs text-primary"></i> Repuestos Solicitados</h3>
          <div class="tile-body">
            <div class="row align-items-end">
              <div class="col-md-7">
                <div class="form-group mb-md-0">
                  <label><b>Buscar Insumo / Repuesto</b></label>
                  <select id="select_insumo" class="form-control select2">
                    <option value="">Seleccione un repuesto...</option>
                    @foreach($insumos as $insumo)
                      <option value="{{ $insumo->id }}">{{ $insumo->serial }} | {{ $insumo->producto }} | {{ $insumo->descripcion }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group mb-md-0">
                  <label><b>Cantidad Pedida</b></label>
                  <input type="number" id="input_cantidad" class="form-control" min="1" value="1">
                </div>
              </div>
              <div class="col-md-2">
                <button type="button" class="btn btn-info btn-block text-white" onclick="agregarProducto()" title="Agregar a la lista">
                  <i class="fa fa-plus"></i> Añadir
                </button>
              </div>
            </div>

            <div class="table-responsive mt-4">
              <table class="table table-bordered table-hover align-middle" id="tabla_productos">
                <thead class="table-light">
                  <tr>
                    <th>Repuesto / Insumo</th>
                    <th width="120px" class="text-center">Cant. Pedida</th>
                    <th width="60px" class="text-center"><i class="fa fa-trash"></i></th>
                  </tr>
                </thead>
                <tbody id="detalles_despacho">
                  {{-- Filas dinámicas vía JavaScript --}}
                </tbody>
              </table>
            </div>
          </div>
          
          <div class="tile-footer bg-white border-top">
            <button class="btn btn-warning text-dark font-weight-bold" type="submit" id="btn-guardar" disabled>
              <i class="fa fa-file-text-o"></i> Enviar Solicitud de Pedido
            </button>
            <a class="btn btn-secondary ml-2" href="{{ route('despacho.index') }}">
                <i class="fa fa-times-circle"></i> Cancelar
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
    var items = 0;

    function agregarProducto() {
        let insumo_id = $('#select_insumo').val();
        let insumo_text = $('#select_insumo option:selected').text();
        let cantidad = parseInt($('#input_cantidad').val());

        if (insumo_id == "" || isNaN(cantidad) || cantidad <= 0) {
            Swal.fire('Atención', 'Seleccione un producto y una cantidad válida mayor a cero.', 'warning');
            return;
        }

        let existe = false;
        $('input[name="id_insumo[]"]').each(function() {
            if ($(this).val() == insumo_id) existe = true;
        });

        if (existe) {
            Swal.fire('Repetido', 'Este producto ya se encuentra agregado en la lista.', 'info');
            return;
        }

        let fila = `
            <tr id="fila_${items}">
                <td>
                    <input type="hidden" name="id_insumo[]" value="${insumo_id}">
                    <span class="font-weight-bold text-dark">${insumo_text}</span>
                </td>
                <td class="text-center">
                    <input type="number" name="cantidad[]" class="form-control form-control-sm text-center font-weight-bold" value="${cantidad}" readonly>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(${items})" title="Quitar">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#detalles_despacho').append(fila);
        items++; 
        verificarBoton();
        
        // Resetear selectores
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

        // Disparar validación inicial por si hay un local precargado
        $('#id_local_origen').trigger('change');

        $('#form-solicitud').on('submit', function(e) {
            e.preventDefault();
            let form = this;

            Swal.fire({
                title: '¿Enviar Solicitud de Pedido?',
                text: "Se registrará la solicitud en estado Pendiente. El almacén de origen será notificado y el inventario no se afectará aún.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#f0ad4e',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa fa-check"></i> Sí, enviar solicitud',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Registrando Solicitud...',
                        text: 'Enviando notificación al almacén.',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading() }
                    });
                    form.submit();
                }
            });
        });

        // Lógica para gestionar origen/destino y carga dinámica de repuestos con stock > 0
        $('#id_local_origen').on('change', function() {
            let origenId = $(this).val();
            let destinoSelect = $('#id_local_destino');
            let insumoSelect = $('#select_insumo');
            
            // 1. Evitar que origen y destino sean iguales
            destinoSelect.find('option').prop('disabled', false);
            
            if (origenId) {
                destinoSelect.find(`option[value="${origenId}"]`).prop('disabled', true);
                if (destinoSelect.val() === origenId) {
                    destinoSelect.val(null).trigger('change');
                }

                // 2. Petición AJAX para obtener solo insumos con stock > 0 en este origen
                $.ajax({
                    url: `/despacho/insumos-por-local/${origenId}`,
                    type: 'GET',
                    dataType: 'json',
                    beforeSend: function() {
                        insumoSelect.empty().append('<option value="">Cargando repuestos disponibles...</option>').trigger('change');
                    },
                    success: function(data) {
                        insumoSelect.empty().append('<option value="">Seleccione un repuesto...</option>');
                        
                        if (data.length === 0) {
                            insumoSelect.append('<option value="" disabled>No hay repuestos con stock disponible en este origen</option>');
                        } else {
                            $.each(data, function(index, item) {
                                let texto = `${item.serial} | ${item.producto} | ${item.descripcion} (Stock: ${item.stock})`;
                                insumoSelect.append(`<option value="${item.id}">${texto}</option>`);
                            });
                        }
                        insumoSelect.trigger('change');
                    },
                    error: function() {
                        insumoSelect.empty().append('<option value="">Error al cargar los repuestos</option>').trigger('change');
                    }
                });
            } else {
                insumoSelect.empty().append('<option value="">Seleccione un origen primero...</option>').trigger('change');
            }
            
            destinoSelect.select2({ width: '100%' });
            insumoSelect.select2({ width: '100%' });
        });
    });
</script>
@endsection