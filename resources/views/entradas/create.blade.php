@extends('layouts.app')

@section('title', 'Cargar Entrada de Almacén')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark"><i class="fas fa-cart-plus mr-2 text-primary"></i>Nueva Entrada</h1>
        </div>
    </div>
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
                                    <option value="{{ $p->id }}">{{ $p->rif }} - {{ $p->nombre }}</option>
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
                            {{-- Fila 1: Buscador de Insumo (Ancho completo) --}}
                            <div class="row">
                                <div class="col-12 form-group">
                                    <label>Seleccionar Insumo</label>
                                    <select id="select_insumo" class="form-control select2" style="width: 100%;">
                                        <option value=""></option>
                                        @foreach($insumos as $i)
                                            <option value="{{ $i->id }}" 
                                                    data-nombre="{{ $i->producto }}" 
                                                    data-costo="{{ $i->costo }}"
                                                    data-descripcion="{{ $i->descripcion }}">
                                                {{ $i->producto }}
                                            </option>
                                        @endforeach
                                    </select>
                                    {{-- Espacio para mostrar la descripción dinámicamente --}}
                                    <div id="info_descripcion" class="mt-2 text-muted" style="display:none;">
                                        <small><strong>Descripción:</strong> <span id="text_descripcion"></span></small>
                                    </div>
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
                                    <label class="d-none d-md-block">&nbsp;</label> {{-- Espaciador para alinear botón en PC --}}
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

    $(document).ready(function() {
        // Inicializar Select2 con Template Personalizado
        $('#select_insumo').select2({
            placeholder: "Busque un insumo por nombre o descripción...",
            allowClear: true,
            templateResult: formatInsumo, // Cómo se ve en la lista desplegable
        });

        // Función para dar formato a las opciones en la lista
        function formatInsumo (insumo) {
            if (!insumo.id) { return insumo.text; } // Si es el placeholder

            // Obtenemos la descripción desde el data-attribute
            let descripcion = $(insumo.element).data('descripcion') || 'Sin descripción';
            
            // Creamos un diseño HTML para la opción
            let $insumo = $(
                '<span>' +
                    '<strong class="text-primary">' + insumo.text + '</strong><br>' +
                    '<small class="text-muted"><i class="fas fa-info-circle mr-1"></i>' + descripcion + '</small>' +
                '</span>'
            );
            
            return $insumo;
        };
        // Al elegir un insumo, sugerir su costo actual
        $('#select_insumo').on('change', function() {
            let costo = $(this).find(':selected').data('costo');
            $('#input_costo').val(costo);
            $('#input_cantidad').focus();
        });

        // Botón Agregar Item
        $('#btnAgregarItem').click(function() {
            let id_insumo = $('#select_insumo').val();
            let nombre = $('#select_insumo').find(':selected').data('nombre');
            let cantidad = parseFloat($('#input_cantidad').val());
            let costo = parseFloat($('#input_costo').val());

            if (!id_insumo || isNaN(cantidad) || cantidad <= 0 || isNaN(costo)) {
                Swal.fire('Atención', 'Por favor complete los datos del insumo correctamente.', 'warning');
                return;
            }

            let subtotal = cantidad * costo;
            
            // Construir fila
            let fila = `
                <tr id="fila_${contador}">
                    <td>
                        <input type="hidden" name="items[${contador}][id_insumo]" value="${id_insumo}">
                        ${nombre}
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
            
            // Actualizar Totales
            actualizarTotal(subtotal);
            
            // Limpiar campos
            $('#select_insumo').val(null).trigger('change');
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