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
        Schema::create('historial_mantenimiento', function (Blueprint $table) {
            $table->unsignedBigInteger('id_mantenimiento');
            $table->unsignedBigInteger('id_vehiculo');
            $table->unsignedBigInteger('id_servicio');
            $table->integer('kilometraje_mantenimiento');
            $table->date('fecha_mantenimiento');
            $table->text('observaciones')->nullable();
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
        Schema::dropIfExists('historial_mantenimiento');
    }
};
