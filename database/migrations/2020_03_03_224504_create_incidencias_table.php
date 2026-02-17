<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIncidenciasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('incidencias', function (Blueprint $table) {
            $table->id(); // Equivalente a bigIncrements('id')
            $table->string('codigo')->unique();
            // Relaciones modernas con foreignId
            // constrained('local') busca automáticamente la tabla 'local'
            $table->foreignId('id_insumo')->constrained('insumos')->onDelete('cascade');
            $table->foreignId('id_local')->constrained('local')->onDelete('cascade');
            
            $table->integer('cantidad');
            $table->enum('tipo', [
                'Dañado de Fábrica', 
                'Dañado en Local', 
                'Dañado y Devuelto', 
                'Perdido', 
                'Vencido',
                'Otro'
            ]);
            
            $table->text('observacion')->nullable();
            $table->date('fecha_incidencia');
            
            $table->timestamps(); // Crea created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('incidencias');
    }
}
