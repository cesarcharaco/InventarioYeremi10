<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminamos la tabla anterior para evitar conflictos de columnas
        Schema::dropIfExists('modelos_venta');

        Schema::create('modelos_venta', function (Blueprint $table) {
            $table->id();
            $table->string('modelo'); // Ejemplo: General, Bajo Costo
            
            // Tasas de cambio
            $table->decimal('tasa_binance', 15, 2)->default(0);
            $table->decimal('tasa_bcv', 15, 2)->default(0);
            
            // Campos mutuamente excluyentes
            $table->decimal('factor_bcv', 8, 2)->nullable(); // Ej: 0.7
            $table->decimal('factor_usdt', 8, 2)->nullable(); // Ej: 0.7
            $table->decimal('porcentaje_extra', 8, 2)->nullable(); // Ej: 0.10
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modelos_venta');
    }
};