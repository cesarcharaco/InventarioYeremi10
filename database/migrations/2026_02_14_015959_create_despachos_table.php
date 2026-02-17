<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('despachos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique(); // Ej: DESP-2026-0001
            
            // Relaciones con la tabla local
            // Usamos constrained('local') para indicar explícitamente la tabla destino
            $table->foreignId('id_local_origen')->constrained('local');
            $table->foreignId('id_local_destino')->constrained('local');
            
            // Datos de logística
            $table->string('transportado_por');
            $table->string('vehiculo_placa')->nullable();
            $table->text('observacion')->nullable();
            
            // Control de flujo
            $table->enum('estado', ['Pendiente', 'En Tránsito', 'Recibido', 'Cancelado'])->default('En Tránsito');
            
            // Tiempos de logística
            $table->timestamp('fecha_despacho')->useCurrent();
            $table->timestamp('fecha_recepcion')->nullable(); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despachos');
    }
};