<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDistanciaToHistorialGpsVehiculo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('historial_gps_vehiculo', function (Blueprint $table) {
            $table->decimal('distancia', 10, 4)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('historial_gps_vehiculo', function (Blueprint $table) {
            $table->dropColumn('distancia');
        });
    }
}
