<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCapacidadAsignadaToInventarioStockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inventario_stock', function (Blueprint $table) {
            $table->decimal('capacidad_asignada', 10,0)->default(0)->after('cantidad_actual');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inventario_stock', function (Blueprint $table) {
            //
        });
    }
}
