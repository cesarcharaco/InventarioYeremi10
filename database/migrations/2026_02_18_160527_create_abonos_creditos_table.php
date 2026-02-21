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
        Schema::create('abonos_creditos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_credito')->constrained('creditos')->onDelete('cascade');
            $table->foreignId('id_user')->constrained('users'); // Quién recibió el pago
            $table->foreignId('id_caja')->constrained('cajas'); // <--- CRÍTICO: Entra a la jornada actual
            
            $table->decimal('monto_pagado_usd', 12, 2);
                    
            // Desglose de cómo pagó el cliente (Efectivo, Bs, Pago Móvil, etc)
            $table->decimal('pago_usd_efectivo', 12, 2)->default(0);
            $table->decimal('pago_bs_efectivo', 12, 2)->default(0);
            $table->decimal('pago_punto_bs', 12, 2)->default(0);
            $table->decimal('pago_pagomovil_bs', 12, 2)->default(0);
            
            $table->string('detalles')->nullable();
            $table->enum('estado', ['Realizado', 'Anulado'])->default('Realizado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abonos_creditos');
    }
};
