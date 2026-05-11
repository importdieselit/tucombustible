<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCamposMgoToDespachosViajesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('despachos_viajes', function (Blueprint $table) {
            $table->string('muelle_atraque')->nullable()->after('litros');
            $table->string('direccion_despacho')->nullable()->after('muelle_atraque');
            $table->unsignedBigInteger('buque_id')->nullable()->after('direccion_despacho');
            $table->string('buque_nombre_manual')->nullable()->after('buque_id');
            $table->string('imo', 50)->nullable()->after('buque_nombre_manual');
            $table->string('bandera', 50)->nullable()->after('imo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('despachos_viajes', function (Blueprint $table) {
            //
        });
    }
}
