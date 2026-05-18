<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductoFleteToViajes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->string('producto_flete')->nullable()->after('tipo_planificacion');
        });
    }

    public function down()
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->dropColumn('producto_flete');
        });
    }
}
