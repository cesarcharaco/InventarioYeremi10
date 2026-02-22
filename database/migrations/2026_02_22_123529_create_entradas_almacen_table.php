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
        Schema::create('entradas_almacen', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_proveedor');
            $table->unsignedBigInteger('id_local'); // A qué tienda entra
            $table->unsignedBigInteger('id_user'); // Quién recibe
            $table->string('nro_orden_entrega')->nullable(); // El nro del papel del proveedor
            $table->date('fecha_entrada');
            $table->decimal('total_costo_usd', 12, 2)->default(0); // Valor de la carga
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Foreign keys manteniendo el estilo de tu SQL
            $table->foreign('id_proveedor')->references('id')->on('proveedores');
            $table->foreign('id_local')->references('id')->on('local');
            $table->foreign('id_user')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entradas_almacen');
    }
};
