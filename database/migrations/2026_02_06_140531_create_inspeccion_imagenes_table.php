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
        Schema::create('inspeccion_imagenes', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('inspeccion_id');
            $table->string('ruta_imagen', 500)->comment('Ruta de la imagen en el servidor');
            $table->string('descripcion')->nullable()->comment('Descripción opcional de la evidencia');
            $table->string('tipo_evidencia', 100)->nullable()->comment('Ej: entrada, salida, daño, etc.');
            $table->integer('orden')->default(0)->comment('Orden de visualización');
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
        Schema::dropIfExists('inspeccion_imagenes');
    }
};
