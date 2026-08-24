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

        /* Tabla de Resumen Financiero */
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

        /* Tabla Unificada de Registros (Estilo Productos) */
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

        /* Clases de apoyo y colores PDF */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-success { color: #28a745 !important; }
        .text-danger { color: #dc3545 !important; }
        .text-warning { color: #b78103 !important; }
        .text-info { color: #17a2b8 !important; }
        .text-muted { color: #6c757d !important; }
        .font-bold { font-weight: bold; }
        .pl-4 { padding-left: 15px !important; }
        
        /* Fondos de filas para PDF */
        .bg-light-gray { background-color: #e9ecef; }
        .bg-anticipo { background-color: #d1ecf1; }
        .bg-abono { background-color: #d4edda; }
        .bg-interes { background-color: #fff3cd; }
        .bg-subtotal { background-color: #f8f9fa; }
        .bg-footer { background-color: #343a40; color: #ffffff; }

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
                    <h4>ESTADO DE CUENTA</h4>
                    <p><strong>FECHA:</strong> {{ \Carbon\Carbon::now()->format('d / m / Y') }}</p>
                    <p><strong>HORA:</strong> {{ \Carbon\Carbon::now()->format('h:i A') }}</p>
                    <p><strong>GENERADO POR:</strong> {{ auth()->user()->name ?? 'Sistema' }}</p>
                </div>
            </td>
        </tr>
    </table>

    {{-- Datos del Cliente y Emisión[cite: 5] --}}
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

    {{-- Resumen Financiero[cite: 5] --}}
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
            <td class="resumen-label" style="background-color: #ffe3e3;">Saldo Deuda Pendiente:</td>
            <td class="resumen-val text-danger" style="background-color: #ffe3e3; font-size: 11px;">
                ${{ number_format($resumen['saldo_pendiente'], 2) }}
            </td>
        </tr>
        
        @if($resumen['saldo_a_favor'] > 0)
        <tr>
            <td class="resumen-label" style="background-color: #d1ecf1; color: #0c5460;">Saldo a Favor Disponible:</td>
            <td class="resumen-val" style="background-color: #d1ecf1; color: #0c5460;">
                + ${{ number_format($resumen['saldo_a_favor'], 2) }}
            </td>
            <td class="resumen-label" style="background-color: #d4edda; color: #155724; font-weight: bold;">TOTAL NETO A PAGAR:</td>
            <td class="resumen-val" style="background-color: #d4edda; color: #155724; font-size: 11px; font-weight: bold;">
                ${{ number_format($resumen['neto_a_pagar'], 2) }}
            </td>
        </tr>
        @endif
    </table>

    {{-- SECCIÓN UNIFICADA DE REGISTROS (ESTILO PRODUCTOS / DETALLADO) --}}
    <div class="section-heading">DETALLE DE MOVIMIENTOS Y REGISTROS</div>
    <table class="data-table">
      <thead>
        <tr>
          <th style="width: 45%;">DESCRIPCIÓN DE VENTA / PRODUCTO / ANTICIPO / INDEXACIÓN</th>
          <th style="width: 15%;">DEBE ($)</th>
          <th style="width: 15%;">ABONO / SALDO ($)</th>
          <th style="width: 25%;">NOTA / OBSERVACIÓN</th>
        </tr>
      </thead>
      <tbody>
        @php 
          $totalDebeGeneral = 0; 
          $totalAbonoGeneral = 0; 
          $totalInteresesGeneral = 0;
          $totalSaldoAFavor = 0;
        @endphp

        @forelse($creditos as $credito)
          @php 
            $esAnticipo = ($credito->estado === 'anticipo' || $credito->saldo_pendiente < 0);
            $venta = $credito->venta; 
            $esCreditoDirecto = (!$venta || $venta->detalles->isEmpty());

            if ($esAnticipo) {
                $totalSaldoAFavor += abs($credito->saldo_pendiente);
            } else {
                $totalDebeGeneral += $credito->monto_inicial;
                
                $abonosValidos = $credito->abonos ? $credito->abonos->where('estado', 'Realizado') : collect();
                $totalAbonoGeneral += $abonosValidos->sum('monto_pagado_usd');

                $interesesAplicados = $credito->intereses ? $credito->intereses->where('estado', 'aplicado') : collect();
                $montoIntereses = $interesesAplicados->sum('monto_interes');
                $totalInteresesGeneral += $montoIntereses;
            }
          @endphp

          <!-- CABECERA DEL REGISTRO -->
          <tr class="{{ $esAnticipo ? 'bg-anticipo font-bold' : 'bg-light-gray font-bold' }}">
            <td>
              @if($esAnticipo)
                <strong>SALDO A FAVOR / ANTICIPO ANT-{{ $credito->id }}</strong>
              @else
                REF: {{ $venta->codigo_factura ?? 'CRD-' . $credito->id }} 
              @endif
              <span style="font-weight: normal; color: #555; font-size: 8px;">(FECHA: {{ $credito->created_at->format('d/m/Y h:i A') }})</span>
            </td>
            <td class="text-right">
              @if(!$esAnticipo)
                ${{ number_format($credito->monto_inicial, 2) }}
              @endif
            </td>
            <td></td>
            <td style="font-size: 8.5px;">
              @if($esAnticipo)
                ANTICIPO / SALDO DISPONIBLE
              @elseif(!empty($credito->observacion))
                <strong>NOTA:</strong> {{ $credito->observacion }}
              @elseif(!empty($venta) && !empty($venta->observacion))
                <strong>NOTA:</strong> {{ $venta->observacion }}
              @elseif($esCreditoDirecto)
                CRÉDITO DIRECTO / PRÉSTAMO
              @else
                <em>Venta a crédito</em>
              @endif
            </td>
          </tr>

          <!-- CONTENIDO SEGÚN TIPO -->
          @if($esAnticipo)
            <tr>
              <td class="pl-4 text-info"><em>Monto registrado a favor del cliente para futuros pagos</em></td>
              <td></td>
              <td class="text-right font-bold text-info">
                +${{ number_format(abs($credito->saldo_pendiente), 2) }}
              </td>
              <td style="color: #666; font-size: 8.5px;">A favor / Excedente</td>
            </tr>
          @elseif(!$esCreditoDirecto)
            @foreach($venta->detalles as $detalle)
              <tr>
                <td class="pl-4">
                  • {{ $detalle->insumo->producto ?? 'Producto N/A' }}
                  @if(!empty($detalle->insumo->serial))
                    <span style="color: #666; font-size: 8px;">(S/N: {{ $detalle->insumo->serial }})</span>
                  @endif
                  <span style="color: #666; font-size: 8px;">x{{ $detalle->cantidad }}</span>
                </td>
                <td class="text-right text-muted" style="font-size: 8.5px;">
                  ${{ number_format($detalle->precio_unitario * $detalle->cantidad, 2) }}
                </td>
                <td></td>
                <td></td>
              </tr>
            @endforeach
          @else
            <tr>
              <td class="pl-4" style="color: #6f42c1; font-style: italic;">Préstamo / Cargo directo registrado en cuenta</td>
              <td class="text-right text-muted" style="font-size: 8.5px;">
                ${{ number_format($credito->monto_inicial, 2) }}
              </td>
              <td></td>
              <td></td>
            </tr>
          @endif

          @if(!$esAnticipo)
            {{-- INDEXACIONES --}}
            @if(isset($interesesAplicados) && $interesesAplicados->isNotEmpty())
              @foreach($interesesAplicados as $interes)
                <tr class="bg-interes">
                  <td class="pl-4" style="font-size: 8.5px;">
                    <strong>INDEXACIÓN POR INFLACIÓN ({{ $interes->porcentaje }}%)</strong>
                    <span style="color: #666;">({{ $interes->aplicado_en ? $interes->aplicado_en->format('d/m/Y') : '' }})</span>
                  </td>
                  <td class="text-right font-bold text-danger">
                    +${{ number_format($interes->monto_interes, 2) }}
                  </td>
                  <td></td>
                  <td style="color: #666; font-size: 8.5px;">Ajuste de valor aplicado</td>
                </tr>
              @endforeach
            @endif

            {{-- ABONOS --}}
            @if(isset($credito->abonos))
              @foreach($credito->abonos->where('estado', 'Realizado') as $abono)
                @php $esReembolso = $abono->monto_pagado_usd < 0; @endphp
                <tr class="{{ $esReembolso ? 'bg-interes' : 'bg-abono' }}">
                  <td class="pl-4" style="font-size: 8.5px;">
                    <strong>{{ $esReembolso ? '#REEMBOLSO:' : '#ABONO:' }}</strong> {{ $abono->codigo_recibo ?? 'ABN-' . $abono->id }}
                    <span style="color: #666;">({{ $abono->created_at->format('d/m/Y h:i A') }})</span>
                  </td>
                  <td></td>
                  <td class="text-right font-bold {{ $esReembolso ? 'text-danger' : 'text-success' }}">
                    {{ $esReembolso ? '-' : '' }}${{ number_format(abs($abono->monto_pagado_usd), 2) }}
                  </td>
                  <td style="color: #666; font-size: 8.5px;">
                    {{ $abono->detalles ?? ($esReembolso ? 'Devolución de saldo' : 'Abono realizado') }}
                  </td>
                </tr>
              @endforeach
            @endif

            <!-- SUBTOTAL PENDIENTE DE ESTE CRÉDITO -->
            <tr class="bg-subtotal">
              <td class="text-right font-bold" style="font-size: 8.5px;">SALDO PENDIENTE ESTE CRÉDITO:</td>
              <td colspan="2" class="text-right font-bold {{ $credito->saldo_pendiente > 0 ? 'text-danger' : 'text-success' }}">
                ${{ number_format(max(0, $credito->saldo_pendiente), 2) }}
              </td>
              <td></td>
            </tr>
          @endif

          <!-- SEPARADOR ENTRE CREDITOS -->
          <tr><td colspan="4" style="background-color: #ffffff; padding: 2px; border: none;"></td></tr>

        @empty
          <tr>
            <td colspan="4" class="text-center text-muted" style="padding: 15px;">
              El cliente no posee registros de créditos, ventas ni saldos pendientes actualmente.
            </td>
          </tr>
        @endforelse

      </tbody>

      {{-- PIE CON TOTALES GENERALES --}}
      @if(isset($creditos) && $creditos->isNotEmpty())
      @php
        $deudaTotalGeneral = $totalDebeGeneral + $totalInteresesGeneral;
        $deudaRestante = $deudaTotalGeneral - $totalAbonoGeneral;
        $balanceFinal = $deudaRestante - $totalSaldoAFavor;
      @endphp
      <tfoot class="bg-footer font-bold" style="font-size: 9.5px;">
        <tr>
          <td class="text-right">TOTAL CRÉDITOS Y COMPRAS:</td>
          <td class="text-right text-warning">${{ number_format($totalDebeGeneral, 2) }}</td>
          <td class="text-right text-success">${{ number_format($totalAbonoGeneral, 2) }}</td>
          <td class="text-right">TOTAL ABONADO NETO</td>
        </tr>
        @if($totalInteresesGeneral > 0)
        <tr>
          <td colspan="3" class="text-right text-warning">TOTAL INDEXACIONES (AJUSTE POR INFLACIÓN):</td>
          <td class="text-right text-warning">+${{ number_format($totalInteresesGeneral, 2) }}</td>
        </tr>
        @endif
        @if($totalSaldoAFavor > 0)
        <tr>
          <td colspan="3" class="text-right text-info">SALDO A FAVOR / ANTICIPOS DISPONIBLES:</td>
          <td class="text-right text-info">-${{ number_format($totalSaldoAFavor, 2) }}</td>
        </tr>
        @endif
        <tr style="font-size: 10.5px; border-top: 2px solid #fff;">
          <td colspan="3" class="text-right" style="text-transform: uppercase;">
            {{ $balanceFinal >= 0 ? 'SALDO NETO PENDIENTE DE PAGO:' : 'BALANCE A FAVOR DEL CLIENTE:' }}
          </td>
          <td class="text-right {{ $balanceFinal >= 0 ? 'text-danger' : 'text-info' }}">
            ${{ number_format(abs($balanceFinal), 2) }}
          </td>
        </tr>
      </tfoot>
      @endif
    </table>

    {{-- Pie de Página Fijo --}}
    <div class="footer">
        Documento generado automáticamente por el sistema de gestión. Página 1 de 1
    </div>

</body>
</html>