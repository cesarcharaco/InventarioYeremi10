@extends('layouts.app')

@php
    /*
     * DATOS GENERALES
     */
    $infoAdicional = $venta->infoAdicional;

    $tipoDoc = strtolower(trim(
        $infoAdicional->tipo_documento
        ?? $venta->tipo_documento
        ?? ''
    ));

    $esFactura = $tipoDoc === 'factura';

    $numeroDocumento = $infoAdicional->correlativo_nota
        ?? $venta->codigo_factura;

    /*
     * IMPORTES GUARDADOS
     *
     * Conservamos como referencia los valores registrados por el controlador.
     * No recalculamos ni sustituimos los importes de la venta en la BD.
     */
    $baseImponibleBS = (float) ($infoAdicional->base_imponible_bs ?? 0);
    $ivaBS = (float) ($infoAdicional->iva_bs ?? 0);
    $totalBS = $baseImponibleBS + $ivaBS;

    $totalVentaUSD = (float) $venta->total_usd;

    /*
     * TASA UTILIZADA PARA PRESENTAR LOS PRODUCTOS
     *
     * Se mantiene el comportamiento de la vista anterior:
     * inferir la tasa desde los importes históricos guardados.
     */
    if ($totalBS > 0 && $totalVentaUSD > 0) {
        $tasa = $totalBS / $totalVentaUSD;
    } else {
        // Respaldo para registros sin importes históricos suficientes.
        $tasaRespaldo = (float) (bcv_rate('USD') ?? 0);
        $tasa = $tasaRespaldo > 0 ? $tasaRespaldo : 1;

        $totalBS = $totalVentaUSD * $tasa;
        $baseImponibleBS = $totalBS / 1.16;
        $ivaBS = $totalBS - $baseImponibleBS;
    }

    /*
     * FORMATO MONETARIO
     */
    $formatoBs = static function ($valor) {
        return number_format((float) $valor, 2, ',', '.');
    };

    /*
     * DESGLOSE DE PRESENTACIÓN POR RENGLÓN
     *
     * Todos los precios incluyen IVA del 16 %.
     * La base y el IVA mostrados corresponden a toda la cantidad del renglón.
     *
     * El IVA se obtiene por diferencia para que, visualmente:
     * base mostrada + IVA mostrado = total mostrado del renglón.
     */
    $renglones = collect($venta->detalles)->map(
        static function ($detalle) use ($tasa) {
            $cantidad = (float) $detalle->cantidad;
            $precioUnitarioUsd = (float) $detalle->precio_unitario;

            $precioUnitarioBs = $precioUnitarioUsd * $tasa;

            $totalRenglonBs = round(
                $cantidad * $precioUnitarioUsd * $tasa,
                2
            );

            $baseRenglonBs = round($totalRenglonBs / 1.16, 2);
            $ivaRenglonBs = round(
                $totalRenglonBs - $baseRenglonBs,
                2
            );

            $insumo = $detalle->insumo;

            $descripcion = trim(
                ($insumo->producto ?? '') . ' ' .
                ($insumo->descripcion ?? '')
            );

            return [
                'cantidad' => $detalle->cantidad,
                'descripcion' => $descripcion !== ''
                    ? $descripcion
                    : 'Producto no disponible',
                'precio_unitario_bs' => $precioUnitarioBs,
                'base_bs' => $baseRenglonBs,
                'iva_bs' => $ivaRenglonBs,
                'total_bs' => $totalRenglonBs,
            ];
        }
    );

    /*
     * CLIENTE
     */
    $cliente = $venta->cliente;

    $nombreCliente = $cliente->nombre ?? 'Cliente no disponible';
    $identificacionCliente = $cliente->identificacion ?? 'N/A';
    $direccionCliente = $cliente->direccion ?? 'San José de Guaribe';
    $telefonoCliente = $cliente->telefono ?? 'N/A';

    /*
     * PAGOS
     *
     * Se respetan los atributos utilizados en la vista original.
     */
    $pagoBsEfectivo = (float) ($venta->pago_bs_efectivo ?? 0);
    $pagoUsdEfectivo = (float) ($venta->pago_usd_efectivo ?? 0);
    $pagoPagomovilBs = (float) ($venta->pago_pagomovil_bs ?? 0);
    $pagoPuntoBs = (float) ($venta->pago_punto_bs ?? 0);

    /*
     * NUMERACIÓN
     *
     * Se conserva el comportamiento anterior.
     * La revisión del número de control queda fuera de este cambio visual.
     */
    $numeroFacturaMostrado = str_pad(
        (string) $venta->codigo_factura,
        6,
        '0',
        STR_PAD_LEFT
    );

    $numeroNotaMostrado = str_pad(
        (string) $numeroDocumento,
        6,
        '0',
        STR_PAD_LEFT
    );

    /*
     * TOTALES PARA MOSTRAR
     *
     * Conservamos el total histórico y calculamos el IVA visible
     * por diferencia entre total y base redondeados.
     * Esto solo afecta la presentación a dos decimales.
     */
    $totalMostrarBS = round($totalBS, 2);
    $baseMostrarBS = round($baseImponibleBS, 2);
    $ivaMostrarBS = round($totalMostrarBS - $baseMostrarBS, 2);
