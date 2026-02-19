<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Local;                
use Illuminate\Support\Facades\Hash; 
class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. CREAR EL SUPERADMIN (Dueño/Gerente)
        $superAdmin = User::create([
            'name'     => 'Gerente General',
            'cedula'   => 'V-11111111',
            'telefono' => '04121111111',
            'email'    => 'admin@sayer.com',
            'password' => Hash::make('admin123'),
            'role'     => User::ROLE_SUPERADMIN, // Usando tu constante
            'activo'   => true,
        ]);

        // 2. CREAR EL ENCARGADO (Administrador)
        $admin = User::create([
            'name'     => 'Encargado de Tienda',
            'cedula'   => 'V-22222222',
            'telefono' => '04122222222',
            'email'    => 'encargado@sayer.com',
            'password' => Hash::make('tienda123'),
            'role'     => User::ROLE_ENCARGADO, // Usando tu constante
            'activo'   => true,
        ]);

        // 3. VENDEDOR
        $vendedor = User::create([
            'name'     => 'Vendedor Repuestos',
            'cedula'   => 'V-33333333',
            'telefono' => '04123333333',
            'email'    => 'ventas@sayer.com',
            'password' => Hash::make('ventas123'),
            'role'     => User::ROLE_VENDEDOR,
            'activo'   => true,
        ]);

        // 4. ALMACENISTA
        $almacen = User::create([
            'name'     => 'Control de Almacén',
            'cedula'   => 'V-44444444',
            'telefono' => '04124444444',
            'email'    => 'almacen@sayer.com',
            'password' => Hash::make('almacen123'),
            'role'     => User::ROLE_ALMACENISTA,
            'activo'   => true,
        ]);

        // 1. Obtener los locales para asignar (usando los nombres del LocalTableSeeder)
            $depositoGuaribe = Local::where('nombre', 'Depósito Guaribe 1')->first();
            $tiendaGuaribe   = Local::where('nombre', 'Tienda Guaribe 1')->first();
            $tiendaValle     = Local::where('nombre', 'Tienda Valle de Guanape 1')->first();

        // 2. Vinculación lógica
            if ($tiendaGuaribe) {
                // El SuperAdmin y el Administrador (Encargado) operan en la tienda principal
                $superAdmin->local()->attach($tiendaGuaribe->id, ['status' => 'Activo']);
                $admin->local()->attach($tiendaGuaribe->id, ['status' => 'Activo']);
                
                // El Vendedor también lo asignamos a la tienda de Guaribe
                $vendedor->local()->attach($tiendaGuaribe->id, ['status' => 'Activo']);
            }

            if ($depositoGuaribe) {
                // El Almacenista lo vinculamos directamente al depósito
                $almacen->local()->attach($depositoGuaribe->id, ['status' => 'Activo']);
            }

            // Ejemplo: Si quieres que el SuperAdmin también tenga acceso a Valle de Guanape 
            // pero no como local activo (status Suspendido o simplemente cargarlo después)
            if ($tiendaValle) {
                $superAdmin->local()->attach($tiendaValle->id, ['status' => 'Suspendido']);
            }
    }
    
}
