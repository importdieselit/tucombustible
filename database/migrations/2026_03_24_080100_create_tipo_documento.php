<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTipoDocumento extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tipo_documento', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion');
            $table->string('campo_fecha_vencimiento');
            $table->string('campo_ruta');
            $table->string('abreviatura');
            $table->integer('tipo');
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
        Schema::dropIfExists('tipo_documento');
    }
}
