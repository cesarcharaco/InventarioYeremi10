<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Local;

class LocalTableSeeder extends Seeder
{
    public function run()
    {
        $locales = [
            // --- GUARIBE ---
            [
                'nombre' => 'Depósito Guaribe 1',
                'tipo'   => 'DEPOSITO',
                'estado' => 'Activo'
            ],
            [
                'nombre' => 'Depósito Guaribe 2',
                'tipo'   => 'DEPOSITO',
                'estado' => 'Activo'
            ],
            [
                'nombre' => 'Tienda Guaribe 1',
                'tipo'   => 'LOCAL',
                'estado' => 'Activo'
            ],
            [
                'nombre' => 'Tienda Guaribe 2',
                'tipo'   => 'LOCAL',
                'estado' => 'Activo'
            ],
            // --- VALLE DE GUANAPE ---
            [
                'nombre' => 'Tienda Valle de Guanape 1',
                'tipo'   => 'LOCAL',
                'estado' => 'Activo'
            ],
            [
                'nombre' => 'Tienda Valle de Guanape 2',
                'tipo'   => 'LOCAL',
                'estado' => 'Activo'
            ],
        ];

        foreach ($locales as $l) {
            Local::create($l);
        }
    }
}