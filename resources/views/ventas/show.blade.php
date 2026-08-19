@extends('layouts.app')

@php
    // Accedemos a infoAdicional con un respaldo seguro por si llega nulo
    $infoAdicional = $venta->infoAdicional;
    $tipoDoc = strtolower(trim($infoAdicional->tipo_documento ?? $venta->tipo_documento ?? ''));
    
    // Verificamos si es Factura Fiscal
    $esFactura = ($tipoDoc === 'factura');

    // Correlativo / Número de documento
    $numeroDocumento = $infoAdicional->correlativo_nota ?? $venta->codigo_factura;

    // 1. Obtener la información adicional relacionada
        $info = $venta->infoAdicional;

        // 2. Obtener Base, IVA y Total en Bolívares guardados en BD
        $baseImponibleBS = $info->base_imponible_bs ?? 0;
        $ivaBS           = $info->iva_bs ?? 0;
        $totalBS         = $baseImponibleBS + $ivaBS;

        // 3. Despejar la tasa histórica exacta (evitando división por cero)
        if ($totalBS > 0 && $venta->total_usd > 0) {
            $tasa = $totalBS / $venta->total_usd;
        } else {
            // Respaldo por si es un registro incompleto o en $0
            $tasa = bcv_rate('USD') ?? 1;
            $totalBS = $venta->total_usd * $tasa;
            $baseImponibleBS = $totalBS / 1.16;
            $ivaBS = $baseImponibleBS * 0.16;
        }
@endphp

@section('title') 
    {{ $esFactura ? 'Factura Fiscal' : 'Nota de Entrega' }} #{{ $venta->codigo_factura }} 
@endsection

