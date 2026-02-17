<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInsumosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('insumos', function (Blueprint $table) {
            $table->id(); 
            $table->string('serial')->unique(); // Asumo que el serial debería ser único
            $table->string('producto');
            $table->text('descripcion')->nullable(); // Agregué nullable por si no hay descripción
            
            // Unsigned evita que el stock sea menor a 0 a nivel de base de datos
            $table->unsignedInteger('stock_min')->default(0);
            $table->unsignedInteger('stock_max')->default(0);
            
            // Nuestro campo de estado con ENUM
            $table->enum('estado', ['En Venta', 'Suspendido', 'No Disponible'])->default('En Venta');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('insumos');
    }
}
