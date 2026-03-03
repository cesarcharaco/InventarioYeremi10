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
        Schema::create('pago_referencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_venta')->constrained('ventas')->onDelete('cascade');
            
            // Método: pago_movil, transferencia, zelle, punto
            $table->string('metodo'); 
            $table->string('referencia');
            
            // Montos en su moneda respectiva
            $table->decimal('monto_bs', 16, 2)->default(0);
            $table->decimal('monto_usd', 12, 2)->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago_referencias');
    }
};
