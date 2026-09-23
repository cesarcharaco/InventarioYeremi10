<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de Cuenta - {{ $cliente->nombre }}</title>
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
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-success { color: #28a745 !important; }
        .text-danger { color: #dc3545 !important; }
        .text-warning { color: #b78103 !important; }
        .font-bold { font-weight: bold; }
        
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-credito { background-color: #fce8e6; color: #c5221f; }
        .badge-abono { background-color: #d4edda; color: #155724; }
        .badge-interes { background-color: #fff3cd; color: #856404; }
        .badge-anticipo { background-color: #d1ecf1; color: #0c5460; }

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
            bottom: 0px;
            left: 0px;
            right: 0px;
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

    {{-- Encabezado Formal --}}
    <table class="header-table">
        <tr>
            <td style="width: 58%; vertical-align: top;">
                <h1 class="company-title">YERMOTORS REPUESTOS C.A.</h1>
                <div class="company-subtitle">Venta de Repuestos y Accesorios</div>
                <div class="company-info-text">
                    <strong>RIF:</strong> {{ $empresa->rif ?? 'J-50186803-4' }}<br>
                    <strong>Dirección:</strong> {{ $empresa->direccion ?? 'San José de Guaribe, Guárico.' }}<br>
                    <strong>Teléfono:</strong> {{ $empresa->telefono ?? '0414-0863107' }}
                </div>
            </td>
            <td style="width: 42%; vertical-align: top;">
                <div class="box-header-right">
                    <h4>ESTADO DE CUENTA ACTUAL</h4>
                    <p><strong>FECHA:</strong> {{ \Carbon\Carbon::now()->format('d / m / Y') }}</p>
                    <p><strong>HORA:</strong> {{ \Carbon\Carbon::now()->format('h:i A') }}</p>
                    <p><strong>GENERADO POR:</strong> {{ auth()->user()->name ?? 'Sistema' }}</p>
                </div>
            </td>
        </tr>
    </table>

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
                <div class="box-title">Detalles de Control</div>
                <strong>Fecha Emisión:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y h:i A') }}<br>
                <strong>Estado Cliente:</strong> <span class="text-success font-bold">Activo</span>
            </td>
        </tr>
    </table>
    {{-- Resumen Financiero --}}
    <div class="section-heading">RESUMEN DE CUENTA</div>
    <table class="resumen-table">
        <tr>
            <td class="resumen-label">Total en Créditos Sacados:</td>
            <td class="resumen-val">${{ number_format($resumen['monto_inicial'], 2) }}</td>
            <td class="resumen-label">Total Abonos Realizados:</td>
            <td class="resumen-val text-success">- ${{ number_format($resumen['total_abonado'], 2) }}</td>
        </tr>
        @if($resumen['total_intereses'] > 0)
        <tr>
            <td class="resumen-label">Ajustes por Inflación:</td>
            <td class="resumen-val text-warning">+ ${{ number_format($resumen['total_intereses'], 2) }}</td>
            <td colspan="2"></td>
        </tr>
        @endif
        <tr style="background-color: #f8f9fa;">
            @if($resumen['saldo_a_favor'] > 0)
                <td class="resumen-label" style="color: #1a73e8;">Saldo a Favor Disponible:</td>
                <td class="resumen-val" style="color: #1a73e8;">+ ${{ number_format($resumen['saldo_a_favor'], 2) }}</td>
            @endif
            <td class="resumen-label" style="font-size: 11px; background-color: #ffebe9; color: #c5221f;">TOTAL DEUDA PENDIENTE:</td>
            <td class="resumen-val text-danger" style="font-size: 11px; background-color: #ffebe9;" @if($resumen['saldo_a_favor'] == 0) colspan="3" @endif>
                ${{ number_format($resumen['neto_a_pagar'], 2) }}
            </td>
        </tr>
    </table>

    {{-- Historial de Movimientos Ordenados por Fecha --}}
    <div class="section-heading">HISTORIAL DE MOVIMIENTOS (DEL MÁS RECIENTE AL MÁS ANTIGUO)</div>
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
            @forelse($movimientos as $mov)
                <tr>
                    {{-- Fecha y Hora --}}
                    <td style="font-size: 8.5px; color: #555;">
                        <strong>{{ $mov['fecha']->format('d/m/Y') }}</strong><br>
                        {{ $mov['fecha']->format('h:i A') }}
                    </td>

                    {{-- Etiqueta de Tipo --}}
                    <td>
                        @if($mov['tipo'] === 'CREDITO')
                            <span class="badge badge-credito">CRÉDITO</span>
                        @elseif($mov['tipo'] === 'ABONO')
                            <span class="badge badge-abono">ABONO / PAGO</span>
                        @elseif($mov['tipo'] === 'INDEXACION')
                            <span class="badge badge-interes">AJUSTE</span>
                        @else
                            <span class="badge badge-anticipo">SALDO A FAVOR</span>
                        @endif
                    </td>

                    {{-- Descripción, Productos y Notas --}}
                    <td>
                        <strong>{{ $mov['titulo'] }}</strong>
                        
                        {{-- Lista de Productos si la compra tiene detalle --}}
                        @if(!empty($mov['detalles']))
                            <ul class="prod-list">
                                @foreach($mov['detalles'] as $prod)
                                    <li>{{ $prod }}</li>
                                @endforeach
                            </ul>
                        @endif

                        {{-- Destacar Observaciones o Notas --}}
                        @if(!empty($mov['observacion']))
                            <div class="nota-box">
                                <strong>Nota / Obs:</strong> {{ $mov['observacion'] }}
                            </div>
                        @endif
                    </td>

                    {{-- Monto (Verde para Abonos, Rojo para Créditos) --}}
                    <td class="text-right">
                        @if($mov['tipo'] === 'ABONO')
                            <span class="text-success" style="font-size: 10px;">- ${{ number_format($mov['monto'], 2) }}</span>
                        @elseif($mov['tipo'] === 'ANTICIPO')
                            <span style="color: #1a73e8; font-weight: bold; font-size: 10px;">+ ${{ number_format($mov['monto'], 2) }}</span>
                        @else
                            <span class="text-danger" style="font-size: 10px;">+ ${{ number_format($mov['monto'], 2) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center" style="padding: 15px; color: #777;">
                        El cliente no registra deudas ni abonos pendientes actualmente.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Documento emitido para control de cuenta de cliente. Sin tachaduras ni enmiendas.
    </div>

</body>
</html>