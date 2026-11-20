@extends('layouts.app')

@section('title', 'Cargar Entrada de Almacén')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark"><i class="fas fa-cart-plus mr-2 text-primary"></i>Nueva Entrada</h1>
        </div>
    </div>
@endsection

@section('css')
<style>
    /* Forzar la altura y padding del Select2 para que coincida con los form-control */
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
        background-color: #38bdf8 !important;
        color: #0f172a !important;
    }

    /* --- ESTILOS ERGONÓMICOS PARA EL BUSCADOR DE INSUMOS --- */
    #resultados-busqueda {
        max-height: 380px;
        overflow-y: auto;
        border: 1px solid #94a3b8;
        border-radius: 0.375rem;
        background-color: #ffffff;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
    }
    #resultados-busqueda .list-group-item {
        color: #0f172a;
        border-left: none;
        border-right: none;
        border-bottom: 1px solid #e2e8f0;
        padding: 12px 16px;
        transition: background-color 0.15s ease-in-out;
    }
    #resultados-busqueda .list-group-item:hover {
        background-color: #f1f5f9;
    }
    
    /* Jerarquía visual clara para descansar la vista */
    .item-descripcion-principal {
        font-size: 1.1rem; /* Texto principal grande y destacado */
        font-weight: 700;
        color: #0f172a;
        line-height: 1.35;
    }
    .item-meta-secundaria {
        font-size: 0.85rem;
        color: #475569;
        font-weight: 500;
    }
    /* Badge de serial limpio, legible y sin fatiga visual */
    .badge-serial-limpio {
        background-color: #f1f5f9;
        color: #1e293b;
        font-weight: 600;
        font-size: 0.8rem;
        padding: 4px 8px;
        border-radius: 4px;
        border: 1px solid #cbd5e1;
        letter-spacing: 0.5px;
    }
</style>
@endsection
@section('content')
    @include('layouts.partials.flash-messages')

    <form action="{{ route('entradas.store') }}" method="POST" id="formEntrada">
        @csrf
        <div class="row">
            {{-- Columna Izquierda: Datos Maestros --}}
            <div class="col-md-4">
                <div class="card card-outline card-primary shadow">
                    <div class="card-header">
                        <h3 class="card-title text-bold">Información de Compra</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Nro. Orden / Factura</label>
                            <input type="text" name="nro_orden_entrega" class="form-control" placeholder="Ej: FAC-001">
                        </div>
                        <div class="form-group">
                            <label>Proveedor <span class="text-danger">*</span></label>
                            <select name="id_proveedor" class="form-control select2" required>
                                <option value=""></option>
                                @foreach($proveedores as $p)
                                    <option value="{{ $p->id }}">{{ $p->rif }} - {{$p->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Depósito Destino <span class="text-danger">*</span></label>
                            <select name="id_local" class="form-control select2" required>
                                @foreach($depositos as $d)
                                    <option value="{{ $d->id }}">{{ $d->nombre }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Solo locales tipo DEPOSITO autorizados.</small>
                        </div>

                        <div class="form-group">
                            <label>Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="3" placeholder="Ej: Factura #1234 - Compra de emergencia"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Card de Totales --}}
                <div class="card bg-gradient-dark shadow">
                    <div class="card-body text-center">
                        <h5>TOTAL A CARGAR</h5>
                        <h2 class="text-orange">$ <span id="total_mostrado">0.00</span></h2>
                        <input type="hidden" name="total_usd" id="total_input" value="0">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg shadow" id="btnGuardar" disabled>
                    <i class="fas fa-save mr-2"></i> PROCESAR CARGA
                </button>
            </div>

            {{-- Columna Derecha: Selector de Productos --}}
            <div class="col-md-8">
                <div class="card card-outline card-success shadow">
                    <div class="card-header">
                        <h3 class="card-title text-bold">Agregar Insumos</h3>
                    </div>
                    <div class="card-body">
                        <div class="border-bottom pb-3 mb-3">
                            {{-- Fila 1: Buscador Flotante de Insumo con Botón de Actualizar --}}
                            <div class="row">
                                <div class="col-12 form-group position-relative">
                                    <label class="d-flex justify-content-between align-items-center">
                                        <span>Buscar Insumo / Repuesto</span>
                                        <button type="button" class="btn btn-tool btn-sm text-primary p-0" id="btnActualizarInsumos" title="Actualizar lista de insumos sin perder datos">
                                            <i class="fas fa-sync-alt mr-1"></i> <small>Actualizar inventario</small>
                                        </button>
                                    </label>
                                    <input type="text" id="buscador" class="form-control" placeholder="Escribe la descripción, producto o serial..." autocomplete="off">
                                    <input type="hidden" id="select_insumo_id" value="">
                                    <input type="hidden" id="select_insumo_nombre" value="">
                                    <input type="hidden" id="select_insumo_descripcion" value="">
                                    <div id="resultados-busqueda" class="list-group position-absolute w-100 shadow" style="z-index: 1000; display:none;"></div>
                                </div>
                            </div>

                            {{-- Fila 2: Cantidad, Costo y Botón --}}
                            <div class="row">
                                <div class="col-md-4 col-6 form-group">
                                    <label>Cantidad</label>
                                    <input type="number" id="input_cantidad" class="form-control" step="0.01" min="0.1" placeholder="0.00">
                                </div>
                                <div class="col-md-4 col-6 form-group">
                                    <label>Costo Unit. ($)</label>
                                    <input type="number" id="input_costo" class="form-control" step="0.01" min="0" placeholder="0.00">
                                </div>
                                <div class="col-md-4 col-12 form-group">
                                    <label class="d-none d-md-block">&nbsp;</label>
                                    <button type="button" class="btn btn-success btn-block" id="btnAgregarItem">
                                        <i class="fas fa-plus mr-1"></i> Añadir
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-sm" id="tabla_items">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Insumo</th>
                                        <th width="15%">Cant.</th>
                                        <th width="20%">Costo U.</th>
                                        <th width="20%">Subtotal</th>
                                        <th width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Aquí caerán los items dinámicos --}}
                                </tbody>
                            </table>
                            <div id="vacio_msg" class="text-center py-4 text-muted">
                                <i class="fas fa-box-open fa-3x mb-2"></i>
                                <p>No hay productos añadidos</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('scripts')
