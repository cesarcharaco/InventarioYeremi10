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
        Schema::create('detalles_entradas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_entrada');
            $table->unsignedBigInteger('id_insumo');
            $table->integer('cantidad');
            $table->decimal('costo_unitario_usd', 12, 2); // Costo real al momento de la compra
            $table->timestamps();

            $table->foreign('id_entrada')->references('id')->on('entradas_almacen')->onDelete('cascade');
            $table->foreign('id_insumo')->references('id')->on('insumos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_entradas');
    }
};
