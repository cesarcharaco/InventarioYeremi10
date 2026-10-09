@php
    $totalDebePeriodo = $montoTotalCreditos ?? 0;
    $totalAbonosPeriodo = $totalAbonadoPeriodo ?? 0;
    $totalIndexacionesPeriodo = $totalInteresesPeriodo ?? 0;

    // Créditos + indexaciones - abonos
    $saldoNetoPeriodo = (
        $totalDebePeriodo
        + $totalIndexacionesPeriodo
        - $totalAbonosPeriodo
    );

    $resumenPeriodo = [
        'monto_inicial'   => $totalDebePeriodo,
        'total_intereses' => $totalIndexacionesPeriodo,
        'total_abonado'   => $totalAbonosPeriodo,
        'saldo_pendiente' => max(0, $saldoNetoPeriodo),
    ];
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Movimientos - {{ $cliente->nombre }}</title>

    <style>
        @page {
            margin: 20px 25px;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #333333;
            line-height: 1.3;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .company-title {
            color: #8b0000;
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.1;
        }

        .company-subtitle {
            font-size: 9.5px;
            font-weight: bold;
            color: #222;
            margin-bottom: 3px;
        }

        .company-info-text {
            font-size: 8.5px;
            color: #444;
            line-height: 1.25;
        }

        .box-header-right {
            border: 1.5px solid #8b0000;
            border-radius: 5px;
            padding: 6px 10px;
            text-align: center;
            background: #fff;
        }

        .box-header-right h4 {
            color: #8b0000;
            font-weight: bold;
            font-size: 10px;
            margin: 0 0 4px 0;
            border-bottom: 1px solid #8b0000;
            padding-bottom: 2px;
            text-transform: uppercase;
        }

        .box-header-right p {
            margin: 2px 0;
            font-size: 8.5px;
            text-align: left;
            font-weight: bold;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .info-table td {
            padding: 5px 8px;
            vertical-align: top;
            font-size: 9px;
        }

        .box-title {
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 1px solid #cccccc;
            margin-bottom: 4px;
            padding-bottom: 2px;
            text-transform: uppercase;
            font-size: 9.5px;
        }

        .resumen-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        .resumen-table td {
            padding: 5px 8px;
            border: 1px solid #dee2e6;
            font-size: 9px;
        }

        .resumen-label {
            font-weight: bold;
            color: #495057;
        }

        .resumen-val {
            text-align: right;
            font-weight: bold;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            border: 2px solid #333;
        }

        .data-table th {
            background-color: #343a40;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 5px;
            border: 1px solid #343a40;
            text-align: left;
        }

        .data-table td {
            padding: 5px;
            border: 1px solid #dee2e6;
            font-size: 9px;
            vertical-align: top;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-success {
            color: #28a745 !important;
        }

        .text-danger {
            color: #dc3545 !important;
        }

        .text-warning {
            color: #b78103 !important;
        }

        .font-bold {
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-credito {
            background-color: #fce8e6;
            color: #c5221f;
        }

        .badge-abono {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-interes {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-anticipo {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .nota-box {
            background-color: #fffde7;
            border-left: 2px solid #fbc02d;
            padding: 3px 6px;
            margin-top: 3px;
            font-size: 8.5px;
            color: #444;
            border-radius: 2px;
        }

        .prod-list {
            margin: 2px 0 0 0;
            padding-left: 12px;
            color: #555;
            font-size: 8.5px;
        }

        .section-heading {
            font-size: 10px;
            font-weight: bold;
            color: #2c3e50;
            margin-top: 8px;
            margin-bottom: 4px;
            border-left: 3px solid #2c3e50;
            padding-left: 5px;
            text-transform: uppercase;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 18px;
            text-align: center;
            font-size: 8px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            padding-top: 3px;
        }
    </style>
</head>

<body>

    {{-- Encabezado de la empresa --}}
    <table class="header-table">
        <tr>
            <td style="width: 58%; vertical-align: top;">
                <h1 class="company-title">YERMOTORS REPUESTOS C.A.</h1>

                <div class="company-subtitle">
                    Venta de Repuestos y Accesorios
                </div>

                <div class="company-info-text">
                    <strong>RIF:</strong>
                    {{ $empresa->rif ?? 'J-50186803-4' }}<br>

                    <strong>Dirección:</strong>
                    {{ $empresa->direccion ?? 'Calle Páez entre Bolívar y Guzmán Blanco, Casa S/N, Sector Centro, San José de Guaribe, Estado Guárico.' }}<br>

                    <strong>Teléfono:</strong>
                    {{ $empresa->telefono ?? '0414-0863107' }}
                </div>
            </td>

            <td style="width: 42%; vertical-align: top;">
                <div class="box-header-right">
                    <h4>HISTORIAL POR FECHA</h4>

                    <p>
                        <strong>DESDE:</strong>
                        {{ \Carbon\Carbon::parse($fechaInicio)->format('d / m / Y') }}
                    </p>

                    <p>
                        <strong>HASTA:</strong>
                        {{ \Carbon\Carbon::parse($fechaFin)->format('d / m / Y') }}
                    </p>

                    <p>
                        <strong>EMISIÓN:</strong>
                        {{ \Carbon\Carbon::now()->format('d/m/Y h:i A') }}
                    </p>

                    <p>
                        <strong>GENERADO POR:</strong>
                        {{ auth()->user()->name ?? 'Sistema' }}
                    </p>
                </div>
            </td>
        </tr>
    </table>

    {{-- Datos del cliente y detalles de la consulta --}}
    <table class="info-table">
        <tr>
            <td style="width: 60%; background-color: #f1f3f5; border-radius: 3px;">
                <div class="box-title">Información del Cliente</div>

                <strong>Nombre / Razón Social:</strong>
                {{ $cliente->nombre }}<br>

                <strong>Cédula / RIF:</strong>
                {{ $cliente->identificacion }}<br>

                <strong>Teléfono:</strong>
                {{ $cliente->telefono ?? 'N/A' }}<br>

                <strong>Dirección:</strong>
                {{ $cliente->direccion ?? 'No especificada' }}
            </td>

            <td style="width: 40%; background-color: #f1f3f5; border-radius: 3px;">
                <div class="box-title">Detalles de la Consulta</div>

                <strong>Rango Solicitado:</strong>
                {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }}
                al
                {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}<br>

                <strong>Estado Cliente:</strong>
                <span class="text-success font-bold">Activo</span>
            </td>
        </tr>
    </table>

    {{-- Resumen del periodo --}}
    <div class="section-heading">RESUMEN DEL PERIODO CONSULTADO</div>

    <table class="resumen-table">
        <tr>
            <td class="resumen-label">Créditos Otorgados (Periodo):</td>
            <td class="resumen-val">
                ${{ number_format($resumenPeriodo['monto_inicial'], 2) }}
            </td>

            <td class="resumen-label">Total Abonado (Periodo):</td>
            <td class="resumen-val text-success">
                - ${{ number_format($resumenPeriodo['total_abonado'], 2) }}
            </td>
        </tr>

        <tr>
            <td class="resumen-label">Intereses / Indexaciones:</td>
            <td class="resumen-val text-warning">
                + ${{ number_format($resumenPeriodo['total_intereses'], 2) }}
            </td>

            <td class="resumen-label" style="background-color: #ffe3e3;">
                Saldo Generado Periodo:
            </td>

            <td class="resumen-val text-danger"
                style="background-color: #ffe3e3; font-size: 11px;">
                ${{ number_format($resumenPeriodo['saldo_pendiente'], 2) }}
            </td>
        </tr>
    </table>

    {{-- Detalle sencillo de movimientos --}}
    <div class="section-heading">HISTORIAL DE MOVIMIENTOS DEL PERIODO</div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 18%;">FECHA / HORA</th>
                <th style="width: 15%;">TIPO</th>
                <th style="width: 42%;">DESCRIPCIÓN Y PRODUCTOS</th>
                <th style="width: 25%; text-align: right;">MONTO ($)</th>
            </tr>
        </thead>

        <tbody>
            @forelse($creditos as $credito)
                @php
                    $esAnticipo = (
                        $credito->estado === 'anticipo'
                        || $credito->saldo_pendiente < 0
                    );

                    $venta = $credito->venta;

                    $esCreditoDirecto = (
                        !$venta
                        || ($venta->detalles && $venta->detalles->isEmpty())
                    );

                    $abonosValidos = $credito->abonos ?? collect();

                    $totalAbonoCredito = $abonosValidos->sum(function ($a) {
                        $estado = optional($a->abono)->estado
                            ?? $a->estado
                            ?? 'Realizado';

                        if (strtolower($estado) !== 'realizado') {
                            return 0;
                        }

                        return $a->monto_aplicado_usd
                            ?? optional($a->pivot)->monto_aplicado_usd
                            ?? $a->monto
                            ?? 0;
                    });

                    $interesesAplicados = $credito->intereses
                        ? $credito->intereses->where('estado', 'aplicado')
                        : collect();

                    $fechaCredito = $credito->created_at;
                @endphp

                {{-- Movimiento del crédito o anticipo --}}
                <tr>
                    <td style="font-size: 8.5px; color: #555;">
                        <strong>{{ $fechaCredito->format('d/m/Y') }}</strong><br>
                        {{ $fechaCredito->format('h:i A') }}
                    </td>

                    <td>
                        @if($esAnticipo)
                            <span class="badge badge-anticipo">SALDO A FAVOR</span>
                        @else
                            <span class="badge badge-credito">CRÉDITO</span>
                        @endif
                    </td>

                    <td>
                        <strong>
                            @if($esAnticipo)
                                Anticipo / saldo a favor ANT-{{ $credito->id }}
                            @else
                                {{ $venta->codigo_factura ?? 'Crédito CRD-' . $credito->id }}
                            @endif
                        </strong>

                        @if($esAnticipo)
                            <div class="nota-box">
                                Saldo registrado a favor del cliente.
                            </div>
                        @elseif(!$esCreditoDirecto && $venta && $venta->detalles)
                            <ul class="prod-list">
                                @foreach($venta->detalles as $detalle)
                                    <li>
                                        {{ $detalle->insumo->producto ?? 'Producto N/A' }}
                                        x{{ $detalle->cantidad }}
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="nota-box">
                                Crédito directo / préstamo registrado en cuenta.
                            </div>
                        @endif

                        @if(!$esAnticipo && !empty($credito->observacion))
                            <div class="nota-box">
                                <strong>Nota / Obs:</strong>
                                {{ $credito->observacion }}
                            </div>
                        @elseif(!$esAnticipo && !empty($venta->observacion))
                            <div class="nota-box">
                                <strong>Nota / Obs:</strong>
                                {{ $venta->observacion }}
                            </div>
                        @endif
                    </td>

                    <td class="text-right">
                        @if($esAnticipo)
                            <span style="color: #1a73e8; font-weight: bold;">
                                + ${{ number_format(abs($credito->saldo_pendiente), 2) }}
                            </span>
                        @else
                            <span class="text-danger">
                                + ${{ number_format($credito->monto_inicial, 2) }}
                            </span>
                        @endif
                    </td>
                </tr>

                @if(!$esAnticipo)
                    {{-- Indexaciones aplicadas --}}
                    @foreach($interesesAplicados as $interes)
                        <tr>
                            <td style="font-size: 8.5px; color: #555;">
                                @if($interes->aplicado_en)
                                    <strong>
                                        {{ \Carbon\Carbon::parse($interes->aplicado_en)->format('d/m/Y') }}
                                    </strong><br>
                                    {{ \Carbon\Carbon::parse($interes->aplicado_en)->format('h:i A') }}
                                @else
                                    —
                                @endif
                            </td>

                            <td>
                                <span class="badge badge-interes">AJUSTE</span>
                            </td>

                            <td>
                                <strong>
                                    Indexación por inflación
                                    ({{ $interes->porcentaje }}%)
                                </strong>

                                @if(!empty($interes->observacion))
                                    <div class="nota-box">
                                        <strong>Nota / Obs:</strong>
                                        {{ $interes->observacion }}
                                    </div>
                                @endif
                            </td>

                            <td class="text-right">
                                <span class="text-danger">
                                    + ${{ number_format($interes->monto_interes, 2) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach

                    {{-- Abonos realizados --}}
                    @php
                        $abonosRealizados = $abonosValidos->filter(function ($a) {
                            $estado = optional($a->abono)->estado
                                ?? $a->estado
                                ?? 'Realizado';

                            return strtolower($estado) === 'realizado';
                        });

                        $ultimoAbono = $abonosRealizados->last();

                        $abonoPadre = $ultimoAbono
                            ? ($ultimoAbono->abono ?? $ultimoAbono)
                            : null;

                        $fechaAbono = $abonoPadre->created_at
                            ?? $ultimoAbono->created_at
                            ?? null;
                    @endphp

                    @if($totalAbonoCredito > 0)
                        <tr>
                            <td style="font-size: 8.5px; color: #555;">
                                @if($fechaAbono)
                                    <strong>
                                        {{ \Carbon\Carbon::parse($fechaAbono)->format('d/m/Y') }}
                                    </strong><br>
                                    {{ \Carbon\Carbon::parse($fechaAbono)->format('h:i A') }}
                                @else
                                    —
                                @endif
                            </td>

                            <td>
                                <span class="badge badge-abono">ABONO / PAGO</span>
                            </td>

                            <td>
                                <strong>Total abonado a este crédito</strong>
                                <div class="nota-box">
                                    Abono realizado a la cuenta.
                                </div>
                            </td>

                            <td class="text-right">
                                <span class="text-success">
                                    - ${{ number_format($totalAbonoCredito, 2) }}
                                </span>
                            </td>
                        </tr>
                    @endif
                @endif
            @empty
                <tr>
                    <td colspan="4" class="text-center"
                        style="padding: 15px; color: #777;">
                        No se encontraron movimientos para el cliente en el rango seleccionado.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pie de página --}}
    <div class="footer">
        Documento generado automáticamente por el sistema de gestión.
        Historial de movimientos crediticios.
    </div>

</body>
</html>