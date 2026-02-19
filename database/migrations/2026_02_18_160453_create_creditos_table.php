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
        Schema::create('creditos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_venta');
            $table->unsignedBigInteger('id_cliente');
            
            // El monto inicial es el total de la venta menos lo que haya dado de inicial
            $table->decimal('monto_inicial', 12, 2); 
            $table->decimal('saldo_pendiente', 12, 2); 
            
            $table->date('fecha_vencimiento');
            $table->enum('estado', ['pendiente', 'pagado', 'vencido', 'revalorizado'])->default('pendiente');
            
            // Auditoría de revalorización
            $table->timestamp('ultima_revalorizacion')->nullable();
            $table->decimal('tasa_cambio_origen', 12, 2)->nullable(); // Opcional: Tasa del día de venta
            
            $table->timestamps();

            $table->foreign('id_venta')->references('id')->on('ventas')->onDelete('cascade');
            $table->foreign('id_cliente')->references('id')->on('clientes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};