<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCostoDeliveryToCotizacionesBunkeringTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cotizaciones_bunkering', function (Blueprint $table) {
            $table->decimal('costo_delivery', 10, 2)->default(0)->after('total'); // Agrega la columna costo_delivery después de total
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cotizaciones_bunkering', function (Blueprint $table) {
            $table->dropColumn('costo_delivery'); // Elimina la columna costo_delivery
        });
    }
}
