@extends('layouts.app')

@section('content')
@include('layouts.partials.flash-messages')
<main class="app-content">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="tile">
                <h3 class="tile-title text-center">
                    <i class="fa fa-edit"></i> 
                    {{ $caja->estado == 'abierta' ? 'Cierre de Jornada' : 'Editar Registro de Caja' }}
                </h3>
                <div class="text-center">
                    <span class="badge {{ $caja->estado == 'abierta' ? 'badge-info' : 'badge-secondary' }}">
                        Estado: {{ strtoupper($caja->estado) }}
                    </span>
                </div>
                <hr>
                
                <form action="{{ route('cajas.update', $caja->id) }}" method="POST" id="form-caja">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        {{-- SECCIÓN IZQUIERDA: REPORTES FÍSICOS --}}
                        <div class="col-md-7 border-right">
                            <h5 class="text-danger mb-3"><i class="fa fa-hand-holding-usd"></i> Valores en Efectivo y Punto</h5>
                            <table class="table table-bordered table-sm">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Concepto</th>
                                        <th>Esperado (Sistema)</th>
                                        <th>Físico (Contado)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Efectivo USD ($)</td>
                                        <td class="bg-light"><strong>{{ number_format($esperado_usd, 2) }}</strong></td>
                                        <td>
                                            <input type="number" step="0.01" name="reportado_usd_efectivo" class="form-control" 
                                            value="{{ old('reportado_usd_efectivo', $caja->reportado_cierre_usd_efectivo ?? 0) }}" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Efectivo Bs.</td>
                                        <td class="bg-light"><strong>{{ number_format($esperado_bs ?? 0, 2) }}</strong></td>
                                        <td>
                                            <input type="number" step="0.01" name="reportado_bs_efectivo" class="form-control" 
                                            value="{{ old('reportado_bs_efectivo', $caja->reportado_cierre_bs_efectivo ?? 0) }}" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Punto de Venta (Bs.)</td>
                                        <td class="bg-light"><strong>{{ number_format($totales['punto_bs'] ?? 0, 2) }}</strong></td>
                                        <td>
                                            <input type="number" step="0.01" name="reportado_punto_bs" class="form-control" 
                                            value="{{ old('reportado_punto_bs', $caja->reportado_cierre_punto ?? 0) }}" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Biopago (Bs.)</td>
                                        <td class="bg-light"><strong>{{ number_format($totales['biopago_bs'] ?? 0, 2) }}</strong></td>
                                        <td>
                                            <input type="number" step="0.01" name="reportado_biopago_bs" class="form-control" 
                                            value="{{ old('reportado_biopago_bs', 0) }}" required>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- SECCIÓN DERECHA: REFERENCIAS DIGITALES --}}
                        <div class="col-md-5">
                            <h5 class="text-primary mb-3"><i class="fa fa-mobile"></i> Referencia Digital</h5>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between">
                                    Pago Móvil: <strong>Bs. {{ number_format($totales['pagomovil_bs'] ?? 0, 2) }}</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    Transferencias: <strong>Bs. {{ number_format($totales['transferencia_bs'] ?? 0, 2) }}</strong>
                                </li>
                            </ul>
                            
                            <div class="form-group mt-3">
                                <label><strong>Observaciones:</strong></label>
                                <textarea name="nota_cierre" class="form-control" rows="3">{{ $caja->nota_cierre }}</textarea>
                            </div>

                            <input type="hidden" name="reportado_pagomovil_bs" value="{{ $totales['pagomovil_bs'] ?? 0 }}">
                        </div>
                    </div>

                    <div class="tile-footer mt-4 text-center">
                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            <i class="fa fa-save"></i> GUARDAR CAMBIOS / CERRAR CAJA
                        </button>
                        <a href="{{ route('cajas.index') }}" class="btn btn-secondary btn-block">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
@endsection
@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function confirmar() {
        // Validación básica de campos vacíos antes de mostrar el SweetAlert
        const campos = document.querySelectorAll('#form-caja input[required]');
        let vacios = false;

        campos.forEach(campo => {
            if (!campo.value || campo.value === "") {
                vacios = true;
                campo.classList.add('is-invalid');
            } else {
                campo.classList.remove('is-invalid');
            }
        });

        if (vacios) {
            Swal.fire({
                icon: 'error',
                title: 'Campos incompletos',
                text: 'Por favor, rellena todos los montos físicos (puedes poner 0 si no hay nada).',
                confirmButtonColor: '#009688'
            });
            return;
        }

        // Si todo está ok, pedimos confirmación
        Swal.fire({
            title: '¿Confirmar cierre de caja?',
            text: "Una vez cerrada, la jornada no podrá ser modificada por el cajero.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa fa-check"></i> Sí, cerrar ahora',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Bloqueamos el botón para evitar doble click
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Guardando el arqueo y cerrando jornada',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                document.getElementById('form-caja').submit();
            }
        });
    }

    // Script opcional para que al hacer click en un input se seleccione todo el texto (más rápido para el cajero)
    $(document).ready(function() {
        $("input[type='number']").on("click", function () {
           $(this).select();
        });
    });
</script>
@endsection