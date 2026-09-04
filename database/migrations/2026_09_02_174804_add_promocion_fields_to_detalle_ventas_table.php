<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->unsignedBigInteger('promocion_regla_id')->nullable()->after('id_insumo');
            $table->decimal('porcentaje_descuento_aplicado', 5, 2)->nullable()->after('precio_unitario');
            
            $table->foreign('promocion_regla_id')
                  ->references('id')
                  ->on('promociones_reglas')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->dropForeign(['promocion_regla_id']);
            $table->dropColumn(['promocion_regla_id', 'porcentaje_descuento_aplicado']);
        });
    }
};