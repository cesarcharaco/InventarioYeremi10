@extends('layouts.app')

@section('title') Registrar Venta @endsection

@section('content')
<style>
    /* Tus estilos originales preservados */
    .select2-results__option { padding: 10px !important; border-bottom: 1px solid #eee; }
    .select2-results__option--highlighted { background-color: #f8f9fa !important; color: #000 !important; }
    .select2-container--bootstrap4 .select2-selection--single { height: calc(2.25rem + 2px) !important; }
    .badge-price { font-size: 11px !important; padding: 4px 8px; margin-right: 5px; }

    /* Ajustes Mobile First */
    @media (max-width: 768px) {
        .app-title h1 { font-size: 1.2rem; }
        .tile { margin-bottom: 10px; padding: 15px; }
        #tabla-ventas thead { display: none; }
        #tabla-ventas tr { display: block; border: 1px solid #dee2e6; margin-bottom: 10px; border-radius: 5px; padding: 10px; }
        #tabla-ventas td { display: flex; justify-content: space-between; align-items: center; border: none !important; padding: 5px 0 !important; text-align: right; }
        #tabla-ventas td:before { content: attr(data-label); font-weight: bold; float: left; text-transform: uppercase; font-size: 0.8rem; color: #666; }
        .monto-pago { font-size: 1.1rem; height: 45px; }
    }

    @keyframes pulse-red {
      0% { background-color: #ff3b3b; color: white; transform: scale(1); }
      50% { background-color: #c00; color: white; transform: scale(1.02); }
      100% { background-color: #ff3b3b; color: white; transform: scale(1); }
    }
</style>

<main class="app-content">
    <div class="app-title">
        <div>
            <h1><i class="fa fa-shopping-cart"></i> SAYER - POS | TASA: <strong>{{number_format($tasa_bcv, 2)}} Bs.</strong></h1>
            <p>Sede: <strong>{{ $local->nombre }}</strong> | Responsable: <strong>{{ $caja->user->name }}</strong></p>
        </div>
    </div>
    <div class="row">
      <div class="col-lg-12">
        <div class="basic-tb-hd text-center">            
            @include('layouts.partials.flash-messages')
            
            {{-- Errores de validación --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul style="margin-bottom: 0;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
      </div>
    </div>

    @if(Gate::denies('operar-caja'))
        <div class="tile"><div class="alert alert-danger mb-0">No tienes permisos para vender.</div></div>
    @else
    <form action="{{ route('ventas.store') }}" method="POST" id="venta-form">
        @csrf
        <input type="hidden" id="tasa_referencial" value="{{ $tasa_bcv ?? 1 }}">
        {{-- PUNTO 5: Campo oculto para el PIN de autorización --}}
        <input type="hidden" name="pin_autorizacion" id="pin_autorizacion_input">
        <input type="hidden" name="id_caja" id="id_caja" value="{{$caja->id}} ">
        <div class="row">
            <div class="col-md-12">
                <div class="tile">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="input-group-append">
                                        <button class="btn btn-primary" title="Agregar un nuevo cliente" type="button" data-toggle="modal" data-target="#modalClienteRapido"><i class="fa fa-user-plus"></i></button>
                                    </div>
                                <label class="font-weight-bold mb-2" style="font-size: 1.1rem;"><i class="fa fa-user"></i> Cliente
                                <span id="info_cliente" style="display: none; margin-left: 10px; vertical-align: middle;">
                                                <span class="badge badge-light border text-muted px-2 py-1" style="font-size: 0.85rem; font-weight: normal;">
                                                    Límite: $<span id="cliente_limite">0</span>
                                                </span>
                                                
                                                <span id="cliente_deuda_container" style="display: none;">
                                                    <span class="badge badge-danger px-2 py-1 shadow-sm" style="font-size: 0.85rem;">
                                                        Deuda: $<span id="cliente_deuda">0</span>
                                                    </span>
                                                </span>
                                            </span></label>
                                <div class="input-group">
                                    <select name="id_cliente" id="id_cliente" class="form-control select2" required>
                                        <option value="">Seleccione cliente...</option>
                                        @foreach($clientes as $cliente)
                                            {{-- PUNTO 1: Data-deuda agregado --}}
                                            <option value="{{ $cliente->id }}" 
                                                data-limite="{{ $cliente->limite_credito }}"
                                                data-deuda="{{ $cliente->saldo_pendiente ?? 0 }}">
                                                {{ $cliente->nombre }} ({{ $cliente->identificacion }}){{ !empty($cliente->alias) ? ' - Alias: ' . $cliente->alias : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    
                                    

                                </div>
                                {{-- Info de deuda para el cajero --}}
                                <small id="info_deuda_cliente" class="form-text text-danger font-weight-bold"></small>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                @if('crear-configuracion')
                                <div class="input-group-append">
                                        <button class="btn btn-primary" title="Agregar un nuevo insumo" type="button" data-toggle="modal" data-target="#modalInsumoRapido"><i class="fa fa-cubes"></i><i class="fa fa-plus ml-1" style="font-size: 0.75rem; vertical-align: super;"></i></button>
                                </div>
                                @endif
                                <label class="font-weight-bold text-primary"><i class="fa fa-search"></i> Buscador de Insumos</label>
                                <select id="buscador_insumos" class="form-control select2-custom">
                                    <option value="">Buscar por producto, descripción o serial...</option>
                                    @foreach($productos as $p)
                                    @php 
                                        $existenciaLocal = $p->existencias->where('id_local', $local->id)->first();
                                        $stockLocal = $existenciaLocal ? $existenciaLocal->cantidad : 0; 
                                    @endphp
                                    <option value="{{ $p->id }}" 
                                            data-producto="{{ $p->producto }}"
                                            data-descripcion="{{ $p->descripcion }}"
                                            data-bcv="{{ $p->precio_venta_usd }}"
                                            data-bs="{{ $p->precio_venta_bs }}"
                                            data-stock="{{ $stockLocal }}"
                                            data-serial="{{ $p->serial }}"
                                            data-en-oferta="{{ $p->en_oferta ? '1' : '0' }}"
                                            data-precio-oferta="{{ $p->precio_oferta ?? 0 }}"
                                            data-porcentaje-descuento="{{ $p->porcentaje_descuento ?? 0 }}"
                                            data-promocion-id="{{ $p->promocion_regla_id ?? '' }}">
                                        {{ $p->producto }} - {{ $p->descripcion }} ({{ $p->serial }})
                                    </option>
                                    @endforeach
                                </select>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="tile">
                    <h3 class="tile-title text-primary">Detalle de la Venta</h3>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="tabla-ventas">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>Producto</th>
                                    <th width="100px">Cant.</th>
                                    <th>Precio ($)</th>
                                    <th>Subtotal ($)</th>
                                    <th width="40px"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="tile p-0 shadow">
                    <div class="bg-dark text-white text-center p-3 rounded shadow">
                        <h6 class="mb-1 text-muted small">TOTAL A PAGAR</h6>

                        {{-- Contenedor de Referencia (Solo se muestra si hay descuento) --}}
                        <div id="contenedor_referencia_original" style="display: none; line-height: 1.2;" class="mb-2">
                            <div class="text-muted small" style="text-decoration: line-through;">
                                Antes: <span id="antes_usd">$ 0.00</span>
                            </div>
                            <div class="text-success small font-weight-bold">
                                ¡Ahorras: <span id="ahorro_usd">$ 0.00</span>!
                            </div>
                        </div>

                        {{-- TUS IDS ORIGINALES (No se tocan) --}}
                        <h1 id="total_final_usd" class="display-4 font-weight-bold mb-0">$ 0.00</h1>
                        <p id="total_final_bs" class="text-warning mb-0" style="font-size: 1.4rem;">0.00 Bs</p>
                    </div>
                    <div class="p-4">
                        {{-- ==========================================
                             INTERRUPTOR: MODO PRESUPUESTO / VENTA
                             ========================================== --}}
                        <div class="form-group mb-4 p-3 bg-light border rounded text-center shadow-sm">
                            <label class="small font-weight-bold text-uppercase d-block mb-2 text-primary">
                                <i class="fa fa-exchange"></i> Tipo de Operación
                            </label>
                            <div class="toggle-flip">
                                <label>
                                    <input type="checkbox" id="switchPresupuesto" name="es_presupuesto" value="1">
                                    <span class="flip-indictor" data-toggle-on="PRESUPUESTO" data-toggle-off="VENTA">Modo</span>
                                </label>
                            </div>
                            <small class="form-text text-muted mt-1" style="font-size: 0.75rem;">
                                Activa para emitir cotización (sin afectar caja ni inventario).
                            </small>
                        </div>
                        <div class="panel-bloqueable">
                        {{-- Sección de Descuento Refactorizada --}}
                        <div class="form-group mt-2 mb-3 p-3 border rounded shadow-sm panel-bloqueable" id="contenedor_descuento" 
                             style="background-color: {{ $ofertasActivas ? '#eef9ff' : '#f8f9fa' }}; 
                                    border: 1px solid {{ $ofertasActivas ? '#b3e5fc' : '#dee2e6' }}; 
                                    transition: all 0.3s ease;">
                            
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="small font-weight-bold {{ $ofertasActivas ? 'text-info' : 'text-primary' }} mb-0">
                                    <i class="fa fa-tag"></i> Descuento Factura
                                </label>
                                
                                @if($ofertasActivas)
                                    <span class="badge badge-info shadow-sm" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                        <i class="fa fa-star"></i> PROMO: {{ strtoupper($motivoOferta) }}
                                    </span>
                                @else
                                    <span class="badge badge-light text-muted border" style="font-size: 0.65rem;">
                                        Modo Estándar
                                    </span>
                                @endif
                            </div>

                            {{-- Select con borde tematizado --}}
                            <select id="porcentaje_descuento" name="porcentaje_descuento" 
                                    class="form-control form-control-sm {{ $ofertasActivas ? 'border-info' : 'border-primary' }}" 
                                    style="font-weight: bold; height: 35px;"
                                    {{ $ofertasActivas ? '' : 'disabled' }}>
                                <option value="0">Sin descuento (0%)</option>
                                @foreach($descuentos as $desc)
                                    <option value="{{ $desc }}">{{ $desc }}% de descuento</option>
                                @endforeach
                            </select>

                            {{-- Nota informativa sobria --}}
                            <div class="mt-2 d-flex align-items-center">
                                <i class="fa fa-info-circle mr-1 {{ $ofertasActivas ? 'text-info' : 'text-muted' }}" style="font-size: 0.8rem;"></i>
                                <small id="info-descuento" class="text-muted d-block" style="font-size: 0.7rem; line-height: 1.2;">
                                    @if($ofertasActivas)
                                        Esta promoción permite aplicar descuentos a cualquier método de pago.
                                    @else
                                        Descuento restringido: Solo se activa con pagos en divisas (Dólares/Zelle).
                                    @endif
                                </small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 col-6">
                                <div class="form-group">
                                    <label class="small font-weight-bold">Efectivo $</label>
                                    <input type="number" step="0.01" name="pago_usd_efectivo" class="form-control monto-pago" value="0">
                                </div>
                            </div>
                            <div class="col-md-6 col-6">
                                <div class="form-group">
                                    <label class="small font-weight-bold">Efectivo Bs</label>
                                    <input type="number" step="0.01" name="pago_bs_efectivo" class="form-control monto-pago" value="0">
                                </div>
                            </div>
                        </div>

                        {{-- PUNTO 2: Zelle y Botones de Referencia --}}
                        {{-- Zelle: Con referencia --}}
                        <div class="form-group">
                            <label class="small font-weight-bold">Zelle ($)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="pago_zelle_usd" class="form-control monto-pago" value="0">
                                <div class="input-group-append">
                                    <button class="btn btn-warning" type="button" onclick="solicitarReferencia('pago_zelle_usd')">
                                        <i class="fa fa-key"></i>
                                    </button>
                                </div>
                            </div>
                            <input type="hidden" name="referencia_zelle" id="referencia_zelle">
                        </div>

                        {{-- Punto / Biopago: Sin referencia --}}
                        <div class="form-group">
                            <label class="small font-weight-bold">Punto / Biopago (Bs)</label>
                            <div class="input-group">   
                                <input type="number" step="0.01" name="pago_punto_bs" class="form-control monto-pago" value="0">
                                <div class="input-group-append">
                                    <span class="input-group-text bg-primary text-white"><i class="fa fa-credit-card"></i></span>
                                </div>
                            </div>
                        </div>


                        {{-- Pago Móvil / Transf: Con referencia --}}
                        <div class="form-group">
                            <label class="small font-weight-bold">Pago Móvil / Transf. (Bs)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="pago_pagomovil_bs" class="form-control monto-pago" value="0">
                                <div class="input-group-append">
                                    <button class="btn btn-info" type="button" onclick="solicitarReferencia('pago_pagomovil_bs')">
                                        <i class="fa fa-university"></i>
                                    </button>
                                </div>
                            </div>
                            <input type="hidden" name="referencia_pagomovil" id="referencia_pagomovil">
                        </div>
                        </div>
                        <div class="alert alert-secondary mb-2 p-2">
                            <div class="d-flex justify-content-between">
                                <div class="text-center">
                                    <small class="d-block text-muted">Resta $</small>
                                    <strong id="display_restante_usd" class="text-danger">$ 0.00</strong>
                                </div>
                                <div class="text-center">
                                    <small class="d-block text-muted">Resta Bs</small>
                                    <strong id="display_restante_bs" class="text-danger">0.00 Bs</strong>
                                </div>
                            </div>
                            <div id="contenedor_excedente" class="text-center mt-1" style="display:none; border-top: 1px solid #ccc; pt-1">
                                <small class="text-muted">Excedente:</small>
                                <strong id="display_excedente_usd" class="text-success">$ 0.00</strong>
                            </div>
                        </div>

                        {{-- PUNTO 2: Sección de Abono Dinámica --}}
                        <div id="seccion_abono_excedente" class="form-group mt-3 p-3 bg-light rounded border" style="display: none;">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="pago_excedente_abono" name="pago_excedente_abono">
                                <label class="custom-control-label font-weight-bold text-info" for="pago_excedente_abono">
                                    <i class="fa fa-hand-holding-usd"></i> 
                                    ¿Desea abonar el excedente a la deuda del cliente?
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                El excedente de $<span id="monto_a_abonar">0.00</span> se aplicará como abono.
                            </small>
                        </div>

                        <div class="toggle-flip mt-2">
                            <label>
                                <input type="checkbox" id="switchCredito" name="es_credito">
                                <span class="flip-indictor" data-toggle-on="CRÉDITO" data-toggle-off="CONTADO">A Crédito</span>
                            </label>
                        </div>
                        
                        <div id="seccion_credito" style="display: none;" class="mt-2 p-2 border border-danger rounded text-center">
                            <input type="hidden" name="monto_credito_usd" id="monto_credito_usd" value="0">
                            <span class="badge badge-danger">Monto a Crédito: $<span id="label_monto_credito">0.00</span></span>
                            <small id="error_limite" class="text-danger d-block mt-1" style="display:none;"></small>
                        </div>
                        <!-- NUEVO CAMPO: OBSERVACIÓN / NOTA -->
                        <div class="form-group mt-3">
                            <label for="observacion" class="font-weight-bold text-muted mb-1">
                                <i class="fa fa-commenting-o"></i> Observación / Notas de la venta:
                            </label>
                            <textarea 
                                name="observacion" 
                                id="observacion" 
                                rows="2" 
                                class="form-control" 
                                placeholder="Ej: Incluye 2 productos no registrados, nota de entrega, etc. (Opcional)"
                            ></textarea>
                        </div>
                        <button type="submit" class="btn btn-success btn-block btn-lg mt-3 shadow" id="btn-finalizar" disabled>
                            <i class="fa fa-check-circle"></i> FINALIZAR VENTA
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @endif
</main>
@include('ventas.modal_insumo')
{{-- PUNTO 5: Modales de Seguridad --}}
<div class="modal fade" id="modalAvisoAutorizacion" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content text-center">
            <div class="modal-body p-4">
                <i class="fa fa-lock fa-3x text-warning mb-3"></i>
                <h5>Autorización Requerida</h5>
                <p class="small">Esta operación requiere el PIN del supervisor para continuar.</p>
                <button type="button" class="btn btn-warning btn-block" id="btnIrAPin">Ingresar PIN</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal para ingresar PIN (Alternativa a SweetAlert si prefieres modal fijo) --}}
<div class="modal fade" id="modalIngresoPin" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white p-2 justify-content-center">
                <h6 class="mb-0">INGRESE PIN JEFE</h6>
            </div>
            <div class="modal-body">
                <input type="password" id="pin_input_field" class="form-control text-center" maxlength="6" style="font-size: 2rem; letter-spacing: 10px;">
            </div>
            <div class="modal-footer p-1">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnValidarPin">Confirmar</button>
            </div>
        </div>
    </div>
</div>

{{-- PUNTO 5: Modal Confirmación Resumen --}}
<div class="modal fade" id="modalConfirmarVenta" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fa fa-shopping-cart"></i> Confirmar Transacción</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {{-- TIPO DE DOCUMENTO --}}
                <div class="form-group text-center mb-3">
                    <label class="small font-weight-bold text-muted">TIPO DE DOCUMENTO</label>
                    <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                        <label class="btn btn-outline-secondary active">
                            <input type="radio" name="tipo_documento" id="tipo_nota_si" value="nota_entrega" checked>
                            <i class="fa fa-check"></i> Sí, Nota de Entrega
                        </label>
                        <label class="btn btn-outline-secondary">
                            <input type="radio" name="tipo_documento" id="tipo_nota_no" value="sin_documento">
                            <i class="fa fa-times"></i> No, sin documento
                        </label>
                        <label class="btn btn-outline-primary" id="btn_tipo_factura">
                            <input type="radio" name="tipo_documento" id="tipo_factura" value="factura">
                            <i class="fa fa-file-invoice-dollar"></i> Factura Fiscal
                        </label>
                    </div>
                    <input type="hidden" name="tipo_documento_hidden" id="tipo_documento_hidden" value="nota_entrega">
                    <input type="hidden" name="correlativo_nota" id="correlativo_nota" value="{{ $correlativo_sugerido }}">
                </div>

                <div class="text-center mb-3">
                    <h6 class="text-muted">TOTAL A COBRAR</h6>
                    <h2 id="confirm_total_usd" class="font-weight-bold text-dark">$ 0.00</h2>
                    <h5 id="confirm_total_bs" class="text-secondary">0.00 Bs</h5>
                </div>

                {{-- SECCIÓN DESCUENTO --}}
                <div id="confirm_seccion_descuento" class="text-center mb-3 p-2 bg-light rounded" style="display: none;">
                    <div class="text-muted small" style="text-decoration: line-through;">
                        Antes: <span id="confirm_antes_usd">$ 0.00</span>
                    </div>
                    <div class="text-success font-weight-bold">
                        Descuento <span id="confirm_porcentaje">0</span>%: 
                        -<span id="confirm_descuento_usd">$ 0.00</span>
                    </div>
                </div>

                {{-- SECCIÓN IVA (SIEMPRE VISIBLE) --}}
                <div id="confirm_seccion_iva" class="mb-3 p-2 bg-light rounded border border-info">
                    <h6 class="text-info font-weight-bold text-center mb-2">
                        <i class="fa fa-calculator"></i> DETALLE FISCAL
                    </h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Base Imponible:</td>
                            <td class="text-right font-weight-bold">
                                <span id="confirm_base_imponible_bs">0.00</span> Bs
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">IVA (16%):</td>
                            <td class="text-right font-weight-bold text-info">
                                <span id="confirm_iva_bs">0.00</span> Bs
                            </td>
                        </tr>
                    </table>
                </div>

                <table class="table table-sm table-bordered">
                    <tr class="bg-light">
                        <th>Método / Concepto</th>
                        <th class="text-right">Monto</th>
                    </tr>
                    <tr><td>Efectivo USD</td><td id="confirm_p_usd" class="text-right">$ 0.00</td></tr>
                    <tr><td>Efectivo BS</td><td id="confirm_p_bs_efec" class="text-right">0.00 Bs</td></tr>
                    <tr><td>Zelle</td><td id="confirm_p_zelle" class="text-right">$ 0.00</td></tr>
                    <tr><td>Punto / Biopago</td><td id="confirm_p_punto" class="text-right">0.00 Bs</td></tr>
                    <tr><td>Pago Móvil</td><td id="confirm_p_pm" class="text-right">0.00 Bs</td></tr>
                    <tr id="fila_confirm_abono" style="display:none;" class="table-info">
                        <td class="font-weight-bold">Abono a Deuda</td>
                        <td id="confirm_monto_abono" class="text-right font-weight-bold">$ 0.00</td>
                    </tr>
                    <tr id="fila_confirm_credito" style="display:none;" class="table-danger">
                        <td class="font-weight-bold">Monto a CRÉDITO</td>
                        <td id="confirm_monto_credito" class="text-right font-weight-bold">$ 0.00</td>
                    </tr>
                </table>

                <div class="alert alert-warning text-center">
                    <p class="mb-0 small">¿Está seguro que desea procesar esta venta? <br><strong>Esta acción no se puede deshacer.</strong></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success btn-lg" id="btnProcesarVentaFinal">
                    <i class="fa fa-check"></i> SÍ, PROCESAR VENTA
                </button>
            </div>
        </div>
    </div>
</div>

{{-- PUNTO 3: Registro Rápido (Campos estrictos) --}}
<div class="modal fade" id="modalClienteRapido" role="dialog" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-user-plus"></i> Registro Rápido de Cliente</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formClienteRapido">
                <div class="modal-body">
                    <input type="hidden" name="id_local" value="{{ $local->id }}">
                    
                    <div class="form-group">
                        <label>Identificación (Cédula/RIF)</label>
                        <input type="text" name="identificacion" class="form-control" required placeholder="V-12345678" maxlength="9">
                    </div>
                    <div class="form-group">
                        <label>Nombre Completo / Razón Social</label>
                        <input type="text" name="nombre" class="form-control" required placeholder="Juan Perez">
                    </div>
                    <div class="form-group">
                        <label>Alias</label>
                        <input type="text" name="alias" class="form-control" placeholder="Juancho">
                    </div>
                    <div class="form-group">
                        <label>Teléfono (Opcional)</label>
                        <input type="text" name="telefono" class="form-control" placeholder="0412-1234567">
                    </div>
                    <div class="form-group">
                        <label>Límite de Crédito ($)</label>
                        <input type="number" step="0.01" name="limite_credito" class="form-control" value="1000">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="btnGuardarCliente">
                        <i class="fa fa-save"></i> Guardar Cliente
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Dinámico para Impresión de Documentos (Factura / Nota de Entrega) -->
<div class="modal fade" id="modalImprimirDocumento" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="modalImprimirLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalImprimirLabel">
          <i class="fa fa-print"></i> Venta Procesada Exitosamente
        </h5>
      </div>
      <div class="modal-body text-center py-4">
        <i class="fa fa-file-text-o fa-4x text-success mb-3"></i>
        <h4 id="modal_titulo_doc">Documento generado</h4>
        <h3 class="text-primary"><strong id="modal_codigo_doc"></strong></h3>
        <p class="text-muted mt-2">¿Desea imprimir el comprobante / ticket de esta venta?</p>
      </div>
      <div class="modal-footer justify-content-center">
        <!-- Botón para Confirmar e Imprimir -->
        <a href="#" id="btn_confirmar_impresion" target="_blank" class="btn btn-primary btn-lg">
          <i class="fa fa-print"></i> Imprimir Ticket
        </a>
        
        <!-- Botón para Cancelar u Omitir -->
        <button type="button" class="btn btn-secondary btn-lg" data-dismiss="modal">
          <i class="fa fa-times"></i> Omitir / Cancelar
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    'use strict';

    const TASA = parseFloat($('#tasa_referencial').val()) || 1;
    let detalleVentas = [];
    window.subtotalSinDescuento = 0; // Subtotal antes de descuento GLOBAL de factura
    window.subtotalSinDescuentoBs = 0;

    // ==========================================================
    // 1. CONFIGURACIÓN ÚNICA DE SELECT2 (Buscador de Insumos)
    // ==========================================================
    function formatRepo(repo) {
        if (repo.loading || !repo.id) return repo.text;
        
        let ds = repo.element ? repo.element.dataset : {};
        let precioBCV = parseFloat(ds.bcv) || 0;
        let precioBS = parseFloat(ds.bs) || 0;
        let stock = parseInt(ds.stock) || 0;
        let enOferta = ds.enOferta === "1" || ds.enOferta === 1;
        let precioOferta = parseFloat(ds.precioOferta) || 0;
        let porcentajeDescuento = parseFloat(ds.porcentajeDescuento) || 0;

        let preciosHtml = (enOferta && porcentajeDescuento > 0) ? `
            <span class='badge badge-success'>BCV: <del>$${precioBCV.toFixed(2)}</del> <strong>$${precioOferta.toFixed(2)}</strong></span>
            <span class='badge badge-warning text-dark'><i class="fa fa-tag"></i> -${porcentajeDescuento}%</span>
            <span class='badge badge-primary'>BS: ${(precioOferta * TASA).toFixed(2)} Bs</span>
        ` : `
            <span class='badge badge-success'>BCV: $${precioBCV.toFixed(2)}</span>
            <span class='badge badge-primary'>BS: ${precioBS.toFixed(2)} Bs</span>
        `;

        let $container = $(`
            <div class='select2-result-repository clearfix' style='${stock <= 0 ? "opacity: 0.6;" : ""}'>
                <div class='select2-result-repository__meta'>
                    <div class='d-flex justify-content-between'>
                        <span class='select2-result-repository__title font-weight-bold text-dark'></span>
                        <small class='text-muted'>${ds.serial || ''}</small>
                    </div>
                    <div class='select2-result-repository__description text-muted small mb-1'></div>
                    <div class='d-flex flex-wrap' style='gap: 5px;'>
                        ${preciosHtml}
                        <span class='badge ${stock > 0 ? 'badge-dark' : 'badge-danger'}'>📦 Stock: ${stock}</span>
                    </div>
                </div>
            </div>
        `);

        $container.find(".select2-result-repository__title").text(repo.text);
        $container.find(".select2-result-repository__description").text(ds.descripcion || 'Sin descripción');
        return $container;
    }

    function matchCustom(params, data) {
        if ($.trim(params.term) === '') return data;
        if (!data.element) return null;

        let term = params.term.toLowerCase();
        let ds = data.element.dataset;
        let texto = (ds.producto || data.text).toLowerCase();
        let desc = (ds.descripcion || '').toLowerCase();
        let serial = (ds.serial || '').toLowerCase();

        return (texto.includes(term) || desc.includes(term) || serial.includes(term)) ? data : null;
    }

    // Inicialización ÚNICA
    if ($('#buscador_insumos').hasClass("select2-hidden-accessible")) {
        $('#buscador_insumos').select2('destroy');
    }

    $('#buscador_insumos').select2({
        theme: 'bootstrap4',
        templateResult: formatRepo,
        templateSelection: repo => repo.text,
        matcher: matchCustom,
        width: '100%',
        escapeMarkup: m => m
    });

    // ==========================================================
    // 2. AGREGAR PRODUCTO AL CARRITO (Con lógica de Promoción)
    // ==========================================================
    $('#buscador_insumos').on('select2:select', function(e) {
        let ds = e.params.data.element.dataset;
        let id = $(this).val();
        
        let stock = parseInt(ds.stock) || 0;
        if (stock <= 0) {
            Swal.fire('Sin Stock', 'No hay existencias de este producto', 'error');
            $(this).val(null).trigger('change');
            return;
        }

        let producto = ds.producto || e.params.data.text.trim();
        let descripcion = ds.descripcion || '';
        let precioBaseBcv = parseFloat(ds.bcv) || 0;
        let enOferta = ds.enOferta === "1" || ds.enOferta === 1;
        let precioOferta = parseFloat(ds.precioOferta) || 0;
        let porcentajeDescuento = parseFloat(ds.porcentajeDescuento) || 0;
        let promocionReglaId = ds.promocionId ? parseInt(ds.promocionId) : null;
        
        // PRECIO BCV: Usa oferta si está activa, si no el base
        let precio_bcv = (enOferta && precioOferta > 0) ? precioOferta : precioBaseBcv;
        // PRECIO BS: Se recalcula SIEMPRE a partir del precio_bcv final * TASA para mantener coherencia
        let precio_bs = precio_bcv * TASA;

        let existe = detalleVentas.find(item => item.id === id);
        if (existe) {
            if (existe.cantidad + 1 > stock) {
                Swal.fire('Límite de Stock', 'No puedes agregar más de lo disponible', 'warning');
                $(this).val(null).trigger('change');
                return;
            }
            existe.cantidad++;
        } else {
            detalleVentas.push({ 
                id, 
                producto, 
                descripcion, 
                precio_bcv, 
                precio_bs, 
                cantidad: 1, 
                stock,
                promocion_regla_id: promocionReglaId,
                porcentaje_descuento_aplicado: enOferta ? porcentajeDescuento : 0
            });
        }

        $(this).val(null).trigger('change');
        renderTabla();
    });

    // ==========================================================
    // 3. RENDERIZAR TABLA DE VENTAS
    // ==========================================================
    function renderTabla() {
        let html = '';
        let totalUSD = 0;

        detalleVentas.forEach((item, index) => {
            let subtotal = item.cantidad * item.precio_bcv;
            totalUSD += subtotal;

            let descHtml = item.descripcion ? `<small class="d-block text-muted" style="font-size: 11px;">${item.descripcion}</small>` : '';
            
            let badgePromo = (item.porcentaje_descuento_aplicado > 0) 
                ? `<span class="badge badge-warning" style="font-size: 9px;"><i class="fa fa-tag"></i> Promo -${item.porcentaje_descuento_aplicado}%</span>` 
                : '';

            html += `<tr>
                <td data-label="Producto">
                    <strong>${item.producto}</strong> ${badgePromo}
                    ${descHtml}
                </td>
                <td data-label="Cant.">
                    <input type="number" class="form-control form-control-sm text-center change-cant" 
                        data-index="${index}" value="${item.cantidad}" min="1" max="${item.stock}" style="width: 70px; display:inline-block;">
                </td>
                <td data-label="Precio">$${item.precio_bcv.toFixed(2)}</td>
                <td data-label="Subtotal" class="font-weight-bold">$${subtotal.toFixed(2)}</td>
                <td data-label="Acción">
                    <button type="button" class="btn btn-sm btn-danger remove-item" data-index="${index}">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
                <input type="hidden" name="articulos[${index}][id_insumo]" value="${item.id}">
                <input type="hidden" name="articulos[${index}][cantidad]" value="${item.cantidad}">
                <input type="hidden" name="articulos[${index}][precio_unitario]" value="${item.precio_bcv}">
                <input type="hidden" name="articulos[${index}][promocion_regla_id]" value="${item.promocion_regla_id ?? ''}">
                <input type="hidden" name="articulos[${index}][porcentaje_descuento_aplicado]" value="${item.porcentaje_descuento_aplicado}">
                <input type="hidden" name="articulos[${index}][subtotal]" value="${subtotal.toFixed(2)}">
            </tr>`;
        });

        if (detalleVentas.length === 0) {
            html = '<tr><td colspan="5" class="text-center text-muted py-3">El carrito está vacío</td></tr>';
        }
        
        $('#tabla-ventas tbody').html(html);
        actualizarTotales(totalUSD);
    }

    // ==========================================================
    // 4. ACTUALIZAR TOTALES (Descuento Global + Promos ya aplicadas)
    // ==========================================================
    function actualizarTotales(subtotalUSD) {
        // Guardamos el subtotal que YA incluye descuentos por producto (promociones)
        window.subtotalSinDescuento = subtotalUSD;
        let subtotalBS = subtotalUSD * TASA;
        window.subtotalSinDescuentoBs = subtotalBS;

        const ofertaGlobalActiva = {{ $ofertasActivas ? 'true' : 'false' }};
        const porcentajeDescuento = parseFloat($('#porcentaje_descuento').val()) || 0;
        
        let totalUSD = subtotalUSD;
        let totalBS = subtotalBS;
        let montoDescuentoUSD = 0;
        let montoDescuentoBS = 0;
        
        // Verificar si hay pago en divisas (para habilitar descuento manual si no hay oferta global)
        const hayPagoDivisas = (parseFloat($('input[name="pago_usd_efectivo"]').val()) || 0) > 0 
                            || (parseFloat($('input[name="pago_zelle_usd"]').val()) || 0) > 0;
        
        // APLICAR DESCUENTO GLOBAL SOBRE EL TOTAL (si aplica)
        if (porcentajeDescuento > 0 && (ofertaGlobalActiva || hayPagoDivisas)) {
            montoDescuentoUSD = subtotalUSD * (porcentajeDescuento / 100);
            montoDescuentoBS = subtotalBS * (porcentajeDescuento / 100);
            
            totalUSD = subtotalUSD - montoDescuentoUSD;
            totalBS = subtotalBS - montoDescuentoBS;
        }
        
        // Visualización del descuento global
        if (montoDescuentoUSD > 0) {
            $('#antes_usd').text('$ ' + subtotalUSD.toFixed(2));
            $('#ahorro_usd').text('$ ' + montoDescuentoUSD.toFixed(2));
            $('#contenedor_referencia_original').slideDown(200);
            $('#total_final_usd').addClass('text-success');
        } else {
            $('#contenedor_referencia_original').slideUp(200);
            $('#total_final_usd').removeClass('text-success');
        }
        
        // Display Principal
        $('#total_final_usd').text(`$ ${totalUSD.toFixed(2)}`);
        $('#total_final_bs').text(`${totalBS.toFixed(2)} Bs`);
        
        // Inputs Hidden para Backend
        if ($('#total_hidden').length === 0) {
            $('#venta-form').append(`<input type="hidden" name="total_usd" id="total_hidden">`);
        }
        $('#total_hidden').val(totalUSD.toFixed(2));

        if ($('#total_bs_hidden').length === 0) {
            $('#venta-form').append(`<input type="hidden" name="total_bs" id="total_bs_hidden">`);
        }
        $('#total_bs_hidden').val(totalBS.toFixed(2));
        
        if ($('#descuento_usd_hidden').length === 0) {
            $('#venta-form').append(`<input type="hidden" name="descuento_usd" id="descuento_usd_hidden">`);
        }
        $('#descuento_usd_hidden').val(montoDescuentoUSD.toFixed(2));
        
        if ($('#descuento_bs_hidden').length === 0) {
            $('#venta-form').append(`<input type="hidden" name="descuento_bs" id="descuento_bs_hidden">`);
        }
        $('#descuento_bs_hidden').val(montoDescuentoBS.toFixed(2));
        
        if ($('#porcentaje_descuento_hidden').length === 0) {
            $('#venta-form').append(`<input type="hidden" name="porcentaje_descuento" id="porcentaje_descuento_hidden">`);
        }
        $('#porcentaje_descuento_hidden').val(porcentajeDescuento);

        // Reset de pagos si carrito vacío
        if (totalUSD <= 0) {
            $('.monto-pago').val('');
            $('input[name^="referencia_"]').val('');
        }

        actualizarCalculoPagos();
    }

    // ==========================================================
    // 5. CÁLCULO DE PAGOS Y VALIDACIÓN
    // ==========================================================
    function actualizarCalculoPagos() {
        let totalFacturaUSD = parseFloat($('#total_hidden').val()) || 0;
        
        // Modo Presupuesto: no validar faltantes
        if ($('#switchPresupuesto').is(':checked')) {
            $('#btn-finalizar')
                .prop('disabled', totalFacturaUSD <= 0)
                .attr('class', 'btn btn-warning btn-block btn-lg mt-3 shadow font-weight-bold text-dark')
                .html('<i class="fa fa-file-pdf-o"></i> GENERAR PRESUPUESTO');
            return;
        }

        let totalFacturaBs = totalFacturaUSD * TASA;

        let pUSD = parseFloat($('input[name="pago_usd_efectivo"]').val()) || 0;
        let pZelle = parseFloat($('input[name="pago_zelle_usd"]').val()) || 0;
        let pBsEfec = parseFloat($('input[name="pago_bs_efectivo"]').val()) || 0;
        let pBsPunto = parseFloat($('input[name="pago_punto_bs"]').val()) || 0;
        let pBsPM = parseFloat($('input[name="pago_pagomovil_bs"]').val()) || 0;
        
        let totalBsPagado = pBsEfec + pBsPunto + pBsPM;
        let totalUSDPagado = pUSD + pZelle;

        let inputActivo = document.activeElement;
        let esInputBs = inputActivo && $(inputActivo).hasClass('monto-pago') && (
            $(inputActivo).attr('name') === 'pago_bs_efectivo' ||
            $(inputActivo).attr('name') === 'pago_punto_bs' ||
            $(inputActivo).attr('name') === 'pago_pagomovil_bs'
        );

        let checkboxAbono = $('#pago_excedente_abono');
        let deudaCliente = parseFloat($('#id_cliente option:selected').data('deuda')) || 0;
        let esCredito = $('#switchCredito').is(':checked');
        
        // Reset visual
        $('#display_restante_usd').removeClass('text-warning text-success text-danger');
        $('#display_restante_bs').removeClass('text-warning text-success text-danger');
        $('#contenedor_excedente').hide();
        $('#seccion_abono_excedente').hide();
        $('#alerta-exceso').remove();

        let restanteUSD, restanteBs;
        
        if (esInputBs) {
            let totalBsEquivalentePagado = totalBsPagado + (totalUSDPagado * TASA);
            restanteBs = totalFacturaBs - totalBsEquivalentePagado;
            restanteUSD = (Math.abs(restanteBs) > 0.01) ? restanteBs / TASA : 0;
            if (Math.abs(restanteBs) <= 0.01) restanteBs = 0;
        } else {
            restanteUSD = totalFacturaUSD - totalUSDPagado - (totalBsPagado / TASA);
            if (Math.abs(restanteUSD) > 0.01) {
                restanteBs = restanteUSD * TASA;
            } else {
                restanteUSD = 0;
                restanteBs = 0;
            }
        }

        let diffUSD = Math.round(restanteUSD * 100) / 100;
        let diffBs = Math.round(restanteBs * 100) / 100;

        if (diffUSD > 0.01 || diffBs > 0.01) {
            // FALTANTE
            $('#seccion_abono_excedente').hide();
            $('#display_restante_usd').text(`$ ${Math.abs(restanteUSD).toFixed(2)}`).addClass('text-danger');
            $('#display_restante_bs').text(`${Math.abs(restanteBs).toFixed(2)} Bs`).addClass('text-danger');
            
            if (esCredito) {
                $('#monto_credito_usd').val(restanteUSD.toFixed(2));
                $('#label_monto_credito').text(restanteUSD.toFixed(2));
                validarLimiteCredito(restanteUSD);
            } else {
                $('#btn-finalizar').prop('disabled', true).html('<i class="fa fa-times-circle"></i> FALTANTE');
            }

        } else if (diffUSD < -0.01 || diffBs < -0.01) {
            // EXCESO
            let excesoUSD = Math.abs(restanteUSD);
            $('#display_restante_usd').text("$ 0.00").removeClass('text-danger');
            $('#display_restante_bs').text("0.00 Bs").removeClass('text-danger');
            $('#contenedor_excedente').show();
            $('#display_excedente_usd').text(`$ ${excesoUSD.toFixed(2)}`);

            if (deudaCliente > 0) {
                if (excesoUSD > deudaCliente) {
                    $('#seccion_abono_excedente').hide();
                    $('#btn-finalizar').prop('disabled', true).html('<i class="fa fa-ban"></i> EXCEDENTE SOBRE DEUDA');
                    Swal.fire({
                        icon: 'warning', title: 'Excedente inválido',
                        text: `El excedente ($${excesoUSD.toFixed(2)}) supera la deuda ($${deudaCliente.toFixed(2)})`,
                        toast: true, position: 'top-end', showConfirmButton: false, timer: 3000
                    });
                } else {
                    $('#seccion_abono_excedente').fadeIn();
                    $('#monto_a_abonar').text(excesoUSD.toFixed(2));
                    if (checkboxAbono.is(':checked')) {
                        $('#btn-finalizar').prop('disabled', false).html('<i class="fa fa-check-circle"></i> FINALIZAR (+ ABONO)');
                    } else {
                        $('#btn-finalizar').prop('disabled', true).html('<i class="fa fa-hand-paper"></i> ¿ES ABONO?');
                    }
                }
            } else {
                $('#btn-finalizar').prop('disabled', true).html('<i class="fa fa-exclamation-triangle"></i> EXCESO');
            }

        } else {
            // EXACTO
            $('#display_restante_usd').text('$ 0.00').removeClass('text-danger');
            $('#display_restante_bs').text('0.00 Bs').removeClass('text-danger');
            $('#btn-finalizar').prop('disabled', false).html('<i class="fa fa-check-circle"></i> FINALIZAR VENTA');
        }

        if (deudaCliente > 0) {
            $('#aviso_deuda_cliente').html(`<i class="fa fa-info-circle"></i> Deuda pendiente: $${deudaCliente.toFixed(2)}`).show();
        } else {
            $('#aviso_deuda_cliente').hide();
        }
    }

    function validarLimiteCredito(monto) {
        let limite = parseFloat($('#id_cliente option:selected').data('limite')) || 0;
        let pinAutorizado = $('#pin_autorizacion').val();

        if (monto > limite && !pinAutorizado) {
            $('#error_limite').html(`<i class="fa fa-exclamation-circle"></i> Límite excedido (Máx: $${limite.toFixed(2)})`).show();
            $('#btn-finalizar').prop('disabled', true).html('<i class="fa fa-lock"></i> REQUIERE AUTORIZACIÓN');
        } else {
            $('#error_limite').hide();
            if (monto > 0) {
                $('#btn-finalizar').prop('disabled', false).html('<i class="fa fa-check-circle"></i> FINALIZAR CRÉDITO');
            }
        }
    }

    // ==========================================================
    // 6. LISTENERS DE LA TABLA
    // ==========================================================
    $(document).on('input', '.monto-pago', actualizarCalculoPagos);

    $(document).on('click', '.remove-item', function() {
        detalleVentas.splice($(this).data('index'), 1);
        renderTabla();
    });

    $(document).on('change', '.change-cant', function() {
        let index = $(this).data('index');
        let val = parseInt($(this).val());
        let stock = detalleVentas[index].stock;

        if (val > stock) {
            Swal.fire('Stock insuficiente', `Solo hay ${stock} disponibles`, 'warning');
            val = stock;
            $(this).val(stock);
        }
        detalleVentas[index].cantidad = val || 1;
        renderTabla();
    });

    // ==========================================================
    // 7. DESCUENTO GLOBAL
    // ==========================================================
    $('#porcentaje_descuento').on('change', function() {
        if (window.subtotalSinDescuento > 0) {
            actualizarTotales(window.subtotalSinDescuento);
        }
    });

    $(document).on('input', 'input[name="pago_usd_efectivo"], input[name="pago_zelle_usd"]', function() {
        gestionarEstadoDescuento();
    });

    function gestionarEstadoDescuento() {
        const ofertaGlobalActiva = {{ $ofertasActivas ? 'true' : 'false' }};
        if (ofertaGlobalActiva) return; 

        let dolares = parseFloat($('input[name="pago_usd_efectivo"]').val()) || 0;
        let zelle = parseFloat($('input[name="pago_zelle_usd"]').val()) || 0;
        
        const $select = $('#porcentaje_descuento');
        const $contenedor = $('#contenedor_descuento');
        const $info = $('#info-descuento');
        const teniaDescuento = parseFloat($select.val()) > 0;

        if (dolares > 0 || zelle > 0) {
            $select.prop('disabled', false);
            $contenedor.css({ 'background-color': '#fff9db', 'border-color': '#ffe066' });
            $info.html('<i class="fa fa-unlock text-warning"></i> Descuento habilitado por pago en divisas.');
        } else {
            $select.prop('disabled', true).val(0);
            $contenedor.css({ 'background-color': '#f8f9fa', 'border-color': '#dee2e6' });
            $info.text('* Se habilitará automáticamente al ingresar Dólares o Zelle.');
            
            if (teniaDescuento && window.subtotalSinDescuento > 0) {
                actualizarTotales(window.subtotalSinDescuento);
            }
        }
    }

    // ==========================================================
    // 8. CLIENTE Y CRÉDITO
    // ==========================================================
    $('#id_cliente').on('change', function() {
        let limite = $('option:selected', this).data('limite') || 0;
        let deuda = $('option:selected', this).data('deuda') || 0;
        
        $('#cliente_limite').text(parseFloat(limite).toFixed(2));
        
        if (deuda > 0) {
            $('#cliente_deuda').text(parseFloat(deuda).toFixed(2));
            $('#cliente_deuda_container').show();
        } else {
            $('#cliente_deuda_container').hide();
        }
        $('#info_cliente').show();
    });

    $(document).on('change', '#pago_excedente_abono', function() {
        actualizarCalculoPagos();
    });

    // ==========================================================
    // 9. REFERENCIAS DE PAGO (Zelle / Pago Móvil)
    // ==========================================================
    window.solicitarReferencia = function(metodo) {
        const mapeo = {
            'pago_pagomovil_bs': { 
                t: 'Referencia Pago Móvil', id: 'referencia_pagomovil',
                btnClass: 'btn-info', icon: 'fa-university'
            },
            'pago_zelle_usd': { 
                t: 'Confirmación Zelle', id: 'referencia_zelle',
                btnClass: 'btn-warning', icon: 'fa-key'
            }
        };

        if (!mapeo[metodo]) return;

        if ($(`#${mapeo[metodo].id}`).length === 0) {
            $('#venta-form').append(`<input type="hidden" name="${mapeo[metodo].id}" id="${mapeo[metodo].id}">`);
        }

        let valorActual = $(`#${mapeo[metodo].id}`).val();

        Swal.fire({
            title: mapeo[metodo].t,
            input: 'text',
            inputLabel: 'Ingrese el número de comprobante',
            inputPlaceholder: 'Ej: 123456',
            inputValue: valorActual,
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            heightAuto: false,
            inputAttributes: { autocapitalize: 'off', autocorrect: 'off', autocomplete: 'off' }
        }).then((result) => {
            if (result.isConfirmed) {
                $(`#${mapeo[metodo].id}`).val(result.value);
                let $input = $(`input[name="${metodo}"]`);
                let $boton = $input.closest('.input-group').find('button');
                
                if (result.value.trim() !== "") {
                    $input.addClass('is-valid');
                    $boton.removeClass('btn-warning btn-primary btn-info').addClass('btn-success').html('<i class="fa fa-check"></i>');
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Referencia guardada', showConfirmButton: false, timer: 1500 });
                } else {
                    $input.removeClass('is-valid');
                    $boton.removeClass('btn-success').addClass(mapeo[metodo].btnClass).html(`<i class="fa ${mapeo[metodo].icon}"></i>`);
                }
            }
        });
    };

    // ==========================================================
    // 10. TOGGLE PRESUPUESTO
    // ==========================================================
    $('#switchPresupuesto').on('change', function() {
        const $form = $('#venta-form');
        const $btn = $('#btn-finalizar');
        const $paneles = $('.panel-bloqueable');
        let totalUSD = parseFloat($('#total_hidden').val()) || 0;

        if ($(this).is(':checked')) {
            $form.attr('action', "{{ route('ventas.presupuesto') }}").attr('target', '_blank');
            $btn.attr('class', 'btn btn-warning btn-block btn-lg mt-3 shadow font-weight-bold text-dark')
                .html('<i class="fa fa-file-pdf-o"></i> GENERAR PRESUPUESTO');
            $btn.prop('disabled', detalleVentas.length === 0);
            
            $paneles.css({ 'opacity': '0.3', 'pointer-events': 'none' });
            $paneles.find('input, select, button').prop('disabled', true);
            
            if ($('#switchCredito').is(':checked')) {
                $('#switchCredito').prop('checked', false).trigger('change');
            }
        } else {
            $form.attr('action', "{{ route('ventas.store') }}").removeAttr('target');
            $btn.attr('class', 'btn btn-success btn-block btn-lg mt-3 shadow')
                .html('<i class="fa fa-check-circle"></i> FINALIZAR VENTA');
            
            $paneles.css({ 'opacity': '1', 'pointer-events': 'auto' });
            const ofertaGlobalActiva = {{ $ofertasActivas ? 'true' : 'false' }};
            $paneles.find('input, select, button').each(function() {
                if (this.id === 'porcentaje_descuento' && !ofertaGlobalActiva) return;
                $(this).prop('disabled', false);
            });
            actualizarCalculoPagos();
        }
    });

    // ==========================================================
    // 11. TOGGLE CRÉDITO
    // ==========================================================
    $('#switchCredito').on('change', function() {
        let checkbox = $(this);
        
        if (checkbox.is(':checked')) {
            @cannot('gestionar-creditos-avanzado')
                checkbox.prop('checked', false);
                Swal.fire({
                    title: '¿Solicitar Autorización?',
                    text: "Se enviará un PIN de 6 dígitos al WhatsApp del jefe para habilitar este crédito.",
                    icon: 'warning', showCancelButton: true,
                    confirmButtonText: 'Sí, enviar PIN', cancelButtonText: 'Cancelar', allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post("{{ route('ventas.solicitar_pin') }}", {
                            _token: "{{ csrf_token() }}",
                            local_nombre: "{{ $local->nombre }}",
                            cliente_nombre: $('#id_cliente option:selected').text().trim(),
                            monto_total: $('#total_hidden').val(),
                            cantidad_items: detalleVentas.length
                        }, function(response) {
                            if(response.wa_link) window.open(response.wa_link, '_blank');
                            
                            Swal.fire({
                                title: 'Introduce el PIN', text: 'El jefe recibió un código de 6 dígitos',
                                input: 'text', inputAttributes: { maxlength: 6, autocapitalize: 'off' },
                                showCancelButton: true, confirmButtonText: 'Validar PIN', cancelButtonText: 'Cancelar',
                                showLoaderOnConfirm: true, allowOutsideClick: false,
                                preConfirm: (pin) => {
                                    return $.post("{{ route('ventas.verificar_pin') }}", {
                                        _token: "{{ csrf_token() }}", pin: pin
                                    }).done(() => {
                                        $('#pin_autorizacion').val(pin);
                                    }).fail(error => {
                                        Swal.showValidationMessage(error.responseJSON.message);
                                    });
                                }
                            }).then((res) => {
                                if (res.isConfirmed) {
                                    checkbox.prop('checked', true);
                                    $('#seccion_credito').fadeIn();
                                    actualizarCalculoPagos();
                                    Swal.fire('Autorizado', 'Crédito desbloqueado.', 'success');
                                } else {
                                    $('#seccion_credito').hide();
                                    checkbox.prop('checked', false);
                                    $('#pin_autorizacion').val('');
                                    actualizarCalculoPagos();
                                }
                            });
                        });
                    } else {
                        $('#seccion_credito').hide();
                        checkbox.prop('checked', false);
                        actualizarCalculoPagos();
                    }
                });
            @else
                $('#seccion_credito').fadeIn();
                actualizarCalculoPagos();
            @endcannot
        } else {
            $('#pin_autorizacion').val('');
            $('#seccion_credito').fadeOut();
            actualizarCalculoPagos();
        }
    });

    // ==========================================================
    // 12. SUBMIT DEL FORMULARIO
    // ==========================================================
    $(document).on('change', 'input[name="tipo_documento"]', function() {
        if ($('#tipo_documento_real').length === 0) {
            $('#venta-form').append('<input type="hidden" name="tipo_documento" id="tipo_documento_real">');
        }
        $('#tipo_documento_real').val($(this).val());
        $('input[name="tipo_documento"]').closest('label').removeClass('active');
        $(this).closest('label').addClass('active');
    });

    $('#venta-form').on('submit', function(e) {
        if ($('#switchPresupuesto').is(':checked')) {
            $(this).attr('action', '{{ route("ventas.presupuesto") }}').attr('target', '_blank');
            setTimeout(() => {
                $('#btn-finalizar').prop('disabled', false).html('<i class="fa fa-file-pdf-o"></i> GENERAR PRESUPUESTO');
            }, 1200);
            return true;
        }

        $(this).attr('action', '{{ route("ventas.store") }}').removeAttr('target');
        e.preventDefault();
        e.stopImmediatePropagation();

        if (!detalleVentas || detalleVentas.length === 0) {
            Swal.fire('Carrito Vacío', 'Debes agregar al menos un producto.', 'error');
            return false;
        }

        // Validar referencias si hay montos
        if ((parseFloat($('input[name="pago_zelle_usd"]').val())||0) > 0 && !$('#referencia_zelle').val()) {
            Swal.fire('Referencia faltante', 'Ingrese la referencia de Zelle.', 'warning'); return false;
        }
        if ((parseFloat($('input[name="pago_pagomovil_bs"]').val())||0) > 0 && !$('#referencia_pagomovil').val()) {
            Swal.fire('Referencia faltante', 'Ingrese la referencia del Pago Móvil.', 'warning'); return false;
        }

        // Cargar datos al modal de confirmación
        $('#confirm_total_usd').text($('#total_final_usd').text());
        $('#confirm_total_bs').text($('#total_final_bs').text());
        $('#confirm_p_usd').text($('input[name="pago_usd_efectivo"]').val() || '0.00');
        $('#confirm_p_bs_efec').text($('input[name="pago_bs_efectivo"]').val() || '0.00');
        $('#confirm_p_zelle').text($('input[name="pago_zelle_usd"]').val() || '0.00');
        $('#confirm_p_punto').text($('input[name="pago_punto_bs"]').val() || '0.00');
        $('#confirm_p_pm').text($('input[name="pago_pagomovil_bs"]').val() || '0.00');

        let montoDesc = parseFloat($('#descuento_usd_hidden').val()) || 0;
        if (montoDesc > 0) {
            $('#confirm_seccion_descuento').show();
            $('#confirm_antes_usd').text('$ ' + window.subtotalSinDescuento.toFixed(2));
            $('#confirm_porcentaje').text($('#porcentaje_descuento').val());
            $('#confirm_descuento_usd').text('$ ' + montoDesc.toFixed(2));
        } else {
            $('#confirm_seccion_descuento').hide();
        }

        // IVA
        let totalBs = parseFloat($('#total_bs_hidden').val()) || 0;
        let baseImponible = totalBs / 1.16;
        let iva = baseImponible * 0.16;
        $('#confirm_base_imponible_bs').text(baseImponible.toFixed(2));
        $('#confirm_iva_bs').text(iva.toFixed(2));

        // Abono
        let excedente = parseFloat($('#display_excedente_usd').text().replace(/[^0-9.]/g, '')) || 0;
        if ($('#pago_excedente_abono').is(':checked') && excedente > 0) {
            $('#fila_confirm_abono').show();
            $('#confirm_monto_abono').text('$ ' + excedente.toFixed(2));
        } else {
            $('#fila_confirm_abono').hide();
        }

        // Crédito
        let montoCred = parseFloat($('#monto_credito_usd').val()) || 0;
        if (montoCred > 0) {
            $('#fila_confirm_credito').show();
            $('#confirm_monto_credito').text('$ ' + montoCred.toFixed(2));
        } else {
            $('#fila_confirm_credito').hide();
        }

        $('#modalConfirmarVenta').modal('show');
    });

    $(document).on('click', '#btnProcesarVentaFinal', function() {
        let correlativo = $('#correlativo_nota').val() || '';
        if ($('#venta-form input[name="correlativo_nota"]').length === 0) {
            $('#venta-form').append(`<input type="hidden" name="correlativo_nota" value="${correlativo}">`);
        } else {
            $('#venta-form input[name="correlativo_nota"]').val(correlativo);
        }
        
        let tipoDoc = $('input[name="tipo_documento"]:checked').val() || 'nota_entrega';
        if ($('#tipo_documento_real').length === 0) {
            $('#venta-form').append(`<input type="hidden" name="tipo_documento" id="tipo_documento_real" value="${tipoDoc}">`);
        } else {
            $('#tipo_documento_real').val(tipoDoc);
        }

        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Procesando...');
        $('#btn-finalizar').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Procesando...');
        document.getElementById('venta-form').submit();
    });

    // ==========================================================
    // 13. CLIENTE RÁPIDO (Modal)
    // ==========================================================
    $('#formClienteRapido').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnGuardarCliente');
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: "{{ route('clientes.store_ajax') }}",
            method: "POST",
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    let newOption = new Option(
                        `${response.cliente.nombre} (${response.cliente.identificacion})`, 
                        response.cliente.id, true, true
                    );
                    $(newOption).attr('data-limite', response.cliente.limite_credito).attr('data-deuda', 0);
                    $('#id_cliente').append(newOption).trigger('change');
                    $('#modalClienteRapido').modal('hide');
                    $('#formClienteRapido')[0].reset();
                    Swal.fire('¡Éxito!', 'Cliente registrado y seleccionado.', 'success');
                }
            },
            error: function(xhr) {
                let msgs = [];
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.values(xhr.responseJSON.errors).forEach(arr => msgs.push(...arr));
                    Swal.fire('Errores de validación', msgs.join('<br>'), 'error');
                } else {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Error al registrar cliente.', 'error');
                }
            },
            complete: () => btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar Cliente')
        });
    });

    // ==========================================================
    // 14. VALIDACIÓN IDENTIFICACIÓN (Modal Cliente)
    // ==========================================================
    (function() {
        const $input = $('input[name="identificacion"]');
        let inicial = ($input.val() || '').replace(/\D/g, '').substring(0, 9);
        if (inicial.length > 0) $input.val('V-' + inicial);

        $input.on('input', function() {
            let numeros = $(this).val().replace(/\D/g, '').substring(0, 9);
            $(this).val(numeros.length > 0 ? 'V-' + numeros : 'V-');
        }).on('keydown click focus select', function() {
            if (this.selectionStart < 2) this.setSelectionRange(2, 2);
        }).on('keydown', function(e) {
            if ((e.key === 'Backspace' || e.key === 'Delete') && this.selectionStart <= 2 && this.selectionEnd <= 2) {
                e.preventDefault();
            }
        });
    })();

    // ==========================================================
    // 15. IMPRESIÓN POST-VENTA
    // ==========================================================
    @if(session('imprimir_documento'))
    $(function() {
        const docData = @json(session('imprimir_documento'));
        $('#modal_titulo_doc').text(docData.tipo + ' Generada:');
        $('#modal_codigo_doc').text(docData.codigo);
        const urlShow = "{{ url('ventas') }}/" + docData.venta_id;
        $('#btn_confirmar_impresion').attr('href', urlShow).attr('target', '_blank');
        $('#modalImprimirDocumento').modal('show');
        $('#btn_confirmar_impresion').on('click', function() {
            setTimeout(() => $('#modalImprimirDocumento').modal('hide'), 1000);
        });
    });
    @endif
});
</script>
@endsection