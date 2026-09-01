<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etiquetas en Lote</title>
    <style>
        @page {
            margin: 3mm;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .page-break {
            page-break-after: always;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        td {
            width: 33.33%;
            padding: 0.5mm;
            vertical-align: top;
            box-sizing: border-box;
        }
        .label-card {
            border: 1px dashed #ccc;
            padding: 1.5mm 1mm;
            text-align: center;
            height: 28mm;
            box-sizing: border-box;
            overflow: hidden;
        }
        .producto-titulo {
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.1;
            max-height: 6mm;
            overflow: hidden;
            margin-bottom: 1.5mm;
            word-wrap: break-word;
        }
        .barcode-img {
            width: 92%;
            height: 12mm;
            display: block;
            margin: 0 auto;
        }
        .serial-texto {
            font-size: 7.5pt;
            font-weight: bold;
            letter-spacing: 1px;
            margin-top: 1mm;
        }
    </style>
</head>
<body>
    @foreach ($listaImpresion as $index => $data)
        <table class="{{ !$loop->last ? 'page-break' : '' }}">
            @for ($i = 0; $i < 24; $i++)
                @if ($i % 3 == 0)
                    <tr>
                @endif

                <td>
                    <div class="label-card">
                        <div class="producto-titulo">{{ $data['insumo']->producto }}: {{ $data['insumo']->descripcion }}</div>
                        <img src="data:image/png;base64,{{ $data['barcodeBase64'] }}" class="barcode-img">
                        <div class="serial-texto">{{ $data['insumo']->serial }}</div>
                    </div>
                </td>

                @if (($i + 1) % 3 == 0 || $i + 1 == 24)
                    </tr>
                @endif
            @endfor
        </table>
    @endforeach
</body>
</html>