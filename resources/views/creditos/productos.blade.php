@extends('layouts.app')
@section('title') Estado de Cuenta Pendiente @endsection

@push('styles')
<style>
    /* Estilos exclusivos para cuando se manda a imprimir */
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

        .table-secondary {
            background-color: #e9ecef !important;
        }

        .table-success {
            background-color: #d4edda !important;
        }

        .table-info {
            background-color: #d1ecf1 !important;
        }

        tr {
            page-break-inside: avoid;
        }
    }
</style>
@endpush

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-file-text-o"></i> Resumen de Cuenta Pendiente</h1>
      <p>Detalle de compras, abonado y saldos para: <strong>{{ $cliente->nombre }}</strong></p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('creditos.show', $cliente->id) }}">Créditos</a></li>
      <li class="breadcrumb-item">Historial</li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row mb-3 d-print-none">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <a class="btn btn-secondary" href="{{ route('creditos.show', $cliente->id) }}">
                <i class="fa fa-arrow-left"></i> Regresar al Perfil
            </a>
            
            <button class="btn btn-primary" onclick="window.print();">
                <i class="fa fa-print"></i> Imprimir Estado
            </button>
        </div>
    </div>

    <div class="tile-body">
      <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle" style="border: 2px solid #333;">
          <thead class="bg-dark text-white text-center">
            <tr>
              <th style="width: 45%;">DESCRIPCIÓN DE VENTA / PRODUCTO / ANTICIPO</th>
              <th style="width: 15%;">DEBE ($)</th>
              <th style="width: 15%;">ABONO ($)</th>
              <th style="width: 25%;">NOTA / OBSERVACIÓN</th>
            </tr>
          </thead>
          <tbody>
            @php 
              $totalDebeGeneral = 0; 
              $totalAbonoGeneral = 0; 
              $totalSaldoAFavor = 0;
            @endphp

            @forelse($creditos as $credito)
              @php 
                $esAnticipo = ($credito->estado === 'anticipo');
                $venta = $credito->venta; 
                $esCreditoDirecto = (!$venta || $venta->detalles->isEmpty());

                if ($esAnticipo) {
                    // Si es anticipo, el saldo a favor viene negativo
                    $totalSaldoAFavor += abs($credito->saldo_pendiente);
                } else {
                    $totalDebeGeneral += $credito->monto_inicial;
                    $abonosCredito = $credito->abonos->sum('monto_pagado_usd');
                    $totalAbonoGeneral += $abonosCredito;
                }
              @endphp

              <!-- CABECERA DEL REGISTRO -->
              <tr class="{{ $esAnticipo ? 'table-info font-weight-bold' : 'table-secondary font-weight-bold' }}">
                <td>
                  @if($esAnticipo)
                    <i class="fa fa-wallet text-info"></i> 
                    <strong>SALDO A FAVOR / ANTICIPO #{{ $credito->id }}</strong>
                  @else
                    <i class="fa fa-file-invoice"></i> 
                    COD-FACTURA: {{ $venta->codigo_factura ?? 'CRD-' . $credito->id }} 
                  @endif
                  <small class="text-muted ml-2">(FECHA: {{ $credito->created_at->format('d-m-Y') }})</small>
                </td>
                <td></td>
                <td></td>
                <td class="align-middle small">
                  @if($esAnticipo)
                    <span class="badge badge-info">ANTICIPO / SALDO DISPONIBLE</span>
                  @elseif(!empty($venta->observacion))
                    <strong>NOTA:</strong> {{ $venta->observacion }}
                  @elseif($esCreditoDirecto)
                    <span class="badge badge-purple" style="background-color: #6f42c1; color: #fff;">CRÉDITO DIRECTO</span>
                  @else
                    <i class="text-muted">Sin nota</i>
                  @endif
                </td>
              </tr>

              <!-- CONTENIDO SEGÚN TIPO DE REGISTRO -->
              @if($esAnticipo)
                <!-- SI ES ANTICIPO -->
                <tr>
                  <td class="pl-4 text-info">
                    <em>Monto entregado como saldo a favor o vuelto pendiente</em>
                  </td>
                  <td></td>
                  <td class="text-right font-weight-bold text-info">
                    ${{ number_format(abs($credito->saldo_pendiente), 2) }}
                  </td>
                  <td class="small italic text-muted">Disponible para próximos pagos</td>
                </tr>
              @elseif(!$esCreditoDirecto)
                <!-- SI ES VENTA CON PRODUCTOS -->
                @foreach($venta->detalles as $detalle)
                  <tr>
                    <td class="pl-4">
                      • {{ $detalle->insumo->producto ?? 'Producto N/A' }}
                      @if(!empty($detalle->insumo->serial))
                        <small class="text-muted">(S/N: {{ $detalle->insumo->serial }})</small>
                      @endif
                    </td>
                    <td class="text-right font-weight-bold">
                      ${{ number_format($detalle->precio_unitario * $detalle->cantidad, 2) }}
                    </td>
                    <td></td>
                    <td></td>
                  </tr>
                @endforeach
              @else
                <!-- SI ES CRÉDITO DIRECTO -->
                <tr>
                  <td class="pl-4 text-italic text-purple">
                    <em>Consumo / Préstamo directo registrado</em>
                  </td>
                  <td class="text-right font-weight-bold">
                    ${{ number_format($credito->monto_inicial, 2) }}
                  </td>
                  <td></td>
                  <td></td>
                </tr>
              @endif

              @if(!$esAnticipo)
                <!-- TOTAL DE ESTE CRÉDITO -->
                <tr class="bg-light">
                  <td class="text-right font-weight-bold small text-uppercase">TOTAL VENTA:</td>
                  <td class="text-right font-weight-bold text-dark border-top border-bottom">
                    ${{ number_format($credito->monto_inicial, 2) }}
                  </td>
                  <td></td>
                  <td></td>
                </tr>

                <!-- HISTORIAL DE ABONOS -->
                @foreach($credito->abonos as $abono)
                  <tr class="table-success">
                    <td class="pl-4 small">
                      <i class="fa fa-check-circle text-success"></i> 
                      <strong>#ABONO:</strong> {{ $abono->codigo_recibo ?? 'ABN-' . $abono->id }}
                      <span class="text-muted ml-2">(FECHA: {{ $abono->created_at->format('d-m-Y') }})</span>
                    </td>
                    <td></td>
                    <td class="text-right font-weight-bold text-success">
                      ${{ number_format($abono->monto_pagado_usd, 2) }}
                    </td>
                    <td class="small italic text-muted">
                      {{ $abono->detalles ?? 'Abono realizado' }}
                    </td>
                  </tr>
                @endforeach
              @endif

              <!-- SEPARADOR -->
              <tr><td colspan="4" class="bg-white p-1"></td></tr>

            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-4">
                  <i class="fa fa-info-circle fa-2x d-block mb-2"></i>
                  El cliente no posee ventas, créditos ni saldos pendientes actualmente.
                </td>
              </tr>
            @endforelse

          </tbody>

          <!-- PIE DE PÁGINA CON BALANCE RESUMIDO -->
          @if($creditos->isNotEmpty())
          @php
            $deudaRestante = $totalDebeGeneral - $totalAbonoGeneral;
            $balanceFinal = $deudaRestante - $totalSaldoAFavor;
          @endphp
          <tfoot class="bg-dark text-white font-weight-bold" style="font-size: 1em;">
            <tr>
              <td class="text-right">TOTAL COMPRAS:</td>
              <td class="text-right text-warning">${{ number_format($totalDebeGeneral, 2) }}</td>
              <td class="text-right text-success">${{ number_format($totalAbonoGeneral, 2) }}</td>
              <td class="text-right">
                TOTAL ABONADO
              </td>
            </tr>
            @if($totalSaldoAFavor > 0)
            <tr>
              <td colspan="3" class="text-right text-info">SALDO A FAVOR ACUMULADO (ANTICIPOS):</td>
              <td class="text-right text-info">${{ number_format($totalSaldoAFavor, 2) }}</td>
            </tr>
            @endif
            <tr>
              <td colspan="3" class="text-right text-uppercase">
                {{ $balanceFinal >= 0 ? 'SALDO NETO PENDIENTE DE PAGO:' : 'BALANCE TOTAL A FAVOR DEL CLIENTE:' }}
              </td>
              <td class="text-right {{ $balanceFinal >= 0 ? 'text-danger' : 'text-info' }}" style="font-size: 1.15em;">
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