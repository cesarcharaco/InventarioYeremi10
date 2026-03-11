@extends('layouts.app')
@section('title') Realizar Pedido @endsection

@section('content')
<main class="app-content">
    <div class="app-title">
        <div>
            <h1><i class="fa fa-cart-plus"></i> Lista de Productos al Mayor</h1>
            <p>Monto mínimo requerido: <strong id="montoMinimoLabel">{{ number_format($lista->monto_minimo, 2) }}</strong> $</p>
        </div>
    </div>

    {{-- Formulario General --}}
    <form action="{{ route('pedidos.store') }}" method="POST" id="formPedido">
        @csrf
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
                            // Buscamos si este producto específico ya está en el borrador
                            $cantidadPrevia = 0;
                            if ($pedidoExistente) {
                                $detalle = $pedidoExistente->detalles->where('insumos_mayor_id', $item->id)->first();
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
                                {{-- select() al hacer foco para facilitar la edición rápida --}}
                                <input type="number" 
                                   name="cantidades[{{ $item->id }}]" 
                                   class="form-control input-cantidad text-center" 
                                   value="{{ $cantidadPrevia }}" 
                                   min="0" 
                                   onfocus="this.select()"
                                   style="border: 2px solid #ddd;">
                            </td>
                            <td class="text-right font-weight-bold subtotal-display">0.00 $</td>
                            {{-- Campo oculto para cálculo numérico preciso --}}
                            <input type="hidden" class="subtotal-raw" value="0">
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</main>
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

    // 3. Validación antes de Enviar
    document.getElementById('formPedido').addEventListener('submit', function(e) {
        let total = 0;
        document.querySelectorAll('.subtotal-raw').forEach(el => {
            total += parseFloat(el.value) || 0;
        });

        if (total < MONTO_MINIMO) {
            e.preventDefault();
            swal({
                title: "Monto Insuficiente",
                text: "Su pedido actual es de " + total.toFixed(2) + " $. Debe alcanzar el mínimo de " + MONTO_MINIMO + " $ para continuar.",
                icon: "error",
                button: "Cerrar",
            });
        }
    });

    // Ejecutar al cargar la página para procesar cantidades precargadas
    window.onload = function() {
        document.querySelectorAll('.input-cantidad').forEach(input => {
            if (parseInt(input.value) > 0) {
                // Disparamos manualmente el evento input para que calcule cada fila
                input.dispatchEvent(new Event('input'));
            }
        });
        // Finalmente recalculamos el total general
        calcularTotal();
    };
</script>
@endsection