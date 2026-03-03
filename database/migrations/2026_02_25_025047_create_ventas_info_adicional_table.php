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
        Schema::create('ventas_info_adicional', function (Blueprint $table) {
            $table->id();
            // Relación uno a uno con ventas
            $table->foreignId('id_venta')->constrained('ventas')->onDelete('cascade');
            
            // Control de Documento
            $table->enum('tipo_documento', ['factura', 'nota_entrega', 'sin_documento'])->default('nota_entrega');
            $table->string('correlativo_nota', 7)->nullable()->unique();
            
            // Lógica de Descuentos
            $table->integer('porcentaje_descuento')->default(0);
            $table->decimal('monto_descuento_usd', 12, 2)->default(0);
            
            // Lógica Fiscal (Base en Bs)
            $table->decimal('base_imponible_bs', 16, 2)->default(0);
            $table->decimal('iva_bs', 16, 2)->default(0);
            
            // Disparador para CxC
            $table->boolean('aplica_abono')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas_info_adicional');
    }
};
