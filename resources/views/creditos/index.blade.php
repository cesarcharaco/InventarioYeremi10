@extends('layouts.app')

@section('title') Cuentas por Cobrar @endsection

@section('content')
<style>
    /* Evita que la tabla se rompa en móviles */
    .table-custom {
        border-collapse: separate !important;
        border-spacing: 0 8px !important; /* Efecto de filas separadas */
    }

    .table-custom tbody tr {
        background-color: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border-radius: 8px;
    }

    /* Ajuste de botones en móvil */
    @media (max-width: 768px) {
        .btn-block-mobile {
            width: 100%;
            display: block;
            margin-top: 5px;
        }
        
        .tile {
            padding: 10px;
            margin-bottom: 10px;
        }

        .badge {
            font-size: 0.9rem;
            width: 100%;
            text-align: center;
        }
    }

    /* Estilo limpio para los encabezados */
    .table-custom thead th {
        border: none;
        background: transparent;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
    }
</style>
<main class="app-content">
    <div class="app-title d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h3 class="title"><i class="fa fa-address-book"></i> Cuentas por Cobrar</h3>
            <p class="mb-0">Listado de clientes con saldo pendiente</p>
        </div>
        <div>
            <button type="button" class="btn btn-success font-weight-bold btn-block-mobile" data-toggle="modal" data-target="#modalCreditoDirectoGeneral">
                <i class="fa fa-plus-circle"></i> Nuevo Crédito Directo
            </button>
        </div>
    </div>

    <div class="basic-tb-hd text-center">          
        @include('layouts.partials.flash-messages')
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="tile">
                <div class="table-responsive">
                    <table class="table table-hover table-custom" id="tabla-clientes" style="width:100%">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th class="d-none d-md-table-cell">Identificación</th> 
                                <th>Saldo Pendiente</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clientes as $cliente)
                            <tr>
                                <td>
                                    <div class="font-weight-bold">{{ $cliente->nombre }}</div>
                                    @if(!empty($cliente->alias))
                                        <small class="text-muted d-block">({{ $cliente->alias }})</small>
                                    @endif
                                </td>
                                <td class="d-none d-md-table-cell text-muted">
                                    {{ $cliente->identificacion }}
                                </td>
                               <td>
                                   @php
                                       // 1. Deuda pendiente (solo si es mayor a 0)
                                       $deuda = (isset($cliente->saldo_total_pendiente) && $cliente->saldo_total_pendiente > 0) 
                                           ? $cliente->saldo_total_pendiente 
                                           : 0;

                                       // 2. Cálculo de Saldo a Favor considerando todas las formas de almacenamiento
                                       $saldoFavor = $cliente->saldo_a_favor 
                                           ?? $cliente->saldo_favor 
                                           ?? ($cliente->creditos ? $cliente->creditos->sum('saldo_a_favor') : 0);

                                       // Si viene como valor negativo en el saldo pendiente acumulado
                                       if ($saldoFavor <= 0 && isset($cliente->saldo_total_pendiente) && $cliente->saldo_total_pendiente < 0) {
                                           $saldoFavor = abs($cliente->saldo_total_pendiente);
                                       }
                                   @endphp

                                   {{-- MOSTRAR ESTADO DE DEUDA --}}
                                   @if($deuda > 0)
                                       <span class="badge badge-danger px-3 py-2" data-toggle="tooltip" title="Deuda Pendiente">
                                           ${{ number_format($deuda, 2) }}
                                       </span>
                                   @else
                                       <span class="badge badge-success px-3 py-2" data-toggle="tooltip" title="Cliente Solvente">
                                           $0.00
                                       </span>
                                   @endif

                                   {{-- MOSTRAR SALDO A FAVOR SI EXISTE --}}
                                   @if($saldoFavor > 0)
                                       <div class="mt-1">
                                           <span class="badge badge-info px-2 py-1" data-toggle="tooltip" title="Saldo a favor disponible">
                                               <i class="fa fa-plus-circle"></i> A favor: ${{ number_format($saldoFavor, 2) }}
                                           </span>
                                       </div>
                                   @endif
                               </td>
                                <td class="text-center">
                                    <a href="{{ route('creditos.show', $cliente->id) }}" class="btn btn-info btn-sm btn-block-mobile">
                                        <i class="fa fa-eye"></i> <span class="d-none d-md-inline">Ver Detalle</span>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>


