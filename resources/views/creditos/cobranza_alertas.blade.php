@extends('layouts.app')

@section('title') Alertas de Cobranza @endsection

@section('content')
<style>
    .alerta-card {
        border-radius: 8px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        border-width: 1px 1px 1px 5px;
    }

    .alerta-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }

    /* Niveles de alerta según antigüedad del crédito pendiente más antiguo */
    .card-tramo-30 {
        background-color: #fdf2f2;
        border-color: #f5c2c7;
        border-left-color: #dc3545;
    }

    .card-tramo-20 {
        background-color: #fff6ec;
        border-color: #ffd8a8;
        border-left-color: #fd7e14;
    }

    .card-tramo-15 {
        background-color: #fffdf0;
        border-color: #ffe69c;
        border-left-color: #ffc107;
    }

    .seccion-header {
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 14px;
    }

    .seccion-30 {
        background-color: #f8d7da;
        color: #721c24;
        border-left: 5px solid #dc3545;
    }

    .seccion-20 {
        background-color: #ffe8cc;
        color: #8a3b00;
        border-left: 5px solid #fd7e14;
    }

    .seccion-15 {
        background-color: #fff3cd;
        color: #664d03;
        border-left: 5px solid #ffc107;
    }

    .total-cobrar-box {
        background-color: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 10px 16px;
        min-width: 220px;
    }

    @media (max-width: 768px) {
        .btn-block-mobile {
            width: 100%;
            display: block;
            margin-top: 6px;
        }

        .total-cobrar-box {
            width: 100%;
            margin-top: 10px;
        }
    }
</style>

