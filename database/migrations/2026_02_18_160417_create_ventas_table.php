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
        Schema::create('ventas', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('codigo_factura')->unique();
            $table->foreignId('id_cliente')->constrained('clientes');
            $table->foreignId('id_user')->constrained('users');
            $table->foreignId('id_local')->constrained('local'); 
            $table->foreignId('id_caja')->constrained('cajas');
            $table->decimal('pago_usd_efectivo', 12, 2)->default(0);
            $table->decimal('pago_bs_efectivo', 12, 2)->default(0);
            $table->decimal('pago_punto_bs', 12, 2)->default(0);
            $table->decimal('pago_pagomovil_bs', 12, 2)->default(0);
            $table->decimal('monto_credito_usd', 12, 2)->default(0);
            $table->decimal('total_usd', 12, 2)->default(0);
            $table->enum('estado', ['completada', 'anulada'])->default('completada');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
