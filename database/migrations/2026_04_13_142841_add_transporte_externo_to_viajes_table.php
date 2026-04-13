<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTransporteExternoToViajesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->boolean('es_transporte_externo')->default(false)->after('ayudante');
            $table->string('vehiculo_externo')->nullable()->after('es_transporte_externo');
            $table->string('chofer_externo')->nullable()->after('vehiculo_externo');
            $table->string('ayudante_externo')->nullable()->after('chofer_externo');
            $table->string('cisterna_externo')->nullable()->after('ayudante_externo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('viajes', function (Blueprint $table) {
            //
        });
    }
}
