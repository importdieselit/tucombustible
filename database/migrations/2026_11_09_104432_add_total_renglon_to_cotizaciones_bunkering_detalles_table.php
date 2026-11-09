<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalRenglonToCotizacionesBunkeringDetallesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cotizaciones_bunkering_detalles', function (Blueprint $table) {
            $table->decimal('total_renglon', 14, 2)->nullable()->after('costo_delivery'); // Agrega la columna total_renglon después de costo_delivery
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cotizaciones_bunkering_detalles', function (Blueprint $table) {
            $table->dropColumn('total_renglon'); // Elimina la columna total_renglon
        });
    }
}
