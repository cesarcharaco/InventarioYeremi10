<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHistorialIncidenciasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historial_incidencias', function (Blueprint $table) {
            $table->id();
                    // Agregamos un índice al código porque harás muchas búsquedas por él
            $table->string('codigo')->index(); 
            
            // Acciones claras
            $table->enum('accion', ['creacion', 'edicion', 'anulacion']);
            
            // En Laravel 10/MySQL 8+, JSON es perfecto. 
            // Si usas una base de datos muy antigua, se puede cambiar a text, pero JSON es lo ideal.
            $table->json('datos_snapshot'); 
            
            $table->text('observacion_snapshot')->nullable();
            
            // Relación con el usuario que realiza la acción
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                    $table->timestamps();
                });
            }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('historial_incidencias');
    }
}
