<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cliente;
use App\Models\Local;
use Illuminate\Support\Facades\DB;

class ClienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtenemos los locales existentes (basado en tu tabla 'local')
        $locales = DB::table('local')->pluck('id')->toArray();

        if (empty($locales)) {
            $this->command->warn("No hay locales registrados. Por favor, registra locales antes de ejecutar este seeder.");
            return;
        }

        $nombres = [
            'Juan Pérez', 'María Rodríguez', 'Carlos Mendoza', 'Ana Colmenares',
            'Pedro Guillén', 'Laura Chacón', 'José Gregorio Torres', 'Carmen Uzcátegui',
            'Luis Alfredo Rivas', 'Elena Martínez', 'Roberto Sanz', 'Patricia Lira',
            'Miguel Ángel Castillo', 'Sofía Ramírez', 'Diego Fernández', 'Isabel Briceño'
        ];

        $contador = 0;

        foreach ($locales as $localId) {
            // Creamos 4 clientes por cada local
            for ($i = 1; $i <= 4; $i++) {
                if (isset($nombres[$contador])) {
                    Cliente::create([
                        'identificacion' => 'V-' . rand(10000000, 30000000), // Cédula aleatoria
                        'nombre'         => $nombres[$contador],
                        'telefono'       => '04' . rand(12, 26) . '-' . rand(1000000, 9999999),
                        'direccion'      => 'Sector ' . rand(1, 10) . ', Av. Principal, Venezuela',
                        'limite_credito' => rand(100, 500), // Límite entre 100$ y 500$
                        'id_local'       => $localId,
                        'activo'         => true,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                    $contador++;
                }
            }
            
            // Si ya llenamos los 16 nombres, detenemos el proceso
            if ($contador >= 16) break;
        }

        $this->command->info("Se han registrado 16 clientes (4 por tienda/local).");
    }
}