@include('creditos.modals.modal_credito_directo_general')
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#tabla-clientes').DataTable({
            responsive: true,
            language: {
                "decimal": "",
                "emptyTable": "No hay datos disponibles en la tabla",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "lengthMenu": "Mostrar _MENU_ registros",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron resultados",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            },
            dom: 'ftip',
            pageLength: 20
        });

        // Inicializar Select2 en el modal
        if ($.fn.select2) {
            $('.select2-modal').select2({
                dropdownParent: $('#modalCreditoDirectoGeneral'),
                width: '100%'
            });
        }
    });

function abrirModalCreditoDirecto(clienteId) {
    $('#formCreditoDirectoGeneral')[0].reset();
    $('#pin_autorizacion_directo_general').val('');
    
    // Si se pasa un cliente por parámetro, seleccionarlo en el select2
    if (clienteId && $('#cliente_id_general').length) {
        $('#cliente_id_general').val(clienteId).trigger('change');
    }
    
    @cannot('gestionar-creditos-avanzado')
        $('#estado_pin_texto_general').html('Requiere autorización de supervisor').removeClass('text-success').addClass('text-dark');
        $('#bloque_pin_warning_general').removeClass('alert-success').addClass('alert-warning');
    @endcannot

    $('#modalCreditoDirectoGeneral').modal('show');
}

$(document).ready(function() {
    // Evento para solicitar y validar el PIN usando SweetAlert2
    $('#btnSolicitarPinDirectoGeneral').on('click', function() {
        let monto = $('#monto_credito_usd_general').val();

        if (!monto || parseFloat(monto) <= 0) {
            Swal.fire('Monto Requerido', 'Por favor ingresa primero el monto del crédito antes de solicitar el PIN.', 'warning');
            return;
        }

        Swal.fire({
            title: '¿Solicitar Autorización?',
            text: "Se enviará un PIN de 6 dígitos al supervisor para autorizar $" + monto,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, enviar PIN',
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false 
        }).then((result) => {
            if (result.isConfirmed) {
                // Obtener el nombre del cliente seleccionado de forma dinámica
                let nombreCliente = $('#cliente_id_general option:selected').text().trim() || 'Cliente';

                $.post("{{ route('ventas.solicitar_pin') }}", {
                    _token: "{{ csrf_token() }}",
                    local_nombre: "{{ auth()->user()->localActual()->nombre ?? 'Local' }}",
                    cliente_nombre: nombreCliente,
                    monto_total: monto,
                    cantidad_items: 1
                }, function(response) {

                    if(response.wa_link) { window.open(response.wa_link, '_blank'); }

                    Swal.fire({
                        title: 'Introduce el PIN',
                        text: 'El supervisor recibió un código de 6 dígitos',
                        input: 'text',
                        inputAttributes: { maxlength: 6, autocapitalize: 'off' },
                        showCancelButton: true,
                        confirmButtonText: 'Validar PIN',
                        cancelButtonText: 'Cancelar',
                        showLoaderOnConfirm: true,
                        allowOutsideClick: false,
                        didOpen: () => {
                            $(document).off('focusin.bs.modal');
                            if ($.fn.modal && $.fn.modal.Constructor) {
                                $.fn.modal.Constructor.prototype._enforceFocus = function() {};
                            }

                            setTimeout(() => {
                                const input = Swal.getInput();
                                if (input) {
                                    $(input).removeAttr('readonly').removeAttr('disabled').focus();
                                }
                            }, 200);
                        },
                        preConfirm: (pin) => {
                            return $.post("{{ route('ventas.verificar_pin') }}", {
                                _token: "{{ csrf_token() }}",
                                pin: pin
                            }).done(res => {
                                $('#pin_autorizacion_directo_general').val(pin); 
                            }).fail(error => {
                                Swal.showValidationMessage(error.responseJSON.message || 'PIN Incorrecto');
                            });
                        }
                    }).then((res) => {
                        if (res.isConfirmed) {
                            $('#estado_pin_texto_general').html('<i class="fa fa-check-circle text-success"></i> Crédito Autorizado por Supervisor').removeClass('text-dark').addClass('text-success');
                            $('#bloque_pin_warning_general').removeClass('alert-warning').addClass('alert-success');
                            Swal.fire('Autorizado', 'Crédito habilitado con éxito.', 'success');
                        } else {
                            $('#pin_autorizacion_directo_general').val('');
                        }
                    });
                });
            }
        });
    });

    // Validación al enviar el formulario general
    $('#formCreditoDirectoGeneral').on('submit', function(e) {
        @cannot('gestionar-creditos-avanzado')
            let pin = $('#pin_autorizacion_directo_general').val();
            if (!pin) {
                e.preventDefault();
                Swal.fire('Autorización Requerida', 'Debes solicitar y validar el PIN del supervisor para guardar este crédito.', 'error');
                return false;
            }
        @endcannot
    });
});
</script>
@endsection