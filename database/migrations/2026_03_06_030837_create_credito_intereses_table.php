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
        Schema::create('credito_intereses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_credito')->constrained('creditos');
            $table->foreignId('id_user')->constrained('users'); // Admin que aplicó
            $table->decimal('porcentaje', 5, 2); // 5.00 = 5%
            $table->decimal('monto_interes', 12, 2); // Calculado sobre saldo
            $table->decimal('saldo_anterior', 12, 2);
            $table->decimal('saldo_nuevo', 12, 2);
            $table->text('observacion')->nullable();
            $table->timestamp('aplicado_en');
            $table->enum('estado', ['aplicado', 'anulado'])->default('aplicado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credito_intereses');
    }
};
