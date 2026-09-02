<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entradas_almacen', function (Blueprint $table) {
            $table->enum('estado', ['PENDIENTE', 'APROBADO', 'ANULADO'])
                  ->default('PENDIENTE')
                  ->after('observaciones');
        });
    }

    public function down(): void
    {
        Schema::table('entradas_almacen', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};