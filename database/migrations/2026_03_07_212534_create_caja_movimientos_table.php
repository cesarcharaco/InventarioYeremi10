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
        Schema::create('caja_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_caja')->constrained('cajas'); 
            $table->foreignId('id_user')->constrained('users'); 
            $table->foreignId('id_credito')->nullable()->constrained('creditos');
            
            $table->enum('tipo', ['egreso', 'ingreso'])->default('egreso'); 
            $table->string('categoria'); 
            
            // Cambiamos monto y metodo_pago por campos de moneda específicos
            $table->decimal('efectivo_bs', 15, 2)->default(0);
            $table->decimal('efectivo_usd', 15, 2)->default(0);
            
            $table->text('observacion'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caja_movimientos');
    }
};
