<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistorialGpsVehiculo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historial_gps_vehiculo', function (Blueprint $table) {
            $table->id();
            $table->integer('vehiculo_id');
            $table->decimal('latitud', 10,8);
            $table->decimal('longitud', 11,8);
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
        Schema::dropIfExists('historial_gps_vehiculo');
    }
}
