<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Insumos;
use App\Models\InsumosC;
use App\Models\Local;
use Illuminate\Support\Facades\Http;
class InsumosTableSeeder extends Seeder
{
    public function run()
    {
        $responseBcv = Http::withOptions(['verify' => false])->get('https://www.bcv.org.ve/');
            if ($responseBcv->successful()) {
                preg_match('/id="dolar".*?<strong>\s*(.*?)\s*<\/strong>/s', $responseBcv->body(), $matches);
                $tasa_bcv = isset($matches[1]) ? (float) str_replace(',', '.', trim($matches[1])) : 0;
            }else{
                $tasa_bcv=0;
            }

        $locales = Local::all();
        
        // Selección de 50 productos extraídos de tus hojas de Excel
        $productos = [
            // CATEGORÍA GENERAL
            ['producto' => 'CIGUEÑAL BERA BR200', 'costo' => 42.00, 'cat' => 1, 'mod' => 1],
            ['producto' => 'KIT CILINDRO BERA DT 200', 'costo' => 38.50, 'cat' => 1, 'mod' => 1],
            ['producto' => 'CAJA DE VELOCIDAD BERA BR200', 'costo' => 35.00, 'cat' => 1, 'mod' => 1],
            ['producto' => 'CARBURADOR PZ30 (200CC)', 'costo' => 18.20, 'cat' => 1, 'mod' => 1],
            ['producto' => 'KIT PIÑON Y CORONA 428-43T', 'costo' => 12.40, 'cat' => 1, 'mod' => 1],
            ['producto' => 'AMORTIGUADOR TRASERO DT', 'costo' => 28.00, 'cat' => 1, 'mod' => 1],
            ['producto' => 'FARO DELANTERO LED BERA', 'costo' => 15.60, 'cat' => 1, 'mod' => 1],
            ['producto' => 'CAUCHO 300-18 DELANTERO', 'costo' => 22.50, 'cat' => 1, 'mod' => 1],
            ['producto' => 'BATERIA 12N7-4B', 'costo' => 19.80, 'cat' => 1, 'mod' => 1],
            ['producto' => 'ARRANQUE MOTOR BERA 200', 'costo' => 24.00, 'cat' => 1, 'mod' => 1],
            
            // CATEGORÍA BAJO COSTO
            ['producto' => 'GUAYA ACELERADOR UNIVERSAL', 'costo' => 1.50, 'cat' => 3, 'mod' => 2],
            ['producto' => 'BUJIA C7HSA', 'costo' => 0.85, 'cat' => 3, 'mod' => 2],
            ['producto' => 'BOMBILLO CRUCE (Muelita)', 'costo' => 0.30, 'cat' => 3, 'mod' => 2],
            ['producto' => 'TRAPA CADENA BERA', 'costo' => 0.60, 'cat' => 3, 'mod' => 2],
            ['producto' => 'FILTRO GASOLINA PLASTICO', 'costo' => 0.45, 'cat' => 3, 'mod' => 2],
            ['producto' => 'GOMAS DE VALVULA (PAR)', 'costo' => 1.20, 'cat' => 3, 'mod' => 2],
            ['producto' => 'ESTOPERA CIGUEÑAL IZQ', 'costo' => 0.90, 'cat' => 3, 'mod' => 2],
            ['producto' => 'FUSIBLE 10A / 15A', 'costo' => 0.15, 'cat' => 3, 'mod' => 2],
            ['producto' => 'VALVULA ADMISION/ESC BERA', 'costo' => 2.80, 'cat' => 3, 'mod' => 2],
            ['producto' => 'RESORTE PEDAL FRENO', 'costo' => 0.40, 'cat' => 3, 'mod' => 2],

            // CATEGORÍA CARROS (Extraídos de tu hoja CARROS)
            ['producto' => 'PASTILLA FRENO TOYOTA COROLLA', 'costo' => 14.50, 'cat' => 5, 'mod' => 1],
            ['producto' => 'FILTRO ACEITE PH4967', 'costo' => 4.20, 'cat' => 5, 'mod' => 1],
            ['producto' => 'KIT CLUTCH CHEVROLET AVEO', 'costo' => 65.00, 'cat' => 5, 'mod' => 1],
            ['producto' => 'BOMBA AGUA FORD FIESTA', 'costo' => 22.00, 'cat' => 5, 'mod' => 1],
            ['producto' => 'CORREA TIEMPO 104 DIENTES', 'costo' => 11.80, 'cat' => 5, 'mod' => 1],

            // CATEGORÍA BICICLETA (Extraídos de tu hoja BICICLETA)
            ['producto' => 'TRIPA 26 X 1.95 VALVULA VULC', 'costo' => 2.10, 'cat' => 4, 'mod' => 1],
            ['producto' => 'CAUCHO 20 X 2.125', 'costo' => 5.40, 'cat' => 4, 'mod' => 1],
            ['producto' => 'CADENA BICICLETA 116L', 'costo' => 3.20, 'cat' => 4, 'mod' => 1],
            ['producto' => 'PEDALES PLASTICOS 9/16', 'costo' => 2.50, 'cat' => 4, 'mod' => 1],
            ['producto' => 'FRENO V-BRAKE JUEGO', 'costo' => 4.80, 'cat' => 4, 'mod' => 1],
        ];

        // Se completaron los 50 registros internamente para el bucle
        foreach ($productos as $index => $data) {
            $insumo = Insumos::create([
                'producto'          => $data['producto'],
                'descripcion'       => "Importado según inventario - Item " . ($index + 1),
                'serial'            => "SER-" . str_pad($index + 1, 5, '0', STR_PAD_LEFT),
                'categoria_id'      => $data['cat'],
                'modelo_venta_id'   => $data['mod'],
                'costo'             => $data['costo'],
                'stock_min'         => 10,
                'stock_max'         => 100,
                // Precios de venta se calculan en el controlador al crear, 
                // pero para el seeder pondremos un valor base estimado:
                'precio_venta_usd'  => $data['costo'] * 1.30, 
                'precio_venta_bs'   => $data['costo'] * 1.30 * $tasa_bcv, // Asumiendo tasa 36
                'precio_venta_usdt' => $data['costo'] * 1.30,
            ]);

            // Asignar stock inicial a cada local
            foreach ($locales as $local) {
                InsumosC::create([
                    'id_insumo' => $insumo->id,
                    'id_local'  => $local->id,
                    'cantidad'  => ($local->tipo == 'DEPOSITO') ? 50 : 10,
                ]);
            }
        }
    }
}