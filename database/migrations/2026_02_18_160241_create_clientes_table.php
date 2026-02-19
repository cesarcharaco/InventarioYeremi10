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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            // En Venezuela: V-12345678 o J-12345678
            $table->string('identificacion')->unique(); 
            $table->string('nombre');
            $table->string('telefono');
            $table->string('direccion')->nullable();
            
            // Importante para tu módulo de créditos
            $table->decimal('limite_credito', 12, 2)->default(0); 
            
            // Saber en qué sede se registró inicialmente
            $table->foreignId('id_local')->constrained('local'); 
            
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Al ser una tabla base, ten cuidado de no borrarla si tiene ventas asociadas
        Schema::dropIfExists('clientes');
    }
};