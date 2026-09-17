@extends('layouts.app')

@section('title') Editar Solicitud de Pedido @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-pencil-square-o"></i> Editar Solicitud de Pedido</h1>
      <p>Modificación de Solicitud Pendiente | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('despacho.index') }}">Logística</a></li>
      <li class="breadcrumb-item active">Editar Solicitud</li>
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

  <form action="{{ route('despacho.solicitud.update', $despacho->id) }}" method="POST" id="form-solicitud">
    @csrf
    @method('PUT')
    
    <div class="row">
      {{-- PANEL IZQUIERDO: CABECERA --}}
      <div class="col-md-4">
        <div class="tile shadow-sm">
          <h3 class="tile-title"><i class="fa fa-info-circle text-primary"></i> Datos de la Solicitud</h3>
          <div class="tile-body">
            <div class="form-group">
              <label><b>Código de Solicitud</b></label>
              <input class="form-control bg-light font-weight-bold text-primary" type="text" name="codigo" value="{{ $despacho->codigo }}" readonly>
            </div>

            <div class="form-group">
              <label><b>Origen (Depósito / Almacén que surte)</b> <b class="text-danger">*</b></label>
              <select name="id_local_origen" id="id_local_origen" class="form-control select2" required>
                  <option value="">Seleccione depósito origen...</option>
                  @foreach($localesOrigen as $local)
                      <option value="{{ $local->id }}" {{ $despacho->id_local_origen == $local->id ? 'selected' : '' }}>
                          {{ $local->nombre }} ({{ $local->tipo }})
                      </option>
                  @endforeach
              </select>
            </div>

            <div class="form-group">
              <label><b>Destino (Tu Local / Sucursal que recibe)</b> <b class="text-danger">*</b></label>
              @if(auth()->user()->role === \App\Models\User::ROLE_ENCARGADO)
                <select class="form-control" disabled>
                    <option value="{{ $despacho->destino->id ?? '' }}">{{ $despacho->destino->nombre ?? 'Sin Local Asignado' }}</option>
                </select>
                <input type="hidden" name="id_local_destino" id="id_local_destino" value="{{ $despacho->id_local_destino }}">
              @else
                <select name="id_local_destino" id="id_local_destino" class="form-control select2" required>
                    <option value="">Seleccione local destino...</option>
                    @foreach($localesDestino as $local)
                      <option value="{{ $local->id }}" {{ $despacho->id_local_destino == $local->id ? 'selected' : '' }}>
                          {{ $local->nombre }}
                      </option>
                    @endforeach
                </select>
              @endif
            </div>

            <div class="form-group">
              <label><b>Observaciones / Justificación</b></label>
              <textarea class="form-control" name="observacion" rows="3">{{ $despacho->observacion }}</textarea>
            </div>
          </div>
        </div>
      </div>

      {{-- PANEL DERECHO: INSUMOS --}}
      <div class="col-md-8">
        <div class="tile shadow-sm">
          <h3 class="tile-title"><i class="fa fa-cogs text-primary"></i> Repuestos Solicitados</h3>
          <div class="tile-body">
            <div class="row align-items-end">
              <div class="col-md-7">
                <div class="form-group mb-md-0 position-relative">
                  <label><b>Buscar Insumo / Repuesto</b></label>
                  <input type="text" id="buscador" class="form-control" placeholder="Escribe el serial o nombre del insumo..." autocomplete="off">
                  <input type="hidden" id="select_insumo_id" value="">
                  <div id="resultados-busqueda" class="list-group position-absolute w-100 shadow" style="z-index: 1000; display:none;"></div>
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
                  {{-- Los detalles existentes se cargarán aquí por JS o PHP --}}
                  @foreach($despacho->detalles as $index => $detalle)
                    <tr id="fila_{{ $index }}">
                        <td>
                            <input type="hidden" name="id_insumo[]" value="{{ $detalle->id_insumo }}">
                            <span class="font-weight-bold text-dark">
                                {{ $detalle->insumos->serial ?? '' }} | {{ $detalle->insumos->producto ?? 'Insumo' }} | {{ $detalle->insumos->descripcion ?? '' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <input type="number" name="cantidad[]" class="form-control form-control-sm text-center font-weight-bold" value="{{ $detalle->cantidad_enviada }}" readonly>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila({{ $index }})" title="Quitar">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
          
          <div class="tile-footer bg-white border-top">
            <button class="btn btn-warning text-dark font-weight-bold" type="submit" id="btn-guardar">
              <i class="fa fa-save"></i> Guardar Cambios en Solicitud
            </button>
            <a class="btn btn-secondary ml-2" href="{{ route('despacho.index') }}">
                <i class="fa fa-times-circle"></i> Cancelar
            </a>
          </div>
        </div>
      </div>
    </div>
  </form>
</main>
@endsection

@section('scripts')
<script>
    // Inicializar el contador de filas basado en los detalles existentes
    var items = {{ $despacho->detalles->count() }};
    let insumosDisponibles = []; // Almacenará los repuestos del depósito origen seleccionado

    function agregarProducto() {
        let insumo_id = $('#select_insumo_id').val();
        let insumo_text = $('#buscador').val();
        let cantidad = parseInt($('#input_cantidad').val());

        if (insumo_id == "" || insumo_text == "" || isNaN(cantidad) || cantidad <= 0) {
            Swal.fire('Atención', 'Seleccione un producto válido del buscador y una cantidad pedida mayor a cero.', 'warning');
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
        
        // Resetear buscador y cantidad
        $('#buscador').val('');
        $('#select_insumo_id').val('');
        $('#input_cantidad').val(1);
    }

    function seleccionarInsumo(id) {
        let item = insumosDisponibles.find(i => i.id == id);
        if (!item) return;

        let textoItem = `${item.serial} | ${item.producto} | ${item.descripcion} (Stock Origen: ${item.stock})`;
        $('#select_insumo_id').val(item.id);
        $('#buscador').val(textoItem);
        $('#resultados-busqueda').hide();
    }

    function eliminarFila(index) {
        $(`#fila_${index}`).remove();
        verificarBoton();
    }

    function verificarBoton() {
        let totalFilas = $('#detalles_despacho tr').length;
        $('#btn-guardar').prop('disabled', totalFilas === 0);
    }

    $(document).ready(function() {$('.select2').select2({ width: '100%' });
        verificarBoton();

        // Ocultar resultados si se hace clic fuera del buscador
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#buscador, #resultados-busqueda').length) {
                $('#resultados-busqueda').hide();
            }
        });

        // Autocompletado local filtrando los datos del origen seleccionado
        $('#buscador').on('keyup', function() {
            let q = $(this).val().toLowerCase();
            if (q.length < 2) {
                $('#resultados-busqueda').hide();
                return;
            }

            let filtrados = insumosDisponibles.filter(item => 
                item.serial.toLowerCase().includes(q) || 
                item.producto.toLowerCase().includes(q) || 
                item.descripcion.toLowerCase().includes(q)
            );

            let html = '';
            if (filtrados.length === 0) {
                html = '<div class="list-group-item text-muted">No se encontraron repuestos</div>';
            } else {
                filtrados.forEach(item => {
                    html += `<a href="#" class="list-group-item list-group-item-action" onclick="seleccionarInsumo(${item.id}); return false;">
                                <strong>[${item.serial}]</strong> ${item.producto} - ${item.descripcion} 
                                <span class="badge badge-success float-right">Stock: ${item.stock}</span>
                             </a>`;
                });
            }
            $('#resultados-busqueda').html(html).show();
        });

        // Disparar validación inicial para cargar los repuestos según el local origen precargado
        $('#id_local_origen').trigger('change');

        $('#form-solicitud').on('submit', function(e) {
            e.preventDefault();
            let form = this;

            Swal.fire({
                title: '¿Actualizar Solicitud de Pedido?',
                text: "Se guardarán los cambios realizados en la solicitud pendiente.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#f0ad4e',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa fa-check"></i> Sí, actualizar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Actualizando...',
                        text: 'Guardando cambios en el servidor.',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading() }
                    });
                    form.submit();
                }
            });
        });

        // Lógica para gestionar origen/destino y carga dinámica de repuestos del origen
        $('#id_local_origen').on('change', function() {
            let origenId = $(this).val();
            let destinoSelect = $('#id_local_destino');
            let buscador = $('#buscador');
            
            destinoSelect.find('option').prop('disabled', false);
            
            if (origenId) {
                destinoSelect.find(`option[value="${origenId}"]`).prop('disabled', true);
                if (destinoSelect.val() === origenId) {
                    destinoSelect.val(null).trigger('change');
                }

                $.ajax({
                    url: `/despacho/insumos-por-local/${origenId}`,
                    type: 'GET',
                    dataType: 'json',
                    beforeSend: function() {
                        buscador.prop('disabled', true).val('Cargando repuestos disponibles...');
                        insumosDisponibles = [];
                    },
                    success: function(data) {
                        insumosDisponibles = data;
                        if (data.length === 0) {
                            buscador.val('No hay repuestos disponibles en este depósito origen');
                        } else {
                            buscador.prop('disabled', false).val('').attr('placeholder', 'Escribe el serial o nombre del insumo...');
                        }
                    },
                    error: function() {
                        buscador.val('Error al cargar los repuestos');
                    }
                });
            } else {
                buscador.prop('disabled', true).val('Seleccione un depósito origen primero...');
                insumosDisponibles = [];
            }
            
            destinoSelect.select2({ width: '100%' });
        });
    });
</script>
@endsection