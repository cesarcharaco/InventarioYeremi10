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
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user'); // Vendedor responsable
            $table->unsignedBigInteger('id_local');
            
            // Apertura
            $table->decimal('monto_apertura_usd', 12, 2)->default(0);
            $table->dateTime('fecha_apertura');

            // Cierre (Se llena al finalizar)
            $table->decimal('monto_cierre_usd_efectivo', 12, 2)->nullable();
            $table->decimal('monto_cierre_bs_efectivo', 12, 2)->nullable();
            $table->decimal('monto_cierre_punto', 12, 2)->nullable();
            $table->decimal('monto_cierre_pagomovil', 12, 2)->nullable();
            $table->dateTime('fecha_cierre')->nullable();
            
            $table->enum('estado', ['abierta', 'cerrada'])->default('abierta');
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users');
            $table->foreign('id_local')->references('id')->on('local');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};
