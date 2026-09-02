<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historico_insumos_recepcion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_detalle_entrada');
            $table->unsignedBigInteger('id_insumo');
            $table->decimal('costo_anterior', 12, 2);
            $table->unsignedBigInteger('id_modelo_venta_anterior')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historico_insumos_recepcion');
    }
};