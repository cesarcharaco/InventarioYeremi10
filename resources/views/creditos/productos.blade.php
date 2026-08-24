@extends('layouts.app')
@section('title') Estado de Cuenta Detallado @endsection

@push('styles')
<style>
    @media print {
        .app-header, 
        .app-sidebar, 
        .app-breadcrumb, 
        .d-print-none, 
        .btn, 
        footer {
            display: none !important;
        }

        body, .app-content {
            background-color: #fff !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        .tile {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .table-bordered, .table-bordered th, .table-bordered td {
            border: 1px solid #000 !important;
        }

        .table thead th {
            background-color: #f2f2f2 !important;
            color: #000 !important;
        }

        tr {
            page-break-inside: avoid;
        }
    }

    .tabular-nums {
        font-variant-numeric: tabular-nums;
    }

    /* ==========================================
       ESTILOS DE ENCABEZADO FORMAL
       ========================================== */
    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    .company-title { 
        color: #8b0000; 
        font-size: 16px; 
        font-weight: bold; 
        text-transform: uppercase; 
        margin: 0; 
        line-height: 1.1; 
    }
    .company-subtitle { 
        font-size: 10px; 
        font-weight: bold; 
        color: #222; 
        margin-bottom: 4px; 
    }
    .company-info-text { 
        font-size: 9px; 
        color: #444; 
        line-height: 1.3; 
    }

    .box-header-right { 
        border: 1.5px solid #8b0000; 
        border-radius: 6px; 
        padding: 8px 12px; 
        text-align: center; 
        background: #fff; 
    }
    .box-header-right h4 { 
        color: #8b0000; 
        font-weight: bold; 
        font-size: 11px; 
        margin: 0 0 5px 0; 
        border-bottom: 1px solid #8b0000; 
        padding-bottom: 3px; 
        text-transform: uppercase;
    }
    .box-header-right p { 
        margin: 3px 0; 
        font-size: 9px; 
        text-align: left; 
        font-weight: bold; 
    }
</style>
@endpush

