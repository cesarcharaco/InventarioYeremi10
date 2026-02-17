<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('despacho_detalles', function (Blueprint $table) {
            $table->id();
            
            // Relación con Despachos (con borrado en cascada)
            $table->foreignId('id_despacho')
                  ->constrained('despachos')
                  ->onDelete('cascade');

            // Relación con Insumos (sin cascada para proteger integridad)
            $table->foreignId('id_insumo')
                  ->constrained('insumos');

            $table->integer('cantidad');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('despacho_detalles');
    }
};
