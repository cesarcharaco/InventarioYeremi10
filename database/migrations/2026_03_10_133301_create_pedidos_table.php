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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); 
            $table->foreignId('listas_oferta_id')->constrained(); 
            
            // Finanzas
            $table->decimal('total', 10, 2)->default(0);
            
            // Gestión de Estados
            $table->enum('estado', [
                'PENDIENTE', 
                'APROBADO', 
                'EN PREPARACIÓN', 
                'ENVIADO', 
                'ENTREGADO', 
                'CANCELADO'
            ])->default('PENDIENTE');

            // Datos de Logística (Se llenan en estado EN PREPARACIÓN / ENVIADO)
            $table->string('nro_guia')->nullable();
            $table->string('transporte')->nullable();
            $table->dateTime('fecha_despacho')->nullable();
            $table->dateTime('fecha_entrega')->nullable();

            // Notas
            $table->text('observaciones')->nullable(); // Notas del cliente al pedir
            $table->text('obs_entrega')->nullable();   // Notas/Reclamos al recibir el producto
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
