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
            $table->string('codigo')->unique();
            
            $table->foreignId('id_local_origen')->constrained('local');
            $table->foreignId('id_local_destino')->constrained('local');
            
            // Datos de logística de salida
            $table->string('transportado_por');
            $table->string('vehiculo_placa')->nullable();
            $table->text('observacion')->nullable(); // Nota de salida (Admin)
            
            // Nota de recepción (Encargado de tienda ante incidencias / ítems erróneos)
            $table->text('observacion_recepcion')->nullable(); 
            
            // Control de flujo
            $table->enum('estado', [
                'Pendiente', 
                'En Tránsito', 
                'Recibido', 
                'recibido_con_incidencias', 
                'Cancelado'
            ])->default('En Tránsito');
            
            // Tiempos
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