@section('css')
<style>
    /* ==========================================
       1. ESTILOS COMUNES Y CONFIGURACIÓN PRINT
       ========================================== */
    @media print {
        .no-print { display: none !important; }
        .invoice-container { border: none !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; }
        body { background: white !important; }
        .tile { padding: 0 !important; margin: 0 !important; border: none !important; box-shadow: none !important; }
    }

    .linea-underline {
        border-bottom: 1px solid #1a365d;
        display: inline-block;
        padding-left: 5px;
        color: #000;
        font-weight: normal;
    }

    /* ==========================================
       2. ESTILOS PARA LA FACTURA FISCAL (SENIAT)
       ========================================== */
    .invoice-fiscal {
        background: #fff;
        padding: 30px 35px;
        border: 1px solid #1a365d;
        max-width: 850px;
        margin: auto;
        color: #1a365d;
        font-family: Arial, Helvetica, sans-serif;
    }

    .header-fiscal h2 { font-size: 1.5rem; font-weight: 800; margin: 0; color: #1a365d; }
    .header-fiscal h3 { font-size: 1.4rem; font-weight: bold; margin: 0; color: #1a365d; }
    .header-fiscal p { margin: 1px 0; font-size: 0.8rem; font-weight: bold; }

    .num-factura { font-size: 1.5rem; font-weight: bold; color: #000; }
    .control-box { font-size: 0.95rem; font-weight: bold; text-align: right; }
    .fecha-box { font-size: 0.85rem; font-weight: bold; margin-top: 10px; }

    .cliente-info-grid { margin-top: 15px; margin-bottom: 15px; font-size: 0.85rem; font-weight: bold; }

    .table-fiscal { width: 100%; border-collapse: collapse; border: 2px solid #1a365d; margin-bottom: 0; }
    .table-fiscal th { border: 1px solid #1a365d; padding: 5px; font-size: 0.8rem; font-weight: bold; text-align: center; background-color: #f8fafc; }
    .table-fiscal td { border-left: 1px solid #1a365d; border-right: 1px solid #1a365d; border-bottom: 1px solid #e2e8f0; padding: 6px; font-size: 0.85rem; color: #000; }
    .table-fiscal tr.empty-row td { height: 28px; }

    .footer-fiscal-container { border: 2px solid #1a365d; border-top: none; display: flex; }
    .formas-pago-box { width: 50%; border-right: 2px solid #1a365d; padding: 8px 12px; font-size: 0.75rem; font-weight: bold; }
    .totales-box { width: 50%; }
    .totales-box table { width: 100%; border-collapse: collapse; }
    .totales-box td { border-bottom: 1px solid #1a365d; padding: 5px 8px; font-size: 0.85rem; font-weight: bold; }
    .totales-box td.val-monto { text-align: right; color: #000; font-size: 0.95rem; width: 40%; }

    .imprenta-legal { font-size: 0.65rem; text-align: center; margin-top: 10px; color: #333; line-height: 1.2; font-weight: bold; }
    .original-copia { font-size: 0.7rem; text-align: center; font-weight: bold; margin-top: 4px; text-transform: uppercase; }

    /* ==========================================
       3. ESTILOS PARA LA NOTA DE ENTREGA (INTERNA)
       ========================================== */
    .invoice-nota {
        background: #fff;
        padding: 30px;
        border: 1px solid #ddd;
        max-width: 850px;
        margin: auto;
        color: #000;
        font-family: Arial, Helvetica, sans-serif;
    }

    .company-title { color: #8b0000; font-size: 1.8rem; font-weight: 900; text-transform: uppercase; margin: 0; line-height: 1.1; }
    .company-subtitle { font-size: 0.95rem; font-weight: bold; color: #222; margin-bottom: 5px; }
    .company-info { font-size: 0.8rem; color: #444; line-height: 1.3; }

    .box-header-right { border: 1.5px solid #000; border-radius: 12px; padding: 10px 15px; text-align: center; background: #fff; }
    .box-header-right h4 { color: #8b0000; font-weight: bold; font-size: 1.1rem; margin-bottom: 8px; border-bottom: 1px solid #000; padding-bottom: 4px; }
    .box-header-right p { margin: 3px 0; font-size: 0.85rem; text-align: left; font-weight: bold; }

    .banner-black { background-color: #000; color: #fff; text-align: center; font-weight: bold; font-size: 1rem; padding: 6px; letter-spacing: 1px; margin: 15px 0; }

    .box-cliente { border: 1.5px solid #000; border-radius: 10px; padding: 12px 18px; margin-bottom: 20px; font-size: 0.9rem; }
    .box-cliente table { width: 100%; }
    .box-cliente td { padding: 3px 0; vertical-align: middle; }

    .table-nota { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    .table-nota th { background-color: #f0f0f0; border: 1px solid #000; padding: 8px; font-size: 0.85rem; font-weight: bold; text-transform: UPPERCASE; }
    .table-nota td { border: 1px solid #000; padding: 7px 10px; font-size: 0.85rem; }

    .table-totales { width: 100%; border-collapse: collapse; float: right; }
    .table-totales td { border: 1px solid #000; padding: 6px 12px; font-size: 0.9rem; font-weight: bold; }
    .table-totales .row-total { color: #8b0000; }

    .footer-nota { border-top: 1px dashed #000; margin-top: 25px; padding-top: 8px; text-align: center; font-size: 0.8rem; font-weight: bold; color: #000; }
</style>
@endsection

@section('content')
<main class="app-content">
    <div class="row">
        <div class="col-md-12">
            <div class="tile">

                @if($esFactura)
                    {{-- ========================================================= --}}
                    {{--                     FACTURA FISCAL                        --}}
                    {{-- ========================================================= --}}
                    <section class="invoice-container invoice-fiscal">
                        
                        {{-- Encabezado Fiscal --}}
                        <div class="row header-fiscal align-items-start">
                            <div class="col-7">
                                <h2>Yermotors Repuestos, C.A.</h2>
                                <p style="font-size: 0.85rem;">RIF. J-50186803-4</p>
                                <p>Venta de Lubricantes y Repuestos para Motos</p>
                                <p>Calle Páez entre Bolívar y Guzmán Blanco</p>
                                <p>Casa s/n, Sector El Centro</p>
                                <p>San José de Guaribe - Guárico - Z.P. 2323</p>
                                <p>Teléfono: 0414 - 086.31.07</p>
                            </div>
                            
                            <div class="col-5 text-right">
                                <div class="d-flex justify-content-end align-items-baseline">
                                    <h3 class="mr-2">Factura</h3>
                                    <span class="num-factura">Nº {{ str_pad($venta->codigo_factura, 6, '0', STR_PAD_LEFT) }}</span>
                                </div>
                                
                                <div class="control-box mt-3">
                                    No. de Control <span style="font-size: 1.1rem; color: #000;">00 — {{ str_pad($venta->codigo_factura, 6, '0', STR_PAD_LEFT) }}</span>
                                </div>

                                <div class="fecha-box mt-2">
                                    Fecha de Emisión: 
                                    <strong>D</strong> <span class="linea-underline" style="min-width: 30px; text-align: center;">{{ $venta->created_at->format('d') }}</span>
                                    <strong>M</strong> <span class="linea-underline" style="min-width: 30px; text-align: center;">{{ $venta->created_at->format('m') }}</span>
                                    <strong>A</strong> <span class="linea-underline" style="min-width: 45px; text-align: center;">{{ $venta->created_at->format('Y') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Datos del Cliente Formateados --}}
                        <div class="cliente-info-grid">
                            <div class="row mb-2">
                                <div class="col-7">
                                    Nombre/Apellido o Razón Social: 
                                    <span class="linea-underline" style="width: 55%;">{{ $venta->cliente->nombre }}</span>
                                </div>
                                <div class="col-5">
                                    RIF./C.I.: 
                                    <span class="linea-underline" style="width: 70%;">{{ $venta->cliente->identificacion }}</span>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-5">
                                    Dirección Fiscal: 
                                    <span class="linea-underline" style="width: 60%;">{{ $venta->cliente->direccion ?? 'San José de Guaribe' }}</span>
                                </div>
                                <div class="col-3">
                                    Telf.: 
                                    <span class="linea-underline" style="width: 70%;">{{ $venta->cliente->telefono ?? 'N/A' }}</span>
                                </div>
                                <div class="col-4">
                                    Condiciones de Pago: 
                                    <span class="linea-underline" style="width: 40%;">{{ $venta->monto_credito_usd > 0 ? 'Crédito' : 'Contado' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Tabla Fiscal de Productos --}}
                        <table class="table-fiscal">
                            <thead>
                                <tr>
                                    <th style="width: 8%;">Cant.</th>
                                    <th style="width: 52%;">DESCRIPCION</th>
                                    <th style="width: 14%;">P. Unit.</th>
                                    <th style="width: 8%;">Alc. %</th>
                                    <th style="width: 4%;">E</th>
                                    <th style="width: 14%;">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($venta->detalles as $detalle)
                                @php
                                    $precioUnitarioBS = $detalle->precio_unitario * $tasa;
                                    $subtotalBS = ($detalle->cantidad * $detalle->precio_unitario) * $tasa;
                                @endphp
                                <tr>
                                    <td style="text-align: center;">{{ $detalle->cantidad }}</td>
                                    <td>{{ $detalle->insumo->producto }} {{ $detalle->insumo->descripcion }}</td>
                                    <td style="text-align: right;">{{ number_format($precioUnitarioBS, 2, ',', '.') }}</td>
                                    <td style="text-align: center;">16</td>
                                    <td style="text-align: center;"></td>
                                    <td style="text-align: right;">{{ number_format($subtotalBS, 2, ',', '.') }}</td>
                                </tr>
                                @endforeach

                                {{-- Relleno de filas estilo talonario --}}
                                @for ($i = count($venta->detalles); $i < 8; $i++)
                                <tr class="empty-row">
                                    <td></td><td></td><td></td><td></td><td></td><td></td>
                                </tr>
                                @endfor
                            </tbody>
                        </table>

                        {{-- Totales y Formas de Pago --}}
                        <div class="footer-fiscal-container">
                            <div class="formas-pago-box">
                                <div style="font-size: 0.7rem; margin-bottom: 4px; font-style: italic;">
                                    Este Documento va sin tachadura ni enmienda
                                </div>
                                <div class="mb-1">Forma de Pago:</div>
                                <div class="mb-1">Divisas ______ B.C.V. ______</div>
                                <div class="row no-gutters">
                                    <div class="col-6">
                                        <i class="{{ $venta->pago_bs_efectivo > 0 ? 'fa fa-check-square-o' : 'fa fa-square-o' }}"></i> Efectivo Bs.<br>
                                        <i class="{{ $venta->pago_pagomovil_bs > 0 ? 'fa fa-check-square-o' : 'fa fa-square-o' }}"></i> Pago Móvil<br>
                                        Banco ____________
                                    </div>
                                    <div class="col-6">
                                        <i class="{{ $venta->pago_punto_bs > 0 ? 'fa fa-check-square-o' : 'fa fa-square-o' }}"></i> Tarjeta<br>
                                        <i class="fa fa-square-o"></i> Transf.
                                    </div>
                                </div>
                            </div>

                            <div class="totales-box">
                                <table>
                                    <tr>
                                        <td>BASE IMPONIBLE Bs.</td>
                                        <td class="val-monto">{{ number_format($baseImponibleBS, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>I.V.A. 16% Sobre Bs. <span style="font-size: 0.75rem; font-weight: normal;">{{ number_format($baseImponibleBS, 2, ',', '.') }}</span></td>
                                        <td class="val-monto">{{ number_format($ivaBS, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>I.G.T.F. ____% Bs.</td>
                                        <td class="val-monto">0,00</td>
                                    </tr>
                                    <tr>
                                        <td>TOTAL EXENTO Bs.</td>
                                        <td class="val-monto">0,00</td>
                                    </tr>
                                    <tr style="border-bottom: none;">
                                        <td style="font-size: 0.95rem; color: #1a365d;">TOTAL A PAGAR Bs.</td>
                                        <td class="val-monto" style="font-weight: 900; font-size: 1.1rem;">{{ number_format($totalBS, 2, ',', '.') }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        {{-- Pie Legal Imprenta SENIAT --}}
                        <div class="imprenta-legal">
                            Soluciones Gráficas SYVI 2013, C.A. - Calle Hurtado Ascanio No. 5, Sector Cumbito - Teléfono: 0238-334.07.28<br>
                            Altagracia de Orituco - Guárico - RIF. J-41000256-2 - Prov. SENIAT 102101355 Fecha 03-08-2018<br>
                            No. de Facturas: Desde el No. 000101 hasta el No. 000150 - No. de Control: Desde el No. 00-000101 hasta el No. 00-000150 - Región Los Llanos - Fecha 19-02-2024
                        </div>

                        <div class="original-copia">
                            Original Blanco - Copia Color - Solo Original da derecho a Crédito Fiscal
                        </div>

                    </section>

                @else
                    {{-- ========================================================= --}}
                    {{--                     NOTA DE ENTREGA                       --}}
                    {{-- ========================================================= --}}
                    <section class="invoice-container invoice-nota">
                        
                        {{-- Encabezado Nota --}}
                        <div class="row align-items-center">
                            <div class="col-7">
                                <h1 class="company-title">YERMOTORS REPUESTOS C.A.</h1>
                                <div class="company-subtitle">Venta de Repuestos y Accesorios</div>
                                <div class="company-info">
                                    <strong>RIF:</strong> J-50186803-4<br>
                                    <strong>Dirección:</strong> Calle Páez entre Bolívar y Guzmán Blanco, Casa S/N, Sector Centro, San José de Guaribe, Estado Guárico.<br>
                                    <strong>Teléfono:</strong> 0414-0863107
                                </div>
                            </div>
                            
                            <div class="col-5">
                                <div class="box-header-right">
                                    <h4>NOTA DE ENTREGA</h4>
                                    <p><strong>FECHA:</strong> {{ $venta->created_at->format('d / m / Y') }}</p>
                                    <p><strong>N° CONTROL:</strong> {{ str_pad($venta->correlativo_nota ?? $venta->codigo_factura, 6, '0', STR_PAD_LEFT) }}</p>
                                    <p><strong>PEDIDO N°:</strong> ______________________</p>
                                </div>
                            </div>
                        </div>

                        {{-- Banner Negro Central --}}
                        <div class="banner-black">
                            NOTA DE ENTREGA / COMPROBANTE DE VENTA
                        </div>

                        {{-- Datos del Cliente --}}
                        <div class="box-cliente">
                            <table>
                                <tr>
                                    <td style="width: 15%;"><strong>CLIENTE:</strong></td>
                                    <td>{{ $venta->cliente->nombre }}</td>
                                </tr>
                                <tr>
                                    <td><strong>DIRECCIÓN:</strong></td>
                                    <td>{{ $venta->cliente->direccion ?? 'San José de Guaribe' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>RIF / C.I.:</strong></td>
                                    <td style="width: 45%;">{{ $venta->cliente->identificacion }}</td>
                                    <td style="width: 15%;"><strong>TELÉFONO:</strong></td>
                                    <td>{{ $venta->cliente->telefono ?? '____________________' }}</td>
                                </tr>
                            </table>
                        </div>

                        {{-- Tabla de Productos --}}
                        <table class="table-nota">
                            <thead>
                                <tr>
                                    <th style="width: 8%; text-align: center;">CANT.</th>
                                    <th style="width: 62%;">DESCRIPCIÓN</th>
                                    <th style="width: 15%; text-align: right;">P. UNITARIO (Bs.)</th>
                                    <th style="width: 15%; text-align: right;">P. TOTAL (Bs.)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($venta->detalles as $detalle)
                                @php
                                    $precioUnitarioBS = $detalle->precio_unitario * $tasa;
                                    $subtotalBS = ($detalle->cantidad * $detalle->precio_unitario) * $tasa;
                                @endphp
                                <tr>
                                    <td style="text-align: center;">{{ $detalle->cantidad }}</td>
                                    <td>{{ $detalle->insumo->producto }} {{ $detalle->insumo->descripcion }}</td>
                                    <td style="text-align: right;">{{ number_format($precioUnitarioBS, 2, ',', '.') }}</td>
                                    <td style="text-align: right;">{{ number_format($subtotalBS, 2, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- Totales en Bolívares --}}
                        <div class="row">
                            <div class="col-6"></div>
                            <div class="col-6">
                                <table class="table-totales">
                                    <tr>
                                        <td style="width: 60%;">SUB-TOTAL (Bs.)</td>
                                        <td style="text-align: right; width: 40%;">{{ number_format($totalBS, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>FLETE (Bs.)</td>
                                        <td style="text-align: right;">0,00</td>
                                    </tr>
                                    <tr class="row-total">
                                        <td>TOTAL GENERAL (Bs.)</td>
                                        <td style="text-align: right;">{{ number_format($totalBS, 2, ',', '.') }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        {{-- Mensaje de Pie de Página --}}
                        <div class="footer-nota">
                            Gracias por su preferencia. Los cambios o reclamos de partes eléctricas no tienen garantía.
                        </div>

                    </section>
                @endif

                {{-- ========================================================= --}}
                {{--         BOTONES DE ACCIÓN (OCULTOS AL IMPRIMIR)           --}}
                {{-- ========================================================= --}}
                <div class="row no-print mt-4">
                    <div class="col-12 text-center">
                        <button class="btn btn-secondary" onclick="window.print();">
                            <i class="fa fa-print"></i> Imprimir {{ $esFactura ? 'Factura Fiscal' : 'Nota de Entrega' }}
                        </button>
                        <a href="{{ route('ventas.index') }}" class="btn btn-primary">
                            Volver al listado
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>
@endsection