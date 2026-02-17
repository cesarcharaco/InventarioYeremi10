<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;

class CategoriaSeeder extends Seeder
{
    public function run()
    {
        $categorias = [
            ['categoria' => 'GENERAL'],
            ['categoria' => 'SECUNDARIO'],
            ['categoria' => 'PRODUCTOS BAJO COSTO'],
            ['categoria' => 'BICICLETA'],
            ['categoria' => 'CARROS'],
        ];

        foreach ($categorias as $cat) {
            Categoria::updateOrCreate(['categoria' => $cat['categoria']], $cat);
        }
    }
}