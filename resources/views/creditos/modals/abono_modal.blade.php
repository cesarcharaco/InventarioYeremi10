{{-- MODAL DE ABONO UNIFICADO --}}
<div class="modal fade" id="modalAbono" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="" method="POST" id="formAbono">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fa fa-money"></i> Registrar Abono - <span id="nombre_cliente"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row text-center mb-3">
                        <div class="col-md-12">
                            <h4 class="text-muted">Saldo Pendiente: <span class="text-danger" id="txt_saldo_pendiente"></span></h4>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 border-right">
                            
                            {{-- NUEVO CAMPO: FECHA DEL ABONO --}}
                            <div class="form-group mb-3">
                                <label class="font-weight-bold">Fecha del Abono:</label>
                                <input type="date" name="fecha_abono" id="fecha_abono" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            {{-- NUEVO CAMPO: SELECTOR DE LOCAL PARA ADMIN --}}
                            @if(auth()->user()->esAdmin())
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-primary">Local (Caja a afectar) <span class="text-danger">*</span></label>
                                <select name="id_local" class="form-control" required>
                                    <option value="">-- Seleccione un local --</option>
                                    @foreach($locales as $local)
                                        <option value="{{ $local->id }}">{{ $local->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="form-group">
                                <label class="font-weight-bold text-primary">Monto Total a Abonar (USD):</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                    <input type="number" step="0.01" min="0.01" name="monto_total_usd" id="monto_total_usd" class="form-control form-control-lg" placeholder="0.00" required>
                                </div>
                                <small class="text-muted d-block mt-1">Si ingresa un monto mayor a la deuda, el excedente quedará disponible a favor del cliente.</small>
                                
                                {{-- Contenedor dinámico de Saldo a Favor --}}
                                <div id="alerta_saldo_favor" class="alert alert-info py-2 mt-2 d-none small mb-0">
                                    <i class="fa fa-info-circle"></i> Se saldará la deuda y quedará <b id="monto_saldo_favor">$0.00</b> como saldo a favor.
                                </div>
                            </div>
                            <hr>
                            <div class="form-group">
                                <label>Referencia / Nota:</label>
                                <textarea name="referencia" class="form-control" rows="2" placeholder="Ej: Pago móvil, transferencia Banesco, etc."></textarea>
                            </div>

                        </div>
                        
                        <div class="col-md-6">
                            <label class="font-weight-bold">Desglose de Pago (Entrada a Caja):</label>
                            <div id="error-desglose" class="alert alert-danger d-none py-1 small">
                                <i class="fa fa-exclamation-circle"></i> Debe ingresar al menos un valor en el desglose.
                            </div>
                            <div class="input-group mb-2">
                                <div class="input-group-prepend"><span class="input-group-text">Efectivo $</span></div>
                                <input type="number" step="0.01" name="pago_usd_efectivo" class="form-control input-desglose" value="0">
                            </div>
                            <div class="input-group mb-2">
                                <div class="input-group-prepend"><span class="input-group-text">Efectivo Bs</span></div>
                                <input type="number" step="0.01" name="pago_bs_efectivo" class="form-control input-desglose" value="0">
                            </div>
                            <div class="input-group mb-2">
                                <div class="input-group-prepend"><span class="input-group-text">Punto Bs</span></div>
                                <input type="number" step="0.01" name="pago_punto_bs" class="form-control input-desglose" value="0">
                            </div>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">P. Móvil Bs</span></div>
                                <input type="number" step="0.01" name="pago_pagomovil_bs" class="form-control input-desglose" value="0">
                            </div>
                            <p class="mt-2 small text-muted"><i class="fa fa-info-circle"></i> El desglose ayuda a cuadrar la caja diaria con el monto real que entra.</p>
                            {{-- WIDGET CALCULADORA BCV --}}
                            <div class="card bg-light border-info mt-3 shadow-sm">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="font-weight-bold text-info small">
                                            <i class="fa fa-calculator"></i> Calculadora de Conversión
                                        </span>
                                        <span class="badge badge-info p-1">
                                            Tasa BCV: <b>{{ number_format(bcv_rate('USD') ?? 0, 2, ',', '.') }}</b> Bs.
                                        </span>
                                    </div>
                                    <div class="form-row">
                                        <div class="col-6 mb-2">
                                            <label class="small text-muted mb-0 font-weight-bold">Monto $</label>

                                            <input type="number" step="0.01" class="form-control form-control-sm" id="calc_usd" placeholder="0.00">
                                        </div>
                                        <div class="col-6 mb-2">
                                            <label class="small text-muted mb-0 font-weight-bold">Monto Bs</label>
                                            <input type="number" step="0.01" class="form-control form-control-sm" id="calc_bs" placeholder="0.00">
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mb-2">Transferir monto en Bs a:</small>
                                    <div class="btn-group btn-group-sm w-100" role="group">
                                        <button type="button" class="btn btn-outline-success btn-copiar-bs" data-target="pago_bs_efectivo" title="Copiar a Efectivo Bs">
                                            Efectivo Bs
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-copiar-bs" data-target="pago_punto_bs" title="Copiar a Punto Bs">
                                            Punto Bs
                                        </button>
                                        <button type="button" class="btn btn-outline-info btn-copiar-bs" data-target="pago_pagomovil_bs" title="Copiar a Pago Móvil Bs">
                                            P. Móvil Bs
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success shadow">
                        <i class="fa fa-save"></i> Procesar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>