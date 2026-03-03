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
        Schema::create('config_ofertas', function (Blueprint $table) {
            $table->id();
            
            // Relación con el local
            $table->unsignedBigInteger('id_local')->index();
            
            // El "por qué" de la oferta
            $table->string('motivo');
            
            // Lógica de expiración
            $table->enum('criterio_fin', ['manual', 'cierre_caja', 'fin_turno'])->default('manual');
            
            // Estado de la oferta
            $table->boolean('estado')->default(true)->index();
            
            // ID de la caja que estaba abierta al activar (para criterio 'cierre_caja')
            $table->unsignedBigInteger('id_caja_origen')->nullable();
            
            // Quién lo activó (Auditoría: Solo Admins)
            $table->unsignedBigInteger('id_usuario_admin');

            $table->timestamps();

            // Índices para optimizar la consulta de validación
            // Buscaremos: donde id_local = X AND estado = true
            $table->index(['id_local', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_ofertas');
    }
};
