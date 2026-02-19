<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(LocalTableSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(CategoriaSeeder::class);
        $this->call(ModeloVentaSeeder::class);
        $this->call(InsumosTableSeeder::class);
        $this->call(ClienteSeeder::class);
    }
}
