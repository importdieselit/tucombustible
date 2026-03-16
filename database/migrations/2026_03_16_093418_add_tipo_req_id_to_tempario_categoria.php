<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipoReqIdToTemparioCategoria extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tempario_categorias', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tipo_req')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tempario_categorias', function (Blueprint $table) {
            $table->drop('id_tipo_req');
        });
    }
}
