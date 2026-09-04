<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('promociones_reglas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('local_id'); // Identificador del local o sucursal
            $table->string('nombre');
            $table->enum('alcance', ['categoria', 'grupo', 'insumo']);
            $table->unsignedBigInteger('referencia_id');
            $table->decimal('porcentaje_descuento', 5, 2);
            $table->boolean('activo')->default(true);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->timestamps();

            // Opcional: Clave foránea si tu tabla de sucursales se llama 'locales'
            $table->foreign('local_id')->references('id')->on('local')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promociones_reglas');
    }
};