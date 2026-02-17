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
        Schema::table('insumos', function (Blueprint $table) {
            // Primero agregamos las columnas como nullable
            $table->foreignId('categoria_id')->nullable()->after('stock_max');
            $table->foreignId('modelo_venta_id')->nullable()->after('categoria_id');
            
            // Los tres precios que calculamos
            $table->decimal('costo', 15, 2)->default(0); 
            $table->decimal('precio_venta_usd', 15, 2)->default(0);
            $table->decimal('precio_venta_bs', 15, 2)->default(0);
            $table->decimal('precio_venta_usdt', 15, 2)->default(0);
            // Luego las convertimos en FKs
            $table->foreign('categoria_id')->references('id')->on('categorias')->onDelete('set null');
            $table->foreign('modelo_venta_id')->references('id')->on('modelos_venta')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insumos', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropForeign(['modelo_venta_id']);
            $table->dropColumn(['categoria_id', 'modelo_venta_id']);
        });
    }
};
