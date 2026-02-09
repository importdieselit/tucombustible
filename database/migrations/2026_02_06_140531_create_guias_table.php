<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('guias', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->integer('numero_guia')->nullable();
            $table->string('cliente')->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('contacto', 150)->nullable();
            $table->integer('viaje_id');
            $table->string('rif')->nullable();
            $table->string('direccion')->nullable();
            $table->string('ruta')->nullable();
            $table->string('buque')->nullable();
            $table->integer('buque_id')->nullable();
            $table->string('muelle', 40)->nullable();
            $table->string('precintos')->nullable();
            $table->string('unidad');
            $table->string('cisterna')->nullable();
            $table->string('conductor');
            $table->string('cedula')->nullable();
            $table->integer('cantidad');
            $table->string('producto')->nullable();
            $table->string('observaciones')->nullable();
            $table->timestamps();
            $table->integer('muelle_id')->nullable();
            $table->integer('cliente_id')->nullable();
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
};
