@extends('layouts.app')

@section('title') Nuevo Despacho @endsection

@section('content')
<main class="app-content">
  {{-- VERIFICACIÓN DE PERMISO PARA CREAR --}}
  @cannot('crear-despacho')
    <div class="tile text-center shadow-sm py-5">
        <h1 class="text-danger mb-3"><i class="fa fa-lock fa-2x"></i></h1>
        <h3 class="text-danger">Acceso Restringido</h3>
        <p class="text-muted">No tienes permisos para registrar salidas de mercancía en el sistema.</p>
        <a href="{{ route('despacho.index') }}" class="btn btn-primary mt-2">
            <i class="fa fa-arrow-left"></i> Volver al listado
        </a>
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
      <li class="breadcrumb-item active">Nuevo</li>
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
  <form action="{{ route('despacho.store') }}" method="POST" id="form-despacho">
    @csrf
    <div class="row">
      {{-- PANEL IZQUIERDO: DATOS DE CABECERA --}}
      <div class="col-md-4">
        <div class="tile shadow-sm">
          <h3 class="tile-title"><i class="fa fa-info-circle text-primary"></i> Datos del Envío</h3>
          <div class="tile-body">
            <div class="form-group">
              <label><b>Código de Despacho</b></label>
              <input class="form-control bg-light font-weight-bold text-primary" type="text" name="codigo" value="{{ $codigo }}" readonly>
            </div>

            <div class="form-group">
              <label><b>Origen (Donde sale)</b> <b class="text-danger">*</b></label>
              @can('seleccionar-cualquier-origen')
                <select name="id_local_origen" id="id_local_origen" class="form-control select2" required>
                    <option value="">Seleccione origen...</option>
                    @foreach($localesOrigen as $local)
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
                @foreach($localesDestino as $local)
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
              <textarea class="form-control" name="observacion" rows="2" placeholder="Notas adicionales del envío..."></textarea>
            </div>
          </div>
        </div>
      </div>

      {{-- PANEL DERECHO: SELECCIÓN DE PRODUCTOS --}}
      <div class="col-md-8">
        <div class="tile shadow-sm">
          <h3 class="tile-title"><i class="fa fa-cogs text-primary"></i> Cargar Repuestos</h3>
          <div class="tile-body">
            <div class="row align-items-end">
              <div class="col-md-7">
                <div class="form-group mb-md-0 position-relative">
                  <label><b>Buscar Insumo / Repuesto</b></label>
                  <input type="text" id="buscador" class="form-control" placeholder="Escribe el serial o nombre del insumo..." autocomplete="off" disabled>
                  <input type="hidden" id="select_insumo_id" value="">
                  <div id="resultados-busqueda" class="list-group position-absolute w-100 shadow" style="z-index: 1000; display:none;"></div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group mb-md-0">
                  <label><b>Cantidad</b></label>
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
                    <th width="120px" class="text-center">Cant.</th>
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
            <button class="btn btn-primary" type="submit" id="btn-guardar" disabled>
              <i class="fa fa-check-circle"></i> Procesar Despacho
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
    let insumosDisponibles = []; // Almacenará los repuestos del local seleccionado

    function agregarProducto() {
        let insumo_id = $('#select_insumo_id').val();
        let insumo_text = $('#buscador').val();
        let cantidad = parseInt($('#input_cantidad').val());

        if (insumo_id == "" || insumo_text == "" || isNaN(cantidad) || cantidad <= 0) {
            Swal.fire('Atención', 'Seleccione un producto válido del buscador y una cantidad mayor a cero.', 'warning');
            return;
        }

        // Validar stock disponible
        let itemSeleccionado = insumosDisponibles.find(i => i.id == insumo_id);
        if (itemSeleccionado && cantidad > itemSeleccionado.stock) {
            Swal.fire('Stock Insuficiente', `La cantidad solicitada supera el stock disponible (${itemSeleccionado.stock}).`, 'error');
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

        let textoItem = `${item.serial} | ${item.producto} | ${item.descripcion} (Stock: ${item.stock})`;
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

        // Ocultar resultados si se hace clic fuera
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

        // Disparar validación inicial por si hay un local precargado
        $('#id_local_origen').trigger('change');

        $('#form-despacho').on('submit', function(e) {
            e.preventDefault();
            let form = this;

            Swal.fire({
                title: '¿Procesar Despacho?',
                text: "Se generará la salida oficial de mercancía y se actualizará el inventario.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#009688',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa fa-check"></i> Sí, procesar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Generando Despacho...',
                        text: 'Registrando inventario y notificando sucursal de destino.',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading() }
                    });
                    form.submit();
                }
            });
        });

        // Lógica para gestionar origen/destino y carga de repuestos
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
                            buscador.val('No hay repuestos con stock disponible en este origen');
                        } else {
                            buscador.prop('disabled', false).val('').attr('placeholder', 'Escribe el serial o nombre del insumo...');
                        }
                    },
                    error: function() {
                        buscador.val('Error al cargar los repuestos');
                    }
                });
            } else {
                buscador.prop('disabled', true).val('Seleccione un origen primero...');
                insumosDisponibles = [];
            }
            
            destinoSelect.select2({ width: '100%' });
        });
    });
</script>
@endsection