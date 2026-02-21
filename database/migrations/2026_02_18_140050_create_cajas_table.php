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
            $table->decimal('monto_apertura_bs', 12, 2)->default(0);
            $table->dateTime('fecha_apertura');

            // Cierre (Se llena al finalizar)
            $table->decimal('monto_cierre_usd_efectivo', 12, 2)->nullable();
            $table->decimal('monto_cierre_bs_efectivo', 12, 2)->nullable();
            $table->decimal('monto_cierre_punto', 12, 2)->nullable();
            $table->decimal('monto_cierre_pagomovil', 12, 2)->nullable();
            //Lo que el vendedor tiene, para comparar pero los pagomoviles y transferencia no entran como reporte
            //ya que eso lo maneja el dueño del negocio
            $table->decimal('reportado_cierre_usd_efectivo', 12, 2)->nullable();// se cuenta el fisico usd
            $table->decimal('reportado_cierre_bs_efectivo', 12, 2)->nullable();// se cuenta el fisico bs
            $table->decimal('reportado_cierre_punto', 12, 2)->nullable();// se suman cierres de punto y biopago
            $table->dateTime('fecha_cierre')->nullable();
            
            $table->enum('estado', ['abierta', 'cerrada','anulada'])->default('abierta');
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
