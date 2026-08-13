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
        Schema::create('auditoria_sistema', function (Blueprint $table) {
            $table->id();
            $table->string('tabla_afectada', 100)->index();
            $table->enum('accion', ['INSERT', 'UPDATE', 'DELETE']);
            $table->unsignedBigInteger('registro_id');
            
            // Usamos tipos JSON para almacenar la data variable de cualquier tabla
            $table->json('valores_anteriores')->nullable();
            $table->json('valores_nuevos')->nullable();
            
            // Relación con el usuario que hace el cambio (opcional por si es del sistema)
            $table->unsignedBigInteger('id_user')->nullable()->index();
            
            // Reemplaza el timestamp por defecto por uno de precisión fina
            $table->timestamp('ejecutado_en')->useCurrent();

            // Si usas la tabla de usuarios nativa de Laravel (users), puedes activar la FK:
            // $table->foreign('id_user')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria_sistema');
    }
};