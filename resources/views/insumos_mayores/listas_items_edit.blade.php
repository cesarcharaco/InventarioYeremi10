@extends('layouts.app')
@section('title') Realizar Pedido @endsection

@section('content')
<style>
    .row-selected {
        background-color: #d4edda !important; /* Verde claro tipo 'success' */
        transition: background-color 0.3s ease;
    }
</style>
<main class="app-content">
    <div class="app-title">
        <div>
            <h1><i class="fa fa-cart-plus"></i> Lista de Productos al Mayor</h1>
            <p>Monto mínimo requerido: <strong id="montoMinimoLabel">{{ number_format($lista->monto_minimo, 2) }}</strong> $</p>
        </div>
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    {{-- Formulario General --}}
    <form action="{{ route('pedidos.actualizar', $pedido->id) }}" method="POST" id="formPedido">
        @csrf
        @method('PUT') {{-- Fundamental para que Laravel entienda que es una actualización --}}
        
        <input type="hidden" name="lista_id" value="{{ $lista->id }}">

        {{-- Barra de Total fija en la parte superior al hacer scroll (Sticky) --}}
        <div class="tile pb-2" style="position: sticky; top: 10px; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div class="row align-items-center">
                <div class="col-md-6 col-12">
                    <h4 class="mb-2">Total Pedido: <span id="totalPedido">0.00</span> $</h4>
                    <div class="progress" style="height: 10px;">
                        <div id="barraProgreso" class="progress-bar bg-danger" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
                <div class="col-md-6 col-12 text-md-right mt-3 mt-md-0">
                    <button type="submit" class="btn btn-success btn-lg btn-block btn-md-inline">
                        <i class="fa fa-check"></i> Confirmar Pedido
                    </button>
                </div>
            </div>
        </div>

        <div class="tile">
            <div class="form-group mb-3">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                    </div>
                    <input type="text" id="buscarProducto" class="form-control" placeholder="Escriba código o descripción para filtrar productos...">
                </div>
                <small class="text-muted">Mostrando productos que coinciden con su búsqueda.</small>
            </div>

            <div class="table-responsive">
                <table class="table table-hover" id="tablaProductos">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th class="text-right">Precio Unit.</th>
                            <th style="width: 120px;">Cantidad</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            @php
                                // Buscamos usando el nombre de columna correcto de tu base de datos
                                $cantidadPrevia = 0;
                                if (isset($pedido)) {
                                    // Usamos 'insumos_mayores_id' (plural) tal como está en tu esquema
                                    $detalle = $pedido->detalles->where('insumos_mayores_id', $item->id)->first();
                                    $cantidadPrevia = $detalle ? $detalle->cantidad_solicitada : 0;
                                }
                            @endphp
                            
                            <tr class="item-row" 
                                data-codigo="{{ strtolower($item->codigo) }}" 
                                data-descripcion="{{ strtolower($item->descripcion) }}">
                                
                                <td class="col-codigo"><strong>{{ $item->codigo }}</strong></td>
                                <td class="col-descripcion">{{ $item->descripcion }}</td>
                                <td class="text-right precio-unitario" data-precio="{{ $item->venta_usd }}">
                                    {{ number_format($item->venta_usd, 2) }} $
                                </td>
                                <td>
                                    {{-- La clave del array debe coincidir con el ID del producto --}}
                                    <input type="number" 
                                           name="cantidades[{{ $item->id }}]" 
                                           class="form-control input-cantidad text-center" 
                                           value="{{ $cantidadPrevia }}" 
                                           min="0" 
                                           onfocus="this.select()"
                                           style="border: 2px solid #ddd;">
                                </td>
                                <td class="text-right font-weight-bold subtotal-display">0.00 $</td>
                                <input type="hidden" class="subtotal-raw" value="0">
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</main>
<div class="modal fade" id="modalConfirmarPedido" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-save"></i> Confirmar Guardado de Pedido</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div id="contenidoAviso">
                    {{-- El mensaje se inyecta dinámicamente por JS --}}
                </div>
                <hr>
                <p class="mb-0">¿Deseas guardar los cambios realizados en tu lista?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" id="btnProcesarSubmit" class="btn btn-success">Sí, Guardar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const MONTO_MINIMO = {{ $lista->monto_minimo }};

    // 1. Buscador Optimizado (Oculta filas que no coinciden)
    document.getElementById('buscarProducto').addEventListener('input', function() {
        let termino = this.value.toLowerCase().trim();
        let filas = document.querySelectorAll('.item-row');
        
        filas.forEach(fila => {
            let codigo = fila.getAttribute('data-codigo') || '';
            let descripcion = fila.getAttribute('data-descripcion') || '';
            
            if (termino === '' || codigo.includes(termino) || descripcion.includes(termino)) {
                fila.style.display = ""; // Mostrar
                if(termino !== '') fila.classList.add('table-info'); else fila.classList.remove('table-info');
            } else {
                fila.style.display = "none"; // Ocultar
            }
        });
    });

    // 2. Cálculo de Subtotales y Totales
    document.querySelectorAll('.input-cantidad').forEach(input => {
        input.addEventListener('input', function() {
            let row = this.closest('.item-row');
            if (parseInt(this.value) > 0) {
                row.classList.add('row-selected');
            } else {
                row.classList.remove('row-selected');
            }
            let precio = parseFloat(row.querySelector('.precio-unitario').dataset.precio);
            let cantidad = parseInt(this.value) || 0;
            
            // Si el usuario borra el número, resetear a 0 visualmente
            if(this.value === "") this.value = 0;

            let subtotal = precio * cantidad;
            
            // Actualizar vista de subtotal
            row.querySelector('.subtotal-display').innerText = subtotal.toLocaleString('en-US', {minimumFractionDigits: 2}) + ' $';
            row.querySelector('.subtotal-raw').value = subtotal; // Guardar valor numérico puro

            calcularTotal();
        });
    });

    function calcularTotal() {
        let total = 0;
        document.querySelectorAll('.subtotal-raw').forEach(el => {
            total += parseFloat(el.value) || 0;
        });

        const totalElement = document.getElementById('totalPedido');
        const barra = document.getElementById('barraProgreso');
        
        totalElement.innerText = total.toLocaleString('en-US', {minimumFractionDigits: 2});

        // Feedback visual (Rojo/Verde y Barra de progreso)
        let porcentaje = (total / MONTO_MINIMO) * 100;
        if(porcentaje > 100) porcentaje = 100;

        barra.style.width = porcentaje + "%";

        if (total >= MONTO_MINIMO) {
            totalElement.style.color = 'green';
            barra.classList.remove('bg-danger');
            barra.classList.add('bg-success');
        } else {
            totalElement.style.color = 'red';
            barra.classList.remove('bg-success');
            barra.classList.add('bg-danger');
        }
    }

    // 3. Gestión de Envío y Confirmación (Integrado con Modal)
        document.getElementById('formPedido').addEventListener('submit', function(e) {
            e.preventDefault(); // Detenemos el envío automático

            let total = 0;
            document.querySelectorAll('.subtotal-raw').forEach(el => {
                total += parseFloat(el.value) || 0;
            });

            const avisoDiv = document.getElementById('contenidoAviso');
            
            // Lógica según tu Workflow: Pendiente vs Aprobado
            if (total < MONTO_MINIMO) {
                // El pedido se puede guardar pero se advierte que no será procesado
                avisoDiv.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle fa-2x"></i><br>
                        <strong>Monto por debajo del mínimo</strong><br>
                        Tu pedido suma <b>${total.toFixed(2)} $</b>. Se guardará en estado <b>PENDIENTE</b>.<br>
                        <small>Recuerda: Hasta que no alcances el mínimo de ${MONTO_MINIMO.toFixed(2)} $, 
                        la empresa no podrá aprobarlo ni prepararlo.</small>
                    </div>`;
            } else {
                // El pedido alcanza el mínimo y entrará en cola de trabajo
                avisoDiv.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle fa-2x"></i><br>
                        <strong>¡Monto Mínimo Alcanzado!</strong><br>
                        Tu pedido suma <b>${total.toFixed(2)} $</b>. Se guardará como <b>APROBADO</b> 
                        y entrará en cola para despacho.
                    </div>`;
            }

            // Mostrar el modal de confirmación
            $('#modalConfirmarPedido').modal('show');
        });

        // Botón final dentro del Modal que realiza el submit real
        document.getElementById('btnProcesarSubmit').addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Guardando...';
            document.getElementById('formPedido').submit();
        });

    // Ejecutar al cargar la página para procesar cantidades precargadas
    window.onload = function() {
        document.querySelectorAll('.input-cantidad').forEach(input => {
            if (parseInt(input.value) > 0) {
                let row = input.closest('.item-row');                
                row.classList.add('row-selected');
                // Disparamos manualmente el evento input para que calcule cada fila
                input.dispatchEvent(new Event('input'));
            }
        });
        // Finalmente recalculamos el total general
        calcularTotal();
    };
</script>
@endsection