@extends('layouts.app')
@section('title') Estado de Cuenta Pendiente @endsection
@push('styles')
<style>
    /* Estilos exclusivos para cuando se manda a imprimir */
    @media print {
        /* Oculta menú lateral, barra superior, botones y migas de pan */
        .app-header, 
        .app-sidebar, 
        .app-breadcrumb, 
        .d-print-none, 
        .btn, 
        footer {
            display: none !important;
        }

        /* Ajusta el contenedor principal al 100% del ancho del papel */
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

        /* Mantiene los bordes negros definidos y compactos para papel */
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

        /* Evita que la tabla se corte a la mitad de una fila entre páginas */
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
      <p>Detalle de compras y abonado para: <strong>{{ $cliente->nombre }}</strong></p>
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
              <th style="width: 45%;">DESCRIPCIÓN DE VENTA / PRODUCTO</th>
              <th style="width: 15%;">DEBE ($)</th>
              <th style="width: 15%;">ABONO ($)</th>
              <th style="width: 25%;">NOTA / OBSERVACIÓN</th>
            </tr>
          </thead>
          <tbody>
            @php 
              $totalDebeGeneral = 0; 
              $totalAbonoGeneral = 0; 
            @endphp

            @forelse($creditos as $credito)
              @php 
                $venta = $credito->venta; 
                $esCreditoDirecto = (!$venta || $venta->detalles->isEmpty());
                $totalDebeGeneral += $credito->monto_inicial;
                $abonosCredito = $credito->abonos->sum('monto');
                $totalAbonoGeneral += $abonosCredito;
              @endphp

              <!-- CABECERA DEL CRÉDITO / VENTA -->
              <tr class="table-secondary font-weight-bold">
                <td>
                  <i class="fa fa-file-invoice"></i> 
                  COD-FACTURA: {{ $venta->codigo_factura ?? 'CRD-' . $credito->id }} 
                  <small class="text-muted ml-2">(FECHA: {{ $credito->created_at->format('d-m-Y') }})</small>
                </td>
                <td></td>
                <td></td>
                {{-- Celda combinada verticalmente si hay nota de venta --}}
                <td class="align-middle small">
                  @if(!empty($venta->observacion))
                    <strong>NOTA:</strong> {{ $venta->observacion }}
                  @elseif($esCreditoDirecto)
                    <span class="badge badge-purple" style="background-color: #6f42c1; color: #fff;">CRÉDITO DIRECTO</span>
                  @else
                    <i class="text-muted">Sin nota</i>
                  @endif
                </td>
              </tr>

              <!-- SI ES VENTA CON PRODUCTOS (DETALLES) -->
              @if(!$esCreditoDirecto)
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
                <!-- SI ES CRÉDITO DIRECTO (SIN PRODUCTOS) -->
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

              <!-- TOTAL DE ESTE CRÉDITO -->
              <tr class="bg-light">
                <td class="text-right font-weight-bold small text-uppercase">TOTAL VENTA:</td>
                <td class="text-right font-weight-bold text-dark border-top border-bottom">
                  ${{ number_format($credito->monto_inicial, 2) }}
                </td>
                <td></td>
                <td></td>
              </tr>

              <!-- HISTORIAL DE ABONOS DE ESTE CRÉDITO -->
              @foreach($credito->abonos as $abono)
                <tr class="table-success">
                  <td class="pl-4 small">
                    <i class="fa fa-check-circle text-success"></i> 
                    <strong>#ABONO:</strong> {{ $abono->codigo_recibo ?? 'ABN-' . $abono->id }}
                    <span class="text-muted ml-2">(FECHA: {{ $abono->created_at->format('d-m-Y') }})</span>
                  </td>
                  <td></td>
                  <td class="text-right font-weight-bold text-success">
                    ${{ number_format($abono->monto, 2) }}
                  </td>
                  <td class="small italic text-muted">
                    {{ $abono->observacion ?? 'Abono realizado' }}
                  </td>
                </tr>
              @endforeach

              <!-- SEPARADOR ENTRE CRÉDITOS -->
              <tr><td colspan="4" class="bg-white p-1"></td></tr>

            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-4">
                  <i class="fa fa-info-circle fa-2x d-block mb-2"></i>
                  El cliente no posee ventas ni créditos pendientes actualmente.
                </td>
              </tr>
            @endforelse

          </tbody>
          <!-- PIE CON RESUMEN TOTAL -->
          @if($creditos->isNotEmpty())
          <tfoot class="bg-dark text-white font-weight-bold" style="font-size: 1.1em;">
            <tr>
              <td class="text-right">TOTAL GENERAL PENDIENTE:</td>
              <td class="text-right text-warning">${{ number_format($totalDebeGeneral, 2) }}</td>
              <td class="text-right text-success">${{ number_format($totalAbonoGeneral, 2) }}</td>
              <td class="text-right text-danger">
                SALDO PENDIENTE: ${{ number_format($totalDebeGeneral - $totalAbonoGeneral, 2) }}
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
        // Atajo opcional: si el usuario presiona Ctrl + P
        $(document).bind("keydown", function(e) {
            if (e.ctrlKey && e.keyCode == 80) {
                e.preventDefault();
                window.print();
            }
        });
    });
</script>
@endsection