<script>
    let contador = 0;
    let totalGeneral = 0;

    // Cargar los insumos directamente desde PHP con json 
    let insumosDisponibles = @json($insumos);

    function seleccionarInsumo(id) {
        let item = insumosDisponibles.find(i => i.id == id);
        if (!item) return;

        let costoStr = item.costo ? parseFloat(item.costo).toFixed(2) : '0.00';
        let descripcionTexto = item.descripcion ? item.descripcion : item.producto;
        // Formato claro para el input principal al seleccionarlo
        let textoItem = `[${item.serial}] ${descripcionTexto} - ${item.producto} (Costo: $${costoStr})`;
        
        $('#select_insumo_id').val(item.id);
        $('#select_insumo_nombre').val(item.producto);
        $('#select_insumo_descripcion').val(item.descripcion || '');
        $('#buscador').val(textoItem);
        $('#resultados-busqueda').hide();

        // Sugerir costo y enfocar cantidad
        $('#input_costo').val(item.costo || 0);
        $('#input_cantidad').focus();
    }

    $(document).ready(function() {
        $('.select2').select2({
            width: '100%'
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#buscador, #resultados-busqueda').length) {
                $('#resultados-busqueda').hide();
            }
        });

        // Autocompletado optimizado con jerarquía visual ergonómica
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
                html = '<div class="list-group-item text-muted py-3 text-center">No se encontraron insumos coincidentes</div>';
            } else {
                filtrados.forEach(item => {
                    let costoVal = item.costo ? parseFloat(item.costo).toFixed(2) : '0.00';
                    
                    // Inversión de jerarquía: Descripción arriba y grande, Producto/Categoría y Serial abajo
                    let textoPrincipal = item.descripcion ? item.descripcion : item.producto;
                    let textoSecundario = item.descripcion ? item.producto : '';

                    html += `<a href="#" class="list-group-item list-group-item-action" onclick="seleccionarInsumo(${item.id}); return false;">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div class="item-descripcion-principal">${textoPrincipal}</div>
                                    <span class="badge badge-success px-2 py-1 font-weight-bold" style="font-size: 0.9rem;">$${costoVal}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="item-meta-secundaria">
                                        ${textoSecundario ? '<span class="text-primary font-weight-bold mr-2"><i class="fas fa-tag mr-1"></i>' + textoSecundario + '</span>' : ''}
                                    </div>
                                    <span class="badge-serial-limpio">
                                        <i class="fas fa-barcode mr-1 text-muted"></i>${item.serial}
                                    </span>
                                </div>
                             </a>`;
                });
            }
            $('#resultados-busqueda').html(html).show();
        });
        // Actualizador en vivo de insumos por AJAX sin perder datos de la tabla
        $('#btnActualizarInsumos').click(function() {
            let $btn = $(this);$btn.find('i').addClass('fa-spin');

            $.ajax({
                url: "{{ route('entradas.insumos_json') }}",
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    insumosDisponibles = response; // Actualiza la lista en memoria con lo recién registrado en otra pestaña
                    $btn.find('i').removeClass('fa-spin');
                    
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Inventario actualizado correctamente',
                        showConfirmButton: false,
                        timer: 2000
                    });
                },
                error: function() {
                    $btn.find('i').removeClass('fa-spin');
                    Swal.fire('Error', 'No se pudo actualizar la lista de insumos.', 'error');
                }
            });
        });
        // Botón Agregar Item
        $('#btnAgregarItem').click(function() {
            let id_insumo = $('#select_insumo_id').val();
            let nombre = $('#select_insumo_nombre').val();
            let descripcion = $('#select_insumo_descripcion').val() || '';
            let cantidad = parseFloat($('#input_cantidad').val());
            let costo = parseFloat($('#input_costo').val());

            if (!id_insumo || isNaN(cantidad) || cantidad <= 0 || isNaN(costo)) {
                Swal.fire('Atención', 'Por favor seleccione un insumo válido del buscador y complete la cantidad y costo correctamente.', 'warning');
                return;
            }

            let subtotal = cantidad * costo;
            
            let htmlDescripcion = descripcion ? `<br><small class="text-muted"><i class="fas fa-info-circle mr-1"></i>${descripcion}</small>` : '';

            let fila = `
                <tr id="fila_${contador}">
                    <td>
                        <input type="hidden" name="items[${contador}][id_insumo]" value="${id_insumo}">
                        <strong>${nombre}</strong>
                        ${htmlDescripcion}
                    </td>
                    <td>
                        <input type="hidden" name="items[${contador}][cantidad]" value="${cantidad}">
                        ${cantidad}
                    </td>
                    <td>
                        <input type="hidden" name="items[${contador}][costo_unitario]" value="${costo}">
                        $ ${costo.toFixed(2)}
                    </td>
                    <td class="text-bold">
                        $ ${subtotal.toFixed(2)}
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-xs" onclick="eliminarFila(${contador}, ${subtotal})">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                </tr>
            `;

            $('#tabla_items tbody').append(fila);
            $('#vacio_msg').hide();
            
            actualizarTotal(subtotal);
            
            // Limpiar campos y buscador
            $('#buscador').val('');
            $('#select_insumo_id').val('');
            $('#select_insumo_nombre').val('');
            $('#select_insumo_descripcion').val('');
            $('#input_cantidad').val('');
            $('#input_costo').val('');
            contador++;
            evaluarBoton();
        });
    });

    function actualizarTotal(monto) {
        totalGeneral += monto;
        $('#total_mostrado').text(totalGeneral.toFixed(2));
        $('#total_input').val(totalGeneral);
    }

    function eliminarFila(index, subtotal) {
        $(`#fila_${index}`).remove();
        totalGeneral -= subtotal;
        $('#total_mostrado').text(totalGeneral.toFixed(2));
        $('#total_input').val(totalGeneral);
        
        if ($('#tabla_items tbody tr').length === 0) {
            $('#vacio_msg').show();
        }
        evaluarBoton();
    }

    function evaluarBoton() {
        if ($('#tabla_items tbody tr').length > 0) {
            $('#btnGuardar').prop('disabled', false);
        } else {
            $('#btnGuardar').prop('disabled', true);
        }
    }
</script>
@endsection