<?php

namespace App\Imports;

use App\Models\InsumosMayor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class InsumosImport implements ToCollection
{
    private $lista_id;
    private $incremento;

    public function __construct($lista_id, $incremento)
    {
        $this->lista_id = $lista_id;
        $this->incremento = $incremento;
    }

    public function collection(Collection $rows)
    {
        $headerIndex = null;

        // 1. Encontrar la fila del encabezado dinámicamente
        foreach ($rows as $index => $row) {
            // Buscamos la fila donde la primera columna diga "CÓDIGO"
            if (isset($row[0]) && trim($row[0]) === 'CÓDIGO') {
                $headerIndex = $index;
                break;
            }
        }

        if ($headerIndex === null) {
            throw new \Exception("No se encontró el encabezado 'CÓDIGO' en el archivo.");
        }
        
        // 2. Procesar solo a partir de la fila siguiente al encabezado
        for ($i = $headerIndex + 1; $i < $rows->count(); $i++) {
            $row = $rows[$i];

            // VALIDACIÓN CRÍTICA: Si el código está vacío, omitimos la fila
            if (empty($row[0])) {
                continue; 
            }

            // Mapeo (Ajusta los índices [x] según tu CSV real después de ver el dd)
            $costo = (float) ($row[6] ?? 0);

            InsumosMayor::create([
                'lista_oferta_id' => $this->lista_id,
                'codigo'          => (string) $row[0],
                'descripcion'     => $row[1],
                'aplicativo'      => $row[5] ?? 'N/A',
                'costo_usd'       => $costo,
                'venta_usd'       => $this->incremento > 0 ? round(($costo / $this->incremento), 2) : 0,
                'estado'          => 'activo'
            ]);
        }
    }
}