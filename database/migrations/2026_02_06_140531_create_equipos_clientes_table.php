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
        Schema::create('equipos_clientes', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('captacion_id');
            $table->string('tipo_equipo')->nullable();
            $table->string('identificador')->nullable();
            $table->longText('caracteristicas')->nullable();
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
        Schema::dropIfExists('equipos_clientes');
    }
};
