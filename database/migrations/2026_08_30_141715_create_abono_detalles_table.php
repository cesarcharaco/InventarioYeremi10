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
        Schema::create('abono_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_abono')->constrained('abonos_credito')->onDelete('cascade');
            $table->foreignId('id_credito')->constrained('creditos')->onDelete('cascade');
            $table->decimal('monto_aplicado_usd', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abono_detalles');
    }
};