@endphp

@section('title')
    {{ $esFactura ? 'Factura Fiscal' : 'Nota de Entrega' }}
    #{{ $venta->codigo_factura }}
@endsection

@section('css')
<style>
    /*
     * ESTILOS COMUNES
     */
    .invoice-container {
        box-sizing: border-box;
    }

    .invoice-container *,
    .invoice-container *::before,
    .invoice-container *::after {
        box-sizing: border-box;
    }

    .text-money {
        text-align: right;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }

    .text-center-cell {
        text-align: center;
    }

    .linea-underline {
        border-bottom: 1px solid #1a365d;
        display: inline-block;
        padding: 0 5px;
        color: #000;
        font-weight: normal;
        overflow-wrap: anywhere;
    }

    .detalle-impuestos-nota {
        margin: 7px 0 12px;
        font-size: 0.72rem;
        color: #444;
        line-height: 1.4;
    }

    .table-scroll {
        width: 100%;
        overflow-x: auto;
    }

    /*
     * FACTURA
     */
    .invoice-fiscal {
        background: #fff;
        padding: 30px 35px;
        border: 1px solid #1a365d;
        max-width: 950px;
        margin: auto;
        color: #1a365d;
        font-family: Arial, Helvetica, sans-serif;
    }

    .header-fiscal h2 {
        font-size: 1.5rem;
        font-weight: 800;
        margin: 0;
        color: #1a365d;
    }

    .header-fiscal h3 {
        font-size: 1.4rem;
        font-weight: bold;
        margin: 0;
        color: #1a365d;
    }

    .header-fiscal p {
        margin: 1px 0;
        font-size: 0.8rem;
        font-weight: bold;
    }

    .num-factura {
        font-size: 1.3rem;
        font-weight: bold;
        color: #000;
        overflow-wrap: anywhere;
    }

    .control-box {
        font-size: 0.95rem;
        font-weight: bold;
        text-align: right;
        overflow-wrap: anywhere;
    }

    .fecha-box {
        font-size: 0.85rem;
        font-weight: bold;
        margin-top: 10px;
    }

    .cliente-info-grid {
        margin: 15px 0;
        font-size: 0.85rem;
        font-weight: bold;
    }

    .table-fiscal {
        width: 100%;
        border-collapse: collapse;
        border: 2px solid #1a365d;
        margin-bottom: 0;
        table-layout: fixed;
    }

    .table-fiscal th {
        border: 1px solid #1a365d;
        padding: 6px 4px;
        font-size: 0.7rem;
        font-weight: bold;
        text-align: center;
        background-color: #f8fafc;
        vertical-align: middle;
    }

    .table-fiscal td {
        border-left: 1px solid #1a365d;
        border-right: 1px solid #1a365d;
        border-bottom: 1px solid #e2e8f0;
        padding: 6px 4px;
        font-size: 0.76rem;
        color: #000;
        vertical-align: top;
    }

    .table-fiscal .descripcion-producto,
    .table-nota .descripcion-producto {
        overflow-wrap: anywhere;
    }

    .table-fiscal tr.empty-row td {
        height: 28px;
    }

    .footer-fiscal-container {
        border: 2px solid #1a365d;
        border-top: none;
        display: flex;
    }

    .formas-pago-box {
        width: 50%;
        border-right: 2px solid #1a365d;
        padding: 8px 12px;
        font-size: 0.75rem;
        font-weight: bold;
    }

    .totales-box {
        width: 50%;
    }

    .totales-box table {
        width: 100%;
        border-collapse: collapse;
    }

    .totales-box td {
        border-bottom: 1px solid #1a365d;
        padding: 6px 8px;
        font-size: 0.8rem;
        font-weight: bold;
    }

    .totales-box td.val-monto {
        text-align: right;
        white-space: nowrap;
        color: #000;
        font-size: 0.9rem;
        width: 40%;
        font-variant-numeric: tabular-nums;
    }

    .totales-box tr:last-child td {
        border-bottom: none;
    }

    .imprenta-legal {
        font-size: 0.65rem;
        text-align: center;
        margin-top: 10px;
        color: #333;
        line-height: 1.3;
        font-weight: bold;
    }

    .original-copia {
        font-size: 0.7rem;
        text-align: center;
        font-weight: bold;
        margin-top: 4px;
        text-transform: uppercase;
    }

    /*
     * NOTA DE ENTREGA
     */
    .invoice-nota {
        background: #fff;
        padding: 30px;
        border: 1px solid #ddd;
        max-width: 950px;
        margin: auto;
        color: #000;
        font-family: Arial, Helvetica, sans-serif;
    }

    .company-title {
        color: #8b0000;
        font-size: 1.8rem;
        font-weight: 900;
        text-transform: uppercase;
        margin: 0;
        line-height: 1.1;
    }

    .company-subtitle {
        font-size: 0.95rem;
        font-weight: bold;
        color: #222;
        margin-bottom: 5px;
    }

    .company-info {
        font-size: 0.8rem;
        color: #444;
        line-height: 1.3;
    }

    .box-header-right {
        border: 1.5px solid #000;
        border-radius: 12px;
        padding: 10px 15px;
        text-align: center;
        background: #fff;
    }

    .box-header-right h4 {
        color: #8b0000;
        font-weight: bold;
        font-size: 1.1rem;
        margin-bottom: 8px;
        border-bottom: 1px solid #000;
        padding-bottom: 4px;
    }

    .box-header-right p {
        margin: 3px 0;
        font-size: 0.85rem;
        text-align: left;
        font-weight: bold;
        overflow-wrap: anywhere;
    }

    .banner-black {
        background-color: #000;
        color: #fff;
        text-align: center;
        font-weight: bold;
        font-size: 1rem;
        padding: 6px;
        letter-spacing: 1px;
        margin: 15px 0;
    }

    .box-cliente {
        border: 1.5px solid #000;
        border-radius: 10px;
        padding: 12px 18px;
        margin-bottom: 20px;
        font-size: 0.9rem;
    }

    .box-cliente table {
        width: 100%;
    }

    .box-cliente td {
        padding: 3px 0;
        vertical-align: middle;
        overflow-wrap: anywhere;
    }

    .table-nota {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
        table-layout: fixed;
    }

    .table-nota th {
        background-color: #f0f0f0;
        border: 1px solid #000;
        padding: 7px 4px;
        font-size: 0.7rem;
        font-weight: bold;
        text-transform: uppercase;
        text-align: center;
        vertical-align: middle;
    }

    .table-nota td {
        border: 1px solid #000;
        padding: 7px 4px;
        font-size: 0.76rem;
        vertical-align: top;
    }

    .table-totales {
        width: 100%;
        border-collapse: collapse;
    }

    .table-totales td {
        border: 1px solid #000;
        padding: 6px 10px;
        font-size: 0.85rem;
        font-weight: bold;
    }

    .table-totales .row-total {
        color: #8b0000;
    }

    .footer-nota {
        border-top: 1px dashed #000;
        margin-top: 25px;
        padding-top: 8px;
        text-align: center;
        font-size: 0.8rem;
        font-weight: bold;
        color: #000;
    }

    /*
     * IMPRESIÓN
     */
    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: #fff !important;
        }

        .app-content {
            margin: 0 !important;
            padding: 0 !important;
        }

        .tile {
            padding: 0 !important;
            margin: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }

        .invoice-container {
            border: none !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }

        .table-scroll {
            overflow: visible !important;
        }

        .table-fiscal thead,
        .table-nota thead {
            display: table-header-group;
        }

        .table-fiscal tr,
        .table-nota tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .footer-fiscal-container,
        .table-totales,
        .box-cliente,
        .box-header-right {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .banner-black,
        .table-nota th,
        .table-fiscal th {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>
@endsection

@section('content')
<main class="app-content">
    <div class="row">
        <div class="col-md-12">
            <div class="tile">

                @if($esFactura)
                    {{-- =============================================== --}}
                    {{--                 FACTURA FISCAL                 --}}
                    {{-- =============================================== --}}
                    <section class="invoice-container invoice-fiscal">

                        <div class="row header-fiscal align-items-start">
                            <div class="col-7">
                                <h2>Yermotors Repuestos, C.A.</h2>
                                <p style="font-size: 0.85rem;">
                                    RIF. J-50186803-4
                                </p>
                                <p>Venta de Lubricantes y Repuestos para Motos</p>
                                <p>Calle Páez entre Bolívar y Guzmán Blanco</p>
                                <p>Casa s/n, Sector El Centro</p>
                                <p>San José de Guaribe - Guárico - Z.P. 2323</p>
                                <p>Teléfono: 0414 - 086.31.07</p>
                            </div>

                            <div class="col-5 text-right">
                                <div class="d-flex justify-content-end align-items-baseline">
                                    <h3 class="mr-2">Factura</h3>
                                    <span class="num-factura">
                                        Nº {{ $numeroFacturaMostrado }}
                                    </span>
                                </div>

                                <div class="control-box mt-3">
                                    No. de Control
                                    <span style="font-size: 1.1rem; color: #000;">
                                        00 — {{ $numeroFacturaMostrado }}
                                    </span>
                                </div>

                                <div class="fecha-box mt-2">
                                    Fecha de Emisión:

                                    <strong>D</strong>
                                    <span
                                        class="linea-underline"
                                        style="min-width: 30px; text-align: center;"
                                    >
                                        {{ $venta->created_at->format('d') }}
                                    </span>

                                    <strong>M</strong>
                                    <span
                                        class="linea-underline"
                                        style="min-width: 30px; text-align: center;"
                                    >
                                        {{ $venta->created_at->format('m') }}
                                    </span>

                                    <strong>A</strong>
                                    <span
                                        class="linea-underline"
                                        style="min-width: 45px; text-align: center;"
                                    >
                                        {{ $venta->created_at->format('Y') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="cliente-info-grid">
                            <div class="row mb-2">
                                <div class="col-7">
                                    Nombre/Apellido o Razón Social:
                                    <span class="linea-underline">
                                        {{ $nombreCliente }}
                                    </span>
                                </div>

                                <div class="col-5">
                                    RIF./C.I.:
                                    <span class="linea-underline">
                                        {{ $identificacionCliente }}
                                    </span>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-5">
                                    Dirección Fiscal:
                                    <span class="linea-underline">
                                        {{ $direccionCliente }}
                                    </span>
                                </div>

                                <div class="col-3">
                                    Telf.:
                                    <span class="linea-underline">
                                        {{ $telefonoCliente }}
                                    </span>
                                </div>

                                <div class="col-4">
                                    Condiciones de Pago:
                                    <span class="linea-underline">
                                        {{ $venta->monto_credito_usd > 0 ? 'Crédito' : 'Contado' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="table-scroll">
                            <table class="table-fiscal">
                                <thead>
                                    <tr>
                                        <th style="width: 6%;">Cant.</th>
                                        <th style="width: 34%;">Descripción</th>
                                        <th style="width: 15%;">
                                            P. unitario<br>
                                            con IVA (Bs.)
                                        </th>
                                        <th style="width: 15%;">
                                            Base sin IVA<br>
                                            del renglón (Bs.)
                                        </th>
                                        <th style="width: 14%;">
                                            IVA 16 %<br>
                                            del renglón (Bs.)
                                        </th>
                                        <th style="width: 16%;">
                                            Total con IVA<br>
                                            del renglón (Bs.)
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach($renglones as $renglon)
                                        <tr>
                                            <td class="text-center-cell">
                                                {{ $renglon['cantidad'] }}
                                            </td>

                                            <td class="descripcion-producto">
                                                {{ $renglon['descripcion'] }}
                                            </td>

                                            <td class="text-money">
                                                {{ $formatoBs($renglon['precio_unitario_bs']) }}
                                            </td>

                                            <td class="text-money">
                                                {{ $formatoBs($renglon['base_bs']) }}
                                            </td>

                                            <td class="text-money">
                                                {{ $formatoBs($renglon['iva_bs']) }}
                                            </td>

                                            <td class="text-money">
                                                {{ $formatoBs($renglon['total_bs']) }}
                                            </td>
                                        </tr>
                                    @endforeach

                                    @for($i = $renglones->count(); $i < 8; $i++)
                                        <tr class="empty-row">
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>

                        <div class="footer-fiscal-container">
                            <div class="formas-pago-box">
                                <div
                                    style="
                                        font-size: 0.7rem;
                                        margin-bottom: 4px;
                                        font-style: italic;
                                    "
                                >
                                    Este Documento va sin tachadura ni enmienda
                                </div>

                                <div class="mb-1">Forma de Pago:</div>
                                <div class="mb-1">
                                    Divisas ______ B.C.V. ______
                                </div>

                                <div class="row no-gutters">
                                    <div class="col-6">
                                        <i class="{{ $pagoBsEfectivo > 0 ? 'fa fa-check-square-o' : 'fa fa-square-o' }}"></i>
                                        Efectivo Bs.<br>

                                        <i class="{{ $pagoPagomovilBs > 0 ? 'fa fa-check-square-o' : 'fa fa-square-o' }}"></i>
                                        Pago Móvil<br>

                                        Banco ____________
                                    </div>

                                    <div class="col-6">
                                        <i class="{{ $pagoPuntoBs > 0 ? 'fa fa-check-square-o' : 'fa fa-square-o' }}"></i>
                                        Tarjeta<br>

                                        <i class="{{ $pagoUsdEfectivo > 0 ? 'fa fa-check-square-o' : 'fa fa-square-o' }}"></i>
                                        Efectivo USD<br>

                                        <i class="fa fa-square-o"></i>
                                        Transf.
                                    </div>
                                </div>
                            </div>

                            <div class="totales-box">
                                <table>
                                    <tr>
                                        <td>BASE IMPONIBLE Bs.</td>
                                        <td class="val-monto">
                                            {{ $formatoBs($baseMostrarBS) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            I.V.A. 16 % sobre Bs.
                                            <span style="font-size: 0.72rem; font-weight: normal;">
                                                {{ $formatoBs($baseMostrarBS) }}
                                            </span>
                                        </td>
                                        <td class="val-monto">
                                            {{ $formatoBs($ivaMostrarBS) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>I.G.T.F. ____% Bs.</td>
                                        <td class="val-monto">0,00</td>
                                    </tr>
                                    <tr>
                                        <td>TOTAL EXENTO Bs.</td>
                                        <td class="val-monto">0,00</td>
                                    </tr>

                                    <tr>
                                        <td style="font-size: 0.9rem;">
                                            TOTAL A PAGAR Bs.
                                        </td>
                                        <td
                                            class="val-monto"
                                            style="font-weight: 900; font-size: 1rem;"
                                        >
                                            {{ $formatoBs($totalMostrarBS) }}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="detalle-impuestos-nota">
                            Precios unitarios con IVA del 16 % incluido.
                            La base y el IVA de cada renglón corresponden
                            a la cantidad completa del producto.
                            El IVA indicado ya forma parte del total:
                            no representa un cargo adicional.
                        </div>

                        <div class="imprenta-legal">
                            Soluciones Gráficas SYVI 2013, C.A. -
                            Calle Hurtado Ascanio No. 5, Sector Cumbito -
                            Teléfono: 0238-334.07.28<br>

                            Altagracia de Orituco - Guárico -
                            RIF. J-41000256-2 -
                            Prov. SENIAT 102101355 Fecha 03-08-2018<br>

                            No. de Facturas: Desde el No. 000101 hasta el
                            No. 000150 -
                            No. de Control: Desde el No. 00-000101 hasta
                            el No. 00-000150 -
                            Región Los Llanos - Fecha 19-02-2024
                        </div>

                        <div class="original-copia">
                            Original Blanco - Copia Color -
                            Solo Original da derecho a Crédito Fiscal
                        </div>
                    </section>

                @else
                    {{-- =============================================== --}}
                    {{--                 NOTA DE ENTREGA                --}}
                    {{-- =============================================== --}}
                    <section class="invoice-container invoice-nota">

                        <div class="row align-items-center">
                            <div class="col-7">
                                <h1 class="company-title">
                                    YERMOTORS REPUESTOS C.A.
                                </h1>

                                <div class="company-subtitle">
                                    Venta de Repuestos y Accesorios
                                </div>

                                <div class="company-info">
                                    <strong>RIF:</strong> J-50186803-4<br>

                                    <strong>Dirección:</strong>
                                    Calle Páez entre Bolívar y Guzmán Blanco,
                                    Casa S/N, Sector Centro,
                                    San José de Guaribe, Estado Guárico.<br>

                                    <strong>Teléfono:</strong>
                                    0414-0863107
                                </div>
                            </div>

                            <div class="col-5">
                                <div class="box-header-right">
                                    <h4>NOTA DE ENTREGA</h4>

                                    <p>
                                        <strong>FECHA:</strong>
                                        {{ $venta->created_at->format('d / m / Y') }}
                                    </p>

                                    <p>
                                        <strong>N° CONTROL:</strong>
                                        {{ $numeroNotaMostrado }}
                                    </p>

                                    <p>
                                        <strong>PEDIDO N°:</strong>
                                        ______________________
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="banner-black">
                            NOTA DE ENTREGA / COMPROBANTE DE VENTA
                        </div>

                        <div class="box-cliente">
                            <table>
                                <tr>
                                    <td style="width: 15%;">
                                        <strong>CLIENTE:</strong>
                                    </td>
                                    <td colspan="3">
                                        {{ $nombreCliente }}
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <strong>DIRECCIÓN:</strong>
                                    </td>
                                    <td colspan="3">
                                        {{ $direccionCliente }}
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <strong>RIF / C.I.:</strong>
                                    </td>
                                    <td style="width: 40%;">
                                        {{ $identificacionCliente }}
                                    </td>
                                    <td style="width: 18%;">
                                        <strong>TELÉFONO:</strong>
                                    </td>
                                    <td>
                                        {{ $telefonoCliente }}
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="table-scroll">
                            <table class="table-nota">
                                <thead>
                                    <tr>
                                        <th style="width: 6%;">Cant.</th>
                                        <th style="width: 34%;">Descripción</th>
                                        <th style="width: 15%;">
                                            P. unitario<br>
                                            con IVA (Bs.)
                                        </th>
                                        <th style="width: 15%;">
                                            Base sin IVA<br>
                                            del renglón (Bs.)
                                        </th>
                                        <th style="width: 14%;">
                                            IVA 16 %<br>
                                            del renglón (Bs.)
                                        </th>
                                        <th style="width: 16%;">
                                            Total con IVA<br>
                                            del renglón (Bs.)
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach($renglones as $renglon)
                                        <tr>
                                            <td class="text-center-cell">
                                                {{ $renglon['cantidad'] }}
                                            </td>

                                            <td class="descripcion-producto">
                                                {{ $renglon['descripcion'] }}
                                            </td>

                                            <td class="text-money">
                                                {{ $formatoBs($renglon['precio_unitario_bs']) }}
                                            </td>

                                            <td class="text-money">
                                                {{ $formatoBs($renglon['base_bs']) }}
                                            </td>

                                            <td class="text-money">
                                                {{ $formatoBs($renglon['iva_bs']) }}
                                            </td>

                                            <td class="text-money">
                                                {{ $formatoBs($renglon['total_bs']) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="detalle-impuestos-nota">
                            Precios unitarios con IVA del 16 % incluido.
                            La base y el IVA de cada renglón corresponden
                            a la cantidad completa del producto.
                            El IVA indicado ya forma parte del total:
                            no representa un cargo adicional.
                        </div>

                        <div class="row">
                            <div class="col-5"></div>

                            <div class="col-7">
                                <table class="table-totales">
                                    <tr>
                                        <td style="width: 62%;">
                                            BASE IMPONIBLE (Bs.)
                                        </td>
                                        <td class="text-money">
                                            {{ $formatoBs($baseMostrarBS) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>IVA 16 % (Bs.)</td>
                                        <td class="text-money">
                                            {{ $formatoBs($ivaMostrarBS) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>SUBTOTAL CON IVA (Bs.)</td>
                                        <td class="text-money">
                                            {{ $formatoBs($totalMostrarBS) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>FLETE (Bs.)</td>
                                        <td class="text-money">0,00</td>
                                    </tr>

                                    <tr class="row-total">
                                        <td>TOTAL GENERAL (Bs.)</td>
                                        <td class="text-money">
                                            {{ $formatoBs($totalMostrarBS) }}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="footer-nota">
                            Gracias por su preferencia.
                            Los cambios o reclamos de partes eléctricas
                            no tienen garantía.
                        </div>
                    </section>
                @endif

                {{-- BOTONES: NO SE IMPRIMEN --}}
                <div class="row no-print mt-4">
                    <div class="col-12 text-center">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            onclick="window.print();"
                        >
                            <i class="fa fa-print"></i>
                            Imprimir
                            {{ $esFactura ? 'Factura Fiscal' : 'Nota de Entrega' }}
                        </button>

                        <a
                            href="{{ route('ventas.index') }}"
                            class="btn btn-primary"
                        >
                            Volver al listado
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>
@endsection