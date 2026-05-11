<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategoriaToOrdenes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ordenes', function (Blueprint $table) {
            // Cambiamos 'id_cliente' por 'id_usuario' que sí existe en la tabla
            $table->bigInteger('id_tipo_req')->unsigned()->nullable()->after('id_usuario');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ordenes', function (Blueprint $table) {
            //
        });
    }
}
