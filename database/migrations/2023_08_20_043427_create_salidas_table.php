<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalidasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salidas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_insumo');
            $table->unsignedBigInteger('id_local');
            $table->integer('cantidad');
            $table->enum('tipo_salida',['Venta','Fiao','Donación'])->default('Venta');
            $table->text('observacion')->nullable();

            $table->foreign('id_local')->references('id')->on('local')->onDelete('cascade');
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
        Schema::dropIfExists('salidas');
    }
}
