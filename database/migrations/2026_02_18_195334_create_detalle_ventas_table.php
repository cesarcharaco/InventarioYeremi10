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
        Schema::create('detalle_ventas', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('id_venta')->constrained('ventas')->onDelete('cascade');
            $blueprint->foreignId('id_insumo')->constrained('insumos')->onDelete('cascade');
            $blueprint->integer('cantidad');
            $blueprint->decimal('precio_unitario', 12, 2); // Precio en USD al momento de la venta
            $blueprint->decimal('subtotal', 12, 2);
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
    }
};
