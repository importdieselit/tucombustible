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
        Schema::create('modulos', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('modulo');
            $table->string('ruta')->nullable();
            $table->string('icono')->nullable();
            $table->integer('orden')->default(0);
            $table->unsignedBigInteger('id_padre')->nullable();
            $table->timestamps();
            $table->tinyInteger('url_directa')->nullable();
            $table->tinyInteger('visible')->nullable();
            $table->string('descripcion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('modulos');
    }
};
