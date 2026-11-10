<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnidadToCotizacionBunkeringDetalles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cotizacion_bunkering_detalles', function (Blueprint $table) {
            $table->string('unidad', 5)->default('MT')->after('tipo_producto');
            $table->decimal('cantidad_solicitada', 12, 3)->default(0)->after('unidad');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cotizacion_bunkering_detalles', function (Blueprint $table) {
            $table->dropColumn('unidad');
            $table->dropColumn('cantidad_solicitada');
        });
    }
}
