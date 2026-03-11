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
        Schema::create('pedido_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained()->onDelete('cascade');
            $table->foreignId('insumos_mayores_id')->constrained(); 

            // Control de Fulfillment
            $table->integer('cantidad_solicitada'); // La intención del cliente
            $table->integer('cantidad_despachada')->default(0); // La realidad del almacén

            // Precio "Congelado" al momento de la compra
            $table->decimal('precio_unitario', 10, 2); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_detalles');
    }
};
