<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEstatusToSuministroComprasDetalle extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('suministros_compras_detalles', function (Blueprint $table) {
            $table->integer('estatus')->default(2);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('suministros_compras_detalles', function (Blueprint $table) {
            $table->dropColumn('estatus');
        });
    }
}
