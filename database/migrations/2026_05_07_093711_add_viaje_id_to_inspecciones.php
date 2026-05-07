<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddViajeIdToInspecciones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inspecciones', function (Blueprint $table) {
            $table->integer('viaje_id')->unsigned();
            $table->foreign('viaje_id')->references('id')->on('viajes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inspecciones', function (Blueprint $table) {
            $table->dropColumn('viaje_id');
        });
    }
}
