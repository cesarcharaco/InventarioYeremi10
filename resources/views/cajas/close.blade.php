@extends('layouts.app')

@section('content')
<main class="app-content">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="tile">
                <h3 class="tile-title text-center"><i class="fa fa-calculator"></i> Cierre de Jornada</h3>
                <hr>
                
                <form action="{{ route('cajas.update', $cajaAbierta->id) }}" method="POST" id="form-cierre">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-7 border-right">
                            <h5 class="text-danger mb-3"><i class="fa fa-hand-holding-usd"></i> Reporte de Valores Físicos</h5>
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
                                        <td><input type="number" step="0.01" name="reportado_usd_efectivo" class="form-control" value="0.00" required></td>
                                    </tr>
                                    <tr>
                                        <td>Efectivo Bs.</td>
                                        {{-- Cambiado para usar el esperado real con apertura --}}
                                        <td class="bg-light"><strong>{{ number_format($esperado_bs, 2) }}</strong></td>
                                        <td><input type="number" step="0.01" name="reportado_bs_efectivo" class="form-control" value="0.00" required></td>
                                    </tr>
                                    <tr>
                                        <td>Punto de Venta (Bs.)</td>
                                        <td class="bg-light"><strong>{{ number_format($totales->punto_bs ?? 0, 2) }}</strong></td>
                                        <td><input type="number" step="0.01" name="reportado_punto_bs" class="form-control" value="0.00" required></td>
                                    </tr>
                                    <tr>
                                        <td>Biopago (Bs.)</td>
                                        <td class="bg-light"><strong>{{ number_format($totales->biopago_bs ?? 0, 2) }}</strong></td>
                                        <td><input type="number" step="0.01" name="reportado_biopago_bs" class="form-control" value="0.00" required></td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="form-group">
                                <label><strong>Observaciones de cierre:</strong></label>
                                <textarea name="nota_cierre" class="form-control" rows="2" placeholder="Ej: Faltaron $1 por falta de cambio..."></textarea>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <h5 class="text-primary mb-3"><i class="fa fa-mobile"></i> Referencia Digital (No reportable)</h5>
                            <div class="alert alert-info">
                                <small>Estos montos son informativos. El SuperAdmin los confirmará en sus cuentas bancarias.</small>
                            </div>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between">
                                    Pago Móvil: <strong>Bs. {{ number_format($totales->pagomovil_bs ?? 0, 2) }}</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    Transferencias: <strong>Bs. {{ number_format($totales->transferencia_bs ?? 0, 2) }}</strong>
                                </li>
                            </ul>
                            
                            {{-- Input para capturar PagoMóvil digital y sumarlo en el controlador --}}
                            <input type="hidden" name="reportado_pagomovil_bs" value="{{ $totales->pagomovil_bs ?? 0 }}">
                        </div>
                    </div>

                    <div class="tile-footer mt-4 text-center">
                        <button type="button" class="btn btn-danger btn-lg btn-block" onclick="confirmar()">
                            <i class="fa fa-save"></i> FINALIZAR Y CERRAR CAJA
                        </button>
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
        Swal.fire({
            title: '¿Confirmar cierre?',
            text: "Asegúrate de haber contado bien el efectivo y los vouchers de punto.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, cerrar jornada',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('form-cierre').submit();
            }
        })
    }
</script>
@endsection