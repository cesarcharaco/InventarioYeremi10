<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Presupuesto - {{ $cliente->nombre }}</title>
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
        
        /* ==========================================
           ESTILOS DE ENCABEZADO FORMAL
           ========================================== */
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

        /* Bloques de Información */
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

        /* Tabla de Artículos (Estilo Productos) */
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
            text-align: center;
        }
        .data-table td {
            padding: 5px;
            border: 1px solid #dee2e6;
            font-size: 9px;
        }

        /* Clases de apoyo */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-success { color: #28a745 !important; }
        .text-warning { color: #b78103 !important; }
        .text-muted { color: #6c757d !important; }
        .font-bold { font-weight: bold; }

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

        /* Pie de página */
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
                    <strong>RIF:</strong> J-50186803-4<br>
                    <strong>Dirección:</strong> Calle Páez entre Bolívar y Guzmán Blanco, Casa S/N, Sector Centro, San José de Guaribe, Estado Guárico.<br>
                    <strong>Teléfono:</strong> 0414-0863107
                </div>
            </td>
            
            <td style="width: 42%; vertical-align: top;">
                <div class="box-header-right">
                    <h4>PRESUPUESTO DE VENTA</h4>
                    <p><strong>FECHA EMISIÓN:</strong> {{ $fecha_emision->format('d / m / Y') }}</p>
                    <p><strong>VALIDEZ HASTA:</strong> {{ $validez->format('d / m / Y') }}</p>
                    <p><strong>ATENDIDO POR:</strong> {{ $generado_por }}</p>
                </div>
            </td>
        </tr>
    </table>

    {{-- Datos del Cliente --}}
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
                <div class="box-title">Condiciones Comerciales</div>
                <strong>Moneda:</strong> Dólares Americanos (USD)<br>
                <strong>Tipo de Documento:</strong> <span class="text-warning font-bold">Presupuesto Informativo</span>
            </td>
        </tr>
    </table>

    {{-- Detalle de Artículos Seleccionados --}}
    <div class="section-heading">DETALLE DE PRODUCTOS Y REPUESTOS</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 7%;">ÍTEM</th>
                <th style="width: 48%;">DESCRIPCIÓN DEL REPUESTO / PRODUCTO</th>
                <th style="width: 12%;" class="text-center">CANT.</th>
                <th style="width: 15%;" class="text-right">PRECIO UNIT. ($)</th>
                <th style="width: 18%;" class="text-right">SUBTOTAL ($)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($articulos as $index => $item)
            <tr>
                <td class="text-center font-bold">{{ $index + 1 }}</td>
                <td>
                    {{ $item['nombre'] }}
                    @if(!empty($item['serial']))
                        <br><span style="color: #666; font-size: 8px;">(S/N: {{ $item['serial'] }})</span>
                    @endif
                </td>
                <td class="text-center">{{ $item['cantidad'] }}</td>
                <td class="text-right">${{ number_format($item['precio_unitario'], 2) }}</td>
                <td class="text-right font-bold">${{ number_format($item['subtotal'], 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center text-muted" style="padding: 15px;">
                    No hay artículos seleccionados en este presupuesto.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Tabla de Observaciones y Totales Finales --}}
    <table style="width: 100%; border-collapse: collapse; margin-top: 5px;">
        <tr>
            <td style="width: 55%; vertical-align: top; padding-right: 15px;">
                @if(!empty($observacion))
                <div class="box-title" style="margin-bottom: 4px;">Observaciones</div>
                <div style="background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 6px 8px; border-radius: 3px; font-size: 9px; min-height: 35px;">
                    {{ $observacion }}
                </div>
                @endif
                <div style="margin-top: 8px; font-size: 8px; color: #555; line-height: 1.2;">
                    * Este documento es una cotización informativa y no compromete la existencia física del inventario tras vencer su fecha de validez.<br>
                    * Precios sujetos a modificación sin previo aviso.
                </div>
            </td>
            
            <td style="width: 45%; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse; background-color: #f8f9fa; border: 1px solid #dee2e6;">
                    <tr>
                        <td style="padding: 5px 8px; font-weight: bold; border-bottom: 1px solid #dee2e6;">Subtotal General:</td>
                        <td style="padding: 5px 8px; text-align: right; font-weight: bold; border-bottom: 1px solid #dee2e6;">${{ number_format($subtotal_general, 2) }}</td>
                    </tr>
                    
                    @if($porcentaje_descuento > 0)
                    <tr>
                        <td style="padding: 5px 8px; font-weight: bold; color: #28a745; border-bottom: 1px solid #dee2e6;">Descuento ({{ $porcentaje_descuento }}%):</td>
                        <td style="padding: 5px 8px; text-align: right; font-weight: bold; color: #28a745; border-bottom: 1px solid #dee2e6;">-${{ number_format($monto_descuento, 2) }}</td>
                    </tr>
                    @endif
                    
                    <tr style="background-color: #343a40; color: #ffffff;">
                        <td style="padding: 7px 8px; font-weight: bold; font-size: 10px; text-transform: uppercase;">Total Neto:</td>
                        <td style="padding: 7px 8px; text-align: right; font-weight: bold; font-size: 11px;">${{ number_format($total_neto, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Pie de Página Fijo --}}
    <div class="footer">
        Yermotors Repuestos C.A. - Documento generado automáticamente por el sistema de gestión.
    </div>

</body>
</html>