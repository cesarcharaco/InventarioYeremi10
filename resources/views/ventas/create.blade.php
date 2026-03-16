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
                                <!-- <div id="info_cliente" style="display: none;">
                                    <span class="badge badge-light border text-muted px-2 py-1" style="font-size: 0.9rem;">
                                        Límite: $<span id="cliente_limite">0</span>
                                    </span>
                                    
                                    <span id="cliente_deuda_container" style="display: none;">
                                        <span class="badge badge-danger px-2 py-1 shadow-sm" style="font-size: 0.9rem;">
                                            Deuda: $<span id="cliente_deuda">0</span>
                                        </span>
                                    </span>
                                </div> -->
                                <div class="input-group">
                                    <select name="id_cliente" id="id_cliente" class="form-control select2" required>
                                        <option value="">Seleccione cliente...</option>
                                        @foreach($clientes as $cliente)
                                            {{-- PUNTO 1: Data-deuda agregado --}}
                                            <option value="{{ $cliente->id }}" 
                                                data-limite="{{ $cliente->limite_credito }}"
                                                data-deuda="{{ $cliente->saldo_pendiente ?? 0 }}">
                                                {{ $cliente->nombre }} ({{ $cliente->identificacion }})
                                            </option>
                                        @endforeach
                                    </select>
                                    
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="button" data-toggle="modal" data-target="#modalClienteRapido"><i class="fa fa-plus"></i></button>
                                    </div>
                                </div>
                                {{-- Info de deuda para el cajero --}}
                                <small id="info_deuda_cliente" class="form-text text-danger font-weight-bold"></small>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="font-weight-bold text-primary"><i class="fa fa-search"></i> Buscador de Insumos</label>
                                <select id="buscador_insumos" class="form-control select2-custom">
                                    <option value="">Buscar por producto, descripción o serial...</option>
                                    @foreach($productos as $p)
                                    @php 
                                        // Buscamos la existencia específica para el local actual del usuario
                                        $existenciaLocal = $p->existencias->where('id_local', $local->id)->first();
                                        // Mantenemos el nombre stockLocal para que coincida con tu lógica previa si es necesario
                                        $stockLocal = $existenciaLocal ? $existenciaLocal->cantidad : 0; 
                                    @endphp
                                    <option value="{{ $p->id }}" 
                                            data-descripcion="{{ $p->descripcion }}"
                                            data-bcv="{{ $p->precio_venta_usd }}"
                                            data-bs="{{ $p->precio_venta_bs }}"
                                            data-stock="{{ $stockLocal }}"
                                            data-serial="{{ $p->serial }}">
                                        {{ $p->producto }} (Stock: {{ $stockLocal }})
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
                        {{-- Sección de Descuento Refactorizada --}}
                        <div class="form-group mt-2 mb-3 p-3 border rounded shadow-sm" id="contenedor_descuento" 
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
                        <!-- <div id="seccion_abono_deuda" style="display: none;" class="alert alert-info border-info py-2">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="aplicar_abono" name="aplicar_abono">
                                <label class="custom-control-label small font-weight-bold" for="aplicar_abono">
                                    ¿Abonar <span id="monto_abono_texto">$0.00</span> a la deuda?
                                </label>
                            </div>
                        </div> -->
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

                        <button type="submit" class="btn btn-success btn-block btn-lg mt-3" id="btn-finalizar" disabled>
                            <i class="fa fa-check-circle"></i> FINALIZAR VENTA
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @endif
</main>

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
                            <input type="radio" name="tipo_documento" id="tipo_nota_si" value="nota_entrega" >
                            <i class="fa fa-check"></i> Sí, Nota de Entrega
                        </label>
                        <label class="btn btn-outline-secondary">
                            <input type="radio" name="tipo_documento" id="tipo_nota_no" value="sin_documento" checked>
                            <i class="fa fa-times"></i> No, sin documento
                        </label>
                        <label class="btn btn-outline-primary" style="display: none;" id="btn_tipo_factura">
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
                        <label>Teléfono (Opcional)</label>
                        <input type="text" name="telefono" class="form-control" placeholder="0412-1234567">
                    </div>
                    <div class="form-group">
                        <label>Límite de Crédito ($)</label>
                        <input type="number" step="0.01" name="limite_credito" class="form-control" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarCliente">
                        <i class="fa fa-save"></i> Guardar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    const TASA = parseFloat($('#tasa_referencial').val()) || 1;

    let detalleVentas = [];

    // VARIABLE GLOBAL PARA PRESERVAR SUBTOTAL
        window.subtotalSinDescuento = 0;
        window.subtotalSinDescuentoBs = 0;
    // --- CONFIGURACIÓN SELECT2 DE LUJO CORREGIDA ---
    function formatRepo(repo) {
        // VALIDACIÓN: Si es el placeholder o está cargando, retornar el texto simple
        if (repo.loading || !repo.id) {
            return repo.text;
        }
        
        let data = repo.element.dataset;
        
        // Formateo de precios para visualización limpia
        let precioBCV = parseFloat(data.bcv).toFixed(2);
        let precioBS = parseFloat(data.bs).toFixed(2);
        let stock = parseInt(data.stock) || 0;

        var $container = $(
            `<div class='select2-result-repository clearfix' style='${stock <= 0 ? "opacity: 0.6;" : ""}'>
                <div class='select2-result-repository__meta'>
                    <div class='d-flex justify-content-between'>
                        <span class='select2-result-repository__title' style='font-weight: bold; color: #333; font-size: 15px;'></span>
                        <small class='text-muted'>${data.serial || ''}</small>
                    </div>
                    <div class='select2-result-repository__description' style='font-size: 11px; color: #777; margin-bottom: 5px; line-height: 1.2;'></div>
                    <div class='d-flex justify-content-start flex-wrap' style='gap: 5px;'>
                        <span class='badge' style='background-color: #28a745; color: white; padding: 5px 8px;'>Venta BCV: $${precioBCV}</span>
                        <span class='badge' style='background-color: #007bff; color: white; padding: 5px 8px;'>Venta BS: ${precioBS} Bs</span>
                       
                        <span class='badge ${stock > 0 ? 'badge-dark' : 'badge-danger'}' style='padding: 5px 8px;'>
                            📦 Stock: ${stock}
                        </span>
                    </div>
                </div>
            </div>`
        );

        $container.find(".select2-result-repository__title").text(repo.text);
        $container.find(".select2-result-repository__description").text(data.descripcion || 'Sin descripción adicional');
        
        return $container;
    }

    function formatRepoSelection(repo) {
        return repo.text;
    }

    // Inicialización del Select2
    $('.select2-custom').select2({
        theme: 'bootstrap4',
        templateResult: formatRepo, 
        templateSelection: formatRepoSelection,
        width: '100%',
        escapeMarkup: function(m) { return m; } 
    });
    // Evento al cambiar el porcentaje de descuento
    $('#porcentaje_descuento').on('change', function() {
        if (window.subtotalSinDescuento > 0) {
            // Recalcular totales con el nuevo porcentaje
            actualizarTotales(window.subtotalSinDescuento);
        }
    });
    // --- LÓGICA DE TABLA ---
    $('#buscador_insumos').on('select2:select', function (e) {
        let data = e.params.data.element.dataset;
        let id = $(this).val();
        let nombre = e.params.data.text.trim();
        
        // Aseguramos valores numéricos válidos
        let precio_bcv = parseFloat(data.bcv) || 0;
        let precio_bs = parseFloat(data.bs) || 0;
        let stock = parseInt(data.stock) || 0;

        if (stock <= 0) {
            Swal.fire('Sin Stock', 'No hay existencias de este producto', 'error');
            $(this).val(null).trigger('change');
            return;
        }

        let existe = detalleVentas.find(item => item.id === id);
        if (existe) {
            if (existe.cantidad + 1 > stock) {
                Swal.fire('Límite de Stock', 'No puedes agregar más de lo disponible', 'warning');
                $(this).val(null).trigger('change');
                return;
            }
            existe.cantidad++;
        } else {
            detalleVentas.push({ id, nombre, precio_bcv, precio_bs, cantidad: 1, stock });
        }

        $(this).val(null).trigger('change');
        renderTabla();
    });

    function renderTabla() {
        let html = '';
        let totalUSD = 0;
        let totalBS = 0;

        detalleVentas.forEach((item, index) => {
            let subtotal = item.cantidad * item.precio_bcv;
            totalUSD += subtotal;
            

            // Agregamos data-label para que el CSS Mobile-First funcione
            html += `<tr>
                <td data-label="Producto"><strong>${item.nombre}</strong></td>
                <td data-label="Cant.">
                    <input type="number" class="form-control change-cant" 
                        data-index="${index}" value="${item.cantidad}" min="1" max="${item.stock}">
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
            </tr>`;
        });

        if (detalleVentas.length === 0) {
            html = '<tr><td colspan="5" class="text-center text-muted">El carrito está vacío</td></tr>';
        }
        
        $('#tabla-ventas tbody').html(html);
        actualizarTotales(totalUSD);
    }

    function actualizarTotales(subtotalUSD) {
        // GUARDAR SUBTOTALES ORIGINALES
        window.subtotalSinDescuento = subtotalUSD;
        let subtotalBS = subtotalUSD * TASA;
        window.subtotalSinDescuentoBs = subtotalBS;

        // Determinar condiciones de descuento
        const ofertaGlobalActiva = {{ $ofertasActivas ? 'true' : 'false' }};
        const porcentajeDescuento = parseFloat($('#porcentaje_descuento').val()) || 0;
        
        let totalUSD = subtotalUSD;
        let totalBS = subtotalBS;
        let montoDescuentoUSD = 0;
        let montoDescuentoBS = 0;
        
        // Verificar si hay pago en divisas
        const hayPagoDivisas = (parseFloat($('input[name="pago_usd_efectivo"]').val()) || 0) > 0 
                            || (parseFloat($('input[name="pago_zelle_usd"]').val()) || 0) > 0;
        
        // APLICAR DESCUENTO SI CORRESPONDE
        if (porcentajeDescuento > 0 && (ofertaGlobalActiva || hayPagoDivisas)) {
            montoDescuentoUSD = subtotalUSD * (porcentajeDescuento / 100);
            montoDescuentoBS = subtotalBS * (porcentajeDescuento / 100);
            
            totalUSD = subtotalUSD - montoDescuentoUSD;
            totalBS = subtotalBS - montoDescuentoBS;
        }
        
        // VISUALIZACIÓN DEL DESCUENTO
        if (montoDescuentoUSD > 0) {
            $('#antes_usd').text('$ ' + subtotalUSD.toFixed(2));
            $('#ahorro_usd').text('$ ' + montoDescuentoUSD.toFixed(2));
            $('#contenedor_referencia_original').slideDown(200);
            $('#total_final_usd').addClass('text-success');
        } else {
            $('#contenedor_referencia_original').slideUp(200);
            $('#total_final_usd').removeClass('text-success');
        }
        
        // DISPLAY PRINCIPAL (siempre muestra el total con descuento aplicado)
        $('#total_final_usd').text(`$ ${totalUSD.toFixed(2)}`);
        $('#total_final_bs').text(`${totalBS.toFixed(2)} Bs`);
        
        // INPUTS HIDDEN PARA BACKEND
        if ($('#total_hidden').length === 0) {
            $('#venta-form').append(`<input type="hidden" name="total_usd" id="total_hidden">`);
        }
        $('#total_hidden').val(totalUSD.toFixed(2));

        if ($('#total_bs_hidden').length === 0) {
            $('#venta-form').append(`<input type="hidden" name="total_bs" id="total_bs_hidden">`);
        }
        $('#total_bs_hidden').val(totalBS.toFixed(2));
        
        // NUEVOS: Inputs para descuento
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

        // Disparar validación de pagos con el nuevo total
        actualizarCalculoPagos();
    }

    function actualizarCalculoPagos() {
        let totalFacturaUSD = parseFloat($('#total_hidden').val()) || 0;
        const TASA = parseFloat($('#tasa_referencial').val()) || 1;
        
        // Total en Bs (fuente de verdad para cálculos Bs)
        let totalFacturaBs = totalFacturaUSD * TASA;

        // Captura de inputs
        let pUSD = parseFloat($('input[name="pago_usd_efectivo"]').val()) || 0;
        let pZelle = parseFloat($('input[name="pago_zelle_usd"]').val()) || 0;
        let pBsEfec = parseFloat($('input[name="pago_bs_efectivo"]').val()) || 0;
        let pBsPunto = parseFloat($('input[name="pago_punto_bs"]').val()) || 0;
        let pBsPM = parseFloat($('input[name="pago_pagomovil_bs"]').val()) || 0;
        
        let totalBsPagado = pBsEfec + pBsPunto + pBsPM;
        let totalUSDPagado = pUSD + pZelle;

        // DETECTAR INPUT ACTIVO AUTOMÁTICAMENTE
        let inputActivo = document.activeElement;
        let esInputBs = inputActivo && $(inputActivo).hasClass('monto-pago') && (
            $(inputActivo).attr('name') === 'pago_bs_efectivo' ||
            $(inputActivo).attr('name') === 'pago_punto_bs' ||
            $(inputActivo).attr('name') === 'pago_pagomovil_bs'
        );

        // Variables de control
        let checkboxAbono = $('#pago_excedente_abono');
        let deudaCliente = parseFloat($('#id_cliente option:selected').data('deuda')) || 0;
        let esCredito = $('#switchCredito').is(':checked');
        
        // Reset visual
        $('#display_restante_usd').removeClass('text-warning text-success text-danger');
        $('#display_restante_bs').removeClass('text-warning text-success text-danger');
        $('#contenedor_excedente').hide();
        $('#seccion_abono_excedente').hide();
        $('#alerta-exceso').remove();
        $('.info-deuda-pago').hide();

        // Determinar fuente de verdad según input activo
        let restanteUSD, restanteBs;
        
        if (esInputBs) {
            // 🎯 FUENTE DE VERDAD: BOLÍVARES
            let totalBsEquivalentePagado = totalBsPagado + (totalUSDPagado * TASA);
            restanteBs = totalFacturaBs - totalBsEquivalentePagado;

            if (restanteBs < -0.01) {
                restanteUSD = restanteBs / TASA;
            } else if (restanteBs > 0.01) {
                restanteUSD = restanteBs / TASA;
            } else {
                restanteUSD = 0;
                restanteBs = 0;
            }
            
        } else {
            // 🎯 FUENTE DE VERDAD: DÓLARES (default)
            restanteUSD = totalFacturaUSD - totalUSDPagado - (totalBsPagado / TASA);
            
            if (restanteUSD > 0.01) {
                restanteBs = restanteUSD * TASA;
            } else if (restanteUSD < -0.01) {
                restanteBs = restanteUSD * TASA;
            } else {
                restanteUSD = 0;
                restanteBs = 0;
            }
        }

        // Redondeo SOLO para decisión lógica, NO para display de Bs
        let diffUSD = Math.round(restanteUSD * 100) / 100;
        let diffBs = Math.round(restanteBs * 100) / 100;

        // Evaluación de casos
        if (diffUSD > 0.01 || diffBs > 0.01) {
            // ❌ CASO: FALTANTE
            
            $('#seccion_abono_excedente').hide();
            
            // Display USD
            $('#display_restante_usd')
                .text(`$ ${Math.abs(restanteUSD).toFixed(2)}`)
                .addClass('text-danger');
            
            // Display Bs - PRESERVAMOS CÁLCULO EXACTO
            let displayBs = Math.abs(restanteBs);
            $('#display_restante_bs')
                .text(`${displayBs.toFixed(2)} Bs`)
                .addClass('text-danger');
            
            if (esCredito) {
                $('#monto_credito_usd').val(restanteUSD.toFixed(2));
                $('#label_monto_credito').text(restanteUSD.toFixed(2));
                validarLimiteCredito(restanteUSD);
            } else {
                $('#btn-finalizar')
                    .prop('disabled', true)
                    .html('<i class="fa fa-times-circle"></i> FALTANTE');
            }

        } else if (diffUSD < -0.01 || diffBs < -0.01) {
            // ⚠️ CASO: EXCESO
            
            let excesoUSD = Math.abs(restanteUSD);
            
            $('#display_restante_usd').text("$ 0.00").removeClass('text-danger');
            $('#display_restante_bs').text("0.00 Bs").removeClass('text-danger');
            $('#contenedor_excedente').show();
            $('#display_excedente_usd').text(`$ ${excesoUSD.toFixed(2)}`);

            if (deudaCliente > 0) {
                // NUEVO: Validar si excedente supera la deuda
        if (excesoUSD > deudaCliente) {
            // Excedente mayor que deuda - BLOQUEAR
            $('#seccion_abono_excedente').hide();
            $('#btn-finalizar')
                .prop('disabled', true)
                .html('<i class="fa fa-ban"></i> EXCEDENTE SOBRE DEUDA');
            
            // Opcional: Mostrar alerta visual
            Swal.fire({
                icon: 'warning',
                title: 'Excedente inválido',
                text: `El excedente ($${excesoUSD.toFixed(2)}) supera la deuda ($${deudaCliente.toFixed(2)})`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        } else {
            // Excedente menor o igual a deuda - PERMITIR ABONO
            $('#seccion_abono_excedente').fadeIn();
            $('#monto_a_abonar').text(excesoUSD.toFixed(2));
            
            if (checkboxAbono.is(':checked')) {
                $('#btn-finalizar')
                    .prop('disabled', false)
                    .html('<i class="fa fa-check-circle"></i> FINALIZAR (+ ABONO)');
            } else {
                $('#btn-finalizar')
                    .prop('disabled', true)
                    .html('<i class="fa fa-hand-paper"></i> ¿ES ABONO?');
            }
        }
            } else {
                // sin deuda no hay abono
                $('#btn-finalizar')
                    .prop('disabled', true)
                    .html('<i class="fa fa-exclamation-triangle"></i> EXCESO');
            }

        } else {
            // ✅ CASO: PAGO EXACTO
            
            $('#display_restante_usd').text('$ 0.00').removeClass('text-danger');
            $('#display_restante_bs').text('0.00 Bs').removeClass('text-danger');
            $('#btn-finalizar')
                .prop('disabled', false)
                .html('<i class="fa fa-check-circle"></i> FINALIZAR VENTA');
        }

        // Aviso de deuda persistente
        if (deudaCliente > 0) {
            $('#aviso_deuda_cliente')
                .html(`<i class="fa fa-info-circle"></i> Deuda pendiente: $${deudaCliente.toFixed(2)}`)
                .show();
        } else {
            $('#aviso_deuda_cliente').hide();
        }
    }

    function validarLimiteCredito(monto) {
        let clienteSeleccionado = $('#id_cliente option:selected');
        let limite = parseFloat(clienteSeleccionado.data('limite')) || 0;
        let pinAutorizado = $('#pin_autorizacion').val(); // Campo donde guardaremos el éxito del PIN

        // Si el monto es mayor al límite Y no hay un PIN de autorización previo
        if (monto > limite && !pinAutorizado) {
            $('#error_limite')
                .html(`<i class="fa fa-exclamation-circle"></i> Límite excedido (Máx: $${limite.toFixed(2)})`)
                .show();
            
            $('#btn-finalizar')
                .prop('disabled', true)
                .html('<i class="fa fa-lock"></i> REQUIERE AUTORIZACIÓN');
        } else {
            // Si está dentro del límite o ya fue autorizado con PIN
            $('#error_limite').hide();
            
            // Solo habilitamos si el monto es mayor a 0 (no tiene sentido un crédito de $0)
            if (monto > 0) {
                $('#btn-finalizar')
                    .prop('disabled', false)
                    .html('<i class="fa fa-check-circle"></i> FINALIZAR CRÉDITO');
            }
        }
    }

    //HANDLER DE EVENTOS
    // Actualizar al escribir montos
    $(document).on('input', '.monto-pago', actualizarCalculoPagos);

    // Eliminar producto
    $(document).on('click', '.remove-item', function() {
        detalleVentas.splice($(this).data('index'), 1);
        renderTabla();
        
    });

    // Cambio de cantidad
    $(document).on('change', '.change-cant', function() {
        let index = $(this).data('index');
        let val = parseInt($(this).val());
        let stock = detalleVentas[index].stock;

        if (val > stock) {
            Swal.fire('Stock insuficiente', `Solo hay ${stock} disponibles`, 'warning');
            val = stock;
        }
        detalleVentas[index].cantidad = val || 1;
        renderTabla();
    });

    $('#venta-form').on('submit', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        // 1. Validación de Carrito
        if (detalleVentas.length === 0) {
            Swal.fire('Carrito Vacío', 'Debes agregar al menos un producto.', 'error');
            return false;
        }
        
        // 2. Recopilación de Montos
        let totalFacturaUSD = parseFloat($('#total_hidden').val()) || 0;
        let totalFacturaBS = parseFloat($('#total_bs_hidden').val()) || 0;
        
        // Captura de inputs del formulario
        let pUSD = parseFloat($('input[name="pago_usd_efectivo"]').val()) || 0;
        let pZelle = parseFloat($('input[name="pago_zelle_usd"]').val()) || 0;
        let pBS_Efectivo = parseFloat($('input[name="pago_bs_efectivo"]').val()) || 0;
        let pBS_Punto = parseFloat($('input[name="pago_punto_bs"]').val()) || 0;
        let pBS_PMovil = parseFloat($('input[name="pago_pagomovil_bs"]').val()) || 0;
        
        let pBS_Total = pBS_Efectivo + pBS_Punto + pBS_PMovil;
        let pagadoTotalUSD = pUSD + pZelle + (pBS_Total / TASA);
        let diferencia = totalFacturaUSD - pagadoTotalUSD;

        // 3. Validaciones de negocio (Exceso y Crédito)
        if (diferencia < -0.05 && !$('#pago_excedente_abono').is(':checked')) {
            Swal.fire('Pago Excedido', 'El monto supera la factura. ¿Es un abono? Márcalo o ajusta.', 'warning');
            return false;
        }

        if ($('#switchCredito').is(':checked')) {
            let limite = parseFloat($('#id_cliente option:selected').data('limite')) || 0;
            let pinAutorizado = $('#pin_autorizacion').val();
            if (diferencia > (limite + 0.01) && !pinAutorizado) {
                Swal.fire('Crédito Bloqueado', 'Excede el límite del cliente.', 'error');
                return false;
            }
        }

        // 4. --- BLOQUE DE VALIDACIÓN DE REFERENCIAS (Integrado) ---
        let refZelle = $('#referencia_zelle').val();
        let refPM = $('#referencia_pagomovil').val();
        let refPunto = $('#referencia_punto').val();

        if (pZelle > 0 && (!refZelle || refZelle.trim() === "")) {
            Swal.fire('Referencia Faltante', 'Por favor, ingrese la referencia de Zelle haciendo clic en el botón de la llave.', 'warning');
            return false;
        }

        if (pBS_PMovil > 0 && (!refPM || refPM.trim() === "")) {
            Swal.fire('Referencia Faltante', 'Por favor, ingrese la referencia de Pago Móvil haciendo clic en el botón del banco.', 'warning');
            return false;
        }
        // --------------------------------------------

        // --- LLENAR MODAL DE CONFIRMACIÓN ---
        
        // Encabezado del Modal
        $('#confirm_total_usd').text(`$ ${totalFacturaUSD.toFixed(2)}`);
        $('#confirm_total_bs').text(`${totalFacturaBS.toLocaleString('es-VE', {minimumFractionDigits: 2})} Bs`);
        
        // Llenado de la Tabla (Fila por Fila)
        $('#confirm_p_usd').text(`$ ${pUSD.toFixed(2)}`);
        $('#confirm_p_bs_efec').text(`${pBS_Efectivo.toFixed(2)} Bs`);
        
        // Zelle con referencia (Integrado)
        let txtZelle = `$ ${pZelle.toFixed(2)}`;
        if (pZelle > 0 && refZelle) txtZelle += ` (Ref: ${refZelle})`;
        $('#confirm_p_zelle').text(txtZelle);
        
        // Punto con referencia (Añadido por consistencia)
        let txtPunto = `${pBS_Punto.toFixed(2)} Bs`;
        if (pBS_Punto > 0 && refPunto) txtPunto += ` (Ref: ${refPunto})`;
        $('#confirm_p_punto').text(txtPunto);
        
        // Pago Móvil con referencia (Integrado)
        let txtPM = `${pBS_PMovil.toFixed(2)} Bs`;
        if (pBS_PMovil > 0 && refPM) txtPM += ` (Ref: ${refPM})`;
        $('#confirm_p_pm').text(txtPM);

        // Lógica de Abono (Fila Azul)
        if ($('#pago_excedente_abono').is(':checked') && diferencia < -0.01) {
            $('#fila_confirm_abono').show();
            $('#confirm_monto_abono').text(`$ ${Math.abs(diferencia).toFixed(2)}`);
        } else {
            $('#fila_confirm_abono').hide();
        }

        // Lógica de Crédito (Fila Roja)
        if ($('#switchCredito').is(':checked') && diferencia > 0.01) {
            $('#fila_confirm_credito').show();
            $('#confirm_monto_credito').text(`$ ${diferencia.toFixed(2)}`);
        } else {
            $('#fila_confirm_credito').hide();
        }
        
        // --- SECCIÓN DE DESCUENTO ---
        let descuentoUSD = parseFloat($('#descuento_usd_hidden').val()) || 0;
        let porcentajeDescuento = parseFloat($('#porcentaje_descuento_hidden').val()) || 0;
        let subtotalOriginal = window.subtotalSinDescuento || totalFacturaUSD;

        if (descuentoUSD > 0) {
            $('#confirm_seccion_descuento').show();
            $('#confirm_antes_usd').text('$ ' + subtotalOriginal.toFixed(2));
            $('#confirm_descuento_usd').text('$ ' + descuentoUSD.toFixed(2));
            $('#confirm_porcentaje').text(porcentajeDescuento);
        } else {
            $('#confirm_seccion_descuento').hide();
        }
        
        // --- SECCIÓN DE IVA (NUEVO) ---
        // Calcular base imponible e IVA (IVA incluido en el precio)
        let baseImponibleBS = totalFacturaBS / 1.16;
        let ivaBS = baseImponibleBS * 0.16;
        
        // Llenar modal
        $('#confirm_base_imponible_bs').text(baseImponibleBS.toFixed(2));
        $('#confirm_iva_bs').text(ivaBS.toFixed(2));
        
        // Crear inputs hidden para backend si no existen
        if ($('#base_imponible_bs_hidden').length === 0) {
            $('#venta-form').append('<input type="hidden" name="base_imponible_bs" id="base_imponible_bs_hidden">');
        }
        $('#base_imponible_bs_hidden').val(baseImponibleBS.toFixed(2));
        
        if ($('#iva_bs_hidden').length === 0) {
            $('#venta-form').append('<input type="hidden" name="iva_bs" id="iva_bs_hidden">');
        }
        $('#iva_bs_hidden').val(ivaBS.toFixed(2));
        
        // Reset tipo documento (siempre nota de entrega por defecto)
        $('#tipo_nota_entrega').prop('checked', true);
        $('#tipo_documento_hidden').val('nota_entrega');
        if ($('#tipo_documento').length === 0) {
            $('#venta-form').append('<input type="hidden" name="tipo_documento" id="tipo_documento">');
        }
        $('#tipo_documento').val($('input[name="tipo_documento"]:checked').val());

        // Monto abono (si aplica)
        if ($('#pago_excedente_abono').is(':checked')) {
            let montoAbono = parseFloat($('#confirm_monto_abono').text().replace('$ ', '')) || 0;
            
            if ($('#monto_excedente').length === 0) {
                $('#venta-form').append('<input type="hidden" name="monto_excedente" id="monto_excedente">');
            }
            $('#monto_excedente').val(montoAbono.toFixed(2));
            
            // Para info_adicional
            if ($('#aplica_abono').length === 0) {
                $('#venta-form').append('<input type="hidden" name="aplica_abono" id="aplica_abono" value="1">');
            }
        } else {
            // Remover si existe para no enviar basura
            $('#monto_excedente').remove();
            $('#aplica_abono').remove();
        }
        // Mostrar el Modal
        $('#modalConfirmarVenta').modal('show');
    });

    // --- NUEVO: FUNCIÓN PARA SOLICITAR REFERENCIAS ---
    function solicitarReferencia(metodo) {
    const mapeo = {
        'pago_pagomovil_bs': { 
            t: 'Referencia Pago Móvil', 
            id: 'referencia_pagomovil',
            btnClass: 'btn-info',
            icon: 'fa-university'
        },
        'pago_zelle_usd': { 
            t: 'Confirmación Zelle', 
            id: 'referencia_zelle',
            btnClass: 'btn-warning',
            icon: 'fa-key'
        }
    };

    if (!mapeo[metodo]) return;

    // Crear input hidden si no existe (CRÍTICO para el backend)
    if ($(`#${mapeo[metodo].id}`).length === 0) {
        $('#venta-form').append(`<input type="hidden" name="${mapeo[metodo].id}" id="${mapeo[metodo].id}">`);
    }

    let valorActual = $(`#${mapeo[metodo].id}`).val();

    Swal.fire({
        title: `<span class="swal-title-mobile">${mapeo[metodo].t}</span>`,
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
        inputAttributes: {
            autocapitalize: 'off',
            autocorrect: 'off',
            autocomplete: 'off'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Guardar valor
            $(`#${mapeo[metodo].id}`).val(result.value);
            
            // Feedback visual en input y botón
            let $input = $(`input[name="${metodo}"]`);
            let $boton = $input.closest('.input-group').find('button');
            
            if (result.value.trim() !== "") {
                $input.addClass('is-valid');
                $boton.removeClass('btn-warning btn-primary btn-info').addClass('btn-success');
                $boton.html('<i class="fa fa-check"></i>');
                
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Referencia guardada',
                    showConfirmButton: false,
                    timer: 1500
                });
            } else {
                // Valor vacío: resetear
                $input.removeClass('is-valid');
                resetBotonEstado(metodo, $boton, mapeo[metodo]);
            }
        }
        // Si cancela, no hacemos nada (mantiene valor anterior)
    });
}
window.solicitarReferencia = solicitarReferencia;
function resetBotonEstado(metodo, $boton, config) {
    $boton.removeClass('btn-success').addClass(config.btnClass);
    $boton.html(`<i class="fa ${config.icon}"></i>`);
}

 // EVENTO PARA EL BOTÓN FINAL DENTRO DEL MODAL
    $(document).on('click', '#btnProcesarVentaFinal', function() {
            // 1. Deshabilitar el botón y mostrar spinner (como hacías en tu función original)
            $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Procesando...');
            $('#btn-finalizar').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Procesando...');

            // 2. Enviar el formulario directamente al servidor
            document.getElementById('venta-form').submit();
    });

    $('#formClienteRapido').on('submit', function(e) {
        e.preventDefault(); 
        
        let btn = $('#btnGuardarCliente');
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: "{{ route('clientes.store_ajax') }}", // Asegúrate que este nombre de ruta coincida con web.php
            method: "POST",
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // 1. Crear la nueva opción en el Select2
                    let newOption = new Option(
                        `${response.cliente.nombre} (${response.cliente.identificacion})`, 
                        response.cliente.id, 
                        true, 
                        true
                    );
                    
                    // 2. Añadir el data-limite para que la lógica de crédito funcione
                    $(newOption).attr('data-limite', response.cliente.limite_credito);
                    $(newOption).attr('data-deuda', 0);
                    $('#id_cliente').append(newOption).trigger('change');

                    // 3. Cerrar modal y limpiar
                    $('#modalClienteRapido').modal('hide');

                    $('#formClienteRapido')[0].reset();
                    
                    Swal.fire('¡Éxito!', 'Cliente registrado y seleccionado.', 'success');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON) {
                    // Errores de validación
                    let errors = xhr.responseJSON.errors || {};
                    let mensajes = [];

                    Object.keys(errors).forEach(function (campo) {
                        errors[campo].forEach(function (msg) {
                            mensajes.push(msg);
                        });
                    });

                    Swal.fire('Errores de validación', mensajes.join('<br>'), 'error');
                } else {
                    // Otros errores (500, etc.)
                    let errorMsg = 'Error al registrar cliente.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    Swal.fire('Error', errorMsg, 'error');
                }
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar Cliente');
            }
        });
    });

    $('#switchCredito').on('change', function() {
        let checkbox = $(this);
        let btnFinalizar = $('#btn-finalizar'); // ID unificado
        
        if (checkbox.is(':checked')) {
            @cannot('gestionar-creditos-avanzado')
                checkbox.prop('checked', false);
                
                Swal.fire({
                    title: '¿Solicitar Autorización?',
                    text: "Se enviará un PIN de 6 dígitos al WhatsApp del jefe para habilitar este crédito.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, enviar PIN',
                    cancelButtonText: 'Cancelar',
                    allowOutsideClick: false 
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post("{{ route('ventas.solicitar_pin') }}", {
                            _token: "{{ csrf_token() }}",
                            local_nombre: "{{ $local->nombre }}",
                            cliente_nombre: $('#id_cliente option:selected').text().trim(),
                            monto_total: $('#total_hidden').val(),
                            cantidad_items: detalleVentas.length
                        }, function(response) {
                            if(response.wa_link) { window.open(response.wa_link, '_blank'); }

                            Swal.fire({
                                title: 'Introduce el PIN',
                                text: 'El jefe recibió un código de 6 dígitos',
                                input: 'text',
                                inputAttributes: { maxlength: 6, autocapitalize: 'off' },
                                showCancelButton: true,
                                confirmButtonText: 'Validar PIN',
                                cancelButtonText: 'Cancelar',
                                showLoaderOnConfirm: true,
                                allowOutsideClick: false,
                                preConfirm: (pin) => {
                                    return $.post("{{ route('ventas.verificar_pin') }}", {
                                        _token: "{{ csrf_token() }}",
                                        pin: pin
                                    }).done(res => {
                                        // CAMBIO 1: Guardamos el PIN validado para que 
                                        // validarLimiteCredito() sepa que ya está autorizado.
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
                                    // Caso: Canceló al meter el PIN
                                    $('#seccion_credito').hide();
                                    checkbox.prop('checked', false);
                                    $('#pin_autorizacion').val(''); // Limpiamos por seguridad
                                    actualizarCalculoPagos();
                                }
                            });
                        });
                    } else {
                        // Caso: Canceló el envío del PIN
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
            // CAMBIO 2: Limpiar el PIN si el usuario desmarca el crédito
            $('#pin_autorizacion').val(''); 
            $('#seccion_credito').fadeOut();
            actualizarCalculoPagos();
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
                $contenedor.css({
                    'background-color': '#fff9db',
                    'border-color': '#ffe066'
                });
                $info.html('<i class="fa fa-unlock text-warning"></i> Descuento habilitado por pago en divisas.');
            } else {
                // Si se quitan los dólares, resetear descuento
                $select.prop('disabled', true).val(0);
                $contenedor.css({
                    'background-color': '#f8f9fa',
                    'border-color': '#dee2e6'
                });
                $info.text('* Se habilitará automáticamente al ingresar Dólares o Zelle.');
                
                // RECALCULAR SIN DESCUENTO si había uno aplicado
                if (teniaDescuento && window.subtotalSinDescuento > 0) {
                    actualizarTotales(window.subtotalSinDescuento);
                }
            }
        }

        function visualizarComparativaDescuento(subtotalSinDescuento, totalConDescuento) {
            const ahorro = subtotalSinDescuento - totalConDescuento;
            
            if (ahorro > 0) {
                // Mostramos la referencia de lo que costaba antes
                $('#antes_usd').text('$ ' + subtotalSinDescuento.toFixed(2));
                $('#ahorro_usd').text('$ ' + ahorro.toFixed(2));
                
                $('#contenedor_referencia_original').slideDown(200);
                
                // Opcional: Resaltamos el total_final_usd para que se note el cambio
                $('#total_final_usd').addClass('text-success');
            } else {
                // Si no hay ahorro, escondemos la sección y volvemos al estado base
                $('#contenedor_referencia_original').slideUp(200);
                $('#total_final_usd').removeClass('text-success');
            }
        }


        $(document).on('change', 'input[name="tipo_documento"]', function() {
            $('#tipo_documento_hidden').val($(this).val());
        });

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
});
</script>
@endsection