<main class="app-content">
    <div class="app-title d-flex justify-content-between align-items-start flex-wrap">
        <div>
            <h3 class="title"><i class="fa fa-bell"></i> Alertas de Cobranza (Créditos Pendientes)</h3>
            <p class="mb-0">Seguimiento por antigüedad del crédito pendiente más antiguo (15, 20 y 30+ días)</p>
        </div>

        {{-- Total por cobrar superior derecha --}}
        <div class="total-cobrar-box text-md-right shadow-sm">
            <small class="text-muted text-uppercase font-weight-bold d-block">Total por Cobrar (Alertas)</small>
            <span class="h4 font-weight-bold text-danger mb-0" id="total-general-cobrar">
                ${{ number_format($totales['general'], 2) }}
            </span>
        </div>
    </div>

    <div class="basic-tb-hd text-center">
        @include('layouts.partials.flash-messages')
    </div>

    {{-- Controles: Buscador, Filtro por Local (solo Admin) y Regreso a Cuentas por Cobrar --}}
    <div class="tile mb-4">
        <div class="row align-items-end">
            <div class="col-md-{{ $esAdmin ? '5' : '8' }} mb-2 mb-md-0">
                <label for="buscador-morosos" class="font-weight-bold small text-muted mb-1">
                    <i class="fa fa-search"></i> Buscar cliente (Nombre, Alias o Cédula/RIF)
                </label>
                <input type="text" id="buscador-morosos" class="form-control" placeholder="Escribe para filtrar en las tarjetas..." value="{{ request('buscar') }}">
            </div>

            @if($esAdmin)
                <div class="col-md-4 mb-2 mb-md-0">
                    <form method="GET" action="{{ route('creditos.cobranza_alertas') }}" id="form-filtro-local">
                        <label for="id_local" class="font-weight-bold small text-muted mb-1">
                            <i class="fa fa-building"></i> Filtrar por Local
                        </label>
                        <select name="id_local" id="id_local" class="form-control" onchange="this.form.submit()">
                            <option value="">Todos los locales activos</option>
                            @foreach($locales as $local)
                                <option value="{{ $local->id }}" {{ (string) $localSeleccionado === (string) $local->id ? 'selected' : '' }}>
                                    {{ $local->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif

            <div class="col-md-3 text-md-right">
                <a href="{{ route('creditos.index') }}" class="btn btn-secondary btn-block-mobile">
                    <i class="fa fa-arrow-left"></i> Cartera General
                </a>
            </div>
        </div>
    </div>

    @php
        $secciones = [
            [
                'titulo'       => '30 Días o Más (Atraso Crítico)',
                'badge_clase'  => 'badge-danger',
                'seccion_clase'=> 'seccion-30',
                'card_clase'   => 'card-tramo-30',
                'items'        => $grupo30,
                'subtotal'     => $totales['tramo_30'],
                'vacio_texto'  => 'No hay clientes con créditos pendientes de 30 o más días.',
            ],
            [
                'titulo'       => '20 a 29 Días de Antigüedad',
                'badge_clase'  => 'badge-warning text-dark',
                'seccion_clase'=> 'seccion-20',
                'card_clase'   => 'card-tramo-20',
                'items'        => $grupo20,
                'subtotal'     => $totales['tramo_20'],
                'vacio_texto'  => 'No hay clientes con créditos pendientes entre 20 y 29 días.',
            ],
            [
                'titulo'       => '15 a 19 Días de Antigüedad',
                'badge_clase'  => 'badge-warning text-dark',
                'seccion_clase'=> 'seccion-15',
                'card_clase'   => 'card-tramo-15',
                'items'        => $grupo15,
                'subtotal'     => $totales['tramo_15'],
                'vacio_texto'  => 'No hay clientes con créditos pendientes entre 15 y 19 días.',
            ],
        ];
    @endphp

    @foreach($secciones as $sec)
        <div class="tile mb-4">
            <div class="seccion-header d-flex justify-content-between align-items-center flex-wrap {{ $sec['seccion_clase'] }}">
                <div class="font-weight-bold">
                    <i class="fa fa-exclamation-circle"></i> {{ $sec['titulo'] }}
                    <span class="badge {{ $sec['badge_clase'] }} ml-2">{{ $sec['items']->count() }}</span>
                </div>
                <div class="font-weight-bold mt-1 mt-sm-0">
                    Subtotal Tramo: ${{ number_format($sec['subtotal'], 2) }}
                </div>
            </div>

            @if($sec['items']->isEmpty())
                <p class="text-muted mb-0 text-center py-2">{{ $sec['vacio_texto'] }}</p>
            @else
                <div class="row contenedor-tarjetas">
                    @foreach($sec['items'] as $item)
                        @php
                            $textoBusqueda = strtolower(trim(
                                ($item['nombre'] ?? '') . ' ' .
                                ($item['alias'] ?? '') . ' ' .
                                ($item['identificacion'] ?? '')
                            ));
                        @endphp
                        <div class="col-md-6 col-lg-4 mb-3 tarjeta-cliente" data-busqueda="{{ $textoBusqueda }}">
                            <div class="card alerta-card h-100 {{ $sec['card_clase'] }}">
                                <div class="card-body d-flex flex-column justify-content-between p-3">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h5 class="card-title font-weight-bold mb-0">{{ $item['nombre'] }}</h5>
                                                @if(!empty($item['alias']))
                                                    <small class="text-muted">({{ $item['alias'] }})</small>
                                                @endif
                                            </div>
                                            <span class="badge {{ $sec['badge_clase'] }} px-2 py-1">
                                                {{ $item['dias_antiguedad'] }} días
                                            </span>
                                        </div>

                                        <div class="small mb-2">
                                            <div><strong>Cédula / ID:</strong> {{ $item['identificacion'] ?? 'N/A' }}</div>
                                            <div>
                                                <strong>Crédito más antiguo:</strong>
                                                {{ \Carbon\Carbon::parse($item['fecha_credito_antiguo'])->format('d/m/Y') }}
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <span class="text-muted small d-block">Monto Total Pendiente</span>
                                            <span class="h5 font-weight-bold text-dark">
                                                ${{ number_format($item['saldo_pendiente'], 2) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center flex-wrap pt-2 border-top">
                                        <a href="{{ route('creditos.show', $item['cliente_id']) }}" class="btn btn-info btn-sm btn-block-mobile">
                                            <i class="fa fa-eye"></i> Ver Historial
                                        </a>

                                        {{-- Enlace para descargar PDF de notificación (conectar con ruta/método PDF) --}}
                                        <a href="{{ route('creditos.pdf_notificacion', $item['cliente_id']) }}" target="_blank" class="btn btn-outline-dark btn-sm btn-block-mobile">
                                            <i class="fa fa-file-pdf-o"></i> PDF Aviso
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</main>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        function filtrarTarjetas() {
            const $input = $('#buscador-morosos');
            const limpio = String($input.val() || '').replace(/<[^>]*>/g, '').trim();
            if (limpio !== $input.val()) {
                $input.val(limpio);
            }
            const termino = limpio.toLowerCase();

            $('.tarjeta-cliente').each(function() {
                const texto = String($(this).data('busqueda') || '');
                const coincide = termino === '' || texto.indexOf(termino) !== -1;
                $(this).toggle(coincide);
            });
        }

        $('#buscador-morosos').on('keyup input', filtrarTarjetas);

        // Si viene ?buscar=... desde la notificación, filtra automáticamente al abrir
        if (($('#buscador-morosos').val() || '').trim() !== '') {
            filtrarTarjetas();
        }
    });
</script>
@endsection