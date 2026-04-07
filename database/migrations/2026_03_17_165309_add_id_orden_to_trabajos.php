<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdOrdenToTrabajos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trabajos', function (Blueprint $table) {
            $table->integer('id_orden')->nullable();
            $table->integer('id_tempario_servicio')->nullable();
            $table->integer('id_categoria')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trabajos', function (Blueprint $table) {
            $table->dropColumn('id_orden');
            $table->dropColumn('id_tempario_servicio');
            $table->dropColumn('id_categoria');
        });
    }
}
