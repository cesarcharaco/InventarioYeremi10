<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ModeloVenta;

class ModeloVentaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    $modelos = [
        [
            'modelo' => 'GENERAL',
            'tasa_binance' => 600.00,
            'tasa_bcv' => 360.00,
            'factor_bcv' => 0.70,
            'factor_usdt' => 0.60,
            'porcentaje_extra' => null,
        ],
        [
            'modelo' => 'BAJO COSTO',
            'tasa_binance' => 600.00,
            'tasa_bcv' => 350.00,
            'factor_bcv' => 0.70,
            'factor_usdt' => 0.60,
            'porcentaje_extra' => null,
        ],
        [
            'modelo' => 'SECUNDARIOS',
            'tasa_binance' => 600.00,
            'tasa_bcv' => 350.00,
            'factor_bcv' => 0.70,
            'factor_usdt' => 0.60,
            'porcentaje_extra' => null,
        ],
        [
            'modelo' => 'MARGEN FIJO 10%',
            'tasa_binance' => 600.00,
            'tasa_bcv' => 360.00,
            'factor_bcv' => null,
            'factor_usdt' => null,
            'porcentaje_extra' => 0.10, // El 10% que mencionaste
        ],
    ];

    foreach ($modelos as $item) {
        \App\Models\ModeloVenta::updateOrCreate(
            ['modelo' => $item['modelo']], // Busca por nombre
            $item // Actualiza o crea con estos datos
        );
    }
}
}
