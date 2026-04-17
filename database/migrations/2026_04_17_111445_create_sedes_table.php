<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sedes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); 
            $table->unsignedBigInteger('estado_id');
            $table->unsignedBigInteger('ciudad_id');
            $table->text('direccion_especifica')->nullable();
            $table->boolean('estatus')->default(true);
            $table->timestamps();

            // Llaves foráneas para mantener la integridad
            $table->foreign('estado_id')->references('id')->on('estados');
            $table->foreign('ciudad_id')->references('id')->on('ciudades');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sedes');
    }
};