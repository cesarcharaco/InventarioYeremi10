<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('despacho_detalles', function (Blueprint $table) {
            $table->id();
            
            // Relación con Despachos (con borrado en cascada)
            $table->foreignId('id_despacho')
                  ->constrained('despachos')
                  ->onDelete('cascade');

            // Relación con Insumos
            $table->foreignId('id_insumo')
                  ->constrained('insumos');

            // Control de cantidades separadas para auditoría exacta
            $table->integer('cantidad_enviada');
            $table->integer('cantidad_recibida')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despacho_detalles');
    }
};