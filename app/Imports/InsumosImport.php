<?php

namespace App\Imports;

use App\Models\InsumosMayor;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InsumosImport implements ToModel, WithHeadingRow
{
    private $lista_id;
    private $incremento;

    // Solo añadimos el constructor para recibir el ID
    public function __construct($lista_id,$incremento)
    {
        $this->lista_id = $lista_id;
        $this->incremento = $incremento;

    }
    // Esto le dice al paquete que el encabezado está en la fila 9
    public function headingRow(): int
    {
        return 10; 
    }
    public function model(array $row)
    {
        //dd($row);
        // Aplicamos el incremento (ejemplo: 10% adicional)
        $costo = (float) ($row[3] ?? 0); // Ajusta 'precio' al nombre exacto de la columna en tu Excel
        
        
        return new InsumosMayor([
            'lista_oferta_id' => $this->lista_id,
            'codigo'      => (string) $row[0],
            'descripcion' => $row[1],
            'aplicativo'  => $row[2],
            'costo_usd'   => $costo,
            'venta_usd'   => round(($costo / $this->incremento), 2),
            'estado'      => 'activo'
        ]);
    }
}
