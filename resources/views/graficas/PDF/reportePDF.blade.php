<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de incidencias e insumos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h2 { margin-bottom: 5px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 4px; text-align: left; }
        th { background-color: #f0f0f0; }
        .center { text-align: center; }
        .right { text-align: right; }
    </style>
</head>
<body>

    {{-- ENCABEZADO --}}
    <h2>Reporte de incidencias</h2>
    <p>
        Rango de fechas:
        <strong>{{ $desde }}</strong> al
        <strong>{{ $hasta }}</strong>
    </p>

    <hr>

    {{-- ESTADO ACTUAL DEL ALMACÉN --}}
    <h3>Estado actual del almacén</h3>
    <table>
        <thead>
            <tr>
                <th class="center">Nro.</th>
                <th>Insumo</th>
                <th>Marca</th>
                <th class="right">En el almacén</th>
                <th class="right">Fuera del almacén</th>
                <th class="right">Disponibles</th>
                <th class="right">Entregados</th>
                <th class="right">Usados</th>
                <th class="right">Inservible</th>
            </tr>
        </thead>
        <tbody>
            @php $num = 0; @endphp
            @foreach($insumos as $insumo)
                <tr>
                    <td class="center">{{ ++$num }}</td>
                    <td>{{ $insumo->producto ?? '' }}</td>
                    <td>{{ $insumo->marca ?? '' }}</td>
                    <td class="right">{{ $insumo->in_almacen ?? 0 }}</td>
                    <td class="right">{{ $insumo->out_almacen ?? 0 }}</td>
                    <td class="right">{{ $insumo->disponibles ?? 0 }}</td>
                    <td class="right">{{ $insumo->entregados ?? 0 }}</td>
                    <td class="right">{{ $insumo->usados ?? 0 }}</td>
                    <td class="right">{{ $insumo->inservible ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <br>
    <hr>

    {{-- INCIDENCIAS EN EL RANGO --}}
    <h3>Incidencias registradas en el período</h3>
    <table>
        <thead>
            <tr>
                <th class="center">Nro.</th>
                <th>Código historial</th>
                <th>Insumo</th>
                <th>Marca</th>
                <th>Tipo de incidencia</th>
                <th class="right">Cantidad</th>
                <th>Descontar</th>
                <th>Observación</th>
                <th>Fecha de incidencia</th>
            </tr>
        </thead>
        <tbody>
            @php $num = 0; @endphp
            @foreach($incidencias as $incidencia)
                @php
                    // Buscar código de historial (si lo pasaste ya agrupado por incidencia)
                    $codigo = '';
                    if (isset($historialPorIncidencia) && isset($historialPorIncidencia[$incidencia->id])) {
                        $codigo = $historialPorIncidencia[$incidencia->id];
                    }
                @endphp
                <tr>
                    <td class="center">{{ ++$num }}</td>
                    <td>{{ $codigo }}</td>
                    <td>{{ optional($incidencia->insumo)->producto }}</td>
                    <td>{{ optional($incidencia->insumo)->marca }}</td>
                    <td>{{ $incidencia->tipo }}</td>
                    <td class="right">{{ $incidencia->cantidad }}</td>
                    <td>{{ $incidencia->descontar }}</td>
                    <td>{{ $incidencia->observacion }}</td>
                    <td>{{ $incidencia->fecha_incidencia }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
