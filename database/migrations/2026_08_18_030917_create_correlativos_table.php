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
        
        Schema::create('correlativos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_factura'); // Ej: 000136
            $table->string('numero_control'); // Ej: 00-000136
            $table->enum('estado', ['disponible', 'usado', 'anulado'])->default('disponible');
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->onDelete('set null');
            $table->timestamp('fecha_uso')->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correlativos');
    }
};
