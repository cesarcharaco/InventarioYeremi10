<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insumos_recepcion', function (Blueprint $table) {
            $table->id();

            // Relación con el detalle original de la entrada
            $table->foreignId('id_detalle_entrada')
                  ->constrained('detalles_entradas')
                  ->onDelete('cascade');

            // Relación con el insumo
            $table->foreignId('id_insumo')
                  ->constrained('insumos')
                  ->onDelete('cascade');

            // Relación con el depósito/local de destino
            $table->foreignId('id_local')
                  ->constrained('local')
                  ->onDelete('cascade');

            $table->integer('cantidad');
            $table->decimal('costo_unitario_usd', 12, 2);
            $table->enum('origen', ['PROVEEDOR', 'CARGA_INICIAL', 'RETENCION_INTERNA'])->default('PROVEEDOR');
            $table->enum('estado', ['PENDIENTE', 'RETENIDO', 'PROCESADO', 'RECHAZADO'])->default('PENDIENTE');
            $table->string('observacion_recepcion')->nullable();
            $table->timestamps();

            // Índices para optimizar las consultas del controlador
            $table->index(['id_local', 'estado']);
            $table->index(['id_insumo', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumos_recepcion');
    }
};