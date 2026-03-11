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
        Schema::create('insumos_mayores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lista_oferta_id')
                  ->constrained('listas_ofertas')
                  ->onDelete('cascade');
            $table->string('codigo')->unique(); // El 'serial' o código del proveedor
            $table->text('descripcion');
            $table->string('aplicativo')->nullable();
            $table->decimal('costo_usd', 12, 2);
            $table->decimal('venta_usd', 12, 2); // Precio con incremento calculado
            $table->string('estado')->default('activo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insumos_mayores');
    }
};
