<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehiculoFotosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vehiculo_fotos', function (Blueprint $table) {
            $table->id();
            $table->integer('vehiculo_id')->nullable()->unsigned();
            $table->foreign('vehiculo_id')->references('id')->on('vehiculos');
            $table->string('ruta');
            $table->boolean('es_principal')->default(false);
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
        Schema::dropIfExists('vehiculo_fotos');
    }
}
