@extends('layouts.app')
@section('title') Detalle de Crédito @endsection

@section('content')
<main class="app-content">
    <div class="app-title">
        <div>
            <h1><i class="fa fa-user"></i> Estado de Cuenta: {{ $cliente->nombre }}</h1>
            <p>
                <strong>Cédula/Rif:</strong> {{ $cliente->identificacion }} | 
                <strong>Teléfono:</strong> {{ $cliente->telefono }} |
                <strong>Total Deuda:</strong> <span class="text-danger font-weight-bold" id="total_deuda_cliente">${{ number_format($cliente->creditos->where('estado', 'pendiente')->sum('saldo_pendiente'), 2) }}</span>
            </p>
        </div>
        <div class="basic-tb-hd text-center">            
          @include('layouts.partials.flash-messages')
        </div>
        <a href="{{ route('creditos.index') }}" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Volver</a>

    </div>
    <div class="row">
            <div class="col-md-12">
                <h3 class="tile-title">Resumen Financiero</h3>
                {{-- Botón para ir al historial de productos --}}
                <a href="{{ route('creditos.productos', $cliente->id) }}" class="btn btn-info btn-sm">
                    <i class="fa fa-list-ul"></i> Historial de Productos
                </a>
            </div>
        </div>
    <div class="row">
        <div class="col-md-4">
            <div class="tile p-0 shadow-sm border-0">
                <div class="bg-dark text-white p-3 d-flex justify-content-between align-items-center rounded-top">
                    <span class="font-weight-bold"><i class="fa fa-calculator"></i> Resumen de Cuenta</span>
                    <div class="btn-group">
                        @if(auth()->user()->esAdmin())
                            <button class="btn btn-sm btn-warning font-weight-bold mr-1" 
                                    onclick="abrirModalInteres({{ $cliente->toJson() }})" 
                                    title="Indexar a todos los créditos">
                                <i class="fa fa-line-chart"> Indexar</i>
                            </button>
                        @endif
                        <button class="btn btn-success btn-sm font-weight-bold" 
                                onclick="abrirModalAbono({{ $cliente->toJson() }})">
                            <i class="fa fa-plus"></i> Abonar
                        </button>
                    </div>
                </div>

                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted">Monto Original:</span>
                        <span class="h6 mb-0 font-weight-bold">${{ number_format($resumen['monto_inicial'], 2) }}</span>
                    </li>
                    
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted">Intereses (Indexación):</span>
                        <span class="h6 mb-0 text-warning font-weight-bold">+ ${{ number_format($resumen['total_intereses'], 2) }}</span>
                    </li>

                    <!-- <li class="list-group-item d-flex justify-content-between align-items-center py-3 bg-light">
                        <span class="font-weight-bold text-dark">Total Deuda Actual:</span>
                        <span class="h6 mb-0 font-weight-bold">${{ number_format($resumen['deuda_total'], 2) }}</span>
                    </li> -->

                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted">Total Abonado:</span>
                        <span class="h6 mb-0 text-success font-weight-bold">- ${{ number_format($resumen['total_abonado'], 2) }}</span>
                    </li>

                    @if($resumen['saldo_a_favor'] > 0)
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-info text-white py-3">
                        <span><i class="fa fa-star"></i> <strong>Saldo a Favor:</strong></span>
                        <div class="text-right">
                            <strong class="d-block h5 mb-0">${{ number_format($resumen['saldo_a_favor'], 2) }}</strong>
                            <button type="button" class="btn btn-xs btn-outline-light mt-1 py-0 px-2" style="font-size: 10px;" onclick="abrirModalGestionSaldo()">
                                Gestionar
                            </button>
                        </div>
                    </li>
                    @endif

                    <li class="list-group-item d-flex justify-content-between align-items-center py-4 border-top" style="background: #fff5f5;">
                        <span class="h5 mb-0 font-weight-bold text-danger">Saldo Restante:</span>
                        <span id="saldo_total_cliente" class="h4 mb-0 font-weight-bold text-danger" 
                              data-valor="{{ $resumen['saldo_pendiente'] }}">
                            ${{ number_format($resumen['saldo_pendiente'], 2) }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-md-8">
            <div class="tile">
                <h3 class="tile-title">Historial de Abonos</h3>
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover" id="tabla-historial-abonos">
                                <thead>
                                    <tr>
                                        <th>Fecha / Hora</th>
                                        <th>Cajero</th>
                                        <th class="d-md-table-cell">#Crédito</th> 
                                        <th class="d-md-table-cell text-nowrap">Monto ($)</th>
                                        <th class="d-md-table-cell">Forma de Pago (Desglose)</th>
                                        <th class="d-md-table-cell">Detalles / Observación</th>
                                        <th>Estado</th>
                                        @can('anular-abono') <th>Acción</th> @endcan
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($historialAbonos as $abono)
                                    <tr style="{{ $abono->estado === 'Anulado' ? 'opacity: 0.6; text-decoration: line-through;' : '' }}">
                                        <td class="small">{{ $abono->created_at->format('d/m/Y h:i A') }}</td>
                                        
                                        <td>{{ $abono->usuario->name }}</td>

                                        <td>
                                            <span class="badge badge-light border">ID: {{ $abono->id_credito }}</span>
                                        </td>

                                        <td class="font-weight-bold text-success">
                                            ${{ number_format($abono->monto_pagado_usd, 2) }}
                                        </td>

                                        <td>
                                            @if($abono->pago_usd_efectivo > 0) 
                                                <small class="badge badge-light border">Efe $: {{ number_format($abono->pago_usd_efectivo, 2) }}</small> 
                                            @endif
                                            @if($abono->pago_bs_efectivo > 0) 
                                                <small class="badge badge-light border">Efe Bs: {{ number_format($abono->pago_bs_efectivo, 2) }}</small> 
                                            @endif
                                            @if($abono->pago_punto_bs > 0) 
                                                <small class="badge badge-light border">Punto: {{ number_format($abono->pago_punto_bs, 2) }}</small> 
                                            @endif
                                            @if($abono->pago_pagomovil_bs > 0) 
                                                <small class="badge badge-light border">P.Móvil: {{ number_format($abono->pago_pagomovil_bs, 2) }}</small> 
                                            @endif
                                        </td>

                                        <td><small class="text-muted">{{ $abono->detalles ?? 'N/A' }}</small></td>

                                        <td>
                                            <span class="badge badge-{{ $abono->estado === 'Realizado' ? 'success' : 'danger' }}">
                                                {{ $abono->estado }}
                                            </span>
                                        </td>

                                        @can('anular-abono')
                                        <td class="text-center">
                                            @if($abono->estado === 'Realizado')
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmarAnulacion('{{ route('abonos.anular', $abono->id) }}', '{{ number_format($abono->monto_pagado_usd, 2) }}')"
                                                        title="Anular Abono">
                                                    <i class="fa fa-ban"></i>
                                                </button>
                                            @endif
                                        </td>
                                        @endcan
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center p-4 text-muted">
                                            <i class="fa fa-info-circle"></i> No hay abonos registrados para este cliente.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                </div>
                <hr class="my-4" style="border: 0; border-top: 2px dashed #ccc;">
                <h3 class="tile-title">Historial de Indexación (Intereses)</h3>
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover" id="tabla-historial-intereses">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th class="d-md-table-cell">Crédito #</th>
                                        <th>Admin</th>
                                        <th class="d-md-table-cell">Porcentaje</th>
                                        <th class="d-md-table-cell">Monto ($)</th>
                                        <th>Estado</th>
                                        @if(auth()->user()->esAdmin()) <th class="text-center">Acción</th> @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($historialIntereses as $interes)
                                    <tr style="{{ $interes->estado === 'anulado' ? 'opacity: 0.6; text-decoration: line-through;' : '' }}">
                                        <td class="small">{{ $interes->aplicado_en->format('d/m/Y h:i A') }}</td>
                                        
                                        <td>
                                            <span class="badge badge-light border">ID: {{ $interes->id_credito }}</span>
                                        </td>

                                        <td>{{ $interes->administrador->name ?? 'N/A' }}</td>

                                        <td class="text-primary font-weight-bold">{{ $interes->porcentaje }}%</td>

                                        <td class="font-weight-bold text-danger">${{ number_format($interes->monto_interes, 2) }}</td>

                                        <td>
                                            <span class="badge badge-{{ $interes->estado === 'aplicado' ? 'warning' : 'danger' }}">
                                                {{ ucfirst($interes->estado) }}
                                            </span>
                                        </td>

                                        @if(auth()->user()->esAdmin())
                                        <td class="text-center">
                                            @if($interes->estado === 'aplicado')
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger" 
                                                        title="Anular Indexación" 
                                                        onclick="confirmarAnulacionInteres('{{ route('creditos.interes.anular', $interes->id) }}', '{{ number_format($interes->monto_interes, 2) }}')">
                                                    <i class="fa fa-ban"></i>
                                                </button>

                                            @endif
                                        </td>
                                        @endif
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center p-4 text-muted">
                                            <i class="fa fa-info-circle"></i> No se han aplicado indexaciones a los créditos de este cliente.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                </div>
            </div>
        </div>
    </div>
</main>

@include('creditos.modals.abono_modal')
@include('creditos.modals.modal_anular_abono')
@include('creditos.modals.modal_anular_interes')
@include('creditos.modals.modal_gestion_saldo')
@include('creditos.modals.modal_interes')
@endsection
@section('scripts')
<script>
    function confirmarAnulacion(url, monto) {
        $('#formAnularAbono').attr('action', url);
        $('#montoAbonoText').text('$' + monto);
        $('#modalAnularAbono').modal('show');
    }

    function abrirModalInteres(cliente) {

        let saldoTotal = cliente.creditos.reduce((sum, c) => sum + parseFloat(c.saldo_pendiente), 0);
        $.ajax({
            url: `/creditos/${cliente.id}/modal-interes`, 
            type: 'GET',
            success: function(html) {
                // 1. Limpiamos y cargamos el HTML (Tu lógica original)
                $('#contenedor-modal-interes').remove();
                $('body').append('<div id="contenedor-modal-interes">' + html + '</div>');
                
                // 2. UNA VEZ CARGADO EL HTML, inyectamos los datos del saldo y la ruta (La mejora necesaria)
                let url = "{{ route('creditos.aplicarInteres', ':id') }}";
                $('#formAplicarInteres').attr('action', url.replace(':id', cliente.creditos[0].id));
                
                // Si el modal tiene estos elementos, actualizamos sus valores
                $('#saldo_base_global').text('$' + saldoTotal.toFixed(2));
                $('#saldo_base_global').data('valor', saldoTotal);
                // Y si necesitas inyectar ese valor en el modal:
/*                $('#saldo_base_global').text('$' + saldoBase.toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#saldo_base_global').data('valor', saldoBase);*/
                // 3. Mostramos el modal
                $('#modalAplicarInteres').modal('show');
            },
            error: function(xhr) {
                // Obtenemos el mensaje del servidor de forma segura
                var msj = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : "Error al cargar modal";
                
                // Inyectamos una alerta de error directa en el contenedor donde se espera que aparezca
                $('.app-content').prepend(`
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error:</strong> ${msj}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                `);
            }
        });
    }
    // Procesa el envío del formulario
    $(document).on('submit', '#formAplicarInteres', function(e) {
            e.preventDefault();
            let form = $(this);
            let btn = form.find('button[type="submit"]');
            
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Indexando...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#modalAplicarInteres').modal('hide');
                        
                        // --- AQUÍ RESTAURAMOS TU ALERTA DINÁMICA ---
                        $('.app-content').prepend(`
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>¡Éxito!</strong> ${response.mensaje}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        `);
                        
                        // Recargamos la página después de un tiempo para actualizar datos
                        setTimeout(() => { location.reload(); }, 2500);
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).text('Confirmar Indexación');
                    let errorMsg = (xhr.responseJSON && xhr.responseJSON.mensaje) ? xhr.responseJSON.mensaje : "Error en el servidor";
                    
                    // Alerta de error dentro del modal
                    $('.modal-body').prepend(`
                        <div class="alert alert-danger">
                            ${errorMsg}
                        </div>
                    `);
                }
            });
        });

    // Escuchar cambios en el input (Ahora sí entrará)
    $(document).on('input', '#input_porcentaje', function() {

        console.log('Calculando...');
        let porcentaje = parseFloat($(this).val()) || 0;
        let saldoBase = parseFloat($('#saldo_base_global').data('valor')) || 0;
        let btn = $('#btn_confirmar_index');

        if (porcentaje > 0) {
            let montoInteres = saldoBase * (porcentaje / 100);
            let nuevoTotal = saldoBase + montoInteres;
            console.log('porcentaje:'+saldoBase);
            $('#preview_interes').text('+$' + montoInteres.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#preview_total').text('$' + nuevoTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            btn.prop('disabled', false);
        } else {
            $('#preview_interes').text('$0.00');
            $('#preview_total').text('$' + saldoBase.toFixed(2));
            btn.prop('disabled', true);
        }
    });

    function abrirModalAbono(cliente) {
        // 1. Limpiamos el formulario
        $('#formAbono')[0].reset();
        
        // 2. Definimos la URL usando el ID del primer crédito pendiente (o un ID de referencia del cliente)
        // Nota: Asegúrate de que tu ruta en Laravel acepte el ID del crédito que servirá de "disparador"
        let url = "{{ route('creditos.abono', ':id') }}";
        
        // Tomamos el ID del crédito más antiguo (o el primero de la lista)
        let primerCreditoId = cliente.creditos[0].id;
        url = url.replace(':id', primerCreditoId);
        $('#formAbono').attr('action', url);

        // 3. Actualizamos los datos para el Cliente
        $('#nombre_cliente').text(cliente.nombre);
        
        // Calculamos el saldo total sumando todos los créditos pendientes del cliente
        let saldoTotal = cliente.creditos.reduce((sum, c) => sum + parseFloat(c.saldo_pendiente), 0);
        
        $('#txt_saldo_pendiente').text('$' + saldoTotal.toFixed(2));
        
        // 4. Actualizamos el límite del input (Ahora el máximo es el total del cliente)
        $('#monto_total_usd').attr('max', saldoTotal);
        
        $('#modalAbono').modal('show');
    }

    $('#formAbono').on('submit', function(e) {
            let totalDesglose = 0;
            let saldoPendiente = parseFloat($('#txt_saldo_pendiente').text().replace(/[^0-9.-]+/g,""));
            let montoAbono = parseFloat($('#monto_total_usd').val());
            let inputs = $('.input-desglose');
            let errorDiv = $('#error-desglose');
            if(montoAbono > saldoPendiente) {
                alert("El abono no puede ser mayor a la deuda.");
                return false;
            }
            // Recorremos todos los inputs que tengan la clase 'input-desglose'
            $('.input-desglose').each(function() {
                let valor = parseFloat($(this).val()) || 0;
                totalDesglose += valor;
            });

            // Validación: Si la suma de todos es 0 o menor
            if (totalDesglose <= 0) {
                e.preventDefault(); // Detener envío
                            
                // Animación y estilo moderno
                errorDiv.removeClass('d-none').hide().fadeIn();
                inputs.addClass('is-invalid'); // Borde rojo de Bootstrap
                
                // Hacer scroll suave al error si el modal es muy largo
                $('.modal-body').animate({ scrollTop: 0 }, 'slow');
                
                return false;
            }
            
            // Si pasa la validación, quitamos el error visual (si existía)
            $('.input-desglose').removeClass('is-invalid');
        });
        function confirmarAnulacionAbono(url, monto) {
            // 1. Asignamos la URL dinámica al formulario
            $('#formAnularAbono').attr('action', url);
            
            // 2. Mostramos el monto en el texto del modal para seguridad del usuario
            $('#montoAbonoText').text('$' + parseFloat(monto).toFixed(2));
            
            // 3. Abrimos el modal
            $('#modalAnularAbono').modal('show');
        }
        $(document).on('input', '.input-desglose', function() {
            let val = parseFloat($(this).val()) || 0;
            if (val > 0) {
                $('.input-desglose').removeClass('is-invalid').addClass('is-valid');
                $('#error-desglose').fadeOut().addClass('d-none');
            }
        });

        function confirmarAnulacionInteres(url, monto) {
            // Limpiamos el formulario antes de mostrarlo
            $('#formAnularInteres')[0].reset();
            
            // Configuramos la acción y el texto informativo
            $('#formAnularInteres').attr('action', url);
            $('#montoInteresText').text('$' + monto);
            
            // Mostramos el modal
            $('#modalAnularInteres').modal('show');
        }

        

     function abrirModalGestionSaldo() {
         // Busca el modal por su ID
         let modal = $('#modalReembolso'); 
         
         if (modal.length > 0) {
             // En lugar de resetear un formulario que no tiene ID, 
             // simplemente muestra el modal
             modal.modal('show');
         } else {
             console.error("El modal #modalReembolso no se encontró.");
         }
     }

     $('#input_porcentaje').on('input', function() {
         let porcentaje = parseFloat($(this).val()) || 0;
         let saldoBase = parseFloat($('#saldo_base').data('valor'));
         let montoInteres = saldoBase * (porcentaje / 100);

         $('#preview_interes').text('$' + montoInteres.toFixed(2));
         $('#preview_total').text('$' + (saldoBase + montoInteres).toFixed(2));

         // Habilitar botón solo si hay porcentaje
         $('#btn_confirmar_index').prop('disabled', porcentaje <= 0);
     });
     
     $('#tabla-historial-abonos').DataTable({
         "pageLength": 5, // Mostrar 5 de 5
         "lengthMenu": [5, 10, 20], // Permite al usuario cambiar a más si quiere
         "responsive": true, // Hace que la tabla sea amigable con móviles
         "language": {
             "search": "Buscar:",
             "paginate": {
                 "next": "Sig",
                 "previous": "Ant"
             },
             "info": "Mostrando _START_ a _END_ de _TOTAL_ abonos"
         },
         "dom": '<"row"<"col-sm-12"f>>t<"row"<"col-sm-12"p>>', // Diseño limpio
         "order": [[0, 'desc']] // Ordenar por fecha descendente automáticamente
     });

     $(document).ready(function() {
        $('#tabla-historial-intereses').DataTable({
            "pageLength": 5,
            "lengthMenu": [5, 10],
            "responsive": true,
            "language": {
                "search": "Buscar:",
                "paginate": {
                    "next": "Sig",
                    "previous": "Ant"
                },
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros"
            },
            "dom": '<"row"<"col-sm-12"f>>t<"row"<"col-sm-12"p>>',
            "order": [[0, 'desc']] // Asegura que la fecha más reciente salga primero
        });
    });
</script>
@endsection