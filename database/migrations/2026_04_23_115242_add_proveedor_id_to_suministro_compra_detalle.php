<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProveedorIdToSuministroCompraDetalle extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('suministros_compras_detalles', function (Blueprint $table) {
            $table->integer('proveedor_id');
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
            $table->dropColumn('proveedor_id');
        });
    }
}
