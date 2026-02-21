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
        Schema::create('autorizacion_pines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_local')->unique(); // Un registro por local
            $table->string('pin', 6);
            $table->decimal('monto', 12, 2);
            $table->string('vendedor');
            $table->string('cliente');
            $table->enum('estado', ['activo', 'usado'])->default('activo');
            $table->timestamps();

            $table->foreign('id_local')->references('id')->on('local')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('autorizacion_pines');
    }
};
