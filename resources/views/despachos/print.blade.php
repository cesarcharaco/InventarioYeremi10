<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante - {{ $despacho->codigo }}</title>
    <!-- AdminLTE / Bootstrap CSS (Asegúrate de ajustar la ruta si usas tu layout principal o CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        body {
            background-color: #f4f6f9;
            color: #333;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }
        .invoice-box {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background: #fff;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
        }
        /* Estilos exclusivos para la impresión en físico */
        @media print {
            body {
                background-color: #fff !important;
            }
            .no-print {
                display: none !important;
            }
            .invoice-box {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 10px !important;
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- BARRA DE ACCIONES (NO SE IMPRIME) -->
        <div class="row no-print my-4">
            <div class="col-12 text-right">
                <a href="{{ route('despacho.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa fa-arrow-left"></i> Volver al Listado
                </a>
                <button onclick="window.print();" class="btn btn-primary btn-sm ml-2">
                    <i class="fa fa-print"></i> Imprimir Comprobante
                </button>
            </div>
        </div>

        <!-- CONTENEDOR PRINCIPAL DEL COMPROBANTE -->
        <div class="invoice-box">
            <!-- Encabezado del Comprobante -->
            <!-- Encabezado del Comprobante -->
            <div class="row mb-4">
                <div class="col-6">
                    <h4 class="text-primary font-weight-bold mb-0">COMPROBANTE DE DESPACHO</h4>
                    <span class="text-muted">Control de Inventario y Logística</span>
                </div>
                <div class="col-6 text-right">
                    <h5 class="font-weight-bold text-dark mb-1">{{ $despacho->codigo }}</h5>
                    <div class="mb-1">
                        @php
                            $estadoConfig = match($despacho->estado) {
                                'Pendiente'                => ['clase' => 'badge-secondary', 'texto' => 'PENDIENTE'],
                                'En Tránsito'              => ['clase' => 'badge-warning text-dark', 'texto' => 'EN TRÁNSITO'],
                                'Recibido'                 => ['clase' => 'badge-success', 'texto' => 'RECIBIDO'],
                                'recibido_con_incidencias' => ['clase' => 'badge-info', 'texto' => 'RECIBIDO CON INCIDENCIAS'],
                                'Rechazado'                => ['clase' => 'badge-danger', 'texto' => 'RECHAZADO'],
                                'Cancelado'                => ['clase' => 'badge-danger', 'texto' => 'CANCELADO'],
                                default                    => ['clase' => 'badge-secondary', 'texto' => strtoupper($despacho->estado)],
                            };
                        @endphp
                        <span class="badge {{ $estadoConfig['clase'] }} px-2 py-1" style="font-size: 0.85rem; border: 1px solid rgba(0,0,0,0.15);">
                            ESTADO: {{ $estadoConfig['texto'] }}
                        </span>
                    </div>
                    <small class="text-muted d-block">
                        Fecha Emisión: {{ $despacho->fecha_despacho ? \Carbon\Carbon::parse($despacho->fecha_despacho)->format('d/m/Y h:i A') : 'N/D' }} {{$despacho->fecha_despacho}}
                    </small>
                </div>
            </div>

            <hr>

            <!-- Datos Generales -->
            <div class="row invoice-info mb-4">
                <div class="col-sm-4 invoice-col">
                    <h6><strong>Origen:</strong></h6>
                    <address class="text-muted">
                        {{ $despacho->origen->nombre ?? 'N/D' }}
                    </address>
                </div>
                <div class="col-sm-4 invoice-col">
                    <h6><strong>Destino:</strong></h6>
                    <address class="text-muted">
                        {{ $despacho->destino->nombre ?? 'N/D' }}
                    </address>
                </div>
                <div class="col-sm-4 invoice-col">
                    <h6><strong>Datos de Transporte:</strong></h6>
                    <address class="text-muted">
                        <strong>Transportista:</strong> {{ $despacho->transportado_por }}<br>
                        @if($despacho->vehiculo_placa)
                            <strong>Placa:</strong> {{ $despacho->vehiculo_placa }}
                        @endif
                    </address>
                </div>
            </div>

            <!-- Tabla de Ítems Despachados -->
            <!-- Tabla de Ítems Despachados -->
            <div class="row">
                <div class="col-12 table-responsive">
                    <table class="table table-bordered table-sm text-sm">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 6%;" class="text-center">#</th>
                                <th style="width: 22%;">Serial</th>
                                <th style="width: 54%;">Producto / Descripción</th>
                                <th style="width: 18%;" class="text-center">Cant. Enviada</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($despacho->detalles as $index => $detalle)
                            <tr>
                                <td class="text-center align-middle">{{ $index + 1 }}</td>
                                <td class="align-middle font-weight-bold text-monospace">
                                    {{ $detalle->insumos->serial ?? 'S/N' }}
                                </td>
                                <td class="align-middle">
                                    <strong>{{ $detalle->insumos->producto ?? 'Insumo ID: ' . $detalle->id_insumo }}</strong>
                                    @if(!empty($detalle->insumos->descripcion))
                                        <div class="text-muted small mt-1">{{ $detalle->insumos->descripcion }}</div>
                                    @endif
                                </td>
                                <td class="text-center align-middle font-weight-bold">{{ $detalle->cantidad_enviada }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Observaciones si existen -->
            @if($despacho->observacion)
            <div class="row mt-3">
                <div class="col-12">
                    <p class="text-muted mb-1"><strong>Observaciones:</strong></p>
                    <div class="p-2 bg-light border rounded text-sm">
                        {{ $despacho->observacion }}
                    </div>
                </div>
            </div>
            @endif

            <!-- Firmas para control físico -->
            <div class="row mt-5 pt-4" style="page-break-inside: avoid;">
                <div class="col-6 text-center">
                    <div style="border-top: 1px solid #333; width: 80%; margin: 0 auto; padding-top: 5px;">
                        <small class="text-muted">Entregado por (Almacén)</small>
                    </div>
                </div>
                <div class="col-6 text-center">
                    <div style="border-top: 1px solid #333; width: 80%; margin: 0 auto; padding-top: 5px;">
                        <small class="text-muted">Recibido por (Transporte / Destino)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>