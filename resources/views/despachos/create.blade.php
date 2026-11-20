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
                        <option value="{{ $local->id }}"
                            {{ (string) old('id_local_origen') === (string) $local->id ? 'selected' : '' }}>
                            {{ $local->nombre }} ({{ $local->tipo }})
                        </option>
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
                    <option value="{{ $local->id }}"
                        {{ (string) old('id_local_destino') === (string) $local->id ? 'selected' : '' }}>
                        {{ $local->nombre }}
                    </option>
                @endforeach
              </select>
            </div>

            <div class="form-group">
              <label><b>Transportado por:</b> <b class="text-danger">*</b></label>
              <input class="form-control" type="text" name="transportado_por" value="{{ old('transportado_por') }}" placeholder="Nombre del chofer" required>
            </div>

            <div class="form-group">
              <label><b>Vehículo / Placa</b></label>
              <input class="form-control" type="text" name="vehiculo_placa" value="{{ old('vehiculo_placa') }}" placeholder="Ej: Toyota Blanca - AB123">
            </div>

            <div class="form-group">
              <label><b>Observaciones</b></label>
              <textarea class="form-control" name="observacion" rows="2" placeholder="Notas adicionales del envío...">{{ old('observacion') }}</textarea>
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
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="mb-0"><b>Buscar Insumo / Repuesto</b></label>
                    <button type="button" id="btnActualizarInsumos" class="btn btn-outline-primary btn-xs py-0 px-2" title="Sincronizar stock disponible del origen" disabled>
                      <i class="fa fa-sync-alt"></i> Actualizar inventario
                    </button>
                  </div>
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
                    <th width="130px" class="text-center">Stock Disp.</th>
                    <th width="130px" class="text-center">Cant.</th>
                    <th width="60px" class="text-center"><i class="fa fa-trash"></i></th>
                  </tr>
                </thead>
                <tbody id="detalles_despacho">
                    @foreach($detallesAnteriores as $detalle)
                        <tr id="fila_{{ $loop->index }}" data-insumo-id="{{ $detalle['id'] }}" >
                            <td class="align-middle">
                                <input type="hidden" name="id_insumo[]" value="{{ $detalle['id'] }}">

                                <div class="font-weight-bold text-dark">
                                    {{ $detalle['producto'] }}
                                </div>

                                <small class="text-muted">
                                    <strong>[{{ $detalle['serial'] }}]</strong>

                                    @if($detalle['descripcion'] !== '')
                                        - {{ $detalle['descripcion'] }}
                                    @endif
                                </small>
                            </td>

                            <td class="text-center align-middle celda-stock">
                                <span class="badge badge-secondary px-2 py-1 badge-stock" style="font-size: 0.9rem;">
                                    Pendiente
                                </span>

                                <div class="small text-danger font-weight-bold mt-1 alerta-stock" style="display:none;"></div>
                            </td>

                            <td class="text-center align-middle">
                                <input type="number" name="cantidad[]" class="form-control form-control-sm text-center font-weight-bold input-cantidad-tabla" min="1" step="1" value="{{ $detalle['cantidad'] }}" required>
                            </td>

                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila({{ $loop->index }})" title="Quitar">
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
    var items = {{ count($detallesAnteriores) }};
    let insumosDisponibles = []; // Almacenará los repuestos del local seleccionado
    let origenActualId = '';     // Guarda el origen activo para revertir si el usuario cancela el cambio
    let inventarioVerificado = false;
    function agregarProducto() {
        let insumo_id = $('#select_insumo_id').val();
        let cantidad = parseInt($('#input_cantidad').val());

        if (insumo_id == "" || isNaN(cantidad) || cantidad <= 0) {
            Swal.fire('Atención', 'Seleccione un producto válido del buscador y una cantidad mayor a cero.', 'warning');
            return;
        }

        let itemSeleccionado = insumosDisponibles.find(i => i.id == insumo_id);
        if (!itemSeleccionado) {
            Swal.fire('Atención', 'El insumo seleccionado ya no está disponible en el inventario actualizado.', 'warning');
            return;
        }

        if (cantidad > itemSeleccionado.stock) {
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
            <tr id="fila_${items}" data-insumo-id="${itemSeleccionado.id}">
                <td class="align-middle">
                    <input type="hidden" name="id_insumo[]" value="${itemSeleccionado.id}">
                    <div class="font-weight-bold text-dark">${itemSeleccionado.producto}</div>
                    <small class="text-muted">
                        <strong>[${itemSeleccionado.serial}]</strong> ${itemSeleccionado.descripcion ? '- ' + itemSeleccionado.descripcion : ''}
                    </small>
                </td>
                <td class="text-center align-middle celda-stock">
                    <span class="badge badge-success px-2 py-1 badge-stock" style="font-size: 0.9rem;">${itemSeleccionado.stock}</span>
                    <div class="small text-danger font-weight-bold mt-1 alerta-stock" style="display:none;"></div>
                </td>
                <td class="text-center align-middle">
                    <input type="number" name="cantidad[]" 
                           class="form-control form-control-sm text-center font-weight-bold input-cantidad-tabla" 
                           min="1" max="${itemSeleccionado.stock}" value="${cantidad}" required>
                </td>
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(${items})" title="Quitar">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#detalles_despacho').append(fila);
        items++;
        evaluarEstadoTabla();

        // Resetear buscador y cantidad
        $('#buscador').val('');
        $('#select_insumo_id').val('');
        $('#input_cantidad').val(1);
    }

    function seleccionarInsumo(id) {
        let item = insumosDisponibles.find(i => i.id == id);
        if (!item) return;

        let textoItem = `${item.serial} | ${item.producto} ${item.descripcion ? '| ' + item.descripcion : ''} (Stock: ${item.stock})`;
        $('#select_insumo_id').val(item.id);
        $('#buscador').val(textoItem);
        $('#resultados-busqueda').hide();
        $('#input_cantidad').attr('max', item.stock).focus();
    }

    function eliminarFila(index) {
        $(`#fila_${index}`).remove();
        evaluarEstadoTabla();
    }

    /**
     * Evalúa todas las filas de la tabla contra insumosDisponibles y habilita/deshabilita #btn-guardar
     */
    function evaluarEstadoTabla() {
        let filas = $('#detalles_despacho tr');
        if (!inventarioVerificado) {
            $('#btn-guardar').prop('disabled', true);

            // Impedir que el submit interprete el estado pendiente
            // como una tabla sin conflictos.
            return filas.length > 0 ? filas.length : 1;
        }
        if (filas.length === 0) {
            $('#btn-guardar').prop('disabled', true);
            return 0;
        }

        let filasConError = 0;

        filas.each(function() {
            let $fila = $(this);
            let insumoId = $fila.data('insumo-id');
            let $inputCant = $fila.find('.input-cantidad-tabla');
            let $badgeStock = $fila.find('.badge-stock');
                $badgeStock.removeClass('badge-secondary');
            let $alertaStock = $fila.find('.alerta-stock');

            let cantIngresada = parseInt($inputCant.val());
            let itemActual = insumosDisponibles.find(i => i.id == insumoId);
            let stockActual = itemActual ? parseInt(itemActual.stock) : 0;

            // Actualizar badge de stock y atributo max del input editable
            $badgeStock.text(stockActual);
            $inputCant.attr('max', stockActual > 0 ? stockActual : 1);

            if (stockActual <= 0) {
                $fila.addClass('table-danger');
                $badgeStock.removeClass('badge-success badge-warning').addClass('badge-danger');
                $alertaStock.text('Sin stock disponible').show();
                $inputCant.addClass('is-invalid');
                filasConError++;
            } else if (isNaN(cantIngresada) || cantIngresada <= 0) {
                $fila.addClass('table-danger');
                $badgeStock.removeClass('badge-danger badge-warning').addClass('badge-success');
                $alertaStock.text('Cantidad inválida').show();
                $inputCant.addClass('is-invalid');
                filasConError++;
            } else if (cantIngresada > stockActual) {
                $fila.addClass('table-danger');
                $badgeStock.removeClass('badge-success').addClass('badge-warning text-dark');
                $alertaStock.text(`Excede stock (Máx: ${stockActual})`).show();
                $inputCant.addClass('is-invalid');
                filasConError++;
            } else {
                $fila.removeClass('table-danger');
                $badgeStock.removeClass('badge-danger badge-warning text-dark').addClass('badge-success');
                $alertaStock.hide().text('');
                $inputCant.removeClass('is-invalid');
            }
        });

        $('#btn-guardar').prop('disabled', filasConError > 0);
        return filasConError;
    }

    /**
     * Consulta los insumos por local via AJAX.
     * @param {string|number} origenId
     * @param {boolean} esSincronizacionManual - true cuando se pulsa "Actualizar inventario"
     */
    function cargarInsumosDeOrigen(origenId,esSincronizacionManual = false,silencioso = false) {
        let buscador = $('#buscador');
        let $btnSync = $('#btnActualizarInsumos');
            inventarioVerificado = false;
        $('#btn-guardar').prop('disabled', true);
        if (!origenId) {
            buscador.prop('disabled', true).val('Seleccione un origen primero...');
            $btnSync.prop('disabled', true);
            insumosDisponibles = [];
            return;
        }

        let url = "{{ route('despacho.insumosPorLocal', ':id') }}".replace(':id', origenId);

        return $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            beforeSend: function() {
                $btnSync.prop('disabled', true).find('i').addClass('fa-spin');
                if (esSincronizacionManual && !silencioso) {
                    buscador.prop('disabled', true).val('Cargando repuestos disponibles...');
                    insumosDisponibles = [];
                }
            },
            success: function(data) {
                insumosDisponibles = data;
                inventarioVerificado = true;
                $btnSync.prop('disabled', false).find('i').removeClass('fa-spin');

                if (data.length === 0) {
                    buscador.prop('disabled', true).val('No hay repuestos con stock disponible en este origen');
                } else {
                    buscador.prop('disabled', false);
                    if (!esSincronizacionManual) {
                        buscador.val('').attr('placeholder', 'Escribe el serial o nombre del insumo...');
                    }
                }

                // Reconciliar las filas ya cargadas en la tabla con el nuevo stock
                let conflictos = evaluarEstadoTabla();

                if (esSincronizacionManual) {
                    if (conflictos > 0) {
                        Swal.fire({
                            title: 'Inventario Actualizado con Alertas',
                            text: `Se detectaron ${conflictos} producto(s) en tu lista cuyo stock disponible disminuyó por debajo de la cantidad asignada. Revisa las filas marcadas en rojo.`,
                            icon: 'warning',
                            confirmButtonText: 'Entendido'
                        });
                    } else {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Stock sincronizado correctamente',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    }
                }
            },
            error: function() {
                inventarioVerificado = false;
                $('#btn-guardar').prop('disabled', true);
                $btnSync.prop('disabled', false)
                    .find('i').removeClass('fa-spin');

                if (!esSincronizacionManual) {
                    buscador.val('Error al cargar los repuestos');
                } else if (!silencioso) {
                    Swal.fire(
                        'Error',
                        'No se pudo sincronizar el inventario del local.',
                        'error'
                    );
                }
            }
        });
    }

    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });

        // Ocultar resultados si se hace clic fuera
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#buscador, #resultados-busqueda').length) {
                $('#resultados-busqueda').hide();
            }
        });

        // Botón de sincronización manual sin perder la tabla cargada
        $('#btnActualizarInsumos').on('click', function() {
            let origenId = $('#id_local_origen').val();
            if (!origenId) return;
            cargarInsumosDeOrigen(origenId, true);
        });

        // Revalidar en tiempo real cuando el usuario edita directamente una cantidad en la tabla
        $(document).on('input change', '.input-cantidad-tabla', function() {
            evaluarEstadoTabla();
        });

        // Autocompletado local filtrando los datos del origen seleccionado
        $('#buscador').on('keyup', function() {
            let q = $(this).val().toLowerCase();
            if (q.length < 2) {
                $('#resultados-busqueda').hide();
                return;
            }

            let palabras = q.split(/\s+/).filter(Boolean);

            let filtrados = insumosDisponibles.filter(item => {
                let serial = (item.serial || '').toLowerCase();
                let producto = (item.producto || '').toLowerCase();
                let descripcion = (item.descripcion || '').toLowerCase();

                return palabras.every(palabra =>
                    serial.includes(palabra) ||
                    producto.includes(palabra) ||
                    descripcion.includes(palabra)
                );
            });

            let html = '';
            if (filtrados.length === 0) {
                html = '<div class="list-group-item text-muted">No se encontraron repuestos</div>';
            } else {
                filtrados.forEach(item => {
                    html += `<a href="#" class="list-group-item list-group-item-action" onclick="seleccionarInsumo(${item.id}); return false;">
                                <strong>[${item.serial}]</strong> ${item.producto} ${item.descripcion ? '- ' + item.descripcion : ''}
                                <span class="badge badge-success float-right">Stock: ${item.stock}</span>
                             </a>`;
                });
            }
            $('#resultados-busqueda').html(html).show();
        });

        // Gestión de cambio de Origen (protegiendo filas ya cargadas)
        $('#id_local_origen').on('change', function() {
            let nuevoOrigenId = $(this).val();
            let filasActuales = $('#detalles_despacho tr').length;

            if (filasActuales > 0 && origenActualId && nuevoOrigenId !== origenActualId) {
                Swal.fire({
                    title: '¿Cambiar Local de Origen?',
                    text: 'Ya tienes repuestos cargados en la lista. Si cambias el origen se limpiará la tabla actual.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, cambiar y limpiar',
                    cancelButtonText: 'Mantener origen actual'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#detalles_despacho').empty();
                        evaluarEstadoTabla();
                        aplicarCambioOrigen(nuevoOrigenId);
                    } else {
                        $(this).val(origenActualId).trigger('change.select2');
                    }
                });
                return;
            }

            aplicarCambioOrigen(nuevoOrigenId);
        });

        function aplicarCambioOrigen(origenId) {
            origenActualId = origenId;
            let destinoSelect = $('#id_local_destino');

            destinoSelect.find('option').prop('disabled', false);

            if (origenId) {
                destinoSelect.find(`option[value="${origenId}"]`).prop('disabled', true);
                if (destinoSelect.val() === origenId) {
                    destinoSelect.val(null).trigger('change');
                }
            }

            destinoSelect.select2({ width: '100%' });
            cargarInsumosDeOrigen(origenId, false);
        }

        // Disparar carga inicial por si hay un local precargado (ej. rol encargado)
        if ($('#id_local_origen').val()) {
            aplicarCambioOrigen($('#id_local_origen').val());
        }

        $('#form-despacho').on('submit', async function(e) {
            e.preventDefault();

            const form = this;
            const $form = $(form);

            // Evitar dos procesos de envío simultáneos.
            if ($form.data('procesando')) {
                return;
            }

            const origenId = $('#id_local_origen').val();

            if (!origenId) {
                Swal.fire(
                    'Origen requerido',
                    'Seleccione un local de origen.',
                    'warning'
                );
                return;
            }

            if ($('#detalles_despacho tr').length === 0) {
                Swal.fire(
                    'Sin productos',
                    'Agregue al menos un producto al despacho.',
                    'warning'
                );
                return;
            }

            $form.data('procesando', true);

            let enviado = false;
            let inventarioVerificado = false;

            // Conservar el estado previo de los controles para restaurarlo.
            const controles = $form
                .find('input, select, textarea, button')
                .toArray()
                .map(elemento => ({
                    elemento,
                    disabled: elemento.disabled
                }));

            try {
                const resultado = await Swal.fire({
                    title: '¿Procesar Despacho?',
                    text: 'Se verificará el stock actual antes de registrar la salida.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#009688',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, verificar y procesar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                });

                if (!resultado.isConfirmed) {
                    return;
                }

                // Evitar cambios en origen, filas o cantidades durante la consulta.
                controles.forEach(({ elemento }) => {
                    elemento.disabled = true;
                });

                Swal.fire({
                    title: 'Verificando inventario...',
                    text: 'Consultando el stock actual del origen.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                try {
                    // true: conserva buscador e inventario durante la sincronización.
                    // true: no muestra las alertas de sincronización manual.
                    await cargarInsumosDeOrigen(origenId, true, true);

                    inventarioVerificado = true;
                } catch (error) {
                    Swal.fire(
                        'No se pudo verificar el stock',
                        'El despacho no fue enviado. Intente nuevamente.',
                        'error'
                    );
                    return;
                }

                const conflictos = evaluarEstadoTabla();

                if (conflictos > 0) {
                    Swal.fire({
                        title: 'Revisa las cantidades',
                        text: `Hay ${conflictos} producto(s) con cantidades inválidas o stock insuficiente. La tabla conserva tus cantidades para corregirlas.`,
                        icon: 'warning',
                        confirmButtonText: 'Corregir'
                    });
                    return;
                }

                // Los controles disabled no deben permanecer así al enviar.
                controles.forEach(({ elemento, disabled }) => {
                    elemento.disabled = disabled;
                });

                // El stock actualizado pudo modificar los límites max.
                if (!form.reportValidity()) {
                    Swal.close();
                    return;
                }

                Swal.fire({
                    title: 'Generando Despacho...',
                    text: 'Registrando la salida de mercancía.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Evita reingresar al manejador submit.
                HTMLFormElement.prototype.submit.call(form);
                enviado = true;

            } finally {
                if (!enviado) {
                    controles.forEach(({ elemento, disabled }) => {
                        elemento.disabled = disabled;
                    });

                    $form.data('procesando', false);

                    // Si no se pudo consultar el inventario, permitir reintentar.
                    if (inventarioVerificado) {
                        evaluarEstadoTabla();
                    }
                }
            }
        });
    });
</script>
@endsection