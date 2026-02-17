<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersHasLocalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_has_local', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedbigInteger('id_user');
            $table->unsignedbigInteger('id_local');
            $table->enum('status',['Activo','Suspendido'])->default('Activo');

            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('users_has_local');
    }
}
