<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Notificación de Cobranza - {{ $cliente->nombre }}</title>
    <style>
        @page {
            margin: 24px 30px;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10.5px;
            color: #333333;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            border-bottom: 2px solid #8b0000;
            padding-bottom: 8px;
        }
        .company-title {
            color: #8b0000;
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
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
            line-height: 1.3;
        }
        .box-aviso {
            border: 1.5px solid #8b0000;
            border-radius: 5px;
            padding: 7px 10px;
            text-align: left;
            background-color: #fff;
        }
        .box-aviso h4 {
            color: #8b0000;
            font-size: 10px;
            font-weight: bold;
            margin: 0 0 4px 0;
            text-transform: uppercase;
            border-bottom: 1px solid #8b0000;
            padding-bottom: 2px;
            text-align: center;
        }
        .box-aviso p {
            margin: 2px 0;
            font-size: 8.5px;
        }
        .bloque-datos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .bloque-datos td {
            background-color: #f4f6f8;
            border: 1px solid #dee2e6;
            padding: 8px 10px;
            vertical-align: top;
            font-size: 9.5px;
        }
        .titulo-seccion {
            font-weight: bold;
            font-size: 9.5px;
            text-transform: uppercase;
            color: #2c3e50;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }
        .resumen-deuda {
            width: 100%;
            border-collapse: collapse;
            margin: 14px 0 18px 0;
            border: 1.5px solid #dc3545;
        }
        .resumen-deuda td {
            padding: 10px 12px;
            border: 1px solid #f5c2c7;
            vertical-align: middle;
        }
        .label-deuda {
            background-color: #fff5f5;
            font-weight: bold;
            color: #842029;
            font-size: 10px;
            width: 45%;
        }
        .valor-deuda {
            background-color: #ffe8ea;
            font-weight: bold;
            color: #b02a37;
            font-size: 15px;
            text-align: right;
            width: 55%;
        }
        .mensaje-cuerpo {
            font-size: 10px;
            color: #222;
            margin-bottom: 18px;
        }
        .pie-firma {
            margin-top: 24px;
            font-size: 9px;
            color: #444;
            border-top: 1px solid #dee2e6;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    {{-- Datos del Negocio --}}
    <table class="header-table">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <h1 class="company-title">YERMOTORS REPUESTOS C.A.</h1>
                <div class="company-subtitle">Venta de Repuestos y Accesorios</div>
                <div class="company-info-text">
                    <strong>RIF:</strong> {{ $empresa->rif ?? 'J-50186803-4' }}<br>
                    <strong>Dirección:</strong> {{ $empresa->direccion ?? 'San José de Guaribe, Guárico.' }}<br>
                    <strong>Teléfono:</strong> {{ $empresa->telefono ?? '0414-0863107' }}
                </div>
            </td>
            <td style="width: 40%; vertical-align: top;">
                <div class="box-aviso">
                    <h4>AVISO DE COBRANZA</h4>
                    <p><strong>Fecha de emisión:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>
                    <p><strong>Hora:</strong> {{ \Carbon\Carbon::now()->format('h:i A') }}</p>
                    <p><strong>Local:</strong> {{ $local->nombre ?? 'No especificado' }}</p>
                    <p><strong>Encargado del local:</strong> {{ $encargado->name ?? 'Por asignar' }}</p>
                </div>
            </td>
        </tr>
    </table>

    {{-- Datos del Cliente --}}
    <table class="bloque-datos">
        <tr>
            <td style="width: 55%;">
                <div class="titulo-seccion">Datos del Cliente</div>
                <strong>Nombre:</strong> {{ $cliente->nombre }}<br>
                @if(!empty($cliente->alias))
                    <strong>Alias:</strong> {{ $cliente->alias }}<br>
                @endif
                <strong>Cédula / RIF:</strong> {{ $cliente->identificacion ?? 'N/A' }}<br>
                <strong>Teléfono:</strong> {{ $cliente->telefono ?? 'N/A' }}
            </td>
            <td style="width: 45%;">
                <div class="titulo-seccion">Referencia de Antigüedad</div>
                <strong>Saldo pendiente desde:</strong> {{ $fechaDesde->format('d/m/Y') }}<br>
                <strong>Días transcurridos:</strong> {{ $diasAntiguedad }} días<br>
                <strong>Sucursal / Local:</strong> {{ $local->nombre ?? 'No especificado' }}<br>
                <strong>Encargado responsable:</strong> {{ $encargado->name ?? 'Por asignar' }}
            </td>
        </tr>
    </table>

    <div class="mensaje-cuerpo">
        Estimado(a) <strong>{{ $cliente->nombre }}</strong>, por medio de la presente le recordamos amablemente que su cuenta mantiene un saldo pendiente registrado desde el <strong>{{ $fechaDesde->format('d/m/Y') }}</strong> correspondiente a sus operaciones en <strong>{{ $local->nombre ?? 'nuestro establecimiento' }}</strong>. Le agradecemos comunicarse o pasar por taquilla para coordinar su abono o cancelación.
    </div>

    {{-- Monto Total Pendiente --}}
    <table class="resumen-deuda">
        <tr>
            <td class="label-deuda">MONTO TOTAL PENDIENTE POR COBRAR</td>
            <td class="valor-deuda">${{ number_format($montoTotalPendiente, 2) }}</td>
        </tr>
    </table>

    <div class="pie-firma">
        <strong>Atentamente,</strong><br>
        <strong>{{ $encargado->name ?? 'Administración / Encargado' }}</strong> — Encargado de {{ $local->nombre ?? 'Local' }}<br>
        <span style="color: #6c757d; font-size: 8px;">Documento informativo de recordatorio de crédito. Para el desglose detallado de movimientos, solicite su Estado de Cuenta.</span>
    </div>

</body>
</html>