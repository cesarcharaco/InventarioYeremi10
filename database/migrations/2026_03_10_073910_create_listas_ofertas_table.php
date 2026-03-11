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
        Schema::create('listas_ofertas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Ejemplo: "Oferta Repuestos Japoneses"
            $table->string('proveedor'); // Ejemplo: "SPACE VISION MOTORCYCLE"
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            
            // Monto mínimo de pedido en dólares
            $table->decimal('monto_minimo', 10, 2)->default(0); 
            $table->decimal('incremento', 10, 2)->default(0); 
            // Estado para controlar la visibilidad rápidamente
            $table->enum('estado', ['activo', 'caducado'])->default('activo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lista_ofertas');
    }
};