@section('content')
<main class="app-content">
  <div class="app-title d-print-none">
    <div>
      <h1><i class="fa fa-file-text-o"></i> Resumen de Cuenta Pendiente</h1>
      <p class="text-muted mb-0">
        Detalle de compras, créditos directos, abonado y saldos para: <strong>{{ $cliente->nombre }}</strong>
      </p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('creditos.show', $cliente->id) }}">Estado de Cuenta</a></li>
      <li class="breadcrumb-item">Historial Detallado</li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row mb-3 d-print-none">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <a class="btn btn-outline-secondary font-weight-bold" href="{{ route('creditos.show', $cliente->id) }}">
                <i class="fa fa-arrow-left"></i> Regresar al Perfil
            </a>
            
            <button class="btn btn-primary font-weight-bold" onclick="window.print();">
                <i class="fa fa-print"></i> Imprimir Reporte
            </button>
        </div>
    </div>

    <div class="tile-body">
      
      <!-- ENCABEZADO FORMAL CORPORATIVO -->
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
      <hr style="border-top: 1px solid #ddd; margin: 15px 0;">

      <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle" style="border: 2px solid #333;">
          <thead class="bg-dark text-white text-center">
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
                    
                    // Suma de abonos válidos (descontando anulados y procesando reembolsos)
                    $abonosValidos = $credito->abonos->where('estado', 'Realizado');
                    $totalAbonoGeneral += $abonosValidos->sum('monto_pagado_usd');

                    // Suma de intereses/indexaciones aplicadas
                    $interesesAplicados = $credito->intereses ? $credito->intereses->where('estado', 'aplicado') : collect();
                    $montoIntereses = $interesesAplicados->sum('monto_interes');
                    $totalInteresesGeneral += $montoIntereses;
                }
              @endphp

              <!-- CABECERA DEL REGISTRO -->
              <tr class="{{ $esAnticipo ? 'table-info font-weight-bold' : 'table-secondary font-weight-bold' }}">
                <td>
                  @if($esAnticipo)
                    <i class="fa fa-wallet text-info"></i> 
                    <strong>SALDO A FAVOR / ANTICIPO ANT-{{ $credito->id }}</strong>
                  @else
                    <i class="fa fa-file-invoice"></i> 
                    REF: {{ $venta->codigo_factura ?? 'CRD-' . $credito->id }} 
                  @endif
                  <small class="text-muted ml-2">(FECHA: {{ $credito->created_at->format('d/m/Y h:i A') }})</small>
                </td>
                <td class="text-right tabular-nums">
                  @if(!$esAnticipo)
                    ${{ number_format($credito->monto_inicial, 2) }}
                  @endif
                </td>
                <td></td>
                <td class="align-middle small">
                  @if($esAnticipo)
                    <span class="badge badge-info">ANTICIPO / SALDO DISPONIBLE</span>
                  @elseif(!empty($credito->observacion))
                    <strong>NOTA:</strong> {{ $credito->observacion }}
                  @elseif(!empty($venta) && !empty($venta->observacion))
                    <strong>NOTA:</strong> {{ $venta->observacion }}
                  @elseif($esCreditoDirecto)
                    <span class="text-muted">Crédito directo</span>
                  @else
                    <i class="text-muted">Venta a crédito</i>
                  @endif
                </td>
              </tr>

              <!-- CONTENIDO SEGÚN TIPO DE REGISTRO -->
              @if($esAnticipo)
                <!-- ANTICIPO / SALDO A FAVOR -->
                <tr>
                  <td class="pl-4 text-info">
                    <em>Monto registrado a favor del cliente para futuros pagos</em>
                  </td>
                  <td></td>
                  <td class="text-right font-weight-bold text-info tabular-nums">
                    +${{ number_format(abs($credito->saldo_pendiente), 2) }}
                  </td>
                  <td class="small italic text-muted">A favor / Excedente</td>
                </tr>
              @elseif(!$esCreditoDirecto)
                <!-- VENTA CON PRODUCTOS -->
                @foreach($venta->detalles as $detalle)
                  <tr>
                    <td class="pl-4">
                      • {{ $detalle->insumo->producto ?? 'Producto N/A' }}
                      @if(!empty($detalle->insumo->serial))
                        <small class="text-muted">(S/N: {{ $detalle->insumo->serial }})</small>
                      @endif
                      <small class="text-muted ml-1">x{{ $detalle->cantidad }}</small>
                    </td>
                    <td class="text-right text-muted small tabular-nums">
                      ${{ number_format($detalle->precio_unitario * $detalle->cantidad, 2) }}
                    </td>
                    <td></td>
                    <td></td>
                  </tr>
                @endforeach
              @else
                <!-- CRÉDITO DIRECTO -->
                <tr>
                  <td class="pl-4 text-italic" style="color: #6f42c1;">
                    <em>Préstamo / Cargo directo registrado en cuenta</em>
                  </td>
                  <td class="text-right text-muted small tabular-nums">
                    ${{ number_format($credito->monto_inicial, 2) }}
                  </td>
                  <td></td>
                  <td></td>
                </tr>
              @endif

              @if(!$esAnticipo)
                <!-- HISTORIAL DE INDEXACIONES / INTERESES -->
                @if(isset($interesesAplicados) && $interesesAplicados->isNotEmpty())
                  @foreach($interesesAplicados as $interes)
                    <tr class="table-warning">
                      <td class="pl-4 small">
                        <i class="fa fa-line-chart text-warning"></i> 
                        <strong>INDEXACIÓN POR INFLACIÓN ({{ $interes->porcentaje }}%)</strong>
                        <span class="text-muted ml-2">({{ $interes->aplicado_en ? $interes->aplicado_en->format('d/m/Y') : '' }})</span>
                      </td>
                      <td class="text-right font-weight-bold text-danger tabular-nums">
                        +${{ number_format($interes->monto_interes, 2) }}
                      </td>
                      <td></td>
                      <td class="small text-muted italic">Ajuste de valor aplicado</td>
                    </tr>
                  @endforeach
                @endif

                <!-- HISTORIAL DE ABONOS -->
                @foreach($credito->abonos->where('estado', 'Realizado') as $abono)
                  @php $esReembolso = $abono->monto_pagado_usd < 0; @endphp
                  <tr class="{{ $esReembolso ? 'table-warning' : 'table-success' }}">
                    <td class="pl-4 small">
                      <i class="fa {{ $esReembolso ? 'fa-undo text-danger' : 'fa-check-circle text-success' }}"></i> 
                      <strong>{{ $esReembolso ? '#REEMBOLSO:' : '#ABONO:' }}</strong> {{ $abono->codigo_recibo ?? 'ABN-' . $abono->id }}
                      <span class="text-muted ml-2">({{ $abono->created_at->format('d/m/Y h:i A') }})</span>
                    </td>
                    <td></td>
                    <td class="text-right font-weight-bold {{ $esReembolso ? 'text-danger' : 'text-success' }} tabular-nums">
                      {{ $esReembolso ? '-' : '' }}${{ number_format(abs($abono->monto_pagado_usd), 2) }}
                    </td>
                    <td class="small italic text-muted">
                      {{ $abono->detalles ?? ($esReembolso ? 'Devolución de saldo' : 'Abono realizado') }}
                    </td>
                  </tr>
                @endforeach

                <!-- SUBTOTAL DE DEUDA RESTANTE DE ESTE CRÉDITO -->
                <tr class="bg-light">
                  <td class="text-right font-weight-bold small text-uppercase">SALDO PENDIENTE ESTE CRÉDITO:</td>
                  <td colspan="2" class="text-right font-weight-bold {{ $credito->saldo_pendiente > 0 ? 'text-danger' : 'text-success' }} tabular-nums">
                    ${{ number_format(max(0, $credito->saldo_pendiente), 2) }}
                  </td>
                  <td></td>
                </tr>
              @endif

              <!-- SEPARADOR -->
              <tr><td colspan="4" class="bg-white p-1"></td></tr>

            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-4">
                  <i class="fa fa-info-circle fa-2x d-block mb-2"></i>
                  El cliente no posee registros de créditos, ventas ni saldos pendientes actualmente.
                </td>
              </tr>
            @endforelse

          </tbody>

          <!-- PIE DE PÁGINA CON RESUMEN FINANCIERO CONSOLIDADO -->
          @if($creditos->isNotEmpty())
          @php
            $deudaTotalGeneral = $totalDebeGeneral + $totalInteresesGeneral;
            $deudaRestante = $deudaTotalGeneral - $totalAbonoGeneral;
            $balanceFinal = $deudaRestante - $totalSaldoAFavor;
          @endphp
          <tfoot class="bg-dark text-white font-weight-bold" style="font-size: 0.95em;">
            <tr>
              <td class="text-right">TOTAL CRÉDITOS Y COMPRAS:</td>
              <td class="text-right text-warning tabular-nums">${{ number_format($totalDebeGeneral, 2) }}</td>
              <td class="text-right text-success tabular-nums">${{ number_format($totalAbonoGeneral, 2) }}</td>
              <td class="text-right">TOTAL ABONADO NETO</td>
            </tr>
            @if($totalInteresesGeneral > 0)
            <tr>
              <td colspan="3" class="text-right text-warning">TOTAL INDEXACIONES (AJUSTE POR INFLACIÓN):</td>
              <td class="text-right text-warning tabular-nums">+${{ number_format($totalInteresesGeneral, 2) }}</td>
            </tr>
            @endif
            @if($totalSaldoAFavor > 0)
            <tr>
              <td colspan="3" class="text-right text-info">SALDO A FAVOR / ANTICIPOS DISPONIBLES:</td>
              <td class="text-right text-info tabular-nums">-${{ number_format($totalSaldoAFavor, 2) }}</td>
            </tr>
            @endif
            <tr style="font-size: 1.1em; border-top: 2px solid #fff;">
              <td colspan="3" class="text-right text-uppercase">
                {{ $balanceFinal >= 0 ? 'SALDO NETO PENDIENTE DE PAGO:' : 'BALANCE A FAVOR DEL CLIENTE:' }}
              </td>
              <td class="text-right {{ $balanceFinal >= 0 ? 'text-danger' : 'text-info' }} tabular-nums">
                ${{ number_format(abs($balanceFinal), 2) }}
              </td>
            </tr>
          </tfoot>
          @endif
        </table>
      </div>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $(document).bind("keydown", function(e) {
            if (e.ctrlKey && e.keyCode == 80) {
                e.preventDefault();
                window.print();
            }
        });
    });
</script>
@endsection