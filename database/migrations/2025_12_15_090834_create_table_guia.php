<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableGuia extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('guias', function (Blueprint $table) {
            $table->id();
            $table->integer('numero_guia')->unique();
            $table->string('cliente');
            $table->integer('viaje_id');
            $table->string('rif');
            $table->string('direccion');
            $table->string('ruta');
            $table->string('buque');
            $table->integer('muelle');
            $table->string('precintos');
            $table->string('unidad');
            $table->string('cisterna');
            $table->string('conductor');
            $table->string('cedula');
            $table->integer('cantidad');
            $table->string('producto');
            $table->string('observaciones')->nullable();
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
        Schema::dropIfExists('guias');
    }
}
