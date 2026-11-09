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
            $table->decimal('subtotal_general', 10, 2)->default(0)->after('fecha_entrega'); // Agrega la columna subtotal_general después de fecha_entrega
            $table->decimal('costo_delivery', 10, 2)->default(0)->after('subtotal_general'); // Agrega la columna costo_delivery después de subtotal_general
            $table->decimal('total_general', 10, 2)->default(0)->after('costo_delivery'); // Agrega la columna total_general después de costo_delivery
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
            $table->dropColumn('subtotal_general'); // Elimina la columna subtotal_general
            $table->dropColumn('total_general'); // Elimina la columna total_general
        });
    }
}
