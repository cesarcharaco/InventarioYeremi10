<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id(); // Estándar en Laravel 10 (equivalente a bigIncrements)
            $table->string('rif', 20)->unique();
            $table->string('razon_social', 191);
            $table->string('direccion')->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('contacto_vendedor', 191)->nullable();
            
            // Los Enums funcionan perfecto en L10
            $table->enum('status', ['Activo', 'Suspendido'])->default('Activo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
