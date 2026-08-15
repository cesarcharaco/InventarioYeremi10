<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de Cuenta - {{ $cliente->nombre }}</title>
    <style>
        @page {
            margin: 25px 30px;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333333;
            line-height: 1.3;
        }
        
        /* Encabezado con Logo Vertical */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .logo-img {
            max-height: 75px; /* Altura ajustada para logo retrato/vertical */
            width: auto;
            display: block;
        }
        .company-info {
            text-align: right;
            font-size: 10px;
        }
        .company-name {
            font-size: 15px;
            font-weight: bold;
            color: #1a1a1a;
            text-transform: uppercase;
        }

        /* Título del Documento */
        .doc-title {
            text-align: center;
            background-color: #2c3e50;
            color: #ffffff;
            padding: 6px 0;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            border-radius: 3px;
        }

        /* Bloques de Información */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .info-table td {
            padding: 4px 6px;
            vertical-align: top;
        }
        .box-title {
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 1px solid #cccccc;
            margin-bottom: 5px;
            padding-bottom: 2px;
            text-transform: uppercase;
            font-size: 10px;
        }

        /* Tabla de Resumen Financiero */
        .resumen-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .resumen-table td {
            padding: 6px 10px;
            border: 1px solid #dee2e6;
        }
        .resumen-label {
            font-weight: bold;
            color: #495057;
        }
        .resumen-val {
            text-align: right;
            font-weight: bold;
        }

        /* Tablas de Detalles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .data-table th {
            background-color: #343a40;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px;
            border: 1px solid #343a40;
            text-align: left;
        }
        .data-table td {
            padding: 5px;
            border: 1px solid #e9ecef;
            font-size: 9.5px;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        /* Clases Útiles */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        .text-warning { color: #d39e00; }
        .font-bold { font-weight: bold; }
        .section-heading {
            font-size: 11px;
            font-weight: bold;
            color: #2c3e50;
            margin-top: 10px;
            margin-bottom: 5px;
            border-left: 3px solid #2c3e50;
            padding-left: 5px;
        }

        /* Pie de página */
        .footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            height: 20px;
            text-align: center;
            font-size: 8.5px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    {{-- Encabezado Membrete --}}
    <table class="header-table">
        <tr>
            <td style="width: 30%;">
                {{-- Carga dinámica de la imagen del Logo --}}
                @if(!empty($empresa->logo) && file_exists(public_path('storage/' . $empresa->logo)))
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('storage/' . $empresa->logo))) }}" class="logo-img">
                @elseif(file_exists(public_path('images/logo.png')))
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo.png'))) }}" class="logo-img">
                @else
                    <div style="font-size: 16px; font-weight: bold; color: #2c3e50;">{{ $empresa->nombre ?? 'MI EMPRESA' }}</div>
                @endif
            </td>
            <td style="width: 70%;" class="company-info">
                <div class="company-name">{{ $empresa->nombre ?? 'REPUESTOS Y SERVICIOS' }}</div>
                <div>RIF / Cédula: {{ $empresa->rif ?? $empresa->identificacion ?? 'N/A' }}</div>
                <div>Teléfono: {{ $empresa->telefono ?? 'N/A' }}</div>
                <div>Dirección: {{ $empresa->direccion ?? 'N/A' }}</div>
            </td>
        </tr>
    </table>

    {{-- Título --}}
    <div class="doc-title">Estado de Cuenta Consolidado</div>

    {{-- Datos del Cliente y Emisión --}}
    <table class="info-table">
        <tr>
            <td style="width: 60%; background-color: #f1f3f5; border-radius: 3px;">
                <div class="box-title">Información del Cliente</div>
                <strong>Nombre / Razón Social:</strong> {{ $cliente->nombre }}<br>
                <strong>Cédula / RIF:</strong> {{ $cliente->identificacion }}<br>
                <strong>Teléfono:</strong> {{ $cliente->telefono ?? 'N/A' }}<br>
                <strong>Dirección:</strong> {{ $cliente->direccion ?? 'No especificada' }}
            </td>
            <td style="width: 40%; background-color: #f1f3f5; border-radius: 3px;">
                <div class="box-title">Detalles de Emisión</div>
                <strong>Fecha Emisión:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y h:i A') }}<br>
                <strong>Generado por:</strong> {{ auth()->user()->name }}<br>
                <strong>Estado Cliente:</strong> <span class="text-success font-bold">Activo</span>
            </td>
        </tr>
    </table>

    {{-- Resumen Financiero --}}
    <div class="section-heading">RESUMEN GENERAL</div>
    <table class="resumen-table">
        <tr>
            <td class="resumen-label">Monto Original Inicial:</td>
            <td class="resumen-val">${{ number_format($resumen['monto_inicial'], 2) }}</td>
            <td class="resumen-label">Total Abonado:</td>
            <td class="resumen-val text-success">- ${{ number_format($resumen['total_abonado'], 2) }}</td>
        </tr>
        <tr>
            <td class="resumen-label">Intereses / Indexaciones:</td>
            <td class="resumen-val text-warning">+ ${{ number_format($resumen['total_intereses'], 2) }}</td>
            <td class="resumen-label" style="background-color: #ffe3e3;">Saldo Restante Pendiente:</td>
            <td class="resumen-val text-danger" style="background-color: #ffe3e3; font-size: 13px;">
                ${{ number_format($resumen['saldo_pendiente'], 2) }}
            </td>
        </tr>
        @if($resumen['saldo_a_favor'] > 0)
        <tr>
            <td colspan="2" class="resumen-label" style="background-color: #d1ecf1;">Saldo a Favor Disponible:</td>
            <td colspan="2" class="resumen-val" style="background-color: #d1ecf1; color: #0c5460;">
                ${{ number_format($resumen['saldo_a_favor'], 2) }}
            </td>
        </tr>
        @endif
    </table>

    {{-- Tabla de Abonos --}}
    <div class="section-heading">HISTORIAL DE ABONOS Y PAGOS</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">Fecha / Hora</th>
                <th style="width: 15%;">Cajero</th>
                <th style="width: 10%;"># Crédito</th>
                <th style="width: 15%;">Monto ($)</th>
                <th style="width: 30%;">Detalles / Observación</th>
                <th style="width: 15%;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($historialAbonos as $abono)
                <tr style="{{ $abono->estado === 'Anulado' ? 'text-decoration: line-through; color: #888;' : '' }}">
                    <td class="text-center">{{ $abono->created_at->format('d/m/Y h:i A') }}</td>
                    <td>{{ $abono->usuario->name ?? 'N/A' }}</td>
                    <td class="text-center">#{{ $abono->id_credito }}</td>
                    <td class="text-right font-bold text-success">${{ number_format($abono->monto_pagado_usd, 2) }}</td>
                    <td>{{ $abono->detalles ?? 'Abono realizado' }}</td>
                    <td class="text-center font-bold {{ $abono->estado === 'Realizado' ? 'text-success' : 'text-danger' }}">
                        {{ $abono->estado }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">No hay registros de abonos realizados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Tabla de Indexación (Intereses) --}}
    @if($historialIntereses->count() > 0)
        <div class="section-heading">HISTORIAL DE INDEXACIÓN (INTERESES)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 18%;">Fecha</th>
                    <th style="width: 12%;"># Crédito</th>
                    <th style="width: 20%;">Administrador</th>
                    <th style="width: 15%;">Porcentaje</th>
                    <th style="width: 18%;">Monto Indexado</th>
                    <th style="width: 17%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($historialIntereses as $interes)
                    <tr style="{{ $interes->estado === 'anulado' ? 'text-decoration: line-through; color: #888;' : '' }}">
                        <td class="text-center">{{ $interes->aplicado_en->format('d/m/Y h:i A') }}</td>
                        <td class="text-center">#{{ $interes->id_credito }}</td>
                        <td>{{ $interes->administrador->name ?? 'N/A' }}</td>
                        <td class="text-center font-bold text-warning">{{ $interes->porcentaje }}%</td>
                        <td class="text-right font-bold text-danger">${{ number_format($interes->monto_interes, 2) }}</td>
                        <td class="text-center font-bold {{ $interes->estado === 'aplicado' ? 'text-warning' : 'text-danger' }}">
                            {{ ucfirst($interes->estado) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Pie de Página Fijo --}}
    <div class="footer">
        Documento generado automáticamente por el sistema de gestión. Página 1 de 1
    </div>

</body